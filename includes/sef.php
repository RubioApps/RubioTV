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

use RubioTV\Framework\Factory; 

class SEF{    
    protected static $task = '';
    protected static $folder = '';
    protected static $source = '';    
    protected static $id = '';  
    protected static $data = [];

    public static function _($uri)
    {
        $config = Factory::getConfig();

        if(!$config->use_sef)
            return $uri;

        $query = parse_url($uri , PHP_URL_QUERY);        
        parse_str($query, $array); 
        
        $fields = ['task','folder','source','id'];
        $trail  = [];
        $query  = [];

        $pathway = [];
        foreach($array as $key => $value)
        {
            if(in_array($key , $fields) && strlen($value) > 0)
            {                                           
                // If there is an alias in the current segment value
                if(strstr( $value , ':') !== false)
                {                    
                    $parts       = explode(':', $value);
                    $code        = $parts[0];                
                    $alias       = $parts[1];

                    // Save the SEF or roll-back                        
                    if (!self::save( join('.',$pathway) , $code , $alias))
                    {
                        $code   = $value;
                        $alias  = join(':', $parts);                   
                    }                    
                } else {
                    $code   = $value;
                    $alias  = $value;         
                }
                $trail[$key]    =  $alias;    

            } else {
                if($value)
                    $query[$key] = $key .'='. $value;             
            }

            // Remove the task from the pathway
            if($key !== 'task') 
                $pathway[] = $code;

            $pathway = array_unique($pathway);
        }

        // Cosmetic: we put the first trail at the end
        if(count($trail)>1)
        {
            $task = array_shift($trail);            
            $trail[] = $task;
        }        

        $ret  = $config->live_site;
        if(count($trail))
            $ret .= '/' . join('/',$trail);

        if(count($query))
            $ret .= '?' . join('&',$query);

        return $ret;
    }


    public static function parseURI(&$params)
    {                     
        $config = Factory::getConfig();

        // Get the current URI
        $proto  = (empty($_SERVER['HTTPS']) ? 'http' : 'https');
        $host   = $_SERVER['HTTP_HOST'] ?? $config->live_site;
        $uri    = $_SERVER['REQUEST_URI'] ?? null;
        $link   = $proto . '://' . $host . $uri;        

        // Get the trail
        $trail = explode('/',parse_url($link,PHP_URL_PATH));           

        // Detect if the web-app runs in a subfolder
        $start = count(explode('/',parse_url($config->live_site,PHP_URL_PATH)));

        // Slice the trail by the starting point
        $trail = array_slice($trail , $start);        

        //Cosmetic: put the last trail as the first
        if(count($trail)>0)
        {            
            $task = $trail[count($trail) - 1];
            $trail = array_slice($trail, 0 , count($trail) - 1);
            array_unshift($trail , $task);
        } else {
            $task = 'home';
        }        

        // Default trails
        $props = ['task','folder','source','id'];
        
        // Hold the cumulative pathway
        $pathway = [];

        for( $i = 0 ; $config->use_sef && $i < count($trail) && $task !== 'home'; $i++ )
        {
            if($i > count($props))
                break;

            // Read across the parts of the path
            $key = $props[$i];                        

            // Find the value
            $value = self::find( join('.' , $pathway) , $trail[$i]);   

            // For other trails than the task, store it in the pathway
            if($key !== 'task')
                $pathway[] = $value;                                     

            // If the parameter is not yet set, create it
            if(empty($params[$key]['value']))
                Factory::setParam($key , null , 'string');  

            // Store the value
            static::$$key = $value; 
            $params[$key]['value'] = $value;     
        }       
        // Save the task in the factory.
        Factory::setTask($task);                                
    }

    public static function get()
    {
        return self::$data;
    }

