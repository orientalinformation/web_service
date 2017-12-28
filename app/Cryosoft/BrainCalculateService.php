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


class BrainCalculateService
{
	/**
	 * @var App\Cryosoft\ValueListService
	 */
	protected $value;

	/**
	 * @var App\Cryosoft\ValueListService
	 */
	protected $convert;

	function __construct(ValueListService $value, UnitsConverterService $convert)
	{
		$this->value = $value;
	}

	public function getCalcParams($idStudyEquipments)
	{
		$calcParameter = CalculationParameter::where('ID_STUDY_EQUIPMENTS', $idStudyEquipments)->first();

		return $calcParameter;
	}

	public function getHradioOn($idStudyEquipments) 
    {
        $etat = "";
        $calcParameter = $this->getCalcParams($idStudyEquipments);
        if (!empty($calcParameter)) {
            if ($calcParameter->isHorizScan()) {
                $etat = "checked";
            }   
        }
        
        return $etat;
    }

    public function getHradioOff($idStudyEquipments) 
    {
        $etat = "";
        $calcParameter = $this->getCalcParams($idStudyEquipments);
        if (!empty($calcParameter)) {
            if (!$calcParameter->isHorizScan()) {
                $etat = "checked";
            }
        }
        return $etat;
    }

    public function getVradioOn($idStudyEquipments) 
    {
        $etat = "";
        $calcParameter = $this->getCalcParams($idStudyEquipments);
        if (!empty($calcParameter)) {
            if ($calcParameter->isVertScan()) {
                $etat = "checked";
            }
        }
        return $etat;
    }

    public function getMaxIter($idStudyEquipments) 
    {
        $calcParameter = $this->getCalcParams($idStudyEquipments);
        $maxItNb = 0;
        if (!empty($calcParameter)) {
            $maxItNb = $calcParameter->getMaxItNb();
        }
        return $maxItNb;
    }

    public function getRelaxCoef($idStudyEquipments) 
    {
        $calcParameter = $this->getCalcParams($idStudyEquipments);
        $relaxCoeff = 0;
        if (!empty($calcParameter)) {
            $relaxCoeff =  $calcParameter->getRelaxCoeff();
        }

        return $relaxCoeff;
    }

    public function getPrecision($idStudyEquipments) 
    {
        $calcParameter = $this->getCalcParams($idStudyEquipments);
        $precision = 0;

        if (!empty($calcParameter)) {
            $precision = $calcParameter->getPrecisionRequest();
        }

        return $this->convert->unitConvert($this->value->TIME, $precision, 3);
    }

    public function getTempPtSurf($idStudyEquipments) 
    {
        $calcParameter = $this->getCalcParams($idStudyEquipments);
        $topSurf = 0;

        if (!empty($calcParameter)) {
            $topSurf = $calcParameter->getStopTopSurf();
        }

        return $this->convert->unitConvert($this->value->TEMPERATURE, $topSurf, 2);
    }

    public function getTempPtIn($idStudyEquipments) 
    {
        $calcParameter = $this->getCalcParams($idStudyEquipments);
        $topInt = 0;

        if (!empty($calcParameter)) {
            $topInt = $calcParameter->getStopInt();
        }

        return $this->convert->unitConvert($this->value->TEMPERATURE, $topInt, 2);
    }

    public function getTempPtBot($idStudyEquipments) 
    {
        $calcParameter = $this->getCalcParams($idStudyEquipments);
        $bottomSurf = 0;

        if (!empty($calcParameter)) {
            $bottomSurf = $calcParameter->getStopBottomSurf();
        }

        return $this->convert->unitConvert($this->value->TEMPERATURE, $bottomSurf, 2);
    }

    public function getTempPtAvg($idStudyEquipments) 
    {
        $calcParameter = $this->getCalcParams($idStudyEquipments);
        $topAvg = 0;

        if (!empty($calcParameter)) {
            $topAvg = $calcParameter->getStopAvg();
        }

        return $this->convert->unitConvert($this->value->TEMPERATURE, $topAvg, 2);
    }

    public function getPrecisionLogStep($idStudyEquipments) 
    {
        $calcParameter = $this->getCalcParams($idStudyEquipments);
        $logStep = 0;

        if (!empty($calcParameter)) {
            $logStep = $calcParameter->getPrecisionLogStep();
        }

        return $logStep;
    }

    public function getStorageStep($idStudyEquipments) 
    {
        $calcParameter = $this->getCalcParams($idStudyEquipments);
        $lfStep = 0.0;

        if (!empty($calcParameter)) {
            $lfStep = $calcParameter->getStorageStep() * $calcParameter->getTimeStep();
        }

        return $this->convert->unitConvert($this->value->TIME, $lfStep, 1);
    }

    public function getTimeStep($idStudyEquipments) 
    {
        $calcParameter = $this->getCalcParams($idStudyEquipments);
        $lfStep = 0.0;

        if (!empty($calcParameter)) {
            $lfStep = $calcParameter->getTimeStep();
        }

        return $this->convert->unitConvert($this->value->TIME, $lfStep, 1);
    }

	public function getMinMax($limitItem) 
    {
		return MinMax::where('LIMIT_ITEM', $limitItem)->first();
	}
}