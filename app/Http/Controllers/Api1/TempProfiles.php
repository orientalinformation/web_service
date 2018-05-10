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
namespace App\Http\Controllers\Api1;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\Auth\Factory as Auth;

class TempProfiles extends Controller 
{
    /**
    * @var Illuminate\Contracts\Auth\Factory
    */
    protected $auth;

    protected $app;

    protected $value;

    protected $units;
    
    public function __construct(\Laravel\Lumen\Application $app)
    {
        $this->app = $app;
        $this->auth = $app['Illuminate\\Contracts\\Auth\\Factory'];
        $this->value = $app['App\\Cryosoft\\ValueListService'];
        $this->units = $app['App\\Cryosoft\\UnitsService'];
    }

    public function getDataSvgTemperature()
    {
        $xsize = $X_ZONE_DE_SAISIE = 245;
        $listofPoints = 22; // list x,y circle
        $svgXsize = (30 + $X_ZONE_DE_SAISIE + 60);
        $ysize = 30 * $listofPoints;
        $svgYsize = $ysize + 60;
        $x0 = $y0 = 30;
        $getYTopGraduate = ($y0 * 2) / 3;
        $getYTopLegend = $y0 / 2;
        $getXquarter = $x0 + ($xsize / 4);
        $getXmedium = $x0 + ($xsize / 2);
        $getXThreeQuarter = $x0 + (($xsize * 3) / 4);
        $getRightLimit = $svgXsize - $x0 - 30;
        $getBottomLimit = $svgYsize - $y0;
        $getYBottomGraduate = ($y0 / 3) + $getBottomLimit;
        $getYBottomLegend = $getBottomLimit + $getYTopGraduate;
        $getXArrow = $getRightLimit + 10;
        $getYArrow = $y0 + 5;
        $getXArrowLeft = $getRightLimit +  15;
        $getXArrowRight = $getRightLimit + 5;
        $getYMediumSVG = $svgYsize / 2;
        $getTextArrow = $getRightLimit + 16;
        $getTempMin = $this->units->temperature(-100, 1, 1);
        $getTempMax = $this->units->temperature(100, 1, 1);
        
        $array = [
            'svgXsize' => $svgXsize,
            'svgYsize' => $svgYsize,
            'x0' => $x0,
            'y0' => $y0,
            'xsize' => $xsize,
            'ysize' => $ysize,
            'getYTopGraduate' => $getYTopGraduate,
            'getYTopLegend' => $getYTopLegend,
            'getXquarter' => $getXquarter,
            'getXmedium' =>  $getXmedium,
            'getXThreeQuarter' => $getXThreeQuarter,
            'getRightLimit' => $getRightLimit,
            'getBottomLimit' => $getBottomLimit,
            'getYBottomGraduate' => $getYBottomGraduate,
            'getYBottomLegend' => $getYBottomLegend,
            'getXArrow' => $getXArrow,
            'getYArrow' => $getYArrow,
            'getXArrowLeft' =>  $getXArrowLeft,
            'getXArrowRight' => $getXArrowRight,
            'getYMediumSVG' => $getYMediumSVG,
            'getTextArrow' => $getTextArrow,
            'getTempMin' => $getTempMin,
            'getTempMax' => $getTempMax,
        ];
        return $array;
    }

    public function initLines($listofPoints)
    {
        
    }
}