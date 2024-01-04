<?php
namespace RubioTV\Framework;

defined('_TVEXEC') or die;

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
        $content=file_get_contents(static::$url);
        return json_decode($content);                
    }        
        
    /**
     * getCountries
     * Provides an associative array of available countries
     * This function uses the iptv-org API
     */
    public static function getCountries()
    {
        static::$url =  static::$api . '/countries.json';
        $content=file_get_contents(static::$url);
        return json_decode($content);                
    }

    /**
     * getCountries
     * Provides an associative array of available categories
     * This function uses the iptv-org API
     */
    public static function getCategories()
    {
        static::$url =  static::$api . '/categories.json';
        $content=file_get_contents(static::$url);
        return json_decode($content);                
    }    

    /**
     * getLanguages
     * Provides an associative array of available languages
     * This function uses the iptv-org API
     */    
    public static function getLanguages()
    {
        static::$url =  static::$api . '/languages.json';
        $content=file_get_contents(static::$url);
        return json_decode($content);                
    }  

    public static function getGuides()
    {
        static::$url =  static::$api . '/guides.json';
        $content=file_get_contents(static::$url);
        return json_decode($content);            
    }
        
}

