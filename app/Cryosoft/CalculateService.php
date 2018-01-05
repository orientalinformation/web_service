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

use App\Cryosoft\UnitsConverterService;
use App\Cryosoft\ValueListService;
use App\Models\CalculationParameter;
use App\Models\CalculationParametersDef;
use App\Models\MinMax;
use App\Models\Study;
use App\Models\StudyEquipment;
use App\Models\TempRecordPts;
use App\Models\MeshPosition;
use App\Models\Product;
use App\Models\ProductElmt;
use App\Models\LayoutResults;
use App\Models\StudEqpPrm;
use Illuminate\Contracts\Auth\Factory as Auth;
use DB;
use App\Quotation;

class CalculateService 
{
	/**
	 * @var Illuminate\Contracts\Auth\Factory
	 */
	protected $auth;

	/**
	 * @var App\Cryosoft\ValueListService
	 */
	protected $value;

	/**
	 * @var App\Cryosoft\UnitsConverterService
	 */
	protected $convert;

	/**
	 * @var App\Models\CalculationParametersDef
	 */
	protected $calParametersDef;

	public function __construct(\Laravel\Lumen\Application $app) 
    {
		$this->app = $app;
		$this->auth = $app['Illuminate\\Contracts\\Auth\\Factory'];
		$this->value = $app['App\\Cryosoft\\ValueListService'];
		$this->convert = $app['App\\Cryosoft\\UnitsConverterService'];
		$this->calParametersDef = $this->getCalculationParametersDef();
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

	public function disableFields($idStudy) 
    {
		$disabledField = 0;

		$study = Study::find($idStudy);

		if ($study != null) {
			$studyOwnerUserID = $study->ID_USER;
			$userProfileID = $this->auth->user()->USERPRIO;
			$userID = $this->auth->user()->ID_USER;

			if (($userProfileID > $this->value->PROFIL_EXPERT) || ($studyOwnerUserID != $userID)) {
				$disabledField = 1;
			}
		}

		return $disabledField;
	}

	public function disableCalculate($idStudy) 
    {
		$disabledField = 0;

		$study = Study::find($idStudy);

		if ($study != null) {
			$studyOwnerUserID = $study->ID_USER;
			$userProfileID = $this->auth->user()->USERPRIO;
			$userID = $this->auth->user()->ID_USER;

			if ($studyOwnerUserID != $userID) {
				$disabledField = 1;
			}

		}

		return $disabledField;
	}

	public function getOptimErrorT() 
    {
		$mmErrorT = 0.0;
		$minMax = $this->getMinMax(1132);
		$mmErrorT = $this->convert->unitConvert($this->value->TEMPERATURE, $minMax->DEFAULT_VALUE);
		return $mmErrorT;
	}

	public function getOptimErrorH() 
    {
		$mmErrorH = 0.0;
		$minMax = $this->getMinMax(1131);
		$mmErrorH = $this->convert->unitConvert($this->value->TEMPERATURE, $minMax->DEFAULT_VALUE);
		return $mmErrorH;
	}

	public function getNbOptim() 
    {
		$mmNbOptim = 0.0;
		$minMax = $this->getMinMax(1130);
		$mmNbOptim = $this->convert->unitConvert($this->value->TEMPERATURE, $minMax->DEFAULT_VALUE);
		return $mmNbOptim;
	}

	public function getMinMax($limitItem) 
    {
		return MinMax::where('LIMIT_ITEM', $limitItem)->first();
	}

	public function getTimeStep($idStudy) 
    {
		$timeStep = -1.0;
		$bOneTimeStep = true;

		$studyEquipments = $this->getCalculableStudyEquipments($idStudy);

		foreach ($studyEquipments as $sEquipment) {
			$calParamester = CalculationParameter::where('ID_CALC_PARAMS', $sEquipment->ID_CALC_PARAMS)->first();
			if ($timeStep != $calParamester->TIME_STEP) {
				if ($timeStep == -1.0) {
					$timeStep = $calParamester->TIME_STEP;
				} else {
					$bOneTimeStep = false;
				}
			}
		}

		if ($bOneTimeStep) {
			return $this->convert->unitConvert($this->value->TIME, $timeStep, 3);
		}

		return "N.A.";
	}

	public function getPrecision($idStudy) 
    {
		$precision = -1.0;
		$bOnePrecision = true;

		$studyEquipments = $this->getCalculableStudyEquipments($idStudy);

		foreach ($studyEquipments as $sEquipment) {
			$calParamester = CalculationParameter::where('ID_CALC_PARAMS', $sEquipment->ID_CALC_PARAMS)->first();

			if ($precision != $calParamester->PRECISION_REQUEST) {
				if ($precision == -1.0) {
					$precision = $calParamester->PRECISION_REQUEST;
				} else {
					$bOnePrecision = false;
				}
			}
		}

		if ($bOnePrecision) {
			return $this->convert->unitConvert($this->value->TIME, $precision, 3);
		}

		return "N.A.";
	}

	public function getStorageStep() 
    {
		$lfStep = 0.0;

		if ($this->calParametersDef != null) {
			$lfStep = $this->calParametersDef->STORAGE_STEP_DEF * $this->calParametersDef->TIME_STEP_DEF;
		}

		return $this->convert->unitConvert($this->value->TIME, $lfStep, 1);
	}

	public function getCalculableStudyEquipments($idStudy) 
    {
		$studyEquipments = StudyEquipment::where(
			[['ID_STUDY', '=', $idStudy], ['RUN_CALCULATE', '=', 1], ['BRAIN_TYPE', '=', 0]])->get();
		return $studyEquipments;
	}

	public function getCalculationParametersDef() 
    {
		$calParametersDef = CalculationParametersDef::find($this->auth->user()->ID_USER);

		return $calParametersDef;
	}

	public function getVradioOn() 
    {
		$etat = 0;

		if ($this->calParametersDef->VERT_SCAN_DEF) {
			$etat = 1;
		}
		return $etat;
	}

	public function getVradioOff() 
    {
		$etat = 0;

		if (!$this->calParametersDef->VERT_SCAN_DEF) {
			$etat = 1;
		}
		return $etat;
	}

	public function getHradioOn() 
    {
		$etat = 0;
		if ($this->calParametersDef->HORIZ_SCAN_DEF) {
			$etat = 1;
		}
		return $etat;
	}

	public function getHradioOff() 
    {
		$etat = 0;
		if (!$this->calParametersDef->HORIZ_SCAN_DEF) {
			$etat = 1;
		}
		return $etat;
	}

	public function getMaxIter() 
    {
		return $this->convert->convertCalculator($this->calParametersDef->MAX_IT_NB_DEF, 1.0, 0.0, 0);
	}

	public function getRelaxCoef() 
    {
		return $this->convert->convertCalculator($this->calParametersDef->RELAX_COEFF_DEF, 1.0, 0.0);
	}

	public function getTempPtSurf() 
    {
		return $this->convert->unitConvert($this->value->TEMPERATURE, $this->calParametersDef->STOP_TOP_SURF_DEF, 2);
	}

	public function getTempPtIn() 
    {
		return $this->convert->unitConvert($this->value->TEMPERATURE, $this->calParametersDef->STOP_INT_DEF, 2);
	}

	public function getTempPtBot() 
    {
		return $this->convert->unitConvert($this->value->TEMPERATURE, $this->calParametersDef->STOP_BOTTOM_SURF_DEF, 2);
	}

	public function getTempPtAvg() 
    {
		return $this->convert->unitConvert($this->value->TEMPERATURE, $this->calParametersDef->STOP_AVG_DEF, 2);
	}

    public function getOption($idStudy, $key, $axe)
    {
        $meshAxis = 0;
        switch ($key) {
            case "X":
                $meshAxis = 1;
                break;

            case "Y":
                $meshAxis = 2;
                break;

            case "Z":
                $meshAxis = 3;
                break;
        }
    
        // $lint = DB::table('MESH_POSITION')->select('MESH_AXIS_POS')
        //         ->join('PRODUCT_ELMT', 'MESH_POSITION.ID_PRODUCT_ELMT', '=', 'PRODUCT_ELMT.ID_PRODUCT_ELMT')
        //         ->join('PRODUCT', 'PRODUCT_ELMT.ID_PROD', '=', 'PRODUCT.ID_PROD')
        //         ->where('PRODUCT.ID_STUDY', '=', $idStudy)
        //         ->where('MESH_POSITION.MESH_AXIS_POS', '=', $meshAxis)
        //         ->orderBy("MESH_POSITION.MESH_AXIS_POS", 'desc')->get();
        $product = Product::where('ID_STUDY', $idStudy)->first();
        $productElmt = null;
        $meshPosition = null;

        if ($product != null) {
            $idProd = $product->ID_PROD;
            $productElmt = ProductElmt::where('ID_PROD', $idProd)->first();
            if ($productElmt != null) {
                $idProductElmt = $productElmt->ID_PRODUCT_ELMT;
                $meshPosition = MeshPosition::select('MESH_AXIS_POS')
                    ->where('MESH_AXIS', '=', $meshAxis)
                    ->where('ID_PRODUCT_ELMT', '=', $idProductElmt)
                    ->orderBy("MESH_AXIS_POS", "ASC")->distinct()->get();
            }
        }

        $arrLint = array();
        $item = array();

        if (!empty($meshPosition)) {
            foreach ($meshPosition as $mesh) {
                $item["selected"] = ($this->getCoordinate($idStudy, $key, $axe) == $this->convert->meshes($mesh->MESH_AXIS_POS, $this->value->MESH_CUT)) ? true : false;
                $item["value"] = $this->convert->meshes($mesh->MESH_AXIS_POS, $this->value->MESH_CUT);
                $item["label"] = $mesh->MESH_AXIS_POS;
                array_push($arrLint, $item);
            }
        }

        return $arrLint;
    }

    public function getCoordinate($idStudy, $key, $axe)
    {
        $val = 0.0;
        $tempRecordsPt = TempRecordPts::where('ID_STUDY', $idStudy)->first();
        
        if ($key == "X" && $axe == "INT") {
            $val = ($tempRecordsPt != null) ? $tempRecordsPt->AXIS1_PT_INT_PT : 0.0;
        } else if ($key == "Y" && $axe == "INT") {
            $val = ($tempRecordsPt != null) ? $tempRecordsPt->AXIS2_PT_INT_PT : 0.0;
        } else if ($key == "Z" && $axe == "INT") {
            $val = ($tempRecordsPt != null) ? $tempRecordsPt->AXIS3_PT_INT_PT : 0.0;
        } else if ($key == "X" && $axe == "BOT") {
            $val = ($tempRecordsPt != null) ? $tempRecordsPt->AXIS1_PT_BOT_SURF : 0.0;
        } else if ($key == "Y" && $axe == "BOT") {
            $val = ($tempRecordsPt != null) ? $tempRecordsPt->AXIS2_PT_BOT_SURF : 0.0;
        } else if ($key == "Z" && $axe == "BOT") {
            $val = ($tempRecordsPt != null) ? $tempRecordsPt->AXIS3_PT_BOT_SURF : 0.0;
        } else if ($key == "X" && $axe == "TOP") {
            $val = ($tempRecordsPt != null) ? $tempRecordsPt->AXIS1_PT_TOP_SURF : 0.0;
        } else if ($key == "Y" && $axe == "TOP") {
            $val = ($tempRecordsPt != null) ? $tempRecordsPt->AXIS2_PT_TOP_SURF : 0.0;
        } else if ($key == "Z" && $axe == "TOP") {
            $val = ($tempRecordsPt != null) ? $tempRecordsPt->AXIS3_PT_TOP_SURF : 0.0;
        }

        return $this->convert->meshes($val, $this->value->MESH_CUT);
    }

    public function isStudyHasChilds($idStudy)
    {
    	$bret = false;
    	$study = Study::find($idStudy);
    	if ($study != null) {
    		if ($study->CHAINING_CONTROLS == 1 && $study->HAS_CHILD == 1) {
	    		$bret = true;
	    	}
    	}
    	return $bret;
    }

    public function setChildsStudiesToRecalculate($idStudy, $idStudyEquipment)
    {
    	if ($this->isStudyHasChilds($idStudy)) {
    		$studies = Study::where('PARENT_ID', '=', $idStudy)->get();
    		if (count($studies) > 0) {
    			for ($i = 0; $i < count($studies) ; $i++) { 
    				if (($idStudyEquipment == -1) || ($idStudyEquipment == $studies[$i]->PARENT_STUD_EQP_ID)) {
    					$studies[$i]->TO_RECALCULATE = 1;
    					$studies[$i]->save();
    				}
    			}
    		}
    	}
    	return 0;
    }
}