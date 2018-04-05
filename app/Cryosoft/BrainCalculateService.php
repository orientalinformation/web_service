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

use App\Models\CalculationParameter;
use App\Cryosoft\ValueListService;
use App\Cryosoft\UnitsConverterService;
use App\Models\MinMax;
use App\Models\StudEqpPrm;
use App\Models\LayoutResults;
use App\Models\Study;
use App\Models\StudyEquipment;


class BrainCalculateService
{
    /**
	 * @var App\Cryosoft\ValueListService
	 */
    protected $auth;
    
	/**
	 * @var App\Cryosoft\ValueListService
	 */
	protected $value;

	/**
	 * @var App\Cryosoft\ValueListService
	 */
    protected $convert;
    
    /**
	 * @var App\Cryosoft\UnitsService
	 */
	protected $units;

    public function __construct(\Laravel\Lumen\Application $app) 
    {
		$this->app = $app;
		$this->auth = $app['Illuminate\\Contracts\\Auth\\Factory'];
		$this->value = $app['App\\Cryosoft\\ValueListService'];
		$this->convert = $app['App\\Cryosoft\\UnitsConverterService'];
		$this->units = $app['App\\Cryosoft\\UnitsService'];

	}

	public function getCalcParams($idStudyEquipments)
	{
		$calcParameter = CalculationParameter::where('ID_STUDY_EQUIPMENTS', $idStudyEquipments)->first();

		return $calcParameter;
	}

	public function getHradioOn($idStudyEquipments) 
    {
        $etat = 0;
        $calcParameter = $this->getCalcParams($idStudyEquipments);
        if (!empty($calcParameter)) {
            if ($calcParameter->HORIZ_SCAN) {
                $etat = 1;
            }   
        }
        
        return $etat;
    }

    public function getHradioOff($idStudyEquipments) 
    {
        $etat = 0;
        $calcParameter = $this->getCalcParams($idStudyEquipments);
        if (!empty($calcParameter)) {
            if (!$calcParameter->HORIZ_SCAN) {
                $etat = 1;
            }
        }
        return $etat;
    }

    public function getVradioOn($idStudyEquipments) 
    {
        $etat = 0;
        $calcParameter = $this->getCalcParams($idStudyEquipments);
        if (!empty($calcParameter)) {
            if ($calcParameter->VERT_SCAN) {
                $etat = 1;
            }
        }
        return $etat;
    }

    public function getMaxIter($idStudyEquipments) 
    {
        $calcParameter = $this->getCalcParams($idStudyEquipments);
        $maxItNb = 0;
        if (!empty($calcParameter)) {
            $maxItNb = $calcParameter->MAX_IT_NB;
        }
        return intval($maxItNb);
    }

    public function getRelaxCoef($idStudyEquipments) 
    {
        $calcParameter = $this->getCalcParams($idStudyEquipments);
        $relaxCoeff = 0;
        if (!empty($calcParameter)) {
            $relaxCoeff =  $calcParameter->RELAX_COEFF;
        }

        return number_format((float)$relaxCoeff, 2, '.', '');
    }

    public function getPrecision($idStudyEquipments) 
    {
        $calcParameter = $this->getCalcParams($idStudyEquipments);
        $precision = 0;

        if (!empty($calcParameter)) {
            $precision = $calcParameter->PRECISION_REQUEST;
        }

        return $this->units->convert($precision, 3);
    }

    public function getTempPtSurf($idStudyEquipments) 
    {
        $calcParameter = $this->getCalcParams($idStudyEquipments);
        $topSurf = 0;

        if (!empty($calcParameter)) {
            $topSurf = $calcParameter->STOP_TOP_SURF;
        }

        return $this->units->temperature($topSurf, 2, 1);
    }

    public function getTempPtIn($idStudyEquipments) 
    {
        $calcParameter = $this->getCalcParams($idStudyEquipments);
        $topInt = 0;

        if (!empty($calcParameter)) {
            $topInt = $calcParameter->STOP_INT;
        }

        return $this->units->temperature($topInt, 2, 1);
    }

    public function getTempPtBot($idStudyEquipments) 
    {
        $calcParameter = $this->getCalcParams($idStudyEquipments);
        $bottomSurf = 0;

        if (!empty($calcParameter)) {
            $bottomSurf = $calcParameter->STOP_BOTTOM_SURF;
        }

        return $this->units->temperature($bottomSurf, 2, 1);
    }

    public function getTempPtAvg($idStudyEquipments) 
    {
        $calcParameter = $this->getCalcParams($idStudyEquipments);
        $topAvg = 0;

        if (!empty($calcParameter)) {
            $topAvg = $calcParameter->STOP_AVG;
        }

        return $this->units->temperature($topAvg, 2, 1);
    }

    public function getPrecisionLogStep($idStudyEquipments) 
    {
        $calcParameter = $this->getCalcParams($idStudyEquipments);
        $logStep = 0;

        if (!empty($calcParameter)) {
            $logStep = $calcParameter->PRECISION_LOG_STEP;
        }

        return intval($logStep);
    }

