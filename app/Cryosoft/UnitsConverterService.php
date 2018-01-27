<?php

namespace App\Cryosoft;

use Illuminate\Contracts\Auth\Factory as Auth;
use App\Cryosoft\ValueListService;
use App\Models\Unit;
use App\Models\UserUnit;
use App\Models\MonetaryCurrency;


class UnitsConverterService
{

    /**
    * @var Illuminate\Contracts\Auth\Factory
    */
    protected $auth;

    public function __construct(\Laravel\Lumen\Application $app)
    {
        $this->app = $app;
        $this->auth = $app['Illuminate\\Contracts\\Auth\\Factory'];
        $this->value = $app['App\\Cryosoft\\ValueListService'];
    }

    public function tmUnitTypeMapping()
    {
        $units =  [
            [
                "name" => "Conductivity",
                "value" => 10
            ],
            [
                "name" => "Consumption unit",
                "value" => 28
            ],
            [
                "name" => "Consumption unit (CO2)",
                "value" => 30
            ],
            [
                "name" => "Consumption unit (LN2)",
                "value" => 29
            ],
            [
                "name" => "Heat losses per hour",
                "value" => 31
            ],
            [
                "name" => "Heat losses per hour (CO2)",
                "value" => 33
            ],
            [
                "name" => "Heat losses per hour (LN2)",
                "value" => 32
            ],
            [
                "name" => "Cooldown",
                "value" => 34
            ],
            [
                "name" => "Cooldown (CO2)",
                "value" => 36
            ],
            [
                "name" => "Cooldown (LN2)",
                "value" => 35
            ],
            [
                "name" => "Convection coef",
                "value" => 14
            ],
            [
                "name" => "Convection speed",
                "value" => 13
            ],
            [
                "name" => "Density",
                "value" => 7
            ],
            [
                "name" => "Enthalpy",
                "value" => 9
            ],
            [
                "name" => "Equipment dimension",
                "value" => 21
            ],
            [
                "name" => "Evaporation",
                "value" => 26
            ],
            [
                "name" => "Fluid flow",
                "value" => 1
            ],
            [
                "name" => "Length",
                "value" => 3
            ],
            [
                "name" => "Line",
                "value" => 17
            ],
            [
                "name" => "Losses in get cold (Line)",
                "value" => 11
            ],
            [
                "name" => "Permanent Heat losses (Line)",
                "value" => 12
            ],
            [
                "name" => "Mass",
                "value" => 4
            ],
            [
                "name" => "Unit of mass (consumption)",
                "value" => 37
            ],
            [
                "name" => "Material Rise",
                "value" => 24
            ],
            [
                "name" => "Mesh cut",
                "value" => 20
            ],
            [
                "name" => "Pressure",
                "value" => 15
            ],
            [
                "name" => "Product dimension - product chart",
                "value" => 38
            ],
            [
                "name" => "Product flow",
                "value" => 2
            ],
            [
                "name" => "Product dimension",
                "value" => 19
            ],
            [
                "name" => "CO2 tank capacity",
                "value" => 25
            ],
            [
                "name" => "LN2 tank capacity",
                "value" => 18
            ],
            [
                "name" => "Slopes position",
                "value" => 23
            ],
            [
                "name" => "Specific Heat",
                "value" => 6
            ],
            [
                "name" => "Temperature",
                "value" => 8
            ],
            [
                "name" => "Thickness packing",
                "value" => 16
            ],
            [
                "name" => "Time",
                "value" => 5
            ],
            [
                "name" => "Carpet/Sieve width",
                "value" => 22
            ],
        ];

        return $units;
    }

    public function productFlowSymbol() 
    {
    	$unit = Unit::select("SYMBOL")->where("TYPE_UNIT", $this->value->PRODUCT_FOLLOW)->first();
    	return $unit->SYMBOL;
    }

    public function massSymbol() 
    {
        $unit = Unit::select("SYMBOL")->where("TYPE_UNIT", $this->value->MASS)->first();
    	return $unit->SYMBOL;
    }

    public function temperatureSymbol() {
        $unit = Unit::select("SYMBOL")->where("TYPE_UNIT", $this->value->TEMPERATURE)->first();
    	return $unit->SYMBOL;
    }

