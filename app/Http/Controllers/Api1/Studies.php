<?php

namespace App\Http\Controllers\Api1;

use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\Auth\Factory as Auth;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Kernel\KernelService;
use App\Cryosoft\UnitsConverterService;
use App\Cryosoft\ValueListService;
use App\Cryosoft\LineService;
use App\Cryosoft\StudyEquipmentService;
use App\Cryosoft\PackingService;
use App\Models\MeshGeneration;
use App\Models\Product;
use App\Models\ProductElmt;
use App\Models\Production;
use App\Models\Packing;
use App\Models\PackingLayer;
use App\Models\Study;
use App\Models\StudyEquipment;
use App\Models\LayoutResults;
use App\Models\MinMax;
use App\Models\PrecalcLdgRatePrm;
use App\Models\LayoutGeneration;
use App\Models\Translation;
use App\Models\StudEqpPrm;
use App\Models\CalculationParametersDef;
use App\Models\CalculationParameter;
use App\Cryosoft\CalculateService;
use App\Cryosoft\StudyService;
use App\Cryosoft\EquipmentsService;
use App\Models\TempRecordPts;
use App\Models\TempRecordPtsDef;
use App\Models\MeshPosition;
use App\Models\InitialTemperature;
use App\Models\Price;
use App\Models\Report;
use App\Models\EconomicResults;
use App\Models\PipeGen;
use App\Models\PipeRes;
use App\Models\LineElmt;
use App\Models\LineDefinition;
use App\Models\RecordPosition;
use App\Models\Mesh3DInfo;
use App\Models\InitTemp3D;
use App\Cryosoft\MeshService;
use App\Cryosoft\UnitsService;
use App\Cryosoft\OutputService;


class Studies extends Controller
{

    /**
     * @var \Illuminate\Http\Request
     */
    protected $request;

    /**
     * @var \Illuminate\Contracts\Auth\Factory
     */
    protected $auth;

    /**
     * @var \App\Kernel\KernelService
     */
    protected $kernel;

    /**
     * @var \App\Cryosoft\UnitsConverterService
     */
    protected $convert;

    /**
     * @var \App\Cryosoft\ValueListService
     */
    protected $value;

    /**
     * @var \App\Cryosoft\StudyEquipmentService
     */
    protected $stdeqp;

    /**
     * @var \App\Cryosoft\PackingService
     */
    protected $packing;

    /**
     * @var \App\Cryosoft\StudyService
     */
    protected $study;

    /**
     * @var \App\Cryosoft\MeshService
     */
    protected $mesh;

    protected $units;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(Request $request, Auth $auth, KernelService $kernel, 
        UnitsConverterService $convert, ValueListService $value, LineService $lineE, 
        StudyEquipmentService $stdeqp, PackingService $packing, StudyService $study, 
        UnitsService $units, MeshService $mesh, EquipmentsService $equip, OutputService $output)
    {
        $this->request = $request;
        $this->auth = $auth;
        $this->kernel = $kernel;
        $this->convert = $convert;
        $this->value = $value;
        $this->lineE = $lineE;
        $this->stdeqp = $stdeqp;
        $this->packing = $packing;
        $this->study = $study;
        $this->mesh = $mesh;
        $this->equip = $equip;
        $this->units = $units;
        $this->output = $output;
        $this->plotFolder3D = $this->output->public_path('3d');
    }

    public function findStudies()
    {
        $input = $this->request->all();
        $idUser = (isset($input['idUser'])) ? $input['idUser'] : 0;
        $idCompFamily = (isset($input['compfamily'])) ? $input['compfamily'] : 0;
        $idCompSubFamily = (isset($input['subfamily'])) ? $input['subfamily'] : 0;
        $idComponent = (isset($input['component'])) ? $input['component'] : 0;

        $mine = '';
        if ($idUser == 0 || $idUser == $this->auth->user()->ID_USER) {

            $querys = Study::distinct();

            if ($idCompFamily + $idCompSubFamily + $idComponent > 0) {
                $querys->join('PRODUCT_ELMT', 'STUDIES.ID_PROD', '=', 'PRODUCT_ELMT.ID_PROD');

                if ($idComponent > 0) {
                    $querys->where('PRODUCT_ELMT.ID_COMP', $idComponent);
                } else {
                    $querys->join('COMPONENT', 'PRODUCT_ELMT.ID_COMP', '=', 'COMPONENT.ID_COMP');
                    if ($idCompFamily > 0) {
                        $querys->where('COMPONENT.CLASS_TYPE', $idCompFamily);
                    }
                    
                    if ($idCompSubFamily > 0) {
                        $querys->where('COMPONENT.SUB_FAMILY', $idCompSubFamily);
                    }
                }
            }

            if ($idUser > 0) {
                $querys->where('STUDIES.ID_USER', $idUser);
            } else {
                $querys->where('STUDIES.ID_USER', $this->auth->user()->ID_USER);
            }

            $querys->where('PARENT_ID', 0);
            $querys->orderBy('STUDIES.STUDY_NAME');

            $mine = $querys->get();
        }
        
        $others = $this->study->getFilteredStudiesList($idUser, $idCompFamily, $idCompSubFamily, $idComponent);

        return compact('mine', 'others');
    }

    public function deleteStudyById($id)
    {
        $study = Study::findOrFail($id);
        if (!$study) return -1;
        
        $conf = $this->kernel->getConfig($this->auth->user()->ID_USER, intval($id), -1);
        $this->kernel->getKernelObject('StudyCleaner')->SCStudyClean($conf, SC_CLEAN_OUTPUT_ALL);
        
        $products = $study->products;
        foreach ($products as $product) {
            // 3d featrue delete mesh3D_info
            $mesh3D_info = Mesh3DInfo::Where('ID_PROD', $product->ID_PROD)->first();
            if(count($mesh3D_info) > 0) {
                // if (file_exists($mesh3D_info->file_path)) {
                //     $dir = $mesh3D_info->file_path;
                //     foreach (scandir($dir) as $object) {
                //         if ($object != "." && $object != "..") {
                //             if (filetype($dir."/".$object) == "dir") 
                //                 rmdir($dir."/".$object); 
                //             else unlink   ($dir."/".$object);
                //         }
                //     }
                //         rmdir($dir);
                // }
                $mesh3D_info->delete();
            }

            $meshGenerations = $product->meshGenerations;
            foreach ($meshGenerations as $mesh) {
                $mesh->delete();
            }

            foreach ($product->productElmts as $productElmt) {
                // 3d featrue not delete mesh position
                foreach ($productElmt->meshPositions as $meshPst) {
                    if ($meshPst) {
                        $meshPst->delete();
                    }
                }

                // 3d feature delete initial_temp
                $initial3D_temps = InitTemp3D::where('ID_PRODUCT_ELMT', $productElmt->ID_PRODUCT_ELMT)->get();
                if (count($initial3D_temps) > 0) {
                    foreach ($initial3D_temps as $initial3D_temp) {
                        $initial3D_temp->delete();
                    }
                }
                $productElmt->delete();
            }

            if (count($product->prodcharColors) > 0) {
                foreach ($product->prodcharColors as $prodCharColor) {
                    $prodCharColor->delete();
                }
            }

            $product->delete();
        }

        $productions = $study->productions;

        foreach ($study->prices as $price) {
            $price->delete();
        }

        // 3d featrue not delete Initial_temperature
        if (count($productions) > 0) {
            foreach ($productions as $production) {
                InitialTemperature::where('ID_PRODUCTION', $production->ID_PRODUCTION)->delete();
                $production->delete();
            }
        }

        $tempRecordPts = $study->tempRecordPts;
        foreach ($tempRecordPts as $tempRecord) {
            $tempRecord->delete();
        }

        foreach ($study->reports as $report) {
            $report->delete();
        }
        
        foreach ($study->packings as $packing) {
            foreach ($packing->packingLayers as $packingLayer) {
                $packingLayer->delete();
            }
            $packing->delete();
        }

        foreach ($study->precalcLdgRatePrms as $precalcLdgRatePrm) {
            $precalcLdgRatePrm->delete();
        }

        foreach ($study->studyEquipments as $equip) {
            foreach ($equip->layoutGenerations as $layoutGen) {
                $layoutGen->delete();
            }

            foreach ($equip->layoutResults as $layoutResult) {
                $layoutResult->delete();
            }

            foreach ($equip->calculationParameters as $calcParam) {
                $calcParam->delete();
            }

            foreach ($equip->pipeGens as $pipeGen) {
                foreach ($pipeGen->lineDefinitions as $lineDef) $lineDef->delete();
                $pipeGen->delete();
            }

            foreach ($equip->pipeRes as $pipeRes) {
                $pipeRes->delete();
            }

            foreach ($equip->exhGens as $exhGen) {
                $exhGen->delete();
            }

            foreach ($equip->exhRes as $exhRes) {
                $exhRes->delete();
            }

            foreach ($equip->studEquipprofiles as $eqpProfile) {
                $eqpProfile->delete();
            }

            foreach ($equip->economicResults as $ecoRes) {
                $ecoRes->delete();
            }

            foreach ($equip->studEqpPrms as $studEqpPrm) {
                $studEqpPrm->delete();
            }

            $equip->delete();
        }

        if (($study->CHAINING_CONTROLS != 0) && ($study->PARENT_ID != 0)) {
            $parent = Study::find($study->PARENT_ID);
            if ($parent) {
                $parent->HAS_CHILD = count(Study::where('PARENT_ID', $parent->ID_STUDY)->get()) - 1 > 0 ? 1 : 0;
                $parent->save();
            }
        }

        return (int) $study->delete();
    }

    public function getStudyById($id)
    {
        $study = Study::find($id);
        return $study;
    }

