<?php

namespace App\Cryosoft;

use App\Models\Study;
use App\Models\StudyEquipment;
use App\Models\Product;
use App\Models\Translation;
use App\Models\ProductElmt;
use App\Models\MeshPosition;
use App\Models\InitialTemperature;
use App\Models\MeshGeneration;
use App\Models\InitTemp3D;

class ProductService
{
    
    public function __construct(\Laravel\Lumen\Application $app)
    {
        $this->app = $app;
        $this->auth = $app['Illuminate\\Contracts\\Auth\\Factory'];
        $this->values = app('App\\Cryosoft\\ValueListService');
        $this->units = app('App\\Cryosoft\\UnitsConverterService');
        $this->convert = app('App\\Cryosoft\\UnitsService');
        $this->studies = app('App\\Cryosoft\\StudyService');
        $this->stdeqp = app('App\\Cryosoft\\StudyEquipmentService');

    }

    public function getAllCompFamily()
    {
        $translations = Translation::where('TRANS_TYPE', 14)
            ->where('CODE_LANGUE', $this->auth->user()->CODE_LANGUE)
            ->get();

        for ($i = 0; $i < $translations->count(); $i++) {
            $translations[$i]->LABEL = \mb_convert_encoding($translations[$i]->LABEL, "UTF-8");
        }
        
        return $translations;
    }

    public function getAllSubFamily($compFamily = 0)
    {
        //compFamily this is value return from combobox after select
        $querys = Translation::where('TRANS_TYPE', 16)
            ->where('CODE_LANGUE', $this->auth->user()->CODE_LANGUE);

        if ($compFamily != 0) {
            $querys->where('ID_TRANSLATION', '>=', $compFamily * 100)
                ->where('ID_TRANSLATION', '<', ($compFamily + 1) * 100);
        }

        $translations = $querys->get();

        for ($i = 0; $i < $translations->count(); $i++) {
            $translations[$i]->LABEL = \mb_convert_encoding($translations[$i]->LABEL, "UTF-8");
        }
        
        return $translations;
    }

    public function getWaterPercentList()
    {
        $data = ["0 - 10%", "10 - 20%", "20 - 30%", "30 - 40%", "40 - 50%", "50 - 60%", "60 - 70%", "70 - 80%", "80 - 90%", "90 - 100%"];

        return $data;
    }

    public function getAllStandardComponents($idStudy = 0, $compFamily = 0, $subFamily = 0, $percentWater = 0)
    {
        $querys = Translation::select('Translation.ID_TRANSLATION', 'Translation.LABEL', 'component.ID_USER', 'component.COMP_RELEASE', 'component.COMP_VERSION', 'component.OPEN_BY_OWNER', 'component.ID_COMP', 'ln2user.USERNAM')
        ->join('component', 'Translation.ID_TRANSLATION', '=', 'component.ID_COMP')
        ->join('ln2user', 'component.ID_USER', '=', 'ln2user.ID_USER')
        ->where('Translation.TRANS_TYPE', 1)
        ->where('Translation.CODE_LANGUE', $this->auth->user()->CODE_LANGUE);
        

        if ($idStudy != 0) {
            $querys->where(function ($query) use ($idStudy) {
                $query->where('component.COMP_IMP_ID_STUDY', 0)
                    ->orWhere('component.COMP_IMP_ID_STUDY', $idStudy);
            })->where(function($query) {
                $query->where(function($q) {
                    $q->where('component.COMP_RELEASE', 3)
                    ->orWhere('component.COMP_RELEASE', 4)
                    ->orWhere('component.COMP_RELEASE', 8);
                })->orWhere(function($q){
                    $q->where('component.COMP_RELEASE', 2)
                    ->where('component.ID_USER', $this->auth->user()->ID_USER);
                });
            });  
        } else {
            $querys->where('component.COMP_RELEASE', '<>', 6);   
        }

        if ($compFamily > 0) {
            $querys->where('component.CLASS_TYPE', $compFamily); 
        }

        if ($subFamily > 0) {
            $querys->where('component.SUB_FAMILY', $subFamily); 
        }

        if ($percentWater > 0) {
            $querys->where('component.WATER', '>=', ($percentWater - 1) * 10); 
            $querys->where('component.WATER', '<=', $percentWater * 10); 
        }

        $querys->orderBy('Translation.LABEL');

        $components = $querys->get();

        $result = [];
        if (count($components) > 0) {
            $i = 0;
            foreach ($components as $cp) {
                if ($cp->COMP_RELEASE != 9 || $cp->ID_USER == $this->auth->user()->ID_USER || $this->auth->user()->USERPRIO <= 1) {
                    $libValue = $this->getLibValue(100, $cp->COMP_RELEASE);
                    $displayName = $cp->LABEL . ' - ' . $cp->COMP_VERSION . ' ('. $libValue .')';
                    if ($cp->USERNAM != 'KERNEL') {
                        $displayName .= ' - ' . $cp->USERNAM; 
                    }
                    $result[$i]['ID_COMP'] = $cp->ID_COMP; 
                    $result[$i]['displayName'] = trim($displayName);
                    $i++;
                }
            }
        }

        return $result;
    }
    
