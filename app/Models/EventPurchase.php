<?php

namespace App\Models;

class EventPurchase extends MyBaseModel
{
    protected $table = 'Wo_Clickfunnel_Event_Purchase';

    protected $fillable = [
        'event_id',
        'email_id',
    ];

    /**
     * The event associated with the message.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function event()
    {
        return $this->belongsTo(Event::class);
    }
}
