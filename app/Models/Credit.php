<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Credit extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'amount',
        'type',
        'description',
        'status',
        'due_date',
    ];

    protected $casts = [
        'due_date' => 'date',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    // Dynamic attribute to calculate how much is left to pay on this specific credit
    public function getBalanceAttribute()
    {
        return $this->amount - $this->payments()->sum('amount_paid');
    }
}
