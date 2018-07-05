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
 * GET getlocationAxisSelected
 * Summary: 
 * Notes: get axis selected number
 * Output-Formats: [application/json]
 */
$router->GET('/api/v1/studies/{id}/locationAxisSelected', 'Api1\\Studies@getlocationAxisSelected');

/**
 * POST saveLocationAxis
 * Summary: 
 * Notes: save mesh axis product chart
 * Output-Formats: [application/json]
 */
$router->POST('/api/v1/studies/{id}/saveLocationAxis', 'Api1\\Studies@saveLocationAxis');

/**
 * GET reCalculate
 * Summary: 
 * Notes: re calculate all study equipment in a study
 * Output-Formats: [application/json]
 */
$router->GET('/api/v1/equipments/{id}/reCalculate', 'Api1\\Equipments@reCalculate');

/**
 * GET getSelectionCriteriaFilter
 * Summary: 
 * Notes: get Selection Criterea filter
 * Output-Formats: [application/json]
 */
$router->GET('/api/v1/equipments/selection/selectionCriteriaFilter', 'Api1\\Equipments@getSelectionCriteriaFilter');

/**
 * GET getAllCompFamily
 * Summary: 
 * Notes: get all CompFamily
 * Output-Formats: [application/json]
 */
$router->GET('/api/v1/components/allCompFamily', 'Api1\\Components@getAllCompFamily');

/**
 * GET getSubfamily
 * Summary: 
 * Notes: get subfamily filter
 * Output-Formats: [application/json]
 */
$router->GET('/api/v1/products/subfamily/{compfamily}', 'Api1\\Products@getSubfamily');

/**
 * GET getMinMaxProduction
 * Summary: 
 * Notes: get min max production
 * Output-Formats: [application/json]
 */
$router->GET('/api/v1/minMaxs/production', 'Api1\\MinMaxs@getMinMaxProduction');

/**
 * GET getMinMaxProductMeshPacking
 * Summary: 
 * Notes: get min max product mesh packing
 * Output-Formats: [application/json]
 */
$router->GET('/api/v1/minMaxs/productMeshPacking', 'Api1\\MinMaxs@getMinMaxProductMeshPacking');

/**
 * GET getMinMaxEquipment
 * Summary: 
 * Notes: get min max equipment
 * Output-Formats: [application/json]
 */
$router->GET('/api/v1/minMaxs/{id}/equipment', 'Api1\\MinMaxs@getMinMaxEquipment');

/**
 * GET findColorPalettes
 * Summary: 
 * Notes: get color palettes
 * Output-Formats: [application/json]
 */
$router->GET('/api/v1/colorPalettes', 'Api1\\ColorPalettes@findColorPalettes');

/**
 * POST updateProductCharColor
 * Summary: 
 * Notes: update product char color
 * Output-Formats: [application/json]
 */
$router->POST('/api/v1/products/{id}/productCharColor', 'Api1\\Products@updateProductCharColor');

/**
 * GET getstudyEquipmentProductChart
 * Summary: 
 * Notes: get Study Equipment Product Chart
 * Output-Formats: [application/json]
 */
$router->GET('/api/v1/studyEquipment/{id}/productChart', 'Api1\\StudyEquipments@getstudyEquipmentProductChart');

/**
 * GET getstudyEquipmentByStudyId
 * Summary: 
 * Notes: get All Study Equipment in Study
 * Output-Formats: [application/json]
 */
$router->GET('/api/v1/studyEquipment/{id}/study', 'Api1\\StudyEquipments@getstudyEquipmentByStudyId');

/**
 * GET getRecordPosition
 * Summary: 
 * Notes: get Study Equipment Record Position
 * Output-Formats: [application/json]
 */
$router->GET('/api/v1/studyEquipment/{id}/recordPosition', 'Api1\\StudyEquipments@getRecordPosition');

/**
 * POST saveEquipSizing
 * Summary: 
 * Notes: save EquipSizing Heat Balance
 * Output-Formats: [application/json]
 */
