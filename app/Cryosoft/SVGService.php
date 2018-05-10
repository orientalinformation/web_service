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

use App\Models\EquipCharact;

class SVGService
{
    /**
    * @var Illuminate\Contracts\Auth\Factory
    */
    protected $auth;

    protected $app;

    protected $value;

    protected $convert;
    
    public function __construct(\Laravel\Lumen\Application $app)
    {
        $this->app = $app;
        $this->auth = $app['Illuminate\\Contracts\\Auth\\Factory'];
        $this->value = $app['App\\Cryosoft\\ValueListService'];
        $this->convert = $app['App\\Cryosoft\\UnitsConverterService'];
    }

    public function getAxisOrigin() 
    {
        $x = PROFILE_CHARTS_MARGIN_WIDTH;
        $y = PROFILE_CHARTS_HEIGHT - PROFILE_CHARTS_MARGIN_HEIGHT;

        $point = [
            'x' => $x,
            'y' => $y
        ];

        return $point;
    }

    public function getAxisXPos($lfVal)
    {
        $pos = $minScaleX = 0;
        $maxScaleX = 100;

        if ($lfVal < $minScaleX) {
          $lfVal = $minScaleX;
        } else if ($lfVal > $maxScaleX) {
          $lfVal = $minScaleX;
        }

        $axisXLength = PROFILE_CHARTS_WIDTH - (2 * PROFILE_CHARTS_MARGIN_WIDTH);

        $pos = PROFILE_CHARTS_MARGIN_WIDTH + round((float)(($lfVal - $minScaleX) / ($maxScaleX - $minScaleX)) * $axisXLength);

        return $pos;
    }

    public function getAxisYPos($lfVal, $minScaleY, $maxScaleY)
    {
        $pos = 0;
        if ($lfVal < $minScaleY) {
          $lfVal = $minScaleY;
        } else if ($lfVal > $maxScaleY) {
          $lfVal = $maxScaleY;
        }

        $axisYLength = PROFILE_CHARTS_HEIGHT - (2 * PROFILE_CHARTS_MARGIN_HEIGHT);

        if ($minScaleY == $maxScaleY) {
            $pos = (PROFILE_CHARTS_HEIGHT - PROFILE_CHARTS_MARGIN_HEIGHT - 125);
        } else {
            $pos = (PROFILE_CHARTS_HEIGHT - PROFILE_CHARTS_MARGIN_HEIGHT) - round((float)(($lfVal - $minScaleY) / ($maxScaleY - $minScaleY)) * $axisYLength);
        }

        return $pos;
    }

    public function getAxisX()
    {
        $listOfGraduation = $axisline = array();
        $unitIdent = 10;
        $minScaleX = 0;
        $maxScaleX = 100.0;
        $axisLength = (PROFILE_CHARTS_WIDTH - (2 * PROFILE_CHARTS_MARGIN_WIDTH)) + 20;
        $nbFractionDigits = 0;
        $axisOriginX = PROFILE_CHARTS_MARGIN_WIDTH;
        $axisOriginY = PROFILE_CHARTS_HEIGHT - PROFILE_CHARTS_MARGIN_HEIGHT;
        $nbOfGraduation = 11;
        $minValueX = 0.0;
        $maxValueX = 100.0;
        $axisType = 0;

        // first point
        // $firstPoint = [$axisOriginX, $axisOriginY]; // 75 300
        $x1 = $axisOriginX;
        $y1 = $axisOriginY;

        // last point
        // $lastPoint = [$axisOriginX, $axisOriginY]; // 75 300
        $x2 = $axisOriginX;
        $y2 = $axisOriginY; 

        if ($axisType == 0) {
          // $lastPoint = $this->translate($axisLength, 0);
          $x2 = $x2 + $axisLength;
          $y2 = $y2 + 0;
        } else {
          // $lastPoint = $this->translate(0, -$axisLength);
          $x2 = $x2 + 0;
          $y2 = $y2 + (-$axisLength);
        }

        $item['x1'] = $x1;
        $item['y1'] = $y1;
        $item['x2'] = $x2;
        $item['y2'] = $y2;
        array_push($axisline, $item);

        //axisLine new axisLine ($fistPoint, $lastPoint, $unitIdent);
        $offset = ($maxScaleX - $minScaleX) / ($nbOfGraduation - 1);
        $offsetpix = ($axisLength - 20) / ($nbOfGraduation - 1);
    
        $lfValue = $minScaleX;
        for ($i = 0; $i < $nbOfGraduation; $i++) {
            // first point
            $x1 = $axisOriginX;
            $y1 = $axisOriginY;

            if ($axisType == 0) {
                // $firstPoint = $this->translate($i*$offsetpix, 0);
                $x1 = $x1 + $i*$offsetpix;
                $y1 = $y1 + 0;

                // $lastPoint = $this->translate(0, 10);
                $x2 = $x1 + 0;
                $y2 = $y1 + 10;
            } else {
                // $firstPoint = $this->translate(0, -$i*$offsetpix);
                $x1 = $x1 + 0;
                $y1 = $y1 + (-$i*$offsetpix);

                // $lastPoint = $this->translate(-10, 0);
                $x2 = $x1 - 10;
                $y2 = $y1 + 0;
            }

            // new EPLine
            $item['x1'] = $x1;
            $item['y1'] = $y1;
            $item['x2'] = $x2;
            $item['y2'] = $y2;
            $item['position'] = $this->convert->convertIdent($lfValue, $unitIdent);
            array_push($listOfGraduation, $item);

            $lfValue += $offset;
        }

        $array = [
            'axisline' => $axisline,
            'listOfGraduation' => $listOfGraduation
        ];

        return $array;
    }

