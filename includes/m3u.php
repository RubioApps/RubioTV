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
use RubioTV\Framework\Language\Text; 

class M3U{    

    protected $folder; 
    protected $source; 
    protected $filename;    
    protected $url;    
    protected $downloaded;   
    protected $content;
    protected $data;

    public function __construct($folder , $source , $url = null)
    {        
        $this->folder = $folder;
        $this->source = $source;
        $this->filename = TV_IPTV . DIRECTORY_SEPARATOR . $this->folder . DIRECTORY_SEPARATOR . $this->source . '.m3u';
        $this->downloaded = false;     

        if(!empty($url))
            $this->url = $url;         
    }

    public function load()
    {                 
        if(!file_exists($this->filename))
        {
            if (!empty($this->url))
            {      
                if(!$this->_download()){                
                    return false; 
                }
            } else {
                $this->_create();                
            }
        }

        // Get the content from the local file
        $this->content = file_get_contents($this->filename);            

        // Parse the content
        $this->data   = $this->_parse($this->content);              

        if(!$this->data)
            return false;
                       
        // At the first pass, replace the original m3u by a standard format one        
        if($this->downloaded)
        {            
            $this->_create();
            $this->_write();  
        }  
    
        $this->content = file_get_contents($this->filename);                 
        $this->downloaded = false;

        return $this->data;
    }

    public function append($item)
    {           
        if(empty($item))
            return false;

        $content = '';

        if(!file_exists($this->filename))
        {                    
            $content = '#EXTM3U' . PHP_EOL;
            file_put_contents($this->filename,$content);
        }

        $content .= '#EXTINF:-1 id="' . $item->id . '" tvg-id="' . $item->tvg_id . '" ' .            
                'tvg-logo="' . $item->logo . '" group-title="' . ($item->group ?? 'Unidentified')  . '", ' . $item->name . PHP_EOL .
                $item->url . PHP_EOL;
    
        // Write the content
        return file_put_contents($this->filename, $content,FILE_APPEND);        

    }        
   
    public function remove($ids)
    {
        if(empty($this->data) || !is_array($this->data))
            return false;

        if(!is_array($ids))
            $ids = array($ids);
                     
        foreach($ids as $id)
        {
            unset($this->data[$id]);   
            // Remove cache
            $cache = TV_CACHE_CHANNELS . DIRECTORY_SEPARATOR . $id . '.png';
            if(file_exists($cache))
                unlink($cache);
        }

        //Rewrite the content
        $this->_create();
        $this->_write();
            
        return true;
    }    

    public function sort($list)
    {            
        if(empty($this->data) || !is_array($this->data))
            return false;                  

        // Separate the chunks
        $pre        = [];
        $current    = [];
        $post       = [];
        $is_before  = true;

        foreach($this->data as $item)
        {
            if(!in_array($item->id , $list, true))
            {
                if($is_before)                    
                    $pre[$item->id] = $item;    
                else
                    $post[$item->id] = $item;    
            } else {
                $current[$item->id] = $item;
                $is_before = false; 
            }
        }

        // Sort the current based on the received list
        $result = [];
        foreach($list as $id)
        {
            foreach($current as $item)
            {
                if($item->id === $id)
                    $result[] = $item;
            }
        }
        
        // Merge all
        $this->data = array_merge($pre , $result , $post);        

        // Compose the result 
        $this->_create();
        $this->_write();

        return true;
    }        

    public function isvalid( $id ) 
    {      
        if(!file_exists($this->filename))
            return false;

        if(empty($this->data) || !is_array($this->data))
            return false;      

        if(!isset($this->data[$id]))
            return false;

        // We consider the channel is only valid if the url exists
        $item = $this->data[$id];
        if (!$this->_remoteExists( $item->url ))
        {
            $this->remove($id);
            return false;
        }

        return true;
    }

    public function import( $text = null)
    {        
        $list = $this->_parse( $text );

        if(is_array($list))
        {
            foreach($list as $item)
                $this->append($item);
        }
    }

