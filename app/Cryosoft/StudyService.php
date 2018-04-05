<?php

namespace App\Cryosoft;

use Illuminate\Contracts\Auth\Factory as Auth;
use App\Cryosoft\ValueListService;
use App\Cryosoft\UnitsConverterService;

use App\Models\Study;
use App\Models\Price;

class StudyService
{
    /**
    * @var Illuminate\Contracts\Auth\Factory
    */
    protected $auth;

    public function __construct(\Laravel\Lumen\Application $app)
    {
        $this->app = $app;
        $this->auth = $app['Illuminate\\Contracts\\Auth\\Factory'];
        $this->value = $app['App\\Cryosoft\\ValueListService'];
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

    public function getStudyPrice($study) {
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

    public function convertPointForDB($ldShape, $bIsParallel, $appDim) {
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
}
