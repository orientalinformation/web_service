<?php

namespace App\Http\Controllers\Api1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Contracts\Auth\Factory as Auth;
use App\Models\Shape;

class Shapes extends Controller
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

    public function getShapes()
    {
        $input = $this->request->all();

        if (isset($input['calMode'])) $calMode = intval($input['calMode']);

        $shapes = Shape::all()->toArray();
        if ($calMode == 0) {
            $shapes = Shape::where('ID_SHAPE', '<', 10)->get();
        } else {
            $shapes = Shape::where('ID_SHAPE', '>=', 10)->get();
        }
        return $shapes;
    }

    public function getShapeById($id)
    {
        $shape = Shape::find($id);
        return $shape;
    }
}
