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

use RubioTV\Framework\Factory; 

class Helpers{    
       
    public static function getChannelsFromFile( $url , $folder , $source , $force = false)
    {                   
        $first      = false;
        $filem3u    = TV_IPTV . DIRECTORY_SEPARATOR . $folder . DIRECTORY_SEPARATOR . $source . '.m3u';

        if(!file_exists($filem3u) || $force)
        {
            // Download the remote content and save it
            $content = self::_downloadSource( $url , $filem3u );
            // This is the first time we read the file
            $first = true;            
        } else {
            // Get the local content
            $content = file_get_contents(TV_IPTV . DIRECTORY_SEPARATOR . $folder . DIRECTORY_SEPARATOR . $source. '.m3u');      
        }

        if(!$content)
            return false;
        
        $list   = self::_parseM3U($content);    
        $result = self::_loadM3U ($list , $folder , $source , $first );
        
        // At the first pass, replace the original m3u by a standard format one
        if($first && count($result))
        {
            self::_overrideSource($result , $filem3u);
        }

        return $result;
    }
   
    public static function channelExists( $item )
    {
        $config     = Factory::getConfig();  
        $filename   = TV_IPTV . DIRECTORY_SEPARATOR . $item->folder . DIRECTORY_SEPARATOR . $item->source . '.m3u';
        $ret        = true;

        //Check whether the URL is a DTV one. Do not purge in such case
        if(strstr($item->url , $config->dtv['host']) !== false)
            return true;        
        
        //Check the remote URL
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $item->url);  
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET"); 
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);        
        curl_setopt($ch, CURLOPT_FAILONERROR, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_exec($ch);  
        
        //If the fail exists locally but the URL does not, there prune the local file
        if(curl_errno($ch) && file_exists($filename))
        {   
            // Get the content
            $content = file_get_contents($filename);
            
            // Clean-up            
            $content = preg_replace('/#EXTINF:-1 id="'.$item->id.'".?[^\r\n]+/', null , $content , 1);                   
            $content = preg_replace('/' . preg_quote($item->url,'/') . '/', null , $content , 1);    
            $content = preg_replace('/^[\r\n]{2}/','',$content);                            

            // Rewrite
            $fp = fopen($filename, 'w+') or die("Unable to open file!");
            flock($fp,LOCK_EX);
            ftruncate($fp,0);
            fwrite($fp, $content);
            fclose($fp);      
            
            // Prune done
            $ret = false;
        }
        curl_close($ch);
        return $ret;
        
    }

   
    public static function removeChannels( $data , $folder, $source )
    {
        if(!is_array($data))
            $list = array($data);
        else
            $list = $data;

        $filename = TV_IPTV . DIRECTORY_SEPARATOR . $folder . DIRECTORY_SEPARATOR . $source . '.m3u';
       
        if(file_exists($filename))
        {                    
            $content = file_get_contents($filename);

            foreach($list as $item)
            {
                list($id,$url) = explode( chr(0), base64_decode($item));   

                // Clean-up file content                                
                $content = preg_replace('/#EXTINF:-1 id="'.$id.'".?[^\r\n]+/','',$content ,1);                   
                $content = preg_replace('/' . preg_quote($url,'/') . '/','',$content , 1);     
                $content = preg_replace('/^[\r\n]{2}/','',$content);             

                // Remove cache
                $cache = TV_CACHE . DIRECTORY_SEPARATOR . $id . '.png';
                if(file_exists($cache))
                    unlink($cache);
            }

            // Rewrite
            $fp = fopen($filename, 'w+') or die("Unable to open file!");
            flock($fp,LOCK_EX);
            ftruncate($fp,0);
            fwrite($fp, $content);
            fclose($fp);      
            
            // Purge done
            return true;
        }
        return false;
        
    }    

    public static function searchChannel( $folder , $source, $id ) 
    {        
        $filename = TV_IPTV . DIRECTORY_SEPARATOR . $folder . DIRECTORY_SEPARATOR . $source. '.m3u';
        $ret = false;
                
        if(file_exists($filename))
        {                    
            $content = file_get_contents($filename);         
            $ret = preg_match('/#EXTINF:-1 id="'.$id.'".?[^\r\n]+/',$content);  
        }
        return $ret;
    }

    protected static function _getMIME($url)
    {
        $match = array();
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
    
    public static function addToPlaylist( $item )
    {
        $filename = TV_IPTV . DIRECTORY_SEPARATOR . 'custom' . DIRECTORY_SEPARATOR . 'playlist.m3u';
        if(!file_exists($filename))
        {
            $fp = fopen($filename, 'w+') or die("Unable to open file!");
            flock($fp,LOCK_EX);
            fwrite($fp, '#EXTM3U' . PHP_EOL);
            fclose($fp);                             
        }
        
        //Prevent duplicates
        self::removeFromPlaylist( $item );           

        //Modify content
        $content = file_get_contents($filename);              
        $content .= '#EXTINF:-1 id="' . $item->id . '" tvg-id="' . $item->tvg_id . '" ' .        
            'tvg-logo="' . $item->image . '" group-title="' . $item->group . '",' . $item->name . PHP_EOL .
            $item->url . PHP_EOL;
                   
        $fp = fopen($filename, 'w+') or die("Unable to open file!");
        flock($fp,LOCK_EX);
        fwrite($fp, $content);
        fclose($fp);                   
    }

    public static function removeFromPlaylist( $item )
    {
        $filename = TV_IPTV . DIRECTORY_SEPARATOR . 'custom' . DIRECTORY_SEPARATOR . 'playlist.m3u';
        if(!file_exists($filename))
        {            
            $fp = fopen($filename, 'w+') or die("Unable to open file!");
            flock($fp,LOCK_EX);
            fwrite($fp, '#EXTM3U' . PHP_EOL);
            fclose($fp);                                     
        }
        
        //Get the content
        $content = file_get_contents($filename);  

        //Remove content
        $content = preg_replace('/#EXTINF:-1 id="'.$item->id.'".?[^\r\n]+/' , null , $content , 1);                   
        $content = preg_replace('/' . preg_quote($item->url,'/') . '/' , null , $content , 1); 
        $content = preg_replace('/^[\r\n]{2}/','',$content);              

        //Replace content
        $fp = fopen($filename, 'w+') or die("Unable to open file!");
        flock($fp,LOCK_EX); 
        ftruncate($fp, 0);     
        fwrite($fp, $content);
        fclose($fp);            
    }   

    public static function addImported( $action = 'add')
    {
        $first      = false;
        $filename   = TV_IPTV . DIRECTORY_SEPARATOR . 'custom' . DIRECTORY_SEPARATOR . 'imported.m3u';

        if(!file_exists($filename))
        {            
            $fp = fopen($filename, 'w+') or die("Unable to open file!");
            flock($fp,LOCK_EX); 
            fwrite($fp, '#EXTM3U' . PHP_EOL);
            fclose($fp);                                     
            $first = true;
        }        

        $input = new \stdClass();
        foreach($_POST as $k => $v)
        {
            $v = trim($v);
            if($v !== null && strlen($v))
                $input->$k = $v;
        }

        switch($action)
        {
            case 'add':
                if(empty( $input->name ) || (empty( $input->url ))){
                    return ERR_IMPORT_EMPTY_FIELD;
                }

                if(!filter_var($input->url,FILTER_VALIDATE_URL)){
                    return ERR_IMPORT_INVALID_URL;
                }

                if(empty( $input->id ))
                    $input->tvg_id = $input->id = md5($input->name);
                else
                    $input->tvg_id = $input->id;

                if(empty( $input->logo )) {
                    $input->logo = Factory::getAssets() . '/images/notfound.png';
                } else {
                    if(!filter_var($input->logo,FILTER_VALIDATE_URL))
                    {
                        return ERR_IMPORT_INVALID_URL;                        
                    }
                }
                
                $import = '#EXTINF:-1 id="' . md5($input->id) . '" tvg-id="' . addslashes($input->tvg_id) . '" ' .            
                    'tvg-logo="' . $input->logo . '" group-title="Imported", ' . $input->name . PHP_EOL .
                    $input->url . PHP_EOL;
                break;

            case 'brut':
                if(empty( $input->brut )){
                    $result['error'] = true;
                    $result['code'] = -4;
                    return $result;
                }
                $import = addslashes(trim($input->brut));
                break;

            case 'upload':
                if(empty($_FILES['file']) || 
                    $_FILES['file']['type'] !== 'audio/x-mpegurl' ||
                    $_FILES['file']['size'] > 4096 * 1024 ||
                    $_FILES['file']['error'] ||
                    !is_uploaded_file($_FILES['file']['tmp_name'])
                    )
                {
                    return ERR_IMPORT_INVALID_FILE;
                }                
                $import = file_get_contents($_FILES['file']['tmp_name']);   
                break;  
        }  

        // Take the content and fulfill a list
        $list = self::_parseM3U($import);

        // Format the list into a standard content
        $data = self::_loadM3U( $list , 'custom' , 'imported' , $first);

        // If there is any entry, the conform the next content
        if(count($data))
        {
            $content = file_get_contents($filename); 

            foreach($data as $item)
            {
                $item->logo = self::loadRemoteImage( $item->logo , $item->id );

                $content .= '#EXTINF:-1 id="' . $item->id . '" tvg-id="' . $item->tvg_id . '" ' .            
                    'tvg-logo="' . $item->logo . '" group-title="Imported", ' . $item->name . PHP_EOL .
                    $item->url . PHP_EOL;
            }                    

            $fp = fopen($filename, 'w+') or die("Unable to open file!");
            flock($fp,LOCK_EX);
            fwrite($fp, $content);
            fclose($fp);  

            return ERR_IMPORT_NONE;
        }

        return ERR_IMPORT_ANY;

    }

    public static function removeImported( $item )
    {
        $filename = TV_IPTV . DIRECTORY_SEPARATOR . 'custom' . DIRECTORY_SEPARATOR . 'imported.m3u';
        if(!file_exists($filename))
        {            
            $fp = fopen($filename, 'w+') or die("Unable to open file!");
            flock($fp,LOCK_EX);
            fwrite($fp, '#EXTM3U' . PHP_EOL);
            fclose($fp);                                     
        }
        
        //Get content
        $content = file_get_contents($filename);  

        //Remove content
        $content = preg_replace('/#EXTINF:-1 id="'.$item->id.'".?[^\r\n]+/','',$content , 1);                   
        $content = preg_replace('/' . preg_quote($item->url,'/') . '/','',$content , 1); 
        $content = preg_replace('/^[\r\n]{2}/','',$content); 

        $fp = fopen($filename, 'w+') or die("Unable to open file!");
        flock($fp,LOCK_EX);
        ftruncate($fp, 0);     
        fwrite($fp, $content);
        fclose($fp);          
    }

    public static function remoteFileExists( $url )
    {        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_NOBODY, true);
        curl_exec($ch);
        $retcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        // $retcode >= 400 -> not found, $retcode = 200, found.
        curl_close($ch);           
        return ($retcode === 200);                         
    }
    
    public static function loadRemoteImage( $url , $id )
    {
        return self::_saveremotefile ( urldecode($url) , $id);
    }

    static function _downloadSource($url , $dest)
    {
        // Get remote content
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);            
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET"); 
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);            
        $content    = curl_exec($ch);      
        $error      = curl_errno($ch);
        $code = !$error ? (int) curl_getinfo($ch, CURLINFO_HTTP_CODE) : null; 
        curl_close($ch);  

        // Error while retrieving the m3u file
        if( $error || $code !== 200){              
            return false;
        }

        // Clean-up            
        $content = preg_replace('/user-agent=".*"/i','',$content);
        $content = preg_replace('/#EXTVLCOPT.*[\r\n]+/i','',$content);  
        
        $fp = fopen($dest , 'w+') or die("Unable to open file!");
        flock($fp,LOCK_EX);
        ftruncate($fp,0);
        fwrite($fp, $content);
        fclose($fp);

        return $content;

    }

    static function _parseM3U( $content)
    {
        $match = array();        
        preg_match_all('/(?P<tag>#EXTINF:-1)|(?:(?P<prop_key>[-a-z]+)=\"(?P<prop_val>[^"]+)")|(?<something>,[^\r\n]+)|(?<url>http[^\s]+)/', $content, $match );
        $count = count( $match[0] );

        $list = [];
        $index = -1;

        for( $i = 0; $i < $count; $i++ )
        {
            $item = $match[0][$i];

            if( !empty($match['tag'][$i])){
                //is a tag increment the result index
                ++$index;
            }elseif( !empty($match['prop_key'][$i])){
                //is a prop - split item
                $list[$index][$match['prop_key'][$i]] = $match['prop_val'][$i];
            }elseif( !empty($match['something'][$i])){
                //is a prop - split item
                $list[$index]['something'] = $item;
            }elseif( !empty($match['url'][$i])){
                $list[$index]['url'] = $item ;
            }
        }  
        return $list;      
    }

    static function _loadM3U( $list , $folder , $source , $first = false)
    {        
        $config = Factory::getConfig();   
        $result = [];   

        foreach ($list as $entry)
        {                    
            if(!isset($entry['url']))
                continue;
            
            $item = new \stdClass();            
            $item->folder = $folder;
            $item->source = $source;

            // Group
            if(!isset($entry['group-title'])) {
                $item->group = 'Undefined';
            } else {
                $item->group = $entry['group-title'];
            }     

            //id
            if(!isset($entry['tvg-id'])) {
                $item->tvg_id = parse_url($entry['url'],PHP_URL_HOST);
                $item->id = md5($entry['url']);
            } else {
                if($folder == 'dtv' || !$first)
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
            if(isset($entry['logo']) && $first)
            {                
                //DTV does not use tvg_logo but logo
                $path = explode('/',parse_url($entry['logo'],PHP_URL_PATH));
                $item->logo = $config->dtv['host'] . $config->dtv['cache'] . '/' . $path[count($path) -1] . '.png';                
                
            } elseif(isset($entry['tvg-logo'])){                            
                
                //Github source uses tvg_logo
                $item->logo = $entry['tvg-logo'];
                
            } else {
                
                //Not found
                $item->logo = Factory::getAssets() . '/images/notfound.png';
            }                        
                           
            //When cache is activate, ensure the local images exists
            $item->remote = '';
            if($config->use_cache)
            {
                // Blank image
                $blank = 'data:image/gif;base64,R0lGODlhAQABAAD/ACwAAAAAAQABAAACADs=';
                // Does the image already exist in the cache?
                if(file_exists(TV_CACHE . DIRECTORY_SEPARATOR . $item->id . '.png')){
                    $item->image = $config->live_site . '/cache/' . $item->id . '.png';
                    $item->remote = $config->live_site . '/cache/' . $item->id . '.png'; 
                } else {                                        
                    if(Factory::getTask() === 'channels') {
                        $item->image = $blank;
                        $item->remote = $config->live_site . '/?task=loadremoteimage&url=' . urlencode($item->logo) . '&id=' . $item->id;                                           
                    } elseif(Factory::getTask() === 'view') {
                        $item->image = Factory::getAssets() . '/images/notfound.png';  
                    } else {
                        $item->image = $item->logo;
                    }

                }    
            } else {
                $item->image = $item->logo; 
                /*
                if(self::remoteFileExists($item->logo)){
                    $item->image = $item->logo; 
                } else {
                    $item->image = Factory::getAssets() . '/images/notfound.png';                     
                }
                */
            }         
                  
            // Link
            $item->link = $config->live_site . '/?task=view' .
               '&folder=' . strtolower($folder) .
               '&source=' . strtolower($source) .
               '&id=' . $item->id;

            // Ammend URL
            if($folder == 'dtv'){
                $item->url = $config->dtv['host'] . $config->dtv['stream'] . '/' . $item->id;            
            } else {
                $item->url = (isset($entry['url']) && $entry['url'] != null)? $entry['url'] : null;            
            }
            
            // Mime
            if($folder != 'dtv' && $item->url){
                $item->mime = self::_getMIME($item->url);
            } else {
                $item->mime = "video/webm";
            }

            // Store item
            $result[$item->id] = $item;            
        } 
        
        return $result;
    }

    static function _overrideSource($list , $dest)
    {
        $fp = fopen($dest, 'w+') or die("Unable to open file!");
        flock($fp, LOCK_EX);
        ftruncate($fp,0);
        $content = '#EXTM3U' . PHP_EOL;
        foreach($list as $item)
        {              
            $content .= '#EXTINF:-1 id="' . $item->id . '" tvg-id="' . $item->tvg_id . '" ' .
                'tvg-logo="' . $item->logo . '" group-title="' . $item->folder . '",' . $item->name . PHP_EOL . 
                $item->url . PHP_EOL;
        }
        fwrite($fp, $content);
        fclose($fp);            
    }
    
    static function _saveremotefile( $url , $id)
    {
        $config = Factory::getConfig();
        // Image path
        $temp   = tmpfile();
        $image  = TV_CACHE . DIRECTORY_SEPARATOR . $id . '.png';

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_FILE, $temp);
        curl_setopt($ch, CURLOPT_HEADER, 0);        
        curl_exec($ch);
        $ret = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);           

        if($ret === 200)
        {
            $path = stream_get_meta_data($temp)['uri'];
            copy($path , $image);

            // Create thumbnail
            if(self::_createThumbnail($image)) 
                return $config->live_site . '/cache/' . $id . '.png';
        }         
        //copy(Factory::getAssets() . '/images/notfound.png' , TV_CACHE . DIRECTORY_SEPARATOR . $id . '.png');                
        return false;
    }

    static function _createThumbnail( $path , $size = 200)
    {
        // Check the type of image
        list($width, $height , $mime) = getimagesize($path);

        if( $mime == IMAGETYPE_JPEG ) 
            $image = imagecreatefromjpeg($path);
        elseif( $mime == IMAGETYPE_GIF )
            $image = imagecreatefromgif($path);
        elseif( $mime == IMAGETYPE_PNG )
            $image = imagecreatefrompng($path);
        else
            return false;  

        // Calculate new size
        if ($width > $height) {
            $x = 0;
            $y = ceil(($width - $height) / 2);
            $widestSide = $width;
        } else {
            $y = 0;
            $x = ceil(($height - $width) / 2);            
            $widestSide = $height;
        }

        
        $thumb = imagecreatetruecolor($widestSide, $widestSide);                

        // Try to preserve the transparency               
        if($mime === IMAGETYPE_PNG){
            imagealphablending($thumb, false);                       
            imagesavealpha($thumb, true); 
            $color = imagecolorallocatealpha($thumb, 255, 255, 255, 127);              
            imagefill($thumb, 0, 0, $color);
 
            // Copy the resampled image into the canvas       
            imagecopy($thumb, $image , $x , $y , 0, 0, $width, $height); 

            // Now resize the result to the given size
            $final = imagecreatetruecolor($size, $size);  
            imagealphablending($final, false);                       
            imagesavealpha($final, true); 
            $color = imagecolorallocatealpha($final, 255, 255, 255, 127);              
            imagefill($final, 0, 0, $color);              
            imagecopyresized($final, $thumb, 0, 0, 0, 0, $size, $size, $widestSide, $widestSide);       

            // Save the binaries into the original
            return imagepng($final, $path);             

        // For JPEG, simply resample
        } 

        imagecopyresampled($thumb, $image, $x, $y, 0, 0, $size, $size, $widestSide, $widestSide);    
        return imagejpeg($thumb, $path);                               
       
    }
        
    static function _sanitizeChannelName( $str)
    {        
        $ret = preg_replace('/\(.*\)/', '', $str);
        $ret = preg_replace('/\[.*\]/', '', $ret);
        return preg_replace('/[ ]{2}/', '', $ret);
    }   
        
}