    public function getAllSleepingComponents($compFamily = 0, $subFamily = 0, $percentWater = 0)
    {
        $querys = Translation::select('Translation.ID_TRANSLATION', 'Translation.LABEL', 'component.COMP_VERSION', 'component.ID_COMP')
        ->join('component', 'Translation.ID_TRANSLATION', '=', 'component.ID_COMP')
        ->where('Translation.CODE_LANGUE', $this->auth->user()->CODE_LANGUE)
        ->where('Translation.TRANS_TYPE', 1)
        ->where('component.COMP_RELEASE', 6);
        

        if ($compFamily > 0) {
            $querys->where('component.CLASS_TYPE', $compFamily); 
        }

        if ($subFamily > 0) {
            $querys->where('component.SUB_FAMILY', $subFamily); 
        }

        if ($percentWater > 0) {
            $querys->where('component.WATER', '>=', ($percentWater - 1) * 10); 
            $querys->where('component.WATER', '<=', $percentWater * 10); 
        }

        $querys->orderBy('Translation.LABEL');

        $components = $querys->get();

        $result = [];
        if (count($components) > 0) {
            $i = 0;
            foreach ($components as $cp) {
                if (($cp->ID_TRANSLATION != 9) || ($cp->ID_USER == $this->auth->user()->ID_USER) || ($this->auth->user()->USERPRIO <= 1)) {
                    $displayName = $cp->LABEL . ' - ' . $cp->COMP_VERSION;
                    $result[$i]['ID_COMP'] = $cp->ID_COMP; 
                    $result[$i]['displayName'] = trim($displayName);
                    $i++;
                }
            }
        }

        return $result;
    }

    public function getComponentDisplayName($idComp)
    {
        $component = Translation::select('translation.ID_TRANSLATION', 'translation.LABEL', 'component.ID_USER', 'component.COMP_RELEASE', 'component.COMP_VERSION', 'component.OPEN_BY_OWNER', 'component.ID_COMP', 'ln2user.USERNAM')
        ->join('component', 'translation.ID_TRANSLATION', '=', 'component.ID_COMP')
        ->join('ln2user', 'component.ID_USER', '=', 'ln2user.ID_USER')
        ->where('translation.TRANS_TYPE', 1)
        ->where('component.ID_COMP', $idComp)
        ->where('translation.CODE_LANGUE', $this->auth->user()->CODE_LANGUE)->first();

        $libValue = $this->getLibValue(100, $component->COMP_RELEASE);

        return $component->LABEL . ' - ' . $component->COMP_VERSION . ' ('. $libValue .')';;
    }

    public function getLibValue($tid, $id)
    {
        $translation = Translation::where('TRANS_TYPE', $tid)
        ->where('CODE_LANGUE', $this->auth->user()->CODE_LANGUE)
        ->where('ID_TRANSLATION', $id)->first();
        if ($translation) return $translation->LABEL;
    }

    // search mesh order for one elment on an axis
    public function searchNbPtforElmt(ProductElmt &$elmt, $axe = 2)
    {
        $mshPsts = MeshPosition::where('ID_PRODUCT_ELMT', $elmt->ID_PRODUCT_ELMT)->where('MESH_AXIS', $axe)->orderBy('MESH_ORDER')->get();
        $points = [];
        $positions = [];
        foreach ($mshPsts as $mshPst) {
            $points[] = $mshPst->MESH_ORDER;
            $positions[] = $this->units->meshesUnit($mshPst->MESH_AXIS_POS);
        }
        rsort($positions);
        
        return compact('points', 'positions');
    }

