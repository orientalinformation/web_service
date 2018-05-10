<?php

namespace App\Http\Controllers\Api1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Contracts\Auth\Factory as Auth;
use App\Models\StudyEquipment;
use App\Models\LayoutGeneration;
use App\Models\RecordPosition;
use App\Models\MinMax;
use App\Cryosoft\EquipmentsService;
use App\Cryosoft\UnitsConverterService;
use App\Cryosoft\BrainCalculateService;
use App\Cryosoft\StudyEquipmentService;


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
     * @var App\Cryosoft\StudyEquipmentService
     */
    protected $stdeqp;


    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(Request $request, Auth $auth, 
        EquipmentsService $equip, UnitsConverterService $unit, 
        BrainCalculateService $brain,
        StudyEquipmentService $stdeqp
    )
    {
        $this->request = $request;
        $this->auth = $auth;
        $this->equip = $equip;
        $this->unit = $unit;
        $this->brain = $brain;
        $this->stdeqp = $stdeqp;
    }

    public function getStudyEquipmentById($id)
    {
        $studyEquipment = \App\Models\StudyEquipment::find($id);
        $equip = $this->stdeqp->getDisplayStudyEquipment($studyEquipment);
        $equip['displayName'] = $this->equip->getResultsEquipName($studyEquipment->ID_STUDY_EQUIPMENTS);
        return $equip;
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

    public function saveEquipSizing($id)
    {
        $input = $this->request->all();
        $width = $input['width'];
        $length = $input['length'];

        $studyEquipment = StudyEquipment::where('ID_STUDY_EQUIPMENTS', $id)->first();
        $studyEquipment->STDEQP_WIDTH = $this->unit->equipDimension($width, ['save' => true]);
        $studyEquipment->STDEQP_LENGTH = $this->unit->equipDimension($length, ['save' => true]);
        $studyEquipment->save();

        $layoutGeneration = $studyEquipment->layoutGenerations->first();
        $layoutResult = $studyEquipment->layoutResults->first();
        $layoutGeneration->LENGTH_INTERVAL = -2;
        $layoutGeneration->WIDTH_INTERVAL = -2;
        $layoutGeneration->save();

        $layoutResult->LEFT_RIGHT_INTERVAL = 0;
        $layoutResult->NUMBER_IN_WIDTH = 0;
        $layoutResult->NUMBER_PER_M = 0;
        $layoutResult->save();

        //runSizingCalculator
        $this->stdeqp->runSizingCalculator($studyEquipment->ID_STUDY, $id);       
        return 1;
    }

    public function getOperatingSetting($id)
    {
        $studyEquipment = StudyEquipment::where('ID_STUDY_EQUIPMENTS', $id)->first();

        $listTr = $this->brain->getListTr($id);
        $trResult = [];
        foreach ($listTr as $tr) {
            $trResult[] = $this->unit->controlTemperature($tr);
        }
        $studyEquipment->tr = $trResult;
        $studyEquipment->ts = $this->brain->getListTs($id);
        $studyEquipment->vc = $this->brain->getVc($id);
        $studyEquipment->alpha = $this->stdeqp->loadAlphaCoef($studyEquipment);
        $studyEquipment->TExt = $this->unit->exhaustTemperature($this->brain->getTExt($id));
        $calculationParameter = $studyEquipment->calculationParameters->first();
        $calculationParameter->STUDY_ALPHA_TOP_FIXED = ($calculationParameter->STUDY_ALPHA_TOP_FIXED == 1) ? true : false;
        $calculationParameter->STUDY_ALPHA_BOTTOM_FIXED = ($calculationParameter->STUDY_ALPHA_BOTTOM_FIXED == 1) ? true : false;
        $calculationParameter->STUDY_ALPHA_LEFT_FIXED = ($calculationParameter->STUDY_ALPHA_LEFT_FIXED == 1) ? true : false;
        $calculationParameter->STUDY_ALPHA_RIGHT_FIXED = ($calculationParameter->STUDY_ALPHA_RIGHT_FIXED == 1) ? true : false;
        $calculationParameter->STUDY_ALPHA_FRONT_FIXED = ($calculationParameter->STUDY_ALPHA_FRONT_FIXED == 1) ? true : false;
        $calculationParameter->STUDY_ALPHA_REAR_FIXED = ($calculationParameter->STUDY_ALPHA_REAR_FIXED == 1) ? true : false;

        $studyEquipment->ldSetpointmax = (count($studyEquipment->ts) > count($studyEquipment->tr)) ? (count($studyEquipment->ts) > count($studyEquipment->vc)) ? count($studyEquipment->ts) : count($studyEquipment->vc) : (count($studyEquipment->tr) > count($studyEquipment->vc)) ? count($studyEquipment->tr) : count($studyEquipment->vc);

        $mmTr = MinMax::where("LIMIT_ITEM", $studyEquipment->ITEM_TR)->first();
        $studyEquipment->minMaxTr = [
            'LIMIT_MIN' => $this->unit->controlTemperature($mmTr->LIMIT_MIN, ['format' => false]),
            'LIMIT_MAX' => $this->unit->controlTemperature($mmTr->LIMIT_MAX, ['format' => false]),
        ];

        $mm = MinMax::where("LIMIT_ITEM", $studyEquipment->ITEM_TS)->first();
        $studyEquipment->minMaxTs = [
            'LIMIT_MIN' => $this->unit->time($mm->LIMIT_MIN, ['format' => false]),
            'LIMIT_MAX' => $this->unit->time($mm->LIMIT_MAX, ['format' => false]),
        ];

        $mm = MinMax::where("LIMIT_ITEM", 1037)->first();
        $studyEquipment->minMaxVc = [
            'LIMIT_MIN' => $this->unit->convectionSpeed($mm->LIMIT_MIN, ['format' => false]),
            'LIMIT_MAX' => $this->unit->convectionSpeed($mm->LIMIT_MAX, ['format' => false]),
        ];

        $mm = MinMax::where("LIMIT_ITEM", 1018)->first();
        $studyEquipment->minMaxAlpha = [
            'LIMIT_MIN' => $this->unit->convectionCoeff($mm->LIMIT_MIN, ['format' => false]),
            'LIMIT_MAX' => $this->unit->convectionCoeff($mm->LIMIT_MAX, ['format' => false]),
        ];

        $studyEquipment->minMaxText = [
            'LIMIT_MIN' => $this->unit->exhaustTemperature($mmTr->LIMIT_MIN, ['format' => false]),
            'LIMIT_MAX' => 0,
        ];

        $tempExts = $this->stdeqp->loadExhaustGasTemperature($studyEquipment);
        $resultTempExts = [];
        if (count($tempExts) > 0) {
            foreach ($tempExts as $tempExt) {
                if ($tempExt->TR <= $studyEquipment->minMaxTr['LIMIT_MAX'] && $tempExt->TR >= $studyEquipment->minMaxTr['LIMIT_MIN']) {
                    $item['TR'] = $this->unit->controlTemperature($tempExt->TR, ['format' => false]);
                    $item['T_EXT'] = $this->unit->controlTemperature($tempExt->T_EXT, ['format' => false]);
                    $resultTempExts[] = $item;
                }
            }
        }

        return compact('resultTempExts', 'studyEquipment');
    }

    public function saveEquipmentData($id)
    {
        $studyEquipment = StudyEquipment::where('ID_STUDY_EQUIPMENTS', $id)->first();
        $input = $this->request->all();
        $studyEquipment->ts = $input['ts'];
        $studyEquipment->tr = $input['tr'];
        $studyEquipment->vc = $input['vc'];
        $studyEquipment->tExt = $input['TExt'];
        $studyEquipment->calculation_parameter = (object) $input['calculation_parameter'];
        $this->stdeqp->updateEquipmentData($studyEquipment);
        $this->stdeqp->applyStudyCleaner($studyEquipment->ID_STUDY, $id, 43);
        return 1;
    }

    public function getStudyEquipmentLayout($id) 
    {
        $stdeqp = StudyEquipment::findOrFail($id);

        return response('data:image/jpeg;base64,'.$this->stdeqp->generateLayoutPreview($stdeqp))
            ->header('Content-Type', 'text/plain');
    }
}
