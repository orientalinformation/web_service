<?php

namespace App\Http\Controllers\Api1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Contracts\Auth\Factory as Auth;
use Illuminate\Support\Facades\DB;

use App\Models\MinMax;

use App\Cryosoft\UnitsConverterService;
use App\Cryosoft\EquipmentsService;


class MinMaxs extends Controller
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
    public function __construct(Request $request, Auth $auth, UnitsConverterService $unit, EquipmentsService $equip)
    {
        $this->request = $request;
        $this->auth = $auth;
        $this->unit = $unit;
        $this->equip = $equip;
    }

    public function getMinMaxProduction()
    {
        $mm = MinMax::where("LIMIT_ITEM", MIN_MAX_PRODUCT_DURATION)->first();
        $mmDaily = [
            'LIMIT_MIN' => $this->unit->none($mm->LIMIT_MIN, ['format' => false]),
            'LIMIT_MAX' => $this->unit->none($mm->LIMIT_MAX, ['format' => false]),
        ];

        $mm = MinMax::where("LIMIT_ITEM", MIN_MAX_WEEKLY_PRODUCTION)->first();
        $mmWeekly = [
            'LIMIT_MIN' => $this->unit->none($mm->LIMIT_MIN, ['format' => false]),
            'LIMIT_MAX' => $this->unit->none($mm->LIMIT_MAX, ['format' => false]),
        ];

        $mm = MinMax::where("LIMIT_ITEM", MIN_MAX_PROD_WEEK_PER_YEAR)->first();
        $mmAnnual = [
            'LIMIT_MIN' => $this->unit->none($mm->LIMIT_MIN, ['format' => false]),
            'LIMIT_MAX' => $this->unit->none($mm->LIMIT_MAX, ['format' => false]),
        ];

        $mm = MinMax::where("LIMIT_ITEM", MIN_MAX_DAILY_STARTUP)->first();
        $mmPerDay = [
            'LIMIT_MIN' => $this->unit->none($mm->LIMIT_MIN, ['format' => false]),
            'LIMIT_MAX' => $this->unit->none($mm->LIMIT_MAX, ['format' => false]),
        ];

        $mm = MinMax::where("LIMIT_ITEM", MIN_MAX_TEMP_AMBIANT)->first();
        $mmFactory = [
            'LIMIT_MIN' => $this->unit->temperature($mm->LIMIT_MIN, ['format' => false]),
            'LIMIT_MAX' => $this->unit->temperature($mm->LIMIT_MAX, ['format' => false]),
        ];

        $mm = MinMax::where("LIMIT_ITEM", MIN_MAX_PROCENT)->first();
        $mmHumidity = [
            'LIMIT_MIN' => $this->unit->none($mm->LIMIT_MIN, ['format' => false]),
            'LIMIT_MAX' => $this->unit->none($mm->LIMIT_MAX, ['format' => false]),
        ];

        $mm = MinMax::where("LIMIT_ITEM", MIN_MAX_TEMPERATURE)->first();
        $mmAverage = [
            'LIMIT_MIN' => $this->unit->prodTemperature($mm->LIMIT_MIN, ['format' => false]),
            'LIMIT_MAX' => $this->unit->prodTemperature($mm->LIMIT_MAX, ['format' => false]),
        ];

        $mm = MinMax::where("LIMIT_ITEM", MIN_MAX_FLOW_RATE)->first();
        $mmProdFlow = [
            'LIMIT_MIN' => $this->unit->productFlow($mm->LIMIT_MIN, ['format' => false]),
            'LIMIT_MAX' => $this->unit->productFlow($mm->LIMIT_MAX, ['format' => false]),
        ];

        return compact('mmDaily', 'mmWeekly', 'mmAnnual', 'mmPerDay', 'mmFactory', 'mmHumidity', 'mmAverage', 'mmProdFlow');
    }

    public function getMinMaxProductMeshPacking()
    {
        $mm = MinMax::where("LIMIT_ITEM", MIN_MAX_PRODUCT_DIM1)->first();
        $mmDim1 = [
            'LIMIT_MIN' => $this->unit->prodDimension($mm->LIMIT_MIN, ['format' => false]),
            'LIMIT_MAX' => $this->unit->prodDimension($mm->LIMIT_MAX, ['format' => false]),
        ];
        
        $mm = MinMax::where("LIMIT_ITEM", MIN_MAX_PRODUCT_DIM2)->first();
        $mmDim2 = [
            'LIMIT_MIN' => $this->unit->prodDimension($mm->LIMIT_MIN, ['format' => false]),
            'LIMIT_MAX' => $this->unit->prodDimension($mm->LIMIT_MAX, ['format' => false]),
        ];

        $mm = MinMax::where("LIMIT_ITEM", MIN_MAX_PRODUCT_DIM3)->first();
        $mmDim3 = [
            'LIMIT_MIN' => $this->unit->prodDimension($mm->LIMIT_MIN, ['format' => false]),
            'LIMIT_MAX' => $this->unit->prodDimension($mm->LIMIT_MAX, ['format' => false]),
        ];

        $mm = MinMax::where("LIMIT_ITEM", MIN_MAX_PRODUCT_WEIGHT)->first();
        $mmMass = [
            'LIMIT_MIN' => $this->unit->mass($mm->LIMIT_MIN, ['format' => false]),
            'LIMIT_MAX' => $this->unit->mass($mm->LIMIT_MAX, ['format' => false]),
        ];

        $mm = MinMax::where("LIMIT_ITEM", MIN_MAX_INITIAL_TEMPERATURE)->first();
        $mmTemp = [
            'LIMIT_MIN' => $this->unit->prodTemperature($mm->LIMIT_MIN, ['format' => false]),
            'LIMIT_MAX' => $this->unit->prodTemperature($mm->LIMIT_MAX, ['format' => false]),
        ];

        $mm = MinMax::where("LIMIT_ITEM", MIN_MAX_PACKING_THICKNESS)->first();
        $mmThickness = [
            'LIMIT_MIN' => $this->unit->packingThickness($mm->LIMIT_MIN, ['format' => false]),
            'LIMIT_MAX' => $this->unit->packingThickness($mm->LIMIT_MAX, ['format' => false]),
        ];

        return compact('mmDim1', 'mmDim2', 'mmDim3', 'mmMass', 'mmTemp', 'mmThickness');
    }

    public function getMinMaxEquipment($id)
    {
        $energy = $this->equip->initEnergyDef($id);
        $mm = MinMax::where("LIMIT_ITEM", MIN_MAX_ENERGY_PRICE)->first();
        $mmPrice = [
            'LIMIT_MIN' => $this->unit->cryogenPrice($mm->LIMIT_MIN, $energy, ['format' => false]),
            'LIMIT_MAX' => $this->unit->cryogenPrice($mm->LIMIT_MAX, $energy, ['format' => false]),
        ];

        $mm = MinMax::where("LIMIT_ITEM", 1034)->first();
        $mmLInterval = [
            'LIMIT_MIN' => $this->unit->prodDimension($mm->LIMIT_MIN, ['format' => false]),
            'LIMIT_MAX' => $this->unit->prodDimension($mm->LIMIT_MAX, ['format' => false]),
        ];

        $mm = MinMax::where("LIMIT_ITEM", 1033)->first();
        $mmWInterval = [
            'LIMIT_MIN' => $this->unit->prodDimension($mm->LIMIT_MIN, ['format' => false]),
            'LIMIT_MAX' => $this->unit->prodDimension($mm->LIMIT_MAX, ['format' => false]),
        ];
        
        return compact('mmPrice', 'mmLInterval', 'mmWInterval');
    }

}