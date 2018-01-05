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
$router->POST('/api/v1/admin/newuser', 'Api1\\Admin@newUser');

/**
 * POST update product
 * Summary: 
 * Notes: get head balance result
 * Output-Formats: [application/json]
 */
$router->POST('/api/v1/products/{id}/elements', 'Api1\\Products@updateProductElement');