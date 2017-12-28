<?php

namespace App\Cryosoft;

use Illuminate\Http\Response;


class ValueListService
{	
	//unit
	public $PRODUCT_FOLLOW = 2;
    public $MASS = 4;
    public $TEMPERATURE = 8;
    public $MASS_PER_UNIT = 37;
    public $TIME = 5;
    public $EQUIP_DIMENSION = 21;
    public $CONV_SPEED = 13;
    public $ENTHALPY =9;
    public $PRODUCT_FLOW = 2;
    public $PROFIL_GUEST = 3;
    public $STUDY_ECO_MODE = 1;
    public $CONSUMPTION_UNIT_LN2 = 29;
    public $CONSUM_MAINTIEN_LN2 = 32;
    public $CONSUM_MEF_LN2 = 35;
    public $CONSUMPTION_UNIT_CO2 = 30;
    public $CONSUM_MAINTIEN_CO2 = 33;
    public $CONSUM_MEF_CO2 = 36;
    public $MIN_MAX_EQUIPMENT_WIDTH = 1077;
    public $MIN_MAX_EQUIPMENT_LENGTH = 1076;
    public $PROFIL_EXPERT = 2;
    public $CONV_SPEED_UNIT = 3;
    public $CONSUMPTION_UNIT = 28;
    public $CONSUM_MAINTIEN = 31;
    public $CONSUM_MEF = 34;
    public $MESH_CUT = 20;
    public $UNIT_TIME = 5;
    public $CONV_COEFF = 14;

    //brand mode
    public $BRAIN_MODE_ESTIMATION = 1;
    public $BRAIN_MODE_ESTIMATION_OPTIM = 2;
    public $BRAIN_MODE_OPTIMUM_CALCULATE = 10;
    public $BRAIN_MODE_OPTIMUM_REFINE = 11;
    public $BRAIN_MODE_OPTIMUM_FULL = 12;
    public $BRAIN_MODE_OPTIMUM_DHPMAX = 13;
    public $BRAIN_MODE_SELECTED_CALCULATE = 14;
    public $BRAIN_MODE_SELECTED_REFINE = 15;
    public $BRAIN_MODE_SELECTED_FULL = 16;
    public $BRAIN_MODE_SELECTED_DHPMAX = 17;

    //Value list
    public $VALUE_N_A = "N.A.";
    public $EQUIP_NOT_STANDARD = 0;
    public $STUDY_OPTIMUM_MODE = 3;
    public $STUDY_SELECTED_MODE = 2;
    public $SLAB = 1;
    public $NO_SPECIFIC_SIZE = -1.0;
    public $CAP_DIMMAT_ENABLE = 16;
    public $CAP_VARIABLE_TR = 1;
    public $TRHIGHT_INDEX = 0;
    public $TRLOW_INDEX = 2;
    public $NO_RESULTS = "---";
    public $CAP_CONSO_ENABLE = 256;
    public $RESULT_NOT_APPLIC = "****";
    public $CAP_VARIABLE_TOC = 8192;
    public $DIMA_STATUS_KO = 0;
    public $DIMA_STATUS_OK = 1;

}
