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
use App\Models\MinMax;

class MinMaxService
{
    /**
    * @var Illuminate\Contracts\Auth\Factory
    */
    protected $auth;

    protected $app;

    protected $value;

    protected $units;
    
    protected $convert;
    
    public function __construct(\Laravel\Lumen\Application $app)
    {
        $this->app = $app;
        $this->auth = $app['Illuminate\\Contracts\\Auth\\Factory'];
        $this->value = $app['App\\Cryosoft\\ValueListService'];
        $this->units = $app['App\\Cryosoft\\UnitsService'];
        $this->convert = $app['App\\Cryosoft\\UnitsConverterService'];
    }

    public function checkMinMaxValue($value, $limitItem)
    {
        $minMax = MinMax::where('LIMIT_ITEM', intval($limitItem))->first();
        if (doubleval($value) < round($minMax->LIMIT_MIN, 4) || doubleval($value) > round($minMax->LIMIT_MAX, 4)) {
            return false;
        } else {
            return true;
        }
    }

    public function getMinMaxMesh($limitItem)
    {  
        $minMax = MinMax::where('LIMIT_ITEM', intval($limitItem))->first();
        $minMax->LIMIT_MAX = $this->units->meshes($minMax->LIMIT_MAX, 2, 1);
        $minMax->LIMIT_MIN = $this->units->meshes($minMax->LIMIT_MIN, 2, 1);
        $minMax->DEFAULT_VALUE = $this->units->meshes($minMax->DEFAULT_VALUE, 2, 1);

        return $minMax; 
    }
    
    public function getMinMaxPressure($limitItem)
    {  
        $minMax = MinMax::where('LIMIT_ITEM', intval($limitItem))->first();
        $minMax->LIMIT_MAX = $this->convert->pressure($minMax->LIMIT_MAX);
        $minMax->LIMIT_MIN = $this->convert->pressure($minMax->LIMIT_MIN);
        $minMax->DEFAULT_VALUE = $this->convert->pressure($minMax->DEFAULT_VALUE);

        return $minMax; 
    }
    
    public function getMinMaxHeight($limitItem)
    {  
        $minMax = MinMax::where('LIMIT_ITEM', intval($limitItem))->first();
        $minMax->LIMIT_MAX = $this->convert->materialRise($minMax->LIMIT_MAX);
        $minMax->LIMIT_MIN = $this->convert->materialRise($minMax->LIMIT_MIN);
        $minMax->DEFAULT_VALUE = $this->convert->materialRise($minMax->DEFAULT_VALUE);

        return $minMax; 
    }

    public function getMinMaxLineDimention($limitItem)
    {  
        $minMax = MinMax::where('LIMIT_ITEM', intval($limitItem))->first();
        $minMax->LIMIT_MAX = $this->convert->lineDimension($minMax->LIMIT_MAX, ['format' => false]);
        $minMax->LIMIT_MIN = $this->convert->lineDimension($minMax->LIMIT_MIN, ['format' => false]);
        $minMax->DEFAULT_VALUE = $this->convert->lineDimension($minMax->DEFAULT_VALUE);

        return $minMax; 
    }

    public function getMinMaxNoneLine($limitItem)
    {  
        $minMax = MinMax::where('LIMIT_ITEM', intval($limitItem))->first();
        $minMax->LIMIT_MAX = $this->convert->none($minMax->LIMIT_MAX);
        $minMax->LIMIT_MIN = $this->convert->none($minMax->LIMIT_MIN);
        $minMax->DEFAULT_VALUE = $this->convert->none($minMax->DEFAULT_VALUE);

        return $minMax; 
    }

    public function getMinMaxProdTemperature($limitItem)
    {  
        $minMax = MinMax::where('LIMIT_ITEM', intval($limitItem))->first();
        $minMax->LIMIT_MAX = $this->units->prodTemperature($minMax->LIMIT_MAX, 2, 1);
        $minMax->LIMIT_MIN = $this->units->prodTemperature($minMax->LIMIT_MIN, 2, 1);
        $minMax->DEFAULT_VALUE = $this->units->prodTemperature($minMax->DEFAULT_VALUE, 2, 1);

        return $minMax; 
    }

