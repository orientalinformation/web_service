<?php
/****************************************************************************
 **
 ** Copyright (C) 2017 Oriental Tran.
 ** Contact: dongtp@dfm-engineering.com
 ** Company: DFM-Engineering Vietnam
 **
 ** This file is part of the cryosoft project.
 **
 **All rights reserved.
 ****************************************************************************/
namespace App\Http\Controllers\Api1;

use App\Http\Controllers\Controller;
use App\Models\Equipment;
use App\Models\Study;
use Illuminate\Http\Request;
use Illuminate\Contracts\Auth\Factory as Auth;
use App\Models\StudyEquipment;
use App\Models\TempRecordPts;
use App\Models\TempRecordPtsDef;
use App\Models\Product;
use App\Models\ProductElmt;
use App\Models\MeshPosition;

class InputInitial extends Controller 
{
    /**
     * @var Illuminate\Http\Request
     */
    protected $request;

    /**
     * @var Illuminate\Contracts\Auth\Factory
     */
    protected $auth;
    protected $cal;
    protected $product;
    protected $productElmts;

    public function __construct(\Laravel\Lumen\Application $app)
    {
        $this->app = $app;
        $this->auth = $app['Illuminate\\Contracts\\Auth\\Factory'];
        $this->request = $app['Illuminate\\Http\\Request'];
        $this->cal = $app['App\\Cryosoft\\CalculateService'];
        $this->product = $app['App\\Cryosoft\\ProductService'];
        $this->productElmts = $app['App\\Cryosoft\\ProductElementsService'];
    }