    public function getSelectedProfile($ID_EQUIP, $profileType, $profileFace)
    {   
        $listOfPoints = $item = array();
        $equipCharacts = EquipCharact::where('ID_EQUIP', $ID_EQUIP)->get();
        if ($equipCharacts) {
            foreach ($equipCharacts as $equipCharact) {
                $item['X_POSITION'] = doubleval($equipCharact->X_POSITION);
                if ($profileType == 1) {
                    switch ($profileFace) {
                        case 0:
                            $item['Y_POINT'] = doubleval($equipCharact->ALPHA_TOP);
                            break;
                        case 1:
                            $item['Y_POINT'] = doubleval($equipCharact->ALPHA_BOTTOM);
                            break;
                        case 2:
                            $item['Y_POINT'] = doubleval($equipCharact->ALPHA_LEFT);
                            break;
                        case 3:
                            $item['Y_POINT'] = doubleval($equipCharact->ALPHA_RIGHT);
                            break;
                        case 4:
                            $item['Y_POINT'] = doubleval($equipCharact->ALPHA_FRONT);
                            break;
                        case 5:
                            $item['Y_POINT'] = doubleval($equipCharact->ALPHA_REAR);
                            break;
                        default:
                            # code..
                            break;
                    }
                } else {
                    switch ($profileFace) {
                        case 0:
                            $item['Y_POINT'] = doubleval($equipCharact->TEMP_TOP);
                            break;
                        case 1:
                            $item['Y_POINT'] = doubleval($equipCharact->TEMP_BOTTOM);
                            break;
                        case 2:
                            $item['Y_POINT'] = doubleval($equipCharact->TEMP_LEFT);
                            break;
                        case 3:
                            $item['Y_POINT'] = doubleval($equipCharact->TEMP_RIGHT);
                            break;
                        case 4:
                            $item['Y_POINT'] = doubleval($equipCharact->TEMP_FRONT);
                            break;
                        case 5:
                            $item['Y_POINT'] = doubleval($equipCharact->TEMP_REAR);
                            break;
                        default:
                            # code...
                            break;
                    }
                }
                array_push($listOfPoints, $item);
            }
        }

        return $listOfPoints;
    }

    public function getAxisY($minScaleY, $maxScaleY, $minValueY, $maxValueY, $nbFractionDigits, $unitIdent)
    {
        $listOfGraduation = $axisline = array();
        $axisLength = (PROFILE_CHARTS_HEIGHT - (2 * PROFILE_CHARTS_MARGIN_HEIGHT)) + 20; //270
        $axisOriginX = $x1 = $x2 = PROFILE_CHARTS_MARGIN_WIDTH;
        $axisOriginY = $y1 = $y2 = PROFILE_CHARTS_HEIGHT - PROFILE_CHARTS_MARGIN_HEIGHT;
        $nbOfGraduation = $this->getBestGraduation($minScaleY, $maxScaleY, $nbFractionDigits);
        $axisMaxValue = 0.0;
        $axisMinValue = 0.0;
        $axisType = 1; // 0
        $textX = $textY = 0;

        // first point
        // $firstPoint = [$axisOriginX, $axisOriginY]; // 75 300
        $x1 = $axisOriginX;
        $y1 = $axisOriginY;

        // last point
        // $lastPoint = [$axisOriginX, $axisOriginY]; // 75 300
        $x2 = $axisOriginX;
        $y2 = $axisOriginY; 

        if ($axisType == 0) {
          // $lastPoint = $this->translate($axisLength, 0);
          $x2 = $x2 + $axisLength;
          $y2 = $y2 + 0;
        } else {
          // $lastPoint = $this->translate(0, -$axisLength);
          $x2 = $x2 + 0;
          $y2 = $y2 + (-$axisLength);
        }

        $item['x1'] = $x1;
        $item['y1'] = $y1;
        $item['x2'] = $x2;
        $item['y2'] = $y2;
        array_push($axisline, $item);

        //axisLine new axisLine ($fistPoint, $lastPoint, $unitIdent);
        $offset = ($maxScaleY - $minScaleY) / ($nbOfGraduation - 1);
        $offsetpix = ($axisLength - 20) / ($nbOfGraduation - 1);
    
        $lfValue = $minScaleY;
        for ($i = 0; $i < $nbOfGraduation; $i++) {
            // first point
            $x1 = $axisOriginX;
            $y1 = $axisOriginY;

            if ($axisType == 0) {
                // $firstPoint = $this->translate($i*$offsetpix, 0);
                $x1 = $x1 + $i*$offsetpix;
                $y1 = $y1 + 0;

                // $lastPoint = $this->translate(0, 10);
                $x2 = $x1 + 0;
                $y2 = $y1 + 10;

                // add text
                $textX = $x2 + 0;
                $textY = $y2 + 10;
            } else {
                // $firstPoint = $this->translate(0, -$i*$offsetpix);
                $x1 = $x1 + 0;
                $y1 = $y1 + (-$i*$offsetpix);

                // $lastPoint = $this->translate(-10, 0);
                $x2 = $x1 - 10;
                $y2 = $y1 + 0;

                // add text
                $textX = $x2 - 10;
                $textY = $y2 + 0;
            }

            // new EPLine
            $item['x1'] = $x1;
            $item['y1'] = $y1;
            $item['x2'] = $x2;
            $item['y2'] = $y2;
            $item['textX'] = $textX;
            $item['textY'] = $textY;
            $item['position'] = $lfValue;
            array_push($listOfGraduation, $item);

            $lfValue += $offset;
        }

        $array = [
            'axisline' => $axisline,
            'listOfGraduation' => $listOfGraduation
        ];

        return $array;
    }

