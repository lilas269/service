<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    use HasFactory;

    /**
     * الحقول التي يمكن تعبئتها.
     */
    protected $fillable = [
        'project_id',
        'client_id',    // في قاعدة بياناتك اسمه reviewer_id
        'freelancer_id',
        'rating',       // رقم من 1 إلى 5
        'comment',
    ];

    // --- العلاقات ---

    /**
     * علاقة التقييم بالمشروع الذي تم تقييمه.
     * للاستدعاء: $review->project
     */
    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * علاقة التقييم بالعميل الذي كتبه.
     * للاستدعاء: $review->client
     */
    public function client()
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    /**
     * علاقة التقييم بالمستقل الذي تم تقييمه.
     * للاستدعاء: $review->freelancer
     */
    public function freelancer()
    {
        return $this->belongsTo(User::class, 'freelancer_id');
    }
}