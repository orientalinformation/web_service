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
    // HAIDT
    public function meshes($value, $decimal, $status) 
    {
        $unit = Unit::where('TYPE_UNIT', $this->value->MESH_CUT)
        ->join('user_unit', 'Unit.ID_UNIT', '=', 'user_unit.ID_UNIT')
        ->where('user_unit.ID_USER', $this->auth->user()->ID_USER)
        ->first();
        $value = doubleval($value);
        $coeffA = $unit->COEFF_A;
        $coeffB = $unit->COEFF_B;
        if (intval($status) == 1) {
            if ($value != null) $value = ($value - $coeffB) * $coeffA;

            return round($value, $decimal);
        } else {
            if ($value != null) $value = ($value + $coeffB) / $coeffA;

            return $value;
        }
    }

    public function prodTemperature($value, $decimal, $status)
    {
        $unit = Unit::where('TYPE_UNIT', $this->value->TEMPERATURE)
        ->join('user_unit', 'Unit.ID_UNIT', '=', 'user_unit.ID_UNIT')
        ->where('user_unit.ID_USER', $this->auth->user()->ID_USER)
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
        ->join('user_unit', 'Unit.ID_UNIT', '=', 'user_unit.ID_UNIT')
        ->where('user_unit.ID_USER', $this->auth->user()->ID_USER)
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
        ->join('user_unit', 'Unit.ID_UNIT', '=', 'user_unit.ID_UNIT')
        ->where('user_unit.ID_USER', $this->auth->user()->ID_USER)
        ->first();

        if ($status == 1) {
            return $this->convertCalculator($value, $unit->COEFF_A, $unit->COEFF_B, $decimal, 1);
        } else {
            return $this->convertCalculator($value, $unit->COEFF_A, $unit->COEFF_B, $decimal, 0);
        }
    }

    public function convertCalculator($value, $coeffA, $coeffB, $decimal, $status)
    {
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
        }
        
        return number_format((float)$number, $decimal, '.', '');
    }

    public function deltaTemperature ($value, $decimal, $status) 
    {
        $unit = Unit::where('TYPE_UNIT', $this->value->TEMPERATURE)
        ->join('user_unit', 'Unit.ID_UNIT', '=', 'user_unit.ID_UNIT')
        ->where('user_unit.ID_USER', $this->auth->user()->ID_USER)
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
        ->join('user_unit', 'Unit.ID_UNIT', '=', 'user_unit.ID_UNIT')
        ->where('user_unit.ID_USER', $this->auth->user()->ID_USER)
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
        ->join('user_unit', 'Unit.ID_UNIT', '=', 'user_unit.ID_UNIT')
        ->where('user_unit.ID_USER', $this->auth->user()->ID_USER)
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

    public function enthalpy($value, $decimal, $status) {
        $unit = Unit::where('TYPE_UNIT', $this->value->ENTHALPY)
        ->join('user_unit', 'Unit.ID_UNIT', '=', 'user_unit.ID_UNIT')
        ->where('user_unit.ID_USER', $this->auth->user()->ID_USER)
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
        ->join('user_unit', 'Unit.ID_UNIT', '=', 'user_unit.ID_UNIT')
        ->where('user_unit.ID_USER', $this->auth->user()->ID_USER)
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
        ->join('user_unit', 'Unit.ID_UNIT', '=', 'user_unit.ID_UNIT')
        ->where('user_unit.ID_USER', $this->auth->user()->ID_USER)
        ->first();

        if ($status == 1) {
            return $this->convertCalculator($value, $unit->COEFF_A, $unit->COEFF_B, $decimal, 1);
        } else {
            return $this->convertCalculator($value, $unit->COEFF_A, $unit->COEFF_B, $decimal, 0);
        }
    }
    // HAIDT
}