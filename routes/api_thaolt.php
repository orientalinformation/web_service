<?php

/**
 * GET getMeshView
 * Summary: 
 * Notes: get mesh view of product
 * Output-Formats: [application/json]
 */
$router->GET('/api/v1/products/{id}/meshView', 'Api1\\Products@getMeshView');