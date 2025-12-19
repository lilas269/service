<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FreelancerPortfolio;
use App\Models\FreelancerProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class FreelancerPortfolioController extends Controller
{
    /**
     * عرض جميع أعمال المستقل (Portfolio Items).
     */
    public function index(Request $request)
    {
        $user = $request->user();

        if ($user->role !== 'freelancer') {
            return response()->json(['message' => 'Only freelancers can view their portfolio'], 403);
        }

        $profile = FreelancerProfile::where('user_id', $user->id)->first();

        if (! $profile) {
            return response()->json(['message' => 'Profile not found'], 404);
        }

        $portfolioItems = FreelancerPortfolio::with('category')
            ->where('profile_id', $profile->id)
            ->latest('completion_date')
            ->paginate(10);

        return response()->json($portfolioItems);
    }

    /**
     * إضافة عمل جديد لمعرض أعمال المستقل.
     */
    public function store(Request $request)
    {
        $user = $request->user();

        if ($user->role !== 'freelancer') {
            return response()->json(['message' => 'Only freelancers can add portfolio items'], 403);
        }

        $profile = FreelancerProfile::where('user_id', $user->id)->first();

        if (! $profile) {
            return response()->json(['message' => 'Profile not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:191',
            'description' => 'nullable|string|max:2000',
            'image_url' => 'nullable|url|max:500',
            'project_url' => 'nullable|url|max:500',
            'category_id' => 'nullable|exists:categories,id',
            'completion_date' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $portfolioItem = FreelancerPortfolio::create([
            'profile_id' => $profile->id,
            'title' => $request->title,
            'description' => $request->description,
            'image_url' => $request->image_url,
            'project_url' => $request->project_url,
            'category_id' => $request->category_id,
            'completion_date' => $request->completion_date,
        ]);

        return response()->json([
            'message' => 'Portfolio item added successfully',
            'portfolio_item' => $portfolioItem->load('category'),
        ], 201);
    }

    /**
     * تحديث عمل موجود في معرض الأعمال.
     */
    public function update(Request $request, FreelancerPortfolio $freelancerPortfolio)
    {
        $user = $request->user();

        if ($user->role !== 'freelancer') {
            return response()->json(['message' => 'Only freelancers can update portfolio items'], 403);
        }

        $profile = FreelancerProfile::where('user_id', $user->id)->first();

        if (! $profile || $freelancerPortfolio->profile_id !== $profile->id) {
            return response()->json(['message' => 'Portfolio item not found or unauthorized'], 404);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'nullable|string|max:191',
            'description' => 'nullable|string|max:2000',
            'image_url' => 'nullable|url|max:500',
            'project_url' => 'nullable|url|max:500',
            'category_id' => 'nullable|exists:categories,id',
            'completion_date' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $freelancerPortfolio->update($request->only([
            'title',
            'description',
            'image_url',
            'project_url',
            'category_id',
            'completion_date',
        ]));

        return response()->json([
            'message' => 'Portfolio item updated successfully',
            'portfolio_item' => $freelancerPortfolio->fresh()->load('category'),
        ]);
    }

    /**
     * حذف عمل من معرض الأعمال.
     */
    public function destroy(Request $request, FreelancerPortfolio $freelancerPortfolio)
    {
        $user = $request->user();

        if ($user->role !== 'freelancer') {
            return response()->json(['message' => 'Only freelancers can delete portfolio items'], 403);
        }

        $profile = FreelancerProfile::where('user_id', $user->id)->first();

        if (! $profile || $freelancerPortfolio->profile_id !== $profile->id) {
            return response()->json(['message' => 'Portfolio item not found or unauthorized'], 404);
        }

        $freelancerPortfolio->delete();

        return response()->json(['message' => 'Portfolio item deleted successfully']);
    }
}