    public function saveStudyAs($id)
    {
        $input = $this->request->all();
        $duplicateStudy = Study::where('STUDY_NAME', '=', $input['name'])->count();
        if ($duplicateStudy) {
            return response([
                'code' => 1002,
                'message' => 'This study name already exists, please try another one.'
            ], 406);
        }

        $studies = $this->study->getChildIdChaining($id);

        if (count($studies) > 0) {
            $parentId = 0;
            $i = 0;
            foreach ($studies as $studyChild) {
                $studyCurrent = Study::find($studyChild);
                if ($studyCurrent) {
                    $study = new Study();
                    $temprecordpst = new TempRecordPts();
                    $production = new Production();
                    $product = new Product();
                    $meshgeneration = new MeshGeneration();
                    $price = new Price();
                    $report = new Report();
                    $precalcLdgRatePrm = new PrecalcLdgRatePrm();
                    $packing = new Packing();

                    $temprecordpstCurr = TempRecordPts::where('ID_STUDY', $studyCurrent->ID_STUDY)->first();
                    $productionCurr = Production::where('ID_STUDY', $studyCurrent->ID_STUDY)->first(); 
                    $productCurr = Product::where('ID_STUDY', $studyCurrent->ID_STUDY)->first();
                    $mesh3D_info = Mesh3DInfo::where('ID_PROD', $productCurr->ID_PROD)->first();

                    $priceCurr = Price::where('ID_STUDY', $studyCurrent->ID_STUDY)->first(); 
                    $reportCurr = Report::where('ID_STUDY', $studyCurrent->ID_STUDY)->first(); 
                    $precalcLdgRatePrmCurr = PrecalcLdgRatePrm::where('ID_STUDY', $studyCurrent->ID_STUDY)->first();
                    $packingCurr = Packing::where('ID_STUDY', $studyCurrent->ID_STUDY)->first(); 
                    $studyemtlCurr = StudyEquipment::where('ID_STUDY', $studyCurrent->ID_STUDY)->get();

                    //duplicate study already exsits
                    $study = $studyCurrent->replicate();
                    unset($study->STUDY_NAME);
                    unset($study->ID_USER);
                    $study->ID_USER = $this->auth->user()->ID_USER;
                    $study->STUDY_NAME = ($i > 0) ? $input['name'] . '_' . $i : $input['name'];
                    if ($i > 0 && $parentId != 0) {
                        $study->PARENT_ID = $parentId;
                    }

                    $date = date("D M j G:i:s T Y") . ' by ' . $this->auth->user()->USERNAM;
                    $study->COMMENT_TXT = $studyCurrent->COMMENT_TXT .'</br>'. 'Created on ' . $date;
                    $study->save();
                    $parentId = $study->ID_STUDY;

                    //duplicate TempRecordPts already exsits
                    if ($temprecordpstCurr) {
                        $temprecordpst = $temprecordpstCurr->replicate();
                        $temprecordpst->ID_STUDY = $study->ID_STUDY;
                        unset($temprecordpst->ID_TEMP_RECORD_PTS);
                        $temprecordpst->save();
                    }

                    //duplicate Production already exsits
                    if ($productionCurr) {
                        $production = $productionCurr->replicate();
                        $production->ID_STUDY = $study->ID_STUDY;
                        unset($production->ID_PRODUCTION);
                        $production->save();

                        //duplicate initial_Temp already exsits
                        $initialTemperatures = InitialTemperature::where('ID_PRODUCTION', $productionCurr->ID_PRODUCTION)->get();
                        if (count($initialTemperatures) > 0) {
                            DB::insert('insert into INITIAL_TEMPERATURE (ID_PRODUCTION, INITIAL_T, MESH_1_ORDER, MESH_2_ORDER, MESH_3_ORDER) select '
                        . $production->ID_PRODUCTION . ',I.INITIAL_T, I.MESH_1_ORDER, I.MESH_2_ORDER, I.MESH_3_ORDER from INITIAL_TEMPERATURE as I where ID_PRODUCTION = ' . $productionCurr->ID_PRODUCTION);
                        }
                    }

                    //duplicate Product already exsits
                    if ($productCurr) {
                        $product = $productCurr->replicate();
                        $product->ID_STUDY = $study->ID_STUDY;
                        unset($product->ID_PROD);
                        $product->save();

                        $meshgenerationCurr = MeshGeneration::where('ID_PROD',$productCurr->ID_PROD)->first(); 
                        $productemltCurr = ProductElmt::where('ID_PROD',$productCurr->ID_PROD)->get(); 
                        //duplicate MeshGeneration already exsits
                        if ($meshgenerationCurr) {
                            $meshgeneration = $meshgenerationCurr->replicate();
                            $meshgeneration->ID_PROD = $product->ID_PROD;
                            unset($meshgeneration->ID_MESH_GENERATION);
                            $meshgeneration->save();
                            $product->ID_MESH_GENERATION = $meshgeneration->ID_MESH_GENERATION;
                            $product->save();
                        }

                        if ($mesh3D_info) {
                            $mesh3D_new = new Mesh3DInfo();
                            $mesh3D_new = $mesh3D_info->replicate();
                            $mesh3D_new->id_prod = $product->ID_PROD;
                            $mesh3D_new->file_path = str_replace('Prod_' . $mesh3D_info->id_prod, 'Prod_' . $mesh3D_new->id_prod, $mesh3D_info->file_path);

                            //copy mesh folder
                            $oldProd3dFolder = $this->plotFolder3D . '/MeshBuilder3D/Prod_' . $mesh3D_info->id_prod;
                            $newProd3dFolder = $this->plotFolder3D . '/MeshBuilder3D/Prod_' . $mesh3D_new->id_prod;

                            if (is_dir($oldProd3dFolder)) {
                                $this->output->recurse_copy($oldProd3dFolder, $newProd3dFolder);
                            }
                            unset($mesh3D_new->id_mesh3d_info);
                            $mesh3D_new->save();
                        }

                        if (count($productemltCurr) > 0) {
                            foreach ($productemltCurr as $prodelmtCurr ) {
                                $productemlt = new ProductElmt();
                                $productemlt = $prodelmtCurr->replicate();
                                $productemlt->ID_PROD = $product->ID_PROD;
                                $productemlt->INSERT_LINE_ORDER = $study->ID_STUDY;
                                unset($productemlt->ID_PRODUCT_ELMT);
                                $productemlt->save();

                                // duplicate MESH_POSITION
                                $meshPositions = MeshPosition::where('ID_PRODUCT_ELMT', $prodelmtCurr->ID_PRODUCT_ELMT)->get();
                                if (count($meshPositions) > 0) {
                                    DB::insert('insert into MESH_POSITION (ID_PRODUCT_ELMT, MESH_AXIS, MESH_ORDER, MESH_AXIS_POS) select '
                                    . $productemlt->ID_PRODUCT_ELMT . ',M.MESH_AXIS, M.MESH_ORDER, M.MESH_AXIS_POS from MESH_POSITION as M where ID_PRODUCT_ELMT = '
                                    . $prodelmtCurr->ID_PRODUCT_ELMT);
                                }

                                $initial3D_temps = InitTemp3D::where('ID_PRODUCT_ELMT', $prodelmtCurr->ID_PRODUCT_ELMT)->get();
                                if (count($initial3D_temps) > 0) {
                                    foreach ($initial3D_temps as $initial3D_temp) {
                                        $inital3D_new = new InitTemp3D();
                                        $inital3D_new = $initial3D_temp->replicate();
                                        $inital3D_new->ID_PRODUCT_ELMT = $productemlt->ID_PRODUCT_ELMT;
                                        unset($inital3D_new->ID_MESH_POSITION);
                                        $inital3D_new->save();
                                    }
                                }
                            }
                        }
                    }
                        
                    //duplicate Price already exsits
                    if ($priceCurr) {
                        $price = $priceCurr->replicate();
                        $price->ID_STUDY = $study->ID_STUDY;
                        unset($price->ID_PRICE);
                        $price->save();
                    }

                    //duplicate Report already exsits
                    if ($reportCurr) {
                        $report = $reportCurr->replicate();
                        $report->ID_STUDY = $study->ID_STUDY;
                        unset($report->ID_REPORT);
                        $report->save();
                    }
                    
                    if ($precalcLdgRatePrmCurr) {
                        $precalcLdgRatePrm = $precalcLdgRatePrmCurr->replicate();
                        $precalcLdgRatePrm->ID_STUDY = $study->ID_STUDY;
                        unset($precalcLdgRatePrm->ID_PRECALC_LDG_RATE_PRM);
                        $precalcLdgRatePrm->save();
                    }

                    if ($packingCurr) {
                        $packing = $packingCurr->replicate();
                        $packing->ID_STUDY = $study->ID_STUDY;
                        unset($packing->ID_PACKING);
                        $packing->save();
         
                        $packingLayerCurr = PackingLayer::where('ID_PACKING', $packingCurr->ID_PACKING)->get(); 
                        if (count($packingLayerCurr) > 0) {
                            foreach ($packingLayerCurr as $pLayer) {
                                $packingLayer = new PackingLayer();
                                $packingLayer = $pLayer->replicate();
                                $packingLayer->ID_PACKING = $packing->ID_PACKING;
                                unset($packingLayer->ID_PACKING_LAYER);
                                $packingLayer->save();
                            }
                        }
                    }

                    if (count($studyemtlCurr) > 0) {
                        foreach ($studyemtlCurr as $stuElmt) {
                            $studyelmt = new StudyEquipment();
                            $studyelmt = $stuElmt->replicate();
                            $studyelmt->ID_STUDY = $study->ID_STUDY;
                            $studyelmt->BRAIN_TYPE = 0;
                            unset($studyelmt->ID_STUDY_EQUIPMENTS);

                            if ($studyelmt->save()) {
                                $studyelmtId = $studyelmt->ID_STUDY_EQUIPMENTS;
                                $pipegen = new PipeGen();
                                $pipegenCurr = PipeGen::where('ID_STUDY_EQUIPMENTS', $stuElmt->ID_STUDY_EQUIPMENTS)->first();

                                if (count($pipegenCurr) > 0) {
                                    $pipegen = $pipegenCurr->replicate();
                                    $pipegen->ID_STUDY_EQUIPMENTS = $studyelmtId;
                                    unset($pipegen->ID_PIPE_GEN );
                                    $pipegen->save();

                                    $studyelmt->ID_PIPE_GEN = $pipegen->ID_PIPE_GEN;
                                    $studyelmt->save();

                                    $lineDefiCurr = LineDefinition::where('ID_PIPE_GEN', $pipegenCurr->ID_PIPE_GEN)->get();
                                    if (count($lineDefiCurr) > 0) {
                                        foreach ($lineDefiCurr as $lineDefiCurrs) {
                                            $lineDefi = new LineDefinition();
                                            $lineDefi = $lineDefiCurrs->replicate();
                                            $lineDefi->ID_PIPE_GEN = $pipegen->ID_PIPE_GEN;
                                            unset($lineDefi->ID_LINE_DEFINITION);
                                            $lineDefi->save();
                                        }
                                    }
                                }

                                $piperes = new PipeRes();
                                $piperesCurr = PipeRes::where('ID_STUDY_EQUIPMENTS', $stuElmt->ID_STUDY_EQUIPMENTS)->first();
                                if (count($piperesCurr) > 0) {
                                    $piperes = $piperesCurr->replicate();
                                    $piperes->ID_STUDY_EQUIPMENTS = $studyelmtId;
                                    unset($piperes->ID_PIPE_RES);
                                    $piperes->save();
                                }

                                $economicRes = new EconomicResults();
                                $economicResults = EconomicResults::where('ID_STUDY_EQUIPMENTS', $stuElmt->ID_STUDY_EQUIPMENTS)->first();
                                if (count($economicResults) > 0) {
                                    $economicRes = $economicResults->replicate();
                                    $economicRes->ID_STUDY_EQUIPMENTS = $studyelmtId;
                                    unset($economicRes->ID_ECONOMIC_RESULTS);
                                    $economicRes->save();
                                }

                                $calparam = CalculationParameter::where('ID_STUDY_EQUIPMENTS', $stuElmt->ID_STUDY_EQUIPMENTS)->first();
                                if (count($calparam) > 0) {
                                    $calparameter = new CalculationParameter();
                                    $calparameter = $calparam->replicate();
                                    $calparameter->ID_STUDY_EQUIPMENTS = $studyelmtId;
                                    unset($calparameter->ID_CALC_PARAMS);
                                    $calparameter->save();
                                } 
                                
                                $stdEqpPrms = StudEqpPrm::where('ID_STUDY_EQUIPMENTS', $stuElmt->ID_STUDY_EQUIPMENTS)->get();
                                if (count($stdEqpPrms) > 0) {
                                    foreach ($stdEqpPrms as $stdEqpPrm) {
                                        $newStdEqpParam = new StudEqpPrm();
                                        $newStdEqpParam->ID_STUDY_EQUIPMENTS = $studyelmtId;
                                        $newStdEqpParam->VALUE_TYPE = $stdEqpPrm->VALUE_TYPE;
                                        $newStdEqpParam->VALUE = $stdEqpPrm->VALUE;
                                        $newStdEqpParam->save();
                                    }
                                }
                                
                                $layoutGenerations = LayoutGeneration::where('ID_STUDY_EQUIPMENTS', $stuElmt->ID_STUDY_EQUIPMENTS)->get(); 
                                if (count($layoutGenerations) > 0) {
                                    foreach ($layoutGenerations as $layoutGeneration) {
                                        $layoutGen = new LayoutGeneration();
                                        $layoutGen = $layoutGeneration->replicate();
                                        $layoutGen->ID_STUDY_EQUIPMENTS = $studyelmtId;
                                        unset($layoutGen->ID_LAYOUT_GENERATION);
                                        $layoutGen->save();
                                    }
                                }

                                $layoutResults = LayoutResults::where('ID_STUDY_EQUIPMENTS', $stuElmt->ID_STUDY_EQUIPMENTS)->get(); 
                                if (count($layoutResults) > 0) {
                                    foreach ($layoutResults as $layoutRes) {
                                        $layoutResult = $layoutRes->replicate();
                                        $layoutResult->ID_STUDY_EQUIPMENTS = $studyelmtId;
                                        unset($layoutResult->ID_LAYOUT_RESULTS);
                                        $layoutResult->save();
                                    }
                                }
                            }
                        }
                        $studyelmt->ID_PIPE_RES = $piperes->ID_PIPE_RES;
                        
                        $studyelmt->ID_ECONOMIC_RESULTS = $economicRes->ID_ECONOMIC_RESULTS;
                        $studyelmt->ID_CALC_PARAMS = $calparameter->ID_CALC_PARAMS;
                        $studyelmt->ID_LAYOUT_GENERATION = $layoutGen->ID_LAYOUT_GENERATION;
                        $studyelmt->ID_LAYOUT_RESULTS = $layoutResult->ID_LAYOUT_RESULTS;
                        $studyelmt->save();
                    }

                    $study->ID_TEMP_RECORD_PTS = $temprecordpst->ID_TEMP_RECORD_PTS;
                    $study->ID_PRODUCTION = $production->ID_PRODUCTION;
                    $study->ID_PROD = $product->ID_PROD;
                    $study->ID_PRICE = $price->ID_PRICE;
                    $study->ID_REPORT = $report->ID_REPORT;
                    $study->ID_PRECALC_LDG_RATE_PRM = $precalcLdgRatePrm->ID_PRECALC_LDG_RATE_PRM;
                    $study->ID_PACKING = $packing->ID_PACKING;
                    $study->save();

                    $i++;
                }
            }

            return 1;
        } else {
           return response([
                'code' => 1003,
                'message' => 'Study id not found'
            ], 406); // Status code here
        }
    }