    public function initTempRecordPts($idStudy)
    {
        $percent = $offset = 0;
        $idShape = null;
        $idUser = $this->auth->user()->ID_USER;
        $tempRecordPtsDef = TempRecordPtsDef::where('ID_USER', $idUser)->first();
        $tempRecordPts = TempRecordPts::where('ID_STUDY', $idStudy)->first();
        $product = Product::where('ID_STUDY', $idStudy)->first();

        if ($product) {
            $productElmt = ProductElmt::where('ID_PROD', $product->ID_PROD)->first();
            if ($productElmt) {
                $idShape = $productElmt->ID_SHAPE;
            }
        }

        $listAxis1 = $this->initListPoints($idStudy, 1);
        $listAxis2 = $this->initListPoints($idStudy, 2);
        $listAxis3 = $this->initListPoints($idStudy, 3);

        $sizeList1 = count($listAxis1);
        $sizeList2 = count($listAxis2);
        $sizeList3 = count($listAxis3);

        if ($sizeList1 == 0) {
            array_push($listAxis1, 0.0);
            $sizeList1 = count($listAxis1);
        }

        if ($sizeList3 == 0) {
            array_push($listAxis3, 0.0);
            $sizeList3 = count($listAxis3);
        }

        if (($tempRecordPtsDef) && ($tempRecordPts) && 
            ($sizeList1 > 0) && ($sizeList2 > 0) && ($sizeList3 > 0)) {
            if ($idShape == 7) {
              $temp2 = 0;
              $temp = $tempRecordPtsDef->AXIS1_PT_TOP_SURF_DEF;
              $tempRecordPtsDef->AXIS1_PT_TOP_SURF_DEF = $tempRecordPtsDef->AXIS2_PT_TOP_SURF_DEF;
              $tempRecordPtsDef->AXIS2_PT_TOP_SURF_DEF = $temp;
              
              $temp = $tempRecordPtsDef->AXIS1_PT_INT_PT_DEF;
              $tempRecordPtsDef->AXIS1_PT_INT_PT_DEF = $tempRecordPtsDef->AXIS2_PT_INT_PT_DEF;
              $tempRecordPtsDef->AXIS2_PT_INT_PT_DEF = $temp;
              
              $temp = $tempRecordPtsDef->AXIS1_PT_BOT_SURF_DEF;
              $tempRecordPtsDef->AXIS1_PT_BOT_SURF_DEF = $tempRecordPtsDef->AXIS2_PT_BOT_SURF_DEF;
              $tempRecordPtsDef->AXIS2_PT_BOT_SURF_DEF = $temp;
              
              $temp = $tempRecordPtsDef->AXIS2_AX_1_DEF;
              $temp2 = $tempRecordPtsDef->AXIS3_AX_1_DEF;
              $tempRecordPtsDef->AXIS2_AX_1_DEF = $tempRecordPtsDef->AXIS1_AX_2_DEF;
              $tempRecordPtsDef->AXIS3_AX_1_DEF = $tempRecordPtsDef->AXIS3_AX_2_DEF;
              $tempRecordPtsDef->AXIS1_AX_2_DEF = $temp;
              $tempRecordPtsDef->AXIS3_AX_2_DEF = $temp2;
              
              $temp = $tempRecordPtsDef->AXIS1_PL_2_3_DEF;
              $tempRecordPtsDef->AXIS1_PL_2_3_DEF = $tempRecordPtsDef->AXIS2_PL_1_3_DEF;
              $tempRecordPtsDef->AXIS2_PL_1_3_DEF = $temp;
            }

            if (($tempRecordPtsDef->AXIS1_PT_TOP_SURF_DEF == 0) && ($tempRecordPtsDef->AXIS2_PT_TOP_SURF_DEF == 0) && ($tempRecordPtsDef->AXIS3_PT_TOP_SURF_DEF == 0)) {
                switch ($idShape) {
                    case 4:
                    case 5:
                        $tempRecordPts->AXIS1_PT_TOP_SURF = $listAxis1[$this->getIndex($sizeList1, 0)];
                        $tempRecordPts->AXIS2_PT_TOP_SURF = $listAxis2[$this->getIndex($sizeList2, 100)];
                        break;

                    case 7:
                        $tempRecordPts->AXIS1_PT_TOP_SURF = $listAxis1[$this->getIndex($sizeList1, 100)];
                        $tempRecordPts->AXIS2_PT_TOP_SURF = $listAxis2[$this->getIndex($sizeList2, 0)];
                        break;
                    case 6:
                    default:
                        $tempRecordPts->AXIS1_PT_TOP_SURF = $listAxis1[$this->getIndex($sizeList1, 50)];
                        $tempRecordPts->AXIS2_PT_TOP_SURF = $listAxis2[$this->getIndex($sizeList2, 100)];
                        break;
                }
                $tempRecordPts->AXIS3_PT_TOP_SURF = $listAxis3[$this->getIndex($sizeList3, 50)];
            } else {
                switch ($idShape) {
                    case 4:
                    case 5:
                        $percent = (abs(floatval($tempRecordPtsDef->AXIS1_PT_TOP_SURF_DEF) - 50) * 2);
                        $offset = $listAxis1[$this->getIndex($sizeList1, $percent)];
                        $tempRecordPts->AXIS1_PT_TOP_SURF = $offset;
                        $tempRecordPts->AXIS2_PT_TOP_SURF = $listAxis2[$this->getIndex($sizeList2, $tempRecordPtsDef->AXIS2_PT_TOP_SURF_DEF)];
                        break;

                    case 7:
                        $percent = (abs(floatval($tempRecordPtsDef->AXIS2_PT_TOP_SURF_DEF) - 50) * 2);
                        $offset = $listAxis2[$this->getIndex($sizeList2, $percent)];
                        $tempRecordPts->AXIS1_PT_TOP_SURF = $listAxis1[$this->getIndex($sizeList1, $tempRecordPtsDef->AXIS1_PT_TOP_SURF_DEF)];
                        $tempRecordPts->AXIS2_PT_TOP_SURF = $offset;
                        break;
                    case 6:
                    default:
                        $tempRecordPts->AXIS1_PT_TOP_SURF = $listAxis1[$this->getIndex($sizeList1, $tempRecordPtsDef->AXIS1_PT_TOP_SURF_DEF)];
                        $tempRecordPts->AXIS2_PT_TOP_SURF = $listAxis2[$this->getIndex($sizeList2, $tempRecordPtsDef->AXIS2_PT_TOP_SURF_DEF)];
                        break;
                }
                $tempRecordPts->AXIS3_PT_TOP_SURF = $listAxis3[$this->getIndex($sizeList3, $tempRecordPtsDef->AXIS3_PT_TOP_SURF_DEF)];
            }

            if (($tempRecordPtsDef->AXIS1_PT_INT_PT_DEF == 0) && ($tempRecordPtsDef->AXIS2_PT_INT_PT_DEF == 0) && ($tempRecordPtsDef->AXIS3_PT_INT_PT_DEF == 0)) {
                switch ($idShape) {
                    case 4:
                    case 5:
                        $tempRecordPts->AXIS1_PT_INT_PT = $listAxis1[$this->getIndex($sizeList1, 0)];
                        $tempRecordPts->AXIS2_PT_INT_PT = $listAxis2[$this->getIndex($sizeList2, 50)];
                        break;

                    case 7:
                        $tempRecordPts->AXIS1_PT_INT_PT = $listAxis1[$this->getIndex($sizeList1, 50)];
                        $tempRecordPts->AXIS2_PT_INT_PT = $listAxis2[$this->getIndex($sizeList2, 0)];
                        break;
                    case 6:
                    default:
                        $tempRecordPts->AXIS1_PT_INT_PT = $listAxis1[$this->getIndex($sizeList1, 50)];
                        $tempRecordPts->AXIS2_PT_INT_PT = $listAxis2[$this->getIndex($sizeList2, 50)];
                        break;
                }
                $tempRecordPts->AXIS3_PT_INT_PT = $listAxis3[$this->getIndex($sizeList3, 50)];
            } else {
                switch ($idShape) {
                    case 4:
                    case 5:
                        $percent = (abs($tempRecordPtsDef->AXIS1_PT_INT_PT_DEF - 50) * 2);
                        $offset = $listAxis1[$this->getIndex($sizeList1, $percent)];
                        $tempRecordPts->AXIS1_PT_INT_PT = $offset;
                        $tempRecordPts->AXIS2_PT_INT_PT = $listAxis2[$this->getIndex($sizeList2, $tempRecordPtsDef->AXIS2_PT_INT_PT_DEF)];
                        break;

                    case 7:
                        $percent = (abs($tempRecordPtsDef->AXIS2_PT_INT_PT_DEF - 50) * 2);
                        $offset = $listAxis2[$this->getIndex($sizeList2, $percent)];
                        $tempRecordPts->AXIS1_PT_INT_PT = $listAxis1[$this->getIndex($sizeList1, $tempRecordPtsDef->AXIS1_PT_INT_PT_DEF)];
                        $tempRecordPts->AXIS2_PT_INT_PT = $offset;
                        break;
                    case 6:
                    default:
                        $tempRecordPts->AXIS1_PT_INT_PT = $listAxis1[$this->getIndex($sizeList1, $tempRecordPtsDef->AXIS1_PT_INT_PT_DEF)];
                        $tempRecordPts->AXIS2_PT_INT_PT = $listAxis2[$this->getIndex($sizeList2, $tempRecordPtsDef->AXIS2_PT_INT_PT_DEF)];
                        break;
                }
                $tempRecordPts->AXIS3_PT_INT_PT = $listAxis3[$this->getIndex($sizeList3, $tempRecordPtsDef->AXIS3_PT_INT_PT_DEF)];
            }

            if (($tempRecordPtsDef->AXIS1_PT_BOT_SURF_DEF == 0) && ($tempRecordPtsDef->AXIS2_PT_BOT_SURF_DEF == 0) && ($tempRecordPtsDef->AXIS3_PT_BOT_SURF_DEF == 0)) {
                switch ($idShape) {
                    case 4:
                    case 5:
                        $tempRecordPts->AXIS1_PT_BOT_SURF = $listAxis1[$this->getIndex($sizeList1, 0)];
                        $tempRecordPts->AXIS2_PT_BOT_SURF = $listAxis2[$this->getIndex($sizeList2, 0)];
                        break;
                    case 7:
                        $tempRecordPts->AXIS1_PT_BOT_SURF = $listAxis1[$this->getIndex($sizeList1, 0)];
                        $tempRecordPts->AXIS2_PT_BOT_SURF = $listAxis2[$this->getIndex($sizeList2, 0)];
                        break;
                    case 6:
                    default:
                        $tempRecordPts->AXIS1_PT_BOT_SURF = $listAxis1[$this->getIndex($sizeList1, 50)];
                        $tempRecordPts->AXIS2_PT_BOT_SURF = $listAxis2[$this->getIndex($sizeList2, 0)];
                        break;
                }
                $tempRecordPts->AXIS3_PT_BOT_SURF = $listAxis3[$this->getIndex($sizeList3, 0)];
            } else {
                switch ($idShape) {
                    case 4:
                    case 5:
                        $percent = (abs($tempRecordPtsDef->AXIS1_PT_BOT_SURF_DEF - 50) * 2);
                        $offset = $listAxis1[($this->getIndex($sizeList1, $percent))];
                        $tempRecordPts->AXIS1_PT_BOT_SURF = $offset;
                        $tempRecordPts->AXIS2_PT_BOT_SURF = $listAxis2[$this->getIndex($sizeList2, $percent)];
                        break;
                    case 7:
                        $percent = (abs($tempRecordPtsDef->AXIS2_PT_BOT_SURF_DEF - 50) * 2);
                        $offset = $listAxis2[($this->getIndex($sizeList2, $percent))];
                        $tempRecordPts->AXIS1_PT_BOT_SURF = $listAxis1[$this->getIndex($sizeList1, $percent)];
                        $tempRecordPts->AXIS2_PT_BOT_SURF = $offset;
                        break;
                    case 6:
                    default:
                        $tempRecordPts->AXIS1_PT_BOT_SURF = $listAxis1[$this->getIndex($sizeList1, $tempRecordPtsDef->AXIS1_PT_BOT_SURF_DEF)];
                        $tempRecordPts->AXIS2_PT_BOT_SURF = $listAxis2[$this->getIndex($sizeList2, $tempRecordPtsDef->AXIS2_PT_BOT_SURF_DEF)];
                        break;
                }
                $tempRecordPts->AXIS3_PT_BOT_SURF = $listAxis3[$this->getIndex($sizeList3, $tempRecordPtsDef->AXIS3_PT_BOT_SURF_DEF)];
            }

            if (($tempRecordPtsDef->AXIS2_AX_1_DEF == 0) && ($tempRecordPtsDef->AXIS3_AX_1_DEF == 0)) {
                switch ($idShape) {
                    case 7:
                        $tempRecordPts->AXIS2_AX_1 = $listAxis2[$this->getIndex($sizeList2, 0)];
                        break;
                    
                    default:
                        $tempRecordPts->AXIS2_AX_1 = $listAxis2[$this->getIndex($sizeList2, 50)];
                        break;
                }
                $tempRecordPts->AXIS3_AX_1 = $listAxis3[$this->getIndex($sizeList3, 50)];
            } else {
                switch ($idShape) {
                    case 7:
                        $percent = (abs($tempRecordPtsDef->AXIS2_AX_1 - 50) * 2);
                        $offset = $listAxis2[$this->getIndex($sizeList2, $percent)];
                        $tempRecordPts->AXIS2_AX_1 = $offset;
                        break;
                    
                    default:
                        $tempRecordPts->AXIS2_AX_1 = $listAxis2[$this->getIndex($sizeList2, $tempRecordPtsDef->AXIS2_AX_1_DEF)];
                        break;
                }
                $tempRecordPts->AXIS3_AX_1 = $listAxis3[$this->getIndex($sizeList3, $tempRecordPtsDef->AXIS3_AX_1_DEF)];
            }

            if (($tempRecordPtsDef->AXIS1_AX_2_DEF == 0) && ($tempRecordPtsDef->AXIS3_AX_2_DEF == 0)) {
                switch ($idShape) {
                    case 4:
                    case 5:
                        $tempRecordPts->AXIS1_AX_2 = $listAxis1[$this->getIndex($sizeList1, 0)];
                        break;
                    
                    default:
                        $tempRecordPts->AXIS1_AX_2 = $listAxis1[$this->getIndex($sizeList1, 50)];
                        break;
                }
                $tempRecordPts->AXIS3_AX_2 = $listAxis3[$this->getIndex($sizeList3, 50)];
            } else {
                switch ($idShape) {
                    case 4:
                    case 5:
                        $percent = (abs($tempRecordPtsDef->AXIS1_AX_2_DEF - 50) * 2);
                        $offset = $listAxis1[$this->getIndex($sizeList1, $percent)];
                        $tempRecordPts->AXIS1_AX_2 = $offset;
                        break;
                    
                    default:
                        $tempRecordPts->AXIS1_AX_2 = $listAxis1[$this->getIndex($sizeList1, $tempRecordPtsDef->AXIS1_AX_2_DEF)];
                        break;
                }
                $tempRecordPts->AXIS3_AX_2 = $listAxis3[$this->getIndex($sizeList3, $tempRecordPtsDef->AXIS3_AX_2_DEF)];
            }

            if (($tempRecordPtsDef->AXIS1_AX_3_DEF == 0) && ($tempRecordPtsDef->AXIS2_AX_3_DEF == 0)) {
                $tempRecordPts->AXIS1_AX_3 = $listAxis1[$this->getIndex($sizeList1, 50)];
                $tempRecordPts->AXIS2_AX_3 = $listAxis2[$this->getIndex($sizeList2, 50)];
            } else {
                $tempRecordPts->AXIS1_AX_3 = $listAxis1[$this->getIndex($sizeList1, $tempRecordPtsDef->AXIS1_AX_3_DEF)];
                $tempRecordPts->AXIS2_AX_3 = $listAxis2[$this->getIndex($sizeList2, $tempRecordPtsDef->AXIS2_AX_3_DEF)];
            }

            if ($tempRecordPtsDef->AXIS1_PL_2_3_DEF == 0) {
                $tempRecordPts->AXIS1_PL_2_3 = $listAxis1[$this->getIndex($sizeList1, 50)];
            } else {
                $tempRecordPts->AXIS1_PL_2_3 = $listAxis1[$this->getIndex($sizeList1, $tempRecordPtsDef->AXIS1_PL_2_3_DEF)];
            }

            if ($tempRecordPtsDef->AXIS2_PL_1_3_DEF == 0) {
                $tempRecordPts->AXIS2_PL_1_3 = $listAxis2[$this->getIndex($sizeList2, 50)];
            } else {
                $tempRecordPts->AXIS2_PL_1_3 = $listAxis2[$this->getIndex($sizeList2, $tempRecordPtsDef->AXIS2_PL_1_3_DEF)];
            }

            if ($tempRecordPtsDef->AXIS3_PL_1_2_DEF == 0) {
                $tempRecordPts->AXIS3_PL_1_2 = $listAxis3[$this->getIndex($sizeList3, $tempRecordPtsDef->AXIS3_PL_1_2_DEF)];
            }
            $tempRecordPts->save();

            $this->cal->saveTempRecordPtsToReport($idStudy);
        }
    }

