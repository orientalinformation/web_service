<?php
/**
 * GET getFamilyTranslations
 * Summary:
 * Notes: get list family
 * Output-Formats: [application/json]
 */
$router->GET('/api/v1/referencedata/{lang}/family', 'Api1\\ReferenceData@getFamilyTranslations');

/**
 * GET getUsers
 * Summary:
 * Notes: get list user
 * Output-Formats: [application/json]
 */
$router->GET('/api/v1/admin/users', 'Api1\\Admin@getUsers');

/**
 * PUT add user
 * Summary:
 * Notes: add user
 * Output-Formats: [application/json]
 */
$router->PUT('/api/v1/admin/users', 'Api1\\Admin@newUser');

/**
 * POST updateUser
 * Summary:
 * Notes: update User
 * Output-Formats: [number]
 */
$router->POST('/api/v1/admin/users/{id}', 'Api1\\Admin@updateUser');

/**
 * GET deleteUser
 * Summary:
 * Notes: delete User
 * Output-Formats: [number]
 */
$router->DELETE('/api/v1/admin/users/{id}', 'Api1\\Admin@deleteUser');

/**
 * POST disconnectUser
 * Summary:
 * Notes: disconnect User
 * Output-Formats: [number]
 */
$router->POST('/api/v1/admin/connections/{id}', 'Api1\\Admin@disconnectUser');

/**
 * GET disconnectUser
 * Summary:
 * Notes: disconnect User
 * Output-Formats: [number]
 */
$router->GET('/api/v1/admin/connections', 'Api1\\Admin@loadConnections');

/**
 * GET findRefPackingElmt
 * Summary:
 * Notes: get packing elmt
 * Output-Formats: [application/json]
 */
$router->GET('/api/v1/referencedata/packing', 'Api1\\PackingElements@findRefPackingElmt');

/**
 * PUT add packing
 * Summary:
 * Notes: add packing
 * Output-Formats: [application/json]
 */
$router->PUT('/api/v1/referencedata/packing', 'Api1\\PackingElements@newPacking');

/**
 * GET deletePacking
 * Summary:
 * Notes: delete Packing
 * Output-Formats: [string]
 */
$router->DELETE('/api/v1/referencedata/packing/{id}', 'Api1\\PackingElements@deletePacking');

/**
 * POST updatePacking
 * Summary:
 * Notes: update PackingElmt
 * Output-Formats: [number]
 */
$router->POST('/api/v1/referencedata/packing', 'Api1\\PackingElements@updatePacking');

/**
 * PUT save as packing
 * Summary:
 * Notes: save as packing
 * Output-Formats: [application/json]
 */
$router->PUT('/api/v1/referencedata/packingelmt', 'Api1\\PackingElements@saveAsPacking');

/**
 * GET findRefLineElmt
 * Summary:
 * Notes: get line elmt
 * Output-Formats: [application/json]
 */
$router->GET('/api/v1/referencedata/pipeline', 'Api1\\PipeLine@findRefPipeline');

/**
 * PUT add pipeline
 * Summary:
 * Notes: add pipeline
 * Output-Formats: [application/json]
 */
$router->PUT('/api/v1/referencedata/pipeline', 'Api1\\PipeLine@newPipeLine');

/**
 * DELETE deletePipeLine
 * Summary:
 * Notes: delete PipeLine
 * Output-Formats: [string]
 */
$router->DELETE('/api/v1/referencedata/pipeline/{id}', 'Api1\\PipeLine@deletePipeLine');

/**
 * POST updatePipeLine
 * Summary:
 * Notes: update LineElmt
 * Output-Formats: [number]
 */
$router->POST('/api/v1/referencedata/pipeline', 'Api1\\PipeLine@updatePipeLine');

/**
 * PUT save as pipe line
 * Summary:
 * Notes: save as pipe line
 * Output-Formats: [application/json]
 */
$router->PUT('/api/v1/referencedata/lineelmt', 'Api1\\PipeLine@saveAsPipeLine');

/**
 * GET getListLineType
 * Summary:
 * Notes: get list line type
 * Output-Formats: [application/json]
 */
$router->GET('/api/v1/translation/linetype', 'Api1\\PipeLine@getListLineType');

/**
 * GET getListLineType
 * Summary:
 * Notes: get list line type
 * Output-Formats: [application/json]
 */
$router->GET('/api/v1/translation/energies', 'Api1\\PipeLine@getListEnergies');

/**
 * GET findRefEquipment
 * Summary:
 * Notes: get equipments
 * Output-Formats: [application/json]
 */
$router->GET('/api/v1/referencedata/equipments', 'Api1\\Equipments@findRefEquipment');

/**
 * POST changePassword
 * Summary:
 * Notes: change password
 * Output-Formats: [number]
 */
$router->POST('/api/v1/users/{id}/changepassword', 'Api1\\Users@changePassword');

