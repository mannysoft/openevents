<?php

namespace App\Services;

use GuzzleHttp\Client;
use Log;

class LobbyAuth
{
    /**
     * How long to wait for the Lobby: to connect, and in all. A Lobby that does not answer
     * must not hold a guest's registration until PHP-FPM kills the request.
     */
    public const CLIENT_OPTIONS = ['connect_timeout' => 3, 'timeout' => 5];

    /**
     * The HTTP client for every call to the Lobby. Resolved from the container, so a test
     * can answer for the Lobby without a request leaving the machine.
     */
    public static function httpClient(): Client
    {
        return app(Client::class, ['config' => self::CLIENT_OPTIONS]);
    }

    public function getAccessToken()
    {
        $client = self::httpClient();
        $result = $client->post('https://lobby.globalwomanclub.com/api/auth', [
            'form_params' => [
                "server_key" => config('services.lobby_app.server_key'),
                "username" => 'admin',
                // "password" => "|o7@b7'4N~I*22y=",
                'password' => ".9=}296B{uf1NJoq[#cmG",

            ],
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.13) Gecko/20080311 Firefox/2.0.0.13',
                'Accept'     => 'application/json',
            ]
        ]);

        $resData = $result->getBody();

        // Log::info($resData);

        $json = json_decode($resData, true);

        if (isset($json['access_token'])) {
            return $json['access_token'];
        } else {
            return null;
        }
    }
}