    public function generateNewProfile($listOfPointsOld, $listOfSelectedPoints, $minValue, $maxValue)
    {   
        $result = null;
        if (count($listOfSelectedPoints) > 0) {
            for ($i = 0; $i < count($listOfSelectedPoints); $i++) {
                if (floatval($listOfSelectedPoints[$i]['Y_POINT']) == floatval(0)) {
                    $listOfSelectedPoints[$i]['Y_POINT'] = $this->linearInterpValue($listOfPointsOld, $minValue, $maxValue, $listOfSelectedPoints[$i]['X_POSITION']);
                }
            }

            $result = $listOfSelectedPoints;
        }

        return $result;
    }

    private function linearInterpValue($listOfSelectedPoints, $minValue, $maxValue, $X_POSITION) 
    {
        $value = 0;
        // Coefficient de la droite y = ax+b
        $coefA = 0;
        $coefB = 0;
        // coordonnes de 2 points connus A et B
        $xA = $yA = $xB = $yB = NEGATIVE_INFINITY;
        // coordonnes point courant
        $x;
        
        $size = count($listOfSelectedPoints);

        if (floatval($X_POSITION) <= floatval($listOfSelectedPoints[0]['X_POSITION'])) {
            $value = $listOfSelectedPoints[0]['Y_POINT'];
        } else if (floatval($X_POSITION) >= floatval($listOfSelectedPoints[$size - 1]['X_POSITION'])) {
            $value = $listOfSelectedPoints[$size - 1]['Y_POINT'];
        } else {
            for ($i = 1; $i < $size; ++$i) {
                $x = $listOfSelectedPoints[$i]['X_POSITION'];
                
                if (floatval($X_POSITION) == floatval($x)) {
                    $value = floatval($listOfSelectedPoints[$i]['Y_POINT']);
                } else if (floatval($x) > floatval($X_POSITION)) {
                    $xA = $listOfSelectedPoints[$i - 1]['X_POSITION'];
                    $yA = $listOfSelectedPoints[$i - 1]['Y_POINT'];

                    $xB = $listOfSelectedPoints[$i]['X_POSITION'];
                    $yB = $listOfSelectedPoints[$i]['Y_POINT'];
                    break;
                }
            }

            if (floatval($xA) != floatval($xB)) {
                $coefA = (floatval($yB) - floatval($yA)) / (floatval($xB) - floatval($xA));
                $coefB = floatval($yB) - (floatval($coefA) * floatval($xB));

                $value = (floatval($coefA) * floatval($X_POSITION)) + floatval($coefB);
            } else {
                $value = floatval($yA);
            }
        }

        if (floatval($value) < floatval($minValue)) {
            $value = $minValue;
        } else if (floatval($value) > floatval($maxValue)) {
            $value = $maxValue;
        }

        return $value;
    }

    private function getBestGraduation($minScaleY, $maxScaleY, $nbFractionDigitsY)
    {
        $nbGraduation = 11;
        $prec = 1.0 / pow(10.0, $nbFractionDigitsY);

        $nbGraduation = (int)round(abs(($maxScaleY - $minScaleY) / $prec) + 1.0);
        if ($nbGraduation < 2) {
          $nbGraduation = 2;
        } else if ($nbGraduation > 11) {
          $nbGraduation = 11;
        }

        return $nbGraduation;
    }
}