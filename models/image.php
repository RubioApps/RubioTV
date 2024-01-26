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

use RubioTV\Framework\Language\Text; 

class modelImage extends Model
{
    public function remote()
    {        
        $id     = Factory::getParam('id');
        $url    = base64_decode($_GET['url']);

        if(strstr($url , $this->config->live_site) !== false)
            $image  = $url;
        else
            $image  = $this->_load($url , $id);

        $data   = [];  
        $data['url']        = $url;
        $data['id']         = $id;
        $data['timestamp']  = microtime(true);

        if($image)
        {
            $data['logo']       = $image;
            $data['message']    = Text::_('CACHE_SUCCESS');
            $data['error']      = false;
        } else{
            $data['logo']       = Factory::getAssets() . '/images/notfound.png';
            $data['message']    = Text::_('CACHE_ERROR');
            $data['error']      = true;
        }

        header('Content-Type: application/json; charset=utf-8');    
        echo json_encode($data);
        exit(0);                
    }


    protected function _load( $url , $id)
    {
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
            if($this->_thumbnail($image)) 
                return $this->config->live_site . '/cache/' . $id . '.png';
        }                      
        return false;
    }

    protected function _thumbnail( $path , $size = 200)
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
}