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
namespace RubioTV\Framework;

defined('_TVEXEC') or die;

// Pre-Load configuration. Don't remove the Output Buffering due to BOM issues  
ob_start();
require_once TV_CONFIGURATION . '/configuration.php';    
ob_end_clean();

require_once TV_INCLUDES . '/language.php';
require_once TV_INCLUDES . '/iptv.php';
require_once TV_INCLUDES . '/epg.php';
require_once TV_INCLUDES . '/m3u.php';
require_once TV_INCLUDES . '/sef.php';
require_once TV_INCLUDES . '/pagination.php';
require_once TV_INCLUDES . '/text.php';
require_once TV_INCLUDES . '/router.php';
require_once TV_INCLUDES . '/model.php';
require_once TV_INCLUDES . '/page.php';
           
class Factory
{
    protected static $language;  
    protected static $locale;
    protected static $config;        
    protected static $params;        
    protected static $assets;
    protected static $task;
    protected static $router;   
    protected static $theme;     
    protected static $page;    
    
    public function __construct()
    {        
        static::$config = new TVConfig();
        
        // Restore the params from the cookies
        self::_restoreParams();

        // Parse config to the SEF engine
        SEF::parseURI(static::$params);             

    }

    public function __destruct() {
        //print "Destroying " . __CLASS__ . "\n";
    }    

    public static function getConfig()
    {
        if(!static::$config){  
            static::$config = new TVConfig();
        }                        
        return static::$config;        
    }   

    public static function isLogged()
    {
        if(isset($_SESSION['sid']) || self::autoLogged())
        {
            if($_SESSION['sid'] === md5(session_id() . static::$config->password))
                return true;
        }
        return false;
    }

    public static function autoLogged()
    {
        if(!static::$config->use_autolog)
            return false;

        // Get client ip address
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }

        // Check the local ip address
        if ( ! filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) )
        {
            $_SESSION['sid'] = md5(session_id() . static::$config->password);
            return true;
        }      
        return false;  
    }
       
    public static function setParam( $name , $default , $type)
    {               
        if(!is_array(static::$params)){
            static::$params = array();
        }
        
        $key = strtolower($name);
        if(!isset(static::$params[$key]))
        {
            $array = array();
            $array['default'] = $default;   
            $array['type'] = $type;
            $array['value'] = null;

            static::$params[$key] = $array;

            self::_saveParams();
        }
        return static::$params[$key];
    }

    public static function getParam( $name )
    {        
        if(!is_array(static::$params)){
            static::$params = array();
        }
                
        if(!isset(static::$params[$name])){
            return null;
        }
        return static::$params[$name]['value'] ?? static::$params[$name]['default'];
    }       

    protected static function _restoreParams()
    {            
        $cookie = filter_input(INPUT_COOKIE,'__rtv');
        if($cookie){            
            static::$params = json_decode($cookie , true); 
        } else {
            static::$params = array();
        }
        static::$params = array();
        if(!count(static::$params))
        {
            // Basic param task
            if(empty(static::$params['task']))
                self::setParam('task','home','string');                               

            // Basic param offset
            if(empty(static::$params['offset']))                
                self::setParam('offset', 0 ,'int');

            // Basic param limit
            if(empty(static::$params['limit']))
                self::setParam('limit', static::$config->list_limit, 'int');                      
            
            // Valid params in the query string
            self::setParam('folder', null , 'string');                   
            self::setParam('source', null , 'string');                   
            self::setParam('id', null , 'string'); 
            self::setParam('format', null ,'string');                   
            self::setParam('term', null ,'string');

            // Add config             
            static::$params['config'] = self::$config;                    
        }      
        
    }    
            
    protected static function _saveParams()
    {
        if(!is_array(static::$params)){
            static::$params = array();
        }
        
        // SAFETY: Exclude the config from the saved params
        if(isset(static::$params['config'])){
            unset(static::$params['config']); 
        }

        foreach(static::$params as $k=>$array)
        {   
            if(!(static::$params[$k]['value'] = filter_input(INPUT_GET, $k)))
            {
                switch (static::$params[$k]['type'])
               {
                    case 'int':
                        static::$params[$k]['value'] = (int) $array['default'];
                        break;
                    case 'string':
                    default:                                                    
                        static::$params[$k]['value'] = $array['default'];
                        break;                                     
                }
            }
        }            
    
        setcookie("__rubiotv", json_encode(static::$params), 86400 , '/');

        // Reload the config
        static::$params['config'] = self::$config; 
        
    }    
    
    public static function getLanguage( $lang = null )
    {
        if(!static::$language){                     
            static::$language= new Language($lang);
        }                
        
        return static::$language;        
    } 
    
    public static function getLangTag()
    {
        if(!static::$locale){ 
            $language = self::getLanguage();
            static::$locale = $language->getTag();
        }
        return static::$locale;
    }

    public static function getAssets()
    {        
        if(!static::$assets){  
            $config = self::getConfig();         
            static::$assets = $config->live_site . '/templates/' . $config->theme . '/assets';
        }                        
        return static::$assets;        
    }      

    public static function getTheme()
    {        
        if(!static::$theme){  
            $config = self::getConfig();         
            static::$theme = TV_THEMES . DIRECTORY_SEPARATOR . $config->theme;
        }                        
        return static::$theme;        
    }        

    public static function getTask()
    {
        static::$task = self::getParam('task');           
        if(!static::$task){                 
            static::$task = 'home';            
        }
        $action = explode('.' , static::$task );                
        if(count($action)>1)
        {        
            static::$task = $action[0];
            array_shift($action); 
            static::$params['action']['value'] =  join('.', $action);                                  
        }                         
        return static::$task;
    }

    public static function setTask( $task = null) 
    {
        if(empty($task) || $task === null)
            static::$task = 'home';

        static::$task = $task;           
        $action = explode('.',static::$task );
        if(count($action)>1){

            static::$task = $action[0];
            static::$params['task']['value'] = static::$task;

            array_shift($action);
            static::$params['action']['value'] =  join('.', $action);        
        }      
        
        return static::$task;
    }    
    
    public static function getRouter( $task = null)
    {
        if(!$task){
            $task = self::getTask();
        }

        if(!static::$router){             
            static::$router= new Router(static::$params);
        }                  
        
        return static::$router;        
    } 

    public static function getPage( $pagename = null)
    {                
        if(!static::$page){             
            static::$page= new Page();
        }    
        
        foreach(static::$params as $key => $p)
        {
            if(is_object($p))
                static::$page->$key = $p;  
            else
                static::$page->$key = $p['value'];  
        }
        
        return static::$page;   
    }

    public static function Link()
    {
        if(!func_num_args())
            return static::$config->live_site;                   
                 
        $props      = ['task','folder','source','id'];
        $ret        = static::$config->live_site  . '/';     

        // For the protected parameters ($props)
        for($counter = 0; $counter <  func_num_args(); $counter++)
        {       
            if($counter >= count($props))
                break;

            if(func_get_arg($counter))
            {                
                $key    = $props[$counter];
                $value  = func_get_arg($counter);    
                
                $ret .= $counter > 0 ? '&' : '?';   
                $ret .= $key . '='. SEF::encode($value);
            } else
                break;
        }

        // For the rest of the arguments
        for($i = $counter ; $i <  func_num_args(); $i++)
        {
            if(func_get_arg($i))
            {   
                $ret .= $ret !=='' ? '&' : '?';  
                $ret .= func_get_arg($i);  
            }
        }    
        
        // Return SEF or plain
        return SEF::_($ret);
    }    

    public static function getToken( $raw = false)
    {
        $method = 'AES-256-CBC';                
        $key    = hash('sha256', static::$config->key);         
        $iv     = substr(hash('sha256', IV_KEY ), 0, 16);        

        $token = substr(bin2hex(openssl_random_pseudo_bytes(16)), 0, 32);          
        $_SESSION['_token'] = $token;

        $value = base64_encode(openssl_encrypt($token, $method, $key, 0, $iv)) ?? null;
        if(!$raw)
        {
            return '<input type="hidden" name="' . $value . '" id="token" value="' . session_id() . '" />';
        } else {
            header('Content-Type: application/json; charset=utf-8');   
            echo json_encode(['token' => $value , 'sid' => session_id()]);
            exit(0);
        }
    }

    public static function checkToken()
    {                
        $method = 'AES-256-CBC';                
        $key    = hash('sha256', static::$config->key);         
        $iv     = substr(hash('sha256', IV_KEY ), 0, 16);

        $id     =  $_SESSION['_token'];
        unset($_SESSION['_token']);

        foreach($_POST as $k => $v)
        {           
            $token  = openssl_decrypt(base64_decode($k), $method, $key, 0, $iv); 
            if($id === $token && $v === session_id())
            {
                return true;
            }
        }
        return false;
    }       
       
}