    public function getMinMaxTime($limitItem)
    {  
        $minMax = MinMax::where('LIMIT_ITEM', intval($limitItem))->first();
        $minMax->LIMIT_MAX = $this->units->time($minMax->LIMIT_MAX, 3, 1);
        $minMax->LIMIT_MIN = $this->units->time($minMax->LIMIT_MIN, 3, 1);
        $minMax->DEFAULT_VALUE = $this->units->time($minMax->DEFAULT_VALUE, 3,1);

        return $minMax; 
    }

    public function getMinMaxLimitItem($limitItem)
    {  
        $minMax = MinMax::where('LIMIT_ITEM', intval($limitItem))->first();

        return $minMax; 
    }
    
    public function getMinMaxDeltaTemperature($limitItem)
    {
        $minMax = MinMax::where('LIMIT_ITEM', intval($limitItem))->first();
        $minMax->LIMIT_MAX = $this->units->deltaTemperature($minMax->LIMIT_MAX, 2, 1);
        $minMax->LIMIT_MIN = $this->units->deltaTemperature($minMax->LIMIT_MIN, 2, 1);
        $minMax->DEFAULT_VALUE = $this->units->deltaTemperature($minMax->DEFAULT_VALUE, 2,1);

        return $minMax; 
    }

    public function getMinMaxUPercent($limitItem)
    {
        $uPercent = $this->units->uPercent();       
        $minMax = MinMax::where('LIMIT_ITEM', intval($limitItem))->first();
        $minMax->LIMIT_MAX =  $this->units->convertCalculator($minMax->LIMIT_MAX, $uPercent["coeffA"], $uPercent["coeffB"], 2, 1);
        $minMax->LIMIT_MIN = $this->units->convertCalculator($minMax->LIMIT_MIN, $uPercent["coeffA"], $uPercent["coeffB"], 2, 1);
        $minMax->DEFAULT_VALUE = $this->units->convertCalculator($minMax->DEFAULT_VALUE, $uPercent["coeffA"], $uPercent["coeffB"], 2, 1);

        return $minMax; 
    }

    public function getMinMaxUPercentNone($limitItem)
    {
        $uPercent = $this->units->uPercent();       
        $minMax = MinMax::where('LIMIT_ITEM', intval($limitItem))->first();
        return $minMax; 
    }

    public function getMinMaxTimeStep($limitItem, $decimal)
    {
        $minMax = MinMax::where('LIMIT_ITEM', intval($limitItem))->first();
        $minMax->LIMIT_MAX = $this->units->timeStep($minMax->LIMIT_MAX, $decimal, 1);
        $minMax->LIMIT_MIN = $this->units->timeStep($minMax->LIMIT_MIN, $decimal, 1);
        $minMax->DEFAULT_VALUE = $this->units->timeStep($minMax->DEFAULT_VALUE, 2,1);

        return $minMax; 
    }

    public function getMinMaxTemperature($limitItem)
    {
        $minMax = MinMax::where('LIMIT_ITEM', intval($limitItem))->first();
        $minMax->LIMIT_MAX = $this->units->temperature($minMax->LIMIT_MAX, 2, 1);
        $minMax->LIMIT_MIN = $this->units->temperature($minMax->LIMIT_MIN, 2, 1);
        $minMax->DEFAULT_VALUE = $this->units->temperature($minMax->DEFAULT_VALUE, 2,1);

        return $minMax; 
    }

