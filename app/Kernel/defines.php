<?php

define('SC_CLEAN_TMP_DATA',10);  // clean temporary data

define('SC_CLEAN_MODE_ESTIM_2_OPTIM',20);  // estimation -> optimum
define('SC_CLEAN_MODE_OPTIM_2_ESTIM',21);  // optimum -> estimation
define('SC_CLEAN_MODE_ESTIM_2_SELECT',22);  // estimation -> selected
define('SC_CLEAN_MODE_SELECT_2_ESTIM',23);  // selected -> estimation
define('SC_CLEAN_MODE_OPTIM_2_SELECT',24);  // optimum -> selected
define('SC_CLEAN_MODE_SELECT_2_OPTIM',25);  // selected -> optimum

define('SC_CLEAN_OPT_ADD_PIPE',30);  // Add pipeline option
define('SC_CLEAN_OPT_REM_PIPE',31);  // Remove pipeline option
define('SC_CLEAN_OPT_ADD_ECO',32);  // Add economic option
define('SC_CLEAN_OPT_REM_ECO',33);  // remove economic option

define('SC_CLEAN_OUTPUT_ALL',40); // all output data
define('SC_CLEAN_OUTPUT_PRODUCT',41); // product data has changed
define('SC_CLEAN_OUTPUT_PRODUCTION',42); // production data has changed
define('SC_CLEAN_OUTPUT_EQP_PRM',43); // equipment parameters has changed
define('SC_CLEAN_OUTPUT_PACKING',44); // Packing has changed
define('SC_CLEAN_OUTPUT_ECONOMIC',45); // economic data has changed
define('SC_CLEAN_OUTPUT_PROFIT',46); // profit data has changed
define('SC_CLEAN_OUTPUT_PRODUCT_MASS',47); // product real mass has changed
define('SC_CLEAN_OUPTUT_LAYOUT_CHANGED',48);	// Layout results has changed
define('SC_CLEAN_OUTPUT_CALCUL',50); // all analytical and numerical results
                                                                                    // call before analytical calculation
define('SC_CLEAN_OUTPUT_SIZINGCONSO',51);	// Layout results has changed 

define('SC_CLEAN_OUTPUT_OPTIM_BRRUN',53);	// Called before user run new calculation (study mode: optimum
                                                                                    //		or selected - dhp chosen )
define('SC_CLEAN_OUTPUT_OPTIMAX_BRRUN',54);	// Called before user run new calculation (study mode: optimum
                                                                                    //		or selected - dhp max )

define('SC_CLEAN_OUTPUT_ESTIM_BRSTOP',55);	// Called when user stop calculation (study mode: estimation)
define('SC_CLEAN_OUTPUT_OPTIM_BRSTOP',56);	// Called when user stop calculation (study mode: optimum
                                                                                    //		or selected - dhp chosen )
define('SC_CLEAN_OUTPUT_OPTIMAX_BRSTOP',57);	// Called when user stop calculation (study mode: optimum
                                                                                    //		or selected - dhp max ) 

// Equipment capabilities masks : used by Equipment.getCapability 
define('CAP_VARIABLE_TR', 0x00000001);
define('CAP_VARIABLE_TS', 0x00000002);
define('CAP_VARIABLE_VC', 0x00000004);
define('CAP_PHAMCAST_ENABLE', 0x00000008);
define('CAP_DIMMAT_ENABLE', 0x00000010);
define('CAP_LAYOUT_ENABLE', 0x00000020);
define('CAP_OPTIM_ENABLE', 0x00000040);
define('CAP_BRAIN_ENABLE', 0x00000080);
define('CAP_CONSO_ENABLE', 0x00000100);
define('CAP_EXRACT_ENABLE', 0x00000200);
define('CAP_DIM_SPECIAL_ENABLE', 0x00000400);
define('CAP_COOLING_EQUIPMENT', 0x00000800);
define('CAP_CHECK_VOLUME', 0x00001000);
define('CAP_VARIABLE_TOC', 0x00002000);
define('CAP_SPECIFIC_POPUP', 0x00004000);
define('CAP_DISPLAY_DB_NAME', 0x00008000);
define('CAP_EQP_DEPEND_ON_TS', 0x00010000);
define('CAP_TS_FROM_TOC', 0x00020000);
define('CAP_TS_FROM_TR', 0x00040000);
define('CAP_TR_FROM_TS', 0x00080000);
define('CAP_EQUIP_SPECIFIC_SIZE', 0x00100000);
define('CAP_EQUIP_SIZING', 0x00200000);