    public function make2Dcontour(Study &$study) 
    {
        $idProduction = $study->productions->first()->ID_PRODUCTION;
        $product = $study->products->first();
        
        $listOfElmtId = ProductElmt::where('ID_PROD', $product->ID_PROD)->where('INSERT_LINE_ORDER', '!=', $study->ID_STUDY)
            ->pluck('ID_PRODUCT_ELMT')->toArray();
        
        if (!count($listOfElmtId)>0) {
            return null;
        }

        $ldAxe[] = $this->getPlanFor2DContour($product, $listOfElmtId, $idProduction);
        if (($ldAxe[0] < $this->values->MESH_AXIS_1) || ($ldAxe[1] < $this->values->MESH_AXIS_1)) {
            return null;
        }

        $lfPasTemp = 0;
        $BorneTemp = $this->getTemperatureBorne($listOfElmtId, $idProduction);
        $BorneTemp[$this->values->ID_TMIN] = $this->units->prodTemperature($BorneTemp[$this->values->ID_TMIN]);
        $BorneTemp[$this->values->ID_TMAX] = $this->units->prodTemperature($BorneTemp[$this->values->ID_TMAX]);

        $res = $this->calculatePasTemp($BorneTemp[$this->values->ID_TMIN], $BorneTemp[$this->values->ID_TMAX]);
        $BorneTemp[$this->values->ID_TMIN] = $res[$this->values->ID_TMIN];
        $BorneTemp[$this->values->ID_TMAX] = res[$this->values->ID_TMAX];
        $lfPasTemp = $res[$this->values->ID_PAS];

        $zStep = $lfPasTemp;
        $zStart = $BorneTemp[$this->values->ID_TMIN];
        $zEnd = $BorneTemp[$this->values->ID_TMAX];
    }

    public function CheckInitialTemperature(Product &$product) 
    {
        // @TODO: implement
        return true;
    }

    public function DeleteOldInitTemp(Product &$product) 
    {
        // @TODO: implement
        // delete all current initial temperature
        InitialTemperature::where('ID_PRODUCTION', $product->study->ID_PRODUCTION)->delete();
    }

