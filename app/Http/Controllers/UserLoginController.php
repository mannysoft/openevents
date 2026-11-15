<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class UserLoginController extends Controller
{
    public function callback(Request $request)
    {
        // $app_id = '77d26d549bb4fa49cc62'; // your application app id / orig
        $app_id = config('services.lobby_app.app_id'); // your application app id
        $app_secret = config('services.lobby_app.app_secret'); // your application app secret
        if ($request->code == null) {
            return redirect('https://lobby.globalwomanclub.com/oauth?app_id=' . $app_id . '&loginoauth=1');
        } else {
            // GET parameter you got in the callback: http://yourdomain/?code=XXX
            $code = $request->code;
            $get = $this->curl_get_contents("https://lobby.globalwomanclub.com/authorize?app_id={$app_id}&app_secret={$app_secret}&code={$code}");

            $json = json_decode($get, true);

            if (isset($json['access_token'])) {
                $access_token = $json['access_token']; // your access token
                $reference = $json['reference'];
                $cookie_name = "access_token";
                $type = "get_user_data"; // or posts_data
                $get = $this->curl_get_contents("https://lobby.globalwomanclub.com/app_api?access_token={$access_token}&type={$type}");
                $character = json_decode($get, true);
                $user_id = $character['user_data']['ref_id'];
                $firstname = $character['user_data']['first_name'];
                $avatar = $character['user_data']['avatar'];
                $lastname = $character['user_data']['last_name'];
                $email = $character['user_data']['email'];
                $ispro = $character['user_data']['ispro'];
                $protype = $character['user_data']['isprotype'];
                $director = $character['user_data']['director'];
                if ($protype == "4" || $protype == "3" || $director == "1") {
                    $member = '1';
                }

                session([
                    $cookie_name => $reference,
                    'ref_id' => $user_id,
                    'events_email' => $email,
                ]);

                Log::info('callback email:' . $email);

                // Check from lounge table if the user subscription still an active
                $ref_no = $character['user_data']['ref_no'];
                $phone = $character['user_data']['phone'];
                session([
                    'avatar' => $avatar,
                    'email' => $email,
                    'firstname' => $firstname,
                    'lastname' => $lastname,
                    'phone' => $phone,
                    'member' => is_member($email),
                ]);
                $ciphering = "AES-128-CTR";
                $options = 0;

                $decryption_iv = '1234567891011121';
                $decryption_key = "AKIAJYYY2N66QTOBWGEQ";
                $decryption = openssl_decrypt(
                    $ref_no,
                    $ciphering,
                    $decryption_key,
                    $options,
                    $decryption_iv
                );

                $account = Account::where('email', $email)->first();

                if ($account) {
                    if ($ispro == 1 && $protype == 3 || $protype == 4) {
                        $account = Account::where('email', $email)->first();

                        if ($account) {
                            if (Auth::attempt(['email' => $email, 'password' => $decryption], true) === false) {
                                return Redirect::back()
                                    ->with(['message' => trans('Controllers.login_password_incorrect'), 'failed' => true])
                                    ->withInput();
                            } else {

                                // If we have last_url in session
                                if (session('last_url')) {
                                    return redirect(session('last_url'));
                                }

                                return redirect(config('app.url'));
                            }
                        } else {
                            $user = User::where('email', $email)->first();
                            if ($user) {
                                $account_data['email'] = $email;
                                $account_data['first_name'] = $firstname;
                                $account_data['last_name'] = $lastname;
                                $account_data['currency_id'] = config('attendize.default_currency');
                                $account_data['timezone_id'] = config('attendize.default_timezone');
                                $account = Account::create($account_data);
                            } else {
                                $account_data['email'] = $email;
                                $account_data['first_name'] = $firstname;
                                $account_data['last_name'] = $lastname;
                                $account_data['currency_id'] = config('attendize.default_currency');
                                $account_data['timezone_id'] = config('attendize.default_timezone');
                                $account = Account::create($account_data);

                                $user = new User();
                                $user_data['email'] = $email;
                                $user_data['first_name'] = $firstname;
                                $user_data['last_name'] = $lastname;
                                $user_data['password'] = Hash::make($decryption);
                                $user_data['account_id'] = $account->id;
                                $user_data['is_parent'] = 1;
                                $user_data['is_registered'] = 1;
                                $user = User::create($user_data);
                            }
                            if (Auth::attempt(['email' => $email, 'password' => $decryption], true) === false) {
                                return Redirect::back()
                                    ->with(['message' => trans('Controllers.login_password_incorrect'), 'failed' => true])
                                    ->withInput();
                            }

                            // If we have last_url in session
                            if (session('last_url')) {
                                return redirect(session('last_url'));
                            }

                            return redirect(config('app.url'));
                        }
                    } else {
                        return Redirect::back()
                            ->with(['message' => trans('Sorry:You dont have any plan subscribed to access this feature.'), 'failed' => true])
                            ->withInput();
                    }
                } else {

                    // If we have last_url in session
                    if (session('last_url')) {
                        return redirect(session('last_url'));
                    }
                    return redirect(config('app.url'));
                }
            }
        }
    }

    function curl_get_contents($url)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.13) Gecko/20080311 Firefox/2.0.0.13');
        // Without these the call is unbounded: a slow or unreachable Lobby pins this
        // php-fpm worker until the OS gives up on the socket. Enough concurrent
        // requests doing that exhaust the pool, and nginx answers 502.
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        $data = curl_exec($ch);

        // The URL carries the app secret and the sign-in code, so it stays out of the log.
        if ($data === false) {
            Log::warning('curl call failed', ['call' => 'lobby-oauth', 'curl_errno' => curl_errno($ch)]);
        }

        curl_close($ch);
        return $data;
    }

    /**
     * Shows login form.
     *
     * @param  Request  $request
     *
     * @return mixed
     */
    public function showLogin(Request $request)
    {
        /*
         * If there's an ajax request to the login page assume the person has been
         * logged out and redirect them to the login page
         */
        if ($request->ajax()) {
            return response()->json([
                'status'      => 'success',
                'redirectUrl' => route('login'),
            ]);
        }

        return View::make('Public.LoginAndRegister.Login');
    }

    /**
     * Handles the login request.
     *
     // * @param  Request  $request
     // *
     // * @return mixed
     */
    // public function postLogin(Request $request)
    // {
    //     $email = $request->get('email');
    //     $password = $request->get('password');
    //
    //     if (empty($email) || empty($password)) {
    //         return Redirect::back()
    //             ->with(['message' => trans('Controllers.fill_email_and_password'), 'failed' => true])
    //             ->withInput();
    //     }
    //
    // if (Auth::attempt(['email' => $email, 'password' => $password], true) === false) {
    //     return Redirect::back()
    //         ->with(['message' => trans('Controllers.login_password_incorrect'), 'failed' => true])
    //         ->withInput();
    // }
    //
    //     return redirect()->intended(route('showSelectOrganiser'));
    // }
}
