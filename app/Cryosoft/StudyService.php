<?php

namespace App\Cryosoft;

use Illuminate\Contracts\Auth\Factory as Auth;
use App\Cryosoft\ValueListService;
use App\Cryosoft\UnitsConverterService;

use App\Models\Study;

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

    
}
