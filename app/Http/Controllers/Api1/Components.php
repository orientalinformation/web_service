<?php

namespace App\Http\Controllers\Api1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Contracts\Auth\Factory as Auth;
use App\Models\Component;
use App\Models\Translation;
use App\Cryosoft\ProductService;

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
     * @var \App\Cryosoft\ProductService
     */
    protected $product;
    

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(Request $request, Auth $auth, ProductService $product)
    {
        $this->request = $request;
        $this->auth = $auth;
        $this->product = $product;
    }

    /*public function findComponents() {
        $active = \App\Models\Component::where('BLS_CODE','=', '')->where('COMP_RELEASE','=', '3')->get();
        $sleeping = \App\Models\Component::where('BLS_CODE','=', '')->where('COMP_RELEASE','=', '6')->get();
        return compact('active', 'sleeping');
    }*/

    public function findComponents() 
    {
        $input = $this->request->all();
        $idStudy = (!empty($input['idStudy'])) ? $input['idStudy'] : 0;
        $compfamily = (!empty($input['compfamily'])) ? $input['compfamily'] : 0;
        $subfamily = (!empty($input['subfamily'])) ? $input['subfamily'] : 0;
        $waterpercent = (!empty($input['waterpercent'])) ? $input['waterpercent'] : 0;
        $active = $this->product->getAllStandardComponents($idStudy, $compfamily, $subfamily, $waterpercent);
        $sleeping = $this->product->getAllSleepingComponents($compfamily, $subfamily, $waterpercent);
        return compact('active', 'sleeping');
    }

    public function findMyComponents() 
    {        
        $mine = Component::where('ID_USER', $this->auth->user()->ID_USER)
        ->join('TRANSLATION', 'ID_COMP', '=', 'TRANSLATION.ID_TRANSLATION')
        ->where('TRANSLATION.TRANS_TYPE', 1)->where('TRANSLATION.CODE_LANGUE', $this->auth->user()->CODE_LANGUE)
        ->orderBy('LABEL', 'ASC')->get();

        $others = Component::where('ID_USER', '!=', $this->auth->user()->ID_USER)
        ->join('TRANSLATION', 'ID_COMP', '=', 'TRANSLATION.ID_TRANSLATION')
        ->where('TRANSLATION.TRANS_TYPE', 1)->where('TRANSLATION.CODE_LANGUE', $this->auth->user()->CODE_LANGUE)
        ->orderBy('LABEL', 'ASC')->get();

        return compact('mine', 'others');
    }

    public function getAllCompFamily()
    {
        return $this->product->getAllCompFamily();
    }
}