    private function initListPoints($idStudy, $axe)
    {
        $product = Product::where('ID_STUDY', $idStudy)->first();
        $productElmt = $meshPositions = null;
        $results = array();

        // MeshPosition::join('product_elmt', 'mesh_position.ID_PRODUCT_ELMT', '=', 'product_elmt.ID_PRODUCT_ELMT')
        // ->join('product', 'product_elmt.ID_PROD' , '=', 'product.ID_PROD')
        // ->where('product.ID_STUDY', $id)->where('MESH_AXIS', $axe)->distinct()->orderBy('MESH_AXIS_POS', 'ASC')->get();

        if ($product) {
            $idProd = $product->ID_PROD;
            $productElmt = ProductElmt::where('ID_PROD', $idProd)->first();
            if ($productElmt) {
                $idProductElmt = $productElmt->ID_PRODUCT_ELMT;
                $meshPositions = MeshPosition::select('MESH_AXIS_POS')
                    ->where('MESH_AXIS', '=', $axe)
                    ->where('ID_PRODUCT_ELMT', '=', $idProductElmt)
                    ->orderBy('MESH_AXIS_POS', 'ASC')->distinct()->get();
            }
        }

        if (count($meshPositions) > 0) {
            foreach ($meshPositions as $mesh) {
                array_push($results, floatval($mesh->MESH_AXIS_POS));
            }
        }

        return $results;
    }

