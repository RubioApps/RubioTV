<?php
/**
 +-------------------------------------------------------------------------+
 | RubioTV  - A domestic IPTV Web app browser                              |
 | Version 1.0.0                                                           |
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
require_once TV_INCLUDES . '/helpers.php';
require_once TV_INCLUDES . '/pagination.php';
require_once TV_INCLUDES . '/text.php';
require_once TV_INCLUDES . '/router.php';
           
class Factory
{
    protected static $language;  
    protected static $locale;
    protected static $config;        
    protected static $params;        
    protected static $assets;
    protected static $task;
    protected static $router;    
    protected static $page;
    protected static $js;    
    protected static $theme;    
    
    public function __construct()
    {        
        static::$config = new TVConfig();
        static::$js     = [];
        self::_restoreParams();
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
        return static::$params[$name]['value'];
    }       

    protected static function _restoreParams()
    {            
        $cookie = filter_input(INPUT_COOKIE,'__rtv');
        if($cookie){            
            static::$params = json_decode($cookie , true); 
        } else {
            static::$params = array();
        }
        
        if(!count(static::$params))
        {
            self::setParam('format','xhtml','string');
            self::setParam('task','home','string');
            self::setParam('folder', null ,'string');
            self::setParam('source', null ,'string');
            self::setParam('id' , null ,'string');
            self::setParam('term', null ,'string');
    
            // Pagination 
            self::setParam('limit', static::$config->list_limit, 'int');
            self::setParam('offset','0','int');            

            // Config             
            static::$params['config'] = self::$config;                    
        }        
    }    
            
    protected static function _saveParams()
    {
        if(!is_array(static::$params)){
            static::$params = array();
        }
        
        // Exclude the config from the saved params
        if(isset(static::$params['config'])){
            unset(static::$params['config']); 
        }

        foreach(static::$params as $k=>$array)
        {   
            if(!(static::$params[$k]['value'] = filter_input(INPUT_GET, $k)))
            {
                switch (static::$params[$k]['value'])
               {
                    case 'int':
                         static::$params[$k]['value'] = (int) $array['default'];
                        break;
                    default:                    
                         static::$params[$k]['value'] = strtolower($array['default']);
                        break;                        
                }
            }
        }                 
        setcookie("__rubiotv", json_encode(static::$params), 86400 , '/tv');

        // Reload the config
        static::$params['config'] = self::$config; 
        
    }    

    public static function addStatic( $type = null)
    {
        $list = [];
        if ($handle = opendir(TV_STATIC))
        {
            while (false !== ($file = readdir($handle)))
            {        
                if ($file != "." && $file != "..")
                {

                    $filepath   = TV_STATIC . DIRECTORY_SEPARATOR . $file; 
                    $extension  = pathinfo($filepath , PATHINFO_EXTENSION); 
                    if(!empty($type) && $type === $extension){
                        $list[$file]= self::addCDN($extension , self::$config->live_site . '/static/' . $file );                    
                    }
                }
            }
            closedir($handle);        
            ksort($list);
        }        
        return join( "\n", $list);
    }

    public static function addCDN ($type , $url, $hash = null, $cors = null)
    {        
        switch($type){
            case 'js':   
                $tag  = 'script';          
                $attr['type']   = 'text/javascript';
                $attr['src']    = $url ;
                $attr['integrity'] = $hash;
                $attr['crossorigin'] = $cors;
                break;
            case 'css':
                $tag = 'link';
                $attr['href'] = $url ;
                $attr['rel'] = 'stylesheet';
                $attr['integrity'] = $hash;
                $attr['crossorigin'] = $cors;                
                break;
            default:
                return;
        }
        $ret = '<' . $tag . ' ';
        foreach($attr as $k => $v){
            if($v !== null)
                $ret .= $k . '=' . $v. ' ';
        }
        $ret .= '></' . $tag . '>';
        return $ret;     
    }

    public static function registerScript( $code )
    {
        self::$js[] = $code;
    }

    public static function getJScripts()
    {        
        $ret = "<script type=\"text/javascript\"> \n";
        $ret .= ";jQuery(document).ready(function(){\n ";
        if(count(self::$js)){
            foreach(self::$js as $code){
                $ret .= $code." \n";
            }
        }
        $ret .= "}); \n";
        $ret .= "</script> \n";
        return $ret;
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
        $action = explode('.',static::$task );
        if(count($action)>1){
            static::$task = array_shift($action);
            self::setParam('action', join('.', $action) , 'string');            
        }                    
        return static::$task;
    }

    public static function setTask( $task = 'home' ) 
    {
        static::$task = $task;           
        $action = explode('.',static::$task );
        if(count($action)>1){
            static::$task = array_shift($action);
            self::setParam('action', join('.', $action) , 'string');            
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
        if(!$pagename)
        {                                              
            if(!static::$page)
            {            
                $filename= ( self::getTheme() . DIRECTORY_SEPARATOR . self::getTask() . '.php');         
                if(file_exists($filename))
                {                
                    static::$page = $filename;
                } else {
                    static::$page = ( self::getTheme() . DIRECTORY_SEPARATOR . '404.php');     
                }            
            }    
            return static::$page;        
        } else {            
            $filename= ( self::getTheme() . DIRECTORY_SEPARATOR . $pagename . '.php');         

            if(file_exists($filename)){     
               return $filename;
            } else {
               return ( self::getTheme() . DIRECTORY_SEPARATOR . '404.php');     
            }
        }
       
    }

    public static function setPage ($pagename)
    {
        static::$page = $pagename;
    }

    public static function getTaskURL( $task = null , $folder = null , $source = null , $id = null)
    {
        $ret = '';
        if(empty($task)) 
            return static::$config->live_site;
        else
            $ret = static::$config->live_site . '/?task=' . $task;

        if(!empty($folder))
            $ret .= '&folder='. $folder;

        if(!empty($source))
            $ret .= '&source=' . $source;

        if(!empty($id))
            $ret .= '&id=' . $id;            

        return $ret;
    }

    public static function sendError( $title , $content)
    {
        self::sendMessage( $title , $content, 'danger');
    }

    
    public static function sendSuccess( $title , $content)
    {
        self::sendMessage( $title , $content , 'success');
    }

    public static function sendMessage( $title , $content, $type = 'info')
    {        
        $notify = "
            var wrapper = $('#tv-toast');
            var toast   = wrapper.find('.toast:first').clone();
            toast.find('.toast-body').html('<b>". addslashes($title) ."</b><br />" . addslashes($content) . "');
            toast.addClass( 'bg-" . $type . "');
            toast.appendTo('body');
            if(bootstrap){
                const tbs = bootstrap.Toast.getOrCreateInstance(toast.get(0));
                tbs.show();
            } else {
                toast.show();
            }
        ";        
        self::registerScript($notify);  
    }

    public static function DoNothing()
    {
        /* Dummy */
    }
    
}