    public function saveMatrixTempComeFromParent(Product &$product)
    {
        echo "start save matrix from parent\n";
        
        $bret = false;
        // save matrix temperature issue from parent study
        $study = $product->study;
        $production = $study->productions->first();
        $idProductElmt = $product->productElmts->first()->ID_PRODUCT_ELMT;

        try {
            if ($this->studies->isStudyHasParent($study) && 
                $this->IsMeshPositionCalculate($idProductElmt) && 
                (!$this->IsThereSomeInitialTemperature($production->ID_PRODUCTION))) {

                echo "study has parent\n";

                $productElmt = null;
                // loop on all product element (from the first inserted to the last excepted for breaded)
                $productElmts = ProductElmt::where('ID_PROD', $product->ID_PROD)->orderBy('SHAPE_POS2', 'DESC')->get();
                if ($product->productElmts->first()->ID_SHAPE != $this->values->PARALLELEPIPED_BREADED) {
                    for ($i = (count($productElmts) - 1); $i >= 0; $i--) {
                        $productElmt = $productElmts[$i];
                        if ($productElmts[$i]->INSERT_LINE_ORDER != $study->ID_STUDY) {
                            break;
                        }
                    }
                } else {
                    for ($i = 0; $i < count($productElmts); $i++) {
                        $productElmt = $productElmts[$i];
                        if ($productElmts[$i]->INSERT_LINE_ORDER != $study->ID_STUDY) {
                            break;
                        }
                    }
                }
                echo $productElmt == null?"cannot found productElmt\n":"found\n";
                
                if ($productElmt != null) {
                    // search the list of mesh points on axis 2 for this product element
                    $offset = [];
                    $offset[0] = 0;
                    $offset[1] = 0;
                    $offset[2] = 0;
                    $meshPoint = null;

                    switch ($productElmt->ID_SHAPE) {
                        case $this->values->SLAB:
                        case $this->values->PARALLELEPIPED_STANDING:
                        case $this->values->PARALLELEPIPED_LAYING:
                        case $this->values->CYLINDER_STANDING:
                        case $this->values->CYLINDER_LAYING:
                            $meshPoint = $this->searchNbPtforElmt($productElmt, $this->values->MESH_AXIS_2);
                            $offset[1] = $meshPoint['points'][0];
                            $offset[0] = $offset[2] = 0;
                            break;

                        case $this->values->PARALLELEPIPED_BREADED:
                            $meshPoint = $this->searchNbPtforElmt($productElmt, $this->values->MESH_AXIS_1);
                            $offset[0] = $meshPoint['points'][0];
                            $meshPoint = $this->searchNbPtforElmt($productElmt, $this->values->MESH_AXIS_2);
                            $offset[1] = $meshPoint['points'][0];
                            $meshPoint = $this->searchNbPtforElmt($productElmt, $this->values->MESH_AXIS_3);
                            $offset[2] = $meshPoint['points'][0];
                            break;

                        case $this->values->CYLINDER_CONCENTRIC_STANDING:
                        case $this->values->CYLINDER_CONCENTRIC_LAYING:
                        case $this->values->SPHERE:
                            $offset[0] = $offset[1] = $offset[2] = 0;
                            break;
                    }
                    
                    $parentStudy = Study::findOrFail($study->PARENT_ID);
                    $sequip = StudyEquipment::findOrFail($study->PARENT_STUD_EQP_ID);
                    $parentProduct = $parentStudy->products()->first();
                    echo $sequip != null ? "found parent stdeqp\n" : "not found stdeqp\n";
                    echo $parentProduct != null ? "found parent\n" : "not found parent\n";
                    if (($sequip != null) && ($parentProduct != null)) {
                        // log . debug("search source for save temperature.....");
                        $bNum = ($sequip->BRAIN_TYPE == $this->values->BRAIN_RUN_FULL_YES) ? true : false;
                        $bAna;
                        if ($study->CALCULATION_MODE == ($this->values->STUDY_ESTIMATION_MODE)) {
                            // estimation
                            $bAna = $this->stdeqp->isAnalogicResults($sequip);
                        } else {
                            // optimum or selected
                            $bAna = ($sequip->BRAIN_TYPE != $this->values->BRAIN_RUN_NONE) ? true : false;
                        }

                        if ($bNum) {
                            echo ".....from numerical results\n";
                            $this->stdeqp->setInitialTempFromNumericalResults1(
                                $sequip,
                                $productElmt->ID_SHAPE,
                                $offset,
                                $parentProduct,
                                $production
                            );
                        } else if ($bAna) {
                            if ($study->CALCULATION_MODE == ($this->values->STUDY_ESTIMATION_MODE)) {
                                echo ".....from analogic results (estimation)\n";
                                $this->stdeqp->setInitialTempFromAnalogicalResults1(
                                    $sequip,
                                    $productElmt->ID_SHAPE,
                                    $offset,
                                    $parentProduct,
                                    $production
                                );
                            } else {
                                echo ".....from analogic results (optimum/selected)\n";
                                $this->stdeqp->setInitialTempFromSimpleNumericalResults1(
                                    $sequip,
                                    $productElmt->ID_SHAPE,
                                    $offset,
                                    $parentProduct,
                                    $production
                                );
                            }
                        }
                        $bret = true;
                    } else {
                        echo "Parent study equipments are not exist - may be deleted";
                        throw new \Exception("Parent study equipments are not exist - may be deleted");
                    }
                }
            }
        } catch (\Exception $qe) {
            throw new \Exception("Exception while saving Temperature");
        }

        return $bret;
    }

    public function PropagationTempElmt (Product &$product, $X, $valueY, $Z, $stemp)
    {
        $study = $product->study;

        $lfTemp = doubleval($this->convert->prodTemperature($stemp, 16, 0));

        $i = $k = 0;

        $listTemp = [];

        for ($i = 0; $i < $X; $i++) {
            for ($k = 0; $k < $Z; $k++) {
                // save node temperature
                $temp = new InitialTemperature();
                $temp->ID_PRODUCTION = ($study->ID_PRODUCTION);
                $temp->MESH_1_ORDER = ($i);
                $temp->MESH_2_ORDER = $valueY;
                $temp->MESH_3_ORDER = ($k);
                $temp->INITIAL_T = ($lfTemp);
                
                array_push($listTemp, $temp->toArray());
            } 
        } 

        $slices = array_chunk($listTemp, 100);
        foreach ($slices as $slice) {
            InitialTemperature::insert($slice);
        }
    }

    public function IsMeshPositionCalculate($idProductionElmt)
    {   
        $etat = false;
        $meshPosition = MeshPosition::where('ID_PRODUCT_ELMT', $idProductionElmt)->first();
        if ($meshPosition) {
            $etat = true;
        }

        return $etat;
    }

    public function IsThereSomeInitialTemperature($idProduction)
    {
        $etat = false;
        $initialTemperatures = InitialTemperature::where('ID_PRODUCTION', $idProduction)->get();
        if (count($initialTemperatures) > 0) {
            $etat = true;
        }

        return $etat;
    }

