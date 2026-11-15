<?php

namespace App\Models;

/**
 * Description of Message.
 *
 * @author Dave
 */
class Signup extends MyBaseModel
{
    protected $table = 'Wo_Clickfunnel_Event_Data_New';

    /**
     *
     * The attributes that are mass assignable.
     * @var array $fillable
     */
    protected $fillable = [
        'email_id',
        'full_name',
        'created',
        'mobile_no',
        'club',
        'event_id',
    ];
}
