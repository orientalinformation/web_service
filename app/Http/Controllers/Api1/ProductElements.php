<?php
namespace App\Http\Controllers\Api1;

use App\Http\Controllers\Controller;
use App\Models\ProductElmt;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Contracts\Auth\Factory as Auth;
use App\Kernel\KernelService;
use App\Cryosoft\MeshService;

class ProductElements extends Controller
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
    public function __construct(Request $request, Auth $auth, KernelService $kernel, MeshService $mesh)
    {
        $this->request = $request;
        $this->auth = $auth;
        $this->kernel = $kernel;
        $this->mesh = $mesh;
    }

    public function productElementMoveUp($id)
    {
        $moveElement = \App\Models\ProductElmt::find($id);
        
        $oldPosition = round($moveElement->SHAPE_POS2, 2);
        $newPosition = round ( (round($oldPosition * 100) + 1)/100.0, 2);
        $elements = \App\Models\ProductElmt::where('ID_PROD', $moveElement->product->ID_PROD)->orderBy('SHAPE_POS2', 'DESC')->get();
        
        foreach ($elements as $index => $elmt) {
            if (round($elmt->SHAPE_POS2, 2) == $newPosition) {
                $elmt->SHAPE_POS2 = $oldPosition;
                $elmt->save(); 
                break;
            }
        }

        $moveElement->SHAPE_POS2 = $newPosition;
        $moveElement->push();

        // call kernel recalculate weight
        $conf = $this->kernel->getConfig($this->auth->user()->ID_USER, $moveElement->product->ID_PROD);

        $this->kernel->getKernelObject('WeightCalculator')->WCWeightCalculation($moveElement->product->ID_STUDY, $conf, 4);
        return $this->mesh->rebuildMesh($moveElement->product->study);
    }

    public function productElementMoveDown($id)
    {
        $moveElement = \App\Models\ProductElmt::find($id);

        $oldPosition = $moveElement->SHAPE_POS2;
        $newPosition = round( (round($moveElement->SHAPE_POS2 * 100) - 1) / 100.0, 2);
        $elements = \App\Models\ProductElmt::where('ID_PROD', $moveElement->product->ID_PROD)->orderBy('SHAPE_POS2', 'DESC')->get();

        foreach ($elements as $index => $elmt) {
            if (round($elmt->SHAPE_POS2, 2) == $newPosition) {
                $elmt->SHAPE_POS2 = $oldPosition;
                $elmt->save();
                break;
            }
        }

        $moveElement->SHAPE_POS2 = $newPosition;
        $moveElement->push();
        
        $conf = $this->kernel->getConfig($this->auth->user()->ID_USER, intval($moveElement->product->ID_PROD));
        
        $this->kernel->getKernelObject('WeightCalculator')->WCWeightCalculation($moveElement->product->ID_STUDY, $conf, 4);
        return $this->mesh->rebuildMesh($moveElement->product->study);
    }

    public function initProdElmtTemp($id) 
    {
        $elmt = \App\Models\ProductElmt::findOrFail($id);

        $input = $this->request->json()->all();

        return $input;
    }
}
