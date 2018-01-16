<?php

/**
 * GET getMeshView
 * Summary: 
 * Notes: get mesh view of product
 * Output-Formats: [application/json]
 */
$router->GET('/api/v1/products/{id}/meshView', 'Api1\\Products@getMeshView');

/**
 * PATCH saveStudy
 * Summary:
 * Notes:
 * Output-Formats: [application/json]
 */
$router->PATCH('/api/v1/studies/{id}', 'Api1\\Studies@saveStudy');

/**
 * PATCH saveProduction
 * Summary:
 * Notes:
 * Output-Formats: [application/json]
 */
$router->PATCH('/api/v1/productions/{id}', 'Api1\\Productions@saveProduction');

/**
 * POST generateMesh
 * Summary:
 * Notes: generate product mesh
 * Output-Formats: [application/json]
 */
$router->POST('/api/v1/products/{idProd}/generateMesh', 'Api1\\Products@generateMesh');

/**
 * POST generateDefaultMesh
 * Summary:
 * Notes: generate product default mesh
 * Output-Formats: [application/json]
 */
$router->POST('/api/v1/products/{idProd}/defaultMesh', 'Api1\\Products@generateDefaultMesh');

/**
 * POST initTemperature
 * Summary:
 * Notes: initialize temperature
 * Output-Formats: [application/json]
 */
$router->POST('/api/v1/products/{idProd}/initTemperature', 'Api1\\Products@initTemperature');

/**
 * PUT createStudy
 * Summary:
 * Notes: create a new study
 * Output-Formats: [application/json]
 */
$router->PUT('/api/v1/studies', 'Api1\\Studies@createStudy');

/**
 * GET recentStudies
 * Summary: 
 * Notes: get my recent studies
 * Output-Formats: [application/json]
 */
$router->GET('/api/v1/recentStudies', 'Api1\\Studies@recentStudies');

/**
 * GET StudyEquipments
 * Summary: 
 * Notes: get study equipment by id
 * Output-Formats: [application/json]
 */
$router->GET('/api/v1/studyEquipment/{id}', 'Api1\\StudyEquipments@getStudyEquipmentById');

/**
 * PUT addEquipment
 * Summary: 
 * Notes: 
 * Output-Formats: [application/json]
 */
$router->PUT('/api/v1/studies/{id}/equipments', 'Api1\\Studies@addEquipment');

/**
 * DELETE removeStudyEquipment
 * Summary: 
 * Notes: 
 * Output-Formats: [application/json]
 */
$router->DELETE('/api/v1/studies/{id}/equipments/{idEquip}', 'Api1\\Studies@removeStudyEquipment');