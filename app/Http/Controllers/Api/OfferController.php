<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Offer;
use App\Models\Project;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class OfferController extends Controller
{
    /**
     * إنشاء عرض جديد من قبل المستقل على مشروع مفتوح.
     */
    public function store(Request $request, Project $project)
    {
        $user = $request->user();

        if ($user->role !== 'freelancer') {
            return response()->json(['message' => 'Only freelancers can place offers'], 403);
        }

        if ($project->status !== 'open') {
            return response()->json(['message' => 'Project is not open for offers'], 422);
        }

        if ($project->client_id === $user->id) {
            return response()->json(['message' => 'You cannot bid on your own project'], 422);
        }

        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:1',
            'delivery_days' => 'required|integer|min:1',
            'cover_message' => 'nullable|string|max:2000',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        // منع تكرار العروض على نفس المشروع من نفس المستقل
        $existing = Offer::where('project_id', $project->id)
            ->where('freelancer_id', $user->id)
            ->first();

        if ($existing) {
            return response()->json(['message' => 'You already submitted an offer for this project'], 422);
        }

        $offer = Offer::create([
            'project_id' => $project->id,
            'freelancer_id' => $user->id,
            'amount' => $request->amount,
            'delivery_days' => $request->delivery_days,
            'cover_message' => $request->cover_message,
        ]);

        return response()->json(['message' => 'Offer submitted successfully', 'offer' => $offer], 201);
    }

    /**
     * استعراض عروض المستقل مع حالة كل عرض والمشروع المرتبط به.
     */
    public function myOffers(Request $request)
    {
        $user = $request->user();

        if ($user->role !== 'freelancer') {
            return response()->json(['message' => 'Only freelancers can access their offers'], 403);
        }

        $offers = Offer::with(['project.category'])
            ->where('freelancer_id', $user->id)
            ->latest()
            ->paginate(10);

        return response()->json($offers);
    }

    /**
     * عرض جميع العروض المقدمة على مشروع معين (من وجهة نظر العميل).
     */
    public function projectOffers(Project $project, Request $request)
    {
        $user = $request->user();

        if ($user->role !== 'client' || $project->client_id !== $user->id) {
            return response()->json(['message' => 'Only the project owner can view its offers'], 403);
        }

        $offers = Offer::with(['freelancer.freelancerProfile'])
            ->where('project_id', $project->id)
            ->orderBy('amount', 'asc')
            ->get();

        return response()->json([
            'project' => $project->load('category'),
            'offers' => $offers,
            'offers_count' => $offers->count(),
        ]);
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
}

