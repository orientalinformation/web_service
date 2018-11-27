<?php

namespace App\Http\Controllers\Api1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Tymon\JWTAuth\JWTAuth;
use App\Models\Connection;
use Carbon\Carbon;


class Auth extends Controller
{
    /**
     * @var \Tymon\JWTAuth\JWTAuth
     */
    protected $jwt;

    /**
     * @var \Illuminate\Contracts\Auth\Factory
     */
    protected $auth;

    public function __construct(JWTAuth $jwt, \Illuminate\Contracts\Auth\Factory $auth)
    {
        $this->jwt = $jwt;
        $this->auth = $auth;
    }

    public function login(Request $request)
    {
        $this->validate($request, [
            'username'    => 'required|max:255',
            'password' => 'required',
        ]);

        try {
            if (! $token = $this->jwt->attempt($request->only('username', 'password'))) {
                return response()->json(['Username or Password is incorrect'], 404);
            }
        } catch (\Tymon\JWTAuth\Exceptions\TokenExpiredException $e) {
            return response()->json(['token_expired'], 500);
        } catch (\Tymon\JWTAuth\Exceptions\TokenInvalidException $e) {
            return response()->json(['token_invalid'], 500);
        } catch (\Tymon\JWTAuth\Exceptions\JWTException $e) {
            return response()->json(['token_absent' => $e->getMessage()], 500);
        }

        $connection = Connection::where('ID_USER', $this->auth->user()->ID_USER)
                        ->where('TYPE_DISCONNECTION', 0)->first();
        if ($connection) {
            return response()->json(['User already connected'], 404);
        }

        $current = Carbon::now();
        $current->timezone = 'Asia/Ho_Chi_Minh';

        $user = $this->auth->user();
        if ($user) {
            $user->USERMAIL = null;

            $connection = new Connection();
            $connection->ID_USER = $user->ID_USER;
            $connection->DATE_CONNECTION = $current->toDateTimeString();
            $connection->DATE_DISCONNECTION = null;
            $connection->TYPE_DISCONNECTION = 0;
            $connection->save();
        }

        return response()->json(compact('token','user'));
    }

    public function logout()
    {
        $connections = null;
        $current = Carbon::now();
        $current->timezone = 'Asia/Ho_Chi_Minh';

        if ($this->auth->user()) {
            $connections = Connection::where('ID_USER', $this->auth->user()->ID_USER)
                            ->where('TYPE_DISCONNECTION', 0)->get();
        }

        if (count($connections) > 0) {
            foreach ($connections as $connection) {
                $connection->DATE_DISCONNECTION = $current->toDateTimeString();
                $connection->TYPE_DISCONNECTION = 2;
                $connection->update();
            }
        }
    }
}
