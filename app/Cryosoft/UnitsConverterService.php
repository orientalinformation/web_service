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
        $unit = Unit::where('TYPE_UNIT', $this->value->PRODUCT_FOLLOW)
        ->join('user_unit', 'Unit.ID_UNIT', '=', 'user_unit.ID_UNIT')
        ->where('user_unit.ID_USER', $this->auth->user()->ID_USER)
        ->first();
        return $unit->SYMBOL;
    }

    public function massSymbol() 
    {
        $unit = Unit::where('TYPE_UNIT', $this->value->MASS)
        ->join('user_unit', 'Unit.ID_UNIT', '=', 'user_unit.ID_UNIT')
        ->where('user_unit.ID_USER', $this->auth->user()->ID_USER)
        ->first();
        return $unit->SYMBOL;
    }

    public function temperatureSymbol() {
        $unit = Unit::where('TYPE_UNIT', $this->value->TEMPERATURE)
        ->join('user_unit', 'Unit.ID_UNIT', '=', 'user_unit.ID_UNIT')
        ->where('user_unit.ID_USER', $this->auth->user()->ID_USER)
        ->first();
        return $unit->SYMBOL;
    }

    public function perUnitOfMassSymbol() 
    {
        $unit = Unit::where('TYPE_UNIT', $this->value->MASS_PER_UNIT)
        ->join('user_unit', 'Unit.ID_UNIT', '=', 'user_unit.ID_UNIT')
        ->where('user_unit.ID_USER', $this->auth->user()->ID_USER)
        ->first();
        return $unit->SYMBOL;
    }
    
    public function timeSymbol() {
        $unit = Unit::where('TYPE_UNIT', $this->value->TIME)
        ->join('user_unit', 'Unit.ID_UNIT', '=', 'user_unit.ID_UNIT')
        ->where('user_unit.ID_USER', $this->auth->user()->ID_USER)
        ->first();
        return $unit->SYMBOL;
    }

    public function enthalpySymbol() 
    {
        $unit = Unit::where('TYPE_UNIT', $this->value->ENTHALPY)
        ->join('user_unit', 'Unit.ID_UNIT', '=', 'user_unit.ID_UNIT')
        ->where('user_unit.ID_USER', $this->auth->user()->ID_USER)
        ->first();
        return $unit->SYMBOL;
    }

    public function equipDimensionSymbol() 
    {
        $unit = Unit::where('TYPE_UNIT', $this->value->EQUIP_DIMENSION)
        ->join('user_unit', 'Unit.ID_UNIT', '=', 'user_unit.ID_UNIT')
        ->where('user_unit.ID_USER', $this->auth->user()->ID_USER)
        ->first();
        return $unit->SYMBOL;
    }

    public function convectionSpeedSymbol() 
    {
        $unit = Unit::where('TYPE_UNIT', $this->value->CONV_SPEED)
        ->join('user_unit', 'Unit.ID_UNIT', '=', 'user_unit.ID_UNIT')
        ->where('user_unit.ID_USER', $this->auth->user()->ID_USER)
        ->first();
        return $unit->SYMBOL;
    }

    public function timePositionSymbol() 
    {
        $unit = Unit::where('TYPE_UNIT', $this->value->UNIT_TIME)
        ->join('user_unit', 'Unit.ID_UNIT', '=', 'user_unit.ID_UNIT')
        ->where('user_unit.ID_USER', $this->auth->user()->ID_USER)
        ->first();
        return $unit->SYMBOL;
    }

    public function convectionCoeffSymbol() {
        $unit = Unit::where('TYPE_UNIT', $this->value->CONV_COEFF)
        ->join('user_unit', 'Unit.ID_UNIT', '=', 'user_unit.ID_UNIT')
        ->where('user_unit.ID_USER', $this->auth->user()->ID_USER)
        ->first();
        return $unit->SYMBOL;
    }

    public function prodchartDimensionSymbol() 
    {
        $unit = Unit::where('TYPE_UNIT', $this->value->PRODCHART_DIMENSION)
        ->join('user_unit', 'Unit.ID_UNIT', '=', 'user_unit.ID_UNIT')
        ->where('user_unit.ID_USER', $this->auth->user()->ID_USER)
        ->first();
        return $unit->SYMBOL;
    }

    public function prodDimensionSymbol()
    {
        $unit = Unit::where('TYPE_UNIT', $this->value->PROD_DIMENSION)
        ->join('user_unit', 'Unit.ID_UNIT', '=', 'user_unit.ID_UNIT')
        ->where('user_unit.ID_USER', $this->auth->user()->ID_USER)
        ->first();
        return $unit->SYMBOL;
    }

    public function meshesSymbol()
    {
        $unit = Unit::where('TYPE_UNIT', $this->value->MESH_CUT)
        ->join('user_unit', 'Unit.ID_UNIT', '=', 'user_unit.ID_UNIT')
        ->where('user_unit.ID_USER', $this->auth->user()->ID_USER)
        ->first();
        return $unit->SYMBOL;
    }

    public function packingThicknessSymbol()
    {
        $unit = Unit::where('TYPE_UNIT', $this->value->THICKNESS_PACKING)
        ->join('user_unit', 'Unit.ID_UNIT', '=', 'user_unit.ID_UNIT')
        ->where('user_unit.ID_USER', $this->auth->user()->ID_USER)
        ->first();
        return $unit->SYMBOL;
    }

    public function lineDimensionSymbol()
    {
        $unit = Unit::where('TYPE_UNIT', $this->value->LINE)
        ->join('user_unit', 'Unit.ID_UNIT', '=', 'user_unit.ID_UNIT')
        ->where('user_unit.ID_USER', $this->auth->user()->ID_USER)
        ->first();
        return $unit->SYMBOL;
    }

    public function shelvesWidthSymbol()
    {
        $unit = Unit::where('TYPE_UNIT', $this->value->W_CARPET_SHELVES)
        ->join('user_unit', 'Unit.ID_UNIT', '=', 'user_unit.ID_UNIT')
        ->where('user_unit.ID_USER', $this->auth->user()->ID_USER)
        ->first();
        return $unit->SYMBOL;
    }

    public function pressureSymbol()
    {
        $unit = Unit::where('TYPE_UNIT', $this->value->PRESSURE)
        ->join('user_unit', 'Unit.ID_UNIT', '=', 'user_unit.ID_UNIT')
        ->where('user_unit.ID_USER', $this->auth->user()->ID_USER)
        ->first();
        return $unit->SYMBOL;
    }

    public function materialRiseSymbol()
    {
        $unit = Unit::where('TYPE_UNIT', $this->value->MATERIAL_RISE)
        ->join('user_unit', 'Unit.ID_UNIT', '=', 'user_unit.ID_UNIT')
        ->where('user_unit.ID_USER', $this->auth->user()->ID_USER)
        ->first();
        return $unit->SYMBOL;
    }

    public function carpetWidthSymbol()
    {
        $unit = Unit::where('TYPE_UNIT', $this->value->W_CARPET_SHELVES)
        ->join('user_unit', 'Unit.ID_UNIT', '=', 'user_unit.ID_UNIT')
        ->where('user_unit.ID_USER', $this->auth->user()->ID_USER)
        ->first();
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

        $unit = Unit::where('TYPE_UNIT', $sUnitLabel)
        ->join('user_unit', 'Unit.ID_UNIT', '=', 'user_unit.ID_UNIT')
        ->where('user_unit.ID_USER', $this->auth->user()->ID_USER)
        ->first();
        return $unit->SYMBOL;
    }

    public function unitConvert($unitType, $value, $decimal = 2)
    {
        $unit = Unit::where('TYPE_UNIT', $unitType)
        ->join('user_unit', 'Unit.ID_UNIT', '=', 'user_unit.ID_UNIT')
        ->where('user_unit.ID_USER', $this->auth->user()->ID_USER)
        ->first();
        if ($unit) 
            return $this->convertCalculator($value, $unit->COEFF_A, $unit->COEFF_B,  $decimal);
        else 
            return $value;
    }
    
    public function convertCalculator($value, $coeffA, $coeffB, $decimal = 2, $options = null)
    {
        $number = $value * $coeffA + $coeffB;
        if (floor( $value ) != $value) {
            $number = round(($number), $decimal, PHP_ROUND_HALF_UP);
            $number = floor($number * pow(10, $decimal)) / pow(10, $decimal);
        } else {
            $number = round(($value * $coeffA + $coeffB), $decimal);
        }

        if (isset($options['save']) && $options['save'] == true) {
            return ($value - $coeffB) / $coeffA;
        } elseif (isset($options['format']) && $options['format'] == false) {
            return $number;
        } else {
            return number_format((float)$number, $decimal, '.', '');
        }
        
    }

    public function convertUnitSave($value, $coeffA, $coeffB)
    {
        $number = ($value - $coeffB) / $coeffA;

        return $number;
    }

    public function uNone()
    {
        return array(
            "coeffA" => 1,
            "coeffB" => 0,
            "symbol" => ""
        );
    }
    
    public function uPercent()
    {
        return array(
            "coeffA" => 100,
            "coeffB" => 0,
            "symbol" => "%"
        );
    }

    public function uMoney()
    {
        $user = $this->auth->user();
        $monetaryUnit = MonetaryCurrency::where("ID_MONETARY_CURRENCY", $user->ID_MONETARY_CURRENCY)->first();

        // $unit = Unit::where("TYPE_UNIT", 27)->where("SYMBOL", "like", "%" . $monetaryUnit->MONEY_TEXT . "%")->first();
        /*$unit = Unit::where("TYPE_UNIT", 27)->where("SYMBOL", $monetaryUnit->MONEY_SYMB)
        ->join('user_unit', 'unit.ID_UNIT', '=', 'user_unit.ID_UNIT')
        ->where('user_unit.ID_USER', $this->auth->user()->ID_USER)
        ->first();*/
        $unit = Unit::where("TYPE_UNIT", 27)->where("SYMBOL", $monetaryUnit->MONEY_SYMB)->first();

        $result = array();

        if (!$unit) {
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
        $unit = Unit::where('TYPE_UNIT', $typeUnit)
        ->join('user_unit', 'Unit.ID_UNIT', '=', 'user_unit.ID_UNIT')
        ->where('user_unit.ID_USER', $this->auth->user()->ID_USER)
        ->first();
        $value = doubleval($sValue);
        $coeffA = $unit->COEFF_A;
        $coeffB = $unit->COEFF_B;
        if ($value != null) $value = ($value - $coeffB) * $coeffA;

        return round($value, 2);
    }

    public function mass($value, $options = null) 
    {
        $unit = Unit::where('TYPE_UNIT', $this->value->MASS)
        ->join('user_unit', 'Unit.ID_UNIT', '=', 'user_unit.ID_UNIT')
        ->where('user_unit.ID_USER', $this->auth->user()->ID_USER)
        ->first();
        return $this->convertCalculator($value, $unit->COEFF_A, $unit->COEFF_B, 3, $options);
    }

    public function massSave($value) 
    {
        $unit = Unit::where('TYPE_UNIT', $this->value->MASS)
        ->join('user_unit', 'Unit.ID_UNIT', '=', 'user_unit.ID_UNIT')
        ->where('user_unit.ID_USER', $this->auth->user()->ID_USER)
        ->first();
        return $this->convertUnitSave($value, $unit->COEFF_A, $unit->COEFF_B, 3);
    }

    public function controlTemperature($value, $options = null)
    {
        $unit = Unit::where('TYPE_UNIT', $this->value->TEMPERATURE)
        ->join('user_unit', 'Unit.ID_UNIT', '=', 'user_unit.ID_UNIT')
        ->where('user_unit.ID_USER', $this->auth->user()->ID_USER)
        ->first();
        return $this->convertCalculator($value, $unit->COEFF_A, $unit->COEFF_B, 0, $options);
    }

    public function prodTemperature($value, $options = null)
    {
        $unit = Unit::where('TYPE_UNIT', $this->value->TEMPERATURE)
        ->join('user_unit', 'Unit.ID_UNIT', '=', 'user_unit.ID_UNIT')
        ->where('user_unit.ID_USER', $this->auth->user()->ID_USER)
        ->first();
        return $this->convertCalculator($value, $unit->COEFF_A, $unit->COEFF_B, 1, $options);
    }

    public function time($value, $options = null)
    {
        $unit = Unit::where('TYPE_UNIT', $this->value->TIME)
        ->join('user_unit', 'Unit.ID_UNIT', '=', 'user_unit.ID_UNIT')
        ->where('user_unit.ID_USER', $this->auth->user()->ID_USER)
        ->first();
        return $this->convertCalculator($value, $unit->COEFF_A, $unit->COEFF_B, 1, $options);
    }

    public function enthalpy($value)
    {
        $unit = Unit::where('TYPE_UNIT', $this->value->ENTHALPY)
        ->join('user_unit', 'Unit.ID_UNIT', '=', 'user_unit.ID_UNIT')
        ->where('user_unit.ID_USER', $this->auth->user()->ID_USER)
        ->first();
        return $this->convertCalculator($value, $unit->COEFF_A, $unit->COEFF_B, 3);
    }

    public function productFlow($value, $options = null)
    {
        $unit = Unit::where('TYPE_UNIT', $this->value->PRODUCT_FLOW)
        ->join('user_unit', 'Unit.ID_UNIT', '=', 'user_unit.ID_UNIT')
        ->where('user_unit.ID_USER', $this->auth->user()->ID_USER)
        ->first();
        return $this->convertCalculator($value, $unit->COEFF_A, $unit->COEFF_B, 1, $options);
    }

    public function equipDimension($value, $options = null)
    {
        $unit = Unit::where('TYPE_UNIT', $this->value->EQUIP_DIMENSION)
        ->join('user_unit', 'Unit.ID_UNIT', '=', 'user_unit.ID_UNIT')
        ->where('user_unit.ID_USER', $this->auth->user()->ID_USER)
        ->first();
        return $this->convertCalculator($value, $unit->COEFF_A, $unit->COEFF_B, 2, $options);
    }

    public function convectionSpeed($value, $options = null)
    {
        $unit = Unit::where('TYPE_UNIT', $this->value->CONV_SPEED)
        ->join('user_unit', 'Unit.ID_UNIT', '=', 'user_unit.ID_UNIT')
        ->where('user_unit.ID_USER', $this->auth->user()->ID_USER)
        ->first();
        return $this->convertCalculator($value, $unit->COEFF_A, $unit->COEFF_B, 1, $options);
    }

    public function timeUnit($value)
    {
        $unit = Unit::where('TYPE_UNIT', $this->value->TIME)
        ->join('user_unit', 'Unit.ID_UNIT', '=', 'user_unit.ID_UNIT')
        ->where('user_unit.ID_USER', $this->auth->user()->ID_USER)
        ->first();
        return $this->convertCalculator($value, $unit->COEFF_A, $unit->COEFF_B, 1);
    }

    public function timePosition($value) 
    {
        $unit = Unit::where('TYPE_UNIT', $this->value->UNIT_TIME)
        ->join('user_unit', 'Unit.ID_UNIT', '=', 'user_unit.ID_UNIT')
        ->where('user_unit.ID_USER', $this->auth->user()->ID_USER)
        ->first();
        return $this->convertCalculator($value, $unit->COEFF_A, $unit->COEFF_B, 3);
    }

    public function temperature($value, $options = null)
    {
        $unit = Unit::where('TYPE_UNIT', $this->value->TEMPERATURE)
        ->join('user_unit', 'Unit.ID_UNIT', '=', 'user_unit.ID_UNIT')
        ->where('user_unit.ID_USER', $this->auth->user()->ID_USER)
        ->first();
        return $this->convertCalculator($value, $unit->COEFF_A, $unit->COEFF_B, 2, $options);
    }

    public function convectionCoeff($value, $options = null)
    {
        $unit = Unit::where('TYPE_UNIT', $this->value->CONV_COEFF)
        ->join('user_unit', 'Unit.ID_UNIT', '=', 'user_unit.ID_UNIT')
        ->where('user_unit.ID_USER', $this->auth->user()->ID_USER)
        ->first();
        return $this->convertCalculator($value, $unit->COEFF_A, $unit->COEFF_B, 2, $options);
    }

    public function carpetWidthSVG($value, $options = null)
    {
        $unit = Unit::where('TYPE_UNIT', W_CARPET_SHELVES)
        ->join('user_unit', 'Unit.ID_UNIT', '=', 'user_unit.ID_UNIT')
        ->where('user_unit.ID_USER', $this->auth->user()->ID_USER)
        ->first();
        return $this->convertCalculator($value, $unit->COEFF_A, $unit->COEFF_B, 4, $options);
    }

    public function shelvesWidthSVG($value, $options = null)
    {
        $unit = Unit::where('TYPE_UNIT', W_CARPET_SHELVES)
        ->join('user_unit', 'Unit.ID_UNIT', '=', 'user_unit.ID_UNIT')
        ->where('user_unit.ID_USER', $this->auth->user()->ID_USER)
        ->first();
        return $this->convertCalculator($value, $unit->COEFF_A, $unit->COEFF_B, 4, $options);
    }

    public function meshesUnit($value) 
    {
        $unit = Unit::where('TYPE_UNIT', $this->value->MESH_CUT)
        ->join('user_unit', 'Unit.ID_UNIT', '=', 'user_unit.ID_UNIT')
        ->where('user_unit.ID_USER', $this->auth->user()->ID_USER)
        ->first();
        return $this->convertCalculator($value, $unit->COEFF_A, $unit->COEFF_B);
    }

    public function meshesUnitSave($value)
    {
        $unit = Unit::where('TYPE_UNIT', $this->value->MESH_CUT)
        ->join('user_unit', 'Unit.ID_UNIT', '=', 'user_unit.ID_UNIT')
        ->where('user_unit.ID_USER', $this->auth->user()->ID_USER)
        ->first();
        return $this->convertUnitSave($value, $unit->COEFF_A, $unit->COEFF_B);
    }

    public function prodchartDimension($value) 
    {
        $unit = Unit::where('TYPE_UNIT', $this->value->PRODCHART_DIMENSION)
        ->join('user_unit', 'Unit.ID_UNIT', '=', 'user_unit.ID_UNIT')
        ->where('user_unit.ID_USER', $this->auth->user()->ID_USER)
        ->first();
        return $this->convertCalculator($value, $unit->COEFF_A, $unit->COEFF_B);
    }

    public function lineDimension($value, $options = null)
    {
        $unit = Unit::where('TYPE_UNIT', $this->value->LINE)
        ->join('user_unit', 'Unit.ID_UNIT', '=', 'user_unit.ID_UNIT')
        ->where('user_unit.ID_USER', $this->auth->user()->ID_USER)
        ->first();
        return $this->convertCalculator($value, $unit->COEFF_A, $unit->COEFF_B, 3, $options);
    }

    public function lineDimensionSave($value)
    {
        $unit = Unit::where('TYPE_UNIT', $this->value->LINE)
        ->join('user_unit', 'Unit.ID_UNIT', '=', 'user_unit.ID_UNIT')
        ->where('user_unit.ID_USER', $this->auth->user()->ID_USER)
        ->first();
        return $this->convertUnitSave($value, $unit->COEFF_A, $unit->COEFF_B);
    }

    public function materialRise($value)
    {
        $unit = Unit::where('TYPE_UNIT', $this->value->MATERIAL_RISE)
        ->join('user_unit', 'Unit.ID_UNIT', '=', 'user_unit.ID_UNIT')
        ->where('user_unit.ID_USER', $this->auth->user()->ID_USER)
        ->first();
        return $this->convertCalculator($value, $unit->COEFF_A, $unit->COEFF_B);
    }

    public function materialRiseSave($value)
    {
        $unit = Unit::where('TYPE_UNIT', $this->value->MATERIAL_RISE)
        ->join('user_unit', 'Unit.ID_UNIT', '=', 'user_unit.ID_UNIT')
        ->where('user_unit.ID_USER', $this->auth->user()->ID_USER)
        ->first();
        return $this->convertUnitSave($value, $unit->COEFF_A, $unit->COEFF_B);
    }

    public function exhaustTemperature($value, $options = null)
    {
        $unit = Unit::where('TYPE_UNIT', $this->value->TEMPERATURE)
        ->join('user_unit', 'Unit.ID_UNIT', '=', 'user_unit.ID_UNIT')
        ->where('user_unit.ID_USER', $this->auth->user()->ID_USER)
        ->first();
        return $this->convertCalculator($value, $unit->COEFF_A, $unit->COEFF_B, 0, $options);
    }

    public function packingThickness($value, $options = null)
    {
        $unit = Unit::where('TYPE_UNIT', $this->value->THICKNESS_PACKING)
        ->join('user_unit', 'Unit.ID_UNIT', '=', 'user_unit.ID_UNIT')
        ->where('user_unit.ID_USER', $this->auth->user()->ID_USER)
        ->first();
        return $this->convertCalculator($value, $unit->COEFF_A, $unit->COEFF_B, 2, $options);
    }

    public function pressure($value)
    {
        $unit = Unit::where('TYPE_UNIT', $this->value->PRESSURE)
        ->join('user_unit', 'Unit.ID_UNIT', '=', 'user_unit.ID_UNIT')
        ->where('user_unit.ID_USER', $this->auth->user()->ID_USER)
        ->first();
        return $this->convertCalculator($value, $unit->COEFF_A, $unit->COEFF_B);
    }
    public function pressureSave($value) 
    {
        $unit = Unit::where('TYPE_UNIT', $this->value->PRESSURE)
        ->join('user_unit', 'Unit.ID_UNIT', '=', 'user_unit.ID_UNIT')
        ->where('user_unit.ID_USER', $this->auth->user()->ID_USER)
        ->first();
        return $this->convertUnitSave($value, $unit->COEFF_A, $unit->COEFF_B);
    }

    public function none($value, $options = null)
    {
        $uNone = $this->uNone();
        return $this->convertCalculator($value, $uNone["coeffA"], $uNone["coeffB"], 2, $options);
    }

    public function toc($value, $options = null) 
    {
        $uPercent = $this->uPercent();
        return $this->convertCalculator($value, $uPercent["coeffA"], $uPercent["coeffB"], 1, $options);
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

    public function cryogenPrice($value, $energy, $options = null)
    {
        $snrjUnitLabel = '';
        switch ($energy) {
            case 2:
                $snrjUnitLabel = CONSUMPTION_UNIT_LN2;
                break;

            case 3:
                $snrjUnitLabel = CONSUMPTION_UNIT_CO2;
                break;
            
            default:
                $snrjUnitLabel = CONSUMPTION_UNIT;
                break;
        }

        $lfCoef = $this->unitConvert($snrjUnitLabel, 1);
        $lfValue = $value;
        if ($lfCoef != 0) {
            $lfValue /= $lfCoef;
        }
        $uMoney = $this->uMoney();
        $sValue = $this->convertCalculator($lfValue, $uMoney['coeffA'], $uMoney['coeffB'], 3, $options);
        return $sValue;
    }


    // convert unit for user
    public function temperatureSymbolUser() 
    {
        $user = $this->auth->user();
        $userUnit = UserUnit::join('unit', 'user_unit.ID_UNIT', '=', 'unit.ID_UNIT')->where('ID_USER', $user->ID_USER)
        ->where("unit.TYPE_UNIT", $this->value->TEMPERATURE)->get();

        return $userUnit[0]->SYMBOL;
    }

    public function shelvesWidthUser($value)
    {
        $user = $this->auth->user();
        $unit = UserUnit::join('unit', 'user_unit.ID_UNIT', '=', 'unit.ID_UNIT')->where('ID_USER', $user->ID_USER)
        ->where("unit.TYPE_UNIT", $this->value->W_CARPET_SHELVES)->first();

        return $this->convertCalculator($value, $unit->COEFF_A, $unit->COEFF_B);
    }

    public function rampsPositionSymbol()
    {
        $user = $this->auth->user();
        $unit = UserUnit::join('unit', 'user_unit.ID_UNIT', '=', 'unit.ID_UNIT')->where('ID_USER', $user->ID_USER)
        ->where("unit.TYPE_UNIT", $this->value->SLOPES_POSITION)->first();

        return $unit->SYMBOL;
    }

    public function rampsPositionUser($value)
    {
        $user = $this->auth->user();
        $unit = UserUnit::join('unit', 'user_unit.ID_UNIT', '=', 'unit.ID_UNIT')->where('ID_USER', $user->ID_USER)
        ->where("unit.TYPE_UNIT", $this->value->SLOPES_POSITION)->first();

        return $this->convertCalculator($value, $unit->COEFF_A, $unit->COEFF_B);
    }

    public function timeSymbolUser() 
    {
        $user = $this->auth->user();
        $userUnit = UserUnit::join('unit', 'user_unit.ID_UNIT', '=', 'unit.ID_UNIT')->where('ID_USER', $user->ID_USER)
        ->where("unit.TYPE_UNIT", $this->value->TIME)->get();

        return $userUnit[0]->SYMBOL;
    }

    public function equipDimensionUser($value)
    {
        $user = $this->auth->user();
        $unit = UserUnit::join('unit', 'user_unit.ID_UNIT', '=', 'unit.ID_UNIT')->where('ID_USER', $user->ID_USER)
        ->where("unit.TYPE_UNIT", $this->value->EQUIP_DIMENSION)->first();

        return $this->convertCalculator($value, $unit->COEFF_A, $unit->COEFF_B);
    }

    public function controlTemperatureUser($value)
    {
        $user = $this->auth->user();

        $unit = UserUnit::join('unit', 'user_unit.ID_UNIT', '=', 'unit.ID_UNIT')->where('ID_USER', $user->ID_USER)
        ->where("unit.TYPE_UNIT", $this->value->TEMPERATURE)->first();

        return $this->convertCalculator($value, $unit->COEFF_A, $unit->COEFF_B, 0);
    }

    public function equipDimensionSymbolUser() 
    {
        $user = $this->auth->user();
        $unit = UserUnit::join('unit', 'user_unit.ID_UNIT', '=', 'unit.ID_UNIT')->where('ID_USER', $user->ID_USER)
        ->where("unit.TYPE_UNIT", $this->value->EQUIP_DIMENSION)->first();

        return $unit->SYMBOL;
    }

    public function prodDimension($value, $options = null) 
    {
        $unit = Unit::where('TYPE_UNIT', $this->value->PROD_DIMENSION)
        ->join('user_unit', 'Unit.ID_UNIT', '=', 'user_unit.ID_UNIT')
        ->where('user_unit.ID_USER', $this->auth->user()->ID_USER)
        ->first();
        return $this->convertCalculator($value, $unit->COEFF_A, $unit->COEFF_B, 2, $options);
    }

    public function prodDimensionSave($value)
    {
        $unit = Unit::where('TYPE_UNIT', $this->value->PROD_DIMENSION)
        ->join('user_unit', 'Unit.ID_UNIT', '=', 'user_unit.ID_UNIT')
        ->where('user_unit.ID_USER', $this->auth->user()->ID_USER)
        ->first();
        return $this->convertUnitSave($value, $unit->COEFF_A, $unit->COEFF_B);

    }

    public function prodDimensionSymbolUser()
    {
        $user = $this->auth->user();
        $unit = UserUnit::join('unit', 'user_unit.ID_UNIT', '=', 'unit.ID_UNIT')->where('ID_USER', $user->ID_USER)
        ->where("unit.TYPE_UNIT", $this->value->PROD_DIMENSION)->first();

        return $unit->SYMBOL;
    }

    public function consumptionUser($value, $energy, $type, $decimal = 2)
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

        return $this->unitConvertUser($sUnitLabel, $value, $decimal);
    }

    public function unitConvertUser($unitType, $value, $decimal = 2)
    {
        $user = $this->auth->user();
        $unit = UserUnit::join('unit', 'user_unit.ID_UNIT', '=', 'unit.ID_UNIT')->where('ID_USER', $user->ID_USER)
        ->where("unit.TYPE_UNIT", $unitType)->first();

        if (!empty($unit)) 
            return $this->convertCalculator($value, $unit->COEFF_A, $unit->COEFF_B,  $decimal);
        else 
            return $value;
    }

    public function unitConvertUserSave($unitType, $value)
    {
        $user = $this->auth->user();
        $unit = UserUnit::join('unit', 'user_unit.ID_UNIT', '=', 'unit.ID_UNIT')->where('ID_USER', $user->ID_USER)
        ->where("unit.TYPE_UNIT", $unitType)->first();

        if (!empty($unit)) 
            return $this->convertUnitSave($value, $unit->COEFF_A, $unit->COEFF_B);
        else 
            return $value;
    }

    public function consumptionSymbolUser($energy, $type) 
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
        $user = $this->auth->user();
        $unit = UserUnit::join('unit', 'user_unit.ID_UNIT', '=', 'unit.ID_UNIT')->where('ID_USER', $user->ID_USER)
        ->where("unit.TYPE_UNIT", $sUnitLabel)->first();

        return $unit->SYMBOL;
    }

    public function timeUser($value) 
    {
        $user = $this->auth->user();
        $unit = UserUnit::join('unit', 'user_unit.ID_UNIT', '=', 'unit.ID_UNIT')->where('ID_USER', $user->ID_USER)
        ->where("unit.TYPE_UNIT", $this->value->TIME)->first();

        return $this->convertCalculator($value, $unit->COEFF_A, $unit->COEFF_B, 1);
    }

    public function convertIdent($value, $ident)
    {
        $sValue = null;
        switch ($ident) {
            case 1:
                $sValue = $this->controlTemperature($value);
                break;
            case 2:
                $sValue = $this->time($value);
                break;
            case 3:
                $sValue = $this->convectionSpeed($value);
                break;
            case 5:
                $sValue = $this->convectionCoeff($value);
                break;
            case 10:
                $sValue = intval($value);
                break;
            
            default:
                $sValue = $this->none($value);
                break;
        }

        return $sValue;
    }

    public function convertToDouble($value)
    {
        return floatval($value);
    }

    public function carpetWidth ($value) {
        $unit = Unit::where('TYPE_UNIT', W_CARPET_SHELVES)
        ->join('user_unit', 'Unit.ID_UNIT', '=', 'user_unit.ID_UNIT')
        ->where('user_unit.ID_USER', $this->auth->user()->ID_USER)
        ->first();
        return $this->convertCalculator($value, $unit->COEFF_A, $unit->COEFF_B);
    }
}