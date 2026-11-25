<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Guarded;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Guarded([])]
class PaymentGateway extends Model
{
    use HasFactory;

    protected $casts = [
        'transaction_fee' => 'decimal:2',
        'is_active' => 'boolean',

    ];

    public function events()
    {
        return $this->hasMany(Event::class);
    }
}
