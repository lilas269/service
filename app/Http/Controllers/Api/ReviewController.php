<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FreelancerProfile;
use App\Models\Project;
use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ReviewController extends Controller
{
    /**
     * إنشاء تقييم لمشروع مكتمل من قِبل العميل.
     */
    public function store(Project $project, Request $request)
    {
        $user = $request->user();

        if ($user->role !== 'client' || $project->client_id !== $user->id) {
            return response()->json(['message' => 'Only the project owner (client) can review'], 403);
        }

        if ($project->status !== 'completed' || ! $project->acceptedOffer) {
            return response()->json(['message' => 'Project must be completed before review'], 422);
        }

        $validator = Validator::make($request->all(), [
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:2000',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        // منع تكرار التقييم لنفس المشروع
        $existing = Review::where('project_id', $project->id)->first();
        if ($existing) {
            return response()->json(['message' => 'Project already reviewed'], 422);
        }

        $freelancerId = $project->acceptedOffer->freelancer_id;

        $review = Review::create([
            'project_id' => $project->id,
            'client_id' => $user->id,
            'freelancer_id' => $freelancerId,
            'rating' => $request->rating,
            'comment' => $request->comment,
        ]);

        $this->updateFreelancerStats($freelancerId);

        return response()->json(['message' => 'Review submitted', 'review' => $review], 201);
    }

    /**
     * تحديث متوسط تقييم المستقل وعدد التقييمات.
     */
    private function updateFreelancerStats(int $freelancerId): void
    {
        $profile = FreelancerProfile::where('user_id', $freelancerId)->first();

        if (! $profile) {
            return;
        }

        $average = Review::where('freelancer_id', $freelancerId)->avg('rating');
        $count = Review::where('freelancer_id', $freelancerId)->count();

        $profile->update([
            'average_rating' => round($average ?? 0, 2),
            'total_reviews' => $count,
        ]);
    }
}

