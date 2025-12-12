<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * الحقول التي يمكن تعبئتها.
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role', // 'client', 'freelancer', 'admin'
    ];

    /**
     * الحقول التي يجب إخفاؤها عند تحويل الموديل إلى مصفوفة أو JSON.
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * تحويل أنواع البيانات.
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    // --- العلاقات ---

    /**
     * علاقة المستخدم بمحفظته (كل مستخدم له محفظة واحدة).
     * للاستدعاء: $user->wallet
     */
    public function wallet()
    {
        return $this->hasOne(Wallet::class);
    }

    /**
     * علاقة المستخدم (كمستقل) بملف تعريفه (له ملف واحد).
     * للاستدعاء: $user->freelancerProfile
     */
    public function freelancerProfile()
    {
        return $this->hasOne(FreelancerProfile::class);
    }

    /**
     * علاقة المستخدم (كعميل) بمشاريعه (له عدة مشاريع).
     * للاستدعاء: $user->projectsAsClient
     */
    public function projectsAsClient()
    {
        return $this->hasMany(Project::class, 'client_id');
    }

    /**
     * علاقة المستخدم (كمستقل) بالعروض التي قدمها.
     * للاستدعاء: $user->offersAsFreelancer
     */
    public function offersAsFreelancer()
    {
        return $this->hasMany(Offer::class, 'freelancer_id');
    }
    
    /**
     * علاقة المستخدم (كمستقل) بالمشاريع التي يعمل عليها.
     * للاستدعاء: $user->projectsAsFreelancer
     */
    public function projectsAsFreelancer()
    {
        return $this->hasManyThrough(Project::class, Offer::class, 'freelancer_id', 'accepted_offer_id', 'id', 'id');
    }
}