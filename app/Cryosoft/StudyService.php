<?php

namespace App\Cryosoft;

use Illuminate\Contracts\Auth\Factory as Auth;
use App\Cryosoft\ValueListService;
use App\Cryosoft\UnitsConverterService;
use App\Cryosoft\BrainCalculateService;
use App\Cryosoft\StudyEquipmentService;

use App\Models\Study;
use App\Models\Price;
use App\Models\MinMax;
use App\Models\StudyEquipment;
use App\Models\Equipment;
use App\Models\StudEqpPrm;

class StudyService
{
    /**
    * @var Illuminate\Contracts\Auth\Factory
    */
    protected $auth;

    public static $MODE_ESTIMATION = 1;
    public static $MODE_SELECTED = 2;
    public static $MODE_OPTIMUM = 3;
    
    // study options
    public static $OPTION_NOT_ECONOMIC = 0;
    public static $OPTION_ECONOMIC = 1;
    
    public static $OPTION_NOT_PIPELINE = 0;
    public static $OPTION_PIPELINE = 1;
    
    public static $OPTION_NOT_CHAINING = 0;
    public static $OPTION_CHAINING = 1;
    
    public static $OPTION_NOT_CHAIN_ADDCOMP = 0;
    public static $OPTION_CHAIN_ADDCOMP = 1;
    
    public static $OPTION_NOT_NODE_DECIM = 0;
    public static $OPTION_NODE_DECIM = 1;
    

    public function __construct(\Laravel\Lumen\Application $app)
    {
        $this->app = $app;
        $this->auth = $app['Illuminate\\Contracts\\Auth\\Factory'];
        $this->value = $app['App\\Cryosoft\\ValueListService'];
        $this->kernel = $app['App\\Kernel\\KernelService'];
        $this->calculator = $app['App\\Cryosoft\\CalculateService'];
        $this->brain = $app['App\\Cryosoft\\BrainCalculateService'];
        $this->sequip = $app['App\\Cryosoft\\StudyEquipmentService'];
    }

    public function isMyStudy($idStudy) 
    {
        $bmine = false;
        $study = Study::find($idStudy);
        if (!empty($study)) {
            $userID = -1;
            $studyOwnerID = -2;

            $user = $this->auth->user();
            if (!empty($user)) {
                $userID = $user->ID_USER;
            }

            $studyOwnerID = $study->ID_USER;

            if ($userID == $studyOwnerID) {
                $bmine = true;
                $isMyStudy = true;
            } else {
                $isMyStudy = false;
            }
        }

        return $bmine;
    } 

    public function disableFields($idStudy)
    {
        $disabled = "";
        $user = $this->auth->user();
        $userID = $user->ID_USER;

        $study = Study::find($idStudy);

        $studyOwnerID = $study->ID_USER;
        $userProfileID = $user->USER_PRIO;

        if (($userProfileID > $this->value->PROFIL_EXPERT) || ($studyOwnerID != $userID)) {
            $sdisabled = "disabled";
        }
        return $disabled;
    }

    public function getStudyPrice($study) 
    {
        if ($study->OPTION_ECO != 0) {

            if ($study->ID_PRICE == 0) {
                $price = new Price();
                $price->ID_STUDY = $study->ID_STUDY;
                $price->ENERGY = 0;
                $price->ECO_IN_CRYO1 = 0;
                $price->ECO_IN_PBP1 = 0;
                $price->ECO_IN_CRYO2 = 0;
                $price->ECO_IN_PBP2 = 0;
                $price->ECO_IN_CRYO3 = 0;
                $price->ECO_IN_PBP3 = 0;
                $price->ECO_IN_CRYO4 = 0;
                $price->ECO_IN_MINMP = 0;
                $price->ECO_IN_MAXMP = 0;
                $price->save();
                $study->ID_PRICE = $price->ID_PRICE;
                $study->update();

                return $price->ENERGY;
            } else {
                $price = Price::find($study->ID_PRICE);

                if ($price) 
                    return $price->ENERGY;
            }
        }
        
        return 0;
    }

