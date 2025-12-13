<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Offer;
use App\Models\Project;
use Illuminate\Http\Request;
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
}

