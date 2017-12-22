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