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

use RubioTV\Framework\Factory;
use RubioTV\Framework\SEF;
use RubioTV\Framework\Language\Text;

class modelRadio extends Model
{

    public function display()
    {
        if($this->params->folder){
            $model = Factory::getModel('stations');
            $this->page->setFile('stations.php');
            $model->display();
            return;
        }
        
        $this->page->title      = Text::_('RADIO');
        $this->page->data       = $this->_data();
        $this->page->pagination = $this->_pagination();

        foreach($this->page->data as $e)
            $e->link    = Factory::Link('radio', 'stations' , $e->id . ':' . $e->name);                    

        parent::display();

    }

    public function search()
    {

        $this->params->folder     = 'stations';

        if($this->_data() && $this->params->format==='json'){   
            header('Content-Type: application/json; charset=utf-8');    
            echo $this->_term();
            exit(0);                
        }    
    }

    /**
     * Get the associative array of stations
     */
    protected function _data()
    {
        $array = IPTV::getCountries();
        $countries = [];
        foreach($array as $item){
            $countries[strtolower($item->code)] = $item;
        }

        $array = [];
        if ($dir = opendir(TV_IPTV . DIRECTORY_SEPARATOR . 'stations')) {
            while (($f = readdir($dir)) !== false) {
                if ($f != '.' && $f != '..') {
                    $item = new \stdClass;
                    if(!isset($countries[pathinfo($f, PATHINFO_FILENAME)])) continue;

                    $country        = $countries[pathinfo($f, PATHINFO_FILENAME)];
                    $item->id       = $country->code;
                    $item->name     = $country->name;                    
                    $item->flag     = $country->flag;
                    $item->folder   = 'stations';
                    $item->code     = $country->code;
                    $item->source   = $country->code;
                    $item->source_alias = SEF::encode($country->name);
                    $array[] = $item;
                }
            }
            closedir($dir);
        }
        $this->data = $array;
        return $this->data;
    }

}
