<?php

namespace App\Models;

use App\Enums\EventStatus;
use Illuminate\Database\Eloquent\Attributes\Guarded;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Guarded([])]
class Event extends Model
{
    use HasUlids, HasFactory, SoftDeletes;

    protected $casts = [
        'status'     => EventStatus::class,
        'event_date' => 'datetime',
        'sell_end_date' => 'date',
        'is_physical' => 'boolean',
        'is_age_restricted' => 'boolean',
        'charge_transaction_fee' => 'boolean',
        'is_visible' => 'boolean',
        'is_blocked' => 'boolean',
        'is_on_hold' => 'boolean',
        'is_free' => 'boolean',
        'is_featured' => 'boolean',
        'is_upcoming' => 'boolean',
        'sponsors' => 'array',
        'highlights' => 'array',
        'meta_data' => 'array',
    ];


    public function organizer()
    {
        return $this->belongsTo(User::class, "user_id");
    }

    public function tickets()
    {
        return $this->hasMany(Ticket::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function attendees()
    {
        return $this->hasMany(Attendee::class);
    }

    public function participants()
    {
        return $this->hasMany(Participant::class);
    }

    public function form()
    {
        return $this->hasOne(EventForm::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function paymentGateway()
    {
        return $this->belongsTo(PaymentGateway::class);
    }
}
