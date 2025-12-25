<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Offer;
use App\Models\Project;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ProjectController extends Controller
{
    /**
     * إنشاء مشروع جديد من قبل العميل.
     */
    public function store(Request $request)
    {
        $user = $request->user();

        if ($user->role !== 'client') {
            return response()->json(['message' => 'Only clients can create projects'], 403);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:191',
            'description' => 'required|string|max:5000',
            'category_id' => 'required|exists:categories,id',
            'budget' => 'required|numeric|min:1',
            'duration_days' => 'nullable|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $project = Project::create([
            'client_id' => $user->id,
            'category_id' => $request->category_id,
            'title' => $request->title,
            'description' => $request->description,
            'budget' => $request->budget,
            'duration_days' => $request->duration_days,
            'status' => 'open',
        ]);

        return response()->json([
            'message' => 'Project created successfully',
            'project' => $project->load(['category', 'client']),
        ], 201);
    }

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
     * تحديث مشروع (فقط إذا كان مفتوحاً ولم يتم قبول أي عرض).
     */
    public function update(Request $request, Project $project)
    {
        $user = $request->user();

        if ($user->role !== 'client' || $project->client_id !== $user->id) {
            return response()->json(['message' => 'Only the project owner can update it'], 403);
        }

        // لا يمكن تعديل المشروع إذا تم قبول عرض أو إذا لم يكن مفتوحاً
        if ($project->status !== 'open' || $project->accepted_offer_id !== null) {
            return response()->json([
                'message' => 'Project cannot be updated. It must be open and have no accepted offers'
            ], 422);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|string|max:191',
            'description' => 'sometimes|string|max:5000',
            'category_id' => 'sometimes|exists:categories,id',
            'budget' => 'sometimes|numeric|min:1',
            'duration_days' => 'nullable|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $project->update($request->only([
            'title',
            'description',
            'category_id',
            'budget',
            'duration_days',
        ]));

        return response()->json([
            'message' => 'Project updated successfully',
            'project' => $project->fresh()->load(['category', 'client']),
        ]);
    }

    /**
     * حذف مشروع (فقط إذا كان مفتوحاً ولم يتم قبول أي عرض).
     */
    public function destroy(Project $project, Request $request)
    {
        $user = $request->user();

        if ($user->role !== 'client' || $project->client_id !== $user->id) {
            return response()->json(['message' => 'Only the project owner can delete it'], 403);
        }

        // لا يمكن حذف المشروع إذا تم قبول عرض أو إذا لم يكن مفتوحاً
        if ($project->status !== 'open' || $project->accepted_offer_id !== null) {
            return response()->json([
                'message' => 'Project cannot be deleted. It must be open and have no accepted offers'
            ], 422);
        }

        // حذف جميع العروض المرتبطة أولاً (سيتم حذفها تلقائياً بسبب cascade delete)
        $project->delete();

        return response()->json(['message' => 'Project deleted successfully']);
    }

    /**
     * عرض قائمة مشاريع العميل مع إمكانية التصفية حسب الحالة.
     */
    public function myProjects(Request $request)
    {
        $user = $request->user();

        if ($user->role !== 'client') {
            return response()->json(['message' => 'Only clients can view their projects'], 403);
        }

        $query = Project::with(['category', 'acceptedOffer.freelancer'])
            ->where('client_id', $user->id);

        // تصفية حسب الحالة
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // تصفية حسب التصنيف
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->integer('category_id'));
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
     * إنهاء المشروع وتحويل المبلغ للمستقل.
     */
    public function completeProject(Project $project, Request $request)
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
            // تحويل المبلغ من حساب العميل (المحجوز) إلى حساب المستقل
            $freelancerWallet->increment('balance', $offer->amount);

            // تسجيل معاملة إيداع للمستقل
            Transaction::create([
                'wallet_id' => $freelancerWallet->id,
                'type' => 'deposit',
                'amount' => $offer->amount,
                'status' => 'completed',
                'reference_type' => 'project',
                'reference_id' => $project->id,
                'details' => ['offer_id' => $offer->id],
            ]);

            // تغيير حالة المشروع إلى completed
            $project->update(['status' => 'completed']);
        });

        return response()->json([
            'message' => 'Project completed and payment released to freelancer',
            'project' => $project->fresh()->load(['acceptedOffer.freelancer']),
        ]);
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

