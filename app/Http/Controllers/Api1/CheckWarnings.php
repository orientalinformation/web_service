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
use App\Models\Equipment;
use App\Models\Study;
use Illuminate\Http\Request;
use Illuminate\Contracts\Auth\Factory as Auth;
use App\Models\StudyEquipment;
use App\Cryosoft\EquipmentsService;

class CheckWarnings extends Controller 
{
    protected $request;
    protected $auth;
    protected $equip;
    protected $kernel;

    public function __construct(\Laravel\Lumen\Application $app)
    {
        $this->app = $app;
        $this->auth = $app['Illuminate\\Contracts\\Auth\\Factory'];
        $this->request = $app['Illuminate\\Http\\Request'];
        $this->equip = $app['App\\Cryosoft\\EquipmentsService'];
        $this->kernel = $app['App\\Kernel\\KernelService'];
    }

    public function checkWarningEquipment()
    {
        $input = $this->request->all();

        $message = 1;
        $check = false;
        if (isset($input['idEquip'])) $idEquip = $input['idEquip'];
        if (isset($input['idStudy'])) $idStudy = $input['idStudy'];

        $equipment = Equipment::find($idEquip);
        if ($equipment) {
            if ($this->equip->getCapability($equipment->CAPABILITIES, 16384)) {
                //Air Blast Freezer 35W - (4.00x5.00) / Active show popup
            }

            if ($equipment->STD == 0) {
                $check = true;
                $message = 'This is a generated equipment. The setpoint is fixed to value used during generation.';

                if ($this->equip->getCapability($equipment->CAPABILITIES, 65536)) {
                    $check = true;
                    $message = 'This is a generated equipment. The generation setpoint is used for first calculations. You can adjust the setpoint by hand.';
                    return [
                        'Message' => $message,
                    ];
                }
            }

            if ((!$this->equip->getCapability($equipment->CAPABILITIES, 8)) || 
                (($this->equip->getCapability($equipment->CAPABILITIES, 1)) && 
                (!$this->equip->getCapability($equipment->CAPABILITIES, 524288)) && 
                ($this->equip->getCapability($equipment->CAPABILITIES, 2)) && 
                (!$this->equip->getCapability($equipment->CAPABILITIES, 131072)) &&
                (!$this->equip->getCapability($equipment->CAPABILITIES, 262144)))) {
                $check = true;
                $message = 'This equipment does not allow to use the assistant of calculation of the couple
                            "dwelling time/temperature setpoint"<br>. 
                            Displayed values are default values of couple "dwelling time/temperature setpoint"';
                return [
                    'Message' => $message,
                ];
            }
        }
        
        if ($check) {
            return [
                'Message' => $message,
            ];
        }
        return $message;
    }

