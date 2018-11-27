<?php
/****************************************************************************
 **
 ** Copyright (C) 2017 Oriental Tran.
 ** Contact: dongtp@dfm-engineering.com
 ** Company: DFM-Engineering Vietnam
 **
 ** This file is part of the cryosoft project.
 **
 **All rights reserved.
 ****************************************************************************/
namespace App\Cryosoft;

use Illuminate\Contracts\Auth\Factory as Auth;
use App\Models\Unit;
use App\Models\UserUnit;
use App\Models\MonetaryCurrency;

class UnitsService
{
    /**
    * @var Illuminate\Contracts\Auth\Factory
    */
    protected $auth;

    protected $app;

    protected $value;
    
    public function __construct(\Laravel\Lumen\Application $app)
    {
        $this->app = $app;
        $this->auth = $app['Illuminate\\Contracts\\Auth\\Factory'];
        $this->value = $app['App\\Cryosoft\\ValueListService'];
    }

    public function meshes($value, $decimal, $status) 
    {
        $unit = Unit::where('TYPE_UNIT', $this->value->MESH_CUT)
        ->join('USER_UNIT', 'UNIT.ID_UNIT', '=', 'USER_UNIT.ID_UNIT')
        ->where('USER_UNIT.ID_USER', $this->auth->user()->ID_USER)
        ->first();

        $value = doubleval($value);
        $coeffA = $unit->COEFF_A;
        $coeffB = $unit->COEFF_B;
        if (intval($status) == 1) {
            if ($value != null) $value = ($value - $coeffB) * $coeffA;

            return round($value, $decimal);
        } else {
            if ($value != null) $value = ($value + $coeffB) / $coeffA;

            return round($value, $decimal);
        }
    }

    public function prodTemperature($value, $decimal = 2, $status = 1)
    {
        $unit = Unit::where('TYPE_UNIT', $this->value->TEMPERATURE)
        ->join('USER_UNIT', 'UNIT.ID_UNIT', '=', 'USER_UNIT.ID_UNIT')
        ->where('USER_UNIT.ID_USER', $this->auth->user()->ID_USER)
        ->first();
        
        if ($status == 1) {
            return $this->convertCalculator($value, $unit->COEFF_A, $unit->COEFF_B, $decimal, 1);
        } else {
            return $this->convertCalculator($value, $unit->COEFF_A, $unit->COEFF_B, $decimal, 0);
        }
    }

    public function time($value, $decimal, $status) 
    {
        $unit = Unit::where('TYPE_UNIT', $this->value->TIME)
        ->join('USER_UNIT', 'UNIT.ID_UNIT', '=', 'USER_UNIT.ID_UNIT')
        ->where('USER_UNIT.ID_USER', $this->auth->user()->ID_USER)
        ->first();

        if ($status == 1) {
            return $this->convertCalculator($value, $unit->COEFF_A, $unit->COEFF_B, $decimal, 1);
        } else {
            return $this->convertCalculator($value, $unit->COEFF_A, $unit->COEFF_B, $decimal, 0);
        }
    }

    public function convectionCoeff($value, $decimal, $status) 
    {
        $unit = Unit::where('TYPE_UNIT', $this->value->CONV_COEFF)
        ->join('USER_UNIT', 'UNIT.ID_UNIT', '=', 'USER_UNIT.ID_UNIT')
        ->where('USER_UNIT.ID_USER', $this->auth->user()->ID_USER)
        ->first();

        if ($status == 1) {
            return $this->convertCalculator($value, $unit->COEFF_A, $unit->COEFF_B, $decimal, 1);
        } else {
            return $this->convertCalculator($value, $unit->COEFF_A, $unit->COEFF_B, $decimal, 0);
        }
    }

    public function convertCalculator($value, $coeffA, $coeffB, $decimal, $status = 1)
    {
        // convert or unconvert (convert: status == 1)
        if ($status == 1) {
            $number = $value * $coeffA + $coeffB;
        } else {
            $number = ($value - $coeffB) / $coeffA ;
        }
        
        if (floor( $value ) != $value) {
            $number = round(($number), $decimal, PHP_ROUND_HALF_UP);
            $number = floor($number * pow(10, $decimal)) / pow(10, $decimal);
        } else {
            $number = round($number, $decimal, PHP_ROUND_HALF_UP);
            $number = floor($number * pow(10, $decimal)) / pow(10, $decimal);
        }
        
        return number_format((float)$number, $decimal, '.', '');
    }

