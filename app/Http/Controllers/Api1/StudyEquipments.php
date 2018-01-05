<?php

namespace App\Http\Controllers\Api1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Contracts\Auth\Factory as Auth;

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
            foreach ($studyEquipments as $row) {
                if ($row->BRAIN_TYPE == 4) {
                    $result[] = $row;
                }
            }
        }
        return $result;
    }
}
