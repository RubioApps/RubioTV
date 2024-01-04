<?php 
define('_TVEXEC', 1);

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

define('TV_BASE', dirname(__FILE__));
require_once TV_BASE . '/includes/defines.php';

// Load Factory
require_once TV_INCLUDES . '/factory.php';
$factory    = new RubioTV\Framework\Factory();
// Load EPG
$epg		= new RubioTV\Framework\EPG();
// Trigger the cron
$epg->Cron();

