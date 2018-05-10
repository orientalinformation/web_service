<?php
/**
 * GET findLines
 * Summary: 
 * Notes: Get a list of line
 * Output-Formats: [application/json]
 */
$router->GET('/api/v1/lines/{id}/getListLine', 'Api1\\Lines@loadPipeline');

/**
 * PUT savePipeline
 * Summary: 
 * Notes: 
 * Output-Formats: [application/json]
 */
$router->POST('/api/v1/lines/saveLines', 'Api1\\Lines@savePipelines');

/**
 * PUT filterTrans
 * Summary: 
 * Notes: 
 * Output-Formats: [application/json]
 */
$router->GET('/api/v1/translations/filterTrans', 'Api1\\Translations@filterTrans');