/**
 * GET getEnergies
 * Summary:
 * Notes: get list Energies
 * Output-Formats: [application/json]
 */
$router->GET('/api/v1/users/energies', 'Api1\\Users@getEnergies');

/**
 * GET getConstructors
 * Summary:
 * Notes: get list Constructors
 * Output-Formats: [application/json]
 */
$router->GET('/api/v1/users/constructors', 'Api1\\Users@getConstructors');

/**
 * GET getFamilies
 * Summary:
 * Notes: get list Family (equipment series)
 * Output-Formats: [application/json]
 */
$router->GET('/api/v1/users/families', 'Api1\\Users@getFamilies');

/**
 * GET getOrigines
 * Summary:
 * Notes: get list Origines (equipment origines)
 * Output-Formats: [application/json]
 */
$router->GET('/api/v1/users/origines', 'Api1\\Users@getOrigines');

/**
 * GET getProcesses
 * Summary:
 * Notes: get list Processes (Processes type)
 * Output-Formats: [application/json]
 */
$router->GET('/api/v1/users/processes', 'Api1\\Users@getProcesses');

/**
 * GET getModels
 * Summary:
 * Notes: get list Models 
 * Output-Formats: [application/json]
 */
$router->GET('/api/v1/users/models', 'Api1\\Users@getModels');

/**
 * GET getLangue
 * Summary:
 * Notes: get list Langue
 * Output-Formats: [application/json]
 */
$router->GET('/api/v1/users/lang', 'Api1\\Users@getLangue');

/**
 * GET getMonetary
 * Summary:
 * Notes: get list Monetary
 * Output-Formats: [application/json]
 */
$router->GET('/api/v1/users/monetary', 'Api1\\Users@getMonetary');

/**
 * GET getUnits
 * Summary:
 * Notes: get list Units 
 * Output-Formats: [application/json]
 */
$router->GET('/api/v1/users/{id}/units', 'Api1\\Users@getUnits');

/**
 * POST updateUnits
 * Summary:
 * Notes: update form Units 
 * Output-Formats: [application/json]
 */
$router->POST('/api/v1/users/{id}/units', 'Api1\\Users@updateUnits');

/**
 * GET getUser
 * Summary:
 * Notes: get user by ID
 * Output-Formats: [application/json]
 */
$router->GET('/api/v1/users/{id}', 'Api1\\Users@getUser');

/**
 * GET getUnitData
 * Summary:
 * Notes: Get Price, interval Width Lenght
 * Output-Formats: [application/json]
 */
$router->GET('/api/v1/studies/{id}/equipment/unitData', 'Api1\\Equipments@getUnitData');

/**
 * POST updatePrice
 * Summary:
 * Notes: update Price 
 * Output-Formats: [application/json]
 */
$router->POST('/api/v1/studies/{id}/equipment/price', 'Api1\\Equipments@updatePrice');

/**
 * POST updateInterval
 * Summary:
 * Notes: update Interval Leght Width 
 * Output-Formats: [application/json]
 */
$router->POST('/api/v1/studies/{id}/equipment/interval', 'Api1\\Equipments@updateInterval');

/**
 * PUT save  equipment
 * Summary:
 * Notes: save equipment
 * Output-Formats: [application/json]
 */
$router->PUT('/api/v1/referencedata/equipments', 'Api1\\Equipments@newEquipment');

/**
 * GET getEquipmentFamily
 * Summary:
 * Notes: get list equipment family
 * Output-Formats: [application/json]
 */
$router->GET('/api/v1/referencedata/equipmentfamilys', 'Api1\\Equipments@getEquipmentFamily');

/**
 * GET getEquipmentSeries
 * Summary:
 * Notes: get list equipment series
 * Output-Formats: [application/json]
 */
$router->GET('/api/v1/referencedata/equipmentseries', 'Api1\\Equipments@getEquipmentSeries');

/**
 * GET getRamps
 * Summary:
 * Notes: get list ramps
 * Output-Formats: [application/json]
 */
$router->GET('/api/v1/referencedata/ramps', 'Api1\\Equipments@getRamps');

/**
 * GET getShelves
 * Summary:
 * Notes: get list shelves
 * Output-Formats: [application/json]
 */
$router->GET('/api/v1/referencedata/shelves', 'Api1\\Equipments@getShelves');

/**
 * GET getConsumptions
 * Summary:
 * Notes: get list Consumptions
 * Output-Formats: [application/json]
 */
$router->GET('/api/v1/referencedata/consumptions', 'Api1\\Equipments@getConsumptions');

/**
 * GET getReport
 * Summary:
 * Notes: get data report for study
 * Output-Formats: [application/json]
 */
$router->GET('/api/v1/studies/{id}/report', 'Api1\\Reports@getReport');

