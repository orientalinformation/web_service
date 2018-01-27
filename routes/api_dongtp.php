<?php

/**
 * GET start Estimation
 * Summary: 
 * Notes: get head balance result
 * Output-Formats: [application/json]
 */
$router->GET('/api/v1/studyequipment/braincalculate', 'Api1\\Calculator@getStudyEquipmentCalculation');

/**
 * POST start Brain Calcuate
 * Summary: 
 * Notes: get head balance result
 * Output-Formats: [application/json]
 */
$router->POST('/api/v1/studyequipment/startbraincalculate', 'Api1\\Calculator@startBrainCalculate');

/**
 * POST new user
 * Summary: 
 * Notes: get head balance result
 * Output-Formats: [application/json]
 */
$router->POST('/api/v1/admin/users', 'Api1\\Admin@newUser');

/**
 * POST update product
 * Summary: 
 * Notes: get head balance result
 * Output-Formats: [application/json]
 */
$router->POST('/api/v1/products/{id}/elements', 'Api1\\Products@updateProductElement');

/**
 * POST start calcul
 * Summary: 
 * Notes: get head balance result
 * Output-Formats: [application/json]
 */
$router->POST('/api/v1/calculator/startcalcul', 'Api1\\Calculator@startCalcul');

/**
 * POST save calcul optim
 * Summary: 
 * Notes: get head balance result
 * Output-Formats: [application/json]
 */
$router->POST('/api/v1/calculator/calculoptim', 'Api1\\Calculator@calculOptim');

/**
 * POST start calcul optim
 * Summary: 
 * Notes: get head balance result
 * Output-Formats: [application/json]
 */
$router->POST('/api/v1/calculator/startcalculoptim', 'Api1\\Calculator@startCalculOptim');

/**
 * GET brain optim
 * Summary: 
 * Notes: get head balance result
 * Output-Formats: [application/json]
 */
$router->GET('/api/v1/calculator/brainoptim', 'Api1\\Calculator@getBrainOptim');

/**
 * GET study equipment
 * Summary: 
 * Notes: get head balance result
 * Output-Formats: [application/json]
 */
$router->GET('/api/v1/calculator/progressbar', 'Api1\\Calculator@getProgressBarStudyEquipment');

/**
 * GET optimumcalculator
 * Summary: 
 * Notes: get head balance result/products/{id}/packingLayers
 * Output-Formats: [application/json]
 */
$router->GET('/api/v1/calculator/optimumcalculator', 'Api1\\Calculator@getOptimumCalculator');

/**
 * POST start caluclate
 * Summary: 
 * Notes: get head balance result
 * Output-Formats: [application/json]
 */
$router->POST('/api/v1/calculator/startcalculate', 'Api1\\Calculator@startCalculate');

/**
 * GET Data family
 * Summary: 
 * Notes: get head balance result
 * Output-Formats: [application/json]
 */
$router->GET('/api/v1/referencedata/component', 'Api1\\ReferenceData@getDataComponent');

/**
 * PUT Data family
 * Summary: 
 * Notes: get head balance result
 * Output-Formats: [application/json]
 */
$router->PUT('/api/v1/referencedata/component', 'Api1\\ReferenceData@saveDataComponent');

/**
 * PUT Data family
 * Summary: 
 * Notes: get head balance result
 * Output-Formats: [application/json]
 */
$router->PUT('/api/v1/referencedata/calculatefreeze', 'Api1\\ReferenceData@calculateFreeze');

/**
 * GET Data temperatures
 * Summary: 
 * Notes: get head balance result
 * Output-Formats: [application/json]
 */
$router->GET('/api/v1/referencedata/component/{id}', 'Api1\\ReferenceData@getTemperaturesByIdComp');

/**
 * Delete Component
 * Summary: 
 * Notes: get head balance result
 * Output-Formats: [application/json]
 */
$router->DELETE('/api/v1/referencedata/component/{id}', 'Api1\\ReferenceData@deleteComponent');

/**
 * GET Data family
 * Summary: 
 * Notes: get head balance result
 * Output-Formats: [application/json]
 */
$router->GET('/api/v1/referencedata/components', 'Api1\\ReferenceData@getMyComponent');

/**
 * PUT Data family
 * Summary: 
 * Notes: get head balance result
 * Output-Formats: [application/json]
 */
$router->PUT('/api/v1/referencedata/calculate', 'Api1\\ReferenceData@startFCCalculate');

/**
 * GET Data compenths
 * Summary: 
 * Notes: get head balance result
 * Output-Formats: [application/json]
 */
$router->GET('/api/v1/referencedata/compenths/{idComp}', 'Api1\\ReferenceData@getCompenthsByIdComp');

/**
 * GET Data compenth
 * Summary: 
 * Notes: get head balance result
 * Output-Formats: [application/json]
 */
$router->GET('/api/v1/referencedata/compenth/{id}', 'Api1\\ReferenceData@getCompenthById');

/**
 * PUT update compenth
 * Summary: 
 * Notes: get head balance result
 * Output-Formats: [application/json]
 */
$router->PUT('/api/v1/referencedata/compenth', 'Api1\\ReferenceData@updateCompenth');