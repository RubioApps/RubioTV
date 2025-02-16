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

use RubioTV\Framework\Factory; 
use RubioTV\Framework\Language\Text; 

class modelGuides extends Model
{
    protected $epg;
    public function display()
    {        
        if(!$this->params->folder)
            $this->params->folder = 'dtv';

        if(!$this->params->source)
            $this->params->source = $this->config->dtv['filename'];    

        $this->params->source_alias = $this->params->source_alias ?? SEF::rfind($this->params->folder , $this->params->source);              
            
        $this->page->title          = Text::_('GUIDES');
        $this->page->folder         = $this->params->folder;
        $this->page->source         = $this->params->source;
        $this->page->source_alias   = $this->params->source_alias;
        $this->page->data           = $this->_data();       
        $this->page->link           = $this->_link();

        if(isset($this->page->data) && is_array($this->page->data))
        {
            foreach($this->data as $e)
                $e-> link = Factory::Link('watch', $this->page->folder , $this->page->source . ':' . $this->page->source_alias , 
                    $e->id . ':' . $e->name);
        }
        parent::display();
    }    

    public function get( $folder , $source)
    {
        $this->params->folder = $folder;
        $this->params->source = $source;
        return $this->_data();  
    }

    protected function _data()
    {    
        $this->epg = new EPG(); 

        if(!$this->epg->MergeXMLTV( $this->params->folder , $this->params->source))
            return false;  

        $this->data = $this->epg->getPlayingNow(); 

        return $this->data;
    }    
    
    protected function _link()
    {
        $this->link = $this->config->live_site . '/epg/' . $this->params->folder . '.' . $this->params->source . '.xmltv';
        return $this->link;
    }
}