    public function getMinMaxConductivity($limitItem, $decimal)
    {
        $minMax = MinMax::where('LIMIT_ITEM', intval($limitItem))->first();
        $minMax->LIMIT_MAX = $this->units->conductivity($minMax->LIMIT_MAX, $decimal, 1);
        $minMax->LIMIT_MIN = $this->units->conductivity($minMax->LIMIT_MIN, $decimal, 1);
        $minMax->DEFAULT_VALUE = $this->units->conductivity($minMax->DEFAULT_VALUE, $decimal,1);

        return $minMax; 
    }

    public function getMinMaxCoeff($limitItem, $decimal)
    {   
        $minMax = MinMax::where('LIMIT_ITEM', intval($limitItem))->first();
        $minMax->LIMIT_MAX =  $this->units->convectionCoeff($minMax->LIMIT_MAX, $decimal, 1);
        $minMax->LIMIT_MIN = $this->units->convectionCoeff($minMax->LIMIT_MIN, $decimal, 1);
        $minMax->DEFAULT_VALUE = $this->units->convectionCoeff($minMax->DEFAULT_VALUE, $decimal, 1);

        return $minMax; 
    }

    public function getMinMaxTankCapacity($limitItem, $typeunit, $decimal)
    {
        $minMax = MinMax::where('LIMIT_ITEM', intval($limitItem))->first();
        $minMax->LIMIT_MAX = $this->units->tankCapacity($minMax->LIMIT_MAX, $typeunit, $decimal, 1);
        $minMax->LIMIT_MIN = $this->units->tankCapacity($minMax->LIMIT_MIN, $typeunit, $decimal, 1);
        $minMax->DEFAULT_VALUE = $this->units->tankCapacity($minMax->DEFAULT_VALUE, $typeunit, $decimal,1);

        return $minMax; 
    }

    public function getMinMaxLineDimension($limitItem, $decimal)
    {
        $minMax = MinMax::where('LIMIT_ITEM', intval($limitItem))->first();
        $minMax->LIMIT_MAX = $this->units->lineDimension($minMax->LIMIT_MAX, $decimal, 1);
        $minMax->LIMIT_MIN = $this->units->lineDimension($minMax->LIMIT_MIN, $decimal, 1);
        $minMax->DEFAULT_VALUE = $this->units->lineDimension($minMax->DEFAULT_VALUE, $decimal,1);

        return $minMax; 
    }

    public function getMinMaxControlTemperature($limitItem, $decimal)
    {
        $minMax = MinMax::where('LIMIT_ITEM', intval($limitItem))->first();
        $minMax->LIMIT_MAX = $this->units->controlTemperature($minMax->LIMIT_MAX, $decimal, 1);
        $minMax->LIMIT_MIN = $this->units->controlTemperature($minMax->LIMIT_MIN, $decimal, 1);
        $minMax->DEFAULT_VALUE = $this->units->controlTemperature($minMax->DEFAULT_VALUE, $decimal,1);

        return $minMax; 

    }

    public function getMinMaxTimes($limitItem, $decimal)
    {  
        $minMax = MinMax::where('LIMIT_ITEM', intval($limitItem))->first();
        $minMax->LIMIT_MAX = $this->units->time($minMax->LIMIT_MAX, $decimal, 1);
        $minMax->LIMIT_MIN = $this->units->time($minMax->LIMIT_MIN, $decimal, 1);
        $minMax->DEFAULT_VALUE = $this->units->time($minMax->DEFAULT_VALUE, $decimal,1);

        return $minMax; 
    }

    public function getMinMaxLimitItemRelaxCoef($limitItem, $decimal)
    {  
        $minMax = MinMax::where('LIMIT_ITEM', intval($limitItem))->first();
        $minMax->LIMIT_MAX = number_format((float)$minMax->LIMIT_MAX, 0, '.', '');
        $minMax->LIMIT_MIN = number_format((float)$minMax->LIMIT_MIN, 0, '.', '');
        $minMax->DEFAULT_VALUE = number_format((float)$minMax->DEFAULT_VALUE, 0, '.', '');

        return $minMax; 
    }

    // end HAIDT
}