    public function deltaTemperature ($value, $decimal, $status) 
    {
        $unit = Unit::where('TYPE_UNIT', $this->value->TEMPERATURE)
        ->join('USER_UNIT', 'UNIT.ID_UNIT', '=', 'USER_UNIT.ID_UNIT')
        ->where('USER_UNIT.ID_USER', $this->auth->user()->ID_USER)
        ->first();

        if ($status == 1) {
            return $this->convertDelta($value, $unit->COEFF_A, $unit->COEFF_B, $decimal, 1);
        } else {
            return $this->convertDelta($value, $unit->COEFF_A, $unit->COEFF_B, $decimal, 0);
        }
    }

    public function convertDelta($value, $coeffA, $coeffB, $decimal, $status)
    {
        if ($status == 1) {
            $number = $value * $coeffA;
        } else {
            $number = $value / $coeffA ;
        }
        
        if (floor( $value ) != $value) {
            $number = round(($number), $decimal, PHP_ROUND_HALF_UP);
            $number = floor($number * pow(10, $decimal)) / pow(10, $decimal);
        } else {
            $number = round(( $value * $coeffA), $decimal);
        }
        
        return number_format((float)$number, $decimal, '.', '');
    }

    public function timeStep($value, $decimal, $status)
    {
        $unit = Unit::where('TYPE_UNIT', $this->value->TIME)
        ->join('USER_UNIT', 'UNIT.ID_UNIT', '=', 'USER_UNIT.ID_UNIT')
        ->where('USER_UNIT.ID_USER', $this->auth->user()->ID_USER)
        ->first();

        if ($status == 1) {
            return $this->convertCalculator($value, $unit->COEFF_A, $unit->COEFF_B, $decimal, 1);
        } else {
            return $this->convertCalculator($value, $unit->COEFF_A, $unit->COEFF_B, $decimal, 0);
        }
    }

    public function convert($value, $decimal) 
    {
        $value = round(($value), $decimal, PHP_ROUND_HALF_UP);
        $value = floor($value * pow(10, $decimal)) / pow(10, $decimal);
        
        return number_format((float)$value, $decimal, '.', '');
    }

    public function temperature($value, $decimal, $status)
    {
        $unit = Unit::where('TYPE_UNIT', $this->value->TEMPERATURE)
        ->join('USER_UNIT', 'UNIT.ID_UNIT', '=', 'USER_UNIT.ID_UNIT')
        ->where('USER_UNIT.ID_USER', $this->auth->user()->ID_USER)
        ->first();

        if ($status == 1) {
            return $this->convertCalculator($value, $unit->COEFF_A, $unit->COEFF_B, $decimal, 1);
        } else {
            return $this->convertCalculator($value, $unit->COEFF_A, $unit->COEFF_B, $decimal, 0);
        }
    }

    public function uPercent()
    {
        return array(
            "coeffA" => "100.0",
            "coeffB" => "0.0",
            "symbol" => "%"
        );
    }

    public function enthalpy($value, $decimal, $status) 
    {
        $unit = Unit::where('TYPE_UNIT', $this->value->ENTHALPY)
        ->join('USER_UNIT', 'UNIT.ID_UNIT', '=', 'USER_UNIT.ID_UNIT')
        ->where('USER_UNIT.ID_USER', $this->auth->user()->ID_USER)
        ->first();

        if ($status == 1) {
            return $this->convertCalculator($value, $unit->COEFF_A, $unit->COEFF_B, $decimal, 1);
        } else {
            return $this->convertCalculator($value, $unit->COEFF_A, $unit->COEFF_B, $decimal, 0);
        }
    }

    public function conductivity($value, $decimal, $status)
    {
        $unit = Unit::where('TYPE_UNIT', $this->value->CONDUCTIVITY)
        ->join('USER_UNIT', 'UNIT.ID_UNIT', '=', 'USER_UNIT.ID_UNIT')
        ->where('USER_UNIT.ID_USER', $this->auth->user()->ID_USER)
        ->first();

        if ($status == 1) {
            return $this->convertCalculator($value, $unit->COEFF_A, $unit->COEFF_B, $decimal, 1);
        } else {
            return $this->convertCalculator($value, $unit->COEFF_A, $unit->COEFF_B, $decimal, 0);
        }
    }