    public function temperatureSymbolUser() {
        $user = $this->auth->user();
        $userUnit = UserUnit::join('unit', 'user_unit.ID_UNIT', '=', 'unit.ID_UNIT')->where('ID_USER', $user->ID_USER)
        ->where("unit.TYPE_UNIT", $this->value->TEMPERATURE)->get();

    	return $userUnit[0]->SYMBOL;
    }

    public function perUnitOfMassSymbol() 
	{
        $unit = Unit::select("SYMBOL")->where("TYPE_UNIT", $this->value->MASS_PER_UNIT)->first();
    	return $unit->SYMBOL;
    }
    
    public function timeSymbol() {
        $unit = Unit::select("SYMBOL")->where("TYPE_UNIT", $this->value->TIME)->first();
    	return $unit->SYMBOL;
    }

    public function enthalpySymbol() 
    {
        $unit = Unit::select("SYMBOL")->where("TYPE_UNIT", $this->value->ENTHALPY)->first();
        return $unit->SYMBOL;
    }

    public function equipDimensionSymbol() 
    {
        $unit = Unit::select("SYMBOL")->where("TYPE_UNIT", $this->value->EQUIP_DIMENSION)->first();
        return $unit->SYMBOL;
    }

    public function convectionSpeedSymbol() 
    {
        $unit = Unit::select("SYMBOL")->where("TYPE_UNIT", $this->value->CONV_SPEED_UNIT)->first();
        return $unit->SYMBOL;
    }

    public function timePositionSymbol() 
    {
        $unit = Unit::select("SYMBOL")->where("TYPE_UNIT", $this->value->UNIT_TIME)->first();
        return $unit->SYMBOL;
    }

    public function convectionCoeffSymbol() {
        $unit = Unit::select("SYMBOL")->where("TYPE_UNIT", $this->value->CONV_COEFF)->first();
        return $unit->SYMBOL;
    }

    public function prodchartDimensionSymbol() {
        $unit = Unit::select("SYMBOL")->where("TYPE_UNIT", $this->value->PRODCHART_DIMENSION)->first();
        return $unit->SYMBOL;
    }
    
    public function monetarySymbol() 
    {
        $uMoney = $this->uMoney();
        return $uMoney["symbol"];
    }

    public function consumptionSymbol($energy, $type) 
    {
        $sValue = "";
        $sUnitLabel = "";

        if ($energy == 2) {
            switch ($type) {
                case 1:
                    $sUnitLabel = $this->value->CONSUMPTION_UNIT_LN2;
                    break;
                case 2:
                    $sUnitLabel = $this->value->CONSUM_MAINTIEN_LN2;
                    break;
                case 3:
                    $sUnitLabel = $this->value->CONSUM_MEF_LN2;
                    break;
                default:
                    $sUnitLabel = $this->value->CONSUMPTION_UNIT_LN2;
                    break;
            }

        } else if ($energy == 3) {
            switch ($type) {
                case 1:
                    $sUnitLabel = $this->value->CONSUMPTION_UNIT_CO2;
                    break;
                case 2:
                    $sUnitLabel = $this->value->CONSUM_MAINTIEN_CO2;
                    break;
                case 3:
                    $sUnitLabel = $this->value->CONSUM_MEF_CO2;
                    break;
                default:
                    $sUnitLabel = $this->value->CONSUMPTION_UNIT_CO2;
                    break;

            }

        } else {
            switch ($type) {
                case 1:
                    $sUnitLabel = $this->value->CONSUMPTION_UNIT;
                    break;
                case 2:
                    $sUnitLabel = $this->value->CONSUM_MAINTIEN;
                    break;
                case 3:
                    $sUnitLabel = $this->value->CONSUM_MEF;
                    break;
                default:
                    $sUnitLabel = $this->value->CONSUMPTION_UNIT;
            }

        }

        $unit = Unit::select("SYMBOL")->where("TYPE_UNIT", $sUnitLabel)->first();
        return $unit->SYMBOL;
    }

    public function unitConvert($unitType, $value, $decimal = 2)
    {
        $unit = Unit::select("COEFF_A", "COEFF_B")->where("TYPE_UNIT", $unitType)->first();
        if (!empty($unit)) 
            return $this->convertCalculator($value, $unit->COEFF_A, $unit->COEFF_B,  $decimal);
        else 
            return $value;
    }
    
