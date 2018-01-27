<?php

/**
 * GET getTempRecordPts
 * Summary: 
 * Notes: get temperature record pts
 * Output-Formats: [application/json]
 */
$router->GET('/api/v1/studies/{id}/tempRecordPts', 'Api1\\Studies@getTempRecordPts');

/**
 * GET getProductElmt
 * Summary: 
 * Notes: get productElmt
 * Output-Formats: [application/json]
 */
$router->GET('/api/v1/studies/{id}/productElmt', 'Api1\\Studies@getProductElmt');

/**
 * GET getProductElmt
 * Summary: 
 * Notes: get productElmt
 * Output-Formats: [application/json]
 */
$router->GET('/api/v1/studies/{id}/meshPoints', 'Api1\\Studies@getMeshPoints');

/**
 * GET getstudyEquipmentProductChart
 * Summary: 
 * Notes: get Study Equipment Product Chart
 * Output-Formats: [application/json]
 */
$router->GET('/api/v1/studyEquipment/{id}/productChart', 'Api1\\StudyEquipments@getstudyEquipmentProductChart');

/**
 * GET getRecordPosition
 * Summary: 
 * Notes: get Study Equipment Record Position
 * Output-Formats: [application/json]
 */
$router->GET('/api/v1/studyEquipment/{id}/recordPosition', 'Api1\\StudyEquipments@getRecordPosition');

/**
 * GET units
 * Summary: 
 * Notes: get Admin Units
 * Output-Formats: [application/json]
 */
$router->GET('/api/v1/admin/units', 'Api1\\Admin@units');

/**
 * PUT createMonetaryCurrency
 * Summary: 
 * Notes: create a new montery currency
 * Output-Formats: [application/json]
 */
$router->PUT('/api/v1/monetaryCurrency', 'Api1\\MonetaryCurrencies@createMonetaryCurrency');

/**
 * PATCH saveMonetaryCurrency
 * Summary:
 * Notes:
 * Output-Formats: [application/json]
 */
$router->PATCH('/api/v1/monetaryCurrency', 'Api1\\MonetaryCurrencies@saveMonetaryCurrency');
/**
 * GET getMonetaryCurrencyById
 * Summary: 
 * Notes: 
 * Output-Formats: [application/json]
 */
$router->GET('/api/v1/monetaryCurrency/{id}', 'Api1\\MonetaryCurrencies@getMonetaryCurrencyById');
/**
 * GET sizingEstimationResult
 * Summary: 
 * Notes: sizing result estimation
 * Output-Formats: [application/json]
 */
$router->GET('/api/v1/output/sizingresult/estimation', 'Api1\\Output@sizingEstimationResult');


/**
 * GET location
 * Summary: 
 * Notes: product chart location
 * Output-Formats: [application/json]
 */
$router->GET('/api/v1/output/location', 'Api1\\Output@location');

/**
 * GET heatExchange
 * Summary: 
 * Notes: get heat exchange chart data
 * Output-Formats: [application/json]
 */
$router->GET('/api/v1/output/heatExchange', 'Api1\\Output@heatExchange');

/**
 * GET timeBased
 * Summary: 
 * Notes: get time based chart data
 * Output-Formats: [application/json]
 */
$router->GET('/api/v1/output/timeBased', 'Api1\\Output@timeBased');

/**
 * GET productSection
 * Summary: 
 * Notes: get product section based chart data
 * Output-Formats: [application/json]
 */
$router->GET('/api/v1/output/productSection', 'Api1\\Output@productSection');

/**
 * POST saveTempRecordPts
 * Summary: 
 * Notes: get product section based chart data
 * Output-Formats: [application/json]
 */
$router->POST('/api/v1/output/saveTempRecordPts', 'Api1\\Output@saveTempRecordPts');

/**
 * GET productchart2D
 * Summary: 
 * Notes: get product chart 2D data
 * Output-Formats: [application/json]
 */
$router->GET('/api/v1/output/productchart2D', 'Api1\\Output@productchart2D');