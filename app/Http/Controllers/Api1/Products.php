<?php

namespace App\Http\Controllers\Api1;

use App\Http\Controllers\Controller;
use App\Models\MeshGeneration;
use App\Models\Product;
use App\Models\ProductElmt;
use Illuminate\Http\Request;
use Illuminate\Contracts\Auth\Factory as Auth;
use App\Kernel\KernelService;
use App\Models\Production;
use App\Models\InitialTemperature;
use App\Models\Study;
use App\Models\StudyEquipment;
use App\Models\ProdcharColor;
use App\Models\ProdcharColorsDef;
use App\Cryosoft\MeshService;
use App\Cryosoft\UnitsConverterService;
use App\Cryosoft\UnitsService;
use App\Cryosoft\ProductService;
use App\Cryosoft\ProductElementsService;
use App\Cryosoft\ValueListService;
use App\Models\MeshPosition;
use App\Models\InitTemp3D;
use App\Models\DimaResults;
use Illuminate\Support\Facades\DB;

class Products extends Controller
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
     * @var \App\Cryosoft\MeshService
     */
    protected $mesh;

    /**
     * @var \App\Cryosoft\ProductService
     */
    protected $product;

    /**
     * @var \App\Cryosoft\ProductElementsService
     */
    protected $productElmts;

    protected $bApplyStudyCleaner = false;
    protected $bCleanerError = false;
    protected $units;

    /**
     * Products constructor.
     * @param Request $request
     * @param Auth $auth
     * @param KernelService $kernel
     */
    public function __construct(Request $request, Auth $auth, KernelService $kernel,
        MeshService $mesh, UnitsConverterService $unit, ProductService $product,
        ProductElementsService $productElmts, ValueListService $values, UnitsService $units)
    {
        $this->request = $request;
        $this->auth = $auth;
        $this->mesh = $mesh;
        $this->kernel = $kernel;
        $this->unit = $unit;
        $this->values = $values;
        $this->product = $product;
        $this->productElmts = $productElmts;
        $this->studies = app('App\\Cryosoft\\StudyService');
        $this->stdeqp = app('App\\Cryosoft\\StudyEquipmentService');
        $this->units = $units;
    }

    public function getProductById($id) 
    {
        $product = Product::find($id);
        return $product;
    }

    public function getElementsByProductId($id) 
    {
        $elements = ProductElmt::where('ID_PROD', $id)->orderBy('SHAPE_POS2','DESC')->get();
        return $elements;
    }

    public function appendElementsToProduct($id)
    {
        $input = $this->request->all();

        $componentId = $input['componentId'];
        $product = Product::find($id);

        $elmt = new ProductElmt();
        $elmt->ID_PROD = $id;
        $elmt->ID_SHAPE = $input['shapeId'];
        $elmt->ID_COMP = $componentId;
        $elmt->PROD_ELMT_ISO = $product->PROD_ISO;
        $elmt->SHAPE_PARAM2 = 0.01; //default 1cm
        $elmt->SHAPE_PARAM4 = 0.01;
        $elmt->SHAPE_PARAM5 = 0.01;

        if (isset($input['dim1']))
            $elmt->SHAPE_PARAM1 = $this->unit->prodDimensionSave($input['dim1']);

        if (isset($input['dim3']))
            $elmt->SHAPE_PARAM3 = $this->unit->prodDimensionSave($input['dim3']);

        $elmt->PROD_ELMT_NAME = "";

        $elmt->ORIGINAL_THICK = 0.0;
        $elmt->PROD_ELMT_WEIGHT = 0.0;
        $elmt->PROD_ELMT_REALWEIGHT = -1;
        $elmt->NODE_DECIM = 0; // @TODO: research more on nodeDecim
        $elmt->INSERT_LINE_ORDER = $product->ID_STUDY;

        $nElements = ProductElmt::where('ID_PROD', $id)->count();
        $elmt->SHAPE_POS2 = doubleval($nElements) / 100.0;
        $elmt->SHAPE_POS1 = 0;
        $elmt->SHAPE_POS3 = 0;

        $elmt->PROD_DEHYD = 0;
        $elmt->PROD_DEHYD_COST = 0;

        $elmt->push();

        $elmtId = $elmt->ID_PRODUCT_ELMT;

        if ($elmt->ID_SHAPE == TRAPEZOID_3D) {
            $updateElmt = $this->productElmts->findProdElmt3D($id, $elmtId, false);
            if ($updateElmt) {
                $elmt->SHAPE_PARAM1 = $updateElmt->SHAPE_PARAM4;
                $elmt->SHAPE_PARAM3 = $updateElmt->SHAPE_PARAM5;
                $elmt->save();
            }
        }

        $study = Study::find($product->ID_STUDY);

        //run studyCleaner 41
        $conf = $this->kernel->getConfig($this->auth->user()->ID_USER, $product->ID_STUDY, -1, 1, 1, 'c:\\temp\\'.$study->ID_STUDY.'\\StudyCleaner_'.$study->ID_STUDY.'_41_.txt');
        $this->kernel->getKernelObject('StudyCleaner')->SCStudyClean($conf, SC_CLEAN_OUTPUT_PRODUCT);

        $studyEquipments = StudyEquipment::where('ID_STUDY', $product->ID_STUDY)->get();
        if (count($studyEquipments) > 0) {
            foreach ($studyEquipments as $studyEquipment) {
                $this->stdeqp->runLayoutCalculator($studyEquipment->ID_STUDY, $studyEquipment->ID_STUDY_EQUIPMENTS);
                $this->stdeqp->runTSCalculator($studyEquipment->ID_STUDY, $studyEquipment->ID_STUDY_EQUIPMENTS);
            }
        }

        $conf = $this->kernel->getConfig($this->auth->user()->ID_USER, $id, $elmtId, 1, 1, 'c:\\temp\\'.$study->ID_STUDY.'\\WeightCalculator_'.$study->ID_STUDY.'_2_.txt');
        $ok1 = $this->kernel->getKernelObject('WeightCalculator')->WCWeightCalculation($product->ID_STUDY,  $conf, 2);

        $conf = $this->kernel->getConfig($this->auth->user()->ID_USER, $id, -1,  1, 1, 'c:\\temp\\'.$study->ID_STUDY.'\\WeightCalculator_'.$study->ID_STUDY.'_3_.txt');
        $ok2 = $this->kernel->getKernelObject('WeightCalculator')->WCWeightCalculation($product->ID_STUDY,  $conf, 3);
        
        $this->mesh->refreshMesh($product->study);

        $layerColor = ProdcharColor::where('ID_PROD', $product->ID_PROD)->where('LAYER_ORDER', $nElements+1)->first();
        $defaultColor = ProdcharColorsDef::where('ID_USER', $this->auth->user()->ID_USER)->where('LAYER_ORDER', $nElements+1)->first();
        
        if (!$layerColor) {
            $layerColor = new ProdcharColor();
            $layerColor->ID_PROD = $product->ID_PROD;
            $layerColor->LAYER_ORDER = $nElements+1;
        }

        if ($defaultColor) $layerColor->ID_COLOR = $defaultColor->ID_COLOR;
        $layerColor->save();

        return compact('ok1', 'ok2', 'elmtId');
    }

    public function updateProductElement($id)
    {
        $input = $this->request->all();

        $idElement = $dim2 = $dim4 = $dim5 = $description = $realmass = null;
        $product = Product::find($id);
        
        if (isset($input['elementId'])) $idElement = intval($input['elementId']);   
        if (isset($input['dim2'])) $dim2 = doubleval($input['dim2']);   
        if (isset($input['dim4'])) $dim4 = doubleval($input['dim4']);
        if (isset($input['dim5'])) $dim5 = doubleval($input['dim5']);

        if (isset($input['description'])) $description = $input['description'];   
        if (isset($input['computedmass'])) $computedmass = doubleval($input['computedmass']);   
        if (isset($input['realmass'])) $realmass = doubleval($input['realmass']);   
        
        $nElements = ProductElmt::find($idElement);
        $oldRealMass = (double) $this->unit->mass($nElements->PROD_ELMT_REALWEIGHT);
        $oldDim2 = (double) $this->unit->prodDimension($nElements->SHAPE_PARAM2);
        $oldDim4 = (double) $this->unit->prodDimension($nElements->SHAPE_PARAM4);
        $oldDim5 = (double) $this->unit->prodDimension($nElements->SHAPE_PARAM5);
        $ok1 = $ok2 = 0;

        $nElements->PROD_ELMT_NAME = $description;
        $nElements->save();
        // $nElements->PROD_ELMT_WEIGHT = $this->unit->mass($computedmass, ['save' => true]);

        if ($nElements->ID_SHAPE == TRAPEZOID_3D) {
            if (($oldDim4 != $dim4) || ($oldDim5 != $dim5)) {
                $nElements->SHAPE_PARAM4 = $this->unit->prodDimension($dim4, ['save' => true]);
                $nElements->SHAPE_PARAM5 = $this->unit->prodDimension($dim5, ['save' => true]);
                $nElements->save();

                $updateElmt = $this->productElmts->findProdElmt3D($id, $idElement, true);
                if ($updateElmt) {
                    $updateElmt->SHAPE_PARAM1 = $this->unit->prodDimension($dim4, ['save' => true]);
                    $updateElmt->SHAPE_PARAM3 = $this->unit->prodDimension($dim5, ['save' => true]);
                    $updateElmt->save();
                }

                // Run studyCleaner 41
                $conf = $this->kernel->getConfig($this->auth->user()->ID_USER, $product->ID_STUDY, -1);
                $this->kernel->getKernelObject('StudyCleaner')->SCStudyClean($conf, SC_CLEAN_OUTPUT_PRODUCT);

                $studyEquipments = StudyEquipment::where('ID_STUDY', $product->ID_STUDY)->get();
                if (count($studyEquipments) > 0) {
                    foreach ($studyEquipments as $studyEquipment) {
                        $this->stdeqp->runLayoutCalculator($studyEquipment->ID_STUDY, $studyEquipment->ID_STUDY_EQUIPMENTS);
                        $this->stdeqp->runTSCalculator($studyEquipment->ID_STUDY, $studyEquipment->ID_STUDY_EQUIPMENTS);
                    }
                }

                $conf = $this->kernel->getConfig($this->auth->user()->ID_USER, $id, $idElement);
                $ok1 = $this->kernel->getKernelObject('WeightCalculator')->WCWeightCalculation($product->ID_STUDY, $conf, 2);

                $conf = $this->kernel->getConfig($this->auth->user()->ID_USER, $id);
                $ok2 = $this->kernel->getKernelObject('WeightCalculator')->WCWeightCalculation($product->ID_STUDY, $conf, 3);
            }
        }

        if ($oldDim2 != $dim2) {
            $nElements->SHAPE_PARAM2 = $this->unit->prodDimension($dim2, ['save' => true]);
            $nElements->save();

            // Run studyCleaner 41
            $conf = $this->kernel->getConfig($this->auth->user()->ID_USER, $product->ID_STUDY, -1);
            $this->kernel->getKernelObject('StudyCleaner')->SCStudyClean($conf, SC_CLEAN_OUTPUT_PRODUCT);

            $studyEquipments = StudyEquipment::where('ID_STUDY', $product->ID_STUDY)->get();
            if (count($studyEquipments) > 0) {
                foreach ($studyEquipments as $studyEquipment) {
                    $this->stdeqp->runLayoutCalculator($studyEquipment->ID_STUDY, $studyEquipment->ID_STUDY_EQUIPMENTS);
                    $this->stdeqp->runTSCalculator($studyEquipment->ID_STUDY, $studyEquipment->ID_STUDY_EQUIPMENTS);
                }
            }

            $conf = $this->kernel->getConfig($this->auth->user()->ID_USER, $id, $idElement);
            $ok1 = $this->kernel->getKernelObject('WeightCalculator')->WCWeightCalculation($product->ID_STUDY, $conf, 2);

            $conf = $this->kernel->getConfig($this->auth->user()->ID_USER, $id);
            $ok2 = $this->kernel->getKernelObject('WeightCalculator')->WCWeightCalculation($product->ID_STUDY, $conf, 3);

            $this->mesh->rebuildMesh($product->study);
        } else if ($oldRealMass != $realmass) {
            $nElements->PROD_ELMT_REALWEIGHT = $this->unit->mass($realmass, ['save' => true]);
            $nElements->save();
            $conf = $this->kernel->getConfig($this->auth->user()->ID_USER, $id, $idElement);
            $ok2 = $this->kernel->getKernelObject('WeightCalculator')->WCWeightCalculation($product->ID_STUDY, $conf, 3);
        }

        return compact('oldDim2', 'dim2', 'ok1', 'ok2', 'idElement');
    }

    public function getProductViewModel($id) 
    {
        $product = Product::findOrFail($id);
        $product->PROD_WEIGHT = $this->unit->mass($product->PROD_WEIGHT);
        $product->PROD_REALWEIGHT = $this->unit->mass($product->PROD_REALWEIGHT);

        $products = ProductElmt::where('ID_PROD', $id)->orderBy('SHAPE_POS2', 'DESC')->get();
        $specificDimension = 0.0;

        $count = count($products);
        $isAddComponentAllowed = true;

        $elements = [];
        if ($count > 0) {
            $shape = $products[0]->ID_SHAPE;
            if (!$this->studies->isAddComponentAllowed($product->ID_STUDY) || !$this->product->updateScreen($shape, count($products))) {
                $isAddComponentAllowed = false;
            }
            foreach ($products as $key => $pr) {
                $elements[] = $pr;

                if ($pr->ID_SHAPE == SPHERE || $pr->ID_SHAPE == CYLINDER_CONCENTRIC_STANDING || $pr->ID_SHAPE == CYLINDER_CONCENTRIC_LAYING || $pr->ID_SHAPE == PARALLELEPIPED_BREADED || $pr->ID_SHAPE == SPHERE_3D || $pr->ID_SHAPE == CYLINDER_CONCENTRIC_STANDING_3D || $pr->ID_SHAPE == CYLINDER_CONCENTRIC_LAYING_3D || $pr->ID_SHAPE == PARALLELEPIPED_BREADED_3D) {
                    if ($key < $count - 1) {
                        $specificDimension += $pr->SHAPE_PARAM2 * 2;
                    } else {
                        $specificDimension += $pr->SHAPE_PARAM2;
                    }
                } else {
                    $specificDimension += $pr->SHAPE_PARAM2;
                }

                $elements[$key]['SHAPE_PARAM1'] = $this->unit->prodDimension($pr->SHAPE_PARAM1);
                $elements[$key]['SHAPE_PARAM2'] = $this->unit->prodDimension($pr->SHAPE_PARAM2);
                $elements[$key]['SHAPE_PARAM3'] = $this->unit->prodDimension($pr->SHAPE_PARAM3);
                $elements[$key]['SHAPE_PARAM4'] = $this->unit->prodDimension($pr->SHAPE_PARAM4);
                $elements[$key]['SHAPE_PARAM5'] = $this->unit->prodDimension($pr->SHAPE_PARAM5);
                $elements[$key]['PROD_ELMT_WEIGHT'] = $this->unit->mass($pr->PROD_ELMT_WEIGHT);
                $elements[$key]['PROD_ELMT_REALWEIGHT'] = $this->unit->mass($pr->PROD_ELMT_REALWEIGHT);
                $elements[$key]['componentName'] = $this->product->getComponentDisplayName($pr->ID_COMP);
                $prodcharColor = ProdcharColor::where('ID_PROD', $id)->where('LAYER_ORDER', $count - $key)->first();
                $elements[$key]['prodcharColor'] = $prodcharColor;
            }
        }
        
        $specificDimension = $this->unit->prodDimension($specificDimension);
        $compFamily = $this->product->getAllCompFamily();
        $subFamily = $this->product->getAllSubFamily();
        $waterPercentList = $this->product->getWaterPercentList();
        
        return compact('product', 'elements', 'specificDimension', 'compFamily', 'subFamily', 'waterPercentList', 'isAddComponentAllowed');
    }

    public function getSubfamily($compFamily)
    {
        return $this->product->getAllSubFamily($compFamily);
    }

    public function removeProductElement($id)
    {
        // $id is product id
        $input = $this->request->all();
        if (!isset($input['elementId'])) {
            throw new \Exception("Error Processing Request", 500);
        }

        $elementId = $input['elementId'];
        $element = \App\Models\ProductElmt::find($elementId);
        $studyId = $element->product->ID_STUDY;

        $study = Study::find($studyId);

        $conf = $this->kernel->getConfig($this->auth->user()->ID_USER, $element->product->ID_STUDY, -1, 1, 1, 'c:\\temp\\'.$study->ID_STUDY.'\\StudyCleaner_'.$study->ID_STUDY.'_41_.txt');
        $this->kernel->getKernelObject('StudyCleaner')->SCStudyClean($conf, SC_CLEAN_OUTPUT_PRODUCT);

        $element->delete();

        $elements = ProductElmt::where('ID_PROD', $id)->orderBy('SHAPE_POS2')->get();

        if (count($elements) > 0) {
            foreach ($elements as $index => $elmt) {
                $elmt->SHAPE_POS2 = floatval($index) / 100;
                $elmt->push();
            }
        } else {
            $product = Product::find($id);
            $product->PROD_WEIGHT = 0;
            $product->PROD_REALWEIGHT = 0;
            $product->save();
        }
        
        //run studyCleaner 41
        $conf = $this->kernel->getConfig($this->auth->user()->ID_USER, $studyId, -1, 1, 1, 'c:\\temp\\'.$study->ID_STUDY.'\\StudyCleaner_'.$study->ID_STUDY.'_41_.txt');
        $this->kernel->getKernelObject('StudyCleaner')->SCStudyClean($conf, SC_CLEAN_OUTPUT_PRODUCT);

        $studyEquipments = StudyEquipment::where('ID_STUDY', $studyId)->get();
        if (count($studyEquipments) > 0) {
            foreach ($studyEquipments as $studyEquipment) {
                $this->stdeqp->runLayoutCalculator($studyEquipment->ID_STUDY, $studyEquipment->ID_STUDY_EQUIPMENTS);
                $this->stdeqp->runTSCalculator($studyEquipment->ID_STUDY, $studyEquipment->ID_STUDY_EQUIPMENTS);
            }
        }
       
        $conf = $this->kernel->getConfig($this->auth->user()->ID_USER, intval($id), -1, 1, 1, 'c:\\temp\\'.$study->ID_STUDY.'\\WeightCalculator_'.$study->ID_STUDY.'_4_.txt');
        $ok = $this->kernel->getKernelObject('WeightCalculator')->WCWeightCalculation($studyId, $conf, 4);

        $this->mesh->rebuildMesh($element->product->study);
        
        return compact('ok', 'product');
    }

    /**
     * @param $id
     * @return array
     * @throws \Exception
     */
    public function getMeshView($id)
    {
        $input = $this->request->all();

        if (!isset($input['ID_STUDY'])) {
            throw new \Exception("Error Processing Request", 500);
        }

        $ID_STUDY = $input['ID_STUDY'];

        /** @var Product $product */
        $product = Product::findOrFail($id);

        if (!$product)
            throw new \Exception("Error Processing Request. Product ID not found", 1);

        $elements = ProductElmt::where('ID_PROD', $product->ID_PROD)->orderBy('SHAPE_POS2', 'DESC')->get();
        $insertLineOrders = ProductElmt::where('INSERT_LINE_ORDER', $ID_STUDY)->first();

        $meshGeneration = $product->meshGenerations->first();
        if ($meshGeneration) {
            if ($elements[0]->ID_SHAPE == 1 || $elements[0]->ID_SHAPE == 6 || $elements[0]->ID_SHAPE == 14) {
                $meshGeneration->MESH_1_SIZE = doubleval(0);
                $meshGeneration->MESH_1_INT = doubleval(0);
            } else {
                $meshGeneration->MESH_1_SIZE = $this->unit->meshesUnit($meshGeneration->MESH_1_SIZE);
                $meshGeneration->MESH_1_INT = $this->unit->meshesUnit($meshGeneration->MESH_1_INT);
            }

            if ($meshGeneration->MESH_3_INT != 0 || $meshGeneration->MESH_3_SIZE !=  0) {
                $meshGeneration->MESH_3_INT = $this->unit->meshesUnit($meshGeneration->MESH_3_INT);
                $meshGeneration->MESH_3_SIZE = $this->unit->meshesUnit($meshGeneration->MESH_3_SIZE);
            } else {
                $meshGeneration->MESH_3_SIZE = doubleval(0);
                $meshGeneration->MESH_3_INT = doubleval(0);
            }

            if ($meshGeneration->MESH_2_INT != 0 || $meshGeneration->MESH_2_SIZE != 0) {
                $meshGeneration->MESH_2_INT = $this->unit->meshesUnit($meshGeneration->MESH_2_INT);
                $meshGeneration->MESH_2_SIZE = $this->unit->meshesUnit($meshGeneration->MESH_2_SIZE);
            } else {
                $meshGeneration->MESH_2_INT = doubleval(0);
                $meshGeneration->MESH_2_SIZE = doubleval(0);
            }
        }

        $elmtMeshPositions = [];
        $productElmtInitTemp = [];
        $initTempPositions = [];
        $nbMeshPointElmt = [];
        $heights = [];
        $averageProductTemp = null;

        // check study chaining insert line order
        if (is_null($insertLineOrders)) {
            $study = Study::find($ID_STUDY);
            $parentStudy = null;
            if ($study) {
                $dimaResult = DimaResults::where('ID_STUDY_EQUIPMENTS', $study->PARENT_STUD_EQP_ID)->first();
                if ($dimaResult) {
                    $averageProductTemp = $this->units->temperature($dimaResult->DIMA_TFP, 1, 0);
                }
            }
        }

        foreach ($elements as $elmt) {
            // shape < 10
            if ($elmt->ID_SHAPE < 10) {
                $meshPositions = MeshPosition::where('ID_PRODUCT_ELMT', $elmt->ID_PRODUCT_ELMT)->orderBy('MESH_ORDER')->get();
                array_push($elmtMeshPositions, $meshPositions);

                $pointMeshOrder2 = $this->product->searchNbPtforElmt($elmt, 2);
                array_push($initTempPositions, $pointMeshOrder2['positions']);
                array_push($nbMeshPointElmt, count($pointMeshOrder2['points']));

                // Fix error reverse
                $elmtInitTemp = $this->productElmts->searchTempMeshPoint($elmt, $pointMeshOrder2['points']);
                
                if (!is_null($elmtInitTemp)) {
                    $elmtInitTemp = array_reverse($elmtInitTemp);
                }

                array_push($productElmtInitTemp, $elmtInitTemp);
            } else {
                if ($meshGeneration) {
                    $pointMeshOrder2 = $this->product->calculateNumberPoint3D($meshGeneration, $elmt);
                    array_push($initTempPositions, $pointMeshOrder2['positions']);
                    array_push($nbMeshPointElmt, count($pointMeshOrder2['positions']));

                    // Fix error reverse
                    $elmtInitTemp = $pointMeshOrder2['points'];
                    if (!is_null($elmtInitTemp)) {
                        $elmtInitTemp = array_reverse($pointMeshOrder2['points']);
                    }
                    
                    array_push($productElmtInitTemp, $elmtInitTemp);
                }
            }

            $shapeParam2 = $this->productElmts->getProdElmtthickness($elmt->ID_PRODUCT_ELMT);
            array_push($heights, $shapeParam2);

            $elmt->componentName = $this->product->getComponentDisplayName($elmt->ID_COMP);
        }

        $productIsoTemp = null;
        
        if ($product->PROD_ISO) {
            // 3D initial temperature
            if (count($elements) > 0 && $elements[0]->ID_SHAPE >= 10) {
                if (InitTemp3D::where('ID_PRODUCT_ELMT', $elements[0]->ID_PRODUCT_ELMT)->count() > 0) {
                    $productIsoTemp = InitTemp3D::where('ID_PRODUCT_ELMT', $elements[0]->ID_PRODUCT_ELMT)->first();
                    if ($productIsoTemp) {
                        $productIsoTemp = $this->unit->temperature($productIsoTemp->INIT_TEMP);
                    }
                }
            } else {
                if (InitialTemperature::where('ID_PRODUCTION', $product->study->ID_PRODUCTION)->count() > 0) {
                    $productIsoTemp = InitialTemperature::where('ID_PRODUCTION', $product->study->ID_PRODUCTION)->first();
                    if ($productIsoTemp) {
                        $productIsoTemp = $this->unit->temperature($productIsoTemp->INITIAL_T);
                    }
                }
            }
        }

        return compact('meshGeneration', 'elements', 'elmtMeshPositions', 'productIsoTemp', 'nbMeshPointElmt', 'productElmtInitTemp', 'initTempPositions', 'heights', 'averageProductTemp');
    }

    public function generateMesh($idProd) 
    {
        $result = 0;
        /** @var Product $product */
        $product = Product::findOrFail($idProd);
        
        if (!$product)
            throw new \Exception("Error Processing Request. Product ID not found", 1);

        $input = $this->request->json()->all();
        $mesh_type = intval($input['mesh_type']);
        // @TODO: implement unit service
        $size1 = floatval($input['size1']) / 1000;
        $size2 = floatval($input['size2']) / 1000;
        $size3 = floatval($input['size3']) / 1000;

        /** @var MeshGeneration $meshGeneration */
        $meshGeneration = $this->mesh->findGenerationByProduct($product);
        $mode = MeshService::MAILLAGE_MODE_REGULAR;

        if ($mesh_type != MeshService::REGULAR_MESH){
            $mode = MeshService::MAILLAGE_MODE_IRREGULAR;
        }
        
        $result = $this->mesh->generate($meshGeneration, $mesh_type, $mode, $size1, $size2, $size3);
        
        return $result;
    }

    /**
     * @param $idProd
     * @return array
     * @throws \Exception
     */
    public function generateDefaultMesh($idProd) 
    {
        /** @var Product $product */
        $product = Product::findOrFail($idProd);

        if (!$product)
            throw new \Exception("Error Processing Request. Product ID not found", 1);

        // run study cleaner, mode 51 change by haipt SC_CLEAN_OUTPUT_SIZINGCONSO => SC_CLEAN_OUTPUT_PRODUCT
        $conf = $this->kernel->getConfig($this->auth->user()->ID_USER, $product->ID_STUDY, -1);
        $this->kernel->getKernelObject('StudyCleaner')->SCStudyClean($conf, SC_CLEAN_OUTPUT_PRODUCT);

        /** @var MeshGeneration $meshGeneration */
        $meshGeneration = $this->mesh->findGenerationByProduct($product);
        
        $this->mesh->generate($meshGeneration, MeshService::REGULAR_MESH, MeshService::MAILLAGE_MODE_REGULAR);
    }

    /**
     * @param $idProd
     * @throws \Exception
     */
    public function initIsoTemperature($idProd) 
    {
        $input = $this->request->all();
        
        /** @var Product $product */
        $product = Product::findOrFail($idProd);        

        if (!$product)
            throw new \Exception("Error Processing Request. Product ID not found", 1);

        $product->PROD_ISO = 1;
        $product->save();
        
        $study = Study::findOrFail($product->ID_STUDY);

        /** @var App\Models\Production $production */
        $production = $study->productions->first();

        // run study cleaner, mode 42
        $conf = $this->kernel->getConfig($this->auth->user()->ID_USER, $product->ID_STUDY, -1);
        $this->kernel->getKernelObject('StudyCleaner')->SCStudyClean($conf, SC_CLEAN_OUTPUT_PRODUCTION);

        // delete all current initial temperature
        InitialTemperature::where('ID_PRODUCTION', $production->ID_PRODUCTION)->delete();

        /** @var MeshGeneration $meshGeneration */
        $meshGeneration = MeshGeneration::where('ID_PROD', $product->ID_PROD)->first();

        // 1D Temp Init
        $nbNode1 = 1;
        $nbNode3 = 1;
        $nbNode2 = $meshGeneration->MESH_2_NB;

        foreach ($product->productElmts as $elmt) {
            $elmt->PROD_ELMT_ISO = 0;
            $elmt->save();
        }

        $listTemp = [];

        for ($x=0; $x < $nbNode1; $x++) {
            for ($y = 0; $y < $nbNode2; $y++) {
                for ($z = 0; $z < $nbNode3; $z++) {
                    /** @var InitialTemperature $t */
                    $t = new InitialTemperature();
                    $t->MESH_1_ORDER = $x;
                    $t->MESH_2_ORDER = $y;
                    $t->MESH_3_ORDER = $z;
                    $t->ID_PRODUCTION = $production->ID_PRODUCTION;
                    $t->INITIAL_T = floatval( $this->unit->temperature($input['initTemp'], ['save' => true]) );
                    array_push($listTemp, $t->toArray());
                }
            }
        }

        $slices = array_chunk($listTemp, 100);
        foreach ($slices as $slice) {
            InitialTemperature::insert($slice);
        }

        $conf = $this->kernel->getConfig($this->auth->user()->ID_USER, $study->ID_STUDY);
        $ktOk = $this->kernel->getKernelObject('KernelToolCalculator')->KTCalculator($conf, 4);
        
        return 0;
    }

    public function initNonIsoTemperature($idProd)
    {
        DB::connection()->disableQueryLog();
        set_time_limit(300);
        ini_set('max_execution_time', 300);
        $bSave = $saveTemp = false;
        $ETATTEMPERATURE = 1;
        $product = Product::findOrFail($idProd);
        $study = $product->study;
        $input = $this->request->json()->all();

        $ldNodeNb1 = $ldNodeNb2 = $ldNodeNb3 = 0;
        
        $this->studies->RunStudyCleaner($study->ID_STUDY, SC_CLEAN_OUTPUT_PRODUCTION);

        // Test if the user has given initial temperature
        if ($this->product->CheckInitialTemperature($product)) {
            //	clean the Initialtemperature Table
            $this->product->DeleteOldInitTemp($product);
            
            // save matrix temperature issue from parent study
            $this->product->saveMatrixTempComeFromParent($product);

            $prodMeshgene = $product->meshGenerations->first();

            $product->PROD_ISO = $this->values->PROD_NOT_ISOTHERM;
            $product->save();
            $idx = -1;

            foreach ($input['elements'] as $pe) {
                $idx++;
                $pe['initTemp'] = $input['productElmtInitTemp'][$idx];
                
                $pb = \App\Models\ProductElmt::findOrFail($pe['ID_PRODUCT_ELMT']);

                if (!$this->studies->isStudyHasParent($study)
                    || ($pb->INSERT_LINE_ORDER == $study->ID_STUDY)) {
                        
                    if ($pe['PROD_ELMT_ISO'] == $this->values->PRODELT_ISOTHERM) {
                        if ($this->studies->isStudyHasParent($study)) {
                            if ($pb->ID_SHAPE != $this->values->PARALLELEPIPED_BREADED) {
                                // propagation on axis 1 and 3
                                $this->productElmts->PropagationTempProdElmtIso($pe, true);
                            } else {
                                // propagation special for breaded
                                $this->productElmts->PropagationTempProdElmtIsoForBreaded($pe);
                            }
                        } else {
                            $this->productElmts->PropagationTempProdElmtIso($pe, false);
                        }

                        $pb->PROD_ELMT_ISO = $this->values->PRODELT_ISOTHERM;
                        $pb->save();
                            
                    } else {
                        if ($pb->ID_SHAPE == $this->values->PARALLELEPIPED_BREADED) {
                            throw new \Exception("BREADED PARALLELEPIPED: product element must be isotherm");
                        }
           
                        $pointMeshOrder2 = $this->product->searchNbPtforElmt($pb, 2)['points'];

                        $t = $pe['initTemp'];

                        $t = array_reverse($t); // Mysql not using
                        
                        $t2 = [];
                        
                        if ((count($t) != count($pointMeshOrder2)) || ($t == null)) {
                            // in case of new mesh generation without T°ini, intiliaze T° in to zero
                            for ($i = 0; $i < count($pointMeshOrder2); $i++) {
                                if ($i < count($t)) {
                                    $t2[] = $t[$i];
                                } else {
                                    $t2[] = 0;
                                }
                            }
                            $t = $t2;
                        }

                        if ($this->studies->isStudyHasParent($study)) {
                            //  3D: dispatch this temp
                            $ldNodeNb1 = $prodMeshgene->MESH_1_NB;
                            $ldNodeNb3 = $prodMeshgene->MESH_3_NB;
                            switch ($pb->ID_SHAPE) {
                                case $this->values->SLAB:
                                case $this->values->SPHERE:
                                    $ldNodeNb1 = $ldNodeNb3 = 1;
                                    break;
                                case $this->values->CYLINDER_STANDING:
                                case $this->values->CYLINDER_CONCENTRIC_LAYING:
                                case $this->values->CYLINDER_LAYING:
                                case $this->values->CYLINDER_CONCENTRIC_STANDING:
                                    $ldNodeNb3 = 1;
                                    break;
                                case $this->values->PARALLELEPIPED_STANDING:
                                case $this->values->PARALLELEPIPED_BREADED:
                                case $this->values->PARALLELEPIPED_LAYING:
                                    break;
                            }
                        } else {
                            $ldNodeNb1 = $ldNodeNb3 = 1;
                        }

                        for ($i = 0; $i < count($t); $i ++) {
                            if (isset($pointMeshOrder2[$i])) {
                                $ldNodeNb2 = $pointMeshOrder2[$i];
                                // ============get the temp
                                $Dt = $t[$i];
                                $this->product->PropagationTempElmt($product, $ldNodeNb1, $ldNodeNb2, $ldNodeNb3, $Dt);
                            }
                        }
                                
                        // save Flag ProdElmt NON ISO to 2
                        $pb->PROD_ELMT_ISO = $this->values->PRODELT_NOT_ISOTHERM;
                        $pb->save();
                    }
                }
            } // end of foreach

            $saveTemp = $this->product->checkRunKernelToolCalculator($study->ID_STUDY);

            if (!$saveTemp) {
                $conf = $this->kernel->getConfig($this->auth->user()->ID_USER, $study->ID_STUDY);
                $this->kernel->getKernelObject('KernelToolCalculator')->KTCalculator($conf, 4);
            }
            
            // indicates that temperature are defined
            $tempIsdefine = true;

            $bSave = true;
        } else {
            // no valid temperature
            throw new \Exception("ERROR_NOVALID_TEMP");
        }
            
        return 1;
    }

    public function updateProductCharColor($id)
    {
        $input = $this->request->all();

        $prodcharColor = ProdcharColor::where('ID_PROD', $id)->where('LAYER_ORDER', $input['LAYER_ORDER'])->first();
        if ($prodcharColor) {
            $prodcharColor->ID_PROD = $id;
            $prodcharColor->ID_COLOR = $input['ID_COLOR'];
            $prodcharColor->LAYER_ORDER = $input['LAYER_ORDER'];
            $prodcharColor->save();
        } else {
            $prodcharColor = new ProdcharColor();
            $prodcharColor->ID_PROD = $id;
            $prodcharColor->ID_COLOR = $input['ID_COLOR'];
            $prodcharColor->LAYER_ORDER = $input['LAYER_ORDER'];
            $prodcharColor->save();
        }
        
        return 1;
    }
}
