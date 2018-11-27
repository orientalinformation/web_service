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
use App\Models\Study;
use Illuminate\Http\Request;
use Illuminate\Contracts\Auth\Factory as Auth;
use App\Models\StudyEquipment;
use App\Models\Product;
use App\Models\ProductElmt;
use App\Models\Equipment;

class Chaining extends Controller 
{
    protected $request;
    protected $auth;
    protected $equip;
    protected $kernel;
    protected $unit;
    protected $value;
    protected $converts;

    public function __construct(\Laravel\Lumen\Application $app)
    {
      $this->app = $app;
      $this->auth = $app['Illuminate\\Contracts\\Auth\\Factory'];
      $this->request = $app['Illuminate\\Http\\Request'];
      $this->equip = $app['App\\Cryosoft\\EquipmentsService'];
      $this->kernel = $app['App\\Kernel\\KernelService'];
      $this->units = $app['App\\Cryosoft\\UnitsService'];
      $this->values = $app['App\\Cryosoft\\ValueListService'];
      $this->converts = $app['App\\Cryosoft\\UnitsConverterService'];
    }

    public function getAllChaining($id) 
    {
      $study = Study::findOrFail($id);

      $chaining = $chain = [];

      $chain['studyName'] = $study->STUDY_NAME;
      $chain['ID_STUDY'] = $id;
      $chain['active'] = 1;
      array_push($chaining, $chain);

      $parents  = $this->getParentChaining($id);
      if (count($parents) > 0) {
        foreach ($parents as $parent) {
          array_push($chaining, $parent);
        }
      }

      $childrens = $this->getChildChaining($id);
      if (count($childrens) > 0) {
        foreach ($childrens as $child) {
          array_push($chaining, $child);
        }
      }

      array_multisort(array_column($chaining, 'ID_STUDY'), SORT_ASC, $chaining); 

      return $chaining;
    }

    public function getOverViewChaining($id)
    {
      $study = Study::findOrFail($id);

      $dataStudies = $chainings = [];

      $chain['ID_STUDY'] = $study->ID_STUDY;
      $chain['PARENT_STUD'] = $study->PARENT_STUD_EQP_ID;
      $chain['HAS_CHILD'] = $study->HAS_CHILD;
      array_push($dataStudies, $chain);

      $parents  = $this->getParentOverviewChaining($id);
      if (count($parents) > 0) {
        foreach ($parents as $parent) {
          array_push($dataStudies, $parent);
        }
      }

      $childrens = $this->getChildOverviewChaining($id);
      if (count($childrens) > 0) {
        foreach ($childrens as $child) {
          array_push($dataStudies, $child);
        }
      }
      array_multisort(array_column($dataStudies, 'ID_STUDY'), SORT_ASC, $dataStudies); 

      foreach ($dataStudies as $data) {
        $item['ID_STUDY'] = $data['ID_STUDY'];
        $item['hasSEquipment'] = 1;

        $prod = Product::where('ID_STUDY', $data['ID_STUDY'])->first();
        if ($prod) {
          $prodEmlts = ProductElmt::where('ID_PROD', $prod->ID_PROD)->get();

          $shape =  $prod->productElmts->first()->ID_SHAPE;
          switch ($shape) {
            case 6:
            case 14:
              $item['shape'] = 'sphere';
              break;
            case 9:
            case 17:
              $item['shape'] = 'bread';
              break;
            default:
              $item['shape'] = 'layers';
              break;
          }
          $item['layer'] = count($prodEmlts);
        }

        $studyEquipments = StudyEquipment::where('ID_STUDY', $data['ID_STUDY'])->get();

        if (count($studyEquipments) > 0) {
          $nameEquipments = $iname = [];
          foreach ($studyEquipments as $sequip) {
            $iname['isChaining'] = $this->checkStudyEquipment($sequip->ID_STUDY_EQUIPMENTS, $dataStudies);

            $equipment = Equipment::find($sequip->ID_EQUIP);
            $iname['name'] = $equipment->EQUIP_NAME;

            array_push($nameEquipments, $iname);
          }

          array_multisort(array_column($nameEquipments, 'isChaining'), SORT_DESC, $nameEquipments); 

          $item['StudyEquipment'] = $nameEquipments;
        } else {
          $item['StudyEquipment'] = null;
        }

        array_push($chainings, $item);
      }

      return $chainings;
    }

