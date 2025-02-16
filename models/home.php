<?php

/**
 +-------------------------------------------------------------------------+
 | RubioTV  - A domestic IPTV Web app browser                              |
 | Version 1.5.0                                                           |
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

class modelHome extends Model
{

    public function display()
    {
        $this->page->title      = Text::_('HOME');
        $this->page->data       = $this->_data();
        parent::display();
    }

    public function snapshot()
    {
        $url     = Request::getVar('url', null, 'GET');
        if ($url) {
            $path   = parse_url(urldecode($url), PHP_URL_PATH);
            $id     = array_pop(explode('/', $path));
            $file   = TV_EPG_SNAPSHOTS .  DIRECTORY_SEPARATOR . $id . '.jpeg';

            ob_end_clean();

            if (isset($_FILES['data']))
            {
                if(file_exists(TV_EPG_SNAPSHOTS . DIRECTORY_SEPARATOR . $id . '.jpeg'))
                    unlink(TV_EPG_SNAPSHOTS . DIRECTORY_SEPARATOR . $id . '.jpeg');

                if (move_uploaded_file($_FILES['data']['tmp_name'], $file)) 
                {
                    header('Content-type: image/jpeg');
                    readfile(TV_EPG_SNAPSHOTS . DIRECTORY_SEPARATOR . $id . '.jpeg');
                    exit(0);
                }
            }
        }
        header('Content-type: image/gif');
        echo base64_decode(explode(',', TV_BLANK)[1]);
        exit(0);
    }

    protected function _data()
    {
        $config = Factory::getConfig();

        //Get current folders
        $this->data = [];
        $this->data['menu']   = $this->_folders();

        //Get channels playing now
        $model = Factory::getModel('guides');
        $array = $model->get('dtv', 'coreelec');

        foreach ($array as &$item) 
        {
            $item->link = Factory::Link('watch', 'dtv', 'coreelec:corelec', $item->id . ':' . SEF::encode($item->name));
            $item->snapshot = TV_BLANK;
            $item->getshot = true;

            $file   = TV_EPG_SNAPSHOTS .  DIRECTORY_SEPARATOR . $item->id . '.jpeg';
            if (file_exists($file))  
            {
                if (filectime($file) <= time() - 900 ) {                    
                    unlink($file);
                } else {                    
                    $item->snapshot = $config->live_site . '/epg/snapshots/' . $item->id . '.jpeg';
                    $item->getshot = false;                    
                }
            }
        }

        $this->data['playing'] = is_array($array) ? array_slice($array, 0, 15) : null;

        return $this->data;
    }
}
