<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Guarded;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Guarded([])]
class Attendee extends Model
{
    use HasUlids, HasFactory;

    protected $casts = [
        'entry_status' => 'boolean',
        'entry_time'   => 'datetime',
        'meta_data'    => 'array',
    ];

    public function orderItem()
    {
        return $this->belongsTo(OrderItem::class);
    }
    public function event()
    {
        return $this->belongsTo(Event::class);
    }
}