<?php

namespace App\Http\Controllers\Api1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Contracts\Auth\Factory as Auth;

class Components extends Controller
{

    /**
     * @var Illuminate\Http\Request
     */
    protected $request;

    /**
     * @var Illuminate\Contracts\Auth\Factory
     */
    protected $auth;
    

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(Request $request, Auth $auth)
    {
        $this->request = $request;
        $this->auth = $auth;
    }

    public function findComponents() {
        $active = \App\Models\Component::where('BLS_CODE','=', '')->where('COMP_RELEASE','=', '3')->get();
        $sleeping = \App\Models\Component::where('BLS_CODE','=', '')->where('COMP_RELEASE','=', '6')->get();
        return compact('active', 'sleeping');
    }
}
