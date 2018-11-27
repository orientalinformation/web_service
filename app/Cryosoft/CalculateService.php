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
use App\Models\Report;

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

    /**
     * @var App\Cryosoft\UnitsService
     */
    protected $units;

    /**
     * @var App\Cryosoft\EquipmentsService
     */
    protected $equipment;

    public function __construct(\Laravel\Lumen\Application $app) 
    {
        $this->app = $app;
        $this->auth = $app['Illuminate\\Contracts\\Auth\\Factory'];
        $this->value = $app['App\\Cryosoft\\ValueListService'];
        $this->convert = $app['App\\Cryosoft\\UnitsConverterService'];
        $this->calParametersDef = $this->getCalculationParametersDef();
        $this->units = $app['App\\Cryosoft\\UnitsService'];
        $this->equipment = $app['App\\Cryosoft\\EquipmentsService'];
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
        $mmErrorT = $this->units->deltaTemperature($minMax->DEFAULT_VALUE, 2, 1);
        return $mmErrorT;
    }

    public function getOptimErrorTMinMax($minMax) 
    {
        $mmErrorT = 0.0;
        if ($minMax) {
            $mmErrorT = $this->units->deltaTemperature($minMax->DEFAULT_VALUE, 2, 0);
        }

        return $mmErrorT;
    }

    public function getOptimErrorH() 
    {
        $mmErrorH = 0.0;
        $minMax = $this->getMinMax(1131);
        $uPercent = $this->units->uPercent();
        $mmErrorH =  $this->units->convertCalculator($minMax->DEFAULT_VALUE, $uPercent["coeffA"], $uPercent["coeffB"], 2, 1);
        return $mmErrorH;
    }

    public function getOptimErrorHMinMax($minMax) 
    {
        $mmErrorH = 0.0;
        $uPercent = $this->units->uPercent();

        if ($minMax) {
            $mmErrorH =  $this->units->convertCalculator($minMax->DEFAULT_VALUE, intval($uPercent["coeffA"]), intval($uPercent["coeffB"]), 2, 0);
        }

        return $mmErrorH;
    }

    public function getNbOptim() 
    {
        $mmNbOptim = 0.0;
        $minMax = $this->getMinMax(1130);
        return intval($minMax->DEFAULT_VALUE);
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
            if ($calParamester) {
                if ($timeStep != $calParamester->TIME_STEP) {
                    if ($timeStep == -1.0) {
                        $timeStep = $calParamester->TIME_STEP;
                    } else {
                        $bOneTimeStep = false;
                    }
                }
            }
        }

        if ($bOneTimeStep) {
            return $this->units->timeStep($timeStep, 3, 1);
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

            if ($calParamester) {
                if ($precision != $calParamester->PRECISION_REQUEST) {
                    if ($precision == -1.0) {
                        $precision = $calParamester->PRECISION_REQUEST;
                    } else {
                        $bOnePrecision = false;
                    }
                }
            }
        }
        
        if ($bOnePrecision) {
            return $this->units->convert($precision, 3);
        }

        return "N.A.";
    }

    public function getStorageStep() 
    {
        $lfStep = 0.0;

        if ($this->calParametersDef) {
            $lfStep = $this->calParametersDef->STORAGE_STEP_DEF * $this->calParametersDef->TIME_STEP_DEF;
        }

        return $this->units->timeStep($lfStep, 1, 1);
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

        if ($this->calParametersDef) {
            if ($this->calParametersDef->VERT_SCAN_DEF) {
                $etat = 1;
            }
        }
        return $etat;
    }

    public function getVradioOff() 
    {
        $etat = 0;

        if ($this->calParametersDef) {
            if (!$this->calParametersDef->VERT_SCAN_DEF) {
                $etat = 1;
            }
        }
        return $etat;
    }

    public function getHradioOn() 
    {
        $etat = 0;

        if ($this->calParametersDef) {
            if ($this->calParametersDef->HORIZ_SCAN_DEF) {
                $etat = 1;
            }
        }
        return $etat;
    }

    public function getHradioOff() 
    {
        $etat = 0;
        if ($this->calParametersDef) {
            if (!$this->calParametersDef->HORIZ_SCAN_DEF) {
                $etat = 1;
            }
        }
        return $etat;
    }

    public function getMaxIter() 
    {
        $result = null;
        if ($this->calParametersDef) {
            $result = $this->calParametersDef->MAX_IT_NB_DEF;
        }
        return intval($result);
    }

    public function getRelaxCoef() 
    {
        $result = null;
        if ($this->calParametersDef) {
            $result = $this->calParametersDef->RELAX_COEFF_DEF;
        }

        return $result;
    }

    public function getTempPtSurf() 
    {
        $result = null;
        if ($this->calParametersDef) {
            $result = $this->units->temperature($this->calParametersDef->STOP_TOP_SURF_DEF, 2, 1);
        }
        return $result;
    }

    public function getTempPtIn() 
    {
        $result = null;
        if ($this->calParametersDef) {
            $result = $this->units->temperature($this->calParametersDef->STOP_INT_DEF, 2, 1);
        }
        return $result;
    }

    public function getTempPtBot() 
    {
        $result = null;
        if ($this->calParametersDef) {
            $result = $this->units->temperature($this->calParametersDef->STOP_BOTTOM_SURF_DEF, 2, 1);
        }
        return $result;
    }

    public function getTempPtAvg() 
    {
        $result = null;
        if ($this->calParametersDef) {
            $result = $this->units->temperature($this->calParametersDef->STOP_AVG_DEF, 2, 1);
        }

        return $result;
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
    
        $meshPosition = MeshPosition::distinct()->select('MESH_POSITION.MESH_AXIS_POS')
        ->join('PRODUCT_ELMT', 'MESH_POSITION.ID_PRODUCT_ELMT', '=', 'PRODUCT_ELMT.ID_PRODUCT_ELMT')
        ->join('PRODUCT', 'PRODUCT_ELMT.ID_PROD' , '=', 'PRODUCT.ID_PROD')
        ->where('PRODUCT.ID_STUDY', $idStudy)
        ->where('MESH_AXIS', $meshAxis)
        ->orderBy('MESH_AXIS_POS', 'ASC')->get();

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

    public function getValueSelected($select = array())
    {
        $value = 0.0;
        if (count($select) > 0) {
            for ($i = 0; $i < count($select); $i++) { 
                if ($select[$i]['selected'] == true) {
                    $value = floatval($select[$i]['label']);
                }
            }

            if ($value == 0.0) {
                $value = floatval($select[0]['label']);
            }
        } else {
            $value = 0.0;
        }

        return $value;
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
                for ($i = 0; $i < count($studies); $i++) { 
                    if (($idStudyEquipment == -1) || ($idStudyEquipment == $studies[$i]->PARENT_STUD_EQP_ID)) {
                        $studies[$i]->TO_RECALCULATE = 1;
                        $studies[$i]->save();
                    }
                }
            }
        }
        return 0;
    }

    public function resetEquipSatus($idStudy)
    {
        $studyEquipments = StudyEquipment::where('ID_STUDY', $idStudy)->get();

        if (count($studyEquipments) > 0) {
            for ($i = 0; $i < count($studyEquipments); $i++) { 
                $studyEquipments[$i]->EQUIP_STATUS = 0;
                $studyEquipments[$i]->BRAIN_TYPE = 0;
                $studyEquipments[$i]->save();
            }
        }
    }

    public function saveTempRecordPtsToReport($idStudy)
    {
        $tempRecordsPt = TempRecordPts::where('ID_STUDY', $idStudy)->first();
        $report = Report::where('ID_STUDY', $idStudy)->first();

        if (($report != null) && ($tempRecordsPt != null)) {
            $report->POINT1_X = $tempRecordsPt->AXIS1_PT_TOP_SURF;
            $report->POINT1_Y = $tempRecordsPt->AXIS2_PT_BOT_SURF;
            $report->POINT1_Z = $tempRecordsPt->AXIS3_PT_BOT_SURF;

            $report->POINT2_X = $tempRecordsPt->AXIS1_PT_INT_PT;
            $report->POINT2_Y = $tempRecordsPt->AXIS2_PT_INT_PT;
            $report->POINT2_Z = $tempRecordsPt->AXIS3_PT_INT_PT;

            $report->POINT3_X = $tempRecordsPt->AXIS1_PT_BOT_SURF;
            $report->POINT3_Y = $tempRecordsPt->AXIS2_PT_BOT_SURF;
            $report->POINT3_Z = $tempRecordsPt->AXIS3_PT_BOT_SURF;

            $report->AXE1_X = $tempRecordsPt->AXIS1_AX_3;
            $report->AXE1_Y = $tempRecordsPt->AXIS2_AX_3;

            $report->AXE2_X = $tempRecordsPt->AXIS1_AX_2;
            $report->AXE2_Z = $tempRecordsPt->AXIS3_AX_2;

            $report->AXE3_Y = $tempRecordsPt->AXIS2_AX_1;
            $report->AXE3_Z = $tempRecordsPt->AXIS3_AX_1;

            $report->PLAN_X = $tempRecordsPt->AXIS1_PL_2_3;
            $report->PLAN_Y = $tempRecordsPt->AXIS2_PL_1_3;
            $report->PLAN_Z = $tempRecordsPt->AXIS3_PL_1_2;

            $report->CONTOUR2D_TEMP_MIN = $tempRecordsPt->CONTOUR2D_TEMP_MIN;
            $report->CONTOUR2D_TEMP_MAX = $tempRecordsPt->CONTOUR2D_TEMP_MAX;

            if (($tempRecordsPt->CONTOUR2D_TEMP_MIN) ==  ($tempRecordsPt->CONTOUR2D_TEMP_MAX)) {
                $report->CONTOUR2D_TEMP_STEP = 0;
            }
            $report->save();
        }
    }

    public function reset2DTempRecordPts($idStudy)
    {
        $tempRecordsPt = TempRecordPts::where('ID_STUDY', $idStudy)->first();
        $tempRecordsPt->CONTOUR2D_TEMP_MIN = 0.0;
        $tempRecordsPt->CONTOUR2D_TEMP_MAX = 0.0;
        $tempRecordsPt->save();

        $this->saveTempRecordPtsToReport($idStudy);
    }

    public function isThereAnEquipWithOptimEnable($idStudy)
    {
        $bret = false;
        $studyEquipments = StudyEquipment::where('ID_STUDY', $idStudy)->get();

        if (count($studyEquipments) > 0) {
            for ($i = 0; $i < count($studyEquipments); $i++) {
                $capability = $studyEquipments[$i]->CAPABILITIES;
                $equipWithSpecificSize = (($studyEquipments[$i]->STDEQP_LENGTH != -1) && ($studyEquipments[$i]->STDEQP_WIDTH != -1)) ? true : false;
                
                $bspecialEquip = ($this->equipment->getCapability($capability, 262144) && $this->equipment->getCapability($capability, 2097152) && (!$equipWithSpecificSize));
                if (($this->equipment->getCapability($capability, 64)) && (!$bspecialEquip)) {
                    $bret = true;
                    break;
                }
            }
        }
        return $bret;
    }
}