<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\Auth\Guard;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Auth;

class UserLogoutController extends Controller
{
    protected $auth;

    public function __construct(Guard $auth)
    {
        $this->auth = $auth;
    }

    /**
     * Log a user out and redirect them
     *
     * @return mixed
     */
    public function doLogout()
    {
        $this->auth->logout();
        Auth::logout();
        Session::flush();
        return redirect()->to('https://lobby.globalwomanclub.com/oauth?app_id=' . config('services.lobby_app.app_id') . '&logout=true&link=' . config('app.url'));
    }
}
