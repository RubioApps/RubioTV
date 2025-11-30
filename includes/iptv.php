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

use RubioTV\Framework\Factory;

class IPTV{        

    protected static $root = 'https://iptv-org.github.io/iptv';    
    protected static $api = 'https://iptv-org.github.io/api';
    protected static $url;


    /**
     * getAPI
     * Provides the API from iptv-org
     */
    public static function getAPI()
    {
        return static::$api;        
    }

    public static function getURL()
    {
        return static::$url;        
    }

    /**
     * getURL
     * Provides the remote sources from iptv-org
     */
    public static function getSource($folder , $source)
    {
        static::$url = static::$root . '/' . $folder . '/' . $source . '.m3u';        
        return static::$url;
    }
    /**
     * getChannels
     * Provides an associative array of available channels
     * This function uses the iptv-org API
     */
    public static function getChannels()
    {
        static::$url = static::$api . '/channels.json';
        $content = self::url_get_content();   
        return json_decode($content);               
    }        

    public static function getStreams()
    {
        static::$url = static::$api . '/streams.json';
        $content = self::url_get_content();      
        return json_decode($content);   
    }
        
    /**
     * getCountries
     * Provides an associative array of available countries
     * This function uses the iptv-org API
     */
    public static function getCountries()
    {
        if(!file_exists(TV_IPTV . DIRECTORY_SEPARATOR . 'countries'))
            mkdir (TV_IPTV . DIRECTORY_SEPARATOR . 'countries');

        static::$url =  static::$api . '/countries.json';
        $content = self::url_get_content();        
        return json_decode($content);  
    }

    /**
     * getCountries
     * Provides an associative array of available categories
     * This function uses the iptv-org API
     */
    public static function getCategories()
    {
        if(!file_exists(TV_IPTV . DIRECTORY_SEPARATOR . 'categories'))
            mkdir (TV_IPTV . DIRECTORY_SEPARATOR . 'categories');

        static::$url =  static::$api . '/categories.json';
        $content = self::url_get_content();           
        return json_decode($content);                
    }    

    /**
     * getLanguages
     * Provides an associative array of available languages
     * This function uses the iptv-org API
     */    
    public static function getLanguages()
    {
        if(!file_exists(TV_IPTV . DIRECTORY_SEPARATOR . 'languages'))
            mkdir (TV_IPTV . DIRECTORY_SEPARATOR . 'languages');

        static::$url =  static::$api . '/languages.json';
        $content = self::url_get_content();     
        return json_decode($content);    
    }  

    /**
     * getGuides
     * Provides an associative array of available guides
     * This function uses the iptv-org API
     */
    public static function getGuides()
    {
        static::$url =  static::$api . '/guides.json';
        $content = self::url_get_content();    
        return json_decode($content);             
    }

    /**
     * getIso
     * Provides an associative array of available ISO-639 table
     * This function uses a static file
     */
    public static function getISO()
    {
        static::$url =  TV_STATIC . DIRECTORY_SEPARATOR . 'iso-639.json';
        $content = self::url_get_content();   
        return json_decode($content);                
    }    

    /**
     * Get a remote content
     * 
     * @param $uri Remote URL
     */
    private static function url_get_content()
    {
        if(!static::$url) die();
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, static::$url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        $content = curl_exec($curl);
        if (!curl_errno($curl)) {
            $error = null;
        } else {
            $error = curl_error($curl);
        }  
        unset($curl);
        if($error) die($error);
        return $content;         
    }        
}