    public function saveStudy($id)
    {
        $study = Study::find($id);
        $update = (object) $this->request->json()->all();

        $study->CALCULATION_MODE = $update->CALCULATION_MODE;
        $study->CALCULATION_STATUS = $update->CALCULATION_STATUS;
        $study->STUDY_NAME = $update->STUDY_NAME;
        $study->CUSTOMER = $update->CUSTOMER;
        $study->COMMENT_TXT = $update->COMMENT_TXT;
        $study->OPTION_CRYOPIPELINE = $update->OPTION_CRYOPIPELINE;
        $study->OPTION_EXHAUSTPIPELINE = $update->OPTION_EXHAUSTPIPELINE;
        $study->OPTION_ECO = $update->OPTION_ECO;
        $study->CHAINING_CONTROLS = $update->CHAINING_CONTROLS;
        $study->CHAINING_ADD_COMP_ENABLE = $update->CHAINING_ADD_COMP_ENABLE;        
        $study->CHAINING_NODE_DECIM_ENABLE = $update->CHAINING_NODE_DECIM_ENABLE;
        $study->TO_RECALCULATE = $update->TO_RECALCULATE;
        $study->HAS_CHILD = $update->HAS_CHILD;
        $study->OPEN_BY_OWNER = $update->OPEN_BY_OWNER;
        if ($study->OPTION_CRYOPIPELINE == 0) {
            if (!empty($study->studyEquipments)) {
                foreach ($study->studyEquipments as $studyEquip) {
                    $pipeGen = $studyEquip->pipeGens->first();
                    if ($pipeGen != null) {
                        $studyEquip->ID_PIPE_GEN = 0;
                        $studyEquip->BRAIN_PROCESS = 0;
                        $studyEquip->save();
                        $pipeDefition = $pipeGen->lineDefinitions;
                        foreach ($pipeDefition as $pipeDefitions) {
                            $pipeDefitions->delete();
                        }
                        $pipeGen->delete();
                    }
                }
            }
        }

        if ($study->CHAINING_ADD_COMP_ENABLE) {
            $product = $study->products()->first();
            $products = $study->products();
            if ($product) {
                if (count($products) > 1) {
                    $meshGen = $product->meshGenerations()->first();
                    if ($meshGen) {
                        if ($meshGen->MESH_1_FIXED != MeshService::IRREGULAR_MESH ||
                            $meshGen->MESH_1_MODE != MeshService::MAILLAGE_MODE_IRREGULAR) {
                            $meshGen->MESH_1_FIXED = MeshService::IRREGULAR_MESH;
                            $meshGen->MESH_2_FIXED = MeshService::IRREGULAR_MESH;
                            $meshGen->MESH_3_FIXED = MeshService::IRREGULAR_MESH;
                            
                            $meshGen->MESH_1_MODE = MeshService::MAILLAGE_MODE_IRREGULAR;
                            $meshGen->MESH_2_MODE = MeshService::MAILLAGE_MODE_IRREGULAR;
                            $meshGen->MESH_3_MODE = MeshService::MAILLAGE_MODE_IRREGULAR;
                            $meshGen->save();
                            $this->mesh->rebuildMesh($study);
                        }
                    }
                }
            }
        }

        return (int) $study->save();
    }

    public function refreshMesh($id)
    {
        $conf = $this->kernel->getConfig($this->auth->user()->ID_USER, $id, 10);
        
        return $this->kernel->getKernelObject('MeshBuilder')->MBMeshBuild($conf);
    }

    public function openStudy($id)
    {
        $study = Study::find($id);

        $tempRecordPts = TempRecordPts::where('ID_STUDY', $id)->first();
        if (!$tempRecordPts) {
            $tempRecordPtsDef = TempRecordPtsDef::where('ID_USER', $this->auth->user()->ID_USER)->first();
            $tempRecordPts = new TempRecordPts();
            $tempRecordPts->NB_STEPS = $tempRecordPtsDef->NB_STEPS_DEF;
            $tempRecordPts->ID_STUDY = $id;

            $tempRecordPts->AXIS1_PT_TOP_SURF = 0;
            $tempRecordPts->AXIS2_PT_TOP_SURF = 0;
            $tempRecordPts->AXIS3_PT_TOP_SURF = 0;

            $tempRecordPts->AXIS1_PT_INT_PT = 0;
            $tempRecordPts->AXIS2_PT_INT_PT = 0;
            $tempRecordPts->AXIS3_PT_INT_PT = 0;

            $tempRecordPts->AXIS1_PT_BOT_SURF = 0;
            $tempRecordPts->AXIS2_PT_BOT_SURF = 0;
            $tempRecordPts->AXIS3_PT_BOT_SURF = 0;

            $tempRecordPts->AXIS1_AX_2 = 0;
            $tempRecordPts->AXIS1_AX_3 = 0;

            $tempRecordPts->AXIS2_AX_3 = 0;
            $tempRecordPts->AXIS2_AX_1 = 0;

            $tempRecordPts->AXIS3_AX_1 = 0;
            $tempRecordPts->AXIS3_AX_2 = 0;

            $tempRecordPts->AXIS1_PL_2_3 = 0;
            $tempRecordPts->AXIS2_PL_1_3 = 0;
            $tempRecordPts->AXIS3_PL_1_2 = 0;

            $tempRecordPts->CONTOUR2D_TEMP_MIN = 0;
            $tempRecordPts->CONTOUR2D_TEMP_MAX = 0;

            $tempRecordPts->save();
        }

        if ($study->ID_TEMP_RECORD_PTS != $tempRecordPts->ID_TEMP_RECORD_PTS) {
            $study->ID_TEMP_RECORD_PTS = $tempRecordPts->ID_TEMP_RECORD_PTS;
            $study->save();
        }

        $conf = $this->kernel->getConfig($this->auth->user()->ID_USER, intval($id), -1);
        return $this->kernel->getKernelObject('StudyCleaner')->SCStudyClean($conf, SC_CLEAN_TMP_DATA);
    }

    public function getStudyEquipments($id) 
    {
        $study = Study::findOrFail($id);
        return $this->stdeqp->findStudyEquipmentsByStudy($study);
    }

    public function newProduct($id) 
    {
        $input = $this->request->all();

        if (!isset($input['name']) || empty($input['name']))
            return 1;

        $study = Study::find($id);
        $product = $study->products;

        if (count($product) == 0) {
            $product = new Product();
            $product->ID_STUDY = $study->ID_STUDY;
        } else {
            $product = $product[0];
        }

        $conf = $this->kernel->getConfig($this->auth->user()->ID_USER, intval($id), -1);
        $this->kernel->getKernelObject('StudyCleaner')->SCStudyClean($conf, SC_CLEAN_OUTPUT_PRODUCT);

        $elements = $product->productElmts;
        if ($elements->count() > 0) {
            foreach ($elements as $elmt) {
                $elmt->delete();
            }
        }

        $product->PRODNAME = $input['name'];
        $product->PROD_WEIGHT = 0.0;
        $product->PROD_REALWEIGHT = 0.0;
        $product->PROD_VOLUME = 0.0;
        $product->PROD_ISO = 1;
        $product->ID_MESH_GENERATION = 0;
        $product->save();

        return 0;
    }

    public function updateProduct($id) 
    {
        $study = Study::find($id);
        $product = $study->products->first();
        $input = $this->request->json()->all();

        if (isset($input['name']) && !empty($input['name'])) {
            $product->PRODNAME = $input['name'];
            $product->save();
        }

        $ok = 0;

        if (isset($input['dim1']) || isset($input['dim3'])) {
            $elements = $product->productElmts;
            if ($elements->count() > 0) {
                foreach ($elements as $elmt) {
                    if (isset($input['dim1'])) $elmt->SHAPE_PARAM1 = $this->convert->prodDimensionSave(floatval($input['dim1']));
                    if (isset($input['dim2'])) $elmt->SHAPE_PARAM2 = $this->convert->prodDimensionSave(floatval($input['dim2']));
                    if (isset($input['dim3'])) $elmt->SHAPE_PARAM3 = $this->convert->prodDimensionSave(floatval($input['dim3']));
                    if (isset($input['dim4'])) $elmt->SHAPE_PARAM4 = $this->convert->prodDimensionSave(floatval($input['dim4']));
                    if (isset($input['dim5'])) $elmt->SHAPE_PARAM5 = $this->convert->prodDimensionSave(floatval($input['dim5']));
                    $elmt->save();

                    $conf = $this->kernel->getConfig($this->auth->user()->ID_USER, $product->ID_PROD, intval($elmt->ID_PRODUCT_ELMT));
                    $ok = $this->kernel->getKernelObject('WeightCalculator')->WCWeightCalculation($id, $conf, 2);
                }

                $conf = $this->kernel->getConfig($this->auth->user()->ID_USER, $product->ID_PROD);
                $ok = $this->kernel->getKernelObject('WeightCalculator')->WCWeightCalculation($id, $conf, 4);
            }
        }

        return $ok;
    }

