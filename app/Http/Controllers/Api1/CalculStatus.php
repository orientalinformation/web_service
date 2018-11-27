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

class CalculStatus extends Controller 
{
    /**
     * @var Illuminate\Http\Request
     */
    protected $request;

    /**
     * @var Illuminate\Contracts\Auth\Factory
     */
    protected $auth;

    public function __construct(Request $request, Auth $auth)
    {
        $this->request = $request;
        $this->auth = $auth;
    }

    public function getMyStudies($idStudy)
    {
        $IS_STUDY_CURRENT = null;
        $arr = $item = array();

        $studies = Study::where('ID_USER', $this->auth->user()->ID_USER)->get();

        if (count($studies) > 0) {
            foreach ($studies as $study) {
                if ($idStudy == intval($study->ID_STUDY)) {
                    $IS_STUDY_CURRENT = 1;
                } else {
                    $IS_STUDY_CURRENT = 0;
                }

                $item['ID_STUDY'] = $study->ID_STUDY;
                $item['STUDY_NAME'] = $study->STUDY_NAME;
                $item['IS_STUDY_CURRENT'] = $IS_STUDY_CURRENT;
                $item['row_span'] = count($this->getStudyEquipmentByIdStudy($study->ID_STUDY));
                $item['studyEquipments'] = $this->getEquipmentByIdStudy($study->ID_STUDY);
                array_push($arr, $item);
            }
        }

        foreach ($arr as $key => $row) {
            $STUDY_CURRENT[$key] = $row['IS_STUDY_CURRENT'];
        }

        array_multisort(array_column($arr, 'IS_STUDY_CURRENT'), SORT_DESC, $arr); //SORT_DESC SORT_ASC

        return $arr;
    }

    private function getStudyEquipmentByIdStudy($idStudy)
    {
        $studyEquipments = StudyEquipment::where('ID_STUDY', $idStudy)->get();
        return $studyEquipments;
    }

    private function getEquipmentByIdStudy($idStudy)
    {
        $arr = $item = array();
        $studyEquipments = StudyEquipment::where('ID_STUDY', $idStudy)->get();

        if (count($studyEquipments) > 0) {
            foreach ($studyEquipments as $studyE) {
                $equipment = Equipment::find($studyE->ID_EQUIP);

                if ($equipment) {
                    $item['ID_EQUIP'] = $equipment->ID_EQUIP;
                    $item['EQUIP_NAME'] = $equipment->EQUIP_NAME;
                    $item['EQUIP_STATUS'] = floatval($studyE->EQUIP_STATUS);
                    array_push($arr, $item);
                }
            }
        }

        return $arr;
    }
}