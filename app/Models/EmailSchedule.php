<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

class EmailSchedule extends MyBaseModel
{
    use SoftDeletes;

    protected $table = 'email_schedules';

    /**
     *
     * The attributes that are mass assignable.
     * @var array $fillable
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'mobile_no',
        'event_id',
        'club',
        'type',
        'date_send',
    ];
}
