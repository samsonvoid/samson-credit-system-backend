<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PendingPayment extends Model
{
    protected $fillable = [
        'credit_id',
        'payment_ref',
        'amount',
        'initiated_at',
        'confirmed_at',
        'customer_phone',
        'status',
        'admin_id',
        'rejected_reason',
        'rejected_at',
    ];

    protected $casts = [
        'initiated_at' => 'datetime',
        'confirmed_at' => 'datetime',
        'rejected_at' => 'datetime',
    ];

    public function credit()
    {
        return $this->belongsTo(Credit::class);
    }

    // Time since initiation in human readable format
    public function getTimeSinceInitiationAttribute()
    {
        if (!$this->initiated_at) return 'N/A';
        
        $diff = now()->diff($this->initiated_at);
        
        if ($diff->i < 1) return 'Hivi punde';
        if ($diff->i < 60) return $diff->i . ' dakika zilizopita';
        if ($diff->h < 24) return $diff->h . ' saa zilizopita';
        return $diff->d . ' siku zilizopita';
    }

    // Check if this is a fresh payment (within 30 minutes)
    public function isFresh()
    {
        return $this->initiated_at && now()->diffInMinutes($this->initiated_at) <= 30;
    }

    // Check if this is suspicious (too quick or too slow)
    public function isSuspicious()
    {
        if (!$this->initiated_at || !$this->confirmed_at) return false;
        
        $diff = $this->confirmed_at->diffInMinutes($this->initiated_at);
        
        // Less than 1 minute = suspicious (should take time to pay)
        // More than 60 minutes = suspicious (took too long)
        return $diff < 1 || $diff > 60;
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeConfirmed($query)
    {
        return $query->where('status', 'confirmed');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }
}