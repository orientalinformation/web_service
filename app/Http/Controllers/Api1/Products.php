<?php

namespace App\Http\Controllers\Api1;

use App\Http\Controllers\Controller;
use App\Models\ProductElmt;
use Illuminate\Http\Request;
use Illuminate\Contracts\Auth\Factory as Auth;

use App\Kernel\KernelService;

class Products extends Controller
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
    public function __construct(Request $request, Auth $auth, KernelService $kernel)
    {
        $this->request = $request;
        $this->auth = $auth;
        $this->kernel = $kernel;
    }

    public function getProductById($id) {
        $product = \App\Models\Product::find($id);
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
        $product = \App\Models\Product::find($id);

    	$elmt = new ProductElmt();
    	$elmt->ID_PROD = $id;
    	$elmt->ID_SHAPE = $input['shapeId'];
    	$elmt->ID_COMP = $componentId;
    	$elmt->PROD_ELMT_ISO = $product->PROD_ISO;
        $elmt->SHAPE_PARAM2 = 0.01; //default 1cm
        
        if (isset($input['dim1']))
            $elmt->SHAPE_PARAM1 = $input['dim1'];
            
        if (isset($input['dim3']))
            $elmt->SHAPE_PARAM3 = $input['dim3'];
            
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

    public function getProductViewModel($id) {
        $product = \App\Models\Product::find($id);
        $elements = \App\Models\ProductElmt::where('ID_PROD', $id)->orderBy('SHAPE_POS2', 'DESC')->get();
        $specificDimension = 0.0;

        foreach ($elements as $elmt) {
            $specificDimension += $elmt->SHAPE_PARAM2;
        }

        return compact('product', 'elements', 'specificDimension');
    }

    public function removeProductElement($id)
    {
        // $id is product id
        $input = $this->request->all();
        if (!isset($input['elementId'])) {
            throw new Exception("Error Processing Request", 500);
        }
        
        $elementId = $input['elementId'];
        $element = \App\Models\ProductElmt::find($elementId);
        $studyId = $element->product->ID_STUDY;
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
     * @id: Product's ID
     */
    public function getMeshView($id)
    {
        $product = \App\Models\Product::findOrFail($id);
        if (!$product)
            throw new Exception("Error Processing Request", 1);

        $meshGeneration = $product->meshGenerations->first();

        $elements = $product->productElmts;
        $elmtMeshPositions = [];

        foreach ($elements as $elmt) {
            $meshPositions = \App\Models\MeshPosition::where('ID_PRODUCT_ELMT', $elmt->ID_PRODUCT_ELMT)->get();
            array_push($elmtMeshPositions, $meshPositions);
        }

        return compact('meshGeneration', 'elements', 'elmtMeshPositions');
    }
}
