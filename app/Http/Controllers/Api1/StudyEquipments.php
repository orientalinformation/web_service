<?php

namespace App\Http\Controllers\Api1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Contracts\Auth\Factory as Auth;
use App\Models\LayoutGeneration;
use App\Models\RecordPosition;

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
    public function __construct(Request $request, Auth $auth)
    {
        $this->request = $request;
        $this->auth = $auth;
    }

    public function getStudyEquipmentById($id)
    {
        $studyEquipment = \App\Models\StudyEquipment::find($id);
        return $studyEquipment;
    }

    public function getstudyEquipmentProductChart($idStudy)
    {
        $studyEquipments = \App\Models\StudyEquipment::where("ID_STUDY", $idStudy)->orderBy("ID_STUDY_EQUIPMENTS", "ASC")->get();
        $result = array();
        if (count($studyEquipments) > 0) {
            $i = 0;
            foreach ($studyEquipments as $row) {
                $layoutGen = LayoutGeneration::where('ID_STUDY_EQUIPMENTS', $row->ID_STUDY_EQUIPMENTS)->first();
                if ($row->BRAIN_TYPE == 4) {
                    $result[] = $row;
                    $result[$i]['ORIENTATION'] = $layoutGen->PROD_POSITION;
                }
                $i++;
            }
        }
        return $result;
    }

    public function getRecordPosition($id)
    {
        $recordPosition = RecordPosition::where('ID_STUDY_EQUIPMENTS', $id)->orderBy('RECORD_TIME', 'ASC')->get();
        return $recordPosition;
    }
}
