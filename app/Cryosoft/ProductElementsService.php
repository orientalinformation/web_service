<?php

namespace App\Cryosoft;

use Illuminate\Contracts\Auth\Factory as Auth;
use App\Models\ProductElmt;
use App\Models\InitialTemperature;
use App\Models\MeshPosition;
use App\Models\Product;

class ProductElementsService
{
    /**
     * @var Illuminate\Contracts\Auth\Factory
     */
    protected $auth;

    protected $app;

    protected $value;

    protected $products;

    protected $units;

    protected $convert;

    public function __construct(\Laravel\Lumen\Application $app)
    {
        $this->app = $app;
        $this->auth = app('Illuminate\\Contracts\\Auth\\Factory');
        $this->value = app('App\\Cryosoft\\ValueListService');
        $this->units = app('App\\Cryosoft\\UnitsConverterService');
        $this->convert = app('App\\Cryosoft\\UnitsService');
        $this->products = app('App\\Cryosoft\\ProductService');
    }

    /**
     * @return array 
     */
    public function searchTempMeshPoint(ProductElmt &$productElmt, $pointMeshOrder2 = null)
    {
        $listtemp = [];
        try {
            //search the mesh2 order point
            if ($pointMeshOrder2 == null) {
                $res = $this->products->searchNbPtforElmt($productElmt, 2);
                $pointMeshOrder2 = $res['points'];
            }

            if (count($pointMeshOrder2) == 0) {
                return null;
            }

            $idProduction = $productElmt->product->study->ID_PRODUCTION;
            $it = InitialTemperature::where('ID_PRODUCTION', $idProduction)
                ->whereIn('MESH_2_ORDER', $pointMeshOrder2)
                ->where('MESH_1_ORDER', 0)
                ->where('MESH_3_ORDER', 0)->orderBy('MESH_2_ORDER')->get();

            foreach ($it as $temp) {
                $data = $this->units->prodTemperature($temp->INITIAL_T);
                array_push($listtemp, $data);
            }
        } catch (\Exception $e) {
            throw new \Exception("Unexpected exception search datatbase");
        }

        return $listtemp;
    }

    public function PropagationTempProdElmtIso($pb, $b3D)
    {
        $pe = ProductElmt::find($pb['ID_PRODUCT_ELMT']);
        $product = Product::find($pb['ID_PROD']);
        $study = $product->study;
        $prodMeshgene = $product->meshGenerations()->first();
        
        // search nb point in axe X Z
        $nbPointaxe1 = 1;
        $nbPointaxe2 = 1;
        $nbPointaxe3 = 1;
        if ($b3D) {
            $nbPointaxe1 = $prodMeshgene->MESH_1_NB;
            $nbPointaxe3 = $prodMeshgene->MESH_3_NB;
            switch (intval($pb['ID_SHAPE'])) {
                case $this->value->SLAB:
                case $this->value->SPHERE:
                    $nbPointaxe1 = $nbPointaxe3 = 1;
                    break;
                case $this->value->CYLINDER_STANDING:
                case $this->value->CYLINDER_CONCENTRIC_LAYING:
                case $this->value->CYLINDER_LAYING:
                case $this->value->CYLINDER_CONCENTRIC_STANDING:
                    $nbPointaxe3 = 1;
                    break;
                case $this->value->PARALLELEPIPED_STANDING:
                case $this->value->PARALLELEPIPED_BREADED:
                case $this->value->PARALLELEPIPED_LAYING:
                    break;
            }
        }
        
        //search meshpoints on axis 2
        $pointMeshOrder2 = $this->products->searchNbPtforElmt($pe, $this->value->MESH_AXIS_2);

        // pb . pointMeshOrder2 = pointMeshOrder2;
        $nbPointaxe2 = count($pointMeshOrder2['points']);
        
        $lfTemp = doubleval($this->convert->prodTemperature(doubleval($pb['initTemp'][0]), 16, 0));
        
        $i = $j = $k = 0;

        $listTemp = [];

        for ($j = 0; $j < $nbPointaxe2; $j ++) {
            $meshOrderaxe2 = $pointMeshOrder2['points'][$j];

            for ($i = 0; $i < $nbPointaxe1; $i ++) {
                for ($k = 0; $k < $nbPointaxe3; $k ++) {
                    $temp = new InitialTemperature();
                    $temp->ID_PRODUCTION = $study->ID_PRODUCTION;
                    $temp->MESH_1_ORDER = ($i);
                    $temp->MESH_2_ORDER = ($meshOrderaxe2);
                    $temp->MESH_3_ORDER = ($k);
                    $temp->INITIAL_T = ($lfTemp);

                    array_push($listTemp, $temp->toArray());
                } // for axis 3
            } // for axis 1
        } // for axis 2
        
        $slices = array_chunk($listTemp, 100);
        foreach ($slices as $slice) {
            InitialTemperature::insert($slice);
        }
    }