    public function getFilteredStudiesList($idUser, $idCompFamily, $idCompSubFamily, $idComponent)
    {
        $querys = Study::distinct();

        if ($idCompFamily + $idCompSubFamily + $idComponent > 0) {
            $querys->join('product_elmt', 'studies.ID_PROD', '=', 'product_elmt.ID_PROD');

            if ($idComponent > 0) {
                $querys->where('product_elmt.ID_PROD', $idComponent);
            } else {
                $querys->join('component', 'product_elmt.ID_COMP', '=', 'component.ID_COMP');
                if ($idCompFamily > 0) {
                    $querys->where('component.CLASS_TYPE', $idCompFamily);
                }
                if ($idCompSubFamily > 0) {
                    $querys->where('component.SUB_FAMILY', $idCompSubFamily);
                }
            }
        }   
        if ($idUser > 0) {
            $querys->where('studies.ID_USER', $idUser);
        } else {
            $querys->where('studies.ID_USER', '!=', $this->auth->user()->ID_USER);  
        }

        $querys->orderBy('studies.STUDY_NAME');

        return $querys->get();
    }  

    public function convertAxisForDB($ldShape, $bIsParallel, $appAxe)
    {
        $dbAxe = [];

        switch ($ldShape) {
            case 1:
                $dbAxe[0] = $this->vector3f($this->convertPointForDB($ldShape, $bIsParallel, $this->vector3f($appAxe[0])));
                $dbAxe[1] = $this->vector3f($this->convertPointForDB($ldShape, $bIsParallel, $this->vector3f($appAxe[1])));
                $dbAxe[2] = $this->vector3f($this->convertPointForDB($ldShape, $bIsParallel, $this->vector3f($appAxe[2])));
                break;

            case 2:
            case 9:
                if ($bIsParallel) {
                    $dbAxe[0] = $this->vector3f($this->convertPointForDB($ldShape, $bIsParallel, $this->vector3f($appAxe[2])));
                    $dbAxe[1] = $this->vector3f($this->convertPointForDB($ldShape, $bIsParallel, $this->vector3f($appAxe[1])));
                    $dbAxe[2] = $this->vector3f($this->convertPointForDB($ldShape, $bIsParallel, $this->vector3f($appAxe[0])));
                } else {
                    $dbAxe[0] = $this->vector3f($this->convertPointForDB($ldShape, $bIsParallel, $this->vector3f($appAxe[0])));
                    $dbAxe[1] = $this->vector3f($this->convertPointForDB($ldShape, $bIsParallel, $this->vector3f($appAxe[1])));
                    $dbAxe[2] = $this->vector3f($this->convertPointForDB($ldShape, $bIsParallel, $this->vector3f($appAxe[2])));
                }
                break;

            case 3:
                if ($bIsParallel) {
                    $dbAxe[0] = $this->vector3f($this->convertPointForDB($ldShape, $bIsParallel, $this->vector3f($appAxe[1])));
                    $dbAxe[1] = $this->vector3f($this->convertPointForDB($ldShape, $bIsParallel, $this->vector3f($appAxe[0])));
                    $dbAxe[2] = $this->vector3f($this->convertPointForDB($ldShape, $bIsParallel, $this->vector3f($appAxe[2])));
                } else {
                    $dbAxe[0] = $this->vector3f($this->convertPointForDB($ldShape, $bIsParallel, $this->vector3f($appAxe[1])));
                    $dbAxe[1] = $this->vector3f($this->convertPointForDB($ldShape, $bIsParallel, $this->vector3f($appAxe[0])));
                    $dbAxe[2] = $this->vector3f($this->convertPointForDB($ldShape, $bIsParallel, $this->vector3f($appAxe[2])));
                }
                break;

            case 4:
            case 5:
                $dbAxe[0] = $this->vector3f($this->convertPointForDB($ldShape, $bIsParallel, $this->vector3f($appAxe[0])));
                $dbAxe[1] = $this->vector3f($this->convertPointForDB($ldShape, $bIsParallel, $this->vector3f($appAxe[1])));
                $dbAxe[2] = [];
                break;

            case 7:
            case 8:
                $dbAxe[0] = $this->vector3f($this->convertPointForDB($ldShape, $bIsParallel, $this->vector3f($appAxe[1])));
                $dbAxe[1] = $this->vector3f($this->convertPointForDB($ldShape, $bIsParallel, $this->vector3f($appAxe[0])));
                $dbAxe[2] = [];
                break;

            case 6:
                $dbAxe[0] = [];
                $dbAxe[1] = $this->vector3f($this->convertPointForDB($ldShape, $bIsParallel, $this->vector3f($appAxe[1])));
                $dbAxe[2] = [];
            
            default:
                $dbAxe = [];
                break;
        }

        return $dbAxe;
    }

