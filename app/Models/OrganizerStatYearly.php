<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrganizerStatYearly extends Model
{
    protected $table = 'organizer_stats_yearly';

    protected $fillable = ['organizer_id', 'year', 'total_sold'];
}
