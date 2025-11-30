<?php

/**
 +-------------------------------------------------------------------------+
 | RubioTV  - A domestic IPTV Web app browser                              |
 | Version 1.6.1                                                          |
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
    /**
     * Action: Create a thumbnail on the fly. Need the following parameters from the query string
     * id:  Unique identifier of the channel or station
     * url: URL of the channel or station
     * cache: Destination folder of the thumbnail
     * 
     * Returns a JSON that contains the output logo, a message and a flag (success or error)
     */

    public function remote()
    {
        $id     = Request::getVar('id', '', 'GET');
        $url    = base64_decode(Request::getVar('url', '', 'GET'));
        $cache  = Request::getVar('cache', 'channels', 'GET');

        if (strstr($url, $this->config->live_site) !== false)
            $image  = $url;
        else
            $image  = $this->_load($url, $id, $cache);

        $data = [];
        $data['url']        = $url;
        $data['id']         = $id;
        $data['timestamp']  = microtime(true);

        if ($image) {
            $data['logo']       = $image;
            $data['message']    = Text::_('CACHE_SUCCESS');
            $data['error']      = false;
        } else {
            $data['logo']       = Factory::getAssets() . '/images/notfound.png';
            $data['message']    = Text::_('CACHE_ERROR');
            $data['error']      = true;
        }

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data);
        exit(0);
    }


    /**
     * Create a thumbnail on the fly. This works for GIF/PNG/JPEG
     * 
     * @param string $url   Remote URL of the image to be acquired
     * @param int $folder   Folder name inside the cache directory
     * 
     * @return bool         Returns the URL of the thumbnail, false if not
     */

    protected function _load($url, $id, $folder = 'channels')
    {
        // Ensure the destination folder exists
        if(!file_exists(TV_CACHE . DIRECTORY_SEPARATOR . $folder ))
            mkdir(TV_CACHE . DIRECTORY_SEPARATOR . $folder );

        // Image path
        $temp   = tmpfile();
        $image  = TV_CACHE . DIRECTORY_SEPARATOR . $folder . DIRECTORY_SEPARATOR . $id . '.png';

        $ch = curl_init($url);

        curl_setopt($ch, CURLOPT_FILE, $temp);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

        curl_exec($ch);
        $ret = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($ret === 200 || $ret === 301 ) {
            $path = stream_get_meta_data($temp)['uri'];
            if (copy($path, $image)) {
                // Create thumbnail
                if ($this->_thumbnail($image)) {
                    return $this->config->live_site . '/cache/' . $folder . '/' . $id . '.png';
                }
            }
        }
        return false;
    }

    /**
     * Create a thumbnail on the fly. This works for GIF/PNG/JPEG
     * 
     * @param string $path  The full path to the remote image file
     * @param int $size     The size of the square output thumbnail
     * 
     * @return bool         Returns true if the thumbnail was created, false if not
     */
    protected function _thumbnail( $path , $size = 150 )
    {
        list($width, $height, $mime) = getimagesize($path);

        if($width == 0 || $height == 0) return false;

        // Select the appropiate function depending on the original image
        switch($mime){
            case IMAGETYPE_GIF:
                $output = "ImageGIF";
                $func = "ImageCreateFromGIF";
                break;
            case IMAGETYPE_JPEG:
                $output = "ImageJPEG";
                $func = "ImageCreateFromJPEG";
                break;
            case IMAGETYPE_PNG:
                $output = "ImagePNG";
                $func = "ImageCreateFromPNG";
                break;
            default:
                return false;
        }        
        
        // Set the dimensions for the resized output image
        if ($width > $height) {
            $dst_width = $size;
            $dst_height = intval($height * $dst_width / $width);
        } else {
            $dst_height = $size;
            $dst_width = intval($width * $dst_height / $height);
        }

        // Calculate offset
        $dst_x = intval(($size - $dst_width) / 2);
        $dst_y = intval(($size - $dst_height) / 2);

        // Store the original image and set the final image
        $original   = $func($path);
        $thumbnail  = imagecreatetruecolor($size, $size); 

        // If PNG, put a background
        if ($mime === IMAGETYPE_PNG) {
            imagesavealpha($thumbnail, TRUE);
            $color = imagecolorallocatealpha($thumbnail, 0, 0, 0, 127);
            imagefill($thumbnail, 0, 0, $color);
        }

        // Resample image
        imagecopyresampled($thumbnail, $original, $dst_x, $dst_y, 0, 0, $dst_width, $dst_height, $width, $height);

        // Output
        return $output($thumbnail, $path);
    }
}
