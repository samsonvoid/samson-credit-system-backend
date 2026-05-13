<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentInitiation extends Model
{
    protected $fillable = [
        'credit_id',
        'customer_phone',
        'payment_ref',
        'amount',
        'initiated_at',
        'confirmed_at',
        'status',
        'notes',
    ];

    protected $casts = [
        'initiated_at' => 'datetime',
        'confirmed_at' => 'datetime',
    ];

    public function credit()
    {
        return $this->belongsTo(Credit::class);
    }

    // Time difference in minutes since initiation
    public function getMinutesSinceInitiationAttribute()
    {
        return $this->initiated_at ? now()->diffInMinutes($this->initiated_at) : 0;
    }

    // Check if initiation has expired (30 minutes)
    public function isExpired()
    {
        return $this->minutes_since_initiation > 30;
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending_verification');
    }

    public function scopeAwaitingConfirmation($query)
    {
        return $query->where('status', 'awaiting_admin_confirmation');
    }
}