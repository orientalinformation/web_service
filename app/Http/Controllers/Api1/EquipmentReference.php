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

use Illuminate\Contracts\Auth\Factory as Auth;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Mesh3DInfo;
use App\Models\ProductElmt;
use App\Models\Equipment;
use App\Models\Product;
use App\Models\EquipGeneration;
use App\Models\Study;

class EquipmentReference extends Controller
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

  public function getEquipment($id)
  {
    $equipment = Equipment::find($id);

    if ($equipment) {
      $equipment->capabilitiesCalc = $this->equip->getCapability($equipment->CAPABILITIES, 65536);
      $equipment->capabilitiesCalc256 = $this->equip->getCapability($equipment->CAPABILITIES, 256);
      $equipment->timeSymbol = $this->converts->timeSymbolUser();
      $equipment->temperatureSymbol = $this->converts->temperatureSymbolUser();
      $equipment->dimensionSymbol = $this->converts->equipDimensionSymbolUser();
      $equipment->consumptionSymbol1 = $this->converts->consumptionSymbolUser($equipment->ID_COOLING_FAMILY, 1);
      $equipment->consumptionSymbol2 = $this->converts->consumptionSymbolUser($equipment->ID_COOLING_FAMILY, 2);
      $equipment->consumptionSymbol3 = $this->converts->consumptionSymbolUser($equipment->ID_COOLING_FAMILY, 3);
      $equipment->shelvesWidthSymbol = $this->converts->shelvesWidthSymbol();
      $equipment->rampsPositionSymbol = $this->converts->rampsPositionSymbol();
      $equipment->EQP_LENGTH = $this->units->equipDimension($equipment->EQP_LENGTH, 2, 1);            
      $equipment->EQP_WIDTH = $this->units->equipDimension($equipment->EQP_WIDTH, 2, 1);
      $equipment->EQP_HEIGHT = $this->units->equipDimension($equipment->EQP_HEIGHT, 2, 1);
      $equipment->MAX_FLOW_RATE = doubleval($this->units->consumption($equipment->MAX_FLOW_RATE, $equipment->ID_COOLING_FAMILY, 1, 2, 1));
      $equipment->TMP_REGUL_MIN = $this->units->controlTemperature($equipment->TMP_REGUL_MIN, 0, 1);

      $equipGener = EquipGeneration::find($equipment->ID_EQUIPGENERATION);
  
      if ($equipGener) { 
          $equipGener->TEMP_SETPOINT = doubleval($this->units->controlTemperature($equipGener->TEMP_SETPOINT, 2, 1));
          $equipGener->DWELLING_TIME = $this->units->time($equipGener->DWELLING_TIME, 2, 1);
          $equipGener->NEW_POS = $this->units->time($equipGener->NEW_POS, 2, 1);
      }
      $equipment->equipGeneration = $equipGener;
    }

    return $equipment;
  }
}