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
use App\Cryosoft\ValueListService;
use App\Models\Equipment;
use App\Models\StudEqpPrm;
use App\Models\DimaResults;
use App\Models\EconomicResults;
use App\Models\CalculationParameter;
use App\Models\LayoutResults;
use App\Models\PipeGen;

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
    protected $value;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(Request $request, Auth $auth, 
        EquipmentsService $equip, UnitsConverterService $unit, 
        BrainCalculateService $brain,
        StudyEquipmentService $stdeqp,
        ValueListService $value
    )
    {
        $this->request = $request;
        $this->auth = $auth;
        $this->equip = $equip;
        $this->unit = $unit;
        $this->brain = $brain;
        $this->stdeqp = $stdeqp;
        $this->value = $value;
    }

    public function getStudyEquipmentById($id)
    {
        $studyEquipment = StudyEquipment::find($id);
        $equip = $this->stdeqp->getDisplayStudyEquipment($studyEquipment);
        $equip['displayName'] = $this->equip->getResultsEquipName($studyEquipment->ID_STUDY_EQUIPMENTS);
        return $equip;
    }

    public function getstudyEquipmentProductChart($idStudy)
    {
        $studyEquipments = StudyEquipment::where("ID_STUDY", $idStudy)->orderBy("ID_STUDY_EQUIPMENTS", "ASC")->get();
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
        $studyEquipments = StudyEquipment::where("ID_STUDY", $idStudy)->orderBy("ID_STUDY_EQUIPMENTS", "ASC")->get();
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
        $changeTr = false;
        $listTr = $this->brain->getListTr($id);
        $trResult = [];
        foreach ($listTr as $tr) {
            $trResult[] = $this->unit->controlTemperature($tr);
        }

        $listTs = $this->brain->getListTs($id);
        $tsResult = [];
        foreach ($listTs as $ts) {
            $tsResult[] = $this->unit->time($ts);
        }

        $listVc = $this->brain->getVc($id);
        $vcResult = [];
        foreach ($listVc as $vc) {
            $vcResult[] = $this->unit->convectionSpeed($vc);
        }
        
        $studyEquipment->displayName = trim($this->equip->getResultsEquipName($studyEquipment->ID_STUDY_EQUIPMENTS));
        if ($studyEquipment->equipment->STD
        && !($studyEquipment->equipment->CAPABILITIES & CAP_DISPLAY_DB_NAME != 0)
        && !($studyEquipment->equipment->CAPABILITIES & CAP_EQUIP_SPECIFIC_SIZE != 0)) {
            $studyEquipment->displayName = $studyEquipment->EQUIP_NAME . " - "
                . number_format($studyEquipment->equipment->EQP_LENGTH + 
                    ($studyEquipment->NB_MODUL * $studyEquipment->equipment->MODUL_LENGTH), 2)
                . "x" . number_format($studyEquipment->equipment->EQP_WIDTH, 2) . " (v" . ($studyEquipment->EQUIP_VERSION) . ")"
                . ($studyEquipment->EQUIP_RELEASE == 3 ? ' / Active' : ''); // @TODO: translate
        } else if (($studyEquipment->equipment->CAPABILITIES & CAP_EQUIP_SPECIFIC_SIZE != 0)
            && ($studyEquipment->equipment->STDEQP_LENGTH != NO_SPECIFIC_SIZE)
            && ($studyEquipment->equipment->STDEQP_WIDTH != NO_SPECIFIC_SIZE)) {
            $studyEquipment->displayName = $studyEquipment->EQUIP_NAME
                . " (v" . ($studyEquipment->EQUIP_VERSION) . ")"
                . ($studyEquipment->EQUIP_RELEASE == 3 ? ' / Active' : ''); // @TODO: translate
        } else {
            $studyEquipment->displayName = $studyEquipment->EQUIP_NAME
                . ($studyEquipment->EQUIP_RELEASE == 3 ? ' / Active' : ''); // @TODO: translate
        }

        $studyEquipment->tr = $trResult;
        $studyEquipment->ts = $tsResult;
        $studyEquipment->vc = $vcResult;
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

        $layoutGen = $this->stdeqp->getStudyEquipmentLayoutGen($studyEquipment);

        $studyEquipment->layoutGen = $layoutGen;
        $layoutResults = LayoutResults::where('ID_STUDY_EQUIPMENTS', $studyEquipment->ID_STUDY_EQUIPMENTS)->first();
        if ($layoutResults) {
            $layoutResults->QUANTITY_PER_BATCH = $this->unit->mass($layoutResults->QUANTITY_PER_BATCH);
            $layoutResults->LOADING_RATE = $this->unit->toc($layoutResults->LOADING_RATE);
            $layoutResults->LEFT_RIGHT_INTERVAL = $this->unit->prodDimension($layoutResults->LEFT_RIGHT_INTERVAL);
            $layoutResults->NUMBER_PER_M = $this->unit->none($layoutResults->NUMBER_PER_M);
            $layoutResults->NUMBER_IN_WIDTH = $this->unit->none($layoutResults->NUMBER_IN_WIDTH);
        }
        
        $studyEquipment->layoutResults = $layoutResults;

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

        // add by oriental
        $equipment = Equipment::find($studyEquipment->ID_EQUIP);
        if ($equipment) {
            if (($equipment->STD == $this->value->EQUIP_NOT_STANDARD) &&
            (!$this->equip->getCapability($equipment->CAPABILITIES, $this->value->CAP_VARIABLE_TR)) &&
            (!$this->equip->getCapability($equipment->CAPABILITIES, $this->value->CAP_EQP_DEPEND_ON_TS))) {
                $changeTr = true;
            }
        }

        return compact('resultTempExts', 'studyEquipment', 'changeTr');
    }

    public function computeTrTsConfig($id)
    {
        $input = $this->request->all();
        $studyEquipment = StudyEquipment::find($id);
        $studyEquipment->ts = $input['ts'];
        $studyEquipment->tr = $input['tr'];

        $this->stdeqp->updateEquipmentData($studyEquipment);

        if ($this->equip->getCapability($studyEquipment->CAPABILITIES, 8)) {
            $this->stdeqp->startPhamCastCalculator($studyEquipment, $input['doTr']);
        }

        if ($this->equip->getCapability($studyEquipment->CAPABILITIES, 512)) {
            $this->stdeqp->startExhaustGasTemp($studyEquipment);
        }

        $listTr = $this->brain->getListTr($id);
        $trResult = [];
        foreach ($listTr as $tr) {
            $trResult[] = $this->unit->controlTemperature($tr);
        }

        $listTs = $this->brain->getListTs($id);
        $tsResult = [];
        foreach ($listTs as $ts) {
            $tsResult[] = $this->unit->time($ts);
        }

        $listVc = $this->brain->getVc($id);
        $vcResult = [];
        foreach ($listVc as $vc) {
            $vcResult[] = $this->unit->convectionSpeed($vc);
        }
        
        $studyEquipment->displayName = trim($this->equip->getResultsEquipName($studyEquipment->ID_STUDY_EQUIPMENTS));
        if ($studyEquipment->equipment->STD
            && !($studyEquipment->equipment->CAPABILITIES & CAP_DISPLAY_DB_NAME != 0)
            && !($studyEquipment->equipment->CAPABILITIES & CAP_EQUIP_SPECIFIC_SIZE != 0)) {
            $studyEquipment->displayName = $studyEquipment->EQUIP_NAME . " - "
                . number_format($studyEquipment->equipment->EQP_LENGTH + 
                    ($studyEquipment->NB_MODUL * $studyEquipment->equipment->MODUL_LENGTH), 2)
                . "x" . number_format($studyEquipment->equipment->EQP_WIDTH, 2) . " (v" . ($studyEquipment->EQUIP_VERSION) . ")"
                . ($studyEquipment->EQUIP_RELEASE == 3 ? ' / Active' : ''); // @TODO: translate
        } else if (($studyEquipment->equipment->CAPABILITIES & CAP_EQUIP_SPECIFIC_SIZE != 0)
            && ($studyEquipment->equipment->STDEQP_LENGTH != NO_SPECIFIC_SIZE)
            && ($studyEquipment->equipment->STDEQP_WIDTH != NO_SPECIFIC_SIZE)) {
            $studyEquipment->displayName = $studyEquipment->EQUIP_NAME
                . " (v" . ($studyEquipment->EQUIP_VERSION) . ")"
                . ($studyEquipment->EQUIP_RELEASE == 3 ? ' / Active' : ''); // @TODO: translate
        } else {
            $studyEquipment->displayName = $studyEquipment->EQUIP_NAME
                . ($studyEquipment->EQUIP_RELEASE == 3 ? ' / Active' : ''); // @TODO: translate
        }
        $studyEquipment->tr = $trResult;
        $studyEquipment->ts = $tsResult;
        $studyEquipment->vc = $vcResult;
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

        return $studyEquipment;
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
        $this->stdeqp->applyStudyCleaner($studyEquipment->ID_STUDY, $id, SC_CLEAN_OUTPUT_EQP_PRM);
        return 1;
    }

    public function getStudyEquipmentLayout($id) 
    {
        $stdeqp = StudyEquipment::findOrFail($id);

        return response('data:image/jpeg;base64,'.$this->stdeqp->generateLayoutPreview($stdeqp))
            ->header('Content-Type', 'text/plain');
    }

    public function addConsPieToReport($id)
    {
        $input = $this->request->all();
        $stdeqp = StudyEquipment::find($id);
        $stdeqp->ENABLE_CONS_PIE = ($input['ENABLE_CONS_PIE'] == true) ? 1 : 0;
        $stdeqp->save();
        return 1;
    }

    public function getSpecialLength($id)
    {
        $stdeqp = StudyEquipment::findOrFail($id);
        $result = [];
        $length = 0;
        for ($i = 1; $i <= $stdeqp->NB_MAX_MODUL; $i++) { 
            $length = $stdeqp->EQP_LENGTH + $i * $stdeqp->MODUL_LENGTH;
            $result[] = $this->unit->equipDimension($length) . ' (' . $this->unit->equipDimensionSymbol() . ')' . ' x ' . $this->unit->equipDimension($stdeqp->EQP_WIDTH) . ' (' . $this->unit->equipDimensionSymbol() . ')';
        }

        return $result;
    }

    public function duplicateEltNewSize($id)
    {
        $stdeqp = StudyEquipment::findOrFail($id);
        $input = $this->request->all();
        $nbModule = $input['nbModule'];
        $studyEquipmentsNew = new StudyEquipment();

        $stdeqpNew = $stdeqp->replicate();
        unset($stdeqpNew->ID_STUDY_EQUIPMENTS);
        $stdeqpNew['NB_MODUL'] = $nbModule;
        $stdeqpNew['ID_CALC_PARAMS'] = 0;
        $stdeqpNew['ID_EXH_GEN'] = 0;
        $stdeqpNew['ID_EXH_RES'] = 0;
        $stdeqpNew['ID_LAYOUT_GENERATION'] = 0;
        $stdeqpNew['ID_LAYOUT_RESULTS'] = 0;
        $stdeqpNew['ID_PIPE_GEN'] = 0;
        $stdeqpNew['ID_PIPE_RES'] = 0;
        $stdeqpNew['ID_STUD_EQUIPPROFILE'] = 0;
        $stdeqpNew['ID_ECONOMIC_RESULTS'] = 0;
        $stdeqpNew['BRAIN_SAVETODB'] = 0;
        $stdeqpNew['BRAIN_TYPE'] = 0;
        $stdeqpNew['AVERAGE_PRODUCT_ENTHALPY'] = 0.0;
        $stdeqpNew['AVERAGE_PRODUCT_TEMP'] = 0.0;
        $stdeqpNew['ENTHALPY_VARIATION'] = 0.0;
        $stdeqpNew['PRECIS'] = 0.0;

        if ($stdeqpNew->save()) {
            $calculationParameter = $stdeqp->calculationParameters->first();
            if ($calculationParameter) {
                $calculationParameterNew = new CalculationParameter();
                $calculationParameterNew = $calculationParameter->replicate();
                unset($calculationParameterNew->ID_CALC_PARAMS);
                $calculationParameterNew->ID_STUDY_EQUIPMENTS = $stdeqpNew->ID_STUDY_EQUIPMENTS;
                $calculationParameterNew->save();
                $stdeqpNew['ID_CALC_PARAMS'] = $calculationParameterNew->ID_CALC_PARAMS;
            }

            $layoutGeneration = $stdeqp->layoutGenerations->first();
            if ($layoutGeneration) {
                $layoutGenerationNew = new LayoutGeneration();
                $layoutGenerationNew = $layoutGeneration->replicate();
                unset($layoutGenerationNew->ID_LAYOUT_GENERATION);
                $layoutGenerationNew->ID_STUDY_EQUIPMENTS = $stdeqpNew->ID_STUDY_EQUIPMENTS;
                $layoutGenerationNew->save();
                $stdeqpNew['ID_LAYOUT_GENERATION'] = $layoutGenerationNew->ID_LAYOUT_GENERATION;
            }

            $layoutResult = $stdeqp->layoutResults->first();
            if ($layoutResult) {
                $layoutResultNew = new LayoutResults();
                $layoutResultNew = $layoutResult->replicate();
                unset($layoutResultNew->ID_LAYOUT_RESULTS);
                $layoutResultNew->ID_STUDY_EQUIPMENTS = $stdeqpNew->ID_STUDY_EQUIPMENTS;
                $layoutResultNew->save();
                $stdeqpNew['ID_LAYOUT_RESULTS'] = $layoutResultNew->ID_LAYOUT_RESULTS;
            }

            $pipeGen = $stdeqp->pipeGens->first();
            if ($pipeGen) {
                $pipeGenNew = new pipeGen();
                $pipeGenNew = $pipeGen->replicate();
                unset($pipeGenNew->ID_PIPE_GEN);
                $pipeGenNew->ID_STUDY_EQUIPMENTS = $stdeqpNew->ID_STUDY_EQUIPMENTS;
                $pipeGenNew->save();
                $stdeqpNew['ID_PIPE_GEN'] = $pipeGenNew->ID_PIPE_GEN;
            }

            if (count($stdeqp->studEqpPrms) > 0) {
                foreach ($stdeqp->studEqpPrms as $studEqpPrm) {
                    $studEqpPrmNew = new StudEqpPrm();
                    $studEqpPrmNew = $studEqpPrm->replicate();
                    unset($studEqpPrmNew->ID_STUD_EQUIP_PRM);
                    $studEqpPrmNew->ID_STUDY_EQUIPMENTS = $stdeqpNew->ID_STUDY_EQUIPMENTS;
                    $studEqpPrmNew->save();
                }
            }

            $stdeqpNew->save();
            $this->stdeqp->performNewCalculation($stdeqpNew);

            return 1;
        } else {
            return 0;
        }
    }
}