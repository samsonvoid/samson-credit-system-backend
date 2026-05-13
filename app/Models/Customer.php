<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Customer extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens;

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

    protected $hidden = [
        'password',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }

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

    /**
     * Recalculate and update current_balance from all active credits
     */
    public function recalculateBalance()
    {
        $totalOutstanding = $this->credits()
            ->where('status', 'active')
            ->get()
            ->sum(function ($credit) {
                return $credit->balance;
            });
        
        $this->current_balance = $totalOutstanding;
        $this->save();
        
        return $this->current_balance;
    }
}
