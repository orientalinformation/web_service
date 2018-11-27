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

class InputInitial3D extends Controller 
{
    protected $request;
    protected $auth;
    protected $cal;
    protected $product;
    protected $productElmts;
    protected $unit;

    public function __construct(\Laravel\Lumen\Application $app)
    {
        $this->app = $app;
        $this->auth = $app['Illuminate\\Contracts\\Auth\\Factory'];
        $this->request = $app['Illuminate\\Http\\Request'];
        $this->cal = $app['App\\Cryosoft\\CalculateService'];
        $this->product = $app['App\\Cryosoft\\ProductService'];
        $this->productElmts = $app['App\\Cryosoft\\ProductElementsService'];
        $this->unit = $app['App\\Cryosoft\\UnitsConverterService'];
    }

    public function initTempRecordPts3D($idStudy)
    {
        $percent = $offset = 0;
        $idShape = null;
        $idUser = $this->auth->user()->ID_USER;
        $tempRecordPtsDef = TempRecordPtsDef::where('ID_USER', $idUser)->first();
        $tempRecordPts = TempRecordPts::where('ID_STUDY', $idStudy)->first();
        $product = Product::where('ID_STUDY', $idStudy)->first();
        
        //dimension of product
        $dim1 = $dim2 = $dim3 = null;

        if ($product) {
            $productElmt = ProductElmt::where('ID_PROD', $product->ID_PROD)->first();
            if ($productElmt) {
                $idShape = $productElmt->ID_SHAPE;
            }

            // get product dimension
            $dim = $this->getProductDim($product->ID_PROD);
            $dim1 = $dim['dim1'];
            $dim2 = $dim['dim2'];
            $dim3 = $dim['dim3'];
        }

        // get position from percent
        if (($tempRecordPtsDef) && ($tempRecordPts)) {
            if ($idShape == CYLINDER_CONCENTRIC_STANDING_3D ||
                $idShape == OVAL_CONCENTRIC_STANDING_3D) {

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
            
            $this->initPoints($tempRecordPtsDef, $tempRecordPts, $dim1, $dim2, $dim3, $idShape);
            $this->initLines($tempRecordPtsDef, $tempRecordPts, $dim1, $dim2, $dim3, $idShape);
            $this->initPlanes($tempRecordPtsDef, $tempRecordPts, $dim1, $dim2, $dim3, $idShape);
            
            $tempRecordPts->save();

            $this->cal->saveTempRecordPtsToReport($idStudy);
        }
    }

    private function getProductDim($idProd)
    {
        $dim1 = $dim2 = $dim3 = 0;
        $results = [];
        $prodElmts = ProductElmt::where('ID_PROD', $idProd)->orderBy('SHAPE_POS2','ASC')->get();

        if (count($prodElmts) > 0) {
            foreach ($prodElmts as $key => $elmt) {
                switch($elmt->ID_SHAPE) {

                    case PARALLELEPIPED_STANDING_3D:
                    case PARALLELEPIPED_LAYING_3D:
                    case TRAPEZOID_3D:

                        if($key == 0) {
                            $dim1 = $elmt->SHAPE_PARAM1;
                            $dim3 = $elmt->SHAPE_PARAM3;
                        } 

                        $dim2 += $elmt->SHAPE_PARAM2;
                        break;

                    case CYLINDER_STANDING_3D:
                    case CYLINDER_LAYING_3D:
                    case SEMI_CYLINDER_3D:

                        if($key == 0) {
                            $dim1 = $elmt->SHAPE_PARAM1;
                            $dim3 = $elmt->SHAPE_PARAM1;
                        } 

                        $dim2 += $elmt->SHAPE_PARAM2;
                        break;

                    case SPHERE_3D :

                        $dim1 += ($key == 0) ? $elmt->SHAPE_PARAM2 : $elmt->SHAPE_PARAM2 * 2.0;
                        $dim2 += ($key == 0) ? $elmt->SHAPE_PARAM2 : $elmt->SHAPE_PARAM2 * 2.0;
                        $dim3 += ($key == 0) ? $elmt->SHAPE_PARAM2 : $elmt->SHAPE_PARAM2 * 2.0;
                        break;

                    case PARALLELEPIPED_BREADED_3D:

                        $dim1 += ($key == 0) ? $elmt->SHAPE_PARAM1 : $elmt->SHAPE_PARAM2 * 2.0;
                        $dim2 += ($key == 0) ? $elmt->SHAPE_PARAM2 : $elmt->SHAPE_PARAM2 * 2.0;
                        $dim3 += ($key == 0) ? $elmt->SHAPE_PARAM3 : $elmt->SHAPE_PARAM2 * 2.0;
                        break;

                    case CYLINDER_CONCENTRIC_STANDING_3D:
                    case CYLINDER_CONCENTRIC_LAYING_3D:

                        if($key == 0) {
                            $dim1 = $elmt->SHAPE_PARAM1;
                            $dim3 = $elmt->SHAPE_PARAM1;
                        } 

                        $dim2 += ($key == 0) ? $elmt->SHAPE_PARAM2 : $elmt->SHAPE_PARAM2 * 2.0;
                        break;

                    case OVAL_STANDING_3D:
                    case OVAL_LAYING_3D:

                        if($key == 0) {
                            $dim1 = $elmt->SHAPE_PARAM1;
                            $dim3 = $elmt->SHAPE_PARAM3;
                        } 
                        $dim2 += $elmt->SHAPE_PARAM2;
                        break;

                    case OVAL_CONCENTRIC_STANDING_3D:
                    case OVAL_CONCENTRIC_LAYING_3D:

                        if($key == 0) {
                            $dim1 = $elmt->SHAPE_PARAM1;
                        } 

                        $dim2 += ($key == 0) ? $elmt->SHAPE_PARAM2 : $elmt->SHAPE_PARAM2 * 2.0;
                        $dim3 += ($key == 0) ? $elmt->SHAPE_PARAM3 : $elmt->SHAPE_PARAM2 * 2.0;
                        break;

                    case SEMI_CYLINDER_3D:

                        break;
                }
            }
        }

        $results = [
            'dim1' => $dim1,
            'dim2' => $dim2,
            'dim3' => $dim3,
        ];

        return $results;
    }

    private function initPoints($tempRecordPtsDef, $tempRecordPts, $dim1, $dim2, $dim3, $idShape)
    {
        // Top points
        if (($tempRecordPtsDef->AXIS1_PT_TOP_SURF_DEF == 0) &&
            ($tempRecordPtsDef->AXIS2_PT_TOP_SURF_DEF == 0) && 
            ($tempRecordPtsDef->AXIS3_PT_TOP_SURF_DEF == 0)) {
            switch ($idShape) {
                case CYLINDER_STANDING_3D:
                case CYLINDER_LAYING_3D:
                case OVAL_STANDING_3D:
                case OVAL_LAYING_3D:
                case SEMI_CYLINDER_3D:
                    $tempRecordPts->AXIS1_PT_TOP_SURF = 0.0;
                    $tempRecordPts->AXIS2_PT_TOP_SURF = 0.0;
                    $tempRecordPts->AXIS3_PT_TOP_SURF = $dim2;
                    break;

                case CYLINDER_CONCENTRIC_STANDING_3D:
                case OVAL_CONCENTRIC_STANDING_3D:
                case CYLINDER_CONCENTRIC_LAYING_3D:
                case OVAL_CONCENTRIC_LAYING_3D:
                    $tempRecordPts->AXIS1_PT_TOP_SURF = 0.0;
                    $tempRecordPts->AXIS2_PT_TOP_SURF = 0.0;
                    $tempRecordPts->AXIS3_PT_TOP_SURF = $dim1;
                    break;

                case SPHERE_3D:
                    $tempRecordPts->AXIS1_PT_TOP_SURF = 0.0;
                    $tempRecordPts->AXIS2_PT_TOP_SURF = 0.0;
                    $tempRecordPts->AXIS3_PT_TOP_SURF = $dim3/2.0;
                    break;

                default:
                    $tempRecordPts->AXIS1_PT_TOP_SURF = $dim1/2.0;
                    $tempRecordPts->AXIS2_PT_TOP_SURF = $dim3/2.0;
                    $tempRecordPts->AXIS3_PT_TOP_SURF = $dim2;
                    break;
            }  
        } else {
            switch ($idShape) {
                case CYLINDER_STANDING_3D:
                case CYLINDER_LAYING_3D:
                case OVAL_STANDING_3D:
                case OVAL_LAYING_3D:
                case SEMI_CYLINDER_3D:

                    $percent1 = floatval($tempRecordPtsDef->AXIS1_PT_TOP_SURF_DEF) - 50;
                    $percent3 = floatval($tempRecordPtsDef->AXIS3_PT_TOP_SURF_DEF) - 50;
                    $percent2 = floatval($tempRecordPtsDef->AXIS2_PT_TOP_SURF_DEF);

                    $tempRecordPts->AXIS1_PT_TOP_SURF = $dim1*$percent1/100.0;
                    $tempRecordPts->AXIS2_PT_TOP_SURF = $dim3*$percent3/100.0;
                    $tempRecordPts->AXIS3_PT_TOP_SURF = $dim2*$percent2/100.0;
                    break;

                case CYLINDER_CONCENTRIC_STANDING_3D:
                case OVAL_CONCENTRIC_STANDING_3D:
                case CYLINDER_CONCENTRIC_LAYING_3D:
                case OVAL_CONCENTRIC_LAYING_3D:

                    $percent1 = floatval($tempRecordPtsDef->AXIS1_PT_TOP_SURF_DEF) - 50;
                    $percent3 = floatval($tempRecordPtsDef->AXIS3_PT_TOP_SURF_DEF) - 50;
                    $percent2 = floatval($tempRecordPtsDef->AXIS2_PT_TOP_SURF_DEF);

                    $tempRecordPts->AXIS1_PT_TOP_SURF = $dim2*$percent1/100.0;
                    $tempRecordPts->AXIS2_PT_TOP_SURF = $dim3*$percent3/100.0;
                    $tempRecordPts->AXIS3_PT_TOP_SURF = $dim1*$percent2/100.0;
                    break;

                case SPHERE_3D:
                    $tempRecordPts->AXIS1_PT_TOP_SURF = 0.0;
                    $tempRecordPts->AXIS2_PT_TOP_SURF = 0.0;
                    $tempRecordPts->AXIS3_PT_TOP_SURF = $dim3/2.0;
                    break;

                default:
                    $percent1 = floatval($tempRecordPtsDef->AXIS1_PT_TOP_SURF_DEF);
                    $percent3 = floatval($tempRecordPtsDef->AXIS3_PT_TOP_SURF_DEF);
                    $percent2 = floatval($tempRecordPtsDef->AXIS2_PT_TOP_SURF_DEF);

                    $tempRecordPts->AXIS1_PT_TOP_SURF = $dim1*$percent1/100.0;
                    $tempRecordPts->AXIS2_PT_TOP_SURF = $dim3*$percent3/100.0;
                    $tempRecordPts->AXIS3_PT_TOP_SURF = $dim2*$percent2/100.0;
                    break;
            }
        }

        // internal point
        if (($tempRecordPtsDef->AXIS1_PT_INT_PT_DEF == 0) && 
            ($tempRecordPtsDef->AXIS2_PT_INT_PT_DEF == 0) && 
            ($tempRecordPtsDef->AXIS3_PT_INT_PT_DEF == 0)) {
            switch ($idShape) {
                case CYLINDER_STANDING_3D:
                case CYLINDER_LAYING_3D:
                case OVAL_STANDING_3D:
                case OVAL_LAYING_3D:
                case SEMI_CYLINDER_3D:
                    $tempRecordPts->AXIS1_PT_INT_PT = 0.0;
                    $tempRecordPts->AXIS2_PT_INT_PT = 0.0;
                    $tempRecordPts->AXIS3_PT_INT_PT = $dim2/2.0;
                    break;

                case CYLINDER_CONCENTRIC_STANDING_3D:
                case OVAL_CONCENTRIC_STANDING_3D:
                case CYLINDER_CONCENTRIC_LAYING_3D:
                case OVAL_CONCENTRIC_LAYING_3D:
                    $tempRecordPts->AXIS1_PT_INT_PT = 0.0;
                    $tempRecordPts->AXIS2_PT_INT_PT = 0.0;
                    $tempRecordPts->AXIS3_PT_INT_PT = $dim1/2.0;
                    break;

                case SPHERE_3D:
                    $tempRecordPts->AXIS1_PT_INT_PT = 0.0;
                    $tempRecordPts->AXIS2_PT_INT_PT = 0.0;
                    $tempRecordPts->AXIS3_PT_INT_PT = 0.0;
                    break;

                default:
                    $tempRecordPts->AXIS1_PT_INT_PT = $dim1/2.0;
                    $tempRecordPts->AXIS2_PT_INT_PT = $dim3/2.0;
                    $tempRecordPts->AXIS3_PT_INT_PT = $dim2/2.0;
                    break;
            }   
        } else {
            switch ($idShape) {
                case CYLINDER_STANDING_3D:
                case CYLINDER_LAYING_3D:
                case OVAL_STANDING_3D:
                case OVAL_LAYING_3D:
                case SEMI_CYLINDER_3D:

                    $percent1 = floatval($tempRecordPtsDef->AXIS1_PT_INT_PT_DEF) - 50;
                    $percent3 = floatval($tempRecordPtsDef->AXIS3_PT_INT_PT_DEF) - 50;
                    $percent2 = floatval($tempRecordPtsDef->AXIS2_PT_INT_PT_DEF);

                    $tempRecordPts->AXIS1_PT_INT_PT = $dim1*$percent1/100.0;
                    $tempRecordPts->AXIS2_PT_INT_PT = $dim3*$percent3/100.0;
                    $tempRecordPts->AXIS3_PT_INT_PT = $dim2*$percent2/100.0;
                    break;

                case CYLINDER_CONCENTRIC_STANDING_3D:
                case OVAL_CONCENTRIC_STANDING_3D:
                case CYLINDER_CONCENTRIC_LAYING_3D:
                case OVAL_CONCENTRIC_LAYING_3D:

                    $percent1 = floatval($tempRecordPtsDef->AXIS1_PT_INT_PT_DEF) - 50;
                    $percent3 = floatval($tempRecordPtsDef->AXIS2_PT_INT_PT_DEF) - 50;
                    $percent2 = floatval($tempRecordPtsDef->AXIS2_PT_INT_PT_DEF);

                    $tempRecordPts->AXIS1_PT_INT_PT = $dim2*$percent1/100.0;
                    $tempRecordPts->AXIS2_PT_INT_PT = $dim3*$percent3/100.0;
                    $tempRecordPts->AXIS3_PT_INT_PT = $dim1*$percent2/100.0;
                    break;

                case SPHERE_3D:
                    $tempRecordPts->AXIS1_PT_INT_PT = 0.0;
                    $tempRecordPts->AXIS2_PT_INT_PT = 0.0;
                    $tempRecordPts->AXIS3_PT_INT_PT = 0.0;
                    break;

                default:

                    $percent1 = floatval($tempRecordPtsDef->AXIS1_PT_INT_PT_DEF);
                    $percent3 = floatval($tempRecordPtsDef->AXIS3_PT_INT_PT_DEF);
                    $percent2 = floatval($tempRecordPtsDef->AXIS2_PT_INT_PT_DEF);

                    $tempRecordPts->AXIS1_PT_INT_PT = $dim1*$percent1/100.0;
                    $tempRecordPts->AXIS2_PT_INT_PT = $dim3*$percent3/100.0;
                    $tempRecordPts->AXIS3_PT_INT_PT = $dim2*$percent2/100.0;
                    break;
            }
        }
        // bottom point
        if (($tempRecordPtsDef->AXIS1_PT_BOT_SURF_DEF == 0) && 
            ($tempRecordPtsDef->AXIS2_PT_BOT_SURF_DEF == 0) && 
            ($tempRecordPtsDef->AXIS3_PT_BOT_SURF_DEF == 0)) {
            switch ($idShape) {
                case CYLINDER_STANDING_3D:
                case CYLINDER_LAYING_3D:
                case OVAL_STANDING_3D:
                case OVAL_LAYING_3D:
                case SEMI_CYLINDER_3D:
                case CYLINDER_CONCENTRIC_STANDING_3D:
                case OVAL_CONCENTRIC_STANDING_3D:
                case CYLINDER_CONCENTRIC_LAYING_3D:
                case OVAL_CONCENTRIC_LAYING_3D:
                    $tempRecordPts->AXIS1_PT_BOT_SURF = 0.0;
                    $tempRecordPts->AXIS2_PT_BOT_SURF = 0.0;
                    $tempRecordPts->AXIS3_PT_BOT_SURF = 0.0;
                    break;

                case SPHERE_3D:
                    $tempRecordPts->AXIS1_PT_BOT_SURF = 0.0;
                    $tempRecordPts->AXIS2_PT_BOT_SURF = 0.0;
                    $tempRecordPts->AXIS3_PT_BOT_SURF = -$dim3/2.0;
                    break;

                default:
                    $tempRecordPts->AXIS1_PT_BOT_SURF = $dim1/2.0;
                    $tempRecordPts->AXIS2_PT_BOT_SURF = $dim3/2.0;
                    $tempRecordPts->AXIS3_PT_BOT_SURF = 0.0;
                    break;
            }  
        } else {
            switch ($idShape) {
                case CYLINDER_STANDING_3D:
                case CYLINDER_LAYING_3D:
                case OVAL_STANDING_3D:
                case OVAL_LAYING_3D:
                case SEMI_CYLINDER_3D:
                    $percent1 = floatval($tempRecordPtsDef->AXIS1_PT_BOT_SURF_DEF) - 50;
                    $percent3 = floatval($tempRecordPtsDef->AXIS3_PT_BOT_SURF_DEF) - 50;
                    $percent2 = floatval($tempRecordPtsDef->AXIS2_PT_BOT_SURF_DEF);

                    $tempRecordPts->AXIS1_PT_BOT_SURF = $dim1*$percent1/100.0;
                    $tempRecordPts->AXIS2_PT_BOT_SURF = $dim3*$percent3/100.0;
                    $tempRecordPts->AXIS3_PT_BOT_SURF = $dim2*$percent2/100.0;
                    break;

                case CYLINDER_CONCENTRIC_STANDING_3D:
                case OVAL_CONCENTRIC_STANDING_3D:
                case CYLINDER_CONCENTRIC_LAYING_3D:
                case OVAL_CONCENTRIC_LAYING_3D:
                    $percent1 = floatval($tempRecordPtsDef->AXIS1_PT_BOT_SURF_DEF) - 50;
                    $percent3 = floatval($tempRecordPtsDef->AXIS3_PT_BOT_SURF_DEF) - 50;
                    $percent2 = floatval($tempRecordPtsDef->AXIS2_PT_BOT_SURF_DEF);

                    $tempRecordPts->AXIS1_PT_BOT_SURF = $dim2*$percent1/100.0;
                    $tempRecordPts->AXIS2_PT_BOT_SURF = $dim3*$percent3/100.0;
                    $tempRecordPts->AXIS3_PT_BOT_SURF = $dim1*$percent2/100.0;
                    break;

                case SPHERE_3D:
                    $tempRecordPts->AXIS1_PT_BOT_SURF = 0.0;
                    $tempRecordPts->AXIS2_PT_BOT_SURF = 0.0;
                    $tempRecordPts->AXIS3_PT_BOT_SURF = -$dim3/2.0;
                    break;

                default:

                    $percent1 = floatval($tempRecordPtsDef->AXIS1_PT_BOT_SURF_DEF);
                    $percent3 = floatval($tempRecordPtsDef->AXIS3_PT_BOT_SURF_DEF);
                    $percent2 = floatval($tempRecordPtsDef->AXIS2_PT_BOT_SURF_DEF);

                    $tempRecordPts->AXIS1_PT_BOT_SURF = $dim1*$percent1/100.0;
                    $tempRecordPts->AXIS2_PT_BOT_SURF = $dim3*$percent3/100.0;
                    $tempRecordPts->AXIS3_PT_BOT_SURF = $dim2*$percent2/100.0;
                    break;
            }
        }
    }

    private function initLines($tempRecordPtsDef, $tempRecordPts, $dim1, $dim2, $dim3, $idShape)
    {
        // Line X
        if (($tempRecordPtsDef->AXIS2_AX_1_DEF == 0) && ($tempRecordPtsDef->AXIS3_AX_1_DEF == 0)) {
            switch ($idShape) {
                case CYLINDER_STANDING_3D:
                case CYLINDER_LAYING_3D:
                case OVAL_STANDING_3D:
                case OVAL_LAYING_3D:
                case SEMI_CYLINDER_3D:
                    $tempRecordPts->AXIS2_AX_1 = 0.0;
                    $tempRecordPts->AXIS3_AX_1 = $dim2/2.0;
                    break;

                case CYLINDER_CONCENTRIC_STANDING_3D:
                case OVAL_CONCENTRIC_STANDING_3D:
                case CYLINDER_CONCENTRIC_LAYING_3D:
                case OVAL_CONCENTRIC_LAYING_3D:
                    $tempRecordPts->AXIS2_AX_1 = 0.0;
                    $tempRecordPts->AXIS3_AX_1 = $dim1/2.0;
                    break;

                case SPHERE_3D:
                    $tempRecordPts->AXIS2_AX_1 = 0.0;
                    $tempRecordPts->AXIS3_AX_1 = 0.0;
                    break;

                default:
                    $tempRecordPts->AXIS2_AX_1 = $dim3/2.0;
                    $tempRecordPts->AXIS3_AX_1 = $dim2/2.0;
                    break;
            }
        } else {
            switch ($idShape) {
                case CYLINDER_STANDING_3D:
                case CYLINDER_LAYING_3D:
                case OVAL_STANDING_3D:
                case OVAL_LAYING_3D:
                case SEMI_CYLINDER_3D:
                    $percent3 = floatval($tempRecordPtsDef->AXIS3_AX_1_DEF) - 50;
                    $percent2 = floatval($tempRecordPtsDef->AXIS2_AX_1_DEF);

                    // Current version temporal force to center
                    $tempRecordPts->AXIS2_AX_1 = 0.0;
                    //$tempRecordPts->AXIS2_AX_1 = $dim3*$percent3/100.0;
                    $tempRecordPts->AXIS3_AX_1 = $dim2*$percent2/100.0;
                    break;

                case CYLINDER_CONCENTRIC_STANDING_3D:
                case OVAL_CONCENTRIC_STANDING_3D:
                case CYLINDER_CONCENTRIC_LAYING_3D:
                case OVAL_CONCENTRIC_LAYING_3D:
                    $percent3 = floatval($tempRecordPtsDef->AXIS3_AX_1_DEF) - 50;
                    $percent2 = floatval($tempRecordPtsDef->AXIS2_AX_1_DEF);

                    // Current version temporal force to center
                    $tempRecordPts->AXIS2_AX_1 = 0.0;
                    //$tempRecordPts->AXIS2_AX_1 = $dim3*$percent3/100.0;
                    $tempRecordPts->AXIS3_AX_1 = $dim1*$percent2/100.0;
                    break;

                case SPHERE_3D:
                    $tempRecordPts->AXIS2_AX_1 = 0.0;
                    $tempRecordPts->AXIS3_AX_1 = 0.0;
                    break;

                default:
                    $percent3 = floatval($tempRecordPtsDef->AXIS3_AX_1_DEF);
                    $percent2 = floatval($tempRecordPtsDef->AXIS2_AX_1_DEF);
                    $tempRecordPts->AXIS2_AX_1 = $dim3*$percent3/100.0;
                    $tempRecordPts->AXIS3_AX_1 = $dim2*$percent2/100.0;
                    break;
            }
        }

        // Line Y
        if (($tempRecordPtsDef->AXIS1_AX_2_DEF == 0) && ($tempRecordPtsDef->AXIS3_AX_2_DEF == 0)) {
            switch ($idShape) {
                case CYLINDER_STANDING_3D:
                case CYLINDER_LAYING_3D:
                case OVAL_STANDING_3D:
                case OVAL_LAYING_3D:
                case SEMI_CYLINDER_3D:
                    $tempRecordPts->AXIS1_AX_2 = 0.0;
                    $tempRecordPts->AXIS3_AX_2 = $dim2/2.0;
                    break;

                case CYLINDER_CONCENTRIC_STANDING_3D:
                case OVAL_CONCENTRIC_STANDING_3D:
                case CYLINDER_CONCENTRIC_LAYING_3D:
                case OVAL_CONCENTRIC_LAYING_3D:
                    $tempRecordPts->AXIS1_AX_2 = 0.0;
                    $tempRecordPts->AXIS3_AX_2 = $dim1/2.0;
                    break;

                case SPHERE_3D:
                    $tempRecordPts->AXIS1_AX_2 = 0.0;
                    $tempRecordPts->AXIS3_AX_2 = 0.0;
                    break;

                default:
                    $tempRecordPts->AXIS1_AX_2 = $dim1/2.0;
                    $tempRecordPts->AXIS3_AX_2 = $dim2/2.0;
                    break;
            }
        } else {
            switch ($idShape) {
                case CYLINDER_STANDING_3D:
                case CYLINDER_LAYING_3D:
                case OVAL_STANDING_3D:
                case OVAL_LAYING_3D:
                case SEMI_CYLINDER_3D:
                    $percent1 = floatval($tempRecordPtsDef->AXIS1_AX_2_DEF) - 50;
                    $percent2 = floatval($tempRecordPtsDef->AXIS3_AX_2_DEF);

                    // Current version temporal force to center
                    $tempRecordPts->AXIS1_AX_2 = 0.0;
                    //$tempRecordPts->AXIS1_AX_2 = $dim1*$percent3/100.0;
                    $tempRecordPts->AXIS3_AX_2 = $dim2*$percent2/100.0;
                    break;

                case CYLINDER_CONCENTRIC_STANDING_3D:
                case OVAL_CONCENTRIC_STANDING_3D:
                case CYLINDER_CONCENTRIC_LAYING_3D:
                case OVAL_CONCENTRIC_LAYING_3D:
                    $percent1 = floatval($tempRecordPtsDef->AXIS1_AX_2_DEF) - 50;
                    $percent2 = floatval($tempRecordPtsDef->AXIS3_AX_2_DEF);

                    // Current version temporal force to center
                    $tempRecordPts->AXIS1_AX_2 = 0.0;
                    //$tempRecordPts->AXIS1_AX_2 = $dim2*$percent1/100.0;
                    $tempRecordPts->AXIS3_AX_2 = $dim1*$percent2/100.0;
                    break;

                case SPHERE_3D:
                    $tempRecordPts->AXIS1_AX_2 = 0.0;
                    $tempRecordPts->AXIS3_AX_2 = 0.0;
                    break;

                default:
                    
                    $percent1 = floatval($tempRecordPtsDef->AXIS1_AX_2_DEF);
                    $percent2 = floatval($tempRecordPtsDef->AXIS3_AX_2_DEF);
                    $tempRecordPts->AXIS1_AX_2 = $dim3*$percent1/100.0;
                    $tempRecordPts->AXIS3_AX_2 = $dim2*$percent2/100.0;
                    break;
            }
        }

        // Line Z
        if (($tempRecordPtsDef->AXIS1_AX_3_DEF == 0) && ($tempRecordPtsDef->AXIS2_AX_3_DEF == 0)) {
            switch ($idShape) {
                case CYLINDER_STANDING_3D:
                case CYLINDER_LAYING_3D:
                case OVAL_STANDING_3D:
                case OVAL_LAYING_3D:
                case SEMI_CYLINDER_3D:
                case CYLINDER_CONCENTRIC_STANDING_3D:
                case OVAL_CONCENTRIC_STANDING_3D:
                case CYLINDER_CONCENTRIC_LAYING_3D:
                case OVAL_CONCENTRIC_LAYING_3D:
                case SPHERE_3D:
                    $tempRecordPts->AXIS1_AX_3 = 0.0;
                    $tempRecordPts->AXIS2_AX_3 = 0.0;
                    break;

                default:
                    $tempRecordPts->AXIS1_AX_3 = $dim1/2.0;
                    $tempRecordPts->AXIS2_AX_3 = $dim3/2.0;
                    break;
            }
        } else {
            switch ($idShape) {
                case CYLINDER_STANDING_3D:
                case CYLINDER_LAYING_3D:
                case OVAL_STANDING_3D:
                case OVAL_LAYING_3D:
                case SEMI_CYLINDER_3D:
                    $percent1 = floatval($tempRecordPtsDef->AXIS1_AX_3_DEF) - 50;
                    $percent2 = floatval($tempRecordPtsDef->AXIS2_AX_3_DEF) - 50;
                    // Current version temporal force to center
                    $tempRecordPts->AXIS1_AX_3 = 0.0;
                    $tempRecordPts->AXIS2_AX_3 = 0.0;
                    // $tempRecordPts->AXIS1_AX_3 = $dim1*$percent3/100.0;
                    // $tempRecordPts->AXIS2_AX_3 = $dim3*$percent2/100.0;
                    break;

                case CYLINDER_CONCENTRIC_STANDING_3D:
                case OVAL_CONCENTRIC_STANDING_3D:
                case CYLINDER_CONCENTRIC_LAYING_3D:
                case OVAL_CONCENTRIC_LAYING_3D:
                    $percent1 = floatval($tempRecordPtsDef->AXIS1_AX_3_DEF) - 50;
                    $percent2 = floatval($tempRecordPtsDef->AXIS2_AX_3_DEF) - 50;
                    // Current version temporal force to center
                    $tempRecordPts->AXIS1_AX_3 = 0.0;
                    $tempRecordPts->AXIS2_AX_3 = 0.0;
                    // $tempRecordPts->AXIS1_AX_3 = $dim2*$percent1/100.0;
                    // $tempRecordPts->AXIS2_AX_3 = $dim3*$percent2/100.0;
                    break;

                case SPHERE_3D:
                    $tempRecordPts->AXIS1_AX_3 = 0.0;
                    $tempRecordPts->AXIS2_AX_3 = 0.0;
                    break;

                case TRAPEZOID_3D:

                    $percent1 = floatval($tempRecordPtsDef->AXIS1_AX_3_DEF);
                    $percent2 = floatval($tempRecordPtsDef->AXIS2_AX_3_DEF);
                    // Current version temporal force to center
                    $tempRecordPts->AXIS1_AX_3 = $dim1/2.0;
                    $tempRecordPts->AXIS2_AX_3 = $dim3/2.0;
                    // $tempRecordPts->AXIS1_AX_3 = $dim1*$percent1/100.0;
                    // $tempRecordPts->AXIS2_AX_3 = $dim3*$percent2/100.0;
                    break;

                default:
                    $percent1 = floatval($tempRecordPtsDef->AXIS1_AX_3_DEF);
                    $percent2 = floatval($tempRecordPtsDef->AXIS2_AX_3_DEF);
                    $tempRecordPts->AXIS1_AX_3 = $dim1*$percent1/100.0;
                    $tempRecordPts->AXIS2_AX_3 = $dim3*$percent2/100.0;
                    break;
            }
        }
    }

    private function initPlanes($tempRecordPtsDef, $tempRecordPts, $dim1, $dim2, $dim3, $idShape)
    {
        //Plan YZ
        if ($tempRecordPtsDef->AXIS1_PL_2_3_DEF == 0) {
            switch ($idShape) {
                case CYLINDER_STANDING_3D:
                case CYLINDER_LAYING_3D:
                case OVAL_STANDING_3D:
                case OVAL_LAYING_3D:
                case SEMI_CYLINDER_3D:
                case CYLINDER_CONCENTRIC_STANDING_3D:
                case OVAL_CONCENTRIC_STANDING_3D:
                case CYLINDER_CONCENTRIC_LAYING_3D:
                case OVAL_CONCENTRIC_LAYING_3D:
                    $tempRecordPts->AXIS1_PL_2_3 = 0.0;
                case SPHERE_3D:
                    $tempRecordPts->AXIS1_PL_2_3 = 0.0;
                    break;

                default:
                    $tempRecordPts->AXIS1_PL_2_3 = $dim1 / 2.0;
                    break;
            }
        } else {
            switch ($idShape) {
                case CYLINDER_STANDING_3D:
                case CYLINDER_LAYING_3D:
                case OVAL_STANDING_3D:
                case OVAL_LAYING_3D:
                case SEMI_CYLINDER_3D:
                    $percent1 = floatval($tempRecordPtsDef->AXIS1_PL_2_3_DEF) - 50;
                    $tempRecordPts->AXIS1_PL_2_3 = $dim1 * $percent1 / 100.0;
                    break;
                case CYLINDER_CONCENTRIC_STANDING_3D:
                case OVAL_CONCENTRIC_STANDING_3D:
                case CYLINDER_CONCENTRIC_LAYING_3D:
                case OVAL_CONCENTRIC_LAYING_3D:
                    $percent1 = floatval($tempRecordPtsDef->AXIS1_PL_2_3_DEF) - 50;
                    // Current version temporal force to center
                    $tempRecordPts->AXIS1_PL_2_3 = 0.0;
                    // $tempRecordPts->AXIS1_PL_2_3 = $dim2 * $percent1 / 100.0;
                    break;
                case SPHERE_3D:
                    $tempRecordPts->AXIS1_PL_2_3 = 0.0;
                    break;
                default:
                    $percent1 = floatval($tempRecordPtsDef->AXIS1_PL_2_3_DEF);
                    $tempRecordPts->AXIS1_PL_2_3 = $dim1 / 2.0;
                    break;
            }
        }

        //Plan XZ
        if ($tempRecordPtsDef->AXIS2_PL_1_3_DEF == 0) {
            switch ($idShape) {
                case CYLINDER_STANDING_3D:
                case CYLINDER_LAYING_3D:
                case OVAL_STANDING_3D:
                case OVAL_LAYING_3D:
                case SEMI_CYLINDER_3D:
                case CYLINDER_CONCENTRIC_STANDING_3D:
                case OVAL_CONCENTRIC_STANDING_3D:
                case CYLINDER_CONCENTRIC_LAYING_3D:
                case OVAL_CONCENTRIC_LAYING_3D:
                    $tempRecordPts->AXIS2_PL_1_3 = 0.0;
                case SPHERE_3D:
                    $tempRecordPts->AXIS2_PL_1_3 = 0.0;
                    break;
                default:
                    $tempRecordPts->AXIS2_PL_1_3 = $dim3/2.0;
                    break;
            }
        } else {
            switch ($idShape) {
                case CYLINDER_STANDING_3D:
                case CYLINDER_LAYING_3D:
                case OVAL_STANDING_3D:
                case OVAL_LAYING_3D:
                case SEMI_CYLINDER_3D:
                case CYLINDER_CONCENTRIC_STANDING_3D:
                case OVAL_CONCENTRIC_STANDING_3D:
                case CYLINDER_CONCENTRIC_LAYING_3D:
                case OVAL_CONCENTRIC_LAYING_3D:
                    $percent1 = floatval($tempRecordPtsDef->AXIS2_PL_1_3_DEF) - 50;
                    // Current version temporal force to center
                    $tempRecordPts->AXIS2_PL_1_3 = 0.0;
                    // $tempRecordPts->AXIS2_PL_1_3 = $dim3*$percent1/100.0;
                    break;
                case SPHERE_3D: 
                    $tempRecordPts->AXIS2_PL_1_3 = 0.0;
                    break;
                default:
                    $percent1 = floatval($tempRecordPtsDef->AXIS2_PL_1_3_DEF);
                    $tempRecordPts->AXIS2_PL_1_3 = $dim3*$percent1/100.0;
                    break;
            }
        }

        //Plan XY
        if ($tempRecordPtsDef->AXIS3_PL_1_2_DEF == 0) {
            switch ($idShape) {
                case CYLINDER_STANDING_3D:
                case CYLINDER_LAYING_3D:
                case OVAL_STANDING_3D:
                case OVAL_LAYING_3D:
                case SEMI_CYLINDER_3D:
                    $tempRecordPts->AXIS3_PL_1_2 = $dim2/2.0;
                    break;
                case CYLINDER_CONCENTRIC_STANDING_3D:
                case OVAL_CONCENTRIC_STANDING_3D:
                case CYLINDER_CONCENTRIC_LAYING_3D:
                case OVAL_CONCENTRIC_LAYING_3D:
                    $tempRecordPts->AXIS3_PL_1_2 = $dim1/2.0;
                    break;
                case SPHERE_3D:
                    $tempRecordPts->AXIS3_PL_1_2 = 0.0;
                    break;
                default:
                    $tempRecordPts->AXIS3_PL_1_2 = $dim2/2.0;
                    break;
            }
        } else {
            switch ($idShape) {
                case CYLINDER_STANDING_3D:
                case CYLINDER_LAYING_3D:
                case OVAL_STANDING_3D:
                case OVAL_LAYING_3D:
                case SEMI_CYLINDER_3D:
                    $percent1 = floatval($tempRecordPtsDef->AXIS3_PL_1_2_DEF);
                    $tempRecordPts->AXIS3_PL_1_2 = $dim2*$percent1/100.0;
                    break;
                case CYLINDER_CONCENTRIC_STANDING_3D:
                case OVAL_CONCENTRIC_STANDING_3D:
                case CYLINDER_CONCENTRIC_LAYING_3D:
                case OVAL_CONCENTRIC_LAYING_3D:
                    $percent1 = floatval($tempRecordPtsDef->AXIS3_PL_1_2_DEF);
                    $tempRecordPts->AXIS3_PL_1_2 = $dim1*$percent1/100.0;
                    break;
                case SPHERE_3D:
                    $tempRecordPts->AXIS3_PL_1_2 = 0.0;
                    break;
                default:
                    $percent1 = floatval($tempRecordPtsDef->AXIS3_PL_1_2_DEF);
                    $tempRecordPts->AXIS3_PL_1_2 = $dim2*$percent1/100.0;
                    break;
            }
        }
    }
}