    public function checkPhamCast()
    {
        $input = $this->request->all();

        $message = 1;
        $check = $doTR = false;
        $errorCode = null;
        if (isset($input['idEquip'])) $idEquip = $input['idEquip'];
        if (isset($input['idStudy'])) $idStudy = $input['idStudy'];
        if (isset($input['idStudyEquipment'])) $idStudyEquipment = $input['idStudyEquipment'];

        $errorCode = $this->runLayoutCalculator($idStudy, $idStudyEquipment, 1);
        if ($errorCode != 0) {
            $message = 'Error with the LayoutCalculator.';
            $check = true;
            return [
                'Message' => $message,
            ];
        }

        $equipment = Equipment::find($idEquip);

        if ($equipment) {
            if ($this->equip->getCapability($equipment->CAPABILITIES, 2) && 
                $this->equip->getCapability($equipment->CAPABILITIES, 131072)) {

                $errorCode = $this->runLayoutCalculator($idStudy, $idStudyEquipment, 2);
                if ($errorCode != 0) {
                    $message = 'Error with the LayoutCalculator.';
                    $check = true;
                    return [
                        'Message' => $message,
                    ];
                }
            }

            if ($this->equip->getCapability($equipment->CAPABILITIES, 1) &&
                $this->equip->getCapability($equipment->CAPABILITIES, 524288) && 
                $this->equip->getCapability($equipment->CAPABILITIES, 8)) {
                $doTR = true;

                $errorCode = $this->runPhamCastCalculator($idStudy, $idStudyEquipment, $doTR);
                if ($errorCode != 0) {
                    $studyEquipment = StudyEquipment::find($idStudyEquipment);
                    $message = '<span style="color:red">Error with the PhamCast ' . 
                    $studyEquipment->EQUIP_STATUS . "<br>\n\t" .

                    'Dwelling time or regulation temperature reached a limit during calculation.'. "\n" .'You can leave calculation run with these values or change the pre-calculated values by clicking on the number located on the left of the equipment name.'."\n".
 
                    '<br>Automatic calculation of setpoint in relation with the dwelling time has aborted. Setpoint value has been limited to its limit. </span>';
                    $check = true;
                    return [
                        'Message' => $message,
                    ];
                }
            }

            if ((!$doTR) && 
                $this->equip->getCapability($equipment->CAPABILITIES, 2) && 
                $this->equip->getCapability($equipment->CAPABILITIES, 262144) &&
                $this->equip->getCapability($equipment->CAPABILITIES, 8)) {

                $errorCode = $this->runPhamCastCalculator($idStudy, $idStudyEquipment, $doTR);
                if ($errorCode != 0) {
                    $studyEquipment = StudyEquipment::find($idStudyEquipment);
                    $message = '<span style="color:red">Error with the PhamCast ' . 
                    $studyEquipment->EQUIP_STATUS . "<br>\n\t" .

                    'Dwelling time or regulation temperature reached a limit during calculation.'. "\n" .'You can leave calculation run with these values or change the pre-calculated values by clicking on the number located on the left of the equipment name.'."\n".
 
                    '<br>Automatic calculation of setpoint in relation with the dwelling time has aborted. Setpoint value has been limited to its limit. </span>';
                    $check = true;
                    return [
                        'Message' => $message,
                    ];
                }
            }

            $errorCode = $this->runKernelToolCalculator($idStudy, $idStudyEquipment);
            if ($errorCode != 0) {
                $message = 'The calculation of the exhaust gas temperature failed.';
                $check = true;
                return [
                    'Message' => $message,
                ];
            }
        }

        if ($check) {
            return [
                'Message' => $message,
            ];
        }
        return $message;
    }

    private function runPhamCastCalculator($idStudy, $idStudyEquipment, $doTR) 
    {
        $study = Study::find($idStudy);

        $conf = $this->kernel->getConfig($this->auth->user()->ID_USER, $idStudy, $idStudyEquipment, 1, 1, 'c:\\temp\\'.$study->STUDY_NAME.'\\Phamcast_'.$idStudy.'_'.$idStudyEquipment.'_'.$doTR.'.txt');
        return $this->kernel->getKernelObject('PhamCastCalculator')->PCCCalculation($conf, !$doTR);
    }

    private function runKernelToolCalculator($idStudy, $idStudyEquipment)
    {
        $study = Study::find($idStudy);

        $conf = $this->kernel->getConfig($this->auth->user()->ID_USER, $idStudy, $idStudyEquipment, 1, 1, 'c:\\temp\\'.$study->STUDY_NAME.'\\ToolCalculator_'.$idStudy.'_'.$idStudyEquipment.'.txt');
        return $this->kernel->getKernelObject('KernelToolCalculator')->KTCalculator($conf, 1);
    }

    private function runLayoutCalculator($idStudy, $idStudyEquipment, $number)
    {
        $study = Study::find($idStudy);

        $conf = $this->kernel->getConfig($this->auth->user()->ID_USER, $idStudy, $idStudyEquipment, 1, 1, 'c:\\temp\\'.$study->STUDY_NAME.'\\layout-trace_'.$idStudy.'_'.$idStudyEquipment.'_'.$number.'.txt');
        return $this->kernel->getKernelObject('LayoutCalculator')->LCCalculation($conf, $number);
    }
}