<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'credit_id',
        'amount_paid',
        'payment_date',
        'method',
    ];

    protected $casts = [
        'payment_date' => 'date',
    ];

    public function credit()
    {
        return $this->belongsTo(Credit::class);
    }
}
