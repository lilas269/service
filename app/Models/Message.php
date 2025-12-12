<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    use HasFactory;

    /**
     * الحقول التي يمكن تعبئتها.
     */
    protected $fillable = [
        'project_id',
        'sender_id',
        'receiver_id', // في قاعدة بياناتك اسمه recipient_id
        'content',     // في قاعدة بياناتك اسمه body
    ];

    // --- العلاقات ---

    /**
     * علاقة الرسالة بالمشروع الذي تنتمي إليه.
     * للاستدعاء: $message->project
     */
    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * علاقة الرسالة بالمستخدم الذي أرسلها.
     * للاستدعاء: $message->sender
     */
    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    /**
     * علاقة الرسالة بالمستخدم الذي استقبلها.
     * للاستدعاء: $message->receiver
     */
    public function receiver()
    {
        return $this->belongsTo(User::class, 'receiver_id');
    }
}