    public function propagationTempProdIso(Product &$product, $x, $y, $z, $stemp)
    {
        $listTemp = [];
        $i = $j = $k = 0;
        $t = null;
        $study = $product->study;
        $lfTemp = floatval($this->units->prodTemperature($stemp));

        for ($i = 0; $i < $x; $i++) {
            for ($j = 0; $j < $y; $j++) {
                for ($k = 0; $k < $z; $k++) {
                    $t = new InitialTemperature();
                    $t->ID_PRODUCTION = ($study->ID_PRODUCTION);
                    $t->MESH_1_ORDER = $i;
                    $t->MESH_2_ORDER = $j;
                    $t->MESH_3_ORDER = $k;
                    $t->INITIAL_T = $lfTemp;
                    array_push($listTemp, $t->toArray());
                }
            }
        }

        $slices = array_chunk($listTemp, 100);
        foreach ($slices as $slice) {
            InitialTemperature::insert($slice);
        }
    }

    public function checkRunKernelToolCalculator($ID_STUDY)
    {
        $check = false;
        $study = Study::find($ID_STUDY);
        $idParentStudy = null;
        if ($study) {
            $idParentStudy = $study->PARENT_ID;
            if ($idParentStudy != 0) {
                $productCurrent = Product::where('ID_STUDY', $ID_STUDY)->first();
                $productParent = Product::where('ID_STUDY', $idParentStudy)->first();
                if ($productCurrent && $productParent) {
                    $emltCurrents = ProductElmt::where('ID_PROD', $productCurrent->ID_PROD)->get();
                    $emltParents = ProductElmt::where('ID_PROD', $productParent->ID_PROD)->get();
                    if (count($emltCurrents) == count($emltParents)) {
                        $check = true;
                    }
                }
            }
        }

        return $check;
    }

    public function calculateNumberPoint3D(MeshGeneration &$meshGeneration, ProductElmt &$elmt)
    {
        $positions = [];
        $points = [];
        $startPoint = $endPoint = $position = $numberPoint = null;
        $meshSize2 = null;

        if ($meshGeneration->MESH_1_FIXED == 1) {
            if (floatval($meshGeneration->MESH_2_SIZE) != 0 ) {
                $numberPoint = intval($this->units->meshesUnit($elmt->SHAPE_PARAM2) / $meshGeneration->MESH_2_SIZE);
                $meshSize2 = $meshGeneration->MESH_2_SIZE;
            }
        } else {
            $numberPoint = intval(log10(1.0 - ($this->units->meshesUnit($elmt->SHAPE_PARAM2) / $meshGeneration->MESH_2_INT) * (1 - $meshGeneration->MESH_2_RATIO)) / log10($meshGeneration->MESH_2_RATIO));

            if ($numberPoint != 0) {
                $meshSize2 = floatval($this->units->meshesUnit($elmt->SHAPE_PARAM2)) / floatval($numberPoint);
            }
        }

        $startPoint = floatval($elmt->SHAPE_POS2);
        $endPoint = floatval($elmt->SHAPE_POS2) + floatval($elmt->SHAPE_PARAM2);
        $position = $this->units->meshesUnit($startPoint);

        array_push($positions, $this->units->meshesUnit($startPoint));

        if ($numberPoint > 0) {
            for ($i = 1; $i < $numberPoint - 1 ; $i++) {
                $position = $position + floatval($meshSize2);
                array_push($positions, round($position, 2));
            }
        }

        array_push($positions, $this->units->meshesUnit($endPoint));

        rsort($positions);

        $initTemp3Ds = InitTemp3D::where('ID_PRODUCT_ELMT', $elmt->ID_PRODUCT_ELMT)->get();
        if (count($initTemp3Ds) == 1) {
            for ($i = 0; $i < count($positions); $i++) {
                array_push($points, $this->convert->temperature($initTemp3Ds[0]->INIT_TEMP, 2, 0));
            }            
        } else {
            foreach ($initTemp3Ds as $init3D) {
                array_push($points, $this->convert->temperature($init3D->INIT_TEMP, 2, 0));
            }
        }

        $points = array_reverse($points);

        return compact('points', 'positions');
    }

    public function getElmtInitTemp(ProductElmt &$elmt)
    {
        $initTemp = [];
        $initTemp3Ds = InitTemp3D::where('ID_PRODUCT_ELMT', $elmt->ID_PRODUCT_ELMT)->get();
        if (count($initTemp3Ds) > 0) {
            foreach ($initTemp3Ds as $init3d) {
                array_push($initTemp, $this->convert->temperature($init3d->INIT_TEMP, 2, 0));
            }
        }

        return $initTemp;
    }
}