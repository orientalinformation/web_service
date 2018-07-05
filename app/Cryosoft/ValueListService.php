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
    public $PROFIL_EXPERT = 2;
    public $CONV_SPEED_UNIT = 3;
    public $CONSUMPTION_UNIT = 28;
    public $CONSUM_MAINTIEN = 31;
    public $CONSUM_MEF = 34;
    public $MESH_CUT = 20;
    public $UNIT_TIME = 5;
    public $CONV_COEFF = 14;
    public $PRODCHART_DIMENSION = 38;
    public $W_CARPET_SHELVES = 22;
    public $SLOPES_POSITION = 23;
    public $LINE = 17;
    public $MATERIAL_RISE = 24;
    public $PROD_DIMENSION = 19;
    public $THICKNESS_PACKING = 16;
    public $PRESSURE = 15;
    public $CONDUCTIVITY = 10;
    public $DENSITY = 7;
    public $RESERVOIR_CAPACITY_LN2 = 18;
    public $RESERVOIR_CAPACITY_CO2 = 25;
    public $LENGTH = 3;

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
    public $NO_SPECIFIC_SIZE = -1.0;
    public $CAP_DIMMAT_ENABLE = 16;
    public $CAP_VARIABLE_TR = 1;
    public $NO_RESULTS = "---";
    public $CAP_CONSO_ENABLE = 256;
    public $RESULT_NOT_APPLIC = "****";
    public $CAP_VARIABLE_TOC = 8192;
    public $DIMA_STATUS_KO = 0;
    public $DIMA_STATUS_OK = 1;
    public $CAP_OPTIM_ENABLE = 64;
    public $CAP_COOLING_EQUIPMENT = 2048;
    public $CAP_EQP_DEPEND_ON_TS = 65536;
    /****************************ESTIMATION*********************/
    public $EQUIP_STANDARD = 1;
    public $BRAIN_OPTIM_TSFIXED = 1;
    public $BRAIN_OPTIM_TRFIXED = 2;
    public $BRAIN_OPTIM_DHPFIXED = 3;
    public $BRAIN_OPTIM_TOPFIXED = 4;
    public $BRAIN_OPTIM_COSTFIXED = 5;

    /****************** MIN_MAX *********************************/

    public $MIN_MAX_TRACE_LEVEL             = 16;   // limit item for trace level
    public $MIN_MAX_WEEKLY_PRODUCTION       = 1000; //weekly production
    public $MIN_MAX_PRODUCT_DURATION        = 1001; //product duration
    public $MIN_MAX_DAILY_STARTUP           = 1002; //daily startup
    public $MIN_MAX_FLOW_RATE               = 1003; // limit for unit kernel FLOW RATE
    public $MIN_MAX_PROD_WEEK_PER_YEAR      = 1004; // limit for unit kernel Number of production weeks
    public $MIN_MAX_TEMP_AMBIANT            = 1005; // limit for temperature ambiant
    public $MIN_MAX_AMBIENT_HUM             = 1006; // limit for unit kernel ambiant humidity
    public $MIN_MAX_AVG_TEMPERATURE_DES     = 1007; // limit for unit kernel average temperature desired
    public $MIN_MAX_DWELLING_TIME           = 1008; // limit for unit kernel DWELLING TIME APROX
    public $MIN_MAX_INITIAL_TEMPERATURE     = 1009; // initial temp for product and product element
    public $MIN_MAX_CONDUCTIVITY_TERM       = 1051; // limit for conductivity termique
    public $MIN_MAX_PACKING_THICKNESS       = 1052; //previously to 24 limit for packing thickness
    public $MIN_MAX_HEAT                    = 1053; // limit for unit kernel specific heat
    public $MIN_MAX_DENSITY             = 1054; // limit for unit kernel density
    public $MIN_MAX_PROTID                  = 1055; // limit for unit kernel protid (%)
    public $MIN_MAX_LIPID                   = 1056; // limit for unit kernel lipid (%)
    public $MIN_MAX_GLUCID                  = 1057; // limit for unit kernel glucid (%)
    public $MIN_MAX_WATER                   = 1058; // limit for unit kernel water (%)
    public $MIN_MAX_SALT                    = 1059; // limit for unit kernel salt (%)
    public $MIN_MAX_AIR                     = 1060; // Limit for air value in component page  (%)
    public $MIN_MAX_UNFREEZE_WATER      = 1061; // limit for unit kernel unfreezable water (%)
    public $MIN_MAX_FREEZE_TEMPERATURE  = 1062; // limit for unit kernel FREEZE TEMPERATURE
    public $MIN_MAX_COMPOSITION_TOTAL       = 1118; // limit for total of component composition
    public $MIN_MAX_AVPRODINTEMP            = 1066; // limit for unit kernel AVERAGE PRODUCT INPUT TEMPERATURE
    public $MIN_MAX_TEMP_SETPOINT           = 1067; // limit for unit kernel TEMPERATURE SETPOINT
    public $MIN_MAX_DWELLING_TIME_REF       = 1068; // limit for unit kernel DWELLING TIME
    public $MIN_MAX_TEMPERATURE             = 1086; // limit for unit kernel temperature
    public $MIN_MAX_PROCENT                 = 1089; // limit for procent
    public $MIN_MAX_PRODUCT_WEIGHT      = 1100; // limit for unit kernel PRODUCT WEIGHT
    public $MIN_MAX_NB_SHELVES          = 1090; // limit for number of shelves
    public $MIN_MAX_ENERGY_PRICE            = 1105; // Limit for unit kernel ENERGY_PRICE
    public $MIN_MAX_NEW_POSITION            = 1108; // Limit for unit kernel NEW POSITION (translate mode of new equipment)
    public $MIN_MAX_PROD_VOLUME         = 1129; // Limit for prod volume in some equipments

    public $MIN_MAX_PROD_ELEMENT            = 1300; // Limit for prod element (number max)

        //CALCULATION_PARAMETERS
    public $MIN_MAX_CALCP_MAX_IT_NB             = 1010;
    public $MIN_MAX_CALCP_NB_TIME_STEP          = 1011;
    public $MIN_MAX_CALCP_RELAX_COEFF               = 1012;
    public $MIN_MAX_CALCP_TIME_STEP                 = 1013;
    public $MIN_MAX_CALCP_TOP_SURF_TEMP_REACHED     = 1014;
    public $MIN_MAX_CALCP_INT_TEMP_REACHED      = 1015;
    public $MIN_MAX_CALCP_BOTTOM_SURF_REACHED       = 1016;
    public $MIN_MAX_CALCP_AVG_TEMP_REACHED      = 1017;
    public $MIN_MAX_CALCP_COEFF_TRANSFERT           = 1018;       // list: alpha top, bottom, left, right, front, rear
    public $MIN_MAX_CALCP_REQUEST_PRECISION         = 1019;
    public $MIN_MAX_CALCP_STORAGE_STEP          = 1106;
    public $MIN_MAX_CALCP_PRECISION_LOG_STEP        = 1107;
    public $MIN_MAX_CALCP_NB_OPTIM              = 1130;
    public $MIN_MAX_CALCP_OPTIMSIMPLE_ERROR_H       = 1131;
    public $MIN_MAX_CALCP_OPTIMSIMPLE_ERROR_T       = 1132;
    public $MIN_MAX_CALCP_OPTIMREFINE_ERROR_H       = 1133;
    public $MIN_MAX_CALCP_OPTIMREFINE_ERROR_T       = 1134;
    public $MIN_MAX_CALCP_OPTIMFULL_ERROR_H         = 1135;
    public $MIN_MAX_CALCP_OPTIMFULL_ERROR_T         = 1136;
    public $MIN_MAX_CALCP_OPTIMDHPMAX_ERROR_H       = 1137;
    public $MIN_MAX_CALCP_OPTIMDHPMAX_ERROR_T       = 1138;

        //MAILLAGE MINMAX
    public $MAILLAGE_LIMITITEM_X                    = 1;
    public $MAILLAGE_LIMITITEM_Y                    = 2;
    public $MAILLAGE_LIMITITEM_Z                    = 3;
    public $MIN_MAX_MESH_RATIO                      = 1064;

        // EQUIPMENT MIN_MAX
    public $MIN_MAX_EQUIPMENT_LENGTH                = 1076;
    public $MIN_MAX_EQUIPMENT_WIDTH                 = 1077;
    public $MIN_MAX_EQUIPMENT_HEIGHT                = 1078;

        // STUDY LINE MIN_MAX
    public $MIN_MAX_STUDY_LINE_GAZ_TEMP                 = 1028;
    public $MIN_MAX_STUDY_LINE_PRESSURE                 = 1027;
    public $MIN_MAX_STUDY_LINE_HEIGHT                       = 1026;
    public $MIN_MAX_STUDY_LINE_INSULATEDLINE_LENGHT     = 1020;
    public $MIN_MAX_STUDY_LINE_NON_INSULATEDLINE_LENGHT = 1021;
    public $MIN_MAX_STUDY_LINE_ELBOWS_NUMBER                = 1022;
    public $MIN_MAX_STUDY_LINE_TEES_NUMBER                  = 1023;
    public $MIN_MAX_STUDY_LINE_INSULATEDVALVE_NUMBER        = 1024;
    public $MIN_MAX_STUDY_LINE_NON_INSULATEDVALVE_NUMBER    = 1025;

        //RESULT PARAMETERS
    public $MIN_MAX_RESULT_PARAM                        = 1089;
    public $MIN_MAX_TFPMARGIN_WARN                      = 1140;

        //STUD_EQP_PRM
    public $MIN_MAX_STDEQP_DW_TIME                  = 1035;
    public $MIN_MAX_STDEQP_TEMP_REGULATION_LN2      = 1036;
    public $MIN_MAX_STDEQP_TEMP_REGULATION_CO2      = 1091;
    public $MIN_MAX_STDEQP_TEMP_REGULATION_FM       = 1092;
    public $MIN_MAX_STDEQP_TEMP_REGULATION_LN2_BATH = 1093;
    public $MIN_MAX_STDEQP_CONVECTION_SPEED         = 1037;
    public $MIN_MAX_STDEQP_TOP                      = 704;
    public $MIN_MAX_INTERVAL_LENGHT                 = 1034;
    public $MIN_MAX_INTERVAL_WIDTH                  = 1033;
    public $MIN_MAX_MULTI_TR_RATIO                  = 1095;
    public $MIN_MAX_SHELVES_LENGTH                  = 1096;
    public $MIN_MAX_SHELVES_WIDTH                   = 1097;
    public $MIN_MAX_EXH_RES_GAS_TEMP                = 1028;
    public $MIN_MAX_EQP_PROFIL_CONVECTION           = 1039;
    public $MIN_MAX_EQP_PROFIL_TEMPERATURE          = 1040;

        // OUT_PRODUCT_CHART : ISOCRONE
    public $MIN_MAX_OUT_PRODUCT_CHART_ISOCRONE  = 1101;

        // MIN_MAX values for PRODUCT DIM1, DIM2, DIM3
    public $MIN_MAX_PRODUCT_DIM1                    = 1102;
    public $MIN_MAX_PRODUCT_DIM2                    = 1103;
    public $MIN_MAX_PRODUCT_DIM3                    = 1104;

        // OUT_PRODUCT_CHART :
    public $MINMAX_PRODUCTCHART_NBSAMPLE            = 1114;
    public $MINMAX_OUT_2D_CONTOUR_TEMP_STEP         = 1115;
    public $MINMAX_OUT_2D_CONTOUR_TEMP_BOUNDS       = 1280;

        //MINMAX REPORT
    public $MINMAX_REPORT_NBSAMPLE                  = 1116;
    public $MINMAX_REPORT_TEMP_STEP                 = 1117;
    public $MINMAX_REPORT_TEMP_BOUNDS               = 1281;

        //MIN MAX LINE ELT
    public $MINMAX_LINE_DIAMETER                    = 1109;
    public $MINMAX_TANK_CAPACITY                    = 1110;
    public $MINMAX_LINE_ELTLOSSES1                  = 1111;
    public $MINMAX_TANK_ELTLOSSES1                  = 1112;
    public $MINMAX_LINE_ELTLOSSES2                  = 1113;
    //Reference data
    public $BROUILLON = 1;
    public $TEST = 2;
    public $ACTIF = 3;
    public $CERTIFIE = 4;
    public $DISABLED = 5;
    public $SLEEPING = 6;
    public $SLEEPING_COPY = 7;
    public $ACTIVE_FROM_SLEEPING = 8;
    public $OBSOLETE = 9;

    public $BRAIN_RUN_NONE            = 0;            // brain never runs
    public $BRAIN_RUN_SIMPLIFIED    = 1;            // come from calculate popup => simplified run
    public $BRAIN_RUN_REFINE        = 2;            // come from refine popup => simplified run
    public $BRAIN_RUN_FULL_NO        = 3;            // come from full request popup with no access to product chart
    public $BRAIN_RUN_FULL_YES        = 4;            // come from full request popup with access to product chart
    
    public $STUDY_ESTIMATION_MODE    = 1;        // budget Estimation
    public $STUDY_OPTIMUM_MODE        = 3;        // Optimum Equipement
    public $STUDY_SELECTED_MODE        = 2;        // Selected Equipement
    
    // shapes
    public $SLAB                             = 1;
    public $PARALLELEPIPED_STANDING         = 2;
    public $PARALLELEPIPED_LAYING             = 3;
    public $CYLINDER_STANDING                 = 4;
    public $CYLINDER_LAYING                 = 5;
    public $SPHERE                             = 6;
    public $CYLINDER_CONCENTRIC_STANDING    = 7;
    public $CYLINDER_CONCENTRIC_LAYING        = 8;
    public $PARALLELEPIPED_BREADED            = 9;
    
    public $POSITION_PARALLEL = 1;
    public $POSITION_NOT_PARALLEL = 0;
    

    public $TRHIGHT_INDEX     = 0;                        // TR+10
    public $TR_INDEX         = 1;                        // TR
    public $TRLOW_INDEX     = 2;                        // TR-10


    public $DIMA_TYPE_ESTIMATION    = 0x0000;        // result for estimation
    public $DIMA_TYPE_DHP_CHOSEN    = 0x0001;        // result for choosen hourly production (optimum+selected)
    public $DIMA_TYPE_DHP_MAX        = 0x0010;        // result for maximum hourly production (optimum+selected)
    
    public $PROD_ISOTHERM              = 1;
    public $PROD_NOT_ISOTHERM         = 0;
    
    public $PRODELT_UNDEFINED         = 0;
    public $PRODELT_ISOTHERM         = 1;
    public $PRODELT_NOT_ISOTHERM     = 2;

    public $MESH_AXIS_1                 = 1;    //axe 1
    public $MESH_AXIS_2                 = 2;    //axe 2
    public $MESH_AXIS_3                 = 3;    //axe 3

    public $IMG_LAYOUTRES_HEIGHT        = 300;            //graph type VerticalLinePlot height
    public $IMG_LAYOUTRES_WIDTH         = 300;            //graph type VerticalLinePlot width

}
