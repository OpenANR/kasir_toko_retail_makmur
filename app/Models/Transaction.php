<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'transaction_number',
        'user_id',
        'customer_id',
        'customer_name',
        'customer_phone',
        'customer_type',
        'subtotal',
        'discount_percent',
        'discount_amount',
        'total_discount',
        'tax',
        'other_fee',
        'grand_total',
        'paid_amount',
        'change_amount',
        'payment_method',
        'status',
        'note',
    ];

    protected $casts = [
        'subtotal' => 'float',
        'discount_percent' => 'float',
        'discount_amount' => 'float',
        'total_discount' => 'float',
        'tax' => 'float',
        'other_fee' => 'float',
        'grand_total' => 'float',
        'paid_amount' => 'float',
        'change_amount' => 'float',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function details()
    {
        return $this->hasMany(TransactionDetail::class);
    }
}
