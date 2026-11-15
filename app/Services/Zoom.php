<?php

namespace App\Services;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\DB;

class Zoom
{
    public function getAccessToken($purchaseId)
    {
        // events.Wo_Clickfunnel_Event_Purchase, 8498 8446, event_id then get event in events table. get events.club

        // Get the details of
        $purchase = DB::table('Wo_Clickfunnel_Event_Purchase')
            ->where('id', $purchaseId)
            ->first();

        $zoomId = null;

        if ($purchase) {

            $event = DB::table('events')
                ->where('id', $purchase->event_id)
                ->first();

            if ($event) {
                $event->club;
                $event->event_ttype; // Online

                $zoomUrl = DB::table('Wo_Clickfunnel_ZoomEvent_Data')
                    ->where('event_id', $purchase->event_id)
                    ->first();

                if ($zoomUrl) {
                    $zoomId = $zoomUrl->zoom_id;
                }
            }
        }

        // Get Token
        $client = new Client();
        $result = $client->post('https://zoom.us/oauth/token', [
            'form_params' => [
                'grant_type' => 'account_credentials',
                'account_id' => 'K7QBTo-oRXuszaSMeIjTDA',
            ],
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.13) Gecko/20080311 Firefox/2.0.0.13',
                'Accept' => 'application/json',
                'Authorization' => 'Basic Q0dZbTU5NjFTTW1SbzRzTzhDTG9nUTpZN3RCRUpSMXN6akRQUEh0ZEh2MVA0TG9PTERuQXJINw=='
            ]
        ]);

        $resData = $result->getBody();

        $json = json_decode($resData, true);

        $accessToken = null;
        if (isset($json['access_token'])) {
            $accessToken = $json['access_token'];
        }

        // Get the first_name and last_name in admin_members.lobbyusers table
        $firstName = $purchase->email_id;
        $lastName = $purchase->email_id;

        $lobbyUser = DB::table('lobbyusers')
            ->where('email', $purchase->email_id)
            ->first();

        if ($lobbyUser) {
            $firstName = $lobbyUser->first_name;
            $lastName = $lobbyUser->last_name;
        }

        if ($accessToken != null && $zoomId != null) {
            $client = new Client();
            $result = $client->post('https://api.zoom.us/v2/meetings/'. $zoomId . '/registrants', [
                'json' => [
                    'email' => $purchase->email_id,
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                ],
                'headers' => [
                    'User-Agent' => 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.13) Gecko/20080311 Firefox/2.0.0.13',
                    'Accept' => 'application/json',
                    'Authorization' => 'Bearer ' . $accessToken
                ]
            ]);

            $resData = $result->getBody();

            $json = json_decode($resData, true);

            if (isset($json['registrant_id'])) {
                $purchase->zoom_url = $json['join_url'];
                $purchase->registrant_id = $json['registrant_id'];
                $purchase->save();
            }
        }
    }
}
