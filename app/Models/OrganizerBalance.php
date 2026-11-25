<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Guarded;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Guarded([])]
class OrganizerBalance extends Model
{
    use HasUlids, HasFactory;

    protected $casts = [
        'total_sales'       => 'decimal:2',
        'available_amount'  => 'decimal:2',
        'withdrawn_amount'  => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
