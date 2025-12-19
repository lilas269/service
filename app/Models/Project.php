<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'category_id',
        'title',
        'description',
        'budget',
        'duration_days',
        'status', // 'open', 'in_progress', 'completed', 'cancelled'
        'accepted_offer_id',
    ];

    // --- العلاقات ---

    /**
     * علاقة المشروع بالعميل الذي أنشأه (المشروع يتبع لعميل واحد).
     * للاستدعاء: $project->client
     */
    public function client()
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    /**
     * علاقة المشروع بالتصنيف الذي ينتمي إليه.
     * للاستدعاء: $project->category
     */
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * علاقة المشروع بالعروض المقدمة عليه (المشروع له عدة عروض).
     * للاستدعاء: $project->offers
     */
    public function offers()
    {
        return $this->hasMany(Offer::class);
    }

    /**
     * علاقة المشروع بالعرض الذي تم قبوله.
     * للاستدعاء: $project->acceptedOffer
     */
    public function acceptedOffer()
    {
        return $this->belongsTo(Offer::class, 'accepted_offer_id');
    }
    
    /**
     * علاقة المشروع بالرسائل المتبادلة فيه.
     * للاستدعاء: $project->messages
     */
    public function messages()
    {
        return $this->hasMany(Message::class);
    }
    
    /**
     * علاقة المشروع بالتقييمات المقدمة عليه.
     * للاستدعاء: $project->reviews
     */
    public function reviews()
    {
        return $this->hasMany(Review::class);
    }
}