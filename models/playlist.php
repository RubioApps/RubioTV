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

class modelPlaylist extends Model
{
    protected $m3u;
    protected $id;
    protected $item;

    public function search()
    {
        $this->data = $this->_data();
        header('Content-Type: application/json; charset=utf-8');    
        echo $this->_term();
        exit(0);        
    }    

    public function add( $id = null)
    {
        if(!$id && $this->params->id)
            $id = $this->params->id;
            
        $this->id   = $id;
        $this->data = $this->_data();  

        if(!$this->id || !isset($this->data[$this->id]))
        {
            $this->page->setFile('404.php'); 
            $this->page->sendError( Text::_('ERROR') , Text::_('ERROR_VIEW'));   
            return false;       
        }

        $this->item =  $this->data[$this->id];
        $this->m3u  = new M3U('custom','playlist');              
        $success    = $this->m3u->append( $this->item );

        header('Content-Type: application/json; charset=utf-8');    
        echo json_encode(['error' => !$success]);
        exit(0);           
        
    }

    public function remove( $id = null)
    {
        if(!$id && $this->params->id)
            $id = $this->params->id;
            
        $this->id   = $id;
        $this->data = $this->_data();
    
        if(!$this->id || !isset($this->data[$this->id]))        
        {
            $this->page->setFile('404.php'); 
            $this->page->sendError( Text::_('ERROR') , Text::_('ERROR_VIEW'));   
            return false;       
        }

        $this->item =  $this->data[$this->id];        
        $this->m3u  = new M3U('custom','playlist');        
        $success    = $this->m3u->remove( array($this->item->id) );

        header('Content-Type: application/json; charset=utf-8');    
        echo json_encode(['error' => !$success]);
        exit(0);           

    }

    protected function _data()
    {    
        $this->m3u = new M3U($this->params->folder,$this->params->source);
        $this->data = $this->m3u->load();   
        return $this->data;
    }        
   
}