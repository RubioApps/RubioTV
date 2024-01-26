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
use RubioTV\Framework\SEF; 
use RubioTV\Framework\IPTV; 
use RubioTV\Framework\Language\Text; 

class modelCategories extends Model
{

    public function display()
    {
        $this->page->title      = Text::_('CATEGORIES');
        $this->page->folder     = 'categories';
        $this->page->alias      = $this->params->source_alias ?? SEF::rfind($this->params->folder , $this->params->source);   
        $this->page->data       = $this->data();
        $this->page->link       = $this->link();
        $this->page->pagination = $this->_pagination();           

        foreach($this->page->data as $e)
            $e->link    = Factory::Link('channels', 'categories' , $e->name . ':' . $e->name);         

        parent::display();
    } 

    public function data()
    {    
        $this->data   = IPTV::getCategories();                                 

        foreach($this->data as $item)
            $item->group = Text::_('GROUPS')[strtoupper($item->name)] ?? ucfirst($item->name);

        return $this->data;
    }    
    
    public function link()
    {
        $this->link = IPTV::getURL();
        return $this->link;
    }
}