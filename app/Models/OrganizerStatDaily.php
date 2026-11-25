<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrganizerStatDaily extends Model
{
    protected $table = 'organizer_stats_daily';

    protected $fillable = ['organizer_id', 'stat_date', 'total_sold'];

    protected $casts = ['stat_date' => 'date'];
}
