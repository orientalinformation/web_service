<?php

namespace App\Http\Controllers\Api1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Contracts\Auth\Factory as Auth;
use App\Kernel\KernelService;
use App\Cryosoft\UnitsConverterService;
use App\Cryosoft\ValueListService;

class Studies extends Controller
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
    public function __construct(Request $request, Auth $auth, KernelService $kernel, UnitsConverterService $convert, ValueListService $value)
    {
        $this->request = $request;
        $this->auth = $auth;
        $this->kernel = $kernel;
        $this->convert = $convert;
    }

    public function findStudies()
    {
        $studies = $this->auth->user()->studies;
        return $studies;
    }

    public function deleteStudyById($id)
    {

    }

    public function getStudyById($id)
    {
        $study = \App\Models\Study::find($id);
        return $study;
    }

    public function saveStudyAs($id)
    {

    }

    public function refreshMesh($id) {
        $conf = $this->kernel->getConfig($this->auth->user()->ID_USER, $id, 10);
        
        return $this->kernel->getKernelObject('MeshBuilder')->MBMeshBuild($conf);
    }

    public function openStudy($id)
    {
        $conf = $this->kernel->getConfig($this->auth->user()->ID_USER, intval($id), -1);

        return $this->kernel->getKernelObject('StudyCleaner')->SCStudyClean($conf, 10);
    }

    /**
    * 
    **/
    public function getStudyEquipments($id) 
    {
        $study = \App\Models\Study::find($id);
        return $study->studyEquipments;
    }

    public function newProduct($id) 
    {
        $input = $this->request->all();

        if (!isset($input['name']) || empty($input['name']))
            return 1;

        $study = \App\Models\Study::find($id);
        $product = $study->products;

        if (count($product) == 0) {
            $product = new \App\Models\Product();
            $product->ID_STUDY = $study->ID_STUDY;
        } else {
            $product = $product[0];
        }

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
        $study = \App\Models\Study::find($id);
        $product = $study->product;
        $input = $this->request->all();

        if (isset($input['name']) && !empty($input['name'])) {
            $product->PRODNAME = $input['name'];
            $product->save();
        }

        if (isset($input['dim1']) || isset($input['dim2']) || isset($input['dim3'])) {
            $elements = $product->productElmts;
            if ($elements->count() > 0) {
                foreach ($elements as $elmt) {
                    if (isset($input['dim1'])) $elmt->SHAPE_PARAM1 = floatval($input['dim1']);
                    if (isset($input['dim2'])) $elmt->SHAPE_PARAM2 = floatval($input['dim2']);
                    if (isset($input['dim3'])) $elmt->SHAPE_PARAM3 = floatval($input['dim3']);
                    $elmt->save();
                }
            }
        }
        return 0;
    }

    public function getStudyPackingLayers($id)
    {
        $packing = \App\Models\Packing::where('ID_STUDY', $id)->first();
        $packingLayers = null;

        if ($packing != null) {
            $packingLayers = \App\Models\PackingLayer::where('ID_PACKING', $packing->ID_PACKING)->get();
            for ($i = 0; $i < count($packingLayers); $i++) { 
                $value = $this->convert->unitConvert(16, $packingLayers[$i]->THICKNESS);
                $packingLayers[$i]->THICKNESS = $value;
            }
        }

        return compact('packing', 'packingLayers');
    }

    public function savePacking($id)
    {
        $input = $this->request->all();
        $packing = \App\Models\Packing::where('ID_STUDY', $id)->first();
        if (empty($packing)) {
            $packing = new \App\Models\Packing();
            $packing->ID_SHAPE = $input['packing']['ID_SHAPE'];
            $packing->ID_STUDY = $id;
        }
        if (!isset($input['packing']['NOMEMBMAT']))
            $input['packing']['NOMEMBMAT'] = "";

        $packing->NOMEMBMAT = $input['packing']['NOMEMBMAT'];
        $packing->save();

        $packingLayer = \App\Models\PackingLayer::where('ID_PACKING', $packing->ID_PACKING)->delete();

        foreach ($input['packingLayers'] as $key => $value) {
            $packingLayer = new \App\Models\PackingLayer();
            $packingLayer->ID_PACKING = $packing->ID_PACKING;
            $packingLayer->ID_PACKING_ELMT = $value['ID_PACKING_ELMT'];
            $packingLayer->THICKNESS = $value['THICKNESS'];
            $packingLayer->PACKING_SIDE_NUMBER = $value['PACKING_SIDE_NUMBER'];
            $packingLayer->PACKING_LAYER_ORDER = $value['PACKING_LAYER_ORDER'];
            $packingLayer->save();
        }
        return 1;
    }
}
