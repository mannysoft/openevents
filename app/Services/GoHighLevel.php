<?php

namespace App\Services;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class GoHighLevel
{
    public $version = '2021-07-28';

    public $clientId = '65d8669f0486ad0f61e6c087-lsygjxfh';
    public $clientSecret = '98119c88-4ee0-422f-a8f9-ce348b1b5893';

    public $locationId = 'ZDavdc0F1sm0KU0x8ggQ';

    public $code = '27563b3b81ecf0a3ec89c4b8de7fdcb8d77c871d';

    public function setCode($code)
    {
        $this->code = $code;
    }

    // App Dashboard https://marketplace.gohighlevel.com/apps/65d8669f0486ad0f61e6c087/settings

    // To get the code 
    // https://marketplace.gohighlevel.com/oauth/chooselocation?response_type=code&redirect_uri=https://lounge.test/getGHToken&client_id=65d8669f0486ad0f61e6c087-lsygjxfh&scope=contacts.readonly contacts.write conversations/message.write payments/subscriptions.readonly payments/orders.readonly payments/orders.write
    // https://marketplace.gohighlevel.com/oauth/chooselocation?response_type=code&redirect_uri=https://events.test/getGHToken&client_id=65d8669f0486ad0f61e6c087-lsygjxfh&scope=contacts.readonly contacts.write conversations/message.write payments/subscriptions.readonly payments/orders.readonly payments/orders.write

    public function getToken()
    {
        // Get new access token
        if (cache('ghl_access_token') == null && cache('ghl_refresh_token') == null) {
            return $this->getAccessToken();
        }

        // return cache access token
        if (cache('ghl_access_token') != null) {
            return cache('ghl_access_token');
        }

        // Get refresh access token
        if (cache('ghl_access_token') == null && cache('ghl_refresh_token') != null) {
            return $this->getRefreshToken();
        }
    }

    public function getAccessToken()
    {
        $grantType = 'authorization_code';

        $client = new Client();
        $result = $client->post('https://services.leadconnectorhq.com/oauth/token', [
            'form_params' => [
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
                'code' => $this->code,
                'grant_type' => $grantType,
                'user_type' => 'Location',
            ],
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.13) Gecko/20080311 Firefox/2.0.0.13',
                'Accept'     => 'application/json',
            ]
        ]);

        $resData = $result->getBody();
        $json = json_decode($resData, true);

        if (isset($json['access_token'])) {

            $accessToken = $json['access_token'];

            //  make this valid for only 23 hours Though the token is valid for 24 hrs 
            cache(['ghl_access_token' => $json['access_token']], now()->addHours(23));
            cache(['ghl_refresh_token' => $json['refresh_token']]);
        }

        return $accessToken;
    }

    public function getRefreshToken()
    {
        $grantType = 'refresh_token';

        $client = new Client();
        $result = $client->post('https://services.leadconnectorhq.com/oauth/token', [
            'form_params' => [
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
                'refresh_token' => cache('ghl_refresh_token'),
                'grant_type' => $grantType,
                'user_type' => 'Location',
            ],
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.13) Gecko/20080311 Firefox/2.0.0.13',
                'Accept'     => 'application/json',
            ]
        ]);

        $resData = $result->getBody();
        $json = json_decode($resData, true);

        if (isset($json['access_token'])) {

            $accessToken = $json['access_token'];

            //  make this valid for only 23 hours Though the token is valid for 24 hrs 
            cache(['ghl_access_token' => $json['access_token']], now()->addHours(23));
            cache(['ghl_refresh_token' => $json['refresh_token']]);
        }

        return $accessToken;
    }

    public function createUpdateContact($contact = null)
    {
        if ($contact == null || empty($contact)) {
            return;
        }

        try {
            $client = new Client();
            $result = $client->post('https://services.leadconnectorhq.com/contacts/upsert', [
                'json' => $contact,
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->getToken(),
                    'Version' => $this->version,
                    'User-Agent' => 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.13) Gecko/20080311 Firefox/2.0.0.13',
                    'Accept'     => 'application/json',
                    'Content-Type' => 'application/json',
                ]
            ]);

            $resData = $result->getBody();

            return json_decode($resData, true);
        } catch (\Exception $e) {
            Log::info($e->getMessage());
            Log::info(json_encode($contact));
        }
    }

    public function addTags($contactId, $tags = null)
    {
        if ($tags == null || empty($tags)) {
            return;
        }

        try {
            $client = new Client();
            $result = $client->post('https://services.leadconnectorhq.com/contacts/' . $contactId . '/tags', [
                'json' => ['tags' => $tags],
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->getToken(),
                    'Version' => $this->version,
                    'User-Agent' => 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.13) Gecko/20080311 Firefox/2.0.0.13',
                    'Accept'     => 'application/json',
                    'Content-Type' => 'application/json',
                ]
            ]);

            $resData = $result->getBody();

            return json_decode($resData, true);
        } catch (\Exception $e) {
            Log::info($e->getMessage());
            Log::info(json_encode($tags));
        }
    }

    public function sendEmail($contactId = null, $jsonData = [])
    {
        // We can add templadeId in $jsonData
        try {
            $client = new Client();
            $result = $client->post('https://services.leadconnectorhq.com/conversations/messages', [
                'json' => $jsonData,
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->getToken(),
                    'Version' => $this->version,
                    'User-Agent' => 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.13) Gecko/20080311 Firefox/2.0.0.13',
                    'Accept'     => 'application/json',
                    'Content-Type' => 'application/json',
                ]
            ]);

            $resData = $result->getBody();

            return json_decode($resData, true);
        } catch (\Exception $e) {
            return $e->getMessage();
            Log::info($e->getMessage());
        }
    }

    public function getTemplates($search = '')
    {
        try {
            $client = new Client();
            $result = $client->get('https://services.leadconnectorhq.com/emails/builder?locationId=' .
                $this->locationId . '&name=' . 'Email 1 -Welcome to Global Woman Club - Before the event Not buy a ticket', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->getToken(),
                    'Version' => $this->version,
                    'User-Agent' => 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.13) Gecko/20080311 Firefox/2.0.0.13',
                    'Accept'     => 'application/json',
                    'Content-Type' => 'application/json',
                ]
            ]);

            $resData = $result->getBody();

            return json_decode($resData, true);
        } catch (\Exception $e) {
            return $e->getMessage();
            Log::info($e->getMessage());
        }
    }

    public function sendWebhookData($url, $jsonData = [])
    {
        try {
            $client = new Client();
            $result = $client->post('https://services.leadconnectorhq.com/conversations/messages', [
                // $result = $client->post($url, [
                'json' => $jsonData,
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->getToken(),
                    'Version' => $this->version,
                    'User-Agent' => 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.13) Gecko/20080311 Firefox/2.0.0.13',
                    'Accept'     => 'application/json',
                    'Content-Type' => 'application/json',
                ]
            ]);

            $resData = $result->getBody();

            return json_decode($resData, true);
        } catch (\Exception $e) {
            return $e->getMessage();
            Log::info($e->getMessage());
        }
    }
}
