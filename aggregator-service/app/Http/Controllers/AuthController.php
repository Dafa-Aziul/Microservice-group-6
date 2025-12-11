<?php

namespace App\Http\Controllers;

use App\Http\Controllers\AggregatorBaseController;
use Illuminate\Http\Request;

class AuthController extends AggregatorBaseController
{
    public function register(Request $request)
    {
        return $this->sendToService($request, 'post', env('USER_SERVICE_URL') . '/auth/register', $request->all());
    }

    public function login(Request $request)
    {
        return $this->sendToService($request, 'post', env('USER_SERVICE_URL') . '/auth/login', $request->all());
    }

    public function me(Request $request)
    {
        return $this->sendToService($request, 'get', env('USER_SERVICE_URL') . '/auth/me');
    }

    public function logout(Request $request)
    {
        return $this->sendToService($request, 'post', env('USER_SERVICE_URL') . '/auth/logout');
    }
}
