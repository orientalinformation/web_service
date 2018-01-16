<?php

/**
 * GET getComponentTranslations
 * Summary: 
 * Notes: 
 * Output-Formats: [application/json]
 */
$router->GET('/api/v1/translations/{lang}/components', 'Api1\\Translations@getComponentTranslations');

/**
 * GET getPackingTranslations
 * Summary: 
 * Notes: 
 * Output-Formats: [application/json]
 */
$router->GET('/api/v1/translations/{lang}/packings', 'Api1\\Translations@getPackingTranslations');

