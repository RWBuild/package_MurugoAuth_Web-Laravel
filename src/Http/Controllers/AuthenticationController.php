<?php

namespace RwandaBuild\MurugoAuth\Http\Controllers;

use Illuminate\Http\Request;
use App\User;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;

class AuthenticationController extends Controller
{
    /**
     * Function than receive response object of murugo server
     * Check if user exist and save it if it does not
     * @param Request $request
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function getMurugoResponse(Request $request)
    {
        //Log::info("Murugo Object" . $request);
        if (!empty($request->all())) {

            //Grab object
            $userObject = User::where('murugo_user_id', '=', $request->murugo_user_id)->first();
            $userId = $userObject->id;
            $token = $userObject->token;

            $checkUser = $this->checkUSerExisting($request->murugo_user_id, $token);

            if (!$checkUser) {
                $user = new User();
                $user->name = $request->murugo_user_account_name;
                $user->email = $request->murugo_user_account_email;
                $user->murugo_user_id = $request->murugo_user_id;
                $user->token = $request->murugo_access_token;
                $user->token_expires_at = $request->expires_at;
                $user->save();
                return response(['response' => 'SUCCESS'], 200);
            }
            //Update the user with new access_token
            DB::table('users')->where('id', $userId)
                ->update(['token' => $request->murugo_access_token, 'token_expires_at' => $request->expires_at]);

            return response(['response' => 'SUCCESS'], 200);
        }
    }

    /**
     * This helper function check if user is exist by using murugo_user_id
     * @param $murugo_user_id
     * @param $murugo_access_token
     * @return
     */
    private function checkUSerExisting($murugo_user_id, $murugo_access_token)
    {
        return User::where('murugo_user_id', '=', $murugo_user_id)->where('token', '=', $murugo_access_token)->count();
    }

    /**
     * Function than gets the token of current logging in user
     * Check if that token exist and get user belongs to that token
     * If token is not exist, mostly for new user, create new user and set token as well
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function authenticateUser(Request $request)
    {
        $uuid = $request->uuid;

        $user = User::where('murugo_user_id', '=', $uuid)->first();

        if (!$user) {
            return view('home');
        }

        //check if access token is valid every time before authenticate user, do the request from murugo
        try {
            $client = new Client();
            $response = $client->request('GET', env('MURUGO_URL') . 'api/thirdparty-me', [
                'headers' => [
                    'Authorization' => "Bearer $user->token",
                    'Accept' => 'application/json'
                ]
            ]);
            json_decode($response->getBody()->getContents());
            Auth::login($user);
            return view('home');
        } catch (ClientException $exception) {
            $this->catchError($exception);
            return view('home');
        }
    }

    private function catchError($exception)
    {
        $response = $exception->getResponse();
        if (!$response) return response('Check your internet connection');
        $statusCode = $response->getStatusCode();
        //$error = json_decode($response->getBody());
        return response($statusCode);
    }

    /**
     * Unset sessions and destroy all of them in sessions variables
     * Check if sessions are destroyed and redirect user on login page
     */
    public function logoutUser()
    {
        //Select user who is going to logout, grab his token like this Auth::user()->token
        $userToken = Auth::user()->token;
        //Log::info(Auth::user()->token);
        try {
            //Logout on murugo by sending request to murugo to destroy the token
            $client = new Client();
            $response = $client->request('GET', env('MURUGO_URL') . 'api/thirdparty-logout', [
                'headers' => [
                    'Authorization' => "Bearer $userToken",
                    'Accept' => 'application/json'
                ]
            ]);
            json_decode($response->getBody()->getContents());
            //Logout on 3rd party
            Auth::logout();
            return view('login');
        } catch (ClientException $exception) {
            $this->catchError($exception);
            return view('home');
        }
    }
}
