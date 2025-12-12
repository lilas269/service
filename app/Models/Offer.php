<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Offer extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'freelancer_id',
        'amount',
        'delivery_days',
        'cover_message',
        'status', // 'pending', 'accepted', 'rejected'
    ];

    // --- العلاقات ---

    /**
     * علاقة العرض بالمشروع الذي قُدم عليه.
     * للاستدعاء: $offer->project
     */
    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * علاقة العرض بالمستقل الذي قدمه.
     * للاستدعاء: $offer->freelancer
     */
    public function freelancer()
    {
        return $this->belongsTo(User::class, 'freelancer_id');
    }
}