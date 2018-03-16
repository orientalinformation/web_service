<?php

namespace App\Http\Controllers\Api1;

use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Http\Response;
use App\Models\Study;
use App\Models\LineElmt;
use App\Models\MinMax;
use App\Models\PipeGen;
use App\Models\StudyEquipment;
use Illuminate\Http\Request;
use Illuminate\Contracts\Auth\Factory as Auth;
use App\Kernel\KernelService;
use App\Cryosoft\UnitsConverterService;
use App\Cryosoft\ValueListService;
use App\Cryosoft\LineService;

class Lines extends Controller
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
     * @var App\Kernel\KernelService
     */
    protected $kernel;

    /**
     * @var App\Cryosoft\UnitsConverterService
     */
    protected $convert;

    /**
     * @var App\Cryosoft\ValueListService
     */
    protected $value;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(Request $request, Auth $auth, KernelService $kernel, UnitsConverterService $convert, ValueListService $value, LineService $lineE)
    {
        $this->request = $request;
        $this->auth = $auth;
        $this->kernel = $kernel;
        $this->convert = $convert;
        $this->value = $value;
        $this->lineE = $lineE;
    }
	function eltTypeString($intType, $idIsolation) {
        switch ($intType) {
            case 1:
            if ($idIsolation != 0) {
                return 'insulatedline';
            } else {
                return 'non_insulated_line';
            }
            case 2:
                return 'storageTank';
            case 3:
                return 'tee';
            case 4:
                return 'elbows';
            case 5:
            if ($idIsolation != 0) {
                return 'insulatedlineval';
            } else {
                return 'non_insulated_valves';
            }
            default:
                break;
        }
        return false;
    }

    public function loadPipeline($id) {
		$study = Study::find($id);
        $user = $study->user;
        foreach ($study->studyEquipments as $studyEquip) {
            $pipeGen = $studyEquip->pipeGens->first();
            $coolingFamily = $studyEquip->ID_COOLING_FAMILY;
            $lineElmts = [];
            if (count($pipeGen) > 0) {
                foreach ($pipeGen->lineDefinitions as $lineDef) {
                    $lineElmt = $lineDef->lineElmt;
                    $lineElmts[] = $lineElmt;
                }
                $diameterParam = $this->lineE->getdiameter($coolingFamily, $lineElmts[0]->INSULATION_TYPE) ?? '';
                $storageTankParam = $this->lineE->getStorageTank($coolingFamily, $lineElmts[0]->INSULATION_TYPE);
                $insulationParams = LineElmt::distinct()->select('INSULATION_TYPE')->where('ID_COOLING_FAMILY', $coolingFamily)->get();
                if ($lineElmts[0]->INSULATION_TYPE == 0 ) {
                    $insulationlineSub = $this->lineE->getNameComboBox(1, $lineElmts[0]->ELT_SIZE, $coolingFamily, $lineElmts[0]->INSULATION_TYPE);
                    $non_insulated_lineSub = $this->lineE->getNonLine(1, $lineElmts[0]->ELT_SIZE, $coolingFamily,0, $lineElmts[0]->INSULATION_TYPE);
                    $insulatedlinevalSub = $this->lineE->getNameComboBox(5, $lineElmts[0]->ELT_SIZE, $coolingFamily, $lineElmts[0]->INSULATION_TYPE);
                    $non_insulated_valveSub = $this->lineE->getNonLine(5, $lineElmts[0]->ELT_SIZE, $coolingFamily,0, $lineElmts[0]->INSULATION_TYPE);
                    $teeSub = $this->lineE->getNameComboBox(3, $lineElmts[0]->ELT_SIZE, $coolingFamily, $lineElmts[0]->INSULATION_TYPE);
                    $elbowsSub = $this->lineE->getNameComboBox(4, $lineElmts[0]->ELT_SIZE, $coolingFamily, $lineElmts[0]->INSULATION_TYPE);
                } else {
                    $insulationlineSub = $this->lineE->getNameComboBoxLarge(1, $lineElmts[0]->ELT_SIZE, $coolingFamily, $lineElmts[0]->INSULATION_TYPE);
                    $non_insulated_lineSub = $this->lineE->getNonLine(1, $lineElmts[0]->ELT_SIZE, $coolingFamily,0, $lineElmts[0]->INSULATION_TYPE);
                    $insulatedlinevalSub = $this->lineE->getNameComboBoxLarge(5, $lineElmts[0]->ELT_SIZE, $coolingFamily, $lineElmts[0]->INSULATION_TYPE);
                    $non_insulated_valveSub = $this->lineE->getNonLine(5, $lineElmts[0]->ELT_SIZE, $coolingFamily, 0, $lineElmts[0]->INSULATION_TYPE);
                    $teeSub = $this->lineE->getNameComboBoxLarge(3, $lineElmts[0]->ELT_SIZE, $coolingFamily, $lineElmts[0]->INSULATION_TYPE);
                    $elbowsSub = $this->lineE->getNameComboBoxLarge(4, $lineElmts[0]->ELT_SIZE, $coolingFamily, $lineElmts[0]->INSULATION_TYPE);
                }
                $arrPipeElmt = [];
                foreach ($lineElmts as $getIDlineElmt) {
                    $arrPipeElmt[] = $getIDlineElmt->ID_PIPELINE_ELMT;
                }
                $getLabels = [];
                foreach ($arrPipeElmt as $idPipeElmt) {
                    $getLabels[] = LineElmt::select('ELT_TYPE','INSULATION_TYPE','LABEL','ID_PIPELINE_ELMT','LINE_RELEASE')->where('ID_USER', '!=', $this->auth->user()->ID_USER)
                    ->join('Translation', 'ID_PIPELINE_ELMT', '=', 'Translation.ID_TRANSLATION')
                    ->where('Translation.TRANS_TYPE', 27)->where('ID_PIPELINE_ELMT', $idPipeElmt)
                    ->where('Translation.CODE_LANGUE', $this->auth->user()->CODE_LANGUE)->orderBy('LABEL', 'ASC')->get();
                }
                $arrLabel = [];
                $arrLabel["idPipeELMT"] = $arrPipeElmt;
                $arrLabel["insulationLineSub"] = $insulationlineSub['LABEL']. "-" .  $this->lineE->getStatus($insulationlineSub['LINE_RELEASE']) ?? '';
                $arrLabel["non_insulated_lineSub"] = $non_insulated_lineSub['LABEL']. "-" .  $this->lineE->getStatus($non_insulated_lineSub['LINE_RELEASE']) ?? '';
                $arrLabel["insulatedlinevalSub"] = $insulatedlinevalSub['LABEL']. "-" .  $this->lineE->getStatus($insulatedlinevalSub['LINE_RELEASE']) ?? '';
                $arrLabel["non_insulated_valveSub"] =  $non_insulated_valveSub['LABEL']. "-" .  $this->lineE->getStatus($non_insulated_valveSub['LINE_RELEASE']) ?? '';
                $arrLabel["teeSub"] = $teeSub['LABEL']. "-" .  $this->lineE->getStatus($teeSub['LINE_RELEASE']) ?? '';
                $arrLabel["elbowsSub"] = $elbowsSub['LABEL']. "-" .  $this->lineE->getStatus($elbowsSub['LINE_RELEASE']) ?? '';
                $arrLabel["insulationLineValue"] =  $insulationlineSub['ID_PIPELINE_ELMT'] ?? '' ;
                $arrLabel["non_insulated_lineValue"] = $non_insulated_lineSub['ID_PIPELINE_ELMT'] ?? '' ;
                $arrLabel["insulatedlinevalValue"] = $insulatedlinevalSub['ID_PIPELINE_ELMT'] ?? '' ;
                $arrLabel["non_insulated_valValue"] = $non_insulated_valveSub['ID_PIPELINE_ELMT'] ?? '' ;
                $arrLabel["teeValue"] =$teeSub['ID_PIPELINE_ELMT'] ?? '' ;
                $arrLabel["elbowsValue"] =   $elbowsSub['ID_PIPELINE_ELMT'] ?? '' ;
                $arrLabel["insulationType"] = $lineElmts[0]->INSULATION_TYPE;
                $arrLabel["height"] = $this->convert->materialRise($pipeGen->HEIGHT);
                $arrLabel["pressuer"] = $this->convert->pressure($pipeGen->PRESSURE);
                $arrLabel["insulllenght"] = $this->convert->lineDimension($pipeGen->INSULLINE_LENGHT);
                $arrLabel["noninsullenght"] = $this->convert->lineDimension($pipeGen->NOINSULLINE_LENGHT);
                $arrLabel["insulvallenght"] = $pipeGen->INSUL_VALVES;
                $arrLabel["noninsulatevallenght"] = $pipeGen->NOINSUL_VALVES;
                $arrLabel["gastemp"] = $pipeGen->GAS_TEMP;
                $arrLabel["elbowsnumber"] = $pipeGen->ELBOWS;
                $arrLabel["teenumber"] = $pipeGen->TEES;
                if (count($getLabels) > 0) {
                    foreach ($getLabels as $getLabelName) {
                        if ($getLabelName[0]['ELT_TYPE'] !=2 ) {
                            $arrLabel[$this->eltTypeString($getLabelName[0]['ELT_TYPE'],$getLabelName[0]['INSULATION_TYPE'] )] = $getLabelName[0]['LABEL'] ."-". $this->lineE->getStatus($getLabelName[0]['LINE_RELEASE']);
                        } else {
                            $arrLabel[$this->eltTypeString($getLabelName[0]['ELT_TYPE'],$getLabelName[0]['INSULATION_TYPE'])] = $getLabelName[0]['ID_PIPELINE_ELMT'];
                            $arrLabel['storageTankName'] = $getLabelName[0]['LABEL'] ."-". $this->lineE->getStatus($getLabelName[0]['LINE_RELEASE']);
                        }
                        if ($lineElmts[0]->ELT_TYPE != 2) {
                            $arrLabel["diameter"] = $lineElmts[0]->ELT_SIZE;
                        } 
                    }
                }
                
                foreach ($diameterParam as $diameterParams) {
                    $arrLabel['diameterParam'][] = $diameterParams['ELT_SIZE']; 
                }
                foreach ($insulationParams as $insulationParam) {
                    $arrLabel['insulationParam'][] = $insulationParam['INSULATION_TYPE'];
                }
                $stLabel = [];
                foreach ($storageTankParam as $storageTankParams) {
                    if ($lineElmts[0]->INSULATION_TYPE == 0) {
                        $stLabel[] = $this->lineE->getNameComboBox(2,$storageTankParams->ELT_SIZE, $coolingFamily,$lineElmts[0]->INSULATION_TYPE);
                        $storageTLabel =[];
                        $storageTValue =[];
                        foreach ($stLabel as $stLabels) {
                            $storageTLabel[] = $stLabels->LABEL . "-" .  $this->lineE->getStatus($stLabels->LINE_RELEASE);
                            $storageTValue[] = $stLabels->ID_PIPELINE_ELMT;
                            $arrLabel['storageTankParam'] = $storageTLabel;
                            $arrLabel['storageTankValue'] = $storageTValue;
                        }
                    } else {
                        // return $storageTankParams->ELT_SIZE;
                        $stLabel[] = $this->lineE->getNameComboBoxLarge(2,$storageTankParams->ELT_SIZE, $coolingFamily,$lineElmts[0]->INSULATION_TYPE);
                        $storageTLabel =[];
                        $storageTValue =[];
                        foreach ($stLabel as $filterLB) {
                            $storageTLabel[] = $filterLB['LABEL'] . "-" .  $this->lineE->getStatus($filterLB['LINE_RELEASE']) ?? '';
                            $storageTValue[] = $filterLB['ID_PIPELINE_ELMT'] ?? '';
                            $arrLabel['storageTankParam'] = $storageTLabel;
                            $arrLabel['storageTankValue'] = $storageTValue;
                        }
                    }
                } 
            } else {
                $lineElmts = LineElmt::distinct()->select('INSULATION_TYPE')->where('ID_COOLING_FAMILY', $coolingFamily)->get();
            }
            
            $resultInsideDiameters= [];
            foreach ($lineElmts as $insulationType) {
                $resultInsideDiameters[] = $this->lineE->getdiameter($coolingFamily, $insulationType->INSULATION_TYPE);
				$storageTanks = $this->lineE->getStorageTank($coolingFamily, $insulationType->INSULATION_TYPE);
            }
            $resultInsideDia = [];
            
            foreach ($resultInsideDiameters as $value) {
                $item = [];
                foreach ($value as $value) {
                    $item[] = $value->ELT_SIZE;
                }
                $resultInsideDia[] = $item;
			}
            $i = 0;
            $dataResult = [];
            $dataResultExist = [];
            foreach ($resultInsideDia as $res) {
                if (count($pipeGen) > 0) {
                    $dataResultExist = $arrLabel;
                    $dataResult[$arrLabel['insulationType']] = $this->getData($arrLabel['diameterParam'], $storageTanks, $coolingFamily, $arrLabel['insulationType']);                    
                    if ($i < $arrLabel['insulationType']) {
                        $dataResult[$i] = $this->getData($res, $storageTanks, $coolingFamily, $i);
                    } else if ($i < 3) {
                        $key = $i + 1;
						$dataResult[$key] = $this->getData($res, $storageTanks, $coolingFamily, $key);
                    }
                } else {
                    $dataResult[] = $this->getData($res, $storageTanks, $coolingFamily, $i);
                }
                $i++;
            }
            return compact("dataResult", "dataResultExist");
        }
    }

    public function getData($resultFirst, $storageTanks, $coolingFamily, $sort)
    {
        $item = [];
        foreach ($resultFirst as $diameter) {
            
            $insulllenght = (float) MinMax::where('LIMIT_ITEM', $this->value->MIN_MAX_STUDY_LINE_INSULATEDLINE_LENGHT)->first()->DEFAULT_VALUE;
            $noninsullenght = (float) MinMax::where('LIMIT_ITEM', $this->value->MIN_MAX_STUDY_LINE_NON_INSULATEDLINE_LENGHT)->first()->DEFAULT_VALUE;
            $insulvallenght = (float) MinMax::where('LIMIT_ITEM', $this->value->MIN_MAX_STUDY_LINE_INSULATEDVALVE_NUMBER)->first()->LIMIT_MIN;
            $noninsulatevallenght = (float) MinMax::where('LIMIT_ITEM', $this->value->MIN_MAX_STUDY_LINE_NON_INSULATEDVALVE_NUMBER)->first()->LIMIT_MIN;
            $teenumber = (float) MinMax::where('LIMIT_ITEM', $this->value->MIN_MAX_STUDY_LINE_TEES_NUMBER)->first()->DEFAULT_VALUE;
            $elbowsnumber = (float) MinMax::where('LIMIT_ITEM', $this->value->MIN_MAX_STUDY_LINE_ELBOWS_NUMBER)->first()->LIMIT_MIN;
            $height = (float) MinMax::where('LIMIT_ITEM', $this->value->MIN_MAX_STUDY_LINE_HEIGHT)->first()->DEFAULT_VALUE;
            $pressuer = (float) MinMax::where('LIMIT_ITEM', $this->value->MIN_MAX_STUDY_LINE_PRESSURE)->first()->LIMIT_MIN;
            $gastemp = (float) MinMax::where('LIMIT_ITEM', $this->value->MIN_MAX_STUDY_LINE_GAZ_TEMP)->first()->DEFAULT_VALUE;
            $resStogeTs =[];
            foreach ($storageTanks as $vstorageTank) {
                $resStogeTs[] = $vstorageTank->ELT_SIZE;
            }
            
            $itemRes = [];
            if ($sort ==  0) {
                $insulatedline = $this->lineE->getNameComboBox(1, $diameter, $coolingFamily, $sort);
                $non_insulated_line = $this->lineE->getNonLine(1, $diameter, $coolingFamily, 0, $sort);
                $insulatedlineval = $this->lineE->getNameComboBox(5, $diameter, $coolingFamily, $sort);
                $non_insulated_valves = $this->lineE->getNonLine(5, $diameter, $coolingFamily, 0, $sort);
                $tee = $this->lineE->getNameComboBox(3, $diameter, $coolingFamily, $sort);
                $elbows = $this->lineE->getNameComboBox(4, $diameter, $coolingFamily, $sort);
                $insulatedlineLabel = $insulatedline->LABEL ."-". $this->lineE->getStatus($insulatedline->LINE_RELEASE);
                $non_insulated_lineLabel = $non_insulated_line->LABEL ."-". $this->lineE->getStatus($non_insulated_line->LINE_RELEASE);
                $insulatedlinevalLabel = $insulatedlineval->LABEL ."-". $this->lineE->getStatus($insulatedlineval->LINE_RELEASE);
                $non_insulated_valvesLabel = $non_insulated_valves->LABEL ."-". $this->lineE->getStatus($non_insulated_valves->LINE_RELEASE);
                $teeLabel = $tee->LABEL ."-". $this->lineE->getStatus($tee->LINE_RELEASE);
                $elbowsLabel = $elbows->LABEL ."-". $this->lineE->getStatus($elbows->LINE_RELEASE);
                $insulationlineValue = $insulatedline->ID_PIPELINE_ELMT;
                $non_insulated_lineValue = $non_insulated_line->ID_PIPELINE_ELMT;
                $insulatedlinevalValue = $insulatedlineval->ID_PIPELINE_ELMT;
                $non_insulated_valValue = $non_insulated_valves->ID_PIPELINE_ELMT;
                $teeValue = $tee->ID_PIPELINE_ELMT;
                $elbowsValue = $elbows->ID_PIPELINE_ELMT;
                $itemResult = [];
                $itemResultVal = [];
                $itemRes = [];
                foreach ($resStogeTs as $resStogeT) {
                    $itemRes[] = $this->lineE->getNameComboBox(2, $resStogeT, $coolingFamily, $sort);
                }
                foreach ($itemRes as $rowItem) {
                    $itemResult[] = $rowItem->LABEL ."-". $this->lineE->getStatus($rowItem->LINE_RELEASE);
                    $itemResultVal[] = $rowItem->ID_PIPELINE_ELMT;
                }
                $storageTank = $itemResult;
                $storageTankValue = $itemResultVal;
            } else {
                $insulatedline = $this->lineE->getNameComboBoxLarge(1, $diameter, $coolingFamily, $sort);
                $non_insulated_line = $this->lineE->getNonLine(1, $diameter, $coolingFamily, 0, $sort);
                $insulatedlineval = $this->lineE->getNameComboBoxLarge(5, $diameter, $coolingFamily, $sort);
                $non_insulated_valves = $this->lineE->getNonLine(5, $diameter, $coolingFamily, 0, $sort);
                $tee = $this->lineE->getNameComboBoxLarge(3, $diameter, $coolingFamily, $sort);
				$elbows = $this->lineE->getNameComboBoxLarge(4, $diameter, $coolingFamily, $sort);
				if ($insulatedline != null) {
                    $insulatedlineLabel = $insulatedline['LABEL']."-". $this->lineE->getStatus($insulatedline['LINE_RELEASE'])  ?? '';
					$insulationlineValue = $insulatedline['ID_PIPELINE_ELMT']  ?? '';
					$non_insulated_lineLabel = $non_insulated_line['LABEL'] ."-". $this->lineE->getStatus($non_insulated_line['LINE_RELEASE'])  ?? '';
                    $non_insulated_lineValue = $non_insulated_line['ID_PIPELINE_ELMT'] ?? '';
                    // return $insulatedlineval;
					$insulatedlinevalLabel = $insulatedlineval['LABEL'] ."-". $this->lineE->getStatus($insulatedlineval['LINE_RELEASE']) ?? '';
					$insulatedlinevalValue = $insulatedlineval['ID_PIPELINE_ELMT'] ?? '';
					$non_insulated_valvesLabel = $non_insulated_valves['LABEL'] ."-". $this->lineE->getStatus($non_insulated_valves['LINE_RELEASE']) ?? '';
					$non_insulated_valValue = $non_insulated_valves['ID_PIPELINE_ELMT'] ?? '';
					$teeLabel = $tee['LABEL'] ."-". $this->lineE->getStatus($tee['LINE_RELEASE']) ?? '';
					$teeValue = $tee['ID_PIPELINE_ELMT'] ?? '';
					$elbowsLabel = $elbows['LABEL'] ."-". $this->lineE->getStatus($elbows['LINE_RELEASE']) ?? '';
					$elbowsValue = $elbows['ID_PIPELINE_ELMT'] ?? '';
				} else {
					$insulatedlineLabel = '';
					$insulationlineValue = 0;
					$non_insulated_lineLabel = '';
					$non_insulated_lineValue = 0;
					$insulatedlinevalLabel = '';
					$insulatedlinevalValue = 0;
					$non_insulated_valvesLabel = '';
					$non_insulated_valValue = 0;
					$teeLabel = '';
					$teeValue = 0;
					$elbowsLabel = '';
					$elbowsValue = 0;
				}
                $itemResult = [];
                foreach ($resStogeTs as $resStogeT) {
                    $itemRes[] = $this->lineE->getNameComboBoxLarge(2, $resStogeT, $coolingFamily, $sort);
                }

                $getLabel = [];
                $getValue = [];
                if (!empty($itemRes)) {
                    foreach ($itemRes as $rowItem) {
                        $getLabel[] = (!empty($rowItem['LABEL'])) ? $rowItem['LABEL'] . $this->lineE->getStatus($rowItem['LINE_RELEASE']) : '';
                        $getValue[] = (!empty($rowItem['ID_PIPELINE_ELMT'])) ? $rowItem['ID_PIPELINE_ELMT'] : '';
                    }
                }
                $storageTank = $getLabel;
                $storageTankValue = $getValue;
            }
           
            $item['diameter'] = $this->convert->lineDimension($diameter);
            $item['insulationType'] = $sort;
            $item['insulatedline'] = $insulatedlineLabel;
            $item['non_insulated_line'] = $non_insulated_lineLabel;
            $item['insulatedlineval'] = $insulatedlinevalLabel;
            $item['non_insulated_valves'] = $non_insulated_valvesLabel;
            $item['tee'] = $teeLabel;
            $item['elbows'] = $elbowsLabel;
            $item['insulationlineValue'] = $insulationlineValue;
            $item['non_insulated_lineValue'] = $non_insulated_lineValue;
            $item['insulatedlinevalValue'] = $insulatedlinevalValue;
            $item['non_insulated_valValue'] = $non_insulated_valValue;
            $item['teeValue'] = $teeValue;
            $item['elbowsValue'] = $elbowsValue;
            $item['storageTankParam'] = $storageTank;
            $item['storageTankValue'] = $storageTankValue;
            $item['height'] = $height;
            $item['pressuer'] = $pressuer;
            $item['insulllenght'] = $insulllenght;
            $item['noninsullenght'] = $noninsullenght;
            $item['insulvallenght'] = $insulvallenght;
            $item['noninsulatevallenght'] = $noninsulatevallenght;
            $item['elbowsnumber'] = $elbowsnumber;
            $item['teenumber'] = $teenumber;
            $item['gastemp'] = $gastemp;
            $data[] = $item;
        }

        return $data;
    }

    public function savePipelines() {
        $input = $this->request->all();
        $id = $input['ID_STUDY'];
        $insulatedLine = $input['INSULATED_LINE'];
        $nonInsulatedLine = $input['NON_INSULATED_LINE'];
        $insulatedValves = $input['INSULATED_VALVES'];
        $nonInsulatedValves = $input['NON_INSULATED_VALVES'];
        $tees = $input['TEESVALUE'];
        $elbows = $input['ELBOWSVALUE'];
        $storageTank = $input['STORAGE_TANK'];
        $insulatedLineLength = ($input['INSULLINE_LENGHT'] == 0) ? 0 : $this->convert->lineDimensionSave($input['INSULLINE_LENGHT']);
        $nonInsulatedLineLength = ($input['NOINSULLINE_LENGHT'] == 0) ? 0 : $this->convert->lineDimensionSave($input['NOINSULLINE_LENGHT']);
        $insulatedValvesQuantity = $input['INSUL_VALVES'];
        $nonInsulatedValvesQuantity = $input['NOINSUL_VALVES'];
        $elbowsQuantity = $input['ELBOWS'];
        $teesQuantity = $input['TEES'];
        $height = ($input['HEIGHT'] == 0) ? 0 : $this->convert->materialRiseSave($input['HEIGHT']);
        $pressure = ($input['PRESSURE'] == 0) ? 0 :$this->convert->pressureSave($input['PRESSURE']);
        // $storageTankCapacity = $input['storageTankCapacity'];
        $gasTemperature = ($input['GAS_TEMP'] == 0) ? 0 : $this->convert->exhaustTemperature($input['GAS_TEMP']);
        
        $study = Study::find($id);
        foreach ($study->studyEquipments as $studyEquip) {
            $pipeGen = $studyEquip->pipeGens->first();
            $coolingFamily = $studyEquip->ID_COOLING_FAMILY;
            if ($pipeGen == null) {
                $pipegen = new PipeGen();
            } else {
                $pipegen = PipeGen::where('ID_PIPE_GEN',$pipeGen->ID_PIPE_GEN)->first();
            }
            $pipegen->INSULLINE_LENGHT = $insulatedLineLength;
            $pipegen->INSUL_VALVES = $insulatedValvesQuantity;
            $pipegen->NOINSULLINE_LENGHT = $nonInsulatedLineLength;
            $pipegen->NOINSUL_VALVES = $nonInsulatedValvesQuantity;
            $pipegen->TEES = $teesQuantity;
            $pipegen->ELBOWS = $elbowsQuantity;
            $pipegen->HEIGHT = $height;
            $pipegen->GAS_TEMP = $gasTemperature;
            $pipegen->FLUID = $coolingFamily;
            $pipegen->MATHIGHER = 0;
            if ($pressure != 0) {
                $pipegen->PRESSURE = $pressure;
            } else {
                return response([
                    'code' => 1005,
                    'message' => 'Value out of range in Tank pressure (0.2 : 150) !'
                ], 406); // Status code here
            }
            if ($storageTank == 0 ) {
                return response([
                    'code' => 1006,
                    'message' => 'A Storage Tank is Obligatory1'
                ], 406); // Status code here
            }
            
            if ($pipegen->ID_STUDY_EQUIPMENTS == null) {
                $pipegen->ID_STUDY_EQUIPMENTS =  $studyEquip->ID_STUDY_EQUIPMENTS;
                // $studyEquip->ID_PIPE_GEN = $pipegen->ID_PIPE_GEN;
                $pipegen->save();
                
            } else {
                
                $pipegen->save();
            }
            if (($insulatedLine != 0) && ($insulatedLineLength != 0.0)) {
                $this->lineE->createLineDefinition($pipegen->ID_PIPE_GEN, $insulatedLine, 1);
            } else if (($insulatedLine == 0) || ($insulatedLineLength == 0.0)) {
                $this->lineE->deleteLineDefinition($pipegen->ID_PIPE_GEN, 1);
            }
            if (($nonInsulatedLine != 0) && ($nonInsulatedLineLength != 0.0)) {
                $this->lineE->createLineDefinition($pipegen->ID_PIPE_GEN, $nonInsulatedLine, 2);
            } else if (($nonInsulatedLine == 0) || ($nonInsulatedLineLength == 0.0)) {
                $this->lineE->deleteLineDefinition($pipegen->ID_PIPE_GEN, 2);
            }
            if (($insulatedValves != 0) && ($insulatedValvesQuantity != 0)) {
                $this->lineE->createLineDefinition($pipegen->ID_PIPE_GEN, $insulatedValves, 5);
            } else if (($insulatedValves == 0) || ($insulatedValvesQuantity == 0)) {
                $this->lineE->deleteLineDefinition($pipegen->ID_PIPE_GEN, 5);
            }
            if (($nonInsulatedValves != 0) && ($nonInsulatedValvesQuantity != 0)) {
                $this->lineE->createLineDefinition($pipegen->ID_PIPE_GEN, $nonInsulatedValves, 6);
            } else if (($nonInsulatedValves == 0) || ($nonInsulatedValvesQuantity == 0)) {
                $this->lineE->deleteLineDefinition($pipegen->ID_PIPE_GEN, 6);
            }
            if (($elbows != 0) && ($elbowsQuantity != 0)) {
                $this->lineE->createLineDefinition($pipegen->ID_PIPE_GEN, $elbows, 3);
            } else if (($elbows == 0) || ($elbowsQuantity == 0)) {
                $this->lineE->deleteLineDefinition($pipegen->ID_PIPE_GEN, 3);
            }
            if (($tees != 0) && ($teesQuantity != 0)) {
                $this->lineE->createLineDefinition($pipegen->ID_PIPE_GEN, $tees, 4);
            } else if (($tees == 0) || ($teesQuantity == 0)) {
                $this->lineE->deleteLineDefinition($pipegen->ID_PIPE_GEN, 4);
            } 
            if ($storageTank != 0) {
                $this->lineE->createLineDefinition($pipegen->ID_PIPE_GEN, $storageTank, 7);
            } else {
                $this->lineE->deleteLineDefinition($pipegen->ID_PIPE_GEN, 7);
            }
        }
    }
}