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
use Illuminate\Http\Request;

class TempProfiles extends Controller 
{
    /**
    * @var Illuminate\Contracts\Auth\Factory
    */
    protected $auth;

    protected $app;

    protected $value;

    protected $units;

    protected $request;
    
    public function __construct(\Laravel\Lumen\Application $app)
    {
        $this->app = $app;
        $this->auth = $app['Illuminate\\Contracts\\Auth\\Factory'];
        $this->value = $app['App\\Cryosoft\\ValueListService'];
        $this->units = $app['App\\Cryosoft\\UnitsService'];
        $this->request = $app['Illuminate\\Http\\Request'];
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

    private function basisPolynomial($points, $j, $x) 
    {
        $xj = $points[$j][0]; 
        $partialProduct = 1;

        for ($m = 0; $m < count($points); $m++) {
            if ($m === $j) { continue; }                 
            $partialProduct *= ($x - $points[$m][0]) / ($xj - $points[$m][0]);
        }
        return $partialProduct;
    }

    private function lagrangePolynomial($points, $x)
    {
        $partialSum = 0;
        for ($j = 0; $j < count($points); $j++) {
            $value = $this->basisPolynomial($points, $j, $x);
            $partialSum += $points[$j][1] * $value;
        }
        return $partialSum;
    }

    public function getPlotPoints()
    {
        $input = $this->request->all();

        $points = $item = $temp = $parents = $childs = [];
        if (isset($input['xPositions'])) $xPositions = $input['xPositions'];    
        if (isset($input['tempPoints'])) $tempPoints = $input['tempPoints'];

        // is_int($var) if a variable is a number or a numeric string. To identify Zero, use 
        // is_numeric($var) is also the solution or use $var === 0
        // is_null($var) if a variable is NULL

        for ($i = 0; $i < count($tempPoints); $i++) {
            if (!is_null($tempPoints[$i]['value'])) {
                $item[0] = $xPositions[$i];
                $item[1] = $tempPoints[$i]['value'];
                array_push($points, $item);
            }
        }

        $plotPoints = [];
        for ($i = 0; $i < count($tempPoints); $i++) {
            $value = $this->lagrangePolynomial($points, $xPositions[$i]);
            // $plotPoints[] = [$i,  $value];
            $temp['temperature'] = $this->units->temperature($value, 2, 1);
            if (floatval($temp['temperature']) > 100) {
                $temp['temperature'] = 100;
            }

            if (floatval($temp['temperature']) < -100) {
                $temp['temperature'] = -100;
            }

            array_push($plotPoints, $temp);
        }

        return $plotPoints;
    }
}