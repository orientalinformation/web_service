<?php

namespace App\Cryosoft;

use Illuminate\Support\Facades\DB;
use App\Cryosoft\ValueListService;
use App\Cryosoft\UnitsConverterService;
use App\Models\TempRecordPts;
use App\Models\TempRecordData;
use App\Models\TempRecordPtsDef;
use App\Models\MeshPosition;
use App\Models\StudyEquipment;
use App\Models\RecordPosition;
use App\Models\ProductElmt;
use App\Models\LayoutGeneration;
use App\Models\StudEqpPrm;

class OutputService
{
    public function __construct(\Laravel\Lumen\Application $app)
    {
        $this->app = $app;
        $this->auth = $app['Illuminate\\Contracts\\Auth\\Factory'];
        $this->value = $app['App\\Cryosoft\\ValueListService'];
        $this->unit = $app['App\\Cryosoft\\UnitsConverterService'];
        $this->stdeqp = $app['App\\Cryosoft\\StudyEquipmentService'];
        $this->equip = $app['App\\Cryosoft\\EquipmentsService'];
        $this->brain = $app['App\\Cryosoft\\BrainCalculateService'];
        $this->cal = $app['App\\Cryosoft\\CalculateService'];
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
            $tdSamplePos[] = ($pos < 0) ? 0 : $pos;
        }

        $pos = $ldNbRecord - 1;
        $tdSamplePos[] = $pos;