    public function getStudyPackingLayers($id)
    {
        $packing = Packing::where('ID_STUDY', $id)->first();
        $packingLayers = null;
        if ($packing) {
            $packingLayers = PackingLayer::where('ID_PACKING', $packing->ID_PACKING)->get();
            for ($i = 0; $i < count($packingLayers); $i++) { 
                $value = $this->units->packingThickness($packingLayers[$i]->THICKNESS, 2, 1);
                $packingLayers[$i]->THICKNESS = $value;
            }
        }

        return compact('packing', 'packingLayers');
    }

    public function savePacking($id)
    {
        $input = $this->request->all();
        
        $packing = Packing::where('ID_STUDY', $id)->first();
        if (empty($packing)) {
            $packing = new \App\Models\Packing();
            $packing->ID_SHAPE = $input['packing']['ID_SHAPE'];
            $packing->ID_STUDY = $id;
        }

        if (!isset($input['packing']['NOMEMBMAT'])) $input['packing']['NOMEMBMAT'] = "";

        $packing->NOMEMBMAT = $input['packing']['NOMEMBMAT'];
        $packing->save();

        $packingLayer = PackingLayer::where('ID_PACKING', $packing->ID_PACKING)->delete();

        foreach ($input['packingLayers'] as $key => $value) {
            $packingLayer = new PackingLayer();
            $packingLayer->ID_PACKING = $packing->ID_PACKING;
            if (isset($value['ID_PACKING_ELMT'])) $packingLayer->ID_PACKING_ELMT = $value['ID_PACKING_ELMT'];
            if (isset($value['THICKNESS'])) $packingLayer->THICKNESS = $this->units->packingThickness($value['THICKNESS'], 5, 0);
            if (isset($value['PACKING_SIDE_NUMBER'])) $packingLayer->PACKING_SIDE_NUMBER = $value['PACKING_SIDE_NUMBER'];
            if (isset($value['PACKING_LAYER_ORDER'])) $packingLayer->PACKING_LAYER_ORDER = $value['PACKING_LAYER_ORDER'];
            $packingLayer->save();
        }
        return 1;
    }

    public function createStudy()
    {
        $study = new Study();

        $production = new Production();

        $precalc = new PrecalcLdgRatePrm();

        $tempRecordPts = new TempRecordPts();

        $input = $this->request->json()->all();

        $study->STUDY_NAME = $input['STUDY_NAME'];
        $duplicateStudy = Study::where('STUDY_NAME', '=', $input['STUDY_NAME'])->count();
        if($duplicateStudy) {
            return response([
                'code' => 1002,
                'message' => 'This study name already exists, please try another one.'
            ], 406);
        }
        
        $study->ID_USER = $this->auth->user()->ID_USER;
        $study->OPTION_ECO = isset($input['OPTION_ECO']) ? $input['OPTION_ECO'] : 0;
        $study->CALCULATION_MODE = $input['CALCULATION_MODE'];

        $date = 'Created on ' . date("D M j G:i:s T Y") . ' by ' . $this->auth->user()->USERNAM;
        $study->COMMENT_TXT = isset($input['COMMENT_TXT']) ? $input['COMMENT_TXT'] . "\n" . $date  : $date;

        $study->OPTION_CRYOPIPELINE = isset($input['OPTION_CRYOPIPELINE']) ? $input['OPTION_CRYOPIPELINE'] : '';
        $study->OPTION_EXHAUSTPIPELINE = isset($input['OPTION_EXHAUSTPIPELINE']) ? $input['OPTION_EXHAUSTPIPELINE']: '';
        $study->CHAINING_CONTROLS = isset($input['CHAINING_CONTROLS']) ? $input['CHAINING_CONTROLS'] : '';
        $study->CHAINING_ADD_COMP_ENABLE = isset($input['CHAINING_ADD_COMP_ENABLE']) ? $input['CHAINING_ADD_COMP_ENABLE'] : '';
        $study->CHAINING_NODE_DECIM_ENABLE = 0;
        $study->HAS_CHILD = 0;
        $study->TO_RECALCULATE = 0;
        $study->ID_REPORT = 0;
        $study->save();

        $nbMF                   = (float) MinMax::where('LIMIT_ITEM', $this->value->MIN_MAX_DAILY_STARTUP)->first()->DEFAULT_VALUE;
        $nbWeekPeryear          = (float) MinMax::where('LIMIT_ITEM', $this->value->MIN_MAX_PROD_WEEK_PER_YEAR)->first()->DEFAULT_VALUE;
        $nbheures               = (float) MinMax::where('LIMIT_ITEM', $this->value->MIN_MAX_PRODUCT_DURATION)->first()->DEFAULT_VALUE;
        $nbjours                = (float) MinMax::where('LIMIT_ITEM', $this->value->MIN_MAX_WEEKLY_PRODUCTION)->first()->DEFAULT_VALUE;
        $humidity               = (float) MinMax::where('LIMIT_ITEM', $this->value->MIN_MAX_AMBIENT_HUM)->first()->DEFAULT_VALUE;
        $dailyProductFlow       = (float) MinMax::where('LIMIT_ITEM', $this->value->MIN_MAX_FLOW_RATE)->first()->DEFAULT_VALUE;
        $avTempDesired          = (float) MinMax::where('LIMIT_ITEM', $this->value->MIN_MAX_AVG_TEMPERATURE_DES)->first()->DEFAULT_VALUE;
        $temp                   = (float) MinMax::where('LIMIT_ITEM', $this->value->MIN_MAX_TEMP_AMBIANT)->first()->DEFAULT_VALUE;

        $production->DAILY_STARTUP          = $nbMF;
        $production->DAILY_PROD             = $nbheures;
        $production->NB_PROD_WEEK_PER_YEAR  = $nbWeekPeryear;
        $production->WEEKLY_PROD            = $nbjours;
        $production->AMBIENT_HUM            = $humidity;
        $production->PROD_FLOW_RATE         = $dailyProductFlow;
        $production->AVG_T_DESIRED          = $avTempDesired;
        $production->AMBIENT_TEMP           = $temp;
        $production->ID_STUDY               = $study->ID_STUDY;
        $production->save();

        $product = new Product();
        $product->ID_STUDY = $study->ID_STUDY;
        $product->ID_MESH_GENERATION = 0;
        $product->PRODNAME = '';
        $product->PROD_ISO = true;
        $product->PROD_WEIGHT = 0.0;
        $product->PROD_REALWEIGHT = 0.0;
        $product->PROD_VOLUME = 0.0;
        $product->save();

        $precalc->ID_STUDY          = $study->ID_STUDY;
        $precalc->W_INTERVAL        = (float)MinMax::where('LIMIT_ITEM', $this->value->MIN_MAX_INTERVAL_WIDTH)->first()->DEFAULT_VALUE;
        $precalc->L_INTERVAL        = (float)MinMax::where('LIMIT_ITEM', $this->value->MIN_MAX_INTERVAL_LENGHT)->first()->DEFAULT_VALUE;
        $precalc->APPROX_LDG_RATE   = (float)MinMax::where('LIMIT_ITEM', $this->value->MIN_MAX_STDEQP_TEMP_REGULATION_LN2)->first()->DEFAULT_VALUE;
        $precalc->PRECALC_LDG_TR    = (float)MinMax::where('LIMIT_ITEM', $this->value->MIN_MAX_STDEQP_TOP)->first()->DEFAULT_VALUE;
        $precalc->save();

        // 165 : tempRecordPts = new TempRecordPtsBean(userPrivateData, convert, this);
        $tempRecordPtsDef = TempRecordPtsDef::where('ID_USER', $this->auth->user()->ID_USER)->first();
        $tempRecordPts->ID_STUDY = $study->ID_STUDY;
        $tempRecordPts->NB_STEPS = $tempRecordPtsDef->NB_STEPS_DEF;

        $tempRecordPts->AXIS1_PT_TOP_SURF = 0;
        $tempRecordPts->AXIS2_PT_TOP_SURF = 0;
        $tempRecordPts->AXIS3_PT_TOP_SURF = 0;

        $tempRecordPts->AXIS1_PT_INT_PT = 0;
        $tempRecordPts->AXIS2_PT_INT_PT = 0;
        $tempRecordPts->AXIS3_PT_INT_PT = 0;

        $tempRecordPts->AXIS1_PT_BOT_SURF = 0;
        $tempRecordPts->AXIS2_PT_BOT_SURF = 0;
        $tempRecordPts->AXIS3_PT_BOT_SURF = 0;

        $tempRecordPts->AXIS1_AX_2 = 0;
        $tempRecordPts->AXIS1_AX_3 = 0;

        $tempRecordPts->AXIS2_AX_3 = 0;
        $tempRecordPts->AXIS2_AX_1 = 0;

        $tempRecordPts->AXIS3_AX_1 = 0;
        $tempRecordPts->AXIS3_AX_2 = 0;

        $tempRecordPts->AXIS1_PL_2_3 = 0;
        $tempRecordPts->AXIS2_PL_1_3 = 0;
        $tempRecordPts->AXIS3_PL_1_2 = 0;

        $tempRecordPts->CONTOUR2D_TEMP_MIN = 0;
        $tempRecordPts->CONTOUR2D_TEMP_MAX = 0;

        $tempRecordPts->save();

        $study->ID_PROD = $product->ID_PROD;
        $study->ID_PRODUCTION = $production->ID_PRODUCTION;
        $study->ID_PRECALC_LDG_RATE_PRM = $precalc->ID_PRECALC_LDG_RATE_PRM;
        $study->ID_TEMP_RECORD_PTS = $tempRecordPts->ID_TEMP_RECORD_PTS;
        $study->save();

        return $study;
    }

    public function updateStudy($idStudy)
    {
        $input = $this->request->all();

        if (isset($input['COMMENT_TXT'])) $comment = $input['COMMENT_TXT'];

        $study = Study::findOrFail($idStudy);
        if ($study) {
            $study->COMMENT_TXT = $comment;
            $study->save();
        }

        return 1;
    }

    public function recentStudies()
    {
        $studies = Study::where('ID_USER', $this->auth->user()->ID_USER)
        ->where('PARENT_ID', '=', 0)
        ->orderBy('ID_STUDY', 'desc')->take(5)->get();

        return $studies;
    }

    // add equipment shape >= 10 error
    function getDefaultPrecision ($productshape, $nbComp, $itemPrecis)
    {
        $limitItem = 0;
        $defaultPrecis = 0.005;
        $FirstItemMonoComp = [
            0, 1151, 1161, 1171, 1181, 1191, 1201, 1211, 1221, 1231, 
            #3D case precision.
            1151, 1161, 1171, 1181, 1191, 1201, 1211, 1221, 1231, 1171, 1181, 1201, 1211, 1171
        ];

        $FirstItemMultiComp = [
            0, 1156, 1166, 1176, 1186, 1196, 1206, 1216, 1226, 1236,
            #3D case precision.
            1156, 1166, 1176, 1186, 1196, 1206, 1216, 1226, 1236, 1176, 1186, 1206, 1216, 1176
        ];

        switch ($productshape) {
            case SLAB:
            case PARALLELEPIPED_STANDING:
            case PARALLELEPIPED_LAYING:
            case CYLINDER_STANDING:
            case CYLINDER_LAYING:
            case SPHERE:
            case CYLINDER_CONCENTRIC_STANDING:
            case CYLINDER_CONCENTRIC_LAYING:
            case PARALLELEPIPED_BREADED:
            case PARALLELEPIPED_STANDING_3D:
            case PARALLELEPIPED_LAYING_3D:
            case CYLINDER_STANDING_3D:
            case CYLINDER_LAYING_3D:
            case SPHERE_3D:
            case CYLINDER_CONCENTRIC_STANDING_3D:
            case CYLINDER_CONCENTRIC_LAYING_3D:
            case PARALLELEPIPED_BREADED_3D:
            case TRAPEZOID_3D:
            case OVAL_STANDING_3D:
            case OVAL_LAYING_3D:
            case OVAL_CONCENTRIC_STANDING_3D:
            case OVAL_CONCENTRIC_LAYING_3D:
            case SEMI_CYLINDER_3D:
                if ($nbComp == 1) {
                    $limitItem = $FirstItemMonoComp[$productshape] + $itemPrecis - 1;
                } else {
                    $limitItem = $FirstItemMultiComp[$productshape] + $itemPrecis - 1;
                }
                break;

            default:
                $limitItem = $FirstItemMonoComp[0] + $itemPrecis - 1;
                break;

        }

        $defaultPrecis = MinMax::where('LIMIT_ITEM', $limitItem)->first()->DEFAULT_VALUE;

        return ($defaultPrecis);
    }

