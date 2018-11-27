<?php
/**
 * POST loginPost
 * Summary: 
 * Notes: 
 * Output-Formats: [application/json]
 */
$router->POST('/api/v1/login', 'Api1\\Auth@login');
/**
 * GET logoutGet
 * Summary: 
 * Notes: 
 * Output-Formats: [application/json]
 */
$router->GET('/api/v1/logout', 'Api1\\Auth@logout');