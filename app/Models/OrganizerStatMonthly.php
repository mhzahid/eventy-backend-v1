<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrganizerStatMonthly extends Model
{
    protected $table = 'organizer_stats_monthly';

    protected $fillable = ['organizer_id', 'year', 'month', 'total_sold'];
}
