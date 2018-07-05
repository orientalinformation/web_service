<?php

namespace App\Cryosoft;

use App\Cryosoft\ValueListService;
use App\Cryosoft\UnitsConverterService;

class EconomicResultsService
{
    public function __construct(\Laravel\Lumen\Application $app)
    {
        $this->app = $app;
        $this->value = $app['App\\Cryosoft\\ValueListService'];
        $this->unit = $app['App\\Cryosoft\\UnitsConverterService'];
    }


    public function isConsoToDisplay($dimaStatus, $equipStatus)
    {
        $dimaStatus = $dimaStatus & 0xFFFF;
        $ldStatus = true;
      
        if ($equipStatus == 1) {
            if ($dimaStatus != 0) {
                if ($dimaStatus == 1) {
                    $ldStatus = true;
                } else if (($dimaStatus & 0x100) != 0) {
                    $ldStatus = false;
                } else {
                    $ldStatus = true;
                }     
            } else {
                $ldStatus = false;
            }
        } else {
            $ldStatus = false;
        }
      
      return $ldStatus;
    }    
}
