<?php

namespace App\Cryosoft;

use App\Cryosoft\ValueListService;
use App\Cryosoft\UnitsConverterService;

class DimaResultsService
{
    public function __construct(\Laravel\Lumen\Application $app)
    {
        $this->app = $app;
        $this->value = $app['App\\Cryosoft\\ValueListService'];
        $this->unit = $app['App\\Cryosoft\\UnitsConverterService'];
    }

    public function getCalculationStatus($dimaStatus)
    {
        $ldStatus = $dimaStatus & 0xFFFF;
        return $ldStatus;
    }

    public function getCalculationWarning($param)
    {
        $r = $param & 0xFFFF0000;
        $r >>= 16;
        return $r;
    }

    public function isConsoToDisplay($dimaStatus)
    {
        return (($this->getCalculationStatus($dimaStatus) & 0x100) == 0) ? true : false;
    }

    public function consumptionCell($lfcoef, $calculationStatus, $valueStr) {
        $sConso = [];

        if ($calculationStatus != 0) {
            if (($calculationStatus == 1) && ($lfcoef == 0.0)) {
                $sConso["value"] = "****";
                $sConso["warning"] = "";
            } else if ($calculationStatus == 1) {
                $sConso["value"] = $valueStr;
                $sConso["warning"] = "";
            } else if (($calculationStatus & 0x100) != 0) {
                $sConso["value"] = "";
                $sConso["warning_fluid"] = "";
                if (($calculationStatus & 0x10) != 0) {
                    $sConso["warning_fluid"] = "warning_dhp";
                }

            } else if (($calculationStatus & 0x10) != 0) {
                $sConso["value"] = $valueStr;
                $sConso["warning"] = "warning_dhp_value";
            }

        } else {
            $sConso["value"] = "****";
            $sConso["warning"] = "";
        }

        return $sConso;
    }
    
    
}
