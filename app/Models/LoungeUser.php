<?php

namespace App\Models;

class LoungeUser extends MyBaseModel
{
    protected $connection = 'mysql_lounge';

    protected $table = 'users';
}
