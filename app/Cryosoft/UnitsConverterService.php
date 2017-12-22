<?php

namespace App\Cryosoft;

use Illuminate\Contracts\Auth\Factory as Auth;
use App\Cryosoft\ValueListService;
use App\Models\Unit;
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

        $unit = Unit::where("TYPE_UNIT", 27)->where("SYMBOL", "like", "%" . $monetaryUnit->MONEY_TEXT . "%")->first();

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
