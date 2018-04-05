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
use App\Cryosoft\MeshService;
use App\Cryosoft\UnitsConverterService;
use App\Cryosoft\ProductService;

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
     * Products constructor.
     * @param Request $request
     * @param Auth $auth
     * @param KernelService $kernel
     */
    public function __construct(Request $request, Auth $auth, KernelService $kernel, MeshService $mesh, UnitsConverterService $unit, ProductService $product)
    {
        $this->request = $request;
        $this->auth = $auth;
        $this->mesh = $mesh;
        $this->kernel = $kernel;
        $this->unit = $unit;
        $this->product = $product;
    }

    /**
     * @param $id
     * @return mixed
     */
    public function getProductById($id) {
        $product = Product::find($id);
        return $product;
    }

    public function getElementsByProductId($id) {
    	$elements = \App\Models\ProductElmt::where('ID_PROD', $id)->orderBy('SHAPE_POS2','DESC')->get();
    	return $elements;
    }

    public function appendElementsToProduct($id)
    {
        // $id is product id
    	$input = $this->request->all();

        $componentId = $input['componentId'];
        $product = Product::find($id);

    	$elmt = new ProductElmt();
    	$elmt->ID_PROD = $id;
    	$elmt->ID_SHAPE = $input['shapeId'];
    	$elmt->ID_COMP = $componentId;
    	$elmt->PROD_ELMT_ISO = $product->PROD_ISO;
        $elmt->SHAPE_PARAM2 = 0.01; //default 1cm

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

        $nElements = \App\Models\ProductElmt::where('ID_PROD', $id)->count();
        $elmt->SHAPE_POS2 = doubleval($nElements) / 100.0;
        $elmt->SHAPE_POS1 = 0;
        $elmt->SHAPE_POS3 = 0;

        $elmt->PROD_DEHYD = 0;
        $elmt->PROD_DEHYD_COST = 0;

    	$elmt->push();

        $elmtId = $elmt->ID_PRODUCT_ELMT;

        $conf = $this->kernel->getConfig($this->auth->user()->ID_USER, $id, $elmtId);
        $ok2 = $this->kernel->getKernelObject('WeightCalculator')->WCWeightCalculation($product->ID_STUDY,  $conf, 2);

        $conf = $this->kernel->getConfig($this->auth->user()->ID_USER, $id);
        $ok2 = $this->kernel->getKernelObject('WeightCalculator')->WCWeightCalculation($product->ID_STUDY,  $conf, 3);

        return compact('ok1', 'ok2', 'elmtId');
    }

    public function updateProductElement($id)
    {
        $input = $this->request->all();

        $product = Product::find($id);

        if (!isset($input['elementId']) || !isset($input['dim2']) || !isset($input['computedmass']) || !isset($input['realmass']))
            throw new \Exception("Error Processing Request", 1);            

        $idElement = $input['elementId'];
        $dim2 = round(doubleval($input['dim2']), 4);
        $description = $input['description'];
        $computedmass = $input['computedmass'];
        $realmass = $input['realmass'];
        
        $nElements = \App\Models\ProductElmt::find($idElement);
        $oldRealMass = $nElements->PROD_ELMT_REALWEIGHT;
        $oldDim2 = round(doubleval($nElements->SHAPE_PARAM2), 4);

        $nElements->PROD_ELMT_NAME = $description;
        $nElements->SHAPE_PARAM2 = $this->unit->prodDimensionSave($dim2);
        $nElements->PROD_ELMT_WEIGHT = $this->unit->massSave($computedmass);
        $nElements->PROD_ELMT_REALWEIGHT = $this->unit->massSave($realmass);
        $nElements->save();

        $ok1 = $ok2 = 0;

        if ($oldDim2 != $dim2) {
            $conf = $this->kernel->getConfig($this->auth->user()->ID_USER, $id, $idElement);
            $ok1 = $this->kernel->getKernelObject('WeightCalculator')->WCWeightCalculation($product->ID_STUDY, $conf, 2);

            $conf = $this->kernel->getConfig($this->auth->user()->ID_USER, $id);
            $ok2 = $this->kernel->getKernelObject('WeightCalculator')->WCWeightCalculation($product->ID_STUDY, $conf, 3);
        } elseif ($oldRealMass != $realmass) {
            $conf = $this->kernel->getConfig($this->auth->user()->ID_USER, $id, $idElement);
            $ok2 = $this->kernel->getKernelObject('WeightCalculator')->WCWeightCalculation($product->ID_STUDY, $conf, 3);
        }

        return compact('oldDim2', 'dim2', 'ok1', 'ok2', 'idElement');
    }

    public function getProductViewModel($id) {
        $product = Product::findOrFail($id);
        $product->PROD_WEIGHT = $this->unit->mass($product->PROD_WEIGHT);
        $product->PROD_REALWEIGHT = $this->unit->mass($product->PROD_REALWEIGHT);


        $products = \App\Models\ProductElmt::where('ID_PROD', $id)->orderBy('SHAPE_POS2', 'DESC')->get();
        $specificDimension = 0.0;

        $elements = [];
        foreach ($products as $key => $pr) {
            $elements[$key] = $pr;
            $specificDimension += $pr->SHAPE_PARAM2;
            $elements[$key]['SHAPE_PARAM1'] = $this->unit->prodDimension($pr->SHAPE_PARAM1);
            $elements[$key]['SHAPE_PARAM2'] = $this->unit->prodDimension($pr->SHAPE_PARAM2);
            $elements[$key]['SHAPE_PARAM3'] = $this->unit->prodDimension($pr->SHAPE_PARAM3);
            $elements[$key]['PROD_ELMT_WEIGHT'] = $this->unit->mass($pr->PROD_ELMT_WEIGHT);
            $elements[$key]['PROD_ELMT_REALWEIGHT'] = $this->unit->mass($pr->PROD_ELMT_REALWEIGHT);
        }

        $specificDimension = $this->unit->prodDimension($specificDimension);
        $compFamily = $this->product->getAllCompFamily();
        $subFamily = $this->product->getAllSubFamily();
        $waterPercentList = $this->product->getWaterPercentList();

        return compact('product', 'elements', 'specificDimension', 'compFamily', 'subFamily', 'waterPercentList');
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

        $conf = $this->kernel->getConfig($this->auth->user()->ID_USER, $element->product->ID_STUDY, -1);
        $this->kernel->getKernelObject('StudyCleaner')->SCStudyClean($conf, SC_CLEAN_OUTPUT_PRODUCT);

        $element->delete();

        $elements = \App\Models\ProductElmt::where('ID_PROD', $id)->orderBy('SHAPE_POS2')->get();

        foreach ($elements as $index => $elmt) {
            $elmt->SHAPE_POS2 = floatval($index) / 100;
            $elmt->push();
        }

        $conf = $this->kernel->getConfig($this->auth->user()->ID_USER, intval($id));
        $ok = $this->kernel->getKernelObject('WeightCalculator')->WCWeightCalculation($studyId, $conf, 4);

        return compact('ok');
    }

    /**
     * @param $id
     * @return array
     * @throws \Exception
     */
    public function getMeshView($id)
    {
        /** @var Product $product */
        $product = Product::findOrFail($id);

        if (!$product)
            throw new \Exception("Error Processing Request. Product ID not found", 1);

        $meshGeneration = $product->meshGenerations->first();
        if ($meshGeneration) {
            $meshGeneration->MESH_1_SIZE = $this->unit->meshesUnit($meshGeneration->MESH_1_SIZE);
            $meshGeneration->MESH_2_SIZE = $this->unit->meshesUnit($meshGeneration->MESH_2_SIZE);
            $meshGeneration->MESH_3_SIZE = $this->unit->meshesUnit($meshGeneration->MESH_3_SIZE);
            $meshGeneration->MESH_1_INT = $this->unit->meshesUnit($meshGeneration->MESH_1_INT);
            $meshGeneration->MESH_2_INT = $this->unit->meshesUnit($meshGeneration->MESH_2_INT);
            $meshGeneration->MESH_3_INT = $this->unit->meshesUnit($meshGeneration->MESH_3_INT);
        }
        $elements = $product->productElmts;
        $elmtMeshPositions = [];

        foreach ($elements as $elmt) {
            $meshPositions = \App\Models\MeshPosition::where('ID_PRODUCT_ELMT', $elmt->ID_PRODUCT_ELMT)->get();
            array_push($elmtMeshPositions, $meshPositions);
        }

        $productIsoTemp = null;
        
        if ($product->PROD_ISO) {
            if (InitialTemperature::where('ID_PRODUCTION', $product->study->ID_PRODUCTION)->count() > 0) {
                $productIsoTemp = InitialTemperature::where('ID_PRODUCTION', $product->study->ID_PRODUCTION)->first();
                if ($productIsoTemp) {
                    $productIsoTemp = $productIsoTemp->INITIAL_T;
                }
            }
        }
        
        return compact('meshGeneration', 'elements', 'elmtMeshPositions', 'productIsoTemp');
    }

    public function generateMesh($idProd) {
        /** @var Product $product */
        $product = Product::findOrFail($idProd);

        if (!$product)
            throw new \Exception("Error Processing Request. Product ID not found", 1);

        $input = $this->request->json()->all();
        $mesh_type = intval($input['mesh_type']);
        // @TODO: implement unit service
        $size1 = floatval($input['size1']) /1000;
        $size2 = floatval($input['size2']) /1000;
        $size3 = floatval($input['size3']) /1000;

        /** @var MeshGeneration $meshGeneration */
        $meshGeneration = $this->mesh->findGenerationByProduct($product);
        $mode = MeshService::MAILLAGE_MODE_REGULAR;

        if ($mesh_type != MeshService::REGULAR_MESH){
            $mode = MeshService::MAILLAGE_MODE_IRREGULAR;
        }
        
        $this->mesh->generate($meshGeneration, $mesh_type, $mode, $size1, $size2, $size3);
        
        return 0;
    }

    /**
     * @param $idProd
     * @return array
     * @throws \Exception
     */
    public function generateDefaultMesh($idProd) {
        /** @var Product $product */
        $product = Product::findOrFail($idProd);

        if (!$product)
            throw new \Exception("Error Processing Request. Product ID not found", 1);

        /** @var MeshGeneration $meshGeneration */
        $meshGeneration = $this->mesh->findGenerationByProduct($product);
        
        $this->mesh->generate($meshGeneration, MeshService::REGULAR_MESH, MeshService::MAILLAGE_MODE_REGULAR);

        // KernelToolsCalculation kerneltools = new KernelToolsCalculation(
        //     CryosoftDB . CRYOSOFT_DB_ODBCNAME,
        //     username,
        //     password,
        //     sLogsDir,
        //     getUserID(),
        //     studyBean . getSelectedStudy(),
        //     0,
        //     0
        // );
    }

    /**
     * @param $idProd
     * @throws \Exception
     */
    public function initTemperature($idProd) {

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

        for ($x=0; $x < $nbNode1; $x++) {
            for ($y = 0; $y < $nbNode2; $y++) {
                for ($z = 0; $z < $nbNode3; $z++) {
                    /** @var InitialTemperature $t */
                    $t = new InitialTemperature();
                    $t->MESH_1_ORDER = $x;
                    $t->MESH_2_ORDER = $y;
                    $t->MESH_3_ORDER = $z;
                    $t->ID_PRODUCTION = $production->ID_PRODUCTION;
                    $t->INITIAL_T = floatval( $input['initTemp'] );
                    $t->save();
                }
            }
        }

        $conf = $this->kernel->getConfig($this->auth->user()->ID_USER, $study->ID_STUDY);
        $ktOk = $this->kernel->getKernelObject('KernelToolCalculator')->KTCalculator($conf, 4);
        
        return 0;
    }
}
