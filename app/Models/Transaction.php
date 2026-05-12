<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'customer_id',
        'type',
        'circulation_type',
        'direction',
        'amount',
        'reference_id',
        'description',
        'metadata'
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}