    public function packingThickness($value, $decimal, $status) 
    {
        $unit = Unit::where('TYPE_UNIT', $this->value->THICKNESS_PACKING)
        ->join('USER_UNIT', 'UNIT.ID_UNIT', '=', 'USER_UNIT.ID_UNIT')
        ->where('USER_UNIT.ID_USER', $this->auth->user()->ID_USER)
        ->first();

        if ($status == 1) {
            return $this->convertCalculator($value, $unit->COEFF_A, $unit->COEFF_B, $decimal, 1);
        } else {
            return $this->convertCalculator($value, $unit->COEFF_A, $unit->COEFF_B, $decimal, 0);
        }
    }

    public function density($value, $decimal, $status)
    {
        $unit = Unit::where('TYPE_UNIT', $this->value->DENSITY)
        ->join('USER_UNIT', 'UNIT.ID_UNIT', '=', 'USER_UNIT.ID_UNIT')
        ->where('USER_UNIT.ID_USER', $this->auth->user()->ID_USER)
        ->first();

        if ($status == 1) {
            return $this->convertCalculator($value, $unit->COEFF_A, $unit->COEFF_B, $decimal, 1);
        } else {
            return $this->convertCalculator($value, $unit->COEFF_A, $unit->COEFF_B, $decimal, 0);
        }
    }

    public function uMoney()
    {
        $user = $this->auth->user();
        $monetaryUnit = MonetaryCurrency::where("ID_MONETARY_CURRENCY", $user->ID_MONETARY_CURRENCY)->first();
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

    public function uNone()
    {
        return array(
            "coeffA" => "1.0",
            "coeffB" => "0.0",
            "symbol" => ""
        );
    }

    public function monetary($value, $decimal, $status) 
    {
        $uMoney = $this->uMoney();

        if ($status == 1) {
            return $this->convertCalculator($value, $uMoney["coeffA"], $uMoney["coeffB"],  $decimal, 1);
        } else {
            return $this->convertCalculator($value, $uMoney["coeffA"], $uMoney["coeffB"],  $decimal, 0);

        }
    }

    public function tankCapacity($value, $typeunit, $decimal, $status) 
    {
        $unit = Unit::where('TYPE_UNIT', $typeunit)
        ->join('USER_UNIT', 'UNIT.ID_UNIT', '=', 'USER_UNIT.ID_UNIT')
        ->where('USER_UNIT.ID_USER', $this->auth->user()->ID_USER)
        ->first();

        if ($status == 1) {
            return $this->convertCalculator($value, $unit->COEFF_A, $unit->COEFF_B, $decimal, 1);
        } else {
            return $this->convertCalculator($value, $unit->COEFF_A, $unit->COEFF_B, $decimal, 0);
        }
    }

    public function lineDimension($value, $decimal, $status) 
    {
        $unit = Unit::where('TYPE_UNIT', $this->value->LINE)
        ->join('USER_UNIT', 'UNIT.ID_UNIT', '=', 'USER_UNIT.ID_UNIT')
        ->where('USER_UNIT.ID_USER', $this->auth->user()->ID_USER)
        ->first();

        if ($status == 1) {
            return $this->convertCalculator($value, $unit->COEFF_A, $unit->COEFF_B, $decimal, 1);
        } else {
            return $this->convertCalculator($value, $unit->COEFF_A, $unit->COEFF_B, $decimal, 0);
        }
    }

    public function equipDimension($value, $decimal, $status) 
    {
        $unit = Unit::where('TYPE_UNIT', $this->value->EQUIP_DIMENSION)
        ->join('USER_UNIT', 'UNIT.ID_UNIT', '=', 'USER_UNIT.ID_UNIT')
        ->where('USER_UNIT.ID_USER', $this->auth->user()->ID_USER)
        ->first();

        if ($status == 1) {
            return $this->convertCalculator($value, $unit->COEFF_A, $unit->COEFF_B, $decimal, 1);
        } else {
            return $this->convertCalculator($value, $unit->COEFF_A, $unit->COEFF_B, $decimal, 0);
        }
    }

    public function consumption($value, $energy, $type, $decimal, $status)
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

        return $this->unitConvert($sUnitLabel, $value, $decimal, $status);
    }

