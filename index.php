<?php 
/**
 +-------------------------------------------------------------------------+
 | RubioTV  - A domestic IPTV Web app browser                              |
 | Version 1.3.0                                                           |
 |                                                                         |
 | This program is free software: you can redistribute it and/or modify    |
 | it under the terms of the GNU General Public License as published by    |
 | the Free Software Foundation.                                           |
 |                                                                         |
 | This file forms part of the RubioTV software.                           |
 |                                                                         |
 | If you wish to use this file in another project or create a modified    |
 | version that will not be part of the RubioTV Software, you              |
 | may remove the exception above and use this source code under the       |
 | original version of the license.                                        |
 |                                                                         |
 | This program is distributed in the hope that it will be useful,         |
 | but WITHOUT ANY WARRANTY; without even the implied warranty of          |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the            |
 | GNU General Public License for more details.                            |
 |                                                                         |
 | You should have received a copy of the GNU General Public License       |
 | along with this program.  If not, see http://www.gnu.org/licenses/.     |
 |                                                                         |
 +-------------------------------------------------------------------------+
 | Author: Jaime Rubio <jaime@rubiogafsi.com>                              |
 +-------------------------------------------------------------------------+
*/

define('_TVEXEC', 1);

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
//error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED );

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
$task       = $factory->getTask();

// Get configuration and locale
$config     = $factory->getConfig();

// Get the language
$language   = $factory->getLanguage();

// Get the router
$router     = $factory->getRouter();

// Check login
if($factory->getTask() !== 'login')
{
    if(!$factory->isLogged())
    {
        header('Location:' . $factory->Link('login')); 
        die();        
    }
} 

// Get the page
$page = $factory->getPage();

// Dispatch
require_once $router->dispatch();

ob_end_flush();