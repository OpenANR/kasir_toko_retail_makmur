<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'barcode',
        'name',
        'price',
        'cost_price',
        'stock',
        'category',
        'unit',
    ];

    protected $casts = [
        'price' => 'float',
        'cost_price' => 'float',
        'stock' => 'integer',
    ];

    public function transactionDetails()
    {
        return $this->hasMany(TransactionDetail::class);
    }
}
