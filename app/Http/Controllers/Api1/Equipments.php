<?php

namespace App\Http\Controllers\Api1;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Request;


class Equipments extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    public function getEquipments()
    {
        $input = Request::all();

        //path params validation


        //not path params validation
        // $energy = $input['energy'];

        // $manufacturer = $input['manufacturer'];

        // $family = $input['family'];

        // $equip_origin = $input['equip_origin'];

        // $process_type = $input['process_type'];

        // $model = $input['model'];

        // $size = $input['size'];
        
        $equipments = \App\Models\Equipment::all()->toArray();

        return $equipments;
    }

}