    public static function encode ( $string)
    {        
        $encoding = mb_detect_encoding($string);
        $string = mb_convert_encoding($string,'UTF-8',$encoding);          

        $string = htmlentities($string, ENT_NOQUOTES, 'UTF-8');
        $string = preg_replace('#&([A-za-z])(?:acute|cedil|caron|circ|grave|orn|ring|slash|th|tilde|uml);#', '\1', $string);
        $string = preg_replace('#&([A-za-z]{2})(?:lig);#', '\1', $string); 
        $string = preg_replace('#&[^;]+;#', '', $string); 
        $string = preg_replace('/[\s\%]+/','-', $string);           

        return strtolower($string);
    }

    public static function decode ( $string)
    {        
        $encoding = mb_detect_encoding($string);
        $string = mb_convert_encoding($string,'UTF-8',$encoding);                   
        $array= preg_split('/[\s,\-]+/',$string);     
        $array = array_map('ucfirst' , $array);
        return join(' ',$array);
    }

    public static function alias( &$trail = null )
    {
        if(!empty($trail))
        {
            if(strstr($trail,':') !== false)
            {
                $parts = explode(':' , $trail );
                $trail = $parts[0];
                return $parts[1];
            }
            return $trail;
        }
        return null;
    }   
      
    public static function save( $pathway , $id , $alias )
    {
        if(empty($id) || empty($alias) || empty($pathway))
            return false;  

        // Ensure the source is already been parsed
        if(strstr($pathway , ':') !== false)
            return false;
   
        $filepath   = TV_SEF . DIRECTORY_SEPARATOR . $pathway . '.xml';   

        if(!isset(self::$data[$pathway]))
            self::$data[$pathway] = [];
            
        // Return the saved value if exists
        if(isset(self::$data[$pathway][$alias]))            
            return true;   

        // Load file
        if(file_exists($filepath))   
            $xml    = simplexml_load_file($filepath);
        else
            $xml    = simplexml_load_string('<?xml version="1.0" encoding="UTF-8" ?><sef></sef>');

        // Create or update the alias        
        $query = $xml->xpath('/sef/node[@alias="' . $alias . '"]');
        if(!count($query))
        {
            $root = $xml->xpath('/sef');
            $node = $root[0]->addChild('node' ,  strtolower($id));
            $node->addAttribute('alias', $alias);                 
        }
        // Store the new alias
        self::$data[$pathway][$alias] = strtolower($id);    
        // Save and exit
        return $xml->asXML($filepath);
    }


    public static function find( $pathway , $alias)
    {             
        $alias = urldecode($alias);

        $filepath   = TV_SEF . DIRECTORY_SEPARATOR . $pathway . '.xml';            

        if(!isset(self::$data[$pathway]))
            self::$data[$pathway] = [];     

        // Return the saved value if exists
        if(isset(self::$data[$pathway][$alias]))            
            return self::$data[$pathway][$alias];                
                
        if(!file_exists($filepath))  
            return $alias;   
                                                                 
        $xml    = simplexml_load_file($filepath);    
        $query  = $xml->xpath('/sef/node[@alias="' . $alias . '"]');        
        
        if(!$query || !count($query))
            $value = $alias;
        else
            $value = $query[0]->__toString();
        
        self::$data[$pathway][$alias] = $value;         
        
        return $value;
    }   

    public static function rfind( $pathway , $id)
    {             
        $filepath   = TV_SEF . DIRECTORY_SEPARATOR . $pathway . '.xml';

        if(!isset(self::$data[$pathway]))
            self::$data[$pathway] = [];

        // Load the existing array
        $array = self::$data[$pathway];

        if(!file_exists($filepath))  
            return $id;                   
        
        $alias = array_search( $id , $array , true);                     
        if(!$alias)
        {           
            $xml    = simplexml_load_file($filepath);    
            $nodes  = $xml->xpath('/sef/node');        
            $alias  = $id;

            if($nodes && count($nodes))
            {
                foreach($nodes as $node)
                {                                           
                    if($id === $node[0]->__toString())
                    {                                               
                        $alias = $node->attributes()->alias;  
                        break;        
                    }
                }
            }            
        } 
        return $alias;
    }    
        
}