    public function getStorageStep($idStudyEquipments) 
    {
        $calcParameter = $this->getCalcParams($idStudyEquipments);
        $lfStep = 0.0;

        if (!empty($calcParameter)) {
            $lfStep = $calcParameter->STORAGE_STEP * $calcParameter->TIME_STEP;
        }

        return $this->units->timeStep($lfStep, 1, 1);
    }

    public function getTimeStep($idStudyEquipments) 
    {
        $calcParameter = $this->getCalcParams($idStudyEquipments);
        $lfStep = 0.0;

        if (!empty($calcParameter)) {
            $lfStep = $calcParameter->TIME_STEP;
        }

        return $this->units->timeStep($lfStep, 3, 1);
    }

	public function getMinMax($limitItem) 
    {
		return MinMax::where('LIMIT_ITEM', $limitItem)->first();
	}

    public function getLoadingRate($idStudyEquipment, $idStudy)
    {
        $calMode = $this->getCalculationMode($idStudy);
        $loadingRate = 0;
        $layoutResult =  LayoutResults::where("ID_STUDY_EQUIPMENTS", $idStudyEquipment)->first();

        if ($layoutResult != null) {
            if ($calMode == $this->value->BRAIN_MODE_OPTIMUM_DHPMAX) {
                $loadingRate = $layoutResult->LOADING_RATE_MAX;
            } else {
                $loadingRate = $layoutResult->LOADING_RATE;
            }
        }

        return $loadingRate;
    }

    public function getListTr($idStudyEquipments)
    {
        $studEqpPrms = $this->loadStudEqpPrm($idStudyEquipments, 300);
        $tR = array();

        if (!empty($studEqpPrms)) {
            foreach ($studEqpPrms as $prms) {
                array_push($tR, $prms->VALUE);
             } 
        }

        return $tR;
    }

    public function setTr($idStudyEquipments, $value, $i) 
    {
        $studEqpPrms = $this->loadStudEqpPrm($idStudyEquipments, 300);
        if (!empty($studEqpPrms[$i])) {
            $studEqpPrms[$i]->VALUE = $value;
            $studEqpPrms[$i]->save();
        }
    }

    public function getListTs($idStudyEquipments)
    {
        $studEqpPrms = $this->loadStudEqpPrm($idStudyEquipments, 200);
        $tS = array();

        if (!empty($studEqpPrms)) {
            foreach ($studEqpPrms as $prms) {
                array_push($tS, $prms->VALUE);
            } 
        }
        
        return $tS;
    }

    public function setTs($idStudyEquipments, $value, $i) 
    {
        $studEqpPrms = $this->loadStudEqpPrm($idStudyEquipments, 200);
        if (!empty($studEqpPrms[$i])) {
            $studEqpPrms[$i]->VALUE = $value;
            $studEqpPrms[$i]->save();
        }
    }

    public function loadStudEqpPrm($idStudyEquipments, $dataType)
    {
        $studEqpPrms = StudEqpPrm::where('ID_STUDY_EQUIPMENTS', $idStudyEquipments)
                                    ->where('VALUE_TYPE', '>=', $dataType)
                                    ->where('VALUE_TYPE', '<', ($dataType + 100))->get();

        return $studEqpPrms;
    }

    public function getCalculationMode($idStudy) 
    {
        $calMode = 0;
        $study = Study::find($idStudy);

        if ($study != null) {
            $calMode = $study->CALCULATION_MODE;
        }

        return $calMode;
    }

    public function getOptimErrorT($brainMode, $idStudyEquipments)
    {
        $sOptimErrorT = 0;
        $studyEquipment = StudyEquipment::find($idStudyEquipments);
        $brandType = $studyEquipment->BRAIN_TYPE;
        $idCalcParams = $studyEquipment->ID_CALC_PARAMS;

        $calcParameters = CalculationParameter::find($idCalcParams);

        switch ($brainMode) {
            case 1:
            case 2:
            case 10:
            case 14:
                $minMax = $this->getMinMax(1132);
                $sOptimErrorT =  $this->units->deltaTemperature($minMax->DEFAULT_VALUE, 2, 1);
                break;

            case 11:
            case 15:
                if ($brandType == 2) {
                    $sOptimErrorT =  $this->units->deltaTemperature($calcParameters->ERROR_T, 2, 1);
                } else {
                    $minMax = $this->getMinMax(1134);
                    $sOptimErrorT =  $this->units->deltaTemperature($minMax->DEFAULT_VALUE, 2, 1);
                }
                break;

            case 12:
            case 16:
                if ($brandType == 4 || $brandType == 3) {
                    $sOptimErrorT =   $this->units->deltaTemperature($calcParameters->ERROR_T, 2, 1);
                } else {
                    $minMax = $this->getMinMax(1136);
                    $sOptimErrorT =  $this->units->deltaTemperature($minMax->DEFAULT_VALUE, 2, 1);
                }
                break;

            case 13:
            case 17:
                if ($brandType != 0 || $brandType != 1) {
                    $sOptimErrorT =   $this->units->deltaTemperature($calcParameters->ERROR_T, 2, 1);
                } else {
                    $minMax = $this->getMinMax(1138);
                    $sOptimErrorT =  $this->units->deltaTemperature($minMax->DEFAULT_VALUE, 2, 1);
                }
                break;
        }

        return $sOptimErrorT;
    }

