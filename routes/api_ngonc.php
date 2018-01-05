<?php

/**
 * GET sizingEstimationResult
 * Summary: 
 * Notes: sizing result estimation
 * Output-Formats: [application/json]
 */
$router->GET('/api/v1/output/sizingresult/estimation', 'Api1\\Output@sizingEstimationResult');

/**
 * GET getstudyEquipmentProductChart
 * Summary: 
 * Notes: get study equipment product chart
 * Output-Formats: [application/json]
 */
$router->GET('/api/v1/studyEquipment/{id}/productChart', 'Api1\\StudyEquipments@getstudyEquipmentProductChart');

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