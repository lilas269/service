<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FreelancerProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class FreelancerProfileController extends Controller
{
    /**
     * عرض ملف التعريف للمستقل الحالي.
     */
    public function show(Request $request)
    {
        $user = $request->user();

        if ($user->role !== 'freelancer') {
            return response()->json(['message' => 'Only freelancers can view their profile'], 403);
        }

        $profile = FreelancerProfile::with(['user', 'portfolioItems.category'])
            ->where('user_id', $user->id)
            ->first();

        if (! $profile) {
            return response()->json(['message' => 'Profile not found'], 404);
        }

        return response()->json($profile);
    }

    /**
     * تحديث ملف التعريف للمستقل الحالي.
     */
    public function update(Request $request)
    {
        $user = $request->user();

        if ($user->role !== 'freelancer') {
            return response()->json(['message' => 'Only freelancers can update their profile'], 403);
        }

        $validator = Validator::make($request->all(), [
            'display_name' => 'nullable|string|max:191',
            'title' => 'nullable|string|max:191',
            'bio' => 'nullable|string|max:2000',
            'skills' => 'nullable|string|max:1000',
            'hourly_rate' => 'nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $profile = FreelancerProfile::where('user_id', $user->id)->first();

        if (! $profile) {
            return response()->json(['message' => 'Profile not found'], 404);
        }

        $profile->update($request->only([
            'display_name',
            'title',
            'bio',
            'skills',
            'hourly_rate',
        ]));

        return response()->json([
            'message' => 'Profile updated successfully',
            'profile' => $profile->fresh(),
        ]);
    }
}

