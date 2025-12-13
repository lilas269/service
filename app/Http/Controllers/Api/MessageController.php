<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Message;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class MessageController extends Controller
{
    /**
     * جلب المحادثة الخاصة بمشروع بعد قبول العرض.
     */
    public function index(Project $project, Request $request)
    {
        if (!$this->userCanAccessProject($project, $request->user()->id)) {
            return response()->json(['message' => 'You are not allowed to view this chat'], 403);
        }

        $messages = Message::where('project_id', $project->id)
            ->with(['sender', 'receiver'])
            ->orderBy('created_at')
            ->get();

        return response()->json($messages);
    }

    /**
     * إرسال رسالة بين العميل والمستقل بعد قبول العرض.
     */
    public function store(Project $project, Request $request)
    {
        $user = $request->user();

        if (!$this->userCanAccessProject($project, $user->id)) {
            return response()->json(['message' => 'You are not allowed to send messages for this project'], 403);
        }

        $validator = Validator::make($request->all(), [
            'content' => 'required|string|max:3000',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $receiverId = $this->resolveReceiverId($project, $user->id);

        if (!$receiverId) {
            return response()->json(['message' => 'Chat is not available for this project'], 422);
        }

        $message = Message::create([
            'project_id' => $project->id,
            'sender_id' => $user->id,
            'receiver_id' => $receiverId,
            'content' => $request->content,
        ]);

        return response()->json(['message' => 'Message sent', 'data' => $message], 201);
    }

    /**
     * يتحقق من إمكانية المستخدم المشاركة في محادثة المشروع.
     */
    private function userCanAccessProject(Project $project, int $userId): bool
    {
        $freelancerId = optional($project->acceptedOffer)->freelancer_id;

        return $project->client_id === $userId
            || ($freelancerId && $freelancerId === $userId);
    }

    /**
     * تحديد المستقبل بناءً على دور المستخدم الحالي.
     */
    private function resolveReceiverId(Project $project, int $senderId): ?int
    {
        $freelancerId = optional($project->acceptedOffer)->freelancer_id;

        if (!$freelancerId) {
            return null;
        }

        if ($project->client_id === $senderId) {
            return $freelancerId;
        }

        return $project->client_id;
    }
}

