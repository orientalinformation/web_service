<?php

namespace App\Http\Controllers\Api1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Contracts\Auth\Factory as Auth;
use App\Models\LayoutGeneration;
use App\Models\RecordPosition;
use App\Cryosoft\EquipmentsService;
use App\Cryosoft\UnitsConverterService;

class StudyEquipments extends Controller
{

    /**
     * @var Illuminate\Http\Request
     */
    protected $request;

    /**
     * @var Illuminate\Contracts\Auth\Factory
     */
    protected $auth;


    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(Request $request, Auth $auth, EquipmentsService $equip, UnitsConverterService $unit)
    {
        $this->request = $request;
        $this->auth = $auth;
        $this->equip = $equip;
        $this->unit = $unit;
    }

    public function getStudyEquipmentById($id)
    {
        $studyEquipment = \App\Models\StudyEquipment::find($id);
        return $studyEquipment;
    }

    public function getstudyEquipmentProductChart($idStudy)
    {
        $studyEquipments = \App\Models\StudyEquipment::where("ID_STUDY", $idStudy)->orderBy("ID_STUDY_EQUIPMENTS", "ASC")->get();
        $returnStudyEquipments = [];

        foreach ($studyEquipments as $studyEquipment) {
            if ($studyEquipment->BRAIN_TYPE == 4) {
                $equip = [
                    'ID_STUDY_EQUIPMENTS' => $studyEquipment->ID_STUDY_EQUIPMENTS,
                    'EQUIP_NAME' => $studyEquipment->EQUIP_NAME,
                    'ID_EQUIP' => $studyEquipment->ID_EQUIP,
                    'EQP_LENGTH' => $studyEquipment->EQP_LENGTH,
                    'EQP_WIDTH' => $studyEquipment->EQP_WIDTH,
                    'EQUIP_VERSION' => $studyEquipment->EQUIP_VERSION,
                ];
                $layoutGen = LayoutGeneration::where('ID_STUDY_EQUIPMENTS', $studyEquipment->ID_STUDY_EQUIPMENTS)->first();
                if (!$layoutGen) continue;

                $equip['ORIENTATION'] = $layoutGen->PROD_POSITION;
                $equip['displayName'] = $this->equip->getResultsEquipName($studyEquipment->ID_STUDY_EQUIPMENTS);
                
                array_push($returnStudyEquipments, $equip);
            }
        }

        return $returnStudyEquipments;
    }

    public function getstudyEquipmentByStudyId($idStudy)
    {
        $studyEquipments = \App\Models\StudyEquipment::where("ID_STUDY", $idStudy)->orderBy("ID_STUDY_EQUIPMENTS", "ASC")->get();
        $returnStudyEquipments = [];

        foreach ($studyEquipments as $studyEquipment) {
            $equip = [
                'ID_STUDY_EQUIPMENTS' => $studyEquipment->ID_STUDY_EQUIPMENTS,
                'EQUIP_NAME' => $studyEquipment->EQUIP_NAME,
                'ID_EQUIP' => $studyEquipment->ID_EQUIP,
                'EQP_LENGTH' => $studyEquipment->EQP_LENGTH,
                'EQP_WIDTH' => $studyEquipment->EQP_WIDTH,
                'EQUIP_VERSION' => $studyEquipment->EQUIP_VERSION,
            ];
            $equip['displayName'] = $this->equip->getSpecificEquipName($studyEquipment->ID_STUDY_EQUIPMENTS);
            
            array_push($returnStudyEquipments, $equip);
        }

        return $returnStudyEquipments;
    }

    public function getRecordPosition($id)
    {
        $recordPosition = RecordPosition::where('ID_STUDY_EQUIPMENTS', $id)->orderBy('RECORD_TIME', 'ASC')->get();

        $result = [];
        if (count($recordPosition)) {
            foreach ($recordPosition as $key => $value) {
                $result[] = $value;
                $result[$key]['RECORD_TIME'] = $this->unit->time($result[$key]['RECORD_TIME']);
            }
        }

        return $recordPosition;
    }
}
