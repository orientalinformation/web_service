<?php

namespace App\Cryosoft;

use App\Cryosoft\ValueListService;
use App\Cryosoft\UnitsConverterService;
use App\Models\TempRecordPts;
use App\Models\TempRecordData;
use App\Models\TempRecordPtsDef;
use App\Models\MeshPosition;

class OutputService
{
    public function __construct(\Laravel\Lumen\Application $app)
    {
        $this->app = $app;
        $this->auth = $app['Illuminate\\Contracts\\Auth\\Factory'];
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
                        $rMeshPositionY = MeshPosition::where('ID_STUDY', $idStudy)->where('MESH_AXIS', 2)->where('MESH_AXIS_POS', $axeTempRecordData[$selectedAxe][1])->first();
                        $rMeshPositionX = MeshPosition::where('ID_STUDY', $idStudy)->where('MESH_AXIS', 3)->where('MESH_AXIS_POS', $axeTempRecordData[$selectedAxe][2])->first();

                        $result = TempRecordData::where("ID_REC_POS", $idRecPos)->where('REC_AXIS_Y_POS', $rMeshPositionY->MESH_ORDER)->where('REC_AXIS_X_POS', $rMeshPositionX->REC_AXIS_X_POS)->orderBy('REC_AXIS_Z_POS', 'ASC')->get();
                    } else {
                        $rMeshPositionY = MeshPosition::where('ID_STUDY', $idStudy)->where('MESH_AXIS', 2)->where('MESH_AXIS_POS', $axeTempRecordData[$selectedAxe][1])->first();
                        $result = TempRecordData::where("ID_REC_POS", $idRecPos)->where('REC_AXIS_Y_POS', $rMeshPositionY->MESH_ORDER)->where('REC_AXIS_Z_POS', 0)->orderBy('REC_AXIS_X_POS', 'ASC')->get();
                    }
                    break;

                case 3:
                case 5:
                case 7:
                    $rMeshPosition = MeshPosition::where('ID_STUDY', $idStudy)->where('MESH_AXIS', 2)->where('MESH_AXIS_POS', $axeTempRecordData[$selectedAxe][1])->first();

                    $result = TempRecordData::where("ID_REC_POS", $idRecPos)->where('REC_AXIS_X_POS', $rMeshPosition->MESH_ORDER)->where('REC_AXIS_Z_POS', 0)->orderBy('REC_AXIS_Y_POS', 'ASC')->get();
                    break;

                case 4:
                case 8: 
                    $rMeshPosition = MeshPosition::where('ID_STUDY', $idStudy)->where('MESH_AXIS', 2)->where('MESH_AXIS_POS', $axeTempRecordData[$selectedAxe][1])->first();

                    $result = TempRecordData::where("ID_REC_POS", $idRecPos)->where('REC_AXIS_Y_POS', $rMeshPosition->MESH_ORDER)->where('REC_AXIS_Z_POS', 0)->orderBy('REC_AXIS_X_POS', 'ASC')->get();
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
                    $rMeshPosition = MeshPosition::where('ID_STUDY', $idStudy)->where('MESH_AXIS', 3)->where('MESH_AXIS_POS', $axeTempRecordData[$selectedAxe][2])->first();

                    $result = TempRecordData::where("ID_REC_POS", $idRecPos)->where('REC_AXIS_X_POS', $rMeshPosition->MESH_ORDER)->where('REC_AXIS_Z_POS', 0)->orderBy('REC_AXIS_Y_POS', 'ASC')->get();
                    
                    break;

                case 3: 
                case 5:
                case 7:
                    $rMeshPosition = MeshPosition::where('ID_STUDY', $idStudy)->where('MESH_AXIS', 1)->where('MESH_AXIS_POS', $axeTempRecordData[$selectedAxe][0])->first();

                    $result = TempRecordData::where("ID_REC_POS", $idRecPos)->where('REC_AXIS_Y_POS', $rMeshPosition->MESH_ORDER)->where('REC_AXIS_Z_POS', 0)->orderBy('REC_AXIS_X_POS', 'ASC')->get();
                    break;

                case 4:
                case 8: 
                    $rMeshPosition = MeshPosition::where('ID_STUDY', $idStudy)->where('MESH_AXIS', 1)->where('MESH_AXIS_POS', $axeTempRecordData[$selectedAxe][0])->first();

                    $result = TempRecordData::where("ID_REC_POS", $idRecPos)->where('REC_AXIS_X_POS', $rMeshPosition->MESH_ORDER)->where('REC_AXIS_Z_POS', 0)->orderBy('REC_AXIS_Y_POS', 'ASC')->get();
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
                    $rMeshPosition = MeshPosition::where('ID_STUDY', $idStudy)->where('MESH_AXIS', 1)->where('MESH_AXIS_POS', $axeTempRecordData[$selectedAxe][0])->first();

                    $result = TempRecordData::where("ID_REC_POS", $idRecPos)->where('REC_AXIS_Y_POS', $rMeshPosition->MESH_ORDER)->where('REC_AXIS_Z_POS', 0)->orderBy('REC_AXIS_X_POS', 'ASC')->get();
                    
                    break;

                case 3: 
                    $rMeshPositionY = MeshPosition::where('ID_STUDY', $idStudy)->where('MESH_AXIS', 1)->where('MESH_AXIS_POS', $axeTempRecordData[$selectedAxe][0])->first();

                    $rMeshPositionX = MeshPosition::where('ID_STUDY', $idStudy)->where('MESH_AXIS', 2)->where('MESH_AXIS_POS', $axeTempRecordData[$selectedAxe][1])->first();

                    $result = TempRecordData::where("ID_REC_POS", $idRecPos)->where('REC_AXIS_Y_POS', $rMeshPositionY->MESH_ORDER)->where('REC_AXIS_X_POS', $rMeshPositionX->MESH_ORDER)->orderBy('REC_AXIS_Z_POS', 'ASC')->get();
                    break;
            }
        }

        return $result;
    }

    public function getAxisForPosition2($idStudy, $recAxis, $selectedAxe)
    {
        $result = "";
        $rMeshPosition = MeshPosition::where('ID_STUDY', $idStudy)->where('MESH_AXIS', $selectedAxe)->where('MESH_ORDER', $recAxis)->first();
        if (!empty($rMeshPosition)) $result = $this->unit->prodchartDimension($rMeshPosition->MESH_AXIS_POS);

        return $result;
    }
}
