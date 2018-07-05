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

/**
 * Delete Equipment
 * Summary: 
 * Notes: get head balance result
 * Output-Formats: [application/json]
 */
$router->DELETE('/api/v1/referencedata/equipment/{id}', 'Api1\\Equipments@deleteEquipment');

/**
 * Save as Equipment
 * Summary: 
 * Notes: get head balance result
 * Output-Formats: [application/json]
 */
$router->PUT('/api/v1/referencedata/equipment', 'Api1\\Equipments@saveAsEquipment');

/**
 * Save as Equipment
 * Summary: 
 * Notes: get head balance result
 * Output-Formats: [application/json]
 */
$router->POST('/api/v1/referencedata/equipment', 'Api1\\Equipments@saveEquipment');

/**
 * Run calculate Equipment
 * Summary: 
 * Notes: get head balance result
 * Output-Formats: [application/json]
 */
$router->PUT('/api/v1/referencedata/equipment/{id}', 'Api1\\Equipments@startEquipmentCalculate');

/**
 * GET Data equip charact
 * Summary: 
 * Notes: get head balance result
 * Output-Formats: [application/json]
 */
$router->GET('/api/v1/referencedata/equipcharacts/{idEquip}', 'Api1\\Equipments@getEquipmentCharacts');

/**
 * GET Data hight charact
 * Summary: 
 * Notes: get head balance result
 * Output-Formats: [application/json]
 */
$router->GET('/api/v1/referencedata/highchart', 'Api1\\Equipments@getDataHighChart');

/**
 * GET equip charact
 * Summary: 
 * Notes: get head balance result
 * Output-Formats: [application/json]
 */
$router->GET('/api/v1/referencedata/equipcharact/{id}', 'Api1\\Equipments@getEquipCharactById');

/**
 * update Equip Charact
 * Summary: 
 * Notes: get head balance result
 * Output-Formats: [application/json]
 */
$router->PUT('/api/v1/referencedata/equipcharact', 'Api1\\Equipments@updateEquipCharact');

/**
 * GET Data curve charact
 * Summary: 
 * Notes: get head balance result
 * Output-Formats: [application/json]
 */
$router->GET('/api/v1/referencedata/curve/{idEquip}', 'Api1\\Equipments@getDataCurve');

/**
 * red raw Curves
 * Summary: 
 * Notes: get head balance result
 * Output-Formats: [application/json]
 */
$router->PUT('/api/v1/referencedata/redrawcurves', 'Api1\\Equipments@redrawCurves');

/**
 * Delete Equip charact
 * Summary: 
 * Notes: get head balance result
 * Output-Formats: [application/json]
 */
$router->DELETE('/api/v1/referencedata/equipcharact/{id}', 'Api1\\Equipments@deleteEquipCharact');

/**
 * Delete all Equip charact
 * Summary: 
 * Notes: get head balance result
 * Output-Formats: [application/json]
 */
$router->DELETE('/api/v1/referencedata/equipcharacts/{idEquip}', 'Api1\\Equipments@deleteEquipCharacts');

/**
 * add equip charact
 * Summary: 
 * Notes: get head balance result
 * Output-Formats: [application/json]
 */
$router->POST('/api/v1/referencedata/equipcharact', 'Api1\\Equipments@addEquipCharact');

/**
 * GET tempset point
 * Summary: 
 * Notes: get head balance result
 * Output-Formats: [application/json]
 */
$router->GET('/api/v1/referencedata/tempsetpoint/{idEquip}', 'Api1\\Equipments@getTempSetPoint');

/**
 * Build For new TR
 * Summary: 
 * Notes: get head balance result
 * Output-Formats: [application/json]
 */
$router->PUT('/api/v1/referencedata/tempsetpoint', 'Api1\\Equipments@buildForNewTR');

/**
 * GET list studies
 * Summary: 
 * Notes: get head balance result
 * Output-Formats: [application/json]
 */
$router->GET('/api/v1/calculator/calculatestatus/{idStudy}', 'Api1\\CalculStatus@getMyStudies');

/**
 * PUT initial 
 * Summary: 
 * Notes: get head balance result
 * Output-Formats: [application/json]
 */
$router->PUT('/api/v1/input/meshinitial/{idStudy}', 'Api1\\InputInitial@initTempRecordPts');

/**
 * POST save hight charact
 * Summary: 
 * Notes: get head balance result
 * Output-Formats: [application/json]
 */
$router->POST('/api/v1/referencedata/highchart', 'Api1\\Equipments@saveSelectedProfile');

/**
 * GET data temp profile
 * Summary: 
 * Notes: get head balance result
 * Output-Formats: [application/json]
 */
$router->GET('/api/v1/input/tempprofile', 'Api1\\TempProfiles@getDataSvgTemperature');

/**
 * GET data temp profile
 * Summary: 
 * Notes: get head balance result
 * Output-Formats: [application/json]
 */
$router->GET('/api/v1/input/temppoint', 'Api1\\InputInitial@getDataTempoint');

/**
 * GET Data capability
 * Summary: 
 * Notes: get head balance result
 * Output-Formats: [application/json]
 */
$router->GET('/api/v1/referencedata/capability/{idEquip}', 'Api1\\ReferenceData@getCapabitity');

/**
 * POST warning
 * Summary: 
 * Notes: get head balance result
 * Output-Formats: [application/json]
 */
$router->POST('/api/v1/input/warningequipment', 'Api1\\CheckWarnings@checkWarningEquipment');

/**
 * POST warning
 * Summary: 
 * Notes: get head balance result
 * Output-Formats: [application/json]
 */
$router->POST('/api/v1/input/phamcast', 'Api1\\CheckWarnings@checkPhamCast');

/**
 * PUT study 
 * Summary: 
 * Notes: get head balance result
 * Output-Formats: [application/json]
 */
$router->PUT('/api/v1/input/update/{idStudy}', 'Api1\\Studies@updateStudy');

/**
 * GET Data equipment
 * Summary: 
 * Notes: get head balance result
 * Output-Formats: [application/json]
 */
$router->GET('/api/v1/referencedata/inputequipment/{idEquip}', 'Api1\\Equipments@getInputEquipment');

/**
 * GET Data capability
 * Summary: 
 * Notes: get head balance result
 * Output-Formats: [application/json]
 */
$router->GET('/api/v1/threed/mesh3dInfo/{idProd}', 'Api1\\Input3Ds@getMesh3DInfo');

/**
 * POST init 3D temparature
 * Summary: 
 * Notes: get head balance result
 * Output-Formats: [application/json]
 */
$router->POST('/api/v1/threed/{idProd}/initIso3DTemperature', 'Api1\\Input3Ds@initIso3DTemperature');

/**
 * POST init none iso 3D temparature
 * Summary: 
 * Notes: get head balance result
 * Output-Formats: [application/json]
 */
$router->POST('/api/v1/threed/{idProd}/initNonIso3DTemperature', 'Api1\\Input3Ds@initNonIso3DTemperature');


/**
 * PUT initial 3D
 * Summary: 
 * Notes: get head balance result
 * Output-Formats: [application/json]
 */
$router->PUT('/api/v1/threed/meshinitial/{idStudy}', 'Api1\\InputInitial3D@initTempRecordPts3D');