    public function getOptimErrorH($brainMode, $idStudyEquipment)
    {
        $sOptimErrorH = 0;
        $studyEquipment = StudyEquipment::find($idStudyEquipment);
        $brandType = $studyEquipment->BRAIN_TYPE;
        $idCalcParams = $studyEquipment->ID_CALC_PARAMS;
        $calcParameters = CalculationParameter::find($idCalcParams);
        $uPercent = $this->convert->uPercent();

        switch ($brainMode) {
            case 1:
            case 2:
            case 10:
            case 14:
                $minMax = $this->getMinMax(1131);
                $sOptimErrorH =  $this->convert->convertCalculator($minMax->DEFAULT_VALUE, $uPercent["coeffA"], $uPercent["coeffB"]);
                break;

            case 11:
            case 15:
                if ($brandType == 2) {
                    $sOptimErrorH =  $this->convert->convertCalculator($calcParameters->ERROR_H, $uPercent["coeffA"], $uPercent["coeffB"]);
                } else {
                    $minMax = $this->getMinMax(1133);
                    $sOptimErrorH =  $this->convert->convertCalculator($minMax->DEFAULT_VALUE, $uPercent["coeffA"], $uPercent["coeffB"]);
                }
                break;

            case 12:
            case 16:
                if ($brandType == 4 || $brandType == 3) {
                    $sOptimErrorH =  $this->convert->convertCalculator($calcParameters->ERROR_H, $uPercent["coeffA"], $uPercent["coeffB"]);
                } else {
                    $minMax = $this->getMinMax(1135);
                    $sOptimErrorH =  $this->convert->convertCalculator($minMax->DEFAULT_VALUE, $uPercent["coeffA"], $uPercent["coeffB"]);
                }
                break;

            case 13:
            case 17:
                if ($brandType != 0 || $brandType != 1) {
                    $sOptimErrorH =  $this->convert->convertCalculator($calcParameters->ERROR_H, $uPercent["coeffA"], $uPercent["coeffB"]);
                } else {
                    $minMax = $this->getMinMax(1137);
                    $sOptimErrorH =  $this->convert->convertCalculator($minMax->DEFAULT_VALUE, $uPercent["coeffA"], $uPercent["coeffB"]);
                }
                break;
        }

        return $sOptimErrorH;
    }

    public function getNbOptim($brainMode, $idStudyEquipment)
    {
        $sNbOptim = "";
        $studyEquipment = StudyEquipment::find($idStudyEquipment);
        $brandType = $studyEquipment->BRAIN_TYPE;
        $idCalcParams = $studyEquipment->ID_CALC_PARAMS;
        $calcParameters = CalculationParameter::find($idCalcParams);
        $uNone = $this->convert->uNone();
        $minMax = $this->getMinMax(1130);

        switch ($brainMode) {
            case 1:
            case 2:
            case 10:
            case 14:
                $sNbOptim =  $this->convert->convertCalculator($minMax->DEFAULT_VALUE, $uNone["coeffA"], $uNone["coeffB"]);
                break;

            case 11:
            case 15:
                if ($brandType == 2 && $calcParameters->NB_OPTIM > 0) {
                    $sNbOptim =  $this->convert->convertCalculator($calcParameters->NB_OPTIM, $uNone["coeffA"], $uNone["coeffB"]);
                } else {
                    $sNbOptim =  $this->convert->convertCalculator($minMax->DEFAULT_VALUE, $uNone["coeffA"], $uNone["coeffB"]);
                }
                break;

            case 12:
            case 16:
                if (($brandType == 4 || $brandType == 3) && $calcParameters->NB_OPTIM > 0) {
                    $sNbOptim =  $this->convert->convertCalculator($calcParameters->NB_OPTIM, $uNone["coeffA"], $uNone["coeffB"]);
                } else {
                    $sNbOptim =  $this->convert->convertCalculator($minMax->DEFAULT_VALUE, $uNone["coeffA"], $uNone["coeffB"]);
                }
                break;

            case 13:
            case 17:
                $sNbOptim =  $this->convert->convertCalculator($minMax->DEFAULT_VALUE, $uNone["coeffA"], $uNone["coeffB"]);
                break;
        }

        return doubleval($sNbOptim);
    }

    public function getBrainMode($idStudy)
    {
        $brainMode = 0;
        $calMode = $this->getCalculationMode($idStudy);
        
        if ($calMode == 2) {
            $brainMode = 14;
        } else if ($calMode == 3) {
            $brainMode = 10;
        }

        return $brainMode;
    }
}