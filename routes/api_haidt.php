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
