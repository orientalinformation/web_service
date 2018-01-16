<?php

namespace App\Cryosoft;

use App\Cryosoft\ValueListService;
use App\Cryosoft\UnitsConverterService;
use App\Models\Unit;
use App\Models\StudyEquipment;
use App\Models\Study;
use App\Models\StudEqpPrm;
use App\Models\MinMax;
use App\Models\Equipment;


class EquipmentsService
{
    public function __construct(\Laravel\Lumen\Application $app)
    {
        $this->app = $app;
        $this->value = $app['App\\Cryosoft\\ValueListService'];
        $this->unit = $app['App\\Cryosoft\\UnitsConverterService'];
    }

    public function getCapability($capabilities, $capMask)
    {
        if (($capabilities & $capMask) != 0) {
            return true;
        } else {
            return false;
        }
    }


    public function getSpecificEquipName($idStudyEquipment) 
    {
        $sEquipName = "";
        $studyEquipment = StudyEquipment::where("ID_STUDY_EQUIPMENTS", $idStudyEquipment)->first();
        $capabilitie = $studyEquipment->CAPABILITIES;

         if (($studyEquipment->STD == 1) && (!($this->getCapability($capabilitie , 32768))) && (!($this->getCapability($capabilitie , 1048576)))) {
            $seriesName = $studyEquipment->SERIES_NAME;
            $equipParameter = $studyEquipment->EQP_LENGTH + $studyEquipment->NB_MODUL * $studyEquipment->MODUL_LENGTH;
            $equipParameterUnit = $this->unit->unitConvert($this->value->EQUIP_DIMENSION, $equipParameter);
            $eqpWidthUnit = $this->unit->unitConvert($this->value->EQUIP_DIMENSION, $studyEquipment->EQP_WIDTH);
            $equipVestion = $studyEquipment->EQUIP_VERSION;

            $sEquipName = $seriesName . " - ". $equipParameterUnit." x ".$eqpWidthUnit." (v".$equipVestion.")";
         } else if (($this->getCapability($capabilitie , 1048576)) && ($studyEquipment->EQP_LENGTH != -1.0) && ($studyEquipment->EQP_WIDTH != -1.0)) {
            $stdEqpLength = $this->unit->unitConvert($this->value->EQUIP_DIMENSION, $studyEquipment->EQP_LENGTH);
            $stdeqpWidth = $this->unit->unitConvert($this->value->EQUIP_DIMENSION, $studyEquipment->EQP_WIDTH);
            $sEquipName = $studyEquipment->EQUIP_NAME . " - " . $stdEqpLength . "x" . $sEquipName;
        } else {
            $sEquipName = $studyEquipment->EQUIP_NAME;
        }

        
        return $sEquipName;
    }

    public function getResultsEquipName($idStudyEquipment) {
        $sEquipName = "";
        $studyEquipment = StudyEquipment::where("ID_STUDY_EQUIPMENTS", $idStudyEquipment)->first();
        $capabilitie = $studyEquipment->CAPABILITIES;

        if (($studyEquipment->STD == 1) && (!($this->getCapability($capabilitie , 32768))) && (!($this->getCapability($capabilitie , 1048576)))) {
            $seriesName = $studyEquipment->SERIES_NAME;
            $equipParameter = $studyEquipment->EQP_LENGTH + $studyEquipment->NB_MODUL * $studyEquipment->MODUL_LENGTH;
            $equipParameterUnit = $this->unit->unitConvert($this->value->EQUIP_DIMENSION, $equipParameter);
            $eqpWidthUnit = $this->unit->unitConvert($this->value->EQUIP_DIMENSION, $studyEquipment->EQP_WIDTH);
            $equipVestion = $studyEquipment->EQUIP_VERSION;

            $sEquipName = $seriesName . " - ". $equipParameterUnit." x ".$eqpWidthUnit." (v".$equipVestion.")";

        } else if (($this->getCapability($capabilitie , 1048576)) && ($studyEquipment->EQP_LENGTH != -1.0) && ($equip->EQP_WIDTH != -1.0)) {
            $sEquipName = $studyEquipment->EQUIP_NAME . " - " . $this->getSpecificEquipSize($idStudyEquipment);
        } else {
            $sEquipName = $studyEquipment->EQUIP_NAME;
        }

        return $sEquipName;
    }

    public function getSpecificEquipSize($idStudyEquipment) {
        $sEquipName = "";
        $studyEquipment = StudyEquipment::where("ID_STUDY_EQUIPMENTS", $idStudyEquipment)->first();
        $capabilitie = $studyEquipment->CAPABILITIES;

        if (($this->getCapability($capabilitie , 1048576)) && ($studyEquipment->EQP_LENGTH != -1.0) && ($studyEquipment->EQP_WIDTH != -1.0)) {

            $stdEqpLength = $this->unit->unitConvert($this->value->EQUIP_DIMENSION, $studyEquipment->EQP_LENGTH);
            $stdeqpWidth = $this->unit->unitConvert($this->value->EQUIP_DIMENSION, $studyEquipment->EQP_WIDTH);

            $sEquipName = "(" . $stdEqpLength . "x" . $stdeqpWidth + ")";
        }

        return $sEquipName;
    }
    
    public function initEnergyDef($idStudy)
    {
        $energyDef = 0;

        $studyEquipments = StudyEquipment::where("ID_STUDY", $idStudy)->orderBy("ID_STUDY_EQUIPMENTS", "ASC")->get();
        foreach($studyEquipments as $row){
            $ener =  $row->ID_COOLING_FAMILY;
            if (($energyDef == 0) && (($ener == 3) || ($ener == 2))) {
                $energyDef = $ener;
            }
        }
        
        return $energyDef;
    }

    public function getStudEqpPrm($idStudyEquipment, $dataType)
    {
        return StudEqpPrm::where("ID_STUDY_EQUIPMENTS", $idStudyEquipment)->where("VALUE_TYPE", ">=", $dataType)->where("VALUE_TYPE", "<", $dataType + 100)->orderBy("VALUE_TYPE", "ASC")->get();
    }

    public function isValidTemperature($idStudyEquipment, $selectTr)
    {
        $bisValid = true;
        $studyEquipment = StudyEquipment::where("ID_STUDY_EQUIPMENTS", $idStudyEquipment)->first();
        $studEqpPrm = $this->getStudEqpPrm($idStudyEquipment, 300);
        $itemTr = $studyEquipment->ITEM_TR;
        $minMax = MinMax::where("LIMIT_ITEM", $itemTr)->first();

        if (count($studEqpPrm) == 1) {
            $lfTr = $studEqpPrm[0]->VALUE;
            if ($selectTr == 2) {           
                if ($lfTr <= $minMax->LIMIT_MIN) {
                  $bisValid = false;
                }
            } else if ($selectTr == 0) {
                if ($lfTr >= $minMax->LIMIT_MAX) {
                  $bisValid = false;
                }
            }
        } else {
          $bisValid = false;
        }

        return $bisValid;       
    }

    public function getStd($idStudyEquipment)
    {
        $std = null;
        $idEquipment = null;
        $studyEquipment = StudyEquipment::find($idStudyEquipment);
        if ($studyEquipment) $idEquipment = $studyEquipment->ID_EQUIP;

        $equipment = Equipment::find($idEquipment);
        if ($equipment) $std = $equipment->STD;

        return $std;
    }
    
}
