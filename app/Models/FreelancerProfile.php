<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FreelancerProfile extends Model
{
    use HasFactory;

    /**
     * الحقول التي يمكن تعبئتها.
     */
    protected $fillable = [
        'user_id',
        'title',          // المسمى الوظيفي
        'bio',            // النبذة التعريفية
        'skills',         // المهارات (يمكن أن تكون نصاً أو JSON)
        'hourly_rate',
        'average_rating',
        'total_reviews',
        'display_name', // اسم العرض للمستقل
    ];

    /**
     * تحويل أنواع البيانات.
     */
    protected $casts = [
        // إذا قررت تخزين المهارات كـ JSON
        // 'skills' => 'array',
    ];

    // --- العلاقات ---

    /**
     * علاقة ملف التعريف بالمستخدم (المستقل) صاحب الملف.
     * للاستدعاء: $profile->user
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * علاقة ملف التعريف بمعرض أعمال المستقل.
     * للاستدعاء: $profile->portfolioItems
     */
    public function portfolioItems()
    {
        return $this->hasMany(FreelancerPortfolio::class, 'profile_id');
    }
}