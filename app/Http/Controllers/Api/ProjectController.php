<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Offer;
use App\Models\Project;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProjectController extends Controller
{
    /**
     * عرض قائمة المشاريع المفتوحة مع إمكانية التصفية حسب الفئة أو الميزانية.
     * مخصص لواجهة المستقل لتصفح الفرص المتاحة.
     */
    public function openProjects(Request $request)
    {
        $query = Project::with(['category', 'client'])
            ->where('status', 'open');

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->integer('category_id'));
        }

        if ($request->filled('min_budget')) {
            $query->where('budget', '>=', $request->float('min_budget'));
        }

        if ($request->filled('max_budget')) {
            $query->where('budget', '<=', $request->float('max_budget'));
        }

        $projects = $query->latest()->paginate(10);

        return response()->json($projects);
    }

    /**
     * عرض تفاصيل مشروع واحد مع العروض المرتبطة به (للمراجعة قبل التقديم).
     */
    public function show(Project $project)
    {
        $project->load([
            'category',
            'client',
            'offers.freelancer.freelancerProfile',
            'acceptedOffer',
        ]);

        return response()->json($project);
    }

    /**
     * قبول عرض معين من قِبل العميل والدفع من المحفظة مع حجز المبلغ.
     */
    public function acceptOffer(Project $project, Offer $offer, Request $request)
    {
        $user = $request->user();

        if ($user->role !== 'client' || $project->client_id !== $user->id) {
            return response()->json(['message' => 'Only the project owner (client) can accept offers'], 403);
        }

        if ($project->status !== 'open') {
            return response()->json(['message' => 'Project is not open'], 422);
        }

        if ($offer->project_id !== $project->id || $offer->status !== 'pending') {
            return response()->json(['message' => 'Invalid offer'], 422);
        }

        $wallet = $user->wallet;
        if (! $wallet || $wallet->balance < $offer->amount) {
            return response()->json(['message' => 'Insufficient wallet balance'], 422);
        }

        DB::transaction(function () use ($project, $offer, $wallet) {
            $wallet->decrement('balance', $offer->amount);

            Transaction::create([
                'wallet_id' => $wallet->id,
                'type' => 'payment',
                'amount' => $offer->amount,
                'status' => 'completed',
                'reference_type' => 'project',
                'reference_id' => $project->id,
                'details' => ['offer_id' => $offer->id],
            ]);

            // تحديث حالة العرض المختار ورفض البقية
            $offer->update(['status' => 'accepted']);
            Offer::where('project_id', $project->id)
                ->where('id', '<>', $offer->id)
                ->update(['status' => 'rejected']);

            $project->update([
                'accepted_offer_id' => $offer->id,
                'status' => 'in_progress',
            ]);
        });

        return response()->json(['message' => 'Offer accepted and payment captured']);
    }

    /**
     * إنهاء المشروع وتحويل المبلغ للمستقل.
     */
    public function complete(Project $project, Request $request)
    {
        $user = $request->user();

        if ($user->role !== 'client' || $project->client_id !== $user->id) {
            return response()->json(['message' => 'Only the project owner (client) can complete the project'], 403);
        }

        if ($project->status !== 'in_progress' || ! $project->acceptedOffer) {
            return response()->json(['message' => 'Project is not in progress'], 422);
        }

        $offer = $project->acceptedOffer;
        $freelancerWallet = $offer->freelancer->wallet;

        DB::transaction(function () use ($project, $offer, $freelancerWallet) {
            $freelancerWallet->increment('balance', $offer->amount);

            Transaction::create([
                'wallet_id' => $freelancerWallet->id,
                'type' => 'deposit',
                'amount' => $offer->amount,
                'status' => 'completed',
                'reference_type' => 'project',
                'reference_id' => $project->id,
                'details' => ['offer_id' => $offer->id],
            ]);

            $project->update(['status' => 'completed']);
        });

        return response()->json(['message' => 'Project completed and payment released']);
    }

    /**
     * إلغاء المشروع قبل البدء (أو أثناء التنفيذ) مع استرجاع المبلغ للعميل.
     */
    public function cancel(Project $project, Request $request)
    {
        $user = $request->user();

        if ($user->role !== 'client' || $project->client_id !== $user->id) {
            return response()->json(['message' => 'Only the project owner (client) can cancel the project'], 403);
        }

        if (in_array($project->status, ['completed', 'cancelled'], true)) {
            return response()->json(['message' => 'Project is already finished'], 422);
        }

        // إذا لم يتم قبول عرض بعد، مجرد إلغاء.
        if (! $project->acceptedOffer || $project->status === 'open') {
            $project->update(['status' => 'cancelled']);
            return response()->json(['message' => 'Project cancelled']);
        }

        $offer = $project->acceptedOffer;
        $clientWallet = $user->wallet;

        DB::transaction(function () use ($project, $offer, $clientWallet) {
            $clientWallet->increment('balance', $offer->amount);

            Transaction::create([
                'wallet_id' => $clientWallet->id,
                'type' => 'refund',
                'amount' => $offer->amount,
                'status' => 'completed',
                'reference_type' => 'project',
                'reference_id' => $project->id,
                'details' => ['offer_id' => $offer->id],
            ]);

            $project->update(['status' => 'cancelled']);
            $offer->update(['status' => 'rejected']);
        });

        return response()->json(['message' => 'Project cancelled and refunded']);
    }

    /**
     * عرض المشاريع المكتملة للمستقل مع التقييمات.
     */
    public function completedProjects(Request $request)
    {
        $user = $request->user();

        if ($user->role !== 'freelancer') {
            return response()->json(['message' => 'Only freelancers can view their completed projects'], 403);
        }

        $projects = Project::with([
                'category',
                'client',
                'acceptedOffer',
                'reviews',
            ])
            ->whereHas('acceptedOffer', function ($query) use ($user) {
                $query->where('freelancer_id', $user->id);
            })
            ->where('status', 'completed')
            ->latest()
            ->paginate(10);

        return response()->json($projects);
    }
}