$router->POST('/api/v1/studyEquipment/{id}/saveEquipSizing', 'Api1\\StudyEquipments@saveEquipSizing');

/**
 * POST addConsPieToReport
 * Summary: 
 * Notes: add Consumption Pie To Report
 * Output-Formats: [application/json]
 */
$router->POST('/api/v1/studyEquipment/{id}/addConsPieToReport', 'Api1\\StudyEquipments@addConsPieToReport');

/**
 * GET getOperatingSetting
 * Summary: 
 * Notes: get Operating Settings
 * Output-Formats: [application/json]
 */
$router->GET('/api/v1/studyEquipment/{id}/operatingSetting', 'Api1\\StudyEquipments@getOperatingSetting');

/**
 * POST saveEquipmentData
 * Summary: 
 * Notes: save Operating Settings Equipment Data
 * Output-Formats: [application/json]
 */
$router->POST('/api/v1/studyEquipment/{id}/saveEquipmentData', 'Api1\\StudyEquipments@saveEquipmentData');

/**
 * POST computeTrTs
 * Summary: 
 * Notes: compute Tr Ts Equiment
 * Output-Formats: [application/json]
 */
$router->POST('/api/v1/studyEquipment/{id}/computeTrTsConfig', 'Api1\\StudyEquipments@computeTrTsConfig');

/**
 * GET units
 * Summary: 
 * Notes: get Admin Units
 * Output-Formats: [application/json]
 */
$router->GET('/api/v1/admin/units', 'Api1\\Admin@units');

/**
 * PUT createUnit
 * Summary: 
 * Notes: create a new unit
 * Output-Formats: [application/json]
 */
$router->PUT('/api/v1/unit', 'Api1\\Units@createUnit');

/**
 * PATCH saveUnit
 * Summary:
 * Notes:
 * Output-Formats: [application/json]
 */
$router->PATCH('/api/v1/unit', 'Api1\\Units@saveUnit');

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
 * GET sizingOptimumResult
 * Summary: 
 * Notes: 
 * Output-Formats: [application/json]
 */
$router->GET('/api/v1/output/sizingresult/{idStudy}/optimum', 'Api1\\Output@sizingOptimumResult');

/**
 * POST sizingOptimumResult
 * Summary: 
 * Notes: 
 * Output-Formats: [application/json]
 */
$router->POST('/api/v1/output/sizingresult/{idStudy}/optimum', 'Api1\\Output@sizingOptimumDraw');

/**
 * GET sizingEstimationResult
 * Summary: 
 * Notes: sizing result estimation
 * Output-Formats: [application/json]
 */
$router->GET('/api/v1/output/sizingresult/estimation', 'Api1\\Output@sizingEstimationResult');

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
 * Notes: save temprecordpts
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

/**
 * POST productChart2DStatic
 * Summary: 
 * Notes: get product chart 2D data record time
 * Output-Formats: [application/json]
 */
$router->POST('/api/v1/output/productChart2DStatic', 'Api1\\Output@productChart2DStatic');

/**
 * POST productchart2DAnim
 * Summary: 
 * Notes: get all product chart 2D data record time
 * Output-Formats: [application/json]
 */
$router->POST('/api/v1/output/productchart2DAnim', 'Api1\\Output@productchart2DAnim');

/**
 * GET readDataContour
 * Summary: 
 * Notes: get data contour file
 * Output-Formats: [application/json]
 */
$router->GET('/api/v1/output/readDataContour/{idStudyEquipment}', 'Api1\\Output@readDataContour');

/**
 * POST computeTrTs
 * Summary: 
 * Notes: compute Tr Ts Output
 * Output-Formats: [application/json]
 */
$router->POST('/api/v1/output/{id}/computeTrTs', 'Api1\\Output@computeTrTs');

/**
 * POST runSequenceCalculation
 * Summary: 
 * Notes: run Sequence Calculation
 * Output-Formats: [application/json]
 */
$router->POST('/api/v1/output/{id}/runSequenceCalculation', 'Api1\\Output@runSequenceCalculation');
