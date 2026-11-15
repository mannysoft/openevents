<?php

use Illuminate\Http\Request;

use App\Http\Controllers\API\MembersApiController;
/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::get('getZoomUrl/{id}', [MembersApiController::class, 'getHostUrl']);
Route::get('getMembersData', [MembersApiController::class, 'loginMember']);
Route::post('saveEvents', [MembersApiController::class, 'postCreateEvent']);
Route::post('saveEventsWebinar', [MembersApiController::class, 'postCreateEventWebinar']);
Route::post('saveEventsAll', [MembersApiController::class, 'postCreateEventALL']);
Route::post('updateEvents', [MembersApiController::class, 'updateEventsEvent']);
Route::post('updateEvents1', [MembersApiController::class, 'updateEventsEvent1']);
Route::post('deleteEvent', [MembersApiController::class, 'postDeleteEvent']);
Route::post('EventSpeakerData', [MembersApiController::class, 'getEventSpeakerData']);
Route::post('updateSpeakerPrice', [MembersApiController::class, 'updateSpeakerPrice']);
Route::post('rejectSpeaker', [MembersApiController::class, 'rejectSpeaker']);
Route::post('registerZoom', [MembersApiController::class, 'registerZoom']);
Route::post('saveFreeUser', [MembersApiController::class, 'saveFreeUser']);
Route::post('saveUserTest', [MembersApiController::class, 'saveUserTest']);
Route::post('saveRec', [MembersApiController::class, 'saveRec']);
Route::post('updateZoomRegister', [MembersApiController::class, 'updateZoomRegister']);
Route::post('saveUserData', [MembersApiController::class, 'saveUserDataEvent']);
Route::get('getUserAllEvent/{id}', [MembersApiController::class, 'getUserAllEvent']);

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

/*
 * ---------------
 * Organisers
 * ---------------
 */


/*
 * ---------------
 * Check-In / Check-Out
 * ---------------
 */
Route::post('saveContactGHLToEvents', function (Request $request) {

    $ghl = new \App\Services\GoHighLevel();
    $locationId = $ghl->locationId;

    $contact = [
        'firstName' => $request->first_name,
        'lastName' => $request->last_name,
        'email' => $request->email,
        'phone' => $request->mobile,
        'locationId' => $locationId,
        'tags' => ['apply membershit']
    ];

    $ghl->createUpdateContact($contact);

    return response()->json(['message' => 'Contact saved successfully']);
});
