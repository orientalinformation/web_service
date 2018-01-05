<?php

namespace App\Http\Controllers\Api1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Contracts\Auth\Factory as Auth;

class Productions extends Controller
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

    public function getProductionById($id) {
        $production = \App\Models\Production::find($id);
        return $production;
    }

    public function saveProduction($id) {
        // @var \App\Models\Production
        $production = \App\Models\Production::find($id);

        $update = (object) $this->request->json()->all();
        $production->DAILY_PROD             = $update->DAILY_PROD;
        $production->DAILY_STARTUP          = $update->DAILY_STARTUP;
        $production->WEEKLY_PROD            = $update->WEEKLY_PROD;
        $production->PROD_FLOW_RATE         = $update->PROD_FLOW_RATE;
        $production->NB_PROD_WEEK_PER_YEAR  = $update->NB_PROD_WEEK_PER_YEAR;
        $production->AMBIENT_TEMP           = $update->AMBIENT_TEMP;
        $production->AMBIENT_HUM            = $update->AMBIENT_HUM;
        $production->AVG_T_DESIRED          = $update->AVG_T_DESIRED;
        $production->AVG_T_INITIAL          = $update->AVG_T_INITIAL;
        $production->APPROX_DWELLING_TIME   = $update->APPROX_DWELLING_TIME;

        return (int) $production->save();
    }
}
