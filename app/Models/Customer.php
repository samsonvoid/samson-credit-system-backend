<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;

class Customer extends Model
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'business_name',
        'business_type',
        'location',
        'email',
        'phone',
        'password',
        'trust_score',
        'credit_limit',
        'current_balance',
    ];

    /**
     * Get the credits for the customer.
     */
    public function credits()
    {
        return $this->hasMany(Credit::class);
    }

    /**
     * Get the transactions for the customer.
     */
    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }
}