    private function getIndex($size, $value) 
    {
        $index  = 0;
        if ($value != 0) {
            $round = round(100 / $value);
            if ($round != 0) {
                $round = round($size / $round);
                $index = ($round >= $size) ? ($size - 1) : $round;
            }
        }

        return $index;
    }

    public function getDataTempoint()
    {
        $input = $this->request->all();

        if (isset($input['ID_PROD'])) $ID_PROD = intval($input['ID_PROD']);
        if (isset($input['INDEX_TEMP'])) $INDEX_TEMP = intval($input['INDEX_TEMP']);

        $product = Product::findOrFail($ID_PROD);

        if (!$product)
            throw new \Exception("Error Processing Request. Product ID not found", 1);
        $elements = ProductElmt::where('ID_PROD', $product->ID_PROD)->orderBy('SHAPE_POS2', 'DESC')->get();

        $elmtMeshPositions = [];
        $productElmtInitTemp = [];
        $initTempPositions = [];
        $nbMeshPointElmt = [];

        foreach ($elements as $elmt) {
            $meshPositions = \App\Models\MeshPosition::where('ID_PRODUCT_ELMT', $elmt->ID_PRODUCT_ELMT)->orderBy('MESH_ORDER')->get();
            array_push($elmtMeshPositions, $meshPositions);

            $pointMeshOrder2 = $this->product->searchNbPtforElmt($elmt, 2);
            array_push($initTempPositions, $pointMeshOrder2['positions']);
            array_push($nbMeshPointElmt, count($pointMeshOrder2['points']));

            $elmtInitTemp = $this->productElmts->searchTempMeshPoint($elmt, $pointMeshOrder2['points']);
            array_push($productElmtInitTemp, $elmtInitTemp);
        }

        $tempPoints = array();
        $item = array();
        if (count($productElmtInitTemp) > 0) {
            for($i = 0; $i < count($productElmtInitTemp[$INDEX_TEMP]); $i++) {
                $item['value'] = $productElmtInitTemp[$INDEX_TEMP][$i];
                array_push($tempPoints, $item);
            }
        }

        if (count($tempPoints) == 0) {
            if (count($initTempPositions) > 0) {
                for ($i = 0; $i < count($initTempPositions[$INDEX_TEMP]); $i++) {
                    $item['value'] = 0;
                    array_push($tempPoints, $item);
                }
            } else {
                for ($i = 0; $i < count($nbMeshPointElmt[$INDEX_TEMP]); $i++) {
                    $item['value'] = 0;
                    array_push($tempPoints, $item);
                }
            }
        }

        $array = [
            'tempPoints' => $tempPoints 
        ];
        return $array;
    }
}