    public function clearParentChaining($id)
    {
      $chaining = [];

      $parents  = $this->getParentChaining($id);
      if (count($parents) > 0) {
        foreach ($parents as $parent) {
          array_push($chaining, $parent);
        }
      }

      $childrens = $this->getChildChaining($id);
      if (count($childrens) > 0) {
        foreach ($childrens as $child) {
          array_push($chaining, $child);
        }
      }

      array_multisort(array_column($chaining, 'ID_STUDY'), SORT_ASC, $chaining); 

      foreach ($chaining as $chain) {
        if (intval($chain['ID_STUDY']) > $id) {

          $conf = $this->kernel->getConfig($this->auth->user()->ID_USER, intval($chain['ID_STUDY']), -1);
          $this->kernel->getKernelObject('StudyCleaner')->SCStudyClean($conf, SC_CLEAN_OUTPUT_CALCUL); // SC_CLEAN_OUTPUT_CALCUL SC_CLEAN_OUTPUT_ALL

          return $chain;
        }
      }

      return $chaining;
    }

    private function getParentChaining($idStudy, $chaining = [])
    {
      $study = Study::findOrFail($idStudy);
      $parent = null;
      if ($study->PARENT_ID != 0) {
          $parent = Study::findOrFail($study->PARENT_ID);
          $chain['studyName'] = $parent->STUDY_NAME;
          $chain['ID_STUDY'] = $parent->ID_STUDY;
          $chain['active'] = 0;
          array_push($chaining, $chain);

          $chaining = $this->getParentChaining($study->PARENT_ID, $chaining);
      }

      return $chaining;
    }

    private function getParentOverviewChaining($idStudy, $chaining = []) 
    {
      $study = Study::findOrFail($idStudy);
      $parent = null;
      if ($study->PARENT_ID != 0) {
        $parent = Study::findOrFail($study->PARENT_ID);
        $chain['ID_STUDY'] = $parent->ID_STUDY;
        $chain['PARENT_STUD'] = $parent->PARENT_STUD_EQP_ID;
        $chain['HAS_CHILD'] = $parent->HAS_CHILD;
        array_push($chaining, $chain);

        $chaining = $this->getParentOverviewChaining($study->PARENT_ID, $chaining);
      }

      return $chaining;
    }

    private function getChildChaining($idStudy, $chaining = [])
    {
      $study = Study::findOrFail($idStudy);
      $children = null;
      if ($study->HAS_CHILD != 0) {
          $children = Study::where('PARENT_ID', $study->ID_STUDY)->first();
          if (count($children) > 0) {
              array_push($chaining, [
                  'studyName' => $children->STUDY_NAME,
                  'ID_STUDY' => $children->ID_STUDY,
                  'active' => 0
              ]);

              $chaining = $this->getChildChaining($children->ID_STUDY, $chaining);
          }
      }
      return $chaining;
    }

    private function getChildOverviewChaining($idStudy, $chaining = [])
    {
      $study = Study::findOrFail($idStudy);
      $children = null;
      if ($study->HAS_CHILD != 0) {
          $children = Study::where('PARENT_ID', $study->ID_STUDY)->first();
          if (count($children) > 0) {
              $chain['ID_STUDY'] = $children->ID_STUDY;
              $chain['PARENT_STUD'] = $children->PARENT_STUD_EQP_ID;
              $chain['HAS_CHILD'] = $children->HAS_CHILD;
              array_push($chaining, $chain);

              $chaining = $this->getChildOverviewChaining($children->ID_STUDY, $chaining);
          }
      }
      return $chaining;
    }

    private function checkStudyEquipment($idStudyEquipment, $dataStudies)
    {
      foreach ($dataStudies as $data) {
        if ((intval($data['PARENT_STUD']) == intval($idStudyEquipment))) {
          return 1;
        } 
      }
      return 0;
    }

    public function getValueProgressStudyEquipment()
    {
      $input = $this->request->all();
      $idStudy = null;
      $progress = array();

      $public_path = rtrim(app()->basePath("public"), '/');
      foreach (scandir($public_path . '/3d/') as $item) {
        if ($item == '.' || $item == '..') {
            continue;
        }
        array_push($progress, $item);
      }

      return $progress;
    }

    public function selectCalculate($id)
    {
      $input = $this->request->all();

      if (isset($input['run_calcuate'])) $runCalcuate = $input['run_calcuate'];

      $sEquipment = StudyEquipment::find($id);
      if ($sEquipment) {
        $sEquipment->RUN_CALCULATE = ($runCalcuate == "true") ? 1 : 0;
        $sEquipment->save();
      }
    }
}