        return $tdSamplePos;
    } 

    public function getTemperaturePosition($idRecPos, $axis1, $axis2)
    {
        return TempRecordData::where("ID_REC_POS", $idRecPos)->where("REC_AXIS_X_POS", $axis1)->where("REC_AXIS_Y_POS", $axis2)->where("REC_AXIS_Z_POS", 0)->first();
    }

    public function convertMeshForAppletDim($ldShape, $bIsParallel, $dbDim)
    {
        $appDim = [];
        switch ($ldShape) {
            case 1:
                $appDim = $dbDim;
                break;

            case 2:
            case 9:
                if ($bIsParallel) {
                    $appDim = array_reverse($dbDim);
                } else {
                    $appDim = $dbDim;
                }
                break;

            case 3:
                if ($bIsParallel) {
                    $appDim[0] = $dbDim[2];
                    $appDim[1] = $dbDim[0];
                    $appDim[2] = $dbDim[1];
                } else {
                    $appDim[0] = $dbDim[1];
                    $appDim[1] = $dbDim[0];
                    $appDim[2] = $dbDim[2];
                }
                break;

            case 4:
            case 5:
                $appDim[0] = $dbDim[0];
                $appDim[1] = $dbDim[1];
                $appDim[2] = "";
                break;

            case 7:
            case 8:
                $appDim[0] = $dbDim[1];
                $appDim[1] = $dbDim[0];
                $appDim[2] = "";
                break;

            case 6:
                $appDim[0] = "";
                $appDim[1] = $dbDim[1];
                $appDim[2] = "";
                break;

            default:
                $appDim[] = ["","",""];
                break;
        }

        return $appDim;
    }

    public function convertPointForAppletDim($ldShape, $bIsParallel, $dbDim)
    {
        $appDim = [];
        switch ($ldShape) {
            case 1:
                $appDim = $dbDim;
                break;

            case 2:
            case 9:
                if ($bIsParallel) {
                    $appDim = array_reverse($dbDim);
                } else {
                    $appDim = $dbDim;
                }
                break;

            case 3:
                if ($bIsParallel) {
                    $appDim[0] = $dbDim[2];
                    $appDim[1] = $dbDim[0];
                    $appDim[2] = $dbDim[1];
                } else {
                    $appDim[0] = $dbDim[1];
                    $appDim[1] = $dbDim[0];
                    $appDim[2] = $dbDim[2];
                }
                break;

            case 4:
            case 5:
                $appDim[0] = $dbDim[0];
                $appDim[1] = $dbDim[1];
                $appDim[2] = 0.0;
                break;

            case 7:
            case 8:
                $appDim[0] = $dbDim[1];
                $appDim[1] = $dbDim[0];
                $appDim[2] = 0.0;
                break;

            case 6:
                $appDim[0] = 0.0;
                $appDim[1] = $dbDim[1];
                $appDim[2] = 0.0;
                break;

            default:
                $appDim[] = [0.0, 0.0, 0.0];
                break;
        }


        return $appDim;
    }

    public function convertAxisForAppletDim($ldShape, $bIsParallel, $dbAxe)
    {
        $appAxe = [];
        switch ($ldShape) {
            case 1:
                $appAxe[0] = $this->convertPointForAppletDim($ldShape, $bIsParallel, $dbAxe[0]);
                $appAxe[1] = $this->convertPointForAppletDim($ldShape, $bIsParallel, $dbAxe[1]);
                $appAxe[2] = $this->convertPointForAppletDim($ldShape, $bIsParallel, $dbAxe[2]);
                break;

            case 2:
            case 9:
                if ($bIsParallel) {
                    $appAxe[0] = $this->convertPointForAppletDim($ldShape, $bIsParallel, $dbAxe[2]);
                    $appAxe[1] = $this->convertPointForAppletDim($ldShape, $bIsParallel, $dbAxe[1]);
                    $appAxe[2] = $this->convertPointForAppletDim($ldShape, $bIsParallel, $dbAxe[0]);
                } else {
                    $appAxe[0] = convertPointForAppletDim($ldShape, $bIsParallel, $dbAxe[0]);
                    $appAxe[1] = convertPointForAppletDim($ldShape, $bIsParallel, $dbAxe[1]);
                    $appAxe[2] = convertPointForAppletDim($ldShape, $bIsParallel, $dbAxe[2]);
                }
                break;

            case 3:
                if ($bIsParallel) {
                    $appAxe[0] = $this->convertPointForAppletDim($ldShape, $bIsParallel, $dbAxe[2]);
                    $appAxe[1] = $this->convertPointForAppletDim($ldShape, $bIsParallel, $dbAxe[0]);
                    $appAxe[2] = $this->convertPointForAppletDim($ldShape, $bIsParallel, $dbAxe[1]);
                } else {
                    $appAxe[0] = $this->convertPointForAppletDim($ldShape, $bIsParallel, $dbAxe[1]);
                    $appAxe[1] = $this->convertPointForAppletDim($ldShape, $bIsParallel, $dbAxe[0]);
                    $appAxe[2] = $this->convertPointForAppletDim($ldShape, $bIsParallel, $dbAxe[2]);
                }
                break;

            case 4:
            case 5:
                $appAxe[0] = $this->convertPointForAppletDim($ldShape, $bIsParallel, $dbAxe[0]);
                $appAxe[1] = $this->convertPointForAppletDim($ldShape, $bIsParallel, $dbAxe[1]);
                $appAxe[2] = "";
                break;
            
            case 7:
            case 8:
                $appAxe[0] = $this->convertPointForAppletDim($ldShape, $bIsParallel, $dbAxe[1]);
                $appAxe[1] = $this->convertPointForAppletDim($ldShape, $bIsParallel, $dbAxe[0]);
                $appAxe[2] = "";
                break;

            case 6:
                $appAxe[0] = "";
                $appAxe[1] = $this->convertPointForAppletDim($ldShape, $bIsParallel, $dbAxe[1]);
                $appAxe[2] = "";
                break;

            default:
                $appAxe = ["", "", ""];
        }
    }

    public function getSelectedMeshPoint($iType, $iObj)
    {
        
    }

    public function getSelectedMeshPoints($idStudy)
    {
        $trp = TempRecordPts::where("ID_STUDY", $idStudy)->first();
        $meshSel = [];
        if ($trp) {
            $meshSel = array_merge($meshSel, array(
                $trp->AXIS1_PT_TOP_SURF,
                $trp->AXIS2_PT_TOP_SURF,
                $trp->AXIS3_PT_TOP_SURF,
                $trp->AXIS1_PT_INT_PT,
                $trp->AXIS2_PT_INT_PT,
                $trp->AXIS3_PT_INT_PT,
                $trp->AXIS1_PT_BOT_SURF,
                $trp->AXIS2_PT_BOT_SURF,
                $trp->AXIS3_PT_BOT_SURF,
                $trp->AXIS2_AX_1,
                $trp->AXIS3_AX_1,
                $trp->AXIS1_AX_2,
                $trp->AXIS3_AX_2,
                $trp->AXIS1_AX_3,
                $trp->AXIS2_AX_3,
                $trp->AXIS1_PL_2_3,
                $trp->AXIS2_PL_1_3,
                $trp->AXIS3_PL_1_2
            ));
        }
        
        return $meshSel;   
    }

    public function getMeshSelectionDef()
    {
        $userID = $this->auth->user()->ID_USER;

        $trp = TempRecordPtsDef::where('id_USER', $userID)->first();
        $meshSel = [];
        $meshSel = array_merge($meshSel, array(
            $trp->AXIS1_PT_TOP_SURF_DEF,
            $trp->AXIS2_PT_TOP_SURF_DEF,
            $trp->AXIS3_PT_TOP_SURF_DEF,
            $trp->AXIS1_PT_INT_PT_DEF,
            $trp->AXIS2_PT_INT_PT_DEF,
            $trp->AXIS3_PT_INT_PT_DEF,
            $trp->AXIS1_PT_BOT_SURF_DEF,
            $trp->AXIS2_PT_BOT_SURF_DEF,
            $trp->AXIS3_PT_BOT_SURF_DEF,
            $trp->AXIS2_AX_1_DEF,
            $trp->AXIS3_AX_1_DEF,
            $trp->AXIS1_AX_2_DEF,
            $trp->AXIS3_AX_2_DEF,
            $trp->AXIS1_AX_3_DEF,
            $trp->AXIS2_AX_3_DEF,
            $trp->AXIS1_PL_2_3_DEF,
            $trp->AXIS2_PL_1_3_DEF,
            $trp->AXIS3_PL_1_2_DEF
        ));

        return $meshSel;
    }

    public function getTempRecordData($idRecPos, $idStudy, $axeTempRecordData, $selectedAxe, $shape, $orientation)
    {
        $result = "";

        if ($axeTempRecordData[$selectedAxe][0] == -1.0) {
            switch ($shape) {
                case 1: 
                case 6: 
                    $result = TempRecordData::where("ID_REC_POS", $idRecPos)->get();
                    break;

                case 2: 
                case 9: 
                    if ($orientation == 1) {
                        $rMeshPositionY = $this->getPositionForAxis2($idStudy, $axeTempRecordData[$selectedAxe][1], 2);
                        $rMeshPositionX = $this->getPositionForAxis2($idStudy, $axeTempRecordData[$selectedAxe][2], 3);

                        if ($rMeshPositionX && $rMeshPositionY) {
                            $result = TempRecordData::where("ID_REC_POS", $idRecPos)->where('REC_AXIS_Y_POS', $rMeshPositionY->MESH_ORDER)->where('REC_AXIS_X_POS', $rMeshPositionX->MESH_ORDER)->orderBy('REC_AXIS_Z_POS', 'ASC')->get();
                        }
                    } else {
                        $rMeshPositionY = $this->getPositionForAxis2($idStudy, $axeTempRecordData[$selectedAxe][1], 2);

                        $result = TempRecordData::where("ID_REC_POS", $idRecPos)->where('REC_AXIS_Y_POS', $rMeshPositionY->MESH_ORDER)->where('REC_AXIS_Z_POS', 0)->orderBy('REC_AXIS_X_POS', 'ASC')->get();
                    }
                    break;

                case 3:
                case 5:
                case 7:
                    $rMeshPosition = $this->getPositionForAxis2($idStudy, $axeTempRecordData[$selectedAxe][1], 2);

                    if ($rMeshPosition) $result = TempRecordData::where("ID_REC_POS", $idRecPos)->where('REC_AXIS_X_POS', $rMeshPosition->MESH_ORDER)->where('REC_AXIS_Z_POS', 0)->orderBy('REC_AXIS_Y_POS', 'ASC')->get();
                    break;

                case 4:
                case 8: 
                    $rMeshPosition = $this->getPositionForAxis2($idStudy, $axeTempRecordData[$selectedAxe][1], 2);

                    if ($rMeshPosition) $result = TempRecordData::where("ID_REC_POS", $idRecPos)->where('REC_AXIS_Y_POS', $rMeshPosition->MESH_ORDER)->where('REC_AXIS_Z_POS', 0)->orderBy('REC_AXIS_X_POS', 'ASC')->get();
                    break;
            }
        }

        if ($axeTempRecordData[$selectedAxe][1] == -1.0) {
            switch ($shape) {
                case 1: 
                case 6: 
                    $result = TempRecordData::where("ID_REC_POS", $idRecPos)->where('REC_AXIS_X_POS', 0)->where('REC_AXIS_Z_POS', 0)->orderBy('REC_AXIS_Y_POS', 'ASC')->get();
                    break;

                case 2: 
                case 9: 
                    $rMeshPosition = $this->getPositionForAxis2($idStudy, $axeTempRecordData[$selectedAxe][2], 3);

                    if ($rMeshPosition) $result = TempRecordData::where("ID_REC_POS", $idRecPos)->where('REC_AXIS_X_POS', $rMeshPosition->MESH_ORDER)->where('REC_AXIS_Z_POS', 0)->orderBy('REC_AXIS_Y_POS', 'ASC')->get();
                    
                    break;

                case 3: 
                case 5:
                case 7:
                    $rMeshPosition = $this->getPositionForAxis2($idStudy, $axeTempRecordData[$selectedAxe][0], 1);

                    if ($rMeshPosition) $result = TempRecordData::where("ID_REC_POS", $idRecPos)->where('REC_AXIS_Y_POS', $rMeshPosition->MESH_ORDER)->where('REC_AXIS_Z_POS', 0)->orderBy('REC_AXIS_X_POS', 'ASC')->get();
                    break;

                case 4:
                case 8: 
                    $rMeshPosition = $this->getPositionForAxis2($idStudy, $axeTempRecordData[$selectedAxe][0], 1);

                    if (!empty($rMeshPosition)) $result = TempRecordData::where("ID_REC_POS", $idRecPos)->where('REC_AXIS_X_POS', $rMeshPosition->MESH_ORDER)->where('REC_AXIS_Z_POS', 0)->orderBy('REC_AXIS_Y_POS', 'ASC')->get();
                    break;
            }
        }

        if ($axeTempRecordData[$selectedAxe][2] == -1.0) {
            switch ($shape) {
                case 1: 
                case 4: 
                case 5: 
                case 6: 
                case 7: 
                case 8: 
                    $result = TempRecordData::where("ID_REC_POS", $idRecPos)->get();
                    break;

                case 2: 
                case 9: 
                    if ($orientation == 1) {
                        $rMeshPosition = $this->getPositionForAxis2($idStudy, $axeTempRecordData[$selectedAxe][1], 2);

                        if ($rMeshPosition) $result = TempRecordData::where("ID_REC_POS", $idRecPos)->where('REC_AXIS_Y_POS', $rMeshPosition->MESH_ORDER)->where('REC_AXIS_Z_POS', 0)->orderBy('REC_AXIS_X_POS', 'ASC')->get();
                    } else {
                        $rMeshPositionX = $this->getPositionForAxis2($idStudy, $axeTempRecordData[$selectedAxe][0], 1);
                        $rMeshPositionY = $this->getPositionForAxis2($idStudy, $axeTempRecordData[$selectedAxe][1], 2);

                        if ($rMeshPositionX && $rMeshPositionY) $result = TempRecordData::where("ID_REC_POS", $idRecPos)->where('REC_AXIS_X_POS', $rMeshPositionX->MESH_ORDER)->where('rMeshPositionY', $rMeshPositionY->MESH_ORDER)->orderBy('REC_AXIS_Z_POS', 'ASC')->get();
                    }
                    
                    break;

                case 3: 
                    $rMeshPositionY = $this->getPositionForAxis2($idStudy, $axeTempRecordData[$selectedAxe][0], 1);
                    $rMeshPositionX = $this->getPositionForAxis2($idStudy, $axeTempRecordData[$selectedAxe][1], 2);

                    if ($rMeshPositionX && $rMeshPositionY) $result = TempRecordData::where("ID_REC_POS", $idRecPos)->where('REC_AXIS_Y_POS', $rMeshPositionY->MESH_ORDER)->where('REC_AXIS_X_POS', $rMeshPositionX->MESH_ORDER)->orderBy('REC_AXIS_Z_POS', 'ASC')->get();
                    break;
            }
        }

        return $result;
    }

    public function getAxisForPosition2($idStudy, $recAxis, $selectedAxe)
    {
        $result = "";
        $rMeshPosition = MeshPosition::where('ID_STUDY', $idStudy)->where('MESH_AXIS', $selectedAxe)->where('MESH_ORDER', $recAxis)->first();
        if ($rMeshPosition) $result = $this->unit->prodchartDimension($rMeshPosition->MESH_AXIS_POS);

        return $result;
    }

    public function getPositionForAxis2($idStudy, $axis, $meshAxis)
    {
        // $rMeshPosition = MeshPosition::where('ID_STUDY', $idStudy)->where('MESH_AXIS', $meshAxis)->where('MESH_AXIS_POS', $axis)->first();
        // $rMeshPosition = DB::table('MESH_POSITION')->join('PRODUCT_ELMT', 'MESH_POSITION.ID_PRODUCT_ELMT', '=', 'PRODUCT_ELMT.ID_PRODUCT_ELMT')->join('PRODUCT', 'PRODUCT_ELMT.ID_PROD', '=', 'PRODUCT.ID_PROD')->whereRaw('PRODUCT.ID_STUDY = '. $idStudy .' AND MESH_AXIS = '. $meshAxis .' AND CAST(MESH_AXIS_POS AS DECIMAL(10,9)) LIKE "%'. $axis .'%"')->first();
        
        if ($this->is_decimal($axis)) {
            $decimal = explode('.', $axis);
            $length = (strlen($decimal[1]) > 9) ? 9 : strlen($decimal[1]) + 1;
            $axisValue = (strlen($decimal[1]) > 9) ? 'CAST('. $axis .' AS DECIMAL(10,9))' : $axis;
        } else {
            $length = 9;
            $axisValue = $axis;
        }
        
        $rMeshPosition = DB::table('MESH_POSITION')->join('PRODUCT_ELMT', 'MESH_POSITION.ID_PRODUCT_ELMT', '=', 'PRODUCT_ELMT.ID_PRODUCT_ELMT')->join('PRODUCT', 'PRODUCT_ELMT.ID_PROD', '=', 'PRODUCT.ID_PROD')->whereRaw('PRODUCT.ID_STUDY = '. $idStudy .' AND MESH_AXIS = '. $meshAxis .' AND CAST(MESH_AXIS_POS AS DECIMAL(10,'. $length .')) = '. $axisValue .'')->first();

        return $rMeshPosition;
    }

    public function is_decimal( $val )
    {
        return is_numeric( $val ) && floor( $val ) != $val;
    }

    public function init2DContourTempInterval($idStudyEquipment, $recordTime, $tempInterval, $pasTemp)
    {
        $tempResult = [];
        $result = '';

        if ($recordTime < 0) {
            $tempRecordDataMin = TempRecordData::where('ID_STUDY_EQUIPMENTS', $idStudyEquipment)->orderBy('TEMP', 'ASC')->first();
            $tempRecordDataMax = TempRecordData::where('ID_STUDY_EQUIPMENTS', $idStudyEquipment)->orderBy('TEMP', 'DESC')->first();
            $tempResult = [$tempRecordDataMin->TEMP, $tempRecordDataMax->TEMP];
        } else {
            $tempRecordDataMin = DB::table('TEMP_RECORD_DATA')
            ->join('RECORD_POSITION', 'TEMP_RECORD_DATA.ID_REC_POS', '=', 'RECORD_POSITION.ID_REC_POS')
            ->whereRaw('RECORD_POSITION.ID_STUDY_EQUIPMENTS = '. $idStudyEquipment .' AND CAST(RECORD_POSITION.RECORD_TIME AS DECIMAL(10,1)) = '. $recordTime .'')
            ->orderBy('TEMP', 'ASC')
            ->first();
            $tempRecordDataMax = DB::table('TEMP_RECORD_DATA')
            ->join('RECORD_POSITION', 'TEMP_RECORD_DATA.ID_REC_POS', '=', 'RECORD_POSITION.ID_REC_POS')
            ->whereRaw('RECORD_POSITION.ID_STUDY_EQUIPMENTS = '. $idStudyEquipment .' AND CAST(RECORD_POSITION.RECORD_TIME AS DECIMAL(10,1)) = '. $recordTime .'')
            ->orderBy('TEMP', 'DESC')
            ->first();

            /*$tempRecordDataMin = TempRecordData::where('ID_STUDY_EQUIPMENTS', $idStudyEquipment)->where('RECORD_TIME', $recordTime)->orderBy('TEMP', 'ASC')->first();
            $tempRecordDataMax = TempRecordData::where('ID_STUDY_EQUIPMENTS', $idStudyEquipment)->where('RECORD_TIME', $recordTime)->orderBy('TEMP', 'DESC')->first();*/

            $tempResult = [$tempRecordDataMin->TEMP, $tempRecordDataMax->TEMP];
        }

        $bornesTemp = [];
        if (!empty($tempResult)) {
            if ($tempInterval[0] >= $tempInterval[1]) {
                $tempInterval[0] = $tempResult[0];
                $tempInterval[1] = $tempResult[1];
            } else {
                if ($tempInterval[0] > $tempResult[0]) {
                    $tempInterval[0] = $tempResult[0];
                }
                if ($tempInterval[1] < $tempResult[1]) {
                    $tempInterval[1] = $tempResult[1];
                }
            }
            $bornesTemp = [$this->unit->prodTemperature($tempInterval[0], ['save' => true]), $this->unit->prodTemperature($tempInterval[1], ['save' => true])];

            $result = $this->calculatePasTemp($bornesTemp[0], $bornesTemp[1], false, $pasTemp);
        }

        return $result;
    }

    protected function calculatePasTemp($lfTmin, $lfTMax, $auto, $pasTemp)
    {
        set_time_limit(1000);
        $tab = [];
        $dTMin = 0;
        $dTMax = 0;
        $dpas = 0;
        $dnbpas = 0;

        $dTMin = intval(floor($lfTmin));
        $dTMax = intval(ceil($lfTMax));

        if ($auto) {
            $dpas = intval(floor(abs($dTMax - $dTMin) / 14) - 1);
        } else {
            $dpas = intval(floor($pasTemp) - 1);
        }

        if ($dpas < 0) {
            $dpas = 0;
        }


        do {
            $dpas++;

            while ($dTMin % $dpas != 0) {
                $dTMin--;
            }

            while ($dTMax % $dpas != 0) {
                $dTMax++;
            }

            $dnbpas = abs($dTMax - $dTMin) / $dpas;
        } while ($dnbpas > 16);

        $tab = [$this->unit->prodTemperature($dTMin), $this->unit->prodTemperature($dTMax), $dpas];

        return $tab;
    }

    public function getAxisName($shape, $orientation, $selectedPlan)
    {
        $sAxe = [];

        switch ($shape) {
            case 1:
            case 6:
                break;

            case 2:
            case 9:
                switch ($selectedPlan) {
                    case 1:
                        $sAxe = [3, 2];
                        break;
                    case 2:
                        if ($orientation == 1) {
                            $sAxe = [3, 1];
                        } else {
                            $sAxe = [1, 3];
                        }
                        break;
                    case 3:
                        $sAxe = [1, 2];
                }

                break;

            case 3:
                switch ($selectedPlan) {
                    case 1:
                        $sAxe = [2, 3];
                        break;
                    case 2:
                        $sAxe = [3, 1];
                        break;
                    case 3:
                        $sAxe = [2, 1];
                }

                break;

            case 7:
                if ($selectedPlan == 3) {
                    $sAxe = [2, 1];
                }
                break;

            case 4:
                if ($selectedPlan == 3) {
                    $sAxe = [1, 2];
                }
                break;

            case 8:
                if ($selectedPlan == 3) {
                    $sAxe = [1, 2];
                }
                break;
            case 5:
                if ($selectedPlan == 3) {
                    $sAxe = [2, 1];
                }

                break;

            default:
                $sAxe = [1, 2, 3];
                break;
        }

        return $sAxe;
    }

    public function getGrideByPlan($idStudy, $idStudyEquipment, $time, $lfTmin, $lfTMax, $tempRecordDataPlan, $selectedPlan, $shape, $orientation)
    {
        // $recordPosition = RecordPosition::where("ID_STUDY_EQUIPMENTS", $idStudyEquipment)->where("RECORD_TIME", $time)->orderBy("RECORD_TIME", "DESC")->first();
        $recordPosition = DB::table('RECORD_POSITION')->whereRaw('ID_STUDY_EQUIPMENTS = '. $idStudyEquipment .' AND CAST(RECORD_TIME AS DECIMAL(10,1)) = '. $time .'')->orderBy("RECORD_TIME", "DESC")->first();

        $result = [];
        $tempRecordDatas = [];
        if (!empty($recordPosition)) {
            $tiRecPos = $this->getRecAxisPos($recordPosition->ID_REC_POS, (double) $lfTmin, (double) $lfTMax);
            if (!empty($tiRecPos)) {
                if ($selectedPlan == 0) {
                    $rMeshPosition = $this->getPositionForAxis2($idStudy, $tempRecordDataPlan[$selectedPlan][0], 1);

                    if (!empty($rMeshPosition)) {
                        $meshOrder = $rMeshPosition->MESH_ORDER;
                    } else {
                        $meshOrder = 0;
                    }

                    switch ($shape) {
                        case 1: 
                        case 4: 
                        case 5: 
                        case 6: 
                        case 7: 
                        case 8: 
                          break;

                        case 2: 
                        case 9: 
                            if ($orientation == 1) {
                                $tempRecordDatas = TempRecordData::select('REC_AXIS_X_POS', 'REC_AXIS_Y_POS', 'REC_AXIS_Z_POS', 'TEMP')->where("ID_REC_POS", $recordPosition->ID_REC_POS)->whereBetween('REC_AXIS_X_POS', [$tiRecPos['x'][0], $tiRecPos['x'][1]])->whereBetween('REC_AXIS_Y_POS', [$tiRecPos['y'][0], $tiRecPos['y'][1]])->where('REC_AXIS_Z_POS', 0)->orderBy('REC_AXIS_Y_POS', 'ASC')->orderBy('REC_AXIS_X_POS', 'ASC')->get();
                                if (count($tempRecordDatas)) {
                                    foreach ($tempRecordDatas as $tempRecordData) {
                                        $item['X'] = $this->getAxisForPosition2($idStudy, $tempRecordData->REC_AXIS_X_POS, 3);
                                        $item['Y'] = $this->getAxisForPosition2($idStudy, $tempRecordData->REC_AXIS_Y_POS, 2);
                                        $item['Z'] = $this->unit->prodTemperature($tempRecordData->TEMP);
                                        $result[] = $item;
                                    }
                                }
                            } else {
                                $tempRecordDatas = TempRecordData::select('REC_AXIS_X_POS', 'REC_AXIS_Y_POS', 'REC_AXIS_Z_POS', 'TEMP')->where("ID_REC_POS", $recordPosition->ID_REC_POS)->whereBetween('REC_AXIS_Y_POS', [$tiRecPos['y'][0], $tiRecPos['y'][1]])->whereBetween('REC_AXIS_Z_POS', [$tiRecPos['z'][0], $tiRecPos['z'][1]])->where('REC_AXIS_X_POS', $meshOrder)->orderBy('REC_AXIS_Y_POS', 'ASC')->orderBy('REC_AXIS_Z_POS', 'ASC')->get();
                                if (count($tempRecordDatas)) {
                                    foreach ($tempRecordDatas as $tempRecordData) {
                                        $item['X'] = $this->getAxisForPosition2($idStudy, $tempRecordData->REC_AXIS_Z_POS, 3);
                                        $item['Y'] = $this->getAxisForPosition2($idStudy, $tempRecordData->REC_AXIS_Y_POS, 2);
                                        $item['Z'] = $this->unit->prodTemperature($tempRecordData->TEMP);
                                        $result[] = $item;
                                    }
                                }
                            }
                            
                            break;
                        case 3: 
                            $tempRecordDatas = TempRecordData::select('REC_AXIS_X_POS', 'REC_AXIS_Y_POS', 'REC_AXIS_Z_POS', 'TEMP')->where("ID_REC_POS", $recordPosition->ID_REC_POS)->whereBetween('REC_AXIS_X_POS', [$tiRecPos['x'][0], $tiRecPos['x'][1]])->whereBetween('REC_AXIS_Z_POS', [$tiRecPos['z'][0], $tiRecPos['z'][1]])->where('REC_AXIS_Y_POS', $meshOrder)->orderBy('REC_AXIS_Z_POS', 'ASC')->orderBy('REC_AXIS_X_POS', 'ASC')->get();
                            if (count($tempRecordDatas)) {
                                foreach ($tempRecordDatas as $tempRecordData) {
                                    $item['X'] = $this->getAxisForPosition2($idStudy, $tempRecordData->REC_AXIS_X_POS, 2);
                                    $item['Y'] = $this->getAxisForPosition2($idStudy, $tempRecordData->REC_AXIS_Z_POS, 3);
                                    $item['Z'] = $this->unit->prodTemperature($tempRecordData->TEMP);
                                    $result[] = $item;
                                }
                            }
                          break;
                    }
                } else if ($selectedPlan == 1) {
                    $rMeshPosition = $this->getPositionForAxis2($idStudy, $tempRecordDataPlan[$selectedPlan][1], 2);

                    if (!empty($rMeshPosition)) {
                        $meshOrder = $rMeshPosition->MESH_ORDER;
                    } else {
                        $meshOrder = 0;
                    }

                    switch ($shape) {
                        case 1: 
                        case 4: 
                        case 5: 
                        case 6: 
                        case 7: 
                        case 8: 
                          break;

                        case 2: 
                        case 9: 
                            $tempRecordDatas = TempRecordData::select('REC_AXIS_X_POS', 'REC_AXIS_Y_POS', 'REC_AXIS_Z_POS', 'TEMP')->where("ID_REC_POS", $recordPosition->ID_REC_POS)->whereBetween('REC_AXIS_X_POS', [$tiRecPos['x'][0], $tiRecPos['x'][1]])->whereBetween('REC_AXIS_Z_POS', [$tiRecPos['z'][0], $tiRecPos['z'][1]])->where('REC_AXIS_Y_POS', $meshOrder)->orderBy('REC_AXIS_Z_POS', 'ASC')->orderBy('REC_AXIS_X_POS', 'ASC')->get();
                            if (count($tempRecordDatas)) {
                                foreach ($tempRecordDatas as $tempRecordData) {
                                    if ($orientation == 1) {
                                        $item['X'] = $this->getAxisForPosition2($idStudy, $tempRecordData->REC_AXIS_X_POS, 3);
                                        $item['Y'] = $this->getAxisForPosition2($idStudy, $tempRecordData->REC_AXIS_Z_POS, 1);
                                    } else {
                                        $item['X'] = $this->getAxisForPosition2($idStudy, $tempRecordData->REC_AXIS_X_POS, 1);
                                        $item['Y'] = $this->getAxisForPosition2($idStudy, $tempRecordData->REC_AXIS_Z_POS, 3);
                                    }
                                    $item['Z'] = $this->unit->prodTemperature($tempRecordData->TEMP);
                                    $result[] = $item;
                                }
                            }
                            
                            break;
                        case 3: 
                            $tempRecordDatas = TempRecordData::select('REC_AXIS_X_POS', 'REC_AXIS_Y_POS', 'REC_AXIS_Z_POS', 'TEMP')->where("ID_REC_POS", $recordPosition->ID_REC_POS)->whereBetween('REC_AXIS_Y_POS', [$tiRecPos['y'][0], $tiRecPos['y'][1]])->whereBetween('REC_AXIS_Z_POS', [$tiRecPos['z'][0], $tiRecPos['z'][1]])->where('REC_AXIS_X_POS', $meshOrder)->orderBy('REC_AXIS_Y_POS', 'ASC')->orderBy('REC_AXIS_Z_POS', 'ASC')->get();
                            if (count($tempRecordDatas)) {
                                foreach ($tempRecordDatas as $tempRecordData) {
                                    $item['X'] = $this->getAxisForPosition2($idStudy, $tempRecordData->REC_AXIS_Z_POS, 3);
                                    $item['Y'] = $this->getAxisForPosition2($idStudy, $tempRecordData->REC_AXIS_Y_POS, 1);
                                    $item['Z'] = $this->unit->prodTemperature($tempRecordData->TEMP);
                                    $result[] = $item;
                                }
                            }
                          break;
                    }
                } else if ($selectedPlan == 2) {
                    $rMeshPosition = $this->getPositionForAxis2($idStudy, $tempRecordDataPlan[$selectedPlan][2], 3);

                    if (!empty($rMeshPosition)) {
                        $meshOrder = ($shape == 4 || $shape == 5) ?  0 : $rMeshPosition->MESH_ORDER;
                    } else {
                        $meshOrder = 0;
                    }

                    switch ($shape) {
                        case 1: 
                        case 6: 
                            break;

                        case 2: 
                        case 9:
                            if ($orientation == 1) {
                                $tempRecordDatas = TempRecordData::select('REC_AXIS_X_POS', 'REC_AXIS_Y_POS', 'REC_AXIS_Z_POS', 'TEMP')->where("ID_REC_POS", $recordPosition->ID_REC_POS)->whereBetween('REC_AXIS_Y_POS', [$tiRecPos['y'][0], $tiRecPos['y'][1]])->whereBetween('REC_AXIS_Z_POS', [$tiRecPos['z'][0], $tiRecPos['z'][1]])->where('REC_AXIS_X_POS', $meshOrder)->orderBy('REC_AXIS_Y_POS', 'ASC')->orderBy('REC_AXIS_Z_POS', 'ASC')->get();
                                if (count($tempRecordDatas)) {
                                    foreach ($tempRecordDatas as $tempRecordData) {
                                        $item['X'] = $this->getAxisForPosition2($idStudy, $tempRecordData->REC_AXIS_Z_POS, 1);
                                        $item['Y'] = $this->getAxisForPosition2($idStudy, $tempRecordData->REC_AXIS_Y_POS, 2);
                                        $item['Z'] = $this->unit->prodTemperature($tempRecordData->TEMP);
                                        $result[] = $item;
                                    }
                                }
                            } else {
                                $tempRecordDatas = TempRecordData::select('REC_AXIS_X_POS', 'REC_AXIS_Y_POS', 'REC_AXIS_Z_POS', 'TEMP')->where("ID_REC_POS", $recordPosition->ID_REC_POS)->whereBetween('REC_AXIS_X_POS', [$tiRecPos['x'][0], $tiRecPos['x'][1]])->whereBetween('REC_AXIS_Y_POS', [$tiRecPos['y'][0], $tiRecPos['y'][1]])->where('REC_AXIS_Z_POS', 0)->orderBy('REC_AXIS_Y_POS', 'ASC')->orderBy('REC_AXIS_X_POS', 'ASC')->get();
                                if (count($tempRecordDatas)) {
                                    foreach ($tempRecordDatas as $tempRecordData) {
                                        $item['X'] = $this->getAxisForPosition2($idStudy, $tempRecordData->REC_AXIS_X_POS, 1);
                                        $item['Y'] = $this->getAxisForPosition2($idStudy, $tempRecordData->REC_AXIS_Y_POS, 2);
                                        $item['Z'] = $this->unit->prodTemperature($tempRecordData->TEMP);
                                        $result[] = $item;
                                    }
                                }
                            }
                            break; 

                        case 3:
                        case 5:
                        case 7:
                            $tempRecordDatas = TempRecordData::select('REC_AXIS_X_POS', 'REC_AXIS_Y_POS', 'REC_AXIS_Z_POS', 'TEMP')->where("ID_REC_POS", $recordPosition->ID_REC_POS)->whereBetween('REC_AXIS_X_POS', [$tiRecPos['x'][0], $tiRecPos['x'][1]])->whereBetween('REC_AXIS_Y_POS', [$tiRecPos['y'][0], $tiRecPos['y'][1]])->where('REC_AXIS_Z_POS', 0)->orderBy('REC_AXIS_Y_POS', 'ASC')->orderBy('REC_AXIS_X_POS', 'ASC')->get();
                            if (count($tempRecordDatas)) {
                                foreach ($tempRecordDatas as $tempRecordData) {
                                    $item['X'] = $this->getAxisForPosition2($idStudy, $tempRecordData->REC_AXIS_X_POS, 2);
                                    $item['Y'] = $this->getAxisForPosition2($idStudy, $tempRecordData->REC_AXIS_Y_POS, 1);
                                    $item['Z'] = $this->unit->prodTemperature($tempRecordData->TEMP);
                                    $result[] = $item;
                                }
                            }
                            break;

                        case 4:
                        case 8:
                            $tempRecordDatas = TempRecordData::select('REC_AXIS_X_POS', 'REC_AXIS_Y_POS', 'REC_AXIS_Z_POS', 'TEMP')->where("ID_REC_POS", $recordPosition->ID_REC_POS)->whereBetween('REC_AXIS_X_POS', [$tiRecPos['x'][0], $tiRecPos['x'][1]])->whereBetween('REC_AXIS_Y_POS', [$tiRecPos['y'][0], $tiRecPos['y'][1]])->where('REC_AXIS_Z_POS', 0)->orderBy('REC_AXIS_Y_POS', 'ASC')->orderBy('REC_AXIS_X_POS', 'ASC')->get();
                            if (count($tempRecordDatas)) {
                                foreach ($tempRecordDatas as $tempRecordData) {
                                    $item['X'] = $this->getAxisForPosition2($idStudy, $tempRecordData->REC_AXIS_X_POS, 1);
                                    $item['Y'] = $this->getAxisForPosition2($idStudy, $tempRecordData->REC_AXIS_Y_POS, 2);
                                    $item['Z'] = $this->unit->prodTemperature($tempRecordData->TEMP);
                                    $result[] = $item;
                                }
                            }
                            break;
                    }
                }
            }
            
        }

        $sort = array();
        if (!empty($result)) {
            foreach ($result as $key => $row)
            {
                $sort[$key] = $row['X'];
            }
            array_multisort($sort, SORT_ASC, $result);
        }
        

        return $result;
    }

    public function getListRecordDataByPlan($idStudy, $idStudyEquipment, $time, $tempRecordDataPlan, $selectedPlan, $shape)
    {
        $recordPosition = RecordPosition::where("ID_STUDY_EQUIPMENTS", $idStudyEquipment)->where("RECORD_TIME", $time)->first();

        $result = '';
        if (!empty($recordPosition)) {
            if ($selectedPlan == 0) {
                $rMeshPosition = $this->getPositionForAxis2($idStudy, $tempRecordDataPlan[$selectedPlan][0], 1);

                switch ($shape) {
                    case 1: 
                    case 4: 
                    case 5: 
                    case 6: 
                    case 7: 
                    case 8: 
                      break;

                    case 2: 
                    case 9: 
                        $result = TempRecordData::where("ID_REC_POS", $recordPosition->ID_REC_POS)->where('REC_AXIS_X_POS', $recordPosition->MESH_ORDER)->orderBy('REC_AXIS_Z_POS', 'ASC')->orderBy('REC_AXIS_Y_POS', 'ASC')->get();
                        break;
                    case 3: 
                        $result = TempRecordData::where("ID_REC_POS", $recordPosition->ID_REC_POS)->where('REC_AXIS_Y_POS', $recordPosition->MESH_ORDER)->orderBy('REC_AXIS_Z_POS', 'ASC')->orderBy('REC_AXIS_Y_POS', 'ASC')->get();
                      break;
                }
            } else if ($selectedPlan == 1) {
                $rMeshPosition = $this->getPositionForAxis2($idStudy, $empRecordDataPlan[$selectedPlan][1], 2);
                if (!empty($rMeshPosition)) {
                    switch ($shape) {
                        case 1: 
                        case 4: 
                        case 5: 
                        case 6: 
                        case 7: 
                        case 8: 
                          break;

                        case 2: 
                        case 9: 
                            $result = TempRecordData::where("ID_REC_POS", $recordPosition->ID_REC_POS)->where('REC_AXIS_Y_POS', $recordPosition->MESH_ORDER)->orderBy('REC_AXIS_Z_POS', 'ASC')->orderBy('REC_AXIS_X_POS', 'ASC')->get();
                            break;
                        case 3: 
                            $result = TempRecordData::where("ID_REC_POS", $recordPosition->ID_REC_POS)->where('REC_AXIS_X_POS', $recordPosition->MESH_ORDER)->orderBy('REC_AXIS_Z_POS', 'ASC')->orderBy('REC_AXIS_X_POS', 'ASC')->get();
                          break;
                    }
                } else {
                    $result = TempRecordData::where("ID_REC_POS", $recordPosition->ID_REC_POS)->where('REC_AXIS_Y_POS', 0)->orderBy('REC_AXIS_Z_POS', 'ASC')->orderBy('REC_AXIS_X_POS', 'ASC')->get();
                }
            } else if ($selectedPlan == 2) {
                $rMeshPosition = $this->getPositionForAxis2($idStudy, $empRecordDataPlan[$selectedPlan][2], 3);
                if (!empty($rMeshPosition)) {
                    switch ($shape) {
                        case 1: 
                        case 4: 
                        case 5: 
                        case 6: 
                        case 7: 
                        case 8: 
                          break;

                        case 2: 
                        case 9: 
                            $result = TempRecordData::where("ID_REC_POS", $recordPosition->ID_REC_POS)->where('REC_AXIS_Z_POS', 0)->orderBy('REC_AXIS_Y_POS', 'ASC')->orderBy('REC_AXIS_X_POS', 'ASC')->get();
                            break;
                        case 3: 
                            $result = TempRecordData::where("ID_REC_POS", $recordPosition->ID_REC_POS)->where('REC_AXIS_Z_POS', 0)->orderBy('REC_AXIS_Y_POS', 'ASC')->orderBy('REC_AXIS_X_POS', 'ASC')->get();
                          break;
                    }
                } else {
                    $result = TempRecordData::where("ID_REC_POS", $recordPosition->ID_REC_POS)->where('REC_AXIS_Y_POS', 0)->orderBy('REC_AXIS_Y_POS', 'ASC')->orderBy('REC_AXIS_X_POS', 'ASC')->get();
                }
            }
        }

        return $recordPosition;
    }

    public function getRecAxisPos($idRec_Pos, $lfTmin, $lfTMax)
    {
        // $tempRecordData = TempRecordData::where('ID_REC_POS', $idRec_Pos)->whereBetween('TEMP', [$lfTmin, $lfTMax])->get();
        $tempRecordData = DB::table('TEMP_RECORD_DATA')->whereRaw('ID_REC_POS = '. $idRec_Pos .' AND CAST(TEMP AS DECIMAL) >= '. $lfTmin .' AND CAST(TEMP AS DECIMAL) <= '. $lfTMax .'')->orderBy('ID_TEMP_RECORD_DATA')->get();

        $result = [];
        if (count($tempRecordData) > 0) {
            $result = [
                'x' => [$tempRecordData[0]->REC_AXIS_X_POS, $tempRecordData[count($tempRecordData) - 1]->REC_AXIS_X_POS],
                'y' => [$tempRecordData[0]->REC_AXIS_Y_POS, $tempRecordData[count($tempRecordData) - 1]->REC_AXIS_Y_POS],
                'z' => [$tempRecordData[0]->REC_AXIS_Z_POS, $tempRecordData[count($tempRecordData) - 1]->REC_AXIS_Z_POS],
            ];
        }

        return $result;
    }

    public function getRightPosition($idStudy, $idStudyEquipment)
    {
        $productElmt = ProductElmt::where('ID_STUDY', $idStudy)->first();
        $shape = $productElmt->SHAPECODE;
        $layoutGen = LayoutGeneration::where('ID_STUDY_EQUIPMENTS', $idStudyEquipment)->first();
        $orientation = $layoutGen->PROD_POSITION;

        $tempRecordPts = TempRecordPts::where('ID_STUDY', $idStudy)->first();
        // $tempRecordPts = TempRecordPts::select('CAST(AXIS2_PT_TOP_SURF AS DECIMAL)')->where('ID_STUDY', $idStudy)->first();
        $meshPosTop = $this->getPositionForAxis2($idStudy, $tempRecordPts->AXIS2_PT_TOP_SURF, 2);
        $meshPosInt = $this->getPositionForAxis2($idStudy, $tempRecordPts->AXIS2_PT_INT_PT, 2);
        $meshPosBot = $this->getPositionForAxis2($idStudy, $tempRecordPts->AXIS2_PT_BOT_SURF, 2);

        $axisValue = [];

        switch ($shape) {
            case 1:
                if ($orientation == 1) {
                    $axisValue['axis1BotPos'] = $this->getPositionForSelectedPoint($tempRecordPts->AXIS3_PT_BOT_SURF, $meshPosBot->ID_PRODUCT_ELMT, 3);
                    $axisValue['axis1IntPos'] = $this->getPositionForSelectedPoint($tempRecordPts->AXIS3_PT_INT_PT, $meshPosInt->ID_PRODUCT_ELMT, 3);
                    $axisValue['axis1TopPos'] = $this->getPositionForSelectedPoint($tempRecordPts->AXIS3_PT_TOP_SURF, $meshPosTop->ID_PRODUCT_ELMT, 3);

                    $axisValue['axis2BotPos'] = $meshPosBot->MESH_ORDER;
                    $axisValue['axis2IntPos'] = $meshPosInt->MESH_ORDER;
                    $axisValue['axis2TopPos'] = $meshPosTop->MESH_ORDER;

                    $axisValue['axis3BotPos'] = $this->getPositionForSelectedPoint($tempRecordPts->AXIS1_PT_BOT_SURF, $meshPosBot->ID_PRODUCT_ELMT, 1);
                    $axisValue['axis3IntPos'] = $this->getPositionForSelectedPoint($tempRecordPts->AXIS1_PT_INT_PT, $meshPosInt->ID_PRODUCT_ELMT, 1);
                    $axisValue['axis3TopSurfPos'] = $this->getPositionForSelectedPoint($tempRecordPts->AXIS1_PT_TOP_SURF, $meshPosTop->ID_PRODUCT_ELMT, 1);
                } else {
                    $axisValue['axis1BotPos'] = $axisValue['axis1IntPos'] = $axisValue['axis1TopPos'] = $axisValue['axis2BotPos'] = $axisValue['axis2IntPos']  = $axisValue['axis2TopPos'] = $axisValue['axis3BotPos'] = $axisValue['axis3IntPos'] = $axisValue['axis3TopSurfPos'] = 0;
                }
                break;

            case 2:
            case 9:
                $axisValue['axis2BotPos'] = $meshPosBot->MESH_ORDER;
                $axisValue['axis2IntPos'] = $meshPosInt->MESH_ORDER;
                $axisValue['axis2TopPos'] = $meshPosTop->MESH_ORDER;

                if ($orientation == 1) {
                    $axisValue['axis1BotPos'] = $this->getPositionForSelectedPoint($tempRecordPts->AXIS3_PT_BOT_SURF, $meshPosBot->ID_PRODUCT_ELMT, 3);
                    $axisValue['axis1IntPos'] = $this->getPositionForSelectedPoint($tempRecordPts->AXIS3_PT_INT_PT, $meshPosInt->ID_PRODUCT_ELMT, 3);
                    $axisValue['axis1TopPos'] = $this->getPositionForSelectedPoint($tempRecordPts->AXIS3_PT_TOP_SURF, $meshPosTop->ID_PRODUCT_ELMT, 3);

                    $axisValue['axis3BotPos'] = $this->getPositionForSelectedPoint($tempRecordPts->AXIS1_PT_BOT_SURF, $meshPosBot->ID_PRODUCT_ELMT, 1);
                    $axisValue['axis3IntPos'] = $this->getPositionForSelectedPoint($tempRecordPts->AXIS1_PT_INT_PT, $meshPosInt->ID_PRODUCT_ELMT, 1);
                    $axisValue['axis3TopSurfPos'] = $this->getPositionForSelectedPoint($tempRecordPts->AXIS1_PT_TOP_SURF, $meshPosTop->ID_PRODUCT_ELMT, 1);
                } else {
                    $axisValue['axis1BotPos'] = $this->getPositionForSelectedPoint($tempRecordPts->AXIS1_PT_BOT_SURF, $meshPosBot->ID_PRODUCT_ELMT, 1);
                    $axisValue['axis1IntPos'] = $this->getPositionForSelectedPoint($tempRecordPts->AXIS1_PT_INT_PT, $meshPosInt->ID_PRODUCT_ELMT, 1);
                    $axisValue['axis1TopPos'] = $this->getPositionForSelectedPoint($tempRecordPts->AXIS1_PT_TOP_SURF, $meshPosTop->ID_PRODUCT_ELMT, 1);

                    $axisValue['axis3BotPos'] = $this->getPositionForSelectedPoint($tempRecordPts->AXIS3_PT_BOT_SURF, $meshPosBot->ID_PRODUCT_ELMT, 3);
                    $axisValue['axis3IntPos'] = $this->getPositionForSelectedPoint($tempRecordPts->AXIS3_PT_INT_PT, $meshPosInt->ID_PRODUCT_ELMT, 3);
                    $axisValue['axis3TopSurfPos'] = $this->getPositionForSelectedPoint($tempRecordPts->AXIS3_PT_TOP_SURF, $meshPosTop->ID_PRODUCT_ELMT, 3);
                }

                break;

            case 3:
                $axisValue['axis1BotPos'] = $meshPosBot->MESH_ORDER;
                $axisValue['axis1IntPos'] = $meshPosInt->MESH_ORDER;
                $axisValue['axis1TopPos'] = $meshPosTop->MESH_ORDER;

                $axisValue['axis2BotPos'] = $this->getPositionForSelectedPoint($tempRecordPts->AXIS1_PT_BOT_SURF, $meshPosBot->ID_PRODUCT_ELMT, 1);
                $axisValue['axis2IntPos'] = $this->getPositionForSelectedPoint($tempRecordPts->AXIS1_PT_INT_PT, $meshPosInt->ID_PRODUCT_ELMT, 1);
                $axisValue['axis2TopPos'] = $this->getPositionForSelectedPoint($tempRecordPts->AXIS1_PT_TOP_SURF, $meshPosTop->ID_PRODUCT_ELMT, 1);

                $axisValue['axis3BotPos'] = $this->getPositionForSelectedPoint($tempRecordPts->AXIS3_PT_BOT_SURF, $meshPosBot->ID_PRODUCT_ELMT, 3);
                $axisValue['axis3IntPos'] = $this->getPositionForSelectedPoint($tempRecordPts->AXIS3_PT_INT_PT, $meshPosInt->ID_PRODUCT_ELMT, 3);
                $axisValue['axis3TopSurfPos'] = $this->getPositionForSelectedPoint($tempRecordPts->AXIS3_PT_TOP_SURF, $meshPosTop->ID_PRODUCT_ELMT, 3);

                break;

            case 4:
            case 8:
                $axisValue['axis1BotPos'] = $this->getPositionForSelectedPoint($tempRecordPts->AXIS1_PT_BOT_SURF, $meshPosBot->ID_PRODUCT_ELMT, 1);
                $axisValue['axis1IntPos'] = $this->getPositionForSelectedPoint($tempRecordPts->AXIS1_PT_INT_PT, $meshPosInt->ID_PRODUCT_ELMT, 1);
                $axisValue['axis1TopPos'] = $this->getPositionForSelectedPoint($tempRecordPts->AXIS1_PT_TOP_SURF, $meshPosTop->ID_PRODUCT_ELMT, 1);

                $axisValue['axis2BotPos'] = $meshPosBot->MESH_ORDER;
                $axisValue['axis2IntPos'] = $meshPosInt->MESH_ORDER;
                $axisValue['axis2TopPos'] = $meshPosTop->MESH_ORDER;

                $axisValue['axis3BotPos'] = $this->getPositionForSelectedPoint($tempRecordPts->AXIS3_PT_BOT_SURF, $meshPosBot->ID_PRODUCT_ELMT, 3);
                $axisValue['axis3IntPos'] = $this->getPositionForSelectedPoint($tempRecordPts->AXIS3_PT_INT_PT, $meshPosInt->ID_PRODUCT_ELMT, 3);
                $axisValue['axis3TopSurfPos'] = $this->getPositionForSelectedPoint($tempRecordPts->AXIS3_PT_TOP_SURF, $meshPosTop->ID_PRODUCT_ELMT, 3);

                break;

            case 5:
            case 7:
                $axisValue['axis1BotPos'] = $meshPosBot->MESH_ORDER;
                $axisValue['axis1IntPos'] = $meshPosInt->MESH_ORDER;
                $axisValue['axis1TopPos'] = $meshPosTop->MESH_ORDER;

                $axisValue['axis2BotPos'] = $this->getPositionForSelectedPoint($tempRecordPts->AXIS1_PT_BOT_SURF, $meshPosBot->ID_PRODUCT_ELMT, 1);
                $axisValue['axis2IntPos'] = $this->getPositionForSelectedPoint($tempRecordPts->AXIS1_PT_INT_PT, $meshPosInt->ID_PRODUCT_ELMT, 1);
                $axisValue['axis2TopPos'] = $this->getPositionForSelectedPoint($tempRecordPts->AXIS1_PT_TOP_SURF, $meshPosTop->ID_PRODUCT_ELMT, 1);

                $axisValue['axis3BotPos'] = $this->getPositionForSelectedPoint($tempRecordPts->AXIS3_PT_BOT_SURF, $meshPosBot->ID_PRODUCT_ELMT, 3);
                $axisValue['axis3IntPos'] = $this->getPositionForSelectedPoint($tempRecordPts->AXIS3_PT_INT_PT, $meshPosInt->ID_PRODUCT_ELMT, 3);
                $axisValue['axis3TopSurfPos'] = $this->getPositionForSelectedPoint($tempRecordPts->AXIS3_PT_TOP_SURF, $meshPosTop->ID_PRODUCT_ELMT, 3);

                break;

            case 6:
                $axisValue['axis2BotPos'] = $meshPosBot->MESH_ORDER;
                $axisValue['axis2IntPos'] = $meshPosInt->MESH_ORDER;
                $axisValue['axis2TopPos'] = $meshPosTop->MESH_ORDER;

                $axisValue['axis1BotPos'] = $this->getPositionForSelectedPoint($tempRecordPts->AXIS3_PT_BOT_SURF, $meshPosBot->ID_PRODUCT_ELMT, 3);
                $axisValue['axis1IntPos'] = $this->getPositionForSelectedPoint($tempRecordPts->AXIS3_PT_INT_PT, $meshPosInt->ID_PRODUCT_ELMT, 3);
                $axisValue['axis1TopPos'] = $this->getPositionForSelectedPoint($tempRecordPts->AXIS3_PT_TOP_SURF, $meshPosTop->ID_PRODUCT_ELMT, 3);

                $axisValue['axis3BotPos'] = $this->getPositionForSelectedPoint($tempRecordPts->AXIS1_PT_BOT_SURF, $meshPosBot->ID_PRODUCT_ELMT, 1);
                $axisValue['axis3IntPos'] = $this->getPositionForSelectedPoint($tempRecordPts->AXIS1_PT_INT_PT, $meshPosInt->ID_PRODUCT_ELMT, 1);
                $axisValue['axis3TopSurfPos'] = $this->getPositionForSelectedPoint($tempRecordPts->AXIS1_PT_TOP_SURF, $meshPosTop->ID_PRODUCT_ELMT, 1);

                break;
            
            default:
                $axisValue['axis1BotPos'] = $axisValue['axis1IntPos'] = $axisValue['axis1TopPos'] = $axisValue['axis2BotPos'] = $axisValue['axis2IntPos']  = $axisValue['axis2TopPos'] = $axisValue['axis3BotPos'] = $axisValue['axis3IntPos'] = $axisValue['axis3TopSurfPos'] = 0;
                break;
        }

        return $axisValue;
    }

    public function getPositionForSelectedPoint($selectedPoint, $idProdElt, $axis)
    {
        // $meshPosition = MeshPosition::where('ID_PRODUCT_ELMT', $idProdElt)->where('MESH_AXIS', $axis)->where('MESH_AXIS_POS', $selectedPoint)->first();
        if ($this->is_decimal($selectedPoint)) {
            $decimal = explode('.', $selectedPoint);
            $length = (strlen($decimal[1]) > 9) ? 9 : strlen($decimal[1]);
            $selectedPointValue = (strlen($decimal[1]) > 9) ? 'CAST('. $selectedPoint .' AS DECIMAL(10,9))' : $selectedPoint;
        } else {
            $length = 9;
            $selectedPointValue = $selectedPoint;
        }

        $meshPosition = DB::table('MESH_POSITION')->whereRaw('ID_PRODUCT_ELMT = '. $idProdElt .' AND MESH_AXIS = '. $axis .' AND CAST(MESH_AXIS_POS AS DECIMAL(10,'. $length .')) = '. $selectedPointValue .'')->first();
        return ($meshPosition) ? $meshPosition->MESH_ORDER : 0;
    }

    public function saveTR_TS_VC(StudyEquipment &$studyEquipment, $dtr, $dts, $dvc, $dhs, $dtext)
    {
        if ($this->equip->getCapability($studyEquipment->CAPABILITIES, 1) && !empty($dtr)) {
            $this->stdeqp->cleanSpecificEqpPrm($studyEquipment->ID_STUDY_EQUIPMENTS, 300);
            $i = 0;
            foreach ($dtr as $tr) {
                $studEqpPrm = new StudEqpPrm();
                $studEqpPrm->ID_STUDY_EQUIPMENTS = $studyEquipment->ID_STUDY_EQUIPMENTS;
                $studEqpPrm->VALUE_TYPE = 300 + $i;
                $studEqpPrm->VALUE = doubleval($this->unit->controlTemperature($tr, ['save' => true]));
                $studEqpPrm->save();
                $i++;
            }
        }

        if(!empty($dts)) {
            $this->stdeqp->cleanSpecificEqpPrm($studyEquipment->ID_STUDY_EQUIPMENTS, 200);
            $i = 0;
            foreach ($dts as $ts) {
                $studEqpPrm = new StudEqpPrm();
                $studEqpPrm->ID_STUDY_EQUIPMENTS = $studyEquipment->ID_STUDY_EQUIPMENTS;
                $studEqpPrm->VALUE_TYPE = 200 + $i;
                $studEqpPrm->VALUE = doubleval($this->unit->time($ts, ['save' => true]));
                $studEqpPrm->save();
                $i++;
            }
        }

        if ($this->equip->getCapability($studyEquipment->CAPABILITIES, 4) && !empty($dvc)) {
            $this->stdeqp->cleanSpecificEqpPrm($studyEquipment->ID_STUDY_EQUIPMENTS, 100);
            $i = 0;
            foreach ($dvc as $vc) {
                $studEqpPrm = new StudEqpPrm();
                $studEqpPrm->ID_STUDY_EQUIPMENTS = $studyEquipment->ID_STUDY_EQUIPMENTS;
                $studEqpPrm->VALUE_TYPE = 100 + $i;
                $studEqpPrm->VALUE = doubleval($this->unit->convectionSpeed($vc, ['save' => true]));
                $studEqpPrm->save();
                $i++;
            }
        }

        if(!empty($dhs)) {
            $this->stdeqp->cleanSpecificEqpPrm($studyEquipment->ID_STUDY_EQUIPMENTS, 400);
            $i = 0;
            foreach ($dhs as $dh) {
                $studEqpPrm = new StudEqpPrm();
                $studEqpPrm->ID_STUDY_EQUIPMENTS = $studyEquipment->ID_STUDY_EQUIPMENTS;
                $studEqpPrm->VALUE_TYPE = 400 + $i;
                $studEqpPrm->VALUE = doubleval($dh);
                $studEqpPrm->save();
                $i++;
            }
        }

        if ($this->equip->getCapability($studyEquipment->CAPABILITIES, 512) && !empty($dtext)) {
            $this->stdeqp->cleanSpecificEqpPrm($studyEquipment->ID_STUDY_EQUIPMENTS, 500);
            $studEqpPrm = new StudEqpPrm();
            $studEqpPrm->ID_STUDY_EQUIPMENTS = $studyEquipment->ID_STUDY_EQUIPMENTS;
            $studEqpPrm->VALUE_TYPE = 500;
            $studEqpPrm->VALUE = doubleval($this->unit->exhaustTemperature($studyEquipment->tExt, ['save' => true]));
            $studEqpPrm->save();
        }
    }

    public function applyStudyCleaner($input, StudyEquipment &$studyEquipment)
    {
        $idStudy = $studyEquipment->ID_STUDY;
        $idStudyEquipment = $studyEquipment->ID_STUDY_EQUIPMENTS;
        $bisApplied = false;

        $listTr = $this->brain->getListTr($idStudyEquipment);
        $listTs = $this->brain->getListTs($idStudyEquipment);
        $listVc = $this->brain->getVc($idStudyEquipment);
        $listTe = $this->brain->getTExt($idStudyEquipment);
        
        if (!$bisApplied) {
            for ($i = 0; $i < count($listTr); $i++) { 
                $oldTr = $this->unit->controlTemperature($listTr[$i]);
                $newTr = $input['TR'][$i];
                if ($oldTr != $newTr) {
                    $bisApplied = true;
                }
            }
        }
        
        if (!$bisApplied) {
            for ($i = 0; $i < count($listTs); $i++) { 
                $oldTs = $this->unit->time($listTs[$i]);
                $newTs = $input['TS'][$i];
                if ($oldTs != $newTs) {
                    $bisApplied = true;
                }
            }
        }

        if (!$bisApplied) {
            for ($i = 0; $i < count($listVc); $i++) { 
                $oldVc = $this->unit->convectionSpeed($listVc[$i]);
                $newVc = $input['VC'][$i];
                if ($oldVc != $newVc) {
                    $bisApplied = true;
                }
            }
        }

        if (!$bisApplied) {
            if ($this->unit->exhaustTemperature($listTe) != $input['TE']) {
                $bisApplied = true;
            }
        }

        if ($bisApplied) {
            if ($this->stdeqp->runStudyCleaner($idStudy, $idStudyEquipment, SC_CLEAN_OUTPUT_EQP_PRM) != 0) {
                $bisApplied = false;
            }

            $this->stdeqp->afterStudyCleaner($idStudy, $idStudyEquipment, SC_CLEAN_OUTPUT_EQP_PRM);
        }

        return $bisApplied;
    }

    public function executeSequence(StudyEquipment &$studyEquipment)
    {
        $error = '';
        if ($this->stdeqp->startDimMat($studyEquipment) == 0) {
            if ($studyEquipment->study->OPTION_CRYOPIPELINE == 1) {
                if ($this->stdeqp->startPipe($studyEquipment) == 0) {
                    $error = "errorPIPE_Kernel";
                }
            }

            if ($studyEquipment->study->OPTION_ECO == 1) {
                if ($this->stdeqp->startEconomic($studyEquipment) == 0) {
                    $error = "errorECO_Kernel";
                }
            }

            if ($this->stdeqp->startConsumptionEconomic($studyEquipment) == 0) {
                $error = "errorECO_Kernel";
            }
        } else {
            $error = "errorDimMat_Kernel";
        }

        if ($error == '' && $this->cal->isStudyHasChilds($studyEquipment->study->ID_STUDY)) {
            $this->cal->setChildsStudiesToRecalculate($studyEquipment->study->ID_STUDY, $studyEquipment->ID_STUDY_EQUIPMENTS);
        }
    }

    public function base_path($path=null)
    {
        return rtrim(app()->basePath($path), '/');
    }

    public function public_path($path=null)
    {
        return rtrim(app()->basePath('public/'.$path), '/');
    }

    public function storage_path($path=null)
    {
        return app()->storagePath($path);
    }

    public function asset($path, $secure = null)
    {
        return app('url')->asset($path, $secure);
    }

    public function recurse_copy($src, $dst) {
        if (is_dir($src)) {
            $dir = opendir($src);
            @mkdir($dst);
            while(false !== ( $file = readdir($dir)) ) {
                if (( $file != '.' ) && ( $file != '..' )) {
                    if ( is_dir($src . '/' . $file) ) {
                        $this->recurse_copy($src . '/' . $file,$dst . '/' . $file);
                    }
                    else {
                        copy($src . '/' . $file,$dst . '/' . $file);
                    }
                }
            }
            closedir($dir);
        }
    }

    public function deleteDirectory($dir) {
        if (!file_exists($dir)) {
            return true;
        }

        if (!is_dir($dir)) {
            return unlink($dir);
        }

        foreach (scandir($dir) as $item) {
            if ($item == '.' || $item == '..') {
                continue;
            }

            if (!$this->deleteDirectory($dir . DIRECTORY_SEPARATOR . $item)) {
                return false;
            }

        }

        return rmdir($dir);
    }

    public function mixRange($color1, $color2, $MIN = 1, $MAX = 10)
    {
        $range = rand($MIN, $MAX);
     
        $r = hexdec(substr($color1,0,2));
        $g = hexdec(substr($color1,2,2));
        $b = hexdec(substr($color1,4,2));
         
        $gr = (hexdec(substr($color2,0,2))-$r)/$MAX; //Graduation Size Red
        $gg = (hexdec(substr($color2,2,2))-$g)/$MAX;
        $gb = (hexdec(substr($color2,4,2))-$b)/$MAX;
         
        return str_pad(dechex($r+($gr*$range)),2,'0',STR_PAD_LEFT) .
            str_pad(dechex($g+($gg*$range)),2,'0',STR_PAD_LEFT) .
            str_pad(dechex($b+($gb*$range)),2,'0',STR_PAD_LEFT);
    }
}