    public function unitConvert($unitType, $value, $decimal, $status)
    {
        $unit = Unit::where('TYPE_UNIT', $unitType)
        ->join('USER_UNIT', 'UNIT.ID_UNIT', '=', 'USER_UNIT.ID_UNIT')
        ->where('USER_UNIT.ID_USER', $this->auth->user()->ID_USER)
        ->first();

        if ($status == 1) {
            return $this->convertCalculator($value, $unit->COEFF_A, $unit->COEFF_B, $decimal, 1);
        } else {
            return $this->convertCalculator($value, $unit->COEFF_A, $unit->COEFF_B, $decimal, 0);
        }
    }

    public function controlTemperature($value, $decimal, $status) 
    {
        $unit = Unit::where('TYPE_UNIT', $this->value->TEMPERATURE)
        ->join('USER_UNIT', 'UNIT.ID_UNIT', '=', 'USER_UNIT.ID_UNIT')
        ->where('USER_UNIT.ID_USER', $this->auth->user()->ID_USER)
        ->first();

        if ($status == 1) {
            return $this->convertCalculator($value, $unit->COEFF_A, $unit->COEFF_B, $decimal, 1);
        } else {
            return $this->convertCalculator($value, $unit->COEFF_A, $unit->COEFF_B, $decimal, 0);
        }
    }

    public function rampsPosition($value, $decimal, $status) 
    {
        $unit = Unit::where('TYPE_UNIT', $this->value->SLOPES_POSITION)
        ->join('USER_UNIT', 'UNIT.ID_UNIT', '=', 'USER_UNIT.ID_UNIT')
        ->where('USER_UNIT.ID_USER', $this->auth->user()->ID_USER)
        ->first();

        if ($status == 1) {
            return $this->convertCalculator($value, $unit->COEFF_A, $unit->COEFF_B, $decimal, 1);
        } else {
            return $this->convertCalculator($value, $unit->COEFF_A, $unit->COEFF_B, $decimal, 0);
        }
    }

    public function shelvesWidthUser($value) 
    {
        $unit = Unit::where('TYPE_UNIT', $this->value->W_CARPET_SHELVES)
        ->join('USER_UNIT', 'UNIT.ID_UNIT', '=', 'USER_UNIT.ID_UNIT')
        ->where('USER_UNIT.ID_USER', $this->auth->user()->ID_USER)
        ->first();

        if ($status == 1) {
            return $this->convertCalculator($value, $unit->COEFF_A, $unit->COEFF_B, $decimal, 1);
        } else {
            return $this->convertCalculator($value, $unit->COEFF_A, $unit->COEFF_B, $decimal, 0);
        }
    }

    public function length($value, $decimal = 2, $status = 1)
    {
        $unit = Unit::where('TYPE_UNIT', $this->value->LENGTH)
        ->join('USER_UNIT', 'UNIT.ID_UNIT', '=', 'USER_UNIT.ID_UNIT')
        ->where('USER_UNIT.ID_USER', $this->auth->user()->ID_USER)
        ->first();
        
        if ($status == 1) {
            return $this->convertCalculator($value, $unit->COEFF_A, $unit->COEFF_B, $decimal, 1);
        } else {
            return $this->convertCalculator($value, $unit->COEFF_A, $unit->COEFF_B, $decimal, 0);
        }
    }

    public function toc($value, $decimal = 2, $status = 1) 
    {
        $uPercent = $this->uPercent();
        return $this->convertCalculator($value, $uPercent["coeffA"], $uPercent["coeffB"], $decimal, $status);
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
        $user = $this->auth->user();
        $unit = UserUnit::join('UNIT', 'USER_UNIT.ID_UNIT', '=', 'UNIT.ID_UNIT')->where('ID_USER', $user->ID_USER)
        ->where("UNIT.TYPE_UNIT", $sUnitLabel)->first();

        return $unit->SYMBOL;
    }
}