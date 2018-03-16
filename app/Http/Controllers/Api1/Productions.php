<?php

namespace App\Http\Controllers\Api1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Contracts\Auth\Factory as Auth;
use App\Cryosoft\UnitsConverterService;

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
    public function __construct(Request $request, Auth $auth, UnitsConverterService $unit)
    {
        $this->request = $request;
        $this->auth = $auth;
        $this->unit = $unit;
    }

    public function getProductionById($id) {
        $production = \App\Models\Production::find($id);
        $result = [];
        $result['ID_PRODUCTION'] = $production->ID_PRODUCTION;
        $result['ID_STUDY'] = $production->ID_STUDY;
        $result['AVG_T_DESIRED'] = $production->AVG_T_DESIRED;
        $result['PROD_FLOW_RATE'] = $production->PROD_FLOW_RATE;
        $result['AVG_T_INITIAL'] = $production->AVG_T_INITIAL;
        $result['DAILY_PROD'] = $this->unit->none($production->DAILY_PROD);
        $result['DAILY_STARTUP'] = $this->unit->none($production->DAILY_STARTUP);
        $result['WEEKLY_PROD'] = $this->unit->none($production->WEEKLY_PROD);
        $result['NB_PROD_WEEK_PER_YEAR'] = $this->unit->none($production->NB_PROD_WEEK_PER_YEAR);
        $result['AMBIENT_TEMP'] = $this->unit->none($production->AMBIENT_TEMP);
        $result['AMBIENT_HUM'] = $this->unit->none($production->AMBIENT_HUM);
        return $result;
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

        return (int) $production->save();
    }
}
