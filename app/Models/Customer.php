<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'phone',
        'member_number',
        'type',
        'discount_percent',
        'address',
    ];

    protected $casts = [
        'discount_percent' => 'float',
    ];

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }
}