    public function getDefaultTimeStep ($itemTimeStep)
    {
        $limitItem = 0;
        $defaultTimeStep = 1;
        $FirstItem = 1241;

        $limitItem = $FirstItem + $itemTimeStep - 1;

        $defaultTimeStep = MinMax::where('LIMIT_ITEM', $limitItem)->first()->DEFAULT_VALUE;

        return ($defaultTimeStep);
    }

    public function addEquipment($id)
    {
        $input = $this->request->all();

        $study = \App\Models\Study::find($id);

        $productshape = $study->products->first()->productElmts->first()->ID_SHAPE;

        $mmTop = MinMax::where('LIMIT_ITEM', MIN_MAX_STDEQP_TOP)->first();

        $equip = \App\Models\Equipment::findOrFail($input['idEquip']);
        
        $sEquip = new \App\Models\StudyEquipment();
        $sEquip->ID_EQUIP = $equip->ID_EQUIP;
        $sEquip->ID_STUDY = $study->ID_STUDY;
        $sEquip->ENABLE_CONS_PIE = DISABLE_CONS_PIE;
        $sEquip->RUN_CALCULATE = EQUIP_SELECTED;
        
        $sEquip->BRAIN_SAVETODB = ($study->CALCULATION_MODE == 1) ? SAVE_NUM_TO_DB_YES : SAVE_NUM_TO_DB_NO;

        $sEquip->STDEQP_WIDTH = -1;
        $sEquip->STDEQP_LENGTH = -1;
        $sEquip->BRAIN_PROCESS = 0;

        $sEquip->save();

        // @TODO: JAVA initCalculationParameters(idUser, sEquip, productshape, nbComp);
        $defaultCalcParams = CalculationParametersDef::where('ID_USER', $this->auth->user()->ID_USER)->first();
        
        $calcParams = new CalculationParameter();
        $calcParams->ID_STUDY_EQUIPMENTS = $sEquip->ID_STUDY_EQUIPMENTS;
        
        // Fixed alpha value
        $calcParams->STUDY_ALPHA_TOP_FIXED = $defaultCalcParams->STUDY_ALPHA_TOP_FIXED_DEF;
        $calcParams->STUDY_ALPHA_BOTTOM_FIXED = $defaultCalcParams->STUDY_ALPHA_BOTTOM_FIXED_DEF;
        $calcParams->STUDY_ALPHA_LEFT_FIXED = $defaultCalcParams->STUDY_ALPHA_LEFT_FIXED_DEF;
        $calcParams->STUDY_ALPHA_RIGHT_FIXED = $defaultCalcParams->STUDY_ALPHA_RIGHT_FIXED_DEF;
        $calcParams->STUDY_ALPHA_FRONT_FIXED = $defaultCalcParams->STUDY_ALPHA_FRONT_FIXED_DEF;
        $calcParams->STUDY_ALPHA_REAR_FIXED = $defaultCalcParams->STUDY_ALPHA_REAR_FIXED_DEF;

        $calcParams->STUDY_ALPHA_TOP = $defaultCalcParams->STUDY_ALPHA_TOP_DEF;
        $calcParams->STUDY_ALPHA_BOTTOM = $defaultCalcParams->STUDY_ALPHA_BOTTOM_DEF;
        $calcParams->STUDY_ALPHA_RIGHT = $defaultCalcParams->STUDY_ALPHA_RIGHT_DEF;
        $calcParams->STUDY_ALPHA_LEFT = $defaultCalcParams->STUDY_ALPHA_LEFT_DEF;
        $calcParams->STUDY_ALPHA_FRONT = $defaultCalcParams->STUDY_ALPHA_FRONT_DEF;
        $calcParams->STUDY_ALPHA_REAR = $defaultCalcParams->STUDY_ALPHA_REAR_DEF;
        
        // Brain calculation parameters
        $calcParams->HORIZ_SCAN = $defaultCalcParams->HORIZ_SCAN_DEF;
        $calcParams->VERT_SCAN = $defaultCalcParams->VERT_SCAN_DEF;
        $calcParams->MAX_IT_NB = $defaultCalcParams->MAX_IT_NB_DEF;
        $calcParams->RELAX_COEFF = $defaultCalcParams->RELAX_COEFF_DEF;
        
        $calcParams->STOP_TOP_SURF = $defaultCalcParams->STOP_TOP_SURF_DEF;
        $calcParams->STOP_INT = $defaultCalcParams->STOP_INT_DEF;
        $calcParams->STOP_BOTTOM_SURF = $defaultCalcParams->STOP_BOTTOM_SURF_DEF;
        $calcParams->STOP_AVG = $defaultCalcParams->STOP_AVG_DEF;
        
        $calcParams->TIME_STEPS_NB = $defaultCalcParams->TIME_STEPS_NB_DEF;
        $calcParams->STORAGE_STEP = $defaultCalcParams->STORAGE_STEP_DEF;
        $calcParams->PRECISION_LOG_STEP = $defaultCalcParams->PRECISION_LOG_STEP_DEF;
        
        // Get default values according to product and equipment
        $defPrecision = $this->GetDefaultPrecision(
            $productshape,
            $study->products->first()->productElmts->count(),
            $sEquip->equipment->ITEM_PRECIS
        );

        $calcParams->PRECISION_REQUEST = $defPrecision;
        
        $defTimeStep = $this->GetDefaultTimeStep($sEquip->equipment->ITEM_TIME_STEP);
        $calcParams->TIME_STEP = $defTimeStep;
        
        $calcParams->save();

        $sEquip->ID_CALC_PARAMS = $calcParams->ID_CALC_PARAMS;
        $sEquip->save();
        
        $TRType = $equip->ITEM_TR;
        $TSType = $equip->ITEM_TS;
        $VCType = $equip->ITEM_VC;
        
        $tr = []; //double
        $ts = []; //double
        
        if ($equip->STD != EQUIP_STANDARD) {
            // TODO: generated non-standard equipment is not supported yet
            // throw new \Exception('Non standard equipment is not yet supported', 500);
            if ($this->equip->getCapability($equip->CAPABILITIES, 65536)) {
                $tr = $this->getEqpPrmInitialData(array_fill(0, $equip->NB_TR, 0.0), $TRType, false);
                $ts = $this->setEqpPrmInitialData(array_fill(0, $equip->NB_TS, 0.0), $equip->equipGenerations->first()->DWELLING_TIME);
            } else {
                $tr = $this->setEqpPrmInitialData(array_fill(0, $equip->NB_TR, 0.0), $equip->equipGenerations->first()->TEMP_SETPOINT);
                $ts = $this->getEqpPrmInitialData(array_fill(0, $equip->NB_TS, 0.0), $TSType, true);
            }
        } else {
            // standard equipment
            $tr = $this->getEqpPrmInitialData(array_fill(0, $equip->NB_TR, 0.0), $TRType, false);
            $ts = $this->getEqpPrmInitialData(array_fill(0, $equip->NB_TS, 0.0), $TSType, true);
        }

        $vc = $this->getEqpPrmInitialData(array_fill(0, $equip->NB_VC, 0.0), $VCType, false);
        //number of DH = number of TR
        $dh = $this->getEqpPrmInitialData(array_fill(0, $equip->NB_TR, 0.0), 0, false);
        $TExt = doubleval($tr[0]);

        // set equipment data
        // clear first
        StudEqpPrm::where('ID_STUDY_EQUIPMENTS', $sEquip->ID_STUDY_EQUIPMENTS)->delete();

        foreach ($tr as $trValue) {
            $p = new StudEqpPrm();
            $p->ID_STUDY_EQUIPMENTS = $sEquip->ID_STUDY_EQUIPMENTS;
            $p->VALUE_TYPE = REGULATION_TEMP;
            $p->VALUE = $trValue;
            $p->save();
        }

        foreach ($ts as $tsValue) {
            $p = new StudEqpPrm();
            $p->ID_STUDY_EQUIPMENTS = $sEquip->ID_STUDY_EQUIPMENTS;
            $p->VALUE_TYPE = DWELLING_TIME;
            $p->VALUE = $tsValue;
            $p->save();
        }

        foreach ($vc as $vcValue) {
            $p = new StudEqpPrm();
            $p->ID_STUDY_EQUIPMENTS = $sEquip->ID_STUDY_EQUIPMENTS;
            $p->VALUE_TYPE = CONVECTION_SPEED;
            $p->VALUE = $vcValue;
            $p->save();
        }

        foreach ($dh as $dhValue) {
            $p = new StudEqpPrm();
            $p->ID_STUDY_EQUIPMENTS = $sEquip->ID_STUDY_EQUIPMENTS;
            $p->VALUE_TYPE = ENTHALPY_VAR;
            $p->VALUE = $dhValue;
            $p->save();
        }

        if ( true ) {
            $p = new StudEqpPrm();
            $p->ID_STUDY_EQUIPMENTS = $sEquip->ID_STUDY_EQUIPMENTS;
            $p->VALUE_TYPE = EXHAUST_TEMP;
            $p->VALUE = $TExt;
            $p->save();
        }

        $position = POSITION_PARALLEL;
        
        $position = (($productshape == CYLINDER_LAYING)
            || ($productshape == CYLINDER_CONCENTRIC_LAYING)
            || ($productshape == PARALLELEPIPED_LAYING))
            ? POSITION_NOT_PARALLEL
            : POSITION_PARALLEL;

        $lg = new LayoutGeneration();
        $lg->ID_STUDY_EQUIPMENTS = $sEquip->ID_STUDY_EQUIPMENTS;
        $lg->PROD_POSITION = $position;

        $equipWithSpecificSize = ( $sEquip->STDEQP_WIDTH != NO_SPECIFIC_SIZE ) && ($sEquip->STDEQP_LENGTH != NO_SPECIFIC_SIZE );

        if ($equipWithSpecificSize) {
            $lg->SHELVES_TYPE = SHELVES_USERDEFINED;
            $lg->SHELVES_LENGTH = $sEquip->STDEQP_LENGTH;
            $lg->SHELVES_WIDTH  = $sEquip->STDEQP_WIDTH;
        } else if ($sEquip->equipment->BATCH_PROCESS) {
            // default is now euronorme
            $lg->SHELVES_TYPE = SHELVES_EURONORME;
            $lg->SHELVES_LENGTH = SHELVES_EURO_LENGTH;
            $lg->SHELVES_WIDTH = SHELVES_EURO_WIDTH;
        } else {
            $lg->SHELVES_TYPE = SHELVES_USERDEFINED;
            $lg->SHELVES_LENGTH = $sEquip->equipment->EQP_LENGTH;
            $lg->SHELVES_WIDTH = $sEquip->equipment->EQP_WIDTH;
        }
        $lg->LENGTH_INTERVAL = INTERVAL_UNDEFINED;
        $lg->WIDTH_INTERVAL = INTERVAL_UNDEFINED;

        $lg->save();

        $sEquip->ID_LAYOUT_GENERATION = $lg->ID_LAYOUT_GENERATION;
        $sEquip->save();
        
        // $sEquip->setLayoutResults(dbdata . getLayoutResults($sEquip, $mmTop));
        $lr = new LayoutResults();
        $lr->ID_STUDY_EQUIPMENTS = $sEquip->ID_STUDY_EQUIPMENTS;
        $lr->LOADING_RATE = $mmTop->DEFAULT_VALUE;
        $lr->LOADING_RATE_MAX = $mmTop->LIMIT_MAX;
        $lr->save();

        $sEquip->ID_LAYOUT_RESULTS = $lr->ID_LAYOUT_RESULTS;
        $sEquip->save();

        $this->stdeqp->calculateEquipmentParams($sEquip);

        return $sEquip;
    }

