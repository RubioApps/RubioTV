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
use RubioTV\Framework\IPTV; 
use RubioTV\Framework\Language\Text; 

class modelLanguages extends Model
{

    public function display()
    {
        $this->page->title      = Text::_('LANGUAGES');
        $this->page->folder     = 'languages';
        $this->page->alias      = $this->params->source_alias ?? SEF::rfind($this->params->folder , $this->params->source);   
        $this->page->data       = $this->_data();
        $this->page->pagination = $this->_pagination();
        $this->page->link       = $this->_link();

        foreach($this->page->data as $e)
            $e->link    = Factory::Link('channels', 'languages' , $e->id . ':' . $e->name);                    

        parent::display();
    }

    public function search()
    {
        $this->params->folder     = 'languages';

        if($this->_data() && $this->params->format==='json'){   
            header('Content-Type: application/json; charset=utf-8');    
            echo $this->_term();
            exit(0);                
        }           
    }    
    
    public function _data()
    {            
        $this->data   = IPTV::getLanguages();  

        foreach($this->data as $item)
            $item->id = $item->code;

        return $this->data;
    }    

    public function _link()
    {
        $this->link = IPTV::getURL();
        return $this->link;
    }   
}