/**
 * GET getMeshAxisPos
 * Summary:
 * Notes: get data MeshAxisPos
 * Output-Formats: [application/json]
 */
$router->GET('/api/v1/studies/{id}/meshaxispos', 'Api1\\Reports@getMeshAxisPos');

/**
 * PUT save  report
 * Summary:
 * Notes: save report
 * Output-Formats: [application/json]
 */
$router->PUT('/api/v1/studies/{id}/report', 'Api1\\Reports@saveReport');

/**
 * GET getEquipmentFilter
 * Summary:
 * Notes: get data equipment of filter
 * Output-Formats: [application/json]
 */
$router->GET('/api/v1/referencedata/equipment/{id}/filter', 'Api1\\Equipments@getEquipmentFilter');

/**
 * POST postFile
 * Summary:
 * Notes: upload file 
 * Output-Formats: [string]
 */
$router->POST('/api/v1/upload', 'Api1\\Reports@postFile');

/**
 * GET getDataSubFamily
 * Summary:
 * Notes: get data sub family
 * Output-Formats: [application/json]
 */
$router->GET('/api/v1/referencedata/subfamily', 'Api1\\ReferenceData@getDataSubFamily');

/**
 * GET getMinMax
 * Summary:
 * Notes: get data min max 
 * Output-Formats: [application/json]
 */
$router->GET('/api/v1/minmax', 'Api1\\CheckMinMax@getMinMax');

/**
 * POST checkCalculationParameters
 * Summary:
 * Notes: check Calculation Parameters
 * Output-Formats: [application/json]
 */
$router->POST('/api/v1/calculator/calculationparameters', 'Api1\\Calculator@checkCalculationParameters');

/**
 * POST checkBrainCalculationParameters
 * Summary:
 * Notes: check Brain Calculation Parameters
 * Output-Formats: [application/json]
 */
$router->POST('/api/v1/calculator/braincalculationparameters', 'Api1\\Calculator@checkBrainCalculationParameters');

/**
 * POST checkStartCalculationParameters
 * Summary:
 * Notes: check Start Calculation Parameters
 * Output-Formats: [application/json]
 */
$router->POST('/api/v1/calculator/startcalculationparameters', 'Api1\\Calculator@checkStartCalculationParameters');

/**
 * POST checkStartCalculationParameters
 * Summary:
 * Notes: check Start Calculation Parameters
 * Output-Formats: [application/json]
 */
$router->POST('/api/v1/referencedata/savedatacomponent', 'Api1\\ReferenceData@checkDataComponent');

/**
 * POST checkTemperature
 * Summary:
 * Notes: Check unit min max temperatures
 * Output-Formats: [application/json]
 */
$router->POST('/api/v1/referencedata/checktemperature', 'Api1\\ReferenceData@checkTemperature');

/**
 * POST checkPacking
 * Summary:
 * Notes: Check unit min max Packing
 * Output-Formats: [application/json]
 */
$router->POST('/api/v1/referencedata/checkpacking', 'Api1\\PackingElements@checkPacking');

/**
 * GET getMonetary
 * Summary:
 * Notes: get list Monetary 
 * Output-Formats: [application/json]
 */
$router->GET('/api/v1/users/{id}/unitsmonetary', 'Api1\\Users@getMonetaryUser');

/**
 * POST checkPipeline
 * Summary:
 * Notes: Check unit min max Pipeline
 * Output-Formats: [application/json]
 */
$router->POST('/api/v1/referencedata/checkpipeline', 'Api1\\PipeLine@checkPipeline');

/**
 * POST checkEquipment
 * Summary:
 * Notes: Check unit min max Equipment
 * Output-Formats: [application/json]
 */
$router->POST('/api/v1/referencedata/checkequipment', 'Api1\\Equipments@checkEquipment');

/**
 * POST checkUpdateEquipment
 * Summary:
 * Notes: Check unit min max Equipment
 * Output-Formats: [application/json]
 */
$router->POST('/api/v1/referencedata/checkupdateequipment', 'Api1\\Equipments@checkUpdateEquipment');

/**
 * POST checkRedrawCurves
 * Summary:
 * Notes: Check unit min max Curves
 * Output-Formats: [application/json]
 */
$router->POST('/api/v1/referencedata/checkredrawcurves', 'Api1\\Equipments@checkRedrawCurves');

/**
 * POST checkBuildForNewTR
 * Summary:
 * Notes: Check unit min max tempsetpoint
 * Output-Formats: [application/json]
 */
$router->POST('/api/v1/referencedata/checktempsetpoint', 'Api1\\Equipments@checkBuildForNewTR');

/**
 * GET Data component
 * Summary: 
 * Notes: get head balance result
 * Output-Formats: [application/json]
 */
$router->GET('/api/v1/referencedata/components/{id}', 'Api1\\ReferenceData@getComponentById');