    public function getTempRecordPts($id)
    {
        return TempRecordPts::where('ID_STUDY', $id)->first();
    }

    public function getProductElmt($id)
    {
        $productElmt = ProductElmt::where('ID_STUDY', $id)->first();

        return $productElmt;
    }

    private function getEqpPrmInitialData (array $dd, $type, $isTS)
    {
        $mm = MinMax::where('LIMIT_ITEM', $type)->first();
        for ($i=0; $i < count($dd); $i++) {
            if ((($type > 0) && ($mm != null))) {
                $dd[$i] = doubleval($mm->DEFAULT_VALUE);
            } else {
                $dd[$i] = 0.0;
            }
        }
        
        // Special for multi_tr : apply a simple coeff : find something better in the future
        if (($isTS) && (count($dd) > 1)) {
            $MultiTRRatio = MinMax::where('LIMIT_ITEM', MIN_MAX_MULTI_TR_RATIO)->first();
            if ($MultiTRRatio != null) {
                $dd[0] = doubleval( doubleval($dd[0]) * $MultiTRRatio->DEFAULT_VALUE);
            }
        }

        return $dd;
    }

    private function setEqpPrmInitialData($dd, $value) 
    {
        for ($i = 0; $i < count($dd); $i++) {
            $dd[$i] = $value;
        }
        return $dd;
    }

    public function removeStudyEquipment($id, $idEquip) 
    {

        $study = \App\Models\Study::findOrFail($id);

        if (!$study) {
            throw new \Exception('Study not found', 404);
        }

        $equip = \App\Models\StudyEquipment::find($idEquip);

        if (!$equip) {
            throw new \Exception('Study equipment not found', 404);
        }

        // add by oriental Tran
        if ($equip) {
            $this->study->RunStudyCleaner($id, SC_CLEAN_OUTPUT_EQP_PRM, $idEquip);
        }

        foreach ($equip->layoutGenerations as $layoutGen) {
            $layoutGen->delete();
        }

        foreach ($equip->layoutResults as $layoutResult) {
            $layoutResult->delete();
        }

        foreach ($equip->calculationParameters as $calcParam) {
            $calcParam->delete();
        }

        foreach ($equip->pipeGens as $pipeGen) {
            foreach ($pipeGen->lineDefinitions as $lineDef) $lineDef->delete();
            $pipeGen->delete();
        }

        foreach ($equip->pipeRes as $pipeRes) {
            $pipeRes->delete();
        }

        foreach ($equip->exhGens as $exhGen) {
            $exhGen->delete();
        }

        foreach ($equip->exhRes as $exhRes) {
            $exhRes->delete();
        }

        foreach ($equip->economicResults as $ecoRes) {
            $ecoRes->delete();
        }

        foreach ($equip->studEquipprofiles as $eqpProfile) {
            $eqpProfile->delete();
        }

        foreach ($equip->dimaResults as $dimaResult) $dimaResult->delete();

        foreach ($equip->studEqpPrms as $studEqpPrm) {
            $studEqpPrm->delete();
        }

        $equip->delete();

        return 0;
    }

    public function getMeshPoints($id)
    {
        $tfMesh = [];
        for ($i = 0; $i < 3; $i++) {
            $meshPoints = MeshPosition::distinct()->select('MESH_AXIS_POS')->where('ID_STUDY', $id)->where('MESH_AXIS', $i+1)->orderBy('MESH_AXIS_POS')->get();
            $itemName = [];
            foreach ($meshPoints as $row) {
                $item['value'] = $row->MESH_AXIS_POS;
                $item['name'] = $this->convert->meshesUnit($row->MESH_AXIS_POS);
                $itemName[] = $item;
            }
            $tfMesh[$i] = array_reverse($itemName);
        }

        return $tfMesh;
    }

    public function getSelectedMeshPoints($id)
    {
        $selPoints = $this->output->getSelectedMeshPoints($id);
        if (empty($selPoints)) {
            $selPoints = $this->output->getMeshSelectionDef();
        }

        if (!empty($selPoints) && count($selPoints) == 18) {
            $meshPoints['POINT1_X'] = [
                'unit' => $this->convert->meshesUnit($selPoints[0]),
                'value' => $selPoints[0],
            ];
            $meshPoints['POINT1_Y'] = [
                'unit' => $this->convert->meshesUnit($selPoints[1]),
                'value' => $selPoints[1],
            ];
            $meshPoints['POINT1_Z'] = [
                'unit' => $this->convert->meshesUnit($selPoints[2]),
                'value' => $selPoints[2],
            ];
            $meshPoints['POINT2_X'] = [
                'unit' => $this->convert->meshesUnit($selPoints[3]),
                'value' => $selPoints[3],
            ];
            $meshPoints['POINT2_Y'] = [
                'unit' => $this->convert->meshesUnit($selPoints[4]),
                'value' => $selPoints[4],
            ];
            $meshPoints['POINT2_Z'] = [
                'unit' => $this->convert->meshesUnit($selPoints[5]),
                'value' => $selPoints[5],
            ];
            $meshPoints['POINT3_X'] = [
                'unit' => $this->convert->meshesUnit($selPoints[6]),
                'value' => $selPoints[6],
            ];
            $meshPoints['POINT3_Y'] = [
                'unit' => $this->convert->meshesUnit($selPoints[7]),
                'value' => $selPoints[7],
            ];
            $meshPoints['POINT3_Z'] = [
                'unit' => $this->convert->meshesUnit($selPoints[8]),
                'value' => $selPoints[8],
            ];
            $meshPoints['AXE1_X'] = [
                'unit' => $this->convert->meshesUnit($selPoints[9]),
                'value' => $selPoints[9],
            ];
            $meshPoints['AXE1_Y'] = [
                'unit' => $this->convert->meshesUnit($selPoints[10]),
                'value' => $selPoints[10],
            ];
            $meshPoints['AXE2_X'] = [
                'unit' => $this->convert->meshesUnit($selPoints[11]),
                'value' => $selPoints[11],
            ];
            $meshPoints['AXE2_Z'] = [
                'unit' => $this->convert->meshesUnit($selPoints[12]),
                'value' => $selPoints[12],
            ];
            $meshPoints['AXE3_Y'] = [
                'unit' => $this->convert->meshesUnit($selPoints[13]),
                'value' => $selPoints[13],
            ];
            $meshPoints['AXE3_Z'] = [
                'unit' => $this->convert->meshesUnit($selPoints[14]),
                'value' => $selPoints[14],
            ];
            $meshPoints['PLAN_X'] = [
                'unit' => $this->convert->meshesUnit($selPoints[15]),
                'value' => $selPoints[15],
            ];
            $meshPoints['PLAN_Y'] = [
                'unit' => $this->convert->meshesUnit($selPoints[16]),
                'value' => $selPoints[16],
            ];
            $meshPoints['PLAN_Z'] = [
                'unit' => $this->convert->meshesUnit($selPoints[17]),
                'value' => $selPoints[17],
            ];
        }

        return $meshPoints;
    }

    public function getStudyComment($id) 
    {
        $study = Study::find($id);
    }

    public function postStudyComment($id)
    {
        $study = Study::find($id);
        $input = $this->request->json()->all();

        if (!empty($input['comment'])) {
            $study->COMMENT_TXT = $input['comment'];
            $study->save();
        }

        return $study;
    }

    public function updateStudyEquipmentLayout($id)
    {
        $sEquip = StudyEquipment::findOrFail($id);
        $input = $this->request->json()->all();    
        $loadingRate = $input['toc'];

        $layoutGen = $this->stdeqp->getStudyEquipmentLayoutGen($sEquip);
        if ($sEquip->BATCH_PROCESS == 1) {
            $shelvesType = $input['crate'];
            switch ($shelvesType) {
                case 2:
                    $shelvesLength = $layoutGen->SHELVES_LENGTH;
                    $shelvesWidth = $layoutGen->SHELVES_WIDTH;
                    break;
                case 0:
                    $shelvesLength = $layoutGen->SHELVES_EURO_LENGTH;
                    $shelvesWidth = $layoutGen->SHELVES_EURO_WIDTH;
                    break;
                case 1:
                    $shelvesLength = $layoutGen->SHELVES_GASTRO_LENGTH;
                    $shelvesWidth = $layoutGen->SHELVES_GASTRO_WIDTH;
                    break;
                default:
                    $shelvesLength = $layoutGen->SHELVES_EURO_LENGTH;
                    $shelvesWidth = $layoutGen->SHELVES_EURO_WIDTH;
            }

            $layoutGen->NB_SHELVES_PERSO = $input['nbShelves'];
            $layoutGen->SHELVES_TYPE = $shelvesType;
            $layoutGen->SHELVES_LENGTH = $this->convert->shelvesWidth($shelvesLength, ['save' => true]);
            $layoutGen->SHELVES_WIDTH = $this->convert->shelvesWidth($shelvesWidth, ['save' => true]);
        }
        
        // $layoutGen->ORI
        $layoutGen->PROD_POSITION = $input['orientation'];
       
        $oldLoadingRate = (double) $this->convert->toc($sEquip->layoutResults->first()->LOADING_RATE);

        if ($loadingRate != $oldLoadingRate) {
            $layoutResult = LayoutResults::where('ID_STUDY_EQUIPMENTS', $id)->first();
            $layoutResult->LOADING_RATE = $this->convert->toc($loadingRate, ['save' => true]);
            $layoutResult->LEFT_RIGHT_INTERVAL = 0.0;
            $layoutResult->NUMBER_IN_WIDTH = 0.0;
            $layoutResult->NUMBER_PER_M = 0.0;
            $layoutResult->save();

            $layoutGen->WIDTH_INTERVAL = -2.0;
            $layoutGen->LENGTH_INTERVAL = -2.0;
        } else {
            $layoutGen->WIDTH_INTERVAL = $this->convert->prodDimensionSave($input['widthInterval']);
            $layoutGen->LENGTH_INTERVAL = $this->convert->prodDimensionSave($input['lengthInterval']);
        }

        unset($layoutGen->SHELVES_EURO_LENGTH);
        unset($layoutGen->SHELVES_EURO_WIDTH);
        unset($layoutGen->SHELVES_GASTRO_LENGTH);
        unset($layoutGen->SHELVES_GASTRO_WIDTH);
        $layoutGen->save();

        $this->stdeqp->calculateEquipmentParams($sEquip);
        if ($input['studyClean'] == true) {
            $this->stdeqp->applyStudyCleaner($sEquip->ID_STUDY, $id, SC_CLEAN_OUPTUT_LAYOUT_CHANGED);
        } else {
            $ret = $this->stdeqp->runStudyCleaner($sEquip->ID_STUDY, $id, SC_CLEAN_OUTPUT_SIZINGCONSO);//mode: 51
            if ($ret == 0) {
                $this->stdeqp->runSizingCalculator($sEquip->ID_STUDY, $id);
            }
        }
    }