define('EQUIP_STANDARD', 1);
define('EQUIP_NOT_STANDARD', 0);
define('NO_SPECIFIC_SIZE', -1.0);

//List of TRANS_TYPE  (to complete if required)

define('TRANS_TYPE_COMPONENT',1);
define('TRANS_TYPE_COOLING_FAMILY',2);
define('TRANS_TYPE_PACKING_ELEMENT',3);
define('TRANS_TYPE_SHAPE',4);
define('TRANS_TYPE_EQUIP_FAMILY',5);
define('TRANS_TYPE_EQUIPSERIES',7);
define('TRANS_TYPE_LINE_ELT_TYPE',8);
define('TRANS_TYPE_LANGUAGE',9);
define('TRANS_TYPE_COMP_FAT',12);
define('TRANS_TYPE_EQP_BATCH_PROCESS',13);
define('TRANS_TYPE_COMP_CLASS_PRODUCT',14);
define('TRANS_TYPE_COMP_NATURE_PRODUCT',15);
define('TRANS_TYPE_COMP_SUB_FAMILY',16);
define('TRANS_TYPE_EQP_ORIGIN',17);
define('TRANS_TYPE_LINE_ELT',27);
define('TRANS_TYPE_STATUS',100);

define('CONVECTION_SPEED', 100);
define('DWELLING_TIME', 200);
define('REGULATION_TEMP', 300);
define('ENTHALPY_VAR', 400);
define('EXHAUST_TEMP', 500);
define('OFFSET', 100);

// REPORT
define('ENABLE_CONS_PIE', 1);				// enable to insert consumption pie in to the report
define('DISABLE_CONS_PIE', 0);				// disable the consumption pie

define('EQUIP_SELECTED', 0x01);
define('EQUIP_UNSELECTED', 0x00);

define('SAVE_NUM_TO_DB_YES', 0x01);
define('SAVE_NUM_TO_DB_NO', 0x00);


//STUD_EQP_PRM
define('MIN_MAX_STDEQP_DW_TIME', 1035);
define('MIN_MAX_STDEQP_TEMP_REGULATION_LN2', 1036);
define('MIN_MAX_STDEQP_TEMP_REGULATION_CO2', 1091);
define('MIN_MAX_STDEQP_TEMP_REGULATION_FM', 1092);
define('MIN_MAX_STDEQP_TEMP_REGULATION_LN2_BATH', 1093);
define('MIN_MAX_STDEQP_CONVECTION_SPEED', 1037);
define('MIN_MAX_STDEQP_TOP', 704);
define('MIN_MAX_INTERVAL_LENGHT', 1034);
define('MIN_MAX_INTERVAL_WIDTH', 1033);
define('MIN_MAX_MULTI_TR_RATIO', 1095);
define('MIN_MAX_SHELVES_LENGTH', 1096);
define('MIN_MAX_SHELVES_WIDTH', 1097);
define('MIN_MAX_EXH_RES_GAS_TEMP', 1028);
define('MIN_MAX_EQP_PROFIL_CONVECTION', 1039);
define('MIN_MAX_EQP_PROFIL_TEMPERATURE', 1040);

define('POSITION_PARALLEL', 1);
define('POSITION_NOT_PARALLEL', 0);

define('SHELVES_EURONORME', 0);	//case euronorme
define('SHELVES_GASTRONORME', 1);	//case gastronorme
define('SHELVES_USERDEFINED', 2);	//case user defined

define('INTERVAL_UNDEFINED', -1.0);	// to identify when no data was given by user
define('INTERVAL_USER_TOC', -2.0);	// to identify when the user has forced the TOC value


define('SHELVES_EURO_LENGTH', 0.8);
define('SHELVES_EURO_WIDTH', 0.6);
define('SHELVES_GASTRO_LENGTH', 0.65);
define('SHELVES_GASTRO_WIDTH', 0.53);

define('SLAB', 1);
define('PARALLELEPIPED_STANDING', 2);
define('PARALLELEPIPED_LAYING', 3);
define('CYLINDER_STANDING', 4);
define('CYLINDER_LAYING', 5);
define('SPHERE', 6);
define('CYLINDER_CONCENTRIC_STANDING', 7);
define('CYLINDER_CONCENTRIC_LAYING', 8);
define('PARALLELEPIPED_BREADED', 9);
