<?php

defined('_TVEXEC') or die;

// Global definitions
$parts = explode(DIRECTORY_SEPARATOR, TV_BASE);

// Defines.
define('TV_ROOT', implode(DIRECTORY_SEPARATOR, $parts));
define('TV_SITE', TV_ROOT);
define('TV_INCLUDES', TV_ROOT . DIRECTORY_SEPARATOR . 'includes');
define('TV_STATIC', TV_ROOT . DIRECTORY_SEPARATOR . 'static');
define('TV_CONFIGURATION', TV_ROOT);
define('TV_THEMES', TV_BASE . DIRECTORY_SEPARATOR . 'templates');
define('TV_IPTV', TV_BASE . DIRECTORY_SEPARATOR . 'iptv');
define('TV_EPG', TV_BASE . DIRECTORY_SEPARATOR . 'guides');
define('TV_EPG_QUEUE', TV_EPG . DIRECTORY_SEPARATOR . 'queue');
define('TV_EPG_SAVED', TV_EPG . DIRECTORY_SEPARATOR . 'saved');
define('TV_CACHE', TV_BASE . DIRECTORY_SEPARATOR . 'cache');

define('ERR_INVALID_TASK', 1);
define('ERR_INVALID_FOLDEE', 2);
define('ERR_INVALID_SOURCE', 3);
define('ERR_INVALID_ITEM', 4);

define('ERR_IMPORT_NONE', 100);
define('ERR_IMPORT_EMPTY_FIELD', 101);
define('ERR_IMPORT_INVALID_URL', 102);
define('ERR_IMPORT_INVALID_FILE', 103);
define('ERR_IMPORT_ANY', 104);