    public function getChainingModel($id) 
    {
        $study = Study::findOrFail($id);
        
        // $chaining = [
        //     'studyName' => '',
        //     'parent' => [
        //         'name' => '',
        //         'equipName' => ''
        //     ],
        //     'children' => [
        //         [
        //             'name' => '',
        //             'equipName' => ''
        //         ]
        //     ],
        //     'packingPreventChildComp' => true/false
        // ];

        $chaining = [];
        $chaining['studyName'] = $study->STUDY_NAME;
        $chaining['parent'] = null;
        $parent = null;

        if ($study->PARENT_ID != 0) {
            $parent = Study::findOrFail($study->PARENT_ID);
            $chaining['parent'] = [];
            $chaining['parent']['id'] = $parent->ID_STUDY;
            $chaining['parent']['name'] = $parent->STUDY_NAME;
            $parentStdEquip = StudyEquipment::findOrFail($study->PARENT_STUD_EQP_ID);
            $chaining['parent']['equipName'] = $parentStdEquip->EQUIP_NAME;
        }

        $children = null;
        if ($study->HAS_CHILD != 0) {
            $children = Study::where('PARENT_ID', $study->ID_STUDY)->get();
            if (count($children) > 0) {
                $chaining['children'] = [];
                foreach ($children as $child) {
                    $equip = StudyEquipment::findOrFail($child->PARENT_STUD_EQP_ID);
                    array_push($chaining['children'], [
                        'id' => $child->ID_STUDY,
                        'name' => $child->STUDY_NAME,
                        'equipName' => $equip->EQUIP_NAME
                    ]);
                }
            }
        }

        $chaining['packingPreventChildComp'] = false;

        if ($study->PARENT_ID != 0) {

            $productShape = $study->products->first()->productElmts->first()->ID_SHAPE;

            if ($this->packing->isTopPackInParent($study)) {
                $chaining['packingPreventChildComp'] = true;
            }

            if ($this->packing->isSidePackInParent($study)) {
                switch ($productShape) {
                    case SLAB:
                    case PARALLELEPIPED_LAYING:
                    case CYLINDER_LAYING:
                    case CYLINDER_CONCENTRIC_STANDING:
                    case CYLINDER_CONCENTRIC_LAYING:
                    case PARALLELEPIPED_BREADED:
                    case SPHERE:
                        $chaining['packingPreventChildComp'] = true;
                        break;
                    case PARALLELEPIPED_STANDING:
                    case CYLINDER_STANDING:
                        break;
                }
            }

            if ($this->packing->isBottomPackInParent($study)) {
                switch ($productShape) {
                    case PARALLELEPIPED_LAYING:
                    case CYLINDER_LAYING:
                    case CYLINDER_CONCENTRIC_LAYING:
                    case PARALLELEPIPED_BREADED:
                    case SPHERE:
                        $chaining['packingPreventChildComp'] = true;
                        break;
                    case SLAB:
                    case PARALLELEPIPED_STANDING:
                    case CYLINDER_STANDING:
                    case CYLINDER_CONCENTRIC_STANDING:
                        break;
                }
            }
        }

        return $chaining;
    }


    public function getlocationAxisSelected($id)
    {
        $tempRecordPts = TempRecordPts::where("ID_STUDY", $id)->first();

        $axisTemp["top"] = [
            [
                'name' => $this->convert->meshesUnit($tempRecordPts->AXIS1_PT_TOP_SURF),
                'value' => $tempRecordPts->AXIS1_PT_TOP_SURF
            ],
            [
                'name' => $this->convert->meshesUnit($tempRecordPts->AXIS2_PT_TOP_SURF),
                'value' => $tempRecordPts->AXIS2_PT_TOP_SURF
            ],
            [
                'name' => $this->convert->meshesUnit($tempRecordPts->AXIS3_PT_TOP_SURF),
                'value' => $tempRecordPts->AXIS3_PT_TOP_SURF
            ],
        ];

        $axisTemp["int"] = [
            [
                'name' => $this->convert->meshesUnit($tempRecordPts->AXIS1_PT_INT_PT),
                'value' => $tempRecordPts->AXIS1_PT_INT_PT
            ],
            [
                'name' => $this->convert->meshesUnit($tempRecordPts->AXIS2_PT_INT_PT),
                'value' => $tempRecordPts->AXIS2_PT_INT_PT
            ],
            [
                'name' => $this->convert->meshesUnit($tempRecordPts->AXIS3_PT_INT_PT),
                'value' => $tempRecordPts->AXIS3_PT_INT_PT
            ]
        ];

        $axisTemp["bot"] = [
            [
                'name' => $this->convert->meshesUnit($tempRecordPts->AXIS1_PT_BOT_SURF),
                'value' => $tempRecordPts->AXIS1_PT_BOT_SURF
            ],
            [
                'name' => $this->convert->meshesUnit($tempRecordPts->AXIS2_PT_BOT_SURF),
                'value' => $tempRecordPts->AXIS2_PT_BOT_SURF
            ],
            [
                'name' => $this->convert->meshesUnit($tempRecordPts->AXIS3_PT_BOT_SURF),
                'value' => $tempRecordPts->AXIS3_PT_BOT_SURF
            ]
        ];

        $axisTemp["axe1"] = [
            [
                'name' => 0.0,
                'value' => 0.0
            ],
            [
                'name' => $this->convert->meshesUnit($tempRecordPts->AXIS2_AX_1),
                'value' => $tempRecordPts->AXIS2_AX_1
            ],
            [
                'name' => $this->convert->meshesUnit($tempRecordPts->AXIS3_AX_1),
                'value' => $tempRecordPts->AXIS3_AX_1
            ]
        ];

        $axisTemp["axe2"] = [
            [
                'name' => $this->convert->meshesUnit($tempRecordPts->AXIS1_AX_2),
                'value' => $tempRecordPts->AXIS1_AX_2
            ],
            [
                'name' => 0.0,
                'value' => 0.0
            ],
            [
                'name' => $this->convert->meshesUnit($tempRecordPts->AXIS3_AX_2),
                'value' => $tempRecordPts->AXIS3_AX_2
            ]
        ];

        $axisTemp["axe3"] = [
            [
                'name' => $this->convert->meshesUnit($tempRecordPts->AXIS1_AX_3),
                'value' => $tempRecordPts->AXIS1_AX_3
            ],
            [
                'name' => $this->convert->meshesUnit($tempRecordPts->AXIS2_AX_3),
                'value' => $tempRecordPts->AXIS2_AX_3
            ],
            [
                'name' => 0.0,
                'value' => 0.0
            ]
        ];

        $axisTemp["plan"] = [
            [
                'name' => $this->convert->meshesUnit($tempRecordPts->AXIS1_PL_2_3),
                'value' => $tempRecordPts->AXIS1_PL_2_3
            ],
            [
                'name' => $this->convert->meshesUnit($tempRecordPts->AXIS2_PL_1_3),
                'value' => $tempRecordPts->AXIS2_PL_1_3
            ],
            [
                'name' => $this->convert->meshesUnit($tempRecordPts->AXIS3_PL_1_2),
                'value' => $tempRecordPts->AXIS3_PL_1_2
            ]
        ];


        return $axisTemp;
    }

    public function saveLocationAxis($id)
    {
        $input = $this->request->all();
        $nbSteps = $input['NB_STEPS'];
        
        if ($this->study->isMyStudy($id)) {
            $idStudyEquipment = $input['ID_STUDY_EQUIPMENTS'];
            $productElmt = ProductElmt::where('ID_STUDY', $id)->first();
            $shape = $productElmt->SHAPECODE;
            $layoutGen = LayoutGeneration::where('ID_STUDY_EQUIPMENTS', $idStudyEquipment)->first();
            $orientation = $layoutGen->PROD_POSITION;
            $tempRecordPts =  TempRecordPts::where('ID_STUDY', $id)->first();
            $report =  Report::where('ID_STUDY', $id)->first();
            

            $pointTop = ['x' => $input['POINT_TOP_X'], 'y' => $input['POINT_TOP_Y'], 'z' => $input['POINT_TOP_Z']];
            $pointInt = ['x' => $input['POINT_INT_X'], 'y' => $input['POINT_INT_Y'], 'z' => $input['POINT_INT_Z']];
            $pointBot = ['x' => $input['POINT_BOT_X'], 'y' => $input['POINT_BOT_Y'], 'z' => $input['POINT_BOT_Z']];

            $axis = [
                [$input['AXIS_AXE1_X'], $input['AXIS_AXE1_Y'], $input['AXIS_AXE1_Z']],
                [$input['AXIS_AXE2_X'], $input['AXIS_AXE2_Y'], $input['AXIS_AXE2_Z']],
                [$input['AXIS_AXE3_X'], $input['AXIS_AXE3_Y'], $input['AXIS_AXE3_Z']]
            ];

            $plan = ['x' => $input['PLAN_X'], 'y' => $input['PLAN_Y'], 'z' => $input['PLAN_Z']];

            $pointTopResult = $this->study->convertPointForDB($shape, $orientation, $pointTop);
            $pointIntResult = $this->study->convertPointForDB($shape, $orientation, $pointInt);
            $pointBotResult = $this->study->convertPointForDB($shape, $orientation, $pointBot);

            $axisResult = $this->study->convertAxisForDB($shape, $orientation, $axis);

            $planResult = $this->study->convertPointForDB($shape, $orientation, $plan);

            $tempRecordPts->NB_STEPS = $nbSteps;
            $tempRecordPts->AXIS1_PT_TOP_SURF = $pointTopResult[0];
            $tempRecordPts->AXIS2_PT_TOP_SURF = $pointTopResult[1];
            $tempRecordPts->AXIS3_PT_TOP_SURF = $pointTopResult[2];

            $tempRecordPts->AXIS1_PT_INT_PT = $pointIntResult[0];
            $tempRecordPts->AXIS2_PT_INT_PT = $pointIntResult[1];
            $tempRecordPts->AXIS3_PT_INT_PT = $pointIntResult[2];

            $tempRecordPts->AXIS1_PT_BOT_SURF = $pointBotResult[0];
            $tempRecordPts->AXIS2_PT_BOT_SURF = $pointBotResult[1];
            $tempRecordPts->AXIS3_PT_BOT_SURF = $pointBotResult[2];

            if ($report) {
                $report->POINT1_X = $pointTopResult[0];
                $report->POINT1_Y = $pointTopResult[1];
                $report->POINT1_Z = $pointTopResult[2];
                $report->POINT2_X = $pointIntResult[0];
                $report->POINT2_Y = $pointIntResult[1];
                $report->POINT2_Z = $pointIntResult[2];
                $report->POINT3_X = $pointBotResult[0];
                $report->POINT3_Y = $pointBotResult[1];
                $report->POINT3_Z = $pointBotResult[2];
            }

            if (isset($axisResult[0]['y'])) {
                $tempRecordPts->AXIS2_AX_1 = $axisResult[0]['y'];
                if ($report) $report->AXE3_Y = $axisResult[0]['y'];
            }

            if (isset($axisResult[0]['z'])) {
                $tempRecordPts->AXIS3_AX_1 = $axisResult[0]['z'];
                if ($report) $report->AXE3_Z = $axisResult[0]['z'];
            }

            if (isset($axisResult[1]['x'])) {
                $tempRecordPts->AXIS1_AX_2 = $axisResult[1]['x'];
                if ($report) $report->AXE2_X = $axisResult[1]['x'];
            }

            if (isset($axisResult[1]['z'])) {
                $tempRecordPts->AXIS3_AX_2 = $axisResult[1]['z'];
                if ($report) $report->AXE2_Z = $axisResult[1]['z'];
            }

            if (isset($axisResult[2]['x'])) {
                $tempRecordPts->AXIS1_AX_3 = $axisResult[2]['x'];
                if ($report) $report->AXE1_X = $axisResult[2]['x'];
            }

            if (isset($axisResult[2]['y'])) {
                $tempRecordPts->AXIS2_AX_3 = $axisResult[2]['y'];
                if ($report) $report->AXE1_Y = $axisResult[2]['y'];
            }

            $tempRecordPts->AXIS1_PL_2_3 = $planResult[0];
            $tempRecordPts->AXIS2_PL_1_3 = $planResult[1];
            $tempRecordPts->AXIS3_PL_1_2 = $planResult[2];
            if ($report) {
                $report->PLAN_X = $planResult[0];
                $report->PLAN_Y = $planResult[1];
                $report->PLAN_Z = $planResult[2];
            }

            $tempRecordPts->save();
            if ($report) $report->save();
        }

        return 1;
    }

