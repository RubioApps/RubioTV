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
defined('_TVEXEC') or die;

// Global definitions
$parts = explode(DIRECTORY_SEPARATOR, TV_BASE);

// Defines.
define('TV_ROOT', implode(DIRECTORY_SEPARATOR, $parts));
define('TV_SITE', TV_ROOT);
define('TV_CONFIGURATION', TV_ROOT);
define('TV_INCLUDES', TV_ROOT . DIRECTORY_SEPARATOR . 'includes');
define('TV_MODELS', TV_ROOT . DIRECTORY_SEPARATOR . 'models');
define('TV_STATIC', TV_ROOT . DIRECTORY_SEPARATOR . 'static');
define('TV_THEMES', TV_BASE . DIRECTORY_SEPARATOR . 'templates');
define('TV_IPTV', TV_BASE . DIRECTORY_SEPARATOR . 'iptv');
define('TV_EPG', TV_BASE . DIRECTORY_SEPARATOR . 'epg');
define('TV_EPG_QUEUE', TV_EPG . DIRECTORY_SEPARATOR . 'queue');
define('TV_EPG_SAVED', TV_EPG . DIRECTORY_SEPARATOR . 'saved');
define('TV_EPG_EXPIRED', TV_EPG . DIRECTORY_SEPARATOR . 'expired');
define('TV_EPG_SNAPSHOTS', TV_EPG . DIRECTORY_SEPARATOR . 'snapshots');
define('TV_CACHE', TV_BASE . DIRECTORY_SEPARATOR . 'cache');
define('TV_CACHE_CHANNELS', TV_CACHE . DIRECTORY_SEPARATOR . 'channels');
define('TV_CACHE_STATIONS', TV_CACHE . DIRECTORY_SEPARATOR . 'stations');
define('TV_RADIO', TV_CACHE . DIRECTORY_SEPARATOR . 'radio');
define('TV_SEF', TV_BASE . DIRECTORY_SEPARATOR . 'sef');

define('ERR_NONE', 0);
define('ERR_INVALID_TOKEN', 500);

define('ERR_INVALID_TASK', 1);
define('ERR_INVALID_FOLDEE', 2);
define('ERR_INVALID_SOURCE', 3);
define('ERR_INVALID_ITEM', 4);

define('ERR_IMPORT_NONE', 100);
define('ERR_IMPORT_EMPTY_FIELD', 101);
define('ERR_IMPORT_INVALID_URL', 102);
define('ERR_IMPORT_INVALID_FILE', 103);
define('ERR_IMPORT_ANY', 104);

define('ERR_INVALID_LISTNAME', 105);
define('ERR_MISSING_LISTNAME', 106);
define('ERR_MAX_LISTS_REACHED', 107);

define('TV_BLANK','data:image/gif;base64,R0lGODlhAQABAAAAACH5BAEKAAEALAAAAAABAAEAAAICTAEAOw==');
define('IV_KEY', '8w)kz^r71Z^V]*X');

