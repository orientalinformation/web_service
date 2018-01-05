<?php

namespace App\Cryosoft;

use App\Cryosoft\ValueListService;
use App\Cryosoft\UnitsConverterService;
use App\Models\TempRecordPts;
use App\Models\TempRecordData;

class OutputService
{
    public function __construct(\Laravel\Lumen\Application $app)
    {
        $this->app = $app;
        $this->value = $app['App\\Cryosoft\\ValueListService'];
        $this->unit = $app['App\\Cryosoft\\UnitsConverterService'];
    }


    public function calculateEchantillon($ldNbSample, $ldNbRecord, $lfDwellingTime, $lfTimeStep) 
    {
        $tdSamplePos = array();
        $pos = 0;
        $lfSampleTime = 0.0;

        if ($ldNbSample > $ldNbRecord) {
            $ldNbSample = $ldNbRecord;
        }

        $lfSampleTime = $lfDwellingTime / ($ldNbSample - 1);

        for ($i = 0; $i < $ldNbSample - 1; $i++) {
            $pos = round($i * $lfSampleTime / $lfTimeStep);
            $tdSamplePos[] = $pos;
        }

        $pos = $ldNbRecord - 1;
        $tdSamplePos[] = $pos;

        return $tdSamplePos;
    } 

    public function getTemperaturePosition($idRecPos, $axis1, $axis2)
    {
        return TempRecordData::where("ID_REC_POS", $idRecPos)->where("REC_AXIS_X_POS", $axis1)->where("REC_AXIS_Y_POS", $axis2)->where("REC_AXIS_Z_POS", 0)->first();
    }
}