    public function createChildStudy($id) 
    {
        $input = $this->request->all();
        $childStudyName = $input['studyName'];
        $stdEqpId = $input['stdEqpId'];
        $stdEqp = StudyEquipment::find($stdEqpId);

        $study = new Study();
        $temprecordpst = new TempRecordPts();
        $production = new Production();
        $product = new Product();
        $meshgeneration = new MeshGeneration();
        $price = new Price();
        $report = new Report();
        $precalcLdgRatePrm = new PrecalcLdgRatePrm();
        $packing = new Packing();
        $isNumerical = ($stdEqp->BRAIN_TYPE == $this->value->BRAIN_RUN_FULL_YES) ? true : false;
        $isAnalogical = false;
        if ($study->CALCULATION_MODE == $this->value->STUDY_ESTIMATION_MODE) {
            // estimation
            $isAnalogical = $this->stdeqp->isAnalogicResults($stdEqp);
        } else {
            // selected or optimum
            $isAnalogical = $stdEqp->BRAIN_TYPE != $this->value->BRAIN_RUN_NONE ? true : false;
        }
        
        $studyCurrent = Study::findOrFail($id);
        $studyCurrent->HAS_CHILD = 1;
        $studyCurrent->save();
        $input = $this->request->all();

        $duplicateStudy = Study::where('STUDY_NAME', '=', $childStudyName)->count();
        if ($duplicateStudy) {
            return response([
                'code' => 1002,
                'message' => 'This study name already exists, please try another one.'
            ], 406);
        }

        if ($studyCurrent != null) {

            $temprecordpstCurr = TempRecordPts::where('ID_STUDY', $studyCurrent->ID_STUDY)->first();
            $productionCurr = Production::where('ID_STUDY', $studyCurrent->ID_STUDY)->first(); 
            $productCurr = Product::where('ID_STUDY', $studyCurrent->ID_STUDY)->first();
            $priceCurr = Price::where('ID_STUDY', $studyCurrent->ID_STUDY)->first(); 
            $reportCurr = Report::where('ID_STUDY', $studyCurrent->ID_STUDY)->first(); 
            $precalcLdgRatePrmCurr = PrecalcLdgRatePrm::where('ID_STUDY', $studyCurrent->ID_STUDY)->first();
            $packingCurr = Packing::where('ID_STUDY', $studyCurrent->ID_STUDY)->first(); 

            if (!empty($childStudyName)) {
                //duplicate study already exsits
                $study->STUDY_NAME = $childStudyName;
                $study->ID_USER = $this->auth->user()->ID_USER;
                $study->OPTION_ECO = $studyCurrent->OPTION_ECO;
                $study->CALCULATION_MODE = $studyCurrent->CALCULATION_MODE;
                $study->COMMENT_TXT = $studyCurrent->COMMENT_TXT;
                $study->OPTION_CRYOPIPELINE = $studyCurrent->OPTION_CRYOPIPELINE;
                $study->OPTION_EXHAUSTPIPELINE = $studyCurrent->OPTION_EXHAUSTPIPELINE;
                $study->CHAINING_CONTROLS = $studyCurrent->CHAINING_CONTROLS;
                $study->CHAINING_ADD_COMP_ENABLE = $studyCurrent->CHAINING_ADD_COMP_ENABLE;
                $study->CHAINING_NODE_DECIM_ENABLE = $studyCurrent->CHAINING_NODE_DECIM_ENABLE;
                $study->HAS_CHILD = 0;
                $study->PARENT_ID = $id;
                $study->PARENT_STUD_EQP_ID = $stdEqpId;
                $study->CALCULATION_STATUS = $studyCurrent->CALCULATION_STATUS;
                $study->TO_RECALCULATE = $studyCurrent->TO_RECALCULATE;
                $study->save();

                //duplicate TempRecordPts already exsits
                if (count($temprecordpstCurr) > 0) {
                    $temprecordpst = $temprecordpstCurr->replicate();
                    $temprecordpst->ID_STUDY = $study->ID_STUDY;
                    unset($temprecordpst->ID_TEMP_RECORD_PTS);
                    $temprecordpst->save();

                }

                //duplicate Production already exsits
                if (count($productionCurr) > 0) {
                    $production = $productionCurr->replicate();
                    $production->ID_STUDY = $study->ID_STUDY;
                    unset($production->ID_PRODUCTION);
                    $production->save();
                }

                //duplicate initial_Temp already exsits
                DB::insert('insert into INITIAL_TEMPERATURE (ID_PRODUCTION, INITIAL_T, MESH_1_ORDER, MESH_2_ORDER, MESH_3_ORDER) select '
                    . $production->ID_PRODUCTION . ',I.INITIAL_T, I.MESH_1_ORDER, I.MESH_2_ORDER, I.MESH_3_ORDER from INITIAL_TEMPERATURE as I where ID_PRODUCTION = ' . $productionCurr->ID_PRODUCTION);
                
                $shapeId = 0;
                //duplicate Product already exsits
                if ((count($productCurr) > 0)) {
                    $product = $productCurr->replicate();
                    $product->ID_STUDY = $study->ID_STUDY;
                    $product->PROD_ISO = 0; //non-iso thermal
                    unset($product->ID_PROD);
                    $product->save();


                    $meshgenerationCurr = MeshGeneration::where('ID_PROD', $productCurr->ID_PROD)->first(); 
                    $productemltCurr = ProductElmt::where('ID_PROD', $productCurr->ID_PROD)->get(); 
                    //duplicate MeshGeneration already exsits
                    if (count($meshgenerationCurr) > 0) {
                        $meshgeneration = $meshgenerationCurr->replicate();
                        $meshgeneration->ID_PROD = $product->ID_PROD;
                        unset($meshgeneration->ID_MESH_GENERATION);
                        $meshgeneration->save();
                        $product->ID_MESH_GENERATION = $meshgeneration->ID_MESH_GENERATION;
                        $product->save();
                    }

                    if (count($productemltCurr) > 0) {
                        foreach ($productemltCurr as $prodelmtCurr) {
                            $productemlt = new ProductElmt();
                            $productemlt = $prodelmtCurr->replicate();
                            $productemlt->ID_PROD = $product->ID_PROD;
                            $shapeId = $productemlt->ID_SHAPE;
                            unset($productemlt->ID_PRODUCT_ELMT);
                            $productemlt->save();

                            DB::insert('insert into MESH_POSITION (ID_PRODUCT_ELMT, MESH_AXIS, MESH_ORDER, MESH_AXIS_POS) select '
                                . $productemlt->ID_PRODUCT_ELMT . ',M.MESH_AXIS, M.MESH_ORDER, M.MESH_AXIS_POS from MESH_POSITION as M where ID_PRODUCT_ELMT = '
                                . $prodelmtCurr->ID_PRODUCT_ELMT);
                        }
                    }
                }
                    
                //duplicate Price already exsits
                if (count($priceCurr) > 0) {
                    $price = $priceCurr->replicate();
                    $price->ID_STUDY = $study->ID_STUDY;
                    unset($price->ID_PRICE);
                    $price->save();
                }

                //duplicate Report already exsits
                if (count($reportCurr) > 0) {
                    $report = $reportCurr->replicate();
                    $report->ID_STUDY = $study->ID_STUDY;
                    unset($report->ID_REPORT);
                    $report->save();
                }

                if (count($precalcLdgRatePrmCurr) > 0) {
                    $precalcLdgRatePrm = $precalcLdgRatePrmCurr->replicate();
                    $precalcLdgRatePrm->ID_STUDY = $study->ID_STUDY;
                    unset($precalcLdgRatePrm->ID_PRECALC_LDG_RATE_PRM);
                    $precalcLdgRatePrm->save();
                }

                if (count($packingCurr) > 0) {
                    $packing = $packingCurr->replicate();
                    $packing->ID_STUDY = $study->ID_STUDY;
                    unset($packing->ID_PACKING);
                    $packing->save();
            
                    $packingLayerCurr = PackingLayer::where('ID_PACKING', $packingCurr->ID_PACKING)->get();
                    if (count($packingLayerCurr) > 0) {
                        foreach ($packingLayerCurr as $pLayer) {
                            $packingLayer = new PackingLayer();
                            $packingLayer = $pLayer->replicate();
                            $packingLayer->ID_PACKING = $packing->ID_PACKING;
                            unset($packingLayer->ID_PACKING_LAYER);
                            $packingLayer->save();
                        }
                    }
                }

                //INITIAL TEMPERATURE
                if ($isNumerical) {
                    // just duplicate => child product = parent product
                    $this->stdeqp->setInitialTempFromNumericalResults($stdEqp, $shapeId, $product, $production);
                } else if ($isAnalogical) {
                    if ($study->CALCULATION_MODE == $this->value->STUDY_ESTIMATION_MODE) {
                        // estimation
                        // just duplicate => child product = parent product
                        $this->stdeqp->setInitialTempFromAnalogicalResults($stdEqp, $shapeId, $product, $production);
                    } else {
                        // selected or optimum
                        // just duplicate => child product = parent product
                        $this->stdeqp->setInitialTempFromSimpleNumericalResults($stdEqp, $shapeId, $product, $production);
                    }
                }

                $study->ID_TEMP_RECORD_PTS = $temprecordpst->ID_TEMP_RECORD_PTS;
                $study->ID_PRODUCTION = $production->ID_PRODUCTION;
                $study->ID_PROD = $product->ID_PROD;
                $study->ID_PRICE = $price->ID_PRICE;
                $study->ID_REPORT = $report->ID_REPORT;
                $study->ID_PRECALC_LDG_RATE_PRM = $precalcLdgRatePrm->ID_PRECALC_LDG_RATE_PRM;
                $study->ID_PACKING = $packing->ID_PACKING;
                $study->save();

                $this->mesh->rebuildMesh($study);

                return $study;

            } else {
                return response([
                    'code' => 1001,
                    'message' => 'Unknown error!'
                ], 406); // Status code here
            }
        } else {
            return response([
                'code' => 1003,
                'message' => 'Study id not found'
            ], 406); // Status code here
        }    

        return 0;
    }
}
    