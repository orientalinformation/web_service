<?php

namespace App\Http\Controllers\Api1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Contracts\Auth\Factory as Auth;
use App\Models\Component;
use App\Models\Translation;

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

    public function findMyComponents() 
    {        
        $mine = Component::where('ID_USER', $this->auth->user()->ID_USER)
        ->join('Translation', 'ID_COMP', '=', 'Translation.ID_TRANSLATION')
        ->where('Translation.TRANS_TYPE', 1)->where('Translation.CODE_LANGUE', $this->auth->user()->CODE_LANGUE)
        ->orderBy('LABEL', 'ASC')->get();

        $others = Component::where('ID_USER', '!=', $this->auth->user()->ID_USER)
        ->join('Translation', 'ID_COMP', '=', 'Translation.ID_TRANSLATION')
        ->where('Translation.TRANS_TYPE', 1)->where('Translation.CODE_LANGUE', $this->auth->user()->CODE_LANGUE)
        ->orderBy('LABEL', 'ASC')->get();

        return compact('mine', 'others');
    }
}
