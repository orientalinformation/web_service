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
 * GET getMeshPoints
 * Summary: 
 * Notes: get meshPoints Study
 * Output-Formats: [application/json]
 */
$router->GET('/api/v1/studyEquipment/{id}/productChart', 'Api1\\StudyEquipments@getstudyEquipmentProductChart');

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