    public function convertCalculator($value, $coeffA, $coeffB, $decimal = 2)
    {
        if ($decimal > 0)
            $number =  round(($value * $coeffA + $coeffB), $decimal, PHP_ROUND_HALF_DOWN);
        else
            $number =  round(($value * $coeffA + $coeffB), $decimal);

        return number_format((float)$number, $decimal, '.', '');
    }

    public function uNone()
    {
        return array(
            "coeffA" => "1.0",
            "coeffB" => "0.0",
            "symbol" => ""
        );
    }
    
    public function uPercent()
    {
        return array(
            "coeffA" => "100.0",
            "coeffB" => "0.0",
            "symbol" => "%"
        );
    }

    public function uMoney()
    {
        $user = $this->auth->user();
        $monetaryUnit = MonetaryCurrency::where("ID_MONETARY_CURRENCY", $user->ID_MONETARY_CURRENCY)->first();

        // $unit = Unit::where("TYPE_UNIT", 27)->where("SYMBOL", "like", "%" . $monetaryUnit->MONEY_TEXT . "%")->first();
        $unit = Unit::where("TYPE_UNIT", 27)->where("SYMBOL", $monetaryUnit->MONEY_SYMB)->first();

        $result = array();

        if ($unit == null) {
            $result = $this->uNone();
        } else {
            $result = [
                "coeffA" => $unit->COEFF_A,
                "coeffB" => $unit->COEFF_B,
                "symbol" => $unit->SYMBOL
            ];
        }

        return $result;
    }

    public function meshes($sValue, $typeUnit) 
    {
        $unit = Unit::select("COEFF_A", "COEFF_B")->where("TYPE_UNIT", $typeUnit)->first();
        $value = doubleval($sValue);
        $coeffA = 1000;
        $coeffB = 0;
        if ($value != null) $value = ($value - $coeffB) * $coeffA;

        return round($value, 2);
    }

    public function mass($value) 
    {
        $unit = Unit::select("COEFF_A", "COEFF_B")->where("TYPE_UNIT", $this->value->MASS)->first();
        return $this->convertCalculator($value, $unit->COEFF_A, $unit->COEFF_B, 3);
    }

    public function controlTemperature($value) {
        $unit = Unit::select("COEFF_A", "COEFF_B")->where("TYPE_UNIT", $this->value->TEMPERATURE)->first();
        return $this->convertCalculator($value, $unit->COEFF_A, $unit->COEFF_B, 0);
    }

    public function prodTemperature($value) {
        $unit = Unit::select("COEFF_A", "COEFF_B")->where("TYPE_UNIT", $this->value->TEMPERATURE)->first();
        return $this->convertCalculator($value, $unit->COEFF_A, $unit->COEFF_B, 1);
    }

    public function time($value) {
        $unit = Unit::select("COEFF_A", "COEFF_B")->where("TYPE_UNIT", $this->value->TIME)->first();
        return $this->convertCalculator($value, $unit->COEFF_A, $unit->COEFF_B, 1);
    }

    public function enthalpy($value) {
        $unit = Unit::select("COEFF_A", "COEFF_B")->where("TYPE_UNIT", $this->value->ENTHALPY)->first();
        return $this->convertCalculator($value, $unit->COEFF_A, $unit->COEFF_B, 3);
    }

    public function productFlow($value) {
        $unit = Unit::select("COEFF_A", "COEFF_B")->where("TYPE_UNIT", $this->value->PRODUCT_FLOW)->first();
        return $this->convertCalculator($value, $unit->COEFF_A, $unit->COEFF_B, 1);
    }

    public function equipDimension($value) {

        $unit = Unit::select("COEFF_A", "COEFF_B")->where("TYPE_UNIT", $this->value->EQUIP_DIMENSION)->first();
        return $this->convertCalculator($value, $unit->COEFF_A, $unit->COEFF_B);
    }

    public function convectionSpeed($value) {
        $unit = Unit::select("COEFF_A", "COEFF_B")->where("TYPE_UNIT", $this->value->CONV_SPEED)->first();
        return $this->convertCalculator($value, $unit->COEFF_A, $unit->COEFF_B, 1);
    }