    protected function _create()
    {
        if(file_exists($this->filename))
            unlink($this->filename);

        file_put_contents($this->filename , '#EXTM3U' . PHP_EOL);
    }    

    protected function _write()
    {                              
        if(empty($this->data) || !is_array($this->data))
            return false;

        // Compose the result 
        foreach($this->data as $item)
            $this->append($item);

    }        
    protected function _download()
    {   
        // Toggle as not downloaded
        $this->downloaded = false;

        if(!$this->_remoteExists())
            return false;         

        // Overwrite 
        if(file_exists($this->filename))
            unlink($this->filename);       

        // Get the content        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET"); 
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);  
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);    
        $content    = curl_exec($ch);
        curl_close($ch);   
        
        if(!$content || !preg_match('/^#EXTM3/', $content))
            return false;

        if(!preg_match('/#EXTINF:/', $content))
            return false;                   

        // Clean
        $content = preg_replace('/user-agent=".*"/i','',$content);
        $content = preg_replace('/#EXTVLCOPT.*[\r\n]+/i','',$content);          
        
        // Write the downloaded content 
        if(!file_put_contents($this->filename , $content))
            return false;         

        // Toggle as downloaded
        $this->content = $content;
        $this->downloaded   = true;            
                                    
        return true;

    }

    protected function _remoteExists( $url = null)
    {
        if(empty($url))
            $url = $this->url;

        // Get remote content
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);            
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET"); 
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);            
        $error      = curl_errno($ch);
        $code = !$error ? (int) curl_getinfo($ch, CURLINFO_HTTP_CODE) : null;                       
        curl_close($ch);  
       
        // Error while retrieving the m3u file
        return ( $error || $code !== 200);
    }

    protected function _parse( $text )
    {
        $match = [];        
        preg_match_all('/(?P<tag>#EXTINF:)|(?:(?P<prop_key>[-a-z]+)=\"(?P<prop_val>[^"]+)")|(?<something>,[^\r\n]+)|(?<url>http[^\s]+)/', $text , $match );
        $count = count( $match[0] );

        if(!$count)
            return false;

        $array = [];
        $index = -1;

        for( $i = 0; $i < $count; $i++ )
        {
            $item = $match[0][$i];

            if( !empty($match['tag'][$i])){
                //is a tag increment the result index
                ++$index;
            }elseif( !empty($match['prop_key'][$i])){
                //is a prop - split item
                $array[$index][$match['prop_key'][$i]] = $match['prop_val'][$i];
            }elseif( !empty($match['something'][$i])){
                //is a prop - split item
                $array[$index]['something'] = $item;
            }elseif( !empty($match['url'][$i])){
                $array[$index]['url'] = $item ;
            }
        }  

        // Format into an array of std Objects
        return $this->_format($array);
    }

    protected function _format( $array )
    {           
        if(empty($array) || !is_array($array))     
            return false;

        $config = Factory::getConfig();   
        $task   = Factory::getTask();
        $result = [];   

        foreach ($array as $entry)
        {                    
            if(!isset($entry['url']))
                continue;
            
            $item = new \stdClass();            
            $item->folder = $this->folder;
            $item->source = $this->source;

            // Group
            if(!isset($entry['group-title'])) {
                $item->group = Text::_('GROUPS')[strtoupper($this->folder)] ?? Text::_(ucfirst($this->folder));
            } else {
                $item->group = $entry['group-title'];
            }     

            //Not id = need to save it once formatted
            if(!isset($entry['id'])){
                $this->downloaded = true;
            }

            //ID
            if(!isset($entry['tvg-id'])) {
                $item->tvg_id = parse_url($entry['url'],PHP_URL_HOST);
                $item->id = md5($entry['url']);
            } else {
                if($this->folder == 'dtv' || !$this->downloaded)
                {
                    $item->id = isset($entry['id']) ? $entry['id'] :  $entry['tvg-id'];
                    $item->tvg_id = $entry['tvg-id'];                    
                } else {
                    $item->id = md5($entry['tvg-id']);                    
                    $item->tvg_id = $entry['tvg-id'];                     
                }
            }
    
            // Name
            if(!isset($entry['something']))
                continue;

            $item->name = self::_sanitizeChannelName(join('',explode(',',$entry['something'])));                                               
            
            // Image
            if(isset($entry['logo']) && $this->downloaded)
            {                
                //DTV does not use tvg_logo but logo. Unfortunately, some icons does not exists remote
                $path = explode('/',parse_url($entry['logo'],PHP_URL_PATH));
                
                if(curl_init($entry['logo']) !== false) {                    
                    $item->logo = $config->dtv['host'] . $config->dtv['cache'] . '/' . $path[count($path) -1] . '.png';   
                } else {
                    $item->logo = Factory::getAssets() . '/images/notfound.png'; 
                }            
                
            } elseif(isset($entry['tvg-logo'])){                            
                
                //Github source uses tvg_logo
                $item->logo = $entry['tvg-logo'];
                
            } else {
                
                //Not found
                $item->logo = Factory::getAssets() . '/images/notfound.png';
            }                        
                           
            //When cache is activate, ensure the local images exists
            if($config->use_cache)
            {
                // Blank image
                $blank = 'data:image/gif;base64,R0lGODlhAQABAAD/ACwAAAAAAQABAAACADs=';

                // Does the image already exist in the cache?
                $cache = ($task === 'channels' ? 'channels' : 'stations');
                if(file_exists(TV_CACHE . DIRECTORY_SEPARATOR . $cache . DIRECTORY_SEPARATOR . $item->id . '.png'))
                {                              
                    $item->remote = $config->live_site . '/cache/' . $cache . '/' . $item->id . '.png'; 
                    $item->image = $config->live_site . '/cache/' . $cache . '/' . $item->id . '.png';                    
                } else {  
                    unset($item->remote);
                    switch($task)                                 
                    {
                        case 'channels':
                            $item->image = $blank;                        
                            break;
                        case 'watch':
                            $item->image = Factory::getAssets() . '/images/notfound.png';  
                            break;
                        default:
                            $item->image = $item->logo;                        
                    }

                }    
            } else {
                unset($item->remote);
                $item->image = $item->logo; 
            }         
                                       
            // Ammend URL
            if($this->folder == 'dtv'){
                $item->url = $config->dtv['host'] . $config->dtv['stream'] . '/' . $item->id;            
            } else {
                $item->url = (isset($entry['url']) && $entry['url'] != null)? $entry['url'] : null;            
            }
            
            // Mime
            switch($this->folder){
                case 'dtv':
                    $item->mime = 'video/mpeg4';
                case 'stations':
                    $item->mime = 'audio/mpeg';
                default:       
                    $item->mime = $this->_getMIME($item->url);           
            }

            // Store item
            $result[$item->id] = $item;            
        } 
       
        return $result;
    }

    protected function _getMIME($url)
    {
        $match = [];
        $parts = parse_url($url);
        if(!isset($parts['path'])) {
            return "video/webm";
        }
        
        if(preg_match_all('/.*\.(.*)/i' , $parts['path'] , $match) && count($match)>1){
            switch($match[1][0]){
                case 'm3u':
                case 'm3u8':
                    $mime = 'application/x-mpegURL';
                    break;
                case 'avi':
                    $mime = "video/x-msvideo";
                    break;            
                case 'wmv':
                    $mime = "video/x-ms-wmv";
                    break;
                case 'flv':
                    $mime = "video/x-flv";
                    break;            
                case 'asf':
                    $mime = "video/x-ms-asf";
                    break;             
                case 'mpeg':
                    $mime = "video/mpeg";
                    break;     
                case 'mkv':
                    $mime = "video/web";
                    break;             
                default:    
                    $mime = "video/webm";
            }
        } else {
            $mime = "video/webm";
        }        
        return $mime;
    }
        
    function _sanitizeChannelName( $str)
    {        
        $ret = preg_replace('/\(.*\)/', '', $str);
        $ret = preg_replace('/\[.*\]/', '', $ret);
        return trim(preg_replace('/[ ]{2}/', '', $ret));
    }   
        
}

