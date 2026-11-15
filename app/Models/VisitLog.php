<?php

namespace App\Models;

/**
 * Description of Message.
 *
 * @author Dave
 */
class VisitLog extends MyBaseModel
{
    protected $table = 'Wo_Clickfunnel_Event_visit_logs';

    /**
     *
     * The attributes that are mass assignable.
     * @var array $fillable
     */
    protected $fillable = [
        'email_id',
        'first_name',
        'last_name',
        'created',
        'mobile_no',
        'club',
        'event_id',
        'app_user_type',
        'is_attended_event',
    ];

    public function purchase()
    {
        dd($this->id);

        // dd($this->event_id);
        // return $this->hasOne(EventPurchase::class, 'email_id', 'email_id');

        return $this->hasOne(EventPurchase::class, 'email_id', 'email_id')
            ->where('event_id', 2239);

        return $this->hasOne(EventPurchase::class, 'email_id', 'email_id')
            ->where('event_id', 2239);
    }

    public function scopeFirstDay($query)
    {
        return $query->where('date_created', now()->format('Y-m-d'))
            ->where('first_day_checked', false);
    }

    public function scopeSecondDay($query)
    {
        return $query->where('date_created', now()->subDay()->format('Y-m-d'))
            ->where('second_day_checked', false);
    }
}