    public function timeUnit($value)
    {
        $unit = Unit::select("COEFF_A", "COEFF_B")->where("TYPE_UNIT", $this->value->TIME)->first();
        return $this->convertCalculator($value, $unit->COEFF_A, $unit->COEFF_B, 1);
    }

    public function timePosition($value) {
        $unit = Unit::select("COEFF_A", "COEFF_B")->where("TYPE_UNIT", $this->value->UNIT_TIME)->first();
        return $this->convertCalculator($value, $unit->COEFF_A, $unit->COEFF_B, 3);
    }

    public function temperature($value) {
        $unit = Unit::select("COEFF_A", "COEFF_B")->where("TYPE_UNIT", $this->value->TEMPERATURE)->first();
        return $this->convertCalculator($value, $unit->COEFF_A, $unit->COEFF_B);
    }

    public function convectionCoeff($value) {
        $unit = Unit::select("COEFF_A", "COEFF_B")->where("TYPE_UNIT", $this->value->CONV_COEFF)->first();
        return $this->convertCalculator($value, $unit->COEFF_A, $unit->COEFF_B);
    }

    public function meshesUnit($value) {
        $unit = Unit::select("COEFF_A", "COEFF_B")->where("TYPE_UNIT", $this->value->MESH_CUT)->first();
        return $this->convertCalculator($value, $unit->COEFF_A, $unit->COEFF_B);
    }

    public function prodchartDimension($value) {
        $unit = Unit::select("COEFF_A", "COEFF_B")->where("TYPE_UNIT", $this->value->PRODCHART_DIMENSION)->first();
        return $this->convertCalculator($value, $unit->COEFF_A, $unit->COEFF_B);
    }

    public function none($value)
    {
        $uNone = $this->uNone();
        return $this->convertCalculator($value, $uNone["coeffA"], $uNone["coeffB"]);
    }

    public function toc($value) 
    {
        $uPercent = $this->uPercent();
        return $this->convertCalculator($value, $uPercent["coeffA"], $uPercent["coeffB"], 1);
    }

    public function precision($value) {
        $uNone = $this->uNone();
        return $this->convertCalculator($value, $uNone["coeffA"], $uNone["coeffB"], 3);
    }


    public function monetary($value, $nbDecimal = 3) 
    {
        $uMoney = $this->uMoney();
        return $this->convertCalculator($value, $uMoney["coeffA"], $uMoney["coeffB"],  $nbDecimal);
    }

    public function consumption($value, $energy, $type, $decimal = 2)
    {
        $sValue = "";
        $sUnitLabel = "";

        if ($energy == 2) {
            switch ($type) {
                case 1:
                    $sUnitLabel = $this->value->CONSUMPTION_UNIT_LN2;
                    break;
                case 2:
                    $sUnitLabel = $this->value->CONSUM_MAINTIEN_LN2;
                    break;
                case 3:
                    $sUnitLabel = $this->value->CONSUM_MEF_LN2;
                    break;
                default:
                    $sUnitLabel = $this->value->CONSUMPTION_UNIT_LN2;
                    break;
            }

        } else if ($energy == 3) {
            switch ($type) {
                case 1:
                    $sUnitLabel = $this->value->CONSUMPTION_UNIT_CO2;
                    break;
                case 2:
                    $sUnitLabel = $this->value->CONSUM_MAINTIEN_CO2;
                    break;
                case 3:
                    $sUnitLabel = $this->value->CONSUM_MEF_CO2;
                    break;
                default:
                    $sUnitLabel = $this->value->CONSUMPTION_UNIT_CO2;
                    break;

            }

        } else {
            switch ($type) {
                case 1:
                    $sUnitLabel = $this->value->CONSUMPTION_UNIT;
                    break;
                case 2:
                    $sUnitLabel = $this->value->CONSUM_MAINTIEN;
                    break;
                case 3:
                    $sUnitLabel = $this->value->CONSUM_MEF;
                    break;
                default:
                    $sUnitLabel = $this->value->CONSUMPTION_UNIT;
            }

        }

        return $this->unitConvert($sUnitLabel, $value, $decimal);
    }
}
