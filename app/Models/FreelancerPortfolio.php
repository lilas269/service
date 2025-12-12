<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FreelancerPortfolio extends Model
{
    use HasFactory;

    // اسم الجدول في قاعدة البيانات يختلف عن اسم الموديل، لذا يجب تحديده
    protected $table = 'freelancer_portfolio';

    /**
     * الحقول التي يمكن تعبئتها.
     */
    protected $fillable = [
        'profile_id',
        'title',
        'description',
        'image_url',
        'project_url',
        'category_id',
        'completion_date',
    ];

    /**
     * تحويل أنواع البيانات.
     */
    protected $casts = [
        'completion_date' => 'date',
    ];

    // --- العلاقات ---

    /**
     * علاقة عنصر معرض الأعمال بملف تعريف المستقل الذي يتبعه.
     * للاستدعاء: $portfolioItem->profile
     */
    public function profile()
    {
        return $this->belongsTo(FreelancerProfile::class, 'profile_id');
    }

    /**
     * علاقة عنصر معرض الأعمال بالتصنيف الذي ينتمي إليه (اختياري).
     * للاستدعاء: $portfolioItem->category
     */
    public function category()
    {
        return $this->belongsTo(Category::class);
    }
}