<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'wallet_id',
        'type', // 'deposit', 'payment', 'refund', 'withdraw'
        'amount',
        'status', // 'pending', 'completed', 'failed'
        'reference_type', // 'project', 'offer'
        'reference_id',
        'details',
    ];

    protected $casts = [
        'details' => 'json', // لتحويل حقل التفاصيل إلى JSON تلقائياً
    ];

    // --- العلاقات ---

    /**
     * علاقة المعاملة بالمحفظة التي تمت فيها.
     * للاستدعاء: $transaction->wallet
     */
    public function wallet()
    {
        return $this->belongsTo(Wallet::class);
    }
}