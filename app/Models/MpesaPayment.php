<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MpesaPayment extends Model
{
    protected $fillable = [
        'credit_id',
        'checkout_request_id',
        'phone',
        'amount',
        'status',
        'response'
    ];

    protected $casts = [
        'amount' => 'decimal:2'
    ];

    public function credit()
    {
        return $this->belongsTo(Credit::class);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }
}