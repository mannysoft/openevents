<?php

namespace App\Models;

class LobbyUser extends MyBaseModel
{
    protected $connection = 'mysql_lobby';

    protected $primaryKey = 'user_id';

    protected $table = 'Wo_Users';
}
