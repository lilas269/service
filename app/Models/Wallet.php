<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Wallet extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'balance',
        'currency',
    ];

    // --- العلاقات ---

    /**
     * علاقة المحفظة بصاحبها (المستخدم).
     * للاستدعاء: $wallet->user
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * علاقة المحفظة بالمعاملات المالية التي تمت عليها.
     * للاستدعاء: $wallet->transactions
     */
    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }
}