    public function convertPointForDB($ldShape, $bIsParallel, $appDim) 
    {
        $dbDim = [];

        switch ($ldShape) {
            case 1:
                $dbDim = [$appDim['x'], $appDim['y'], $appDim['z']];
                break;

            case 2:
            case 9:
                if ($bIsParallel) {
                    $dbDim = [$appDim['z'], $appDim['y'], $appDim['x']];
                } else {
                    $dbDim = [$appDim['x'], $appDim['y'], $appDim['z']];
                }
                break;

            case 3:
                if ($bIsParallel) {
                    $dbDim = [$appDim['y'], $appDim['z'], $appDim['x']];
                } else {
                    $dbDim = [$appDim['y'], $appDim['x'], $appDim['z']];
                }
                break;

            case 4:
            case 5:
                $dbDim = [$appDim['x'], $appDim['y'], $appDim['z']];
                break;

            case 7:
            case 8:
                $dbDim = [$appDim['y'], $appDim['x'], $appDim['z']];
                break;

            case 6:
                $dbDim = [$appDim['x'], $appDim['y'], $appDim['z']];
                break;

            default:
                $dbDim = [$appDim['x'], $appDim['y'], $appDim['z']];
        }

        return $dbDim;
    }

    public function vector3f($array)
    {
        $data = [
            'x' => $array[0],
            'y' => $array[1],
            'z' => $array[2]
        ];

        return $data;
    }

    public function RunStudyCleaner($idStudy, $ld_Mode, $ld_StudEqpId = -1) 
    {
        $study = Study::find($idStudy);

        $ret = 0;
        $conf = $this->kernel->getConfig($this->auth->user()->ID_USER, $idStudy, $ld_StudEqpId, 1, 1, 'c:\\temp\\'.$study->STUDY_NAME.'\\StudyClear_'.$idStudy.'_'.$ld_StudEqpId.'_'.$ld_Mode.'.txt');
        $ret = $this->kernel->getKernelObject('StudyCleaner')->SCStudyClean($conf, $ld_Mode);
        // Chaining management to mark childs to recalculate
        if (($ret = 0) && $this->calculator->isStudyHasChilds($idStudy)) {
            $this->calculator->getCalculableStudyEquipments($idStudy, $ld_StudEqpId);
        }

        return $ret;
    }

    public function isStudyHasParent (\App\Models\Study $mySTD) 
    {
        $bret = false;
        //study with chaining?
        if (($mySTD->CHAINING_CONTROLS == self::$OPTION_CHAINING)
            && ($mySTD->PARENT_ID > 0)) {
            $bret = true;
        }
        return $bret;
    }

