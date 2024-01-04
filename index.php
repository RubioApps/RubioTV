<?php 
define('_TVEXEC', 1);

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

ob_start();

header("Access-Control-Allow-Origin: *");
header('Access-Control-Allow-Credentials: true');
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// Saves the start time and memory usage.
$startTime = microtime(1);
$startMem  = memory_get_usage();
session_start();

define('TV_BASE', dirname(__FILE__));
require_once TV_BASE . '/includes/defines.php';

// Load Factory
require_once TV_INCLUDES . '/factory.php';
$factory    = new RubioTV\Framework\Factory();
// Get configuration and locale
$config     = $factory->getConfig();
// Get language
$language   = $factory->getLanguage();
// Get router
$router     = $factory->getRouter();
// Dispatch the page
require_once $router->dispatch();

ob_end_flush();