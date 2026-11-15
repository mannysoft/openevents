<?php

namespace App\Models;

class LobbyPoint extends MyBaseModel
{
    protected $connection = 'mysql_lobby';

    protected $table = 'points';
}
