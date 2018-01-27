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
$router->POST('/api/v1/referencedata/packing/{id}', 'Api1\\PackingElements@updatePacking');

/**
 * PUT save as packing
 * Summary:
 * Notes: save as packing
 * Output-Formats: [application/json]
 */
$router->PUT('/api/v1/referencedata/packing/{id}', 'Api1\\PackingElements@saveAsPacking');

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
$router->POST('/api/v1/referencedata/pipeline/{id}', 'Api1\\PipeLine@updatePipeLine');

/**
 * PUT save as pipe line
 * Summary:
 * Notes: save as pipe line
 * Output-Formats: [application/json]
 */
$router->PUT('/api/v1/referencedata/pipeline/{id}', 'Api1\\PipeLine@saveAsPipeLine');

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