    public function PropagationTempProdElmtIsoForBreaded($pb)
    {
        $pe = ProductElmt::find($pb['ID_PRODUCT_ELMT']);
        $product = Product::find($pb['ID_PROD']);
        $study = $product->study;
        $prodMeshgene = $product->meshGenerations()->first();
        // log . debug("PropagationTempProdElmtIso for BREADED");
        if ($pe->ID_SHAPE != $this->value->PARALLELEPIPED_BREADED) {
            $this->PropagationTempProdElmtIso($pb, true);
        } else {
            $lfTemp = 0;
            
            // search nb point in axe 1, 2, 3
            $nbPointaxe1 = 1;
            $nbPointaxe2 = 1;
            $nbPointaxe3 = 1;

            $firstMesh1 = 0;
            $firstMesh2 = 0;
            $firstMesh3 = 0;
            $lastMesh1 = 0;
            $lastMesh2 = 0;
            $lastMesh3 = 0;

            $firstIntNode1 = 0;
            $lastIntNode1 = 0;

            $firstIntNode2 = 0;
            $lastIntNode2 = 0;

            $firstIntNode3 = 0;
            $lastIntNode3 = 0;

            $i = $j = $k = 0;
            
            //search meshpoints on axis 2
            $pointMeshOrder1 = $this->products->searchNbPtforElmt($pe, $this->value->MESH_AXIS_1)['points'];
            $pointMeshOrder2 = $this->products->searchNbPtforElmt($pe, $this->value->MESH_AXIS_2)['points'];
            $pointMeshOrder3 = $this->products->searchNbPtforElmt($pe, $this->value->MESH_AXIS_3)['points'];
            
            // get the first and last nodes of the internal product
            $nbPointaxe1 = count($pointMeshOrder1);
            $nbPointaxe2 = count($pointMeshOrder2);
            $nbPointaxe3 = count($pointMeshOrder3);
            $first = $last = 0;
            
            for ($i = 1; $i < $nbPointaxe1; $i ++) {
                $last = $pointMeshOrder1[$i];
                $first = $pointMeshOrder1[$i-1];
                if (($last - $first) > 1) {
                    $firstIntNode1 = ($first + 1);
                    $lastIntNode1 = ($last - 1);
                    break;
                }
            }

            for ($j = 1; $j < $nbPointaxe2; $j ++) {
                $last = $pointMeshOrder2[$j];
                $first = $pointMeshOrder2[$j-1];
                if (($last - $first) > 1) {
                    $firstIntNode2 = ($first + 1);
                    $lastIntNode2 = ($last - 1);
                    break;
                }
            }

            for ($k = 1; $k < $nbPointaxe3; $k ++) {
                $last = $pointMeshOrder3[$k];
                $first = $pointMeshOrder3[$k-1];
                if (($last - $first) > 1) {
                    $firstIntNode3 = ($first + 1);
                    $lastIntNode3 = ($last - 1);
                    break;
                }
            }
            
            //  first mesh order (node number) on each axis ( -1 => for following loop)
            $firstMesh1 = $pointMeshOrder1[0];
            $firstMesh2 = $pointMeshOrder2[0];
            $firstMesh3 = $pointMeshOrder3[0];
            
            // number of nodes contains in this component
            $lastMesh1 = $pointMeshOrder1[$nbPointaxe1 - 1];
            $lastMesh2 = $pointMeshOrder2[$nbPointaxe2 - 1];
            $lastMesh3 = $pointMeshOrder3[$nbPointaxe3 - 1];
            
            // save temperature
            $lfTemp = $this->convert->prodTemperature(doubleval($pb['initTemp'][0]), 16, 0);

            $listTemp = [];
 
            for ($i = $firstMesh1; $i <= $lastMesh1; $i ++) {
                for ($j = $firstMesh2; $j <= $lastMesh2; $j ++) {
                    for ($k = $firstMesh3; $k <= $lastMesh3; $k ++) {
                        if (!(($i >= $firstIntNode1) && ($i <= $lastIntNode1)
                            && ($j >= $firstIntNode2) && ($j <= $lastIntNode2)
                            && ($k >= $firstIntNode3) && ($k <= $lastIntNode3))) {
                            //  save node temperature
                            $temp = new InitialTemperature();
                            $temp->ID_PRODUCTION = ($study->ID_PRODUCTION);
                            $temp->MESH_1_ORDER = ($i);
                            $temp->MESH_2_ORDER = ($j);
                            $temp->MESH_3_ORDER = ($k);
                            $temp->INITIAL_T = ($lfTemp);
                            
                            array_push($listTemp, $temp->toArray());
                        }
                    }  // for axis 3
                } // for axis 2
            } // for axis 1
            
            $slices = array_chunk($listTemp, 100);
            foreach ($slices as $slice) {
                InitialTemperature::insert($slice);
            }
        }
    }

    public function getProdElmtthickness($ID_PRODUCT_ELMT) 
    { 
        $lfheight = null;

        $prodElmt = ProductElmt::find($ID_PRODUCT_ELMT);

        if ($prodElmt) {
            $lfheight = doubleval($prodElmt->SHAPE_PARAM2);
            $shapepos = doubleval($prodElmt->SHAPE_POS2);

            switch ($prodElmt->ID_SHAPE) {
              case 1: 
              case 2: 
              case 3: 
              case 4: 
              case 5: 
              case 9: 
                break;
              
              case 6: 
              case 7: 
              case 8: 
                if ($shapepos == 0.0) {
                  $lfheight /= 2.0;
                }
                break;
            }
            $lfheight = $this->units->prodDimension($lfheight);
        }

        return $lfheight;
    }

    public function findProdElmt3D($idProd, $idProdElmt, $status)
    {   
        $prodEmltBeforeCurrent = null;
        $max = 0;
        $elmts = [];
        $pElmts = ProductElmt::where('ID_PROD', $idProd)->get();
        foreach ($pElmts as $elmt) {
            if ($status) {
                if ($elmt->ID_PRODUCT_ELMT > $idProdElmt) {
                    return $elmt; 
                }
            } else {
                if ($elmt->ID_PRODUCT_ELMT < $idProdElmt) {
                    array_push($elmts, $elmt);
                }
            }
        }

        if (count($elmts) > 0) {
            foreach ($elmts as $elmt) {
                if ($elmt->ID_PRODUCT_ELMT > $max) {
                    $max = $elmt->ID_PRODUCT_ELMT;
                    $prodEmltBeforeCurrent = $elmt;
                }
            }
        }

        return $prodEmltBeforeCurrent;
    }
}