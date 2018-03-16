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
    
}
