<?php

/**
 +-------------------------------------------------------------------------+
 | RubioTV  - A domestic IPTV Web app browser                              |
 | Version 1.6.1                                                           |
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

namespace RubioTV\Framework;

defined('_TVEXEC') or die;

// Pre-Load configuration. Don't remove the Output Buffering due to BOM issues
ob_start();
require_once TV_CONFIGURATION . '/configuration.php';
ob_end_clean();

// Load the include needed classes
if ($folder = opendir(TV_INCLUDES)) {
    while (false !== ($file = readdir($folder))) {
        if ($file != "." && $file != ".." && $file != 'factory.php') {
            require_once TV_INCLUDES . DIRECTORY_SEPARATOR . $file;
        }
    }
    closedir($folder);
}

if(file_exists(TV_ROOT . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php')){
    require_once TV_ROOT . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';
}

class Factory
{
    protected static $startTime;
    protected static $language;
    protected static $locale;
    protected static $config;
    protected static $prefs;
    protected static $params;
    protected static $assets;
    protected static $task;
    protected static $action;
    protected static $router;
    protected static $theme;
    protected static $page;

    public function __construct()
    {
        static::$startTime  = microtime(1);
        static::$config     = new TVConfig();

        // Set error log        
        ini_set('display_errors', '0');
        ini_set('display_startup_errors', '0');
        ini_set("error_log",  static::$config->log_path . DIRECTORY_SEPARATOR . 'php.log');
        error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED  );        

        //Build paths
        if(!file_exists(TV_IPTV)) mkdir(TV_IPTV);

        if(!file_exists(TV_CACHE)) mkdir(TV_CACHE);
        if(!file_exists(TV_CACHE_CHANNELS)) mkdir(TV_CACHE_CHANNELS);
        if(!file_exists(TV_CACHE_STATIONS)) mkdir(TV_CACHE_STATIONS);
        if(!file_exists(TV_CACHE_SNAPSHOTS)) mkdir(TV_CACHE_SNAPSHOTS); 

        if(!file_exists(TV_EPG)) mkdir(TV_EPG);
        if(!file_exists(TV_EPG_QUEUE)) mkdir(TV_EPG_QUEUE);
        if(!file_exists(TV_EPG_SAVED)) mkdir(TV_EPG_SAVED);
        if(!file_exists(TV_EPG_EXPIRED)) mkdir(TV_EPG_EXPIRED);
               
 
        // Restore the preferences from the cookies
        self::_restorePrefs();

        // Get the task
        static::$task = self::getTask();

        // Get parameters
        static::$params = self::getParams();

        // Parse config to the SEF engine
        SEF::parseURI();

        // Save the preferences
        self::savePrefs();
    }

    public function __destruct() {}

    public static function getConfig()
    {
        if (!static::$config) {
            static::$config = new TVConfig();
        }
        return static::$config;
    }

    public static function initialize()
    {        
        // Start session
        ini_set('max_execution_time', '1800');
        ini_set('session.gc_maxlifetime', 86400); 
        ini_set('session.cookie_lifetime', 86400);
        session_start([
            'name'              => '__Host_sid',
            'cookie_path'       => parse_url(static::$config->live_site,PHP_URL_PATH),
            'cookie_secure'     => true,
            'cookie_httponly'   => true,
            'cookie_samesite'   => 'none'
        ]);
        
        // Start output buffer
        ob_start();
        
        // Set header
        header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
        header("Cache-Control: post-check=0, pre-check=0", false);
        header("Pragma: no-cache");
        header('Expires: '.gmdate('D, d M Y H:i:s \G\M\T', time() + 3600));
    }  
    
    public static function finalize()
    {
        ob_end_flush();
        session_write_close();        
        die();        
    }

    public static function isLogged()
    {
        if (isset($_SESSION['utoken']) || self::autoLogged()) {
            if ($_SESSION['utoken'] === md5(session_id() . static::$config->password))
                return true;
        }
        return false;
    }

    public static function autoLogged()
    {
        if (!static::$config->use_autolog)
            return false;

        // Get client ip address
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'] ?? null;
        }

        // Check the local ip address
        if ($ip && ! filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            $_SESSION['utoken'] = md5(session_id() . static::$config->password);
            return true;
        }
        return false;
    }

    public static function isAjax()
    {
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strcasecmp($_SERVER['HTTP_X_REQUESTED_WITH'], 'xmlhttprequest') == 0) {
            return true;
        }
        return false;
    }

    protected static function _defPreference($name, $default, $type)
    {
        if (!is_array(static::$prefs)) {
            static::$prefs = [];
        }

        $key = strtolower($name);

        $array = [];
        $array['default'] = $default;
        $array['type'] = $type;
        $array['value'] = $default;

        static::$prefs[$key] = $array;

        return static::$prefs[$key];
    }

    protected static function _getPreference($name)
    {
        if (!is_array(static::$prefs)) {
            static::$prefs = [];
        }

        if (!isset(static::$prefs[$name]))
            return null;

        return static::$prefs[$name]['value'] ?? static::$prefs[$name]['default'];
    }

    protected static function _setPreference($name, $value)
    {
        if (!isset(static::$prefs[$name])) {
            self::_defPreference($name, null, 'string');
        }

        static::$prefs[$name]['value'] = $value;
        return static::$prefs[$name];
    }

    protected static function _restorePrefs()
    {
        // Get prefs from saved cookies
        $cookie = filter_input(INPUT_COOKIE, '__Host_prefs');
        if ($cookie) {
            static::$prefs = json_decode(self::_decrypt($cookie), true);
        } else {
            static::$prefs = [];
        }

        if (!count(static::$prefs)) {

            // Basic pref mode
            if (empty(static::$prefs['mode']))
                self::_defPreference('mode', 'light', 'string');

            // Basic pref limit
            if (empty(static::$prefs['limit']))
                self::_defPreference('limit', static::$config->list_limit, 'int');

            // Basic pref term
            if (empty(static::$prefs['term']))
                self::_defPreference('term', '', 'string');
        }

        // Override the preferences with the query  
        foreach (Request::get('GET') as $k => $v) {
            if (isset(static::$prefs[$k]))
                self::_setPreference($k, $v);
        }

        // Override the query if the preference was not in the query               
        foreach (static::$prefs as $k => $p) {
            if (!array_key_exists($k, Request::get('GET'))) {
                if (isset($p['value']))
                    Request::setVar($k, $p['value'], 'GET');
                else
                    Request::setVar($k, $p['default'], 'GET');
            }
            static::$params[$k] = Request::getVar($k, $p['default'], 'GET');
        }        
    }

    public static function savePrefs()
    {
        // Store the prefs only if logged                
        if (self::isLogged())
        {
            setcookie('__Host_prefs', self::_encrypt(json_encode(static::$prefs)), [
                'path'       => parse_url(static::$config->live_site, PHP_URL_PATH),
                'secure'     => true,
                'httponly'   => true,
                'samesite'   => 'None',
                'expires'    => static::$startTime + 86400,
            ]);                        
        }
    }

    public static function changeTheme($mode = 'dark')
    {        
        self::_setPreference('mode', $mode);
        self::savePrefs();
    }

    public static function getParams()
    {
        if (empty(static::$params))
            static::$params = [];

        // Add basic parameters
        static::$params['task'] = 'home';
        static::$params['folder'] = null;
        static::$params['source'] = null;
        static::$params['id'] = null;

        // Add query            
        $query = Request::get('GET');
        foreach ($query as $k => $v)
            static::$params[$k] = $v;

        // Add config
        static::$params['config'] = static::$config;

        return static::$params;
    }

    public static function saveParams()
    {
        $query = Request::get('GET');
        foreach ($query as $k => $p) {
            if ($p != '') static::$params[$k] = $p;
        }
    }

    public static function getLanguage($lang = null)
    {
        if (!static::$language) {
            static::$language = new Language($lang);
        }

        return static::$language;
    }

    public static function getLangTag()
    {
        if (!static::$locale) {
            $language = self::getLanguage();
            static::$locale = $language->getTag();
        }
        return static::$locale;
    }

    public static function getAssets()
    {
        if (!static::$assets) {
            $config = self::getConfig();
            static::$assets = $config->live_site . '/templates/' . $config->theme . '/assets';
        }
        return static::$assets;
    }

    public static function getTheme()
    {
        if (!static::$theme) {
            $config = self::getConfig();
            static::$theme = TV_THEMES . DIRECTORY_SEPARATOR . $config->theme;
        }
        return static::$theme;
    }

    public static function getTask()
    {
        if (!empty(static::$task))
            return static::$task;

        $task = Request::getVar('task', 'home', 'GET');

        if (strstr($task, '.') !== false) {
            $parts  = explode('.', $task);
            $task   = array_shift($parts);
            static::$action =  join('.', $parts);
            Request::setVar('action', static::$action, 'GET');
        }
        static::$task = $task;
        Request::setVar('task', static::$task, 'GET');
        return static::$task;
    }

    public static function getAction()
    {
        if (!static::$action)
            static::$action = Request::getVar('action', 'display', 'GET');

        if (strstr(static::$task, '.')) {
            $parts = explode('.', static::$task);
            static::$task = array_shift($parts);
            static::$action =  join('.', $parts);
        }
        return static::$action;
    }

    public static function setTask($task = null)
    {
        if (empty($task) || $task === null)
            $task = 'home';

        if (strstr($task, '.') !== false) {
            $parts  = explode('.', $task);
            $task   = array_shift($parts);
            static::$action =  join('.', $parts);
            Request::setVar('action', static::$action, 'GET');
        }

        static::$task = $task;
        Request::setVar('task', static::$task, 'GET');

        return static::$task;
    }

    public static function getRouter($task = null)
    {
        if (!$task) {
            $task = self::getTask();
        }

        if (!static::$router) {
            static::$router = new Router(static::$params);
        }

        return static::$router;
    }

    public static function getModel($name)
    {
        if (file_exists(TV_MODELS . DIRECTORY_SEPARATOR . strtolower($name) . '.php')) {
            require_once(TV_MODELS . DIRECTORY_SEPARATOR . strtolower($name) . '.php');

            $classname  = '\RubioTV\Framework\model' . ucfirst($name);

            if (class_exists($classname))
                return new $classname(self::$params);
        } else {
            return false;
        }
    }

    public static function getPage($pagename = null)
    {
        if (!static::$page) {
            static::$page = new Page(static::$params);
        }

        return static::$page;
    }

    public static function Link()
    {
        if (!func_num_args())
            return self::Link('home');

        $props      = ['task', 'folder', 'source', 'id'];
        $ret        = static::$config->live_site  . '/';

        // For the protected parameters ($props)
        for ($counter = 0; $counter <  func_num_args(); $counter++) {
            if ($counter >= count($props))
                break;

            if (func_get_arg($counter)) {
                $key    = $props[$counter];
                $value  = func_get_arg($counter);

                $ret .= $counter > 0 ? '&' : '?';
                $ret .= $key . '=' . SEF::encode($value);
            } else
                break;
        }

        // For the rest of the arguments
        for ($i = $counter; $i <  func_num_args(); $i++) {
            if (func_get_arg($i)) {
                $ret .= $ret !== '' ? '&' : '?';
                $ret .= func_get_arg($i);
            }
        }
                
        // Return SEF or plain
        return SEF::_($ret);
    }

    protected static function _encrypt($string)
    {
        $method = 'AES-256-CBC';
        $key    = hash('sha256', static::$config->key);
        $iv     = substr(hash('sha256', IV_KEY), 0, 16);

        return base64_encode(openssl_encrypt($string, $method, $key, 0, $iv)) ?? null;
    }

    protected static function _decrypt($string)
    {
        $method = 'AES-256-CBC';
        $key    = hash('sha256', static::$config->key);
        $iv     = substr(hash('sha256', IV_KEY), 0, 16);

        return openssl_decrypt(base64_decode($string), $method, $key, 0, $iv);
    }

    public static function RemoveDir($dir)
    {
        if (!file_exists($dir)) {
            return true;
        }
        if (!is_dir($dir)) {
            return unlink($dir);
        }
        foreach (scandir($dir) as $item) {
            if ($item == '.' || $item == '..') {
                continue;
            }
            if (!self::RemoveDir($dir . DIRECTORY_SEPARATOR . $item)) {
                return false;
            }
        }
        return rmdir($dir);
    }    

    /**
     * Serve the labels for Javascript framework
     */
    public static function jsBridge()
    {
        $task = self::getTask();

        switch ($task) {
            case 'labels':
                $source = static::$language->getStrings();
                break;
            case 'keepalive':
                $source = ['success' => self::isLogged(), 'message' => 'logged in'];
                //Check the hartbeat of any watch: destroy any PID older than 30s.
                $files = glob(TV_CACHE_HLS . DIRECTORY_SEPARATOR . '*/lastview');
                foreach($files as $file)
                {
                    $lastview = (int) @file_get_contents($file);
                    if(time() - $lastview > 30){
                        $parts = pathinfo($file);
                        $dir = $parts['dirname'];
                        if(file_exists($dir . DIRECTORY_SEPARATOR . 'pid'))
                        {
                            $pid = @file_get_contents($dir . DIRECTORY_SEPARATOR . 'pid');
                            exec("kill $pid 2>/dev/null");
                        } 
                        self::RemoveDir($dir);
                    }
                }
                break;
            case 'theme':
                $mode = Request::getVar('mode', 'dark', 'GET');
                self::changeTheme($mode);
                $source = ['success' => true, 'message' => $mode];
                break;
            case 'token':
                $tokenName = Request::getVar('name', 'token', 'GET');
                $source = self::getToken($tokenName, true);
                break;
            default:
                return;
        }
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($source, JSON_UNESCAPED_SLASHES);
        exit(0);
    }

    public static function getToken($tokenName = null, $raw = false)
    {
        if (empty($tokenName)) $tokenName = 'token';

        $method = 'AES-256-CBC';
        $key    = hash('sha256', static::$config->key);
        $iv     = substr(hash('sha256', IV_KEY), 0, 16);

        $token = substr(bin2hex(openssl_random_pseudo_bytes(16)), 0, 32);
        $_SESSION[$tokenName] = $token;

        $value = base64_encode(openssl_encrypt($token, $method, $key, 0, $iv)) ?? null;
        if ($raw) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([$tokenName => $value, 'sid' => session_id()]);
            exit(0);
        }
        return '<input type="hidden" name="' . $value . '" id="' . $tokenName . '" value="' . session_id() . '" />';
    }

    public static function checkToken($tokenName = null)
    {
        if (empty($tokenName)) $tokenName = 'token';

        $method = 'AES-256-CBC';
        $key    = hash('sha256', static::$config->key);
        $iv     = substr(hash('sha256', IV_KEY), 0, 16);

        $id     =  $_SESSION[$tokenName] ?: null;
        unset($_SESSION[$tokenName]);

        foreach ($_POST as $k => $v) {
            $token  = openssl_decrypt(base64_decode($k), $method, $key, 0, $iv);
            if ($id == $token && $v == session_id()) {
                return true;
            }
        }
        return false;
    }
}
