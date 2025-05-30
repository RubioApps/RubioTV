<?php 
/**
 +-------------------------------------------------------------------------+
 | RubioTV  - A domestic IPTV Web app browser                              |
 | Version 1.5.1                                                           |
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
if(php_sapi_name() !== 'cli'){
    throw new \Exception('Cron not accessible');
    die();
}

define('_TVEXEC', 1);
define('TV_BASE', dirname(__FILE__));
require_once TV_BASE . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'defines.php';

// Load Factory
require_once TV_INCLUDES . DIRECTORY_SEPARATOR . 'factory.php';
$factory    = new RubioTV\Framework\Factory();

// Initialize
$factory->initialize();

// Load EPG
$epg		= new RubioTV\Framework\EPG();

// Trigger the cron
$key = $epg->getCronId();
$epg->Unlock($key);
$epg->Cron();
$epg->Lock();

// Finalize
$factory->finalize();