    public function updateStudyEquipmentAfterChangeTR($idStudyEquipment, $idEquipment)
    {
        $equip = Equipment::find($idEquipment);
        $sequip = StudyEquipment::find($idStudyEquipment);

        $tr = []; 
        $dh = [];

        $tr = $this->setEqpPrmInitialData(array_fill(0, $equip->NB_TR, 0.0), $equip->equipGenerations->first()->TEMP_SETPOINT);
        $dh = $this->getEqpPrmInitialData(array_fill(0, $equip->NB_TR, 0.0), 0, false);

        // set equipment data
        // clear first
        $trs = $this->brain->getListTr($idStudyEquipment);

        if ($trs != null) {
            $this->cleanSpecificEqpPrm($idStudyEquipment, 300);

            foreach ($tr as $trValue) {
                $p = new StudEqpPrm();
                $p->ID_STUDY_EQUIPMENTS = $idStudyEquipment;
                $p->VALUE_TYPE = REGULATION_TEMP;
                $p->VALUE = $trValue;
                $p->save();
            }
        }

        $dhs = $this->brain->getListDh($idStudyEquipment);
        if ($dhs != null) {
            $this->cleanSpecificEqpPrm($idStudyEquipment, 400);

            foreach ($dh as $dhValue) {
                $p = new StudEqpPrm();
                $p->ID_STUDY_EQUIPMENTS = $idStudyEquipment;
                $p->VALUE_TYPE = ENTHALPY_VAR;
                $p->VALUE = $dhValue;
                $p->save();
            }
        }

        // run kernel tool
        $err = $this->runKernelToolCalculator($sequip->ID_STUDY, $idStudyEquipment);
        if ($err != 0) {
            echo 'KernelTools: calcul de la temperature extraction ( mode EXHAUST_GAS_TEMPERATURE= 1) - retour erreur:';
        } else {
            $TExt = $this->sequip->loadEquipmentData($sequip, 500)[0];
            $this->cleanSpecificEqpPrm($idStudyEquipment, 500);
            $p = new StudEqpPrm();
            $p->ID_STUDY_EQUIPMENTS = $idStudyEquipment;
            $p->VALUE_TYPE = EXHAUST_TEMP;
            $p->VALUE = $TExt;
            $p->save();
        }

    }

    private function cleanSpecificEqpPrm($idStudyEquipment, $value_type)
    {
        StudEqpPrm::where('ID_STUDY_EQUIPMENTS', $idStudyEquipment)
                    ->where('VALUE_TYPE', '>=', $value_type)
                    ->where('VALUE_TYPE', '<', $value_type + 100)->delete();
    }

    private function runKernelToolCalculator($idStudy, $idStudyEquipment)
    {
        $study = Study::find($idStudy);

        $conf = $this->kernel->getConfig($this->auth->user()->ID_USER, $idStudy, $idStudyEquipment, 1, 1, 'c:\\temp\\'.$study->STUDY_NAME.'\\ToolCalculator_'.$idStudy.'_'.$idStudyEquipment.'.txt');
        return $this->kernel->getKernelObject('KernelToolCalculator')->KTCalculator($conf, 1);
    }

    private function setEqpPrmInitialData($dd, $value) 
    {
        for ($i = 0; $i < count($dd); $i++) {
            $dd[$i] = $value;
        }
        return $dd;
    }

    private function getEqpPrmInitialData (array $dd, $type, $isTS)
    {
        $mm = MinMax::where('LIMIT_ITEM', $type)->first();
        for ($i=0; $i < count($dd); $i++) {
            if ((($type > 0) && ($mm != null))) {
                $dd[$i] = doubleval($mm->DEFAULT_VALUE);
            } else {
                $dd[$i] = 0.0;
            }
        }
        
        if (($isTS) && (count($dd) > 1)) {
            $multiTRRatio = MinMax::where('LIMIT_ITEM', MIN_MAX_MULTI_TR_RATIO)->first();
            if ($multiTRRatio != null) {
                $dd[0] = doubleval( doubleval($dd[0]) * $multiTRRatio->DEFAULT_VALUE);
            }
        }

        return $dd;
    }
}
