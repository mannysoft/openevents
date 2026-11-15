<?php

namespace App\Http\Controllers\API;

use App\Models\Event;
use App\Models\LoungeEventUniqueLink;
use Illuminate\Http\Request;
use App\Models\EventImage;
use GuzzleHttp\Client;
use App\Models\Timezone;
use Carbon\Carbon;
use App\Models\Ticket;
use App\Services\LobbyAuth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class MembersApiController extends ApiBaseController
{
    // Seconds. The speaker Lambda has not been timed, so these are judgements.
    private const UPSTREAM_CONNECT_TIMEOUT = 2;
    private const UPSTREAM_TIMEOUT = 10;

    public $successStatus = 200;

    /**
     * @param Request $request
     * @return mixed
     */
    public function loginMember()
    {
        $a = "Tets";
        $success['token'] = $a;
        $success['status'] = $this->successStatus;
        return response()->json(['result' => $success], 200);
    }

    public function saveRec(Request $request)
    {
        $currentdate = date('Y-m-d H:i:s');
        $eventId = $request->eventId;
        $s3UrlLink = $request->s3UrlLink;
        $selectrec = $request->selectrec;
        if ($selectrec == "video") {
            DB::table('Wo_Clickfunnel_ZoomEvent_Data')
                ->where('event_id', '=', $eventId)
                ->update(['recording_url' => $s3UrlLink]);
        } else {
            DB::table('Wo_Clickfunnel_ZoomEvent_Data')
                ->where('event_id', '=', $eventId)
                ->update(['chat_url' => $s3UrlLink]);
        }

        $success['status'] = $this->successStatus;
        return response()->json(['result' => $success], 200);
    }

    public function getHostUrl($id)
    {
        // dd($id);
        $zoomId = $id;
        $allZoomData =    DB::table('Wo_Clickfunnel_ZoomEvent_Data')
            ->select('Wo_Clickfunnel_ZoomEvent_Data.*')
            ->join('events', 'events.id', '=', 'Wo_Clickfunnel_ZoomEvent_Data.event_id')
            ->where(['events.lobby_event_id' => $id])
            ->get();
        // dd($allZoomData);
        if (!$allZoomData->isEmpty()) {
            foreach ($allZoomData as $zoomAll) {
                $zoomId = $zoomAll->start_url;
            }
        }
        $a = $zoomId;
        $success['zoomId'] = $a;
        $success['status'] = $this->successStatus;
        return response()->json(['result' => $success], 200);
    }

    public function DeleteZoom($zoomId)
    {
        $status = 200;
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://api.zoom.us/v2/meetings/" . $zoomId . "?cancel_meeting_reminder=true&schedule_for_reminder=true",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "DELETE",
            CURLOPT_HTTPHEADER => array(
                "authorization: Bearer eyJzdiI6IjAwMDAswMSIsImFsZyI6IkhTNTEyIiwidiI6IjIuMCIsIgmtpZCI6IjYxMDMyN2ZhLWE0MTEtNGI2Mi1hYWFjLWNhYzUzMzlmY2E0ZiJ9.eyJhdWQiOiJodHRwczovL29hdXRoLnpvb20udXMiLCJ1aWQiOiI3RTA1cXJlOFNYT0lyNW1VbTNnMVlnIiwidmVyIjo5LCJhdWlkIjoiNTZlYzIyZjZjNDE0OGE5M2Q1Mjg3ZTk1NTViNTQ0ODQiLCJuYmYiOjE2ODg1NjQ1NDAsImNvZGUiOiJtaHBDVU45alR1bS1WNldUem5TT1hRNFlQUjRYNHBzME0iLCJpc3MiOiJ6bTpjaWQ6Q0dZbTU5NjFTTW1SbzRzTzhDTG9nUSIsImdubyI6MCwiZXhwIjoxNjg4NTY4MTQwLCJ0eXBlIjozLCJpYXQiOjE2ODg1NjQ1NDAsImFpZCI6Iks3UUJUby1vUlh1c3phU01lSWpUREEifQ.jybrJH9oYdtNMZ_61InVsEyiS8_ll7fR_fLX9jCFXeveOl9s9anivHUw8DvJGrK_z4jVXteacJInU_2xDdMmbA"
            ),
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
            echo "cURL Error #:" . $err;
        } else {
            echo $response;
        }
        return $status;
    }

    public function getUserAllEvent($id)
    {
        $allZoomData =    DB::table('Wo_Clickfunnel_Event_Purchase')
            ->join('events AS c', 'c.id', '=', 'Wo_Clickfunnel_Event_Purchase.event_id')
            ->where(['Wo_Clickfunnel_Event_Purchase.email_id' => $id])
            ->select('Wo_Clickfunnel_Event_Purchase.email_id', 'Wo_Clickfunnel_Event_Purchase.full_name', 'Wo_Clickfunnel_Event_Purchase.created', 'Wo_Clickfunnel_Event_Purchase.mobile_no', 'Wo_Clickfunnel_Event_Purchase.event_id', 'Wo_Clickfunnel_Event_Purchase.amount', 'Wo_Clickfunnel_Event_Purchase.user_id', 'Wo_Clickfunnel_Event_Purchase.type', 'c.title', 'c.start_date')
            ->get();


        $success['data'] = $allZoomData;
        $success['status'] = $this->successStatus;
        return response()->json(['result' => $success], 200);
    }


    public function postCreateEvent(Request $request)
    {
        $event = new Event();

        $event->title = $request->title;
        $event->location = $request->location;
        $event->description = $request->description;
        $event->bg_type = 'image';
        $event->bg_image_path = 'assets/images/public/EventPage/backgrounds/5.jpg';
        $event->start_date = date('Y-m-d H:i', strtotime($request->start_date));
        $event->end_date = date('Y-m-d H:i', strtotime($request->end_date));

        $event->account_id = '14';
        $event->user_id  = '31';
        $event->currency_id = '1';
        $event->organiser_id = '11';
        $event->venue_name = 'Virtual';
        $event->venue_name_full = 'Virtual';
        $event->is_live = '1';
        $event->lobby_page_id = $request->get('lobby_page');
        $event->event_type = '1';
        $event->lobby_live = $request->lobby_live;
        if ($request->get('lobby_live') == '0') {
            $event->others_url = $request->location_virtual_others;
        } else {
            $event->lobby_url = 'https://lobby.globalwomanclub.com';
        }
        $event->event_timezone = $request->timezone_id;
        $event->club = $request->club;
        $event->webinar = $request->webinar;
        // dd($request);
        try {

            $event->save();
        } catch (\Exception $e) {
            Log::error($e);

            return response()->json([
                'status'   => 'error',
                'messages' => trans("Controllers.event_create_exception"),
            ]);
        }
        $eventImage = new EventImage();
        $eventImage->image_path = $request->image_path;
        $eventImage->event_id = $event->id;
        $eventImage->account_id = '14';
        $eventImage->user_id  = '31';
        $eventImage->save();
        // echo $event->id;die();
        $lobbyEventId = $event->id;
        $lobbyeventimage = $request->image_path;
        $lobbyeventtitle = $request->title;
        $lobbyeventdescription = $request->description;
        $lobbyeventstartdate = $request->start_date;
        $clickfunnelurl =  $request->eventfunnelurl;
        $lobbyeventlocation = $request->location;
        $webinar =  $request->webinar;
        $lobbyeventenddate = $request->end_date;
        $lobbyEventPageId = $request->lobby_page;
        $lobbytimezone = $request->timezone_id;
        $lobbyClub =  $request->club;

        $getZoomData = $this->registerZoom($lobbyeventtitle, $lobbyeventstartdate, $lobbytimezone, $lobbyEventId, $lobbyClub);


        $event->save();

        // Ticket Creation

        DB::table('tickets')->insert(
            ['event_id' => $lobbyEventId, 'title' => 'Online Networking Ticket', 'quantity_available' => 500, 'start_sale_date' => date("Y-m-d H:i"), 'created_at' => date("Y-m-d H:i"), 'updated_at' => date("Y-m-d H:i"), 'end_sale_date' => NULL, 'price' => 10, 'min_per_person' => 1, 'max_per_person' => 30, 'description' => '', 'account_id' => 14, 'user_id' => 31, 'is_hidden' => 0]
        );

        DB::table('tickets')->insert(
            ['event_id' => $lobbyEventId, 'title' => 'Online Networking Ticket', 'quantity_available' => 500, 'start_sale_date' => date("Y-m-d H:i"), 'created_at' => date("Y-m-d H:i"), 'updated_at' => date("Y-m-d H:i"), 'end_sale_date' => NULL, 'price' => 20, 'min_per_person' => 1, 'max_per_person' => 30, 'description' => '', 'account_id' => 14, 'user_id' => 31, 'is_hidden' => 0]
        );

        DB::table('tickets')->insert(
            ['event_id' => $lobbyEventId, 'title' => 'Inperson Networking Ticket', 'quantity_available' => 500, 'start_sale_date' => date("Y-m-d H:i"), 'created_at' => date("Y-m-d H:i"), 'updated_at' => date("Y-m-d H:i"), 'end_sale_date' => NULL, 'price' => 40, 'min_per_person' => 1, 'max_per_person' => 30, 'description' => '', 'account_id' => 14, 'user_id' => 31, 'is_hidden' => 0]
        );

        DB::table('tickets')->insert(
            ['event_id' => $lobbyEventId, 'title' => 'Online Networking Ticket', 'quantity_available' => 500, 'start_sale_date' => date("Y-m-d H:i"), 'created_at' => date("Y-m-d H:i"), 'updated_at' => date("Y-m-d H:i"), 'end_sale_date' => NULL, 'price' => 0, 'min_per_person' => 1, 'max_per_person' => 30, 'description' => '', 'account_id' => 14, 'user_id' => 31, 'is_hidden' => 0]
        );


        $success['status'] = $this->successStatus;
        return response()->json(['result' => $success], 200);
    }


    public function postCreateEventWebinar(Request $request)
    {
        $event = new Event();
        $event->title = $request->title;
        $event->location = "Virtual";
        $event->description = $request->description;
        $event->bg_type = 'image';
        $event->bg_image_path = 'assets/images/public/EventPage/backgrounds/5.jpg';
        $event->start_date = date('Y-m-d H:i', strtotime($request->start_date));
        $event->end_date = date('Y-m-d H:i', strtotime($request->end_date));

        $event->account_id = '14';
        $event->user_id  = '31';
        $event->currency_id = '1';
        $event->organiser_id = '11';
        $event->venue_name = 'Virtual';
        $event->venue_name_full = 'Virtual';
        $event->is_live = '1';
        $event->lobby_page_id = $request->get('lobby_page');
        $event->event_type = '1';
        $event->lobby_live = '1';
        $event->lobby_url = $request->eventfunnelurl;

        $event->event_timezone = $request->timezone_id;
        $event->club = $request->club;
        $event->webinar = $request->webinar;
        try {

            $event->save();
        } catch (\Exception $e) {
            Log::error($e);

            return response()->json([
                'status'   => 'error',
                'messages' => trans("Controllers.event_create_exception"),
            ]);
        }
        $eventImage = new EventImage();
        $eventImage->image_path = $request->image_path;
        $eventImage->event_id = $event->id;
        $eventImage->account_id = '14';
        $eventImage->user_id  = '31';
        $eventImage->save();

        $lobbyEventId = $event->id;
        $lobbyeventimage = $request->image_path;
        $lobbyeventtitle = $request->title;
        $lobbyeventdescription = $request->description;
        $lobbyeventstartdate = $request->start_date;
        $clickfunnelurl =  $request->eventfunnelurl;
        $lobbyeventlocation = 'Virtual';
        $webinar =  $request->webinar;
        $lobbyeventenddate = $request->end_date;
        $lobbyEventPageId = $request->lobby_page;
        $lobbytimezone = $request->timezone_id;
        $lobbyClub =  $request->club;

        $getZoomData = $this->registerZoom($lobbyeventtitle, $lobbyeventstartdate, $lobbytimezone, $lobbyEventId, $lobbyClub);

        $event->save();

        // Ticket Creation

        DB::table('tickets')->insert(
            ['event_id' => $lobbyEventId, 'title' => 'Online Networking Ticket', 'quantity_available' => 500, 'start_sale_date' => date("Y-m-d H:i"), 'created_at' => date("Y-m-d H:i"), 'updated_at' => date("Y-m-d H:i"), 'end_sale_date' => NULL, 'price' => 10, 'min_per_person' => 1, 'max_per_person' => 30, 'description' => '', 'account_id' => 14, 'user_id' => 31, 'is_hidden' => 0]
        );

        DB::table('tickets')->insert(
            ['event_id' => $lobbyEventId, 'title' => 'Online Networking Ticket', 'quantity_available' => 500, 'start_sale_date' => date("Y-m-d H:i"), 'created_at' => date("Y-m-d H:i"), 'updated_at' => date("Y-m-d H:i"), 'end_sale_date' => NULL, 'price' => 20, 'min_per_person' => 1, 'max_per_person' => 30, 'description' => '', 'account_id' => 14, 'user_id' => 31, 'is_hidden' => 0]
        );

        DB::table('tickets')->insert(
            ['event_id' => $lobbyEventId, 'title' => 'Online Networking Ticket', 'quantity_available' => 500, 'start_sale_date' => date("Y-m-d H:i"), 'created_at' => date("Y-m-d H:i"), 'updated_at' => date("Y-m-d H:i"), 'end_sale_date' => NULL, 'price' => 0, 'min_per_person' => 1, 'max_per_person' => 30, 'description' => '', 'account_id' => 14, 'user_id' => 31, 'is_hidden' => 0]
        );


        $success['status'] = $this->successStatus;
        return response()->json(['result' => $success], 200);
    }

    public function postDeleteEvent(Request $request)
    {
        $zoomId = '';
        $lobbyId = $request->eventId;
        DB::table('events')->where('lobby_event_id', '=', $lobbyId)->delete();

        $allZoomData =    DB::table('Wo_Clickfunnel_ZoomEvent_Data')
            ->select('Wo_Clickfunnel_ZoomEvent_Data.*')
            ->join('events', 'events.id', '=', 'Wo_Clickfunnel_ZoomEvent_Data.event_id')
            ->where(['events.lobby_event_id' => $lobbyId])
            ->get();

        if (count($allZoomData) > 0) {
            foreach ($allZoomData as $zoomData) {
                $zoomId = $zoomData->zoom_id;
            }
            $getZoomDeleteData = $this->DeleteZoom($zoomId);
        }
        $success['status'] = $this->successStatus;
        return response()->json(['result' => $success], 200);
    }

    public function postCreateEventALL(Request $request)
    {
        $event = new Event();

        $event->title = $request->title;
        $event->location = $request->location;
        $event->description = $request->description;
        $event->bg_type = 'image';
        $event->bg_image_path = 'assets/images/public/EventPage/backgrounds/5.jpg';
        $event->start_date = date('Y-m-d H:i', strtotime($request->start_date));
        $event->end_date = date('Y-m-d H:i', strtotime($request->end_date));

        $event->account_id = '14';
        $event->user_id  = '31';
        $event->currency_id = $request->currency;
        $event->organiser_id = '11';
        $event->mprice = $request->mPriceAll;
        $event->nmprice = $request->nmPriceAll;
        $event->lobby1_live = $request->lobby_live;

        if ($request->vat == "1") {
            $event->organiser_fee_percentage = "20.00";
        }

        if ($request->eventType == "online") {
            $event->venue_name = 'Virtual';
            $event->venue_name_full = 'Virtual';
            $event->event_ttype = "Online";
        } elseif ($request->eventType == "in-person") {
            $event->event_ttype = "In-Person";
            $event->venue_name = $request->location;
            $event->venue_name_full = $request->location;
        } else {
            $event->event_ttype = "Hybrid";
            $event->hmprice = $request->mPriceHybrid;
            $event->hnmprice = $request->nmPriceHybrid;
            $event->vimage = $request->virtualimage;
            $event->iimage = $request->inpersonimage;
            $event->venue_name = $request->location;
            $event->venue_name_full = $request->location;
        }

        $event->is_live = '1';
        $event->lobby_page_id = $request->get('lobby_page');
        $event->event_type = '1';
        $event->lobby_live = '1';
        $event->lobby_url = 'https://lobby.globalwomanclub.com';
        $event->event_timezone = $request->timezone_id;
        $event->club = $request->club;
        $event->webinar = $request->webinar ?? 0;
        $event->is_member = $request->is_member;
        $event->is_premium = $request->is_member;
        // dd($request->title);
        try {

            $event->save();
        } catch (\Exception $e) {
            Log::error($e);

            return response()->json([
                'status'   => 'error',
                'messages' => trans("Controllers.event_create_exception"),
            ]);
        }

        $eventImage = new EventImage();
        $eventImage->image_path = $request->image_path;
        $eventImage->event_id = $event->id;
        $eventImage->account_id = '14';
        $eventImage->user_id  = '31';
        $eventImage->save();

        $lobbyEventId = $event->id;
        $lobbyeventimage = $request->image_path;
        $lobbyeventtitle = $request->title;
        $lobbyeventdescription = $request->description;
        $lobbyeventstartdate = $request->start_date;
        $clickfunnelurl = config('app.url') . "/" . $request->club;
        $lobbyeventlocation = $request->location;
        $webinar =  $request->webinar;
        $lobbyeventenddate = $request->end_date;
        $lobbyEventPageId = $request->lobby_page;
        $lobbytimezone = $request->timezone_id;
        $lobbyClub =  $request->club;
        $eType =  $request->eventType;

        $event->save();

        $gwl_event_id = $this->insertEventLobby($event->id, $lobbyeventimage, $lobbyeventtitle, $lobbyeventdescription, $lobbyeventstartdate, $lobbyeventlocation, $lobbyeventenddate, $lobbyEventPageId, $clickfunnelurl, $webinar, $lobbyClub, $lobbytimezone, $eType, $request->is_member, $request->user_id);
        $event->lobby_event_id = $gwl_event_id;
        $event->save();

        // Ticket Creation
        if ($request->eventType == "online") {
            $getZoomData = $this->registerZoom($lobbyeventtitle, $lobbyeventstartdate, $lobbytimezone, $lobbyEventId, $lobbyClub);
            DB::table('tickets')->insert(
                ['event_id' => $lobbyEventId, 'title' => 'Online Networking Ticket', 'quantity_available' => 500, 'start_sale_date' => date("Y-m-d H:i"), 'created_at' => date("Y-m-d H:i"), 'updated_at' => date("Y-m-d H:i"), 'end_sale_date' => NULL, 'price' => $request->mPriceAll, 'min_per_person' => 1, 'max_per_person' => 30, 'description' => '', 'account_id' => 14, 'user_id' => 31, 'is_hidden' => 0]
            );

            DB::table('tickets')->insert(
                ['event_id' => $lobbyEventId, 'title' => 'Online Networking Ticket', 'quantity_available' => 500, 'start_sale_date' => date("Y-m-d H:i"), 'created_at' => date("Y-m-d H:i"), 'updated_at' => date("Y-m-d H:i"), 'end_sale_date' => NULL, 'price' => $request->nmPriceAll, 'min_per_person' => 1, 'max_per_person' => 30, 'description' => '', 'account_id' => 14, 'user_id' => 31, 'is_hidden' => 0]
            );

            DB::table('tickets')->insert(
                ['event_id' => $lobbyEventId, 'title' => 'Online Networking Ticket', 'quantity_available' => 500, 'start_sale_date' => date("Y-m-d H:i"), 'created_at' => date("Y-m-d H:i"), 'updated_at' => date("Y-m-d H:i"), 'end_sale_date' => NULL, 'price' => 0, 'min_per_person' => 1, 'max_per_person' => 30, 'description' => '', 'account_id' => 14, 'user_id' => 31, 'is_hidden' => 0]
            );
        } elseif ($request->eventType == "in-person") {
            DB::table('tickets')->insert(
                ['event_id' => $lobbyEventId, 'title' => 'Inperson Networking Ticket', 'quantity_available' => 500, 'start_sale_date' => date("Y-m-d H:i"), 'created_at' => date("Y-m-d H:i"), 'updated_at' => date("Y-m-d H:i"), 'end_sale_date' => NULL, 'price' => $request->mPriceAll, 'min_per_person' => 1, 'max_per_person' => 30, 'description' => '', 'account_id' => 14, 'user_id' => 31, 'is_hidden' => 0]
            );

            DB::table('tickets')->insert(
                ['event_id' => $lobbyEventId, 'title' => 'Inperson Networking Ticket', 'quantity_available' => 500, 'start_sale_date' => date("Y-m-d H:i"), 'created_at' => date("Y-m-d H:i"), 'updated_at' => date("Y-m-d H:i"), 'end_sale_date' => NULL, 'price' => $request->nmPriceAll, 'min_per_person' => 1, 'max_per_person' => 30, 'description' => '', 'account_id' => 14, 'user_id' => 31, 'is_hidden' => 0]
            );

            DB::table('tickets')->insert(
                ['event_id' => $lobbyEventId, 'title' => 'Inperson Networking Ticket', 'quantity_available' => 500, 'start_sale_date' => date("Y-m-d H:i"), 'created_at' => date("Y-m-d H:i"), 'updated_at' => date("Y-m-d H:i"), 'end_sale_date' => NULL, 'price' => 0, 'min_per_person' => 1, 'max_per_person' => 30, 'description' => '', 'account_id' => 14, 'user_id' => 31, 'is_hidden' => 0]
            );
        } else {
            $getZoomData = $this->registerZoom($lobbyeventtitle, $lobbyeventstartdate, $lobbytimezone, $lobbyEventId, $lobbyClub);
            DB::table('tickets')->insert(
                ['event_id' => $lobbyEventId, 'title' => 'Online Networking Ticket', 'quantity_available' => 500, 'start_sale_date' => date("Y-m-d H:i"), 'created_at' => date("Y-m-d H:i"), 'updated_at' => date("Y-m-d H:i"), 'end_sale_date' => NULL, 'price' => $request->mPriceAll, 'min_per_person' => 1, 'max_per_person' => 30, 'description' => '', 'account_id' => 14, 'user_id' => 31, 'is_hidden' => 0]
            );

            DB::table('tickets')->insert(
                ['event_id' => $lobbyEventId, 'title' => 'Online Networking Ticket', 'quantity_available' => 500, 'start_sale_date' => date("Y-m-d H:i"), 'created_at' => date("Y-m-d H:i"), 'updated_at' => date("Y-m-d H:i"), 'end_sale_date' => NULL, 'price' => $request->nmPriceAll, 'min_per_person' => 1, 'max_per_person' => 30, 'description' => '', 'account_id' => 14, 'user_id' => 31, 'is_hidden' => 0]
            );

            DB::table('tickets')->insert(
                ['event_id' => $lobbyEventId, 'title' => 'Inperson Networking Ticket', 'quantity_available' => 500, 'start_sale_date' => date("Y-m-d H:i"), 'created_at' => date("Y-m-d H:i"), 'updated_at' => date("Y-m-d H:i"), 'end_sale_date' => NULL, 'price' => $request->mPriceHybrid, 'min_per_person' => 1, 'max_per_person' => 30, 'description' => '', 'account_id' => 14, 'user_id' => 31, 'is_hidden' => 0]
            );

            DB::table('tickets')->insert(
                ['event_id' => $lobbyEventId, 'title' => 'Inperson Networking Ticket', 'quantity_available' => 500, 'start_sale_date' => date("Y-m-d H:i"), 'created_at' => date("Y-m-d H:i"), 'updated_at' => date("Y-m-d H:i"), 'end_sale_date' => NULL, 'price' => $request->nmPriceHybrid, 'min_per_person' => 1, 'max_per_person' => 30, 'description' => '', 'account_id' => 14, 'user_id' => 31, 'is_hidden' => 0]
            );

            DB::table('tickets')->insert(
                ['event_id' => $lobbyEventId, 'title' => 'Networking Ticket', 'quantity_available' => 500, 'start_sale_date' => date("Y-m-d H:i"), 'created_at' => date("Y-m-d H:i"), 'updated_at' => date("Y-m-d H:i"), 'end_sale_date' => NULL, 'price' => 0, 'min_per_person' => 1, 'max_per_person' => 30, 'description' => '', 'account_id' => 14, 'user_id' => 31, 'is_hidden' => 0]
            );
        }

        $success['status'] = $this->successStatus;
        return response()->json(['result' => $success], 200);
    }





    public function getEventSpeakerData(Request $request)
    {
        $lobbyId = $request->eventId;


        $allSpeaker =    DB::table('Wo_Clickfunnel_Event_SpeakerData')
            ->select('Wo_Clickfunnel_Event_SpeakerData.*')
            ->join('events', 'events.id', '=', 'Wo_Clickfunnel_Event_SpeakerData.event_id')
            ->where(['events.lobby_event_id' => $lobbyId])
            ->get();
        $success['status'] = $this->successStatus;
        $success['data'] = $allSpeaker;
        return response()->json(['result' => $success], 200);
    }

    public function updateSpeakerPrice(Request $request)
    {
        $speakerPrice = $request->speakerPrice;
        $nameUser = '';
        $emailUser = '';
        $refId = '';
        $eventId = $request->eventId;
        DB::table('tickets')->insert(
            ['event_id' => $eventId, 'title' => 'Speaker Ticket', 'quantity_available' => 100, 'start_sale_date' => date("Y-m-d H:i"), 'created_at' => date("Y-m-d H:i"), 'updated_at' => date("Y-m-d H:i"), 'end_sale_date' => NULL, 'price' => $speakerPrice, 'min_per_person' => 1, 'max_per_person' => 100, 'description' => '', 'account_id' => 14, 'user_id' => 31, 'is_hidden' => 0]
        );



        $lobbyUserId = $request->lobbyUserId;
        DB::table('Wo_Clickfunnel_Event_SpeakerData')
            ->where('event_id', $eventId)
            ->where('user_id', $lobbyUserId)
            ->update(['amount' => $speakerPrice, 'status' => 'approved', 'approve_date' => date("Y-m-d H:i")]);

        $getDataSpeaker = DB::table('Wo_Clickfunnel_Event_SpeakerData')
            ->where('event_id', $eventId)
            ->where('user_id', $lobbyUserId)
            ->get();
        foreach ($getDataSpeaker as $speakerData) {
            $nameUser = $speakerData->first_name;
            $emailUser = $speakerData->email_id;
            $refId = $speakerData->id;
        }
        $urlData = config('app.url') . "/purchase-speaker-ticket?ref=" . $refId;



        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://o5en18ihoe.execute-api.eu-west-2.amazonaws.com/prod/purchaselink",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_CONNECTTIMEOUT => self::UPSTREAM_CONNECT_TIMEOUT,
            CURLOPT_TIMEOUT => self::UPSTREAM_TIMEOUT,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => "{\r\n    \"refId\":\"$refId\",\r\n    \"reqStatus\":\"new\"\r\n}",
            CURLOPT_HTTPHEADER => array(
                "Content-Type: application/json"
            ),
        ));

        $response = curl_exec($curl);

        // The speaker's row and ticket are already saved, and the Lounge can resend the link.
        if ($response === false) {
            Log::warning('curl call failed', ['call' => 'purchaselink', 'ref_id' => $refId, 'curl_errno' => curl_errno($curl)]);
        }

        curl_close($curl);


        $success['status'] = $this->successStatus;
        $success['nameUser'] = $nameUser;
        $success['emailUser'] = $emailUser;
        $success['urlData'] = $urlData;
        $success['refId'] = $refId;
        return response()->json(['result' => $success], 200);
    }

    public function rejectSpeaker(Request $request)
    {
        $eventId = $request->eventId;
        $lobbyUserId = $request->lobbyUserId;
        DB::table('Wo_Clickfunnel_Event_SpeakerData')
            ->where('event_id', $eventId)
            ->where('user_id', $lobbyUserId)
            ->update(['status' => 'rejected']);


        $success['status'] = $this->successStatus;
        return response()->json(['result' => $success], 200);
    }


    public function registerZoom($lobbyeventtitle, $lobbyeventstartdate, $lobbytimezone, $lobbyEventId, $lobbyClub)
    {
        return false;


        print_r($lobbyeventtitle);
        echo "<br>";
        print_r($lobbyeventstartdate);
        echo "<br>";
        print_r($lobbytimezone);
        echo "<br>";
        print_r($lobbyEventId);
        echo "<br>";
        print_r($lobbyClub);
        echo "<br>";


        $startTimeEvent    =  date('Y-m-d\TH:i:s', strtotime($lobbyeventstartdate));
        $currentdate = date('Y-m-d H:i:s');

        // $eventIdNew = DB::table('events')->where(
        // 	'lobby_event_id',  $lobbyEventId
        // )->get();
        // print_r($eventIdNew);
        // print_r('55555');
        // $evntId= $eventIdNew->first()->id;

        // create access token


        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://zoom.us/oauth/token',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => 'grant_type=account_credentials&account_id=K7QBTo-oRXuszaSMeIjTDA',
            CURLOPT_HTTPHEADER => array(
                'Host: zoom.us',
                'Authorization: Basic Q0dZbTU5NjFTTW1SbzRzTzhDTG9nUTpZN3RCRUpSMXN6akRQUEh0ZEh2MVA0TG9PTERuQXJINw',
                'Content-Type: application/x-www-form-urlencoded',
                'Cookie: TS018dd1ba=01593b78b83fe9ea8c2922802b4eb7d931bcd7de386f993d11889b87fb5986d955322f5a73fbbd354c32f512e07b8af7154344574c; TS01f92dc5=01593b78b83fe9ea8c2922802b4eb7d931bcd7de386f993d11889b87fb5986d955322f5a73fbbd354c32f512e07b8af7154344574c; __cf_bm=LcQfcv7AM4D4N1wEg7U.OTwiThvfDgN4LRYKkuYtOrI-1688626494-0-ARuBfn+Yy2OyODHICSx9wci+T9oycqX2qzx/v8aVS2dzlJ4CXQKgu9jYhVSBqXeK0iSQ32NRK5eECyhcKt0XQP8=; _zm_chtaid=277; _zm_ctaid=zUSvLUA5Q5igRtODz0IwdQ.1688626494812.28a3000473b18a1170c2a88edb184839; _zm_mtk_guid=fb26da89c98b4c5fad1ee0bfc97eb7fb; _zm_page_auth=us06_c_4mht9bqySUWpyCfrYykhbA; _zm_ssid=us06_c_ruYZ0nwNTx-ccvrQi8JyWA; cred=2B857552899B266830F01A044D5077DB'
            ),
        ));

        $response_acc_token = curl_exec($curl);

        curl_close($curl);
        $access_token_new = json_decode($response_acc_token, true);
        $access_token_new_set = $access_token_new['access_token'];
        // echo $access_token_new_set;die();

        // create access token


        $curl = curl_init();
        $authorization = "Authorization: Bearer $access_token_new_set";
        // echo($authorization);die();
        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://api.zoom.us/v2/users/me/meetings",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => "{\r\n  \"topic\": \"$lobbyeventtitle\",\r\n  \"type\": \"2\",\r\n  \"start_time\": \"$startTimeEvent\",\r\n  \"duration\": \"120\",\r\n  \"timezone\": \"$lobbytimezone\",\r\n  \"agenda\": \"$lobbyeventtitle\",\r\n  \"settings\": {\r\n    \"host_video\": \"false\",\r\n    \"participant_video\": \"false\",\r\n    \"cn_meeting\": \"false\",\r\n    \"in_meeting\": \"false\",\r\n    \"join_before_host\": \"false\",\r\n    \"mute_upon_entry\": \"true\",\r\n    \"watermark\": \"false\",\r\n    \"use_pmi\": \"false\",\r\n    \"approval_type\": \"0\",\r\n    \"registration_type\": \"2\",\r\n    \"audio\": \"both\",\r\n    \"auto_recording\": \"cloud\",\r\n    \"enforce_login\": \"false\",\r\n    \"enforce_login_domains\": \"\",\r\n    \"alternative_hosts\": \"\",\r\n    \"global_dial_in_countries\": [\r\n      \"\"\r\n    ],\r\n    \"registrants_email_notification\": \"true\"\r\n  }\r\n}",
            CURLOPT_HTTPHEADER => array(
                $authorization,
                "Content-Type: application/json",
                "Cookie: cred=1038E93C272652D2EF07345FA40C9F02"
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        $json = json_decode($response, true);
        $zoom_id = $json['id'];
        $registration_url = $json['registration_url'];
        $uuid = $json['uuid'];
        $join_url = $json['join_url'];
        $start_url1 = $json['start_url'];


        // dd($zoomInsert);
        try {

            $zoomInsert = DB::table('Wo_Clickfunnel_ZoomEvent_Data')->insert(
                ['start_url' => $start_url1, 'club' => $lobbyClub, 'event_id' => $lobbyEventId, 'zoom_id' => $zoom_id, 'join_url' => $join_url, 'registration_url' => $registration_url, 'uuid' => $uuid, 'created' => $currentdate]
            );
            print_r($zoomInsert);
            echo "<br>";
            print_r('data insert');
        } catch (\Exception $e) {
            print_r($e);
            Log::error($e);

            return response()->json([
                'status'   => 'error',
            ]);
        }
        $success['status'] = $this->successStatus;
        $success['data'] = $json;
        return response()->json(['result' => $success], 200);
    }


    public function updateEventsEvent(Request $request)
    {
        return [];
        $lobbyeventtitle = $request->title;
        $lobbyeventstartdate = $request->start_date;
        $lobbytimezone = $request->timezone_id;
        $lobbyEventId = $request->eventId;

        $startTimeEvent    =  date('Y-m-d\TH:i:s', strtotime($lobbyeventstartdate));
        $currentdate = date('Y-m-d H:i:s');
        $allZoomData =    DB::table('Wo_Clickfunnel_ZoomEvent_Data')
            ->select('Wo_Clickfunnel_ZoomEvent_Data.*')
            ->join('events', 'events.id', '=', 'Wo_Clickfunnel_ZoomEvent_Data.event_id')
            ->where(['events.lobby_event_id' => $lobbyEventId])
            ->get();

        if (count($allZoomData) > 0) {
            foreach ($allZoomData as $zoomData) {
                $zoomId = $zoomData->zoom_id;
            }

            $curl = curl_init();

            curl_setopt_array($curl, array(
                CURLOPT_URL => "https://api.zoom.us/v2/meetings/" . $zoomId,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "PATCH",
                CURLOPT_POSTFIELDS => "{\"topic\":\"$lobbyeventtitle\",\"type\":2,\"start_time\":\"$startTimeEvent\",\"duration\":120,\"timezone\":\"$lobbytimezone\",\"agenda\":\"$lobbyeventtitle\",\"settings\":{\"host_video\":false,\"in_meeting\":false,\"join_before_host\":false,\"mute_upon_entry\":true,\"participant_video\":false,\"registrants_confirmation_email\":true,\"use_pmi\":false,\"waiting_room\":true,\"alternative_hosts\":\"zoom@globalwomanclub.com\",\"watermark\":false,\"approval_type\":0,\"global_dial_in_countries\":[]}}",
                CURLOPT_HTTPHEADER => array(
                    "authorization: Bearer eyJzdiI6IjAwMDAwMSIsImFsZyI6IkhTNTEyIiwidiI6IjIuMCIsImtpZCI6IjYxMDMyN2ZhLWE0MTEtNGI2Mi1hYWFjLWNhYzUzMzlmY2E0ZiJ9.eyJhdWQiOiJodHRwczovL29hdXRoLnpvb20udXMiLCJ1aWQiOiI3RTA1cXJlOFNYT0lyNW1VbTNnMVlnIiwidmVyIjo5LCJhdWlkIjoiNTZlYzIyZjZjNDE0OGE5M2Q1Mjg3ZTk1NTViNTQ0ODQiLCJuYmYiOjE2ODg1NjQ1NDAsImNvZGUiOiJtaHBDVU45alR1bS1WNldUem5TT1hRNFlQUjRYNHBzME0iLCJpc3MiOiJ6bTpjaWQ6Q0dZbTU5NjFTTW1SbzRzTzhDTG9nUSIsImdubyI6MCwiZXhwIjoxNjg4NTY4MTQwLCJ0eXBlIjozLCJpYXQiOjE2ODg1NjQ1NDAsImFpZCI6Iks3UUJUby1vUlh1c3phU01lSWpUREEifQ.jybrJH9oYdtNMZ_61InVsEyiS8_ll7fR_fLX9jCFXeveOl9s9anivHUw8DvJGrK_z4jVXteacJInU_2xDdMmbA",
                    "content-type: application/json"
                ),
            ));

            $response = curl_exec($curl);
            $err = curl_error($curl);

            curl_close($curl);

            $json = json_decode($response, true);
            return $json;
            $success['status'] = $this->successStatus;
            $success['data'] = $response;
        } else {
            $success['status'] = $this->successStatus;
            $success['data'] = '';
        }


        return response()->json(['result' => $success], 200);
    }

    // Lobby API
    public function insertEventLobby($eventId, $lobbyeventimage, $lobbyeventtitle, $lobbyeventdescription, $lobbyeventstartdate, $lobbyeventlocation, $lobbyeventenddate, $lobbyEventPageId, $clickfunnelurl, $webinar, $lobbyClub, $lobbytimezone, $eType, $isMember = false, $userId = null)
    {
        $lobbyAuth = new LobbyAuth;
        $accessToken = $lobbyAuth->getAccessToken();

        if ($accessToken == null) {
            return;
        }

        $date = strtotime($lobbyeventstartdate);
        $startTimeEvent    = date('H:i:s', $date);
        $startDateEvent    = date('Y-m-d', $date);

        $date1 = strtotime($lobbyeventenddate);
        $endTimeEvent    = date('H:i:s', $date1);
        $endDateEvent    = date('Y-m-d', $date1);
        $eventUrl = $clickfunnelurl;

        if ($lobbyClub == null) {
            $lobbyClub = '';
        }

        $id = DB::connection('mysql_lobby')->table('Wo_Events')->insertGetId([
            'name' => $lobbyeventtitle,
            'location' => $lobbyeventlocation,
            'description' => $lobbyeventdescription,
            'start_date' => $startDateEvent,
            'start_time' => $startTimeEvent,
            'end_date' => $endDateEvent,
            'end_time' => $endTimeEvent,
            'poster_id' => 1,
            'cover' => $lobbyeventimage,
            'pro_url' => $eventUrl,
            'club' => $lobbyClub,
            'ee_id' => $eventId,
            'timezone' => $lobbytimezone . '',
            'etype' => $eType . '',
            'is_pro' => 1,
            'is_member' => $isMember,
            'user_id' => $userId,
        ]);

        return $id;

        // Ignore below

        $client = new Client();
        $result = $client->post('https://lobby.globalwomanclub.com/api/create-event?access_token=' . $accessToken, [
            'form_params' => [
                "server_key" => config('services.lobby_app.server_key'),
                "event_name" => $lobbyeventtitle,
                "event_location" => $lobbyeventlocation,
                "event_description" => $lobbyeventdescription,
                "event_start_date" => $startDateEvent,
                "event_end_date" => $endDateEvent,
                "event_start_time" => $startTimeEvent,
                "event_end_time" => $endTimeEvent,
                "event_image"    => $lobbyeventimage,
                "is_pro" => 1,
                "event_url" => $eventUrl,
                "event_page_id" => $lobbyEventPageId,
                "webinar" =>  $webinar,
                "club" => $lobbyClub,
                "eType" => $eType,
                "timezone" => $lobbytimezone,
                "ee_id" => $eventId,
                "is_member" => $isMember,
            ],
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.13) Gecko/20080311 Firefox/2.0.0.13',
                'Accept'     => 'application/json',
            ]
        ]);
        $resData = $result->getBody();
        Log::info($resData);
        $json = json_decode($resData, true);
        $gwl_event_id = $json['event_id'];
        return $gwl_event_id;
    }

    public function insertEventLobby11($lobbyeventimage, $lobbyeventtitle, $lobbyeventdescription, $lobbyeventstartdate, $lobbyeventlocation, $lobbyeventenddate, $lobbytimezone, $lobbyEventId, $lobbyEventPageId, $clickfunnelurl, $webinar, $lobbyClub, $eType)
    {
        $access_token = '';
        $date = strtotime($lobbyeventstartdate);
        $startTimeEvent    = date('H:i:s', $date);
        $startDateEvent    = date('Y-m-d', $date);

        $date1 = strtotime($lobbyeventenddate);
        $endTimeEvent    = date('H:i:s', $date1);
        $endDateEvent    = date('Y-m-d', $date1);
        $eventUrl = $clickfunnelurl;

        // $curl = curl_init();

        // curl_setopt_array($curl, array(
        // CURLOPT_URL => "https://lobby.globalwomanclub.com/api/auth",
        // CURLOPT_RETURNTRANSFER => true,
        // CURLOPT_ENCODING => "",
        // CURLOPT_MAXREDIRS => 10,
        // CURLOPT_TIMEOUT => 0,
        // CURLOPT_FOLLOWLOCATION => true,
        // CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        // CURLOPT_CUSTOMREQUEST => "POST",
        // ));

        // $response1 = curl_exec($curl);


        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://lobby.globalwomanclub.com/api/auth",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => array('server_key' => '4025df01045c1336163159ea5577b4d0', 'username' => 'globalwomanapi', 'password' => 'pass123'),
            CURLOPT_HTTPHEADER => array(
                "Cookie: ad-con=%7B%26quot%3Bdate%26quot%3B%3A%26quot%3B2020-08-07%26quot%3B%2C%26quot%3Bads%26quot%3B%3A%5B%5D%7D; _us=1596882344; mode=day; src=1; ad-con=%7B%26quot%3Bdate%26quot%3B%3A%26quot%3B2020-07-20%26quot%3B%2C%26quot%3Bads%26quot%3B%3A%5B%5D%7D; _us=1595316757; PHPSESSID=4fb1pv7heafvbs9tegq5nv2ero; access=1"
            ),
        ));

        $response1 = curl_exec($curl);
        curl_close($curl);
        $json = json_decode($response1, true);
        $apiStatus = $json['api_status'];
        if ($apiStatus == 200) {
            $access_token = $json['access_token'];
        }
        $client = new Client();
        $result = $client->post('https://lobby.globalwomanclub.com/api/create-event-lobby?access_token=' . $access_token, [
            'form_params' => [
                "server_key" => config('services.lobby_app.server_key'),
                "event_name" => $lobbyeventtitle,
                "event_location" => $lobbyeventlocation,
                "event_description" => $lobbyeventtitle,
                "event_start_date" => $startDateEvent,
                "event_end_date" => $endDateEvent,
                "event_start_time" => $startTimeEvent,
                "event_end_time" => $endTimeEvent,
                "event_image"    => $lobbyeventimage,
                "is_pro" => 1,
                "event_url" => $eventUrl,
                "event_page_id" => $lobbyEventPageId,
                "webinar" =>  $webinar,
                "club" => $lobbyClub,
                "eType" => $eType,
                "timezone" => $lobbytimezone
            ]
        ]);
        $resData = $result->getBody();
        $json = json_decode($resData, true);
        $gwl_event_id = $json['event_id'];
        return $gwl_event_id;
    }


    public function updateEventsEvent1(Request $request)
    {
        // dd($request);
        $lobbyeventtitle = $request->title;
        $lobbyeventstartdate = $request->start_date;
        $lobbytimezone = $request->timezone_id;
        $lobbyEventId = $request->eventId;
        $lobbyClub = $request->lobbyClub;

        $startTimeEvent    =  date('Y-m-d\TH:i:s', strtotime($lobbyeventstartdate));
        $currentdate = date('Y-m-d H:i:s');
        $allZoomData =    DB::table('Wo_Clickfunnel_ZoomEvent_Data')
            ->select('Wo_Clickfunnel_ZoomEvent_Data.*')
            ->join('events', 'events.id', '=', 'Wo_Clickfunnel_ZoomEvent_Data.event_id')
            ->where(['events.lobby_event_id' => $lobbyEventId])
            ->get();
        // dd($allZoomData);

        if (count($allZoomData) > 0) {
            foreach ($allZoomData as $zoomData) {
                $zoomId = $zoomData->zoom_id;
            }
            // dd($zoomId);
            $curl = curl_init();

            curl_setopt_array($curl, array(
                CURLOPT_URL => "https://api.zoom.us/v2/meetings/" . $zoomId,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "PATCH",
                CURLOPT_POSTFIELDS => "{\"topic\":\"$lobbyeventtitle\",\"type\":2,\"start_time\":\"$startTimeEvent\",\"duration\":120,\"timezone\":\"$lobbytimezone\",\"agenda\":\"$lobbyeventtitle\",\"settings\":{\"host_video\":false,\"in_meeting\":false,\"join_before_host\":false,\"mute_upon_entry\":true,\"participant_video\":false,\"registrants_confirmation_email\":true,\"use_pmi\":false,\"waiting_room\":true,\"alternative_hosts\":\"zoom@globalwomanclub.com\",\"watermark\":false,\"approval_type\":0,\"global_dial_in_countries\":[]}}",
                CURLOPT_HTTPHEADER => array(
                    "authorization: Bearer eyJzdiI6IjAwMDAwMSIsImFsZyI6IkhTNTEyIiwidiI6IjIuMCIsImtpZCI6IjYxMDMyN2ZhLWE0MTEtNGI2Mi1hYWFjLWNhYzUzMzlmY2E0ZiJ9.eyJhdWQiOiJodHRwczovL29hdXRoLnpvb20udXMiLCJ1aWQiOiI3RTA1cXJlOFNYT0lyNW1VbTNnMVlnIiwidmVyIjo5LCJhdWlkIjoiNTZlYzIyZjZjNDE0OGE5M2Q1Mjg3ZTk1NTViNTQ0ODQiLCJuYmYiOjE2ODg1NjQ1NDAsImNvZGUiOiJtaHBDVU45alR1bS1WNldUem5TT1hRNFlQUjRYNHBzME0iLCJpc3MiOiJ6bTpjaWQ6Q0dZbTU5NjFTTW1SbzRzTzhDTG9nUSIsImdubyI6MCwiZXhwIjoxNjg4NTY4MTQwLCJ0eXBlIjozLCJpYXQiOjE2ODg1NjQ1NDAsImFpZCI6Iks3UUJUby1vUlh1c3phU01lSWpUREEifQ.jybrJH9oYdtNMZ_61InVsEyiS8_ll7fR_fLX9jCFXeveOl9s9anivHUw8DvJGrK_z4jVXteacJInU_2xDdMmbA",
                    "content-type: application/json"
                ),
            ));

            $response = curl_exec($curl);
            $err = curl_error($curl);

            curl_close($curl);

            $json = json_decode($response, true);
            return $json;
            $success['status'] = $this->successStatus;
            $success['data'] = $response;
        } else {
            // dd('check3');
            $getZoomData = $this->registerZoom($lobbyeventtitle, $lobbyeventstartdate, $lobbytimezone, $lobbyEventId, $lobbyClub);
            $success['status'] = $this->successStatus;
            $success['data'] = '';
        }


        return response()->json(['result' => $success], 200);
    }

    public function saveFreeUser(Request $request)
    {
        $emailId = $request->emailId;
        if ($emailId == null) {
            $emailId = $request->email;
        }

        $validator = Validator::make([
            'eventId' => $request->eventId,
            'emailId' => $emailId,
            'fullname' => $request->fullname,
            'mobileNo' => $request->mobileNo,
            'eventtType' => $request->eventtType,
            'unique_token' => $request->unique_token,
        ], [
            'eventId' => ['required', 'integer', 'exists:events,id'],
            'emailId' => ['required', 'email', 'max:255'],
            'fullname' => ['required', 'string', 'min:2', 'max:100', 'regex:/^[A-Za-z][A-Za-z\s\'\.-]{1,99}$/'],
            'mobileNo' => ['required', 'string', 'regex:/^\+?[0-9\s().-]{7,20}$/'],
            'eventtType' => ['required', 'in:in-person,online,hybrid'],
            'unique_token' => ['nullable', 'string', 'max:255'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $currentdate = date('Y-m-d H:i:s');
        $current_timestamp = Carbon::now()->timestamp;
        $zoomEventId = "";
        $lobbyEventId = $request->eventId;
        $uniqueToken = $request->unique_token;
        $fullName = $request->fullname . '';
        $mobileNo = $request->mobileNo;
        $club = $request->club;
        $eventtType = $request->eventtType;
        $registrant_id = '';
        $join_url = '';
        $lobbyLastName = ' g';
        $eLocation = "";
        $eStartDate1 = "";

        $uniqueLink = null;
        if (!empty($uniqueToken)) {
            $uniqueLink = LoungeEventUniqueLink::where('token', $uniqueToken)->first();

            if (!$uniqueLink || $uniqueLink->isExpired()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'This invitation link has already been used or has expired.',
                ], 410);
            }

            if ((int) $uniqueLink->event_id !== (int) $lobbyEventId) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'This invitation link does not match this event.',
                ], 422);
            }
        }

        if ($eventtType == "in-person") {
            $eventsData1 = DB::select("select * from events where id = ?", [$lobbyEventId]);
            if (count($eventsData1) > 0) {
                foreach ($eventsData1 as $visitEvents) {
                    $eLocation = $visitEvents->location;
                    $eStartDate1 = $visitEvents->start_date;
                }
            }
            DB::table('Wo_Clickfunnel_Event_Free')->insert(
                ['event_id' => $lobbyEventId, 'email_id' => $emailId, 'full_name' => $fullName, 'mobile_no' => $mobileNo, 'club' => $club, 'created' => $currentdate]
            );

            DB::table('Wo_Clickfunnel_Event_Purchase')->insert(
                ['event_id' => $lobbyEventId, 'email_id' => $emailId, 'full_name' => $fullName, 'mobile_no' => $mobileNo, 'club' => $club, 'created' => $currentdate, 'zoom_url' => $join_url, 'registrant_id' => $join_url, 'amount' => '0', 'type' => 'general', 'user_id' => '1', 'is_purchased' => false]
            );
            $finalDate = date('l, d-m-Y', strtotime($eStartDate1));
            $eStartDate = date('H:i', strtotime($eStartDate1));
            $club1 = ucfirst(strtolower($club));
            $data12 = array('name' => $fullName, 'email' => $emailId, 'club' => $club1, 'refno' => $current_timestamp, 'eLocation' => $eLocation, 'eStartDate' => $eStartDate, 'finalDate' => $finalDate);

            try {
                //  Mail::send('en.Emails.messageInPerson', $data12, function($message)use($emailId) {
                // 	 $message->to($emailId)->subject
                // 		('Global Woman Club Meeting information');
                // 	 $message->from('admin@globalwomanclub.com','Admin');
                //   });
            } catch (\Exception $e) {
            }
        } else {
            // dd('check2');
            $eventsData1 = DB::select("select * from Wo_Clickfunnel_ZoomEvent_Data where event_id = ?", [$lobbyEventId]);
            // dd($eventsData1);
            if (count($eventsData1) > 0) {
                foreach ($eventsData1 as $visitEvents) {
                    $zoomEventId = $visitEvents->zoom_id;
                }
            }

            // $curl = curl_init();

            // curl_setopt_array($curl, array(
            //     CURLOPT_URL => "https://api.zoom.us/v2/meetings/" . $zoomEventId . "/registrants",
            //     CURLOPT_RETURNTRANSFER => true,
            //     CURLOPT_ENCODING => "",
            //     CURLOPT_MAXREDIRS => 10,
            //     CURLOPT_TIMEOUT => 0,
            //     CURLOPT_FOLLOWLOCATION => true,
            //     CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            //     CURLOPT_CUSTOMREQUEST => "POST",
            //     CURLOPT_POSTFIELDS => "{\r\n    \"email\": \"$emailId\",\r\n    \"first_name\": \"$fullName\",\r\n    \"last_name\": \"$lobbyLastName\",\r\n    \"address\": \"\",\r\n    \"city\": \"\",\r\n    \"country\": \"\",\r\n    \"zip\": \"\",\r\n    \"state\": \"\",\r\n    \"phone\": \"\",\r\n    \"industry\": \"\",\r\n    \"org\": \"\",\r\n    \"job_title\": \"\",\r\n    \"purchasing_time_frame\": \"\",\r\n    \"role_in_purchase_process\": \"\",\r\n    \"no_of_employees\": \"\",\r\n    \"comments\": \"\",\r\n    \"custom_questions\": []\r\n}",
            //     CURLOPT_HTTPHEADER => array(
            //         "Authorization:Bearer eyJzdiI6IjAwMDAwMSIsImFsZyhI6IkhTNTEyIiwidiI6IjIuMCIsImtpZCI6IjYxMDMyN2ZhLWE0MTEtNGI2Mi1hYWFjLWNhYzUzMzlmY2E0ZiJ9.eyJhdWQiOiJodHRwczovL29hdXRoLnpvb20udXMiLCJ1aWQiOiI3RTA1cXJlOFNYT0lyNW1VbTNnMVlnIiwidmVyIjo5LCJhdWlkIjoiNTZlYzIyZjZjNDE0OGE5M2Q1Mjg3ZTk1NTViNTQ0ODQiLCJuYmYiOjE2ODg1NjQ1NDAsImNvZGUiOiJtaHBDVU45alR1bS1WNldUem5TT1hRNFlQUjRYNHBzME0iLCJpc3MiOiJ6bTpjaWQ6Q0dZbTU5NjFTTW1SbzRzTzhDTG9nUSIsImdubyI6MCwiZXhwIjoxNjg4NTY4MTQwLCJ0eXBlIjozLCJpYXQiOjE2ODg1NjQ1NDAsImFpZCI6Iks3UUJUby1vUlh1c3phU01lSWpUREEifQ.jybrJH9oYdtNMZ_61InVsEyiS8_ll7fR_fLX9jCFXeveOl9s9anivHUw8DvJGrK_z4jVXteacJInU_2xDdMmbA",
            //         "Content-Type: application/json",
            //         "Cookie: cred=4F1EFECA0E7A3119794ECAE0762FCC5E"
            //     ),
            // ));

            // $response = curl_exec($curl);

            // $registrant_id = null;
            // $join_url = null;

            // curl_close($curl);
            // $json = json_decode($response, true);
            try {
                if (isset($json['registrant_id'])) {
                    $registrant_id = $json['registrant_id'];
                    $join_url = $json['join_url'];
                }

                DB::table('Wo_Clickfunnel_Event_Free')->insert(
                    ['event_id' => $lobbyEventId, 'email_id' => $emailId, 'full_name' => $fullName, 'mobile_no' => $mobileNo, 'club' => $club, 'created' => $currentdate]
                );

                DB::table('Wo_Clickfunnel_Event_Purchase')->insert(
                    ['event_id' => $lobbyEventId, 'email_id' => $emailId, 'full_name' => $fullName, 'mobile_no' => $mobileNo, 'club' => $club, 'created' => $currentdate, 'zoom_url' => $join_url, 'registrant_id' => $registrant_id, 'amount' => '0', 'type' => 'general', 'user_id' => '1']
                );
            } catch (\Exception $e) {
                echo $e->getMessage();
            }
        }

        if ($uniqueLink) {
            $uniqueLink->expires_at = now();
            $uniqueLink->save();

            $getUrl = config('app.url') . '/club/' . $request->eventId;
            $data = array('name' => $fullName, 'email' => $emailId, 'club' => $club, 'urlData' => $getUrl);
            try {
                Mail::send('freemail', $data, function ($message) use ($emailId) {
                    $message->to($emailId)->subject('You are invited for Global Woman Club Event');
                    $message->from('admin@globalwomanclub.com', 'Admin');
                });
            } catch (\Exception $e) {
                Log::error('Failed to send email: ' . $e->getMessage());
            }
        }

        return response()->json([
            'status' => 'success',
        ]);
    }




    public function updateZoomRegister(Request $request)
    {
        return '';
        $emailId = $request->emailId;
        $fullName = $request->fullname;
        $lobbyEventId = '367';
        $registrant_id = '';
        $join_url = '';
        $lobbyLastName = '';

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://api.zoom.us/v2/meetings/82168591386/registrants",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => "{\r\n    \"email\": \"$emailId\",\r\n    \"first_name\": \"$fullName\",\r\n    \"last_name\": \"$lobbyLastName\",\r\n    \"address\": \"\",\r\n    \"city\": \"\",\r\n    \"country\": \"\",\r\n    \"zip\": \"\",\r\n    \"state\": \"\",\r\n    \"phone\": \"\",\r\n    \"industry\": \"\",\r\n    \"org\": \"\",\r\n    \"job_title\": \"\",\r\n    \"purchasing_time_frame\": \"\",\r\n    \"role_in_purchase_process\": \"\",\r\n    \"no_of_employees\": \"\",\r\n    \"comments\": \"\",\r\n    \"custom_questions\": []\r\n}",
            CURLOPT_HTTPHEADER => array(
                "Authorization: Bearer eyJzdiI6IjAwMDAwMSIsImFsZyI6IkhTNTEyIiwidiI6IjIuMCIsImtpZCI6IjYxMDMyN2ZhLWE0MTEtNGI2Mi1hYWFjLWNhYzUzMzlmY2E0ZiJ9.eyJhdWQiOiJodHRwczovL29hdXRoLnpvb20udXMiLCJ1aWQiOiI3RTA1cXJlOFNYT0lyNW1VbTNnMVlnIiwidmVyIjo5LCJhdWlkIjoiNTZlYzIyZjZjNDE0OGE5M2Q1Mjg3ZTk1NTViNTQ0ODQiLCJuYmYiOjE2ODg1NjQ1NDAsImNvZGUiOiJtaHBDVU45alR1bS1WNldUem5TT1hRNFlQUjRYNHBzME0iLCJpc3MiOiJ6bTpjaWQ6Q0dZbTU5NjFTTW1SbzRzTzhDTG9nUSIsImdubyI6MCwiZXhwIjoxNjg4NTY4MTQwLCJ0eXBlIjozLCJpYXQiOjE2ODg1NjQ1NDAsImFpZCI6Iks3UUJUby1vUlh1c3phU01lSWpUREEifQ.jybrJH9oYdtNMZ_61InVsEyiS8_ll7fR_fLX9jCFXeveOl9s9anivHUw8DvJGrK_z4jVXteacJInU_2xDdMmbA",
                "Content-Type: application/json",
                "Cookie: cred=4F1EFECA0E7A3119794ECAE0762FCC5E"
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        $json = json_decode($response, true);
        try {
            $registrant_id = $json['registrant_id'];
            $join_url = $json['join_url'];

            $affected = DB::table('Wo_Clickfunnel_Event_Purchase')
                ->where('event_id', $lobbyEventId)
                ->where('email_id', $emailId)
                ->update(['zoom_url' => $join_url, 'registrant_id' => $registrant_id]);
        } catch (\Exception $e) {
            echo $e->getMessage();
        }
        $a = 'test';
        return $a;
    }
}
