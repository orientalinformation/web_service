<?php

namespace App\Http\Controllers\Api1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Contracts\Auth\Factory as Auth;
use App\Models\Unit;

class Units extends Controller
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

    public function createUnit()
    {
        $input = $this->request->all();
        $countUnit =  Unit::where('TYPE_UNIT', $input['TYPE_UNIT'])->where('SYMBOL', $input['SYMBOL'])->count();
        if ($countUnit > 0) {
            return response([
                'code' => 1000,
                'message' => 'This symbol already exists !'
            ], 406);
        }

        $unit = new Unit();
        $unit->TYPE_UNIT = $input['TYPE_UNIT'];
        $unit->SYMBOL = $input['SYMBOL'];
        $unit->COEFF_A = $input['COEFF_A'];
        $unit->COEFF_B = $input['COEFF_B'];
        $unit->save();

        return $unit;
    }

    public function saveUnit()
    {   
        $input = $this->request->all();
        $countUnit =  Unit::where('ID_UNIT', '<>', $input['ID_UNIT'])->where('TYPE_UNIT', $input['TYPE_UNIT'])->where('SYMBOL', $input['SYMBOL'])->count();
        if ($countUnit > 0) {
            return response([
                'code' => 1000,
                'message' => 'This symbol already exists !'
            ], 406);
        }
        
        $id = $input['ID_UNIT'];
        $unit = Unit::find($id);
        $unit->TYPE_UNIT = $input['TYPE_UNIT'];
        $unit->SYMBOL = $input['SYMBOL'];
        $unit->COEFF_A = $input['COEFF_A'];
        $unit->COEFF_B = $input['COEFF_B'];
        $unit->save();

        return $unit;
    }
}
