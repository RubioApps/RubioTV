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
use RubioTV\Framework\M3U; 
use RubioTV\Framework\Language\Text; 

class modelChannels extends Model
{
    public function display()
    {     
        // Get the SEF
        $this->params->source_alias = $this->params->source_alias ?? SEF::rfind($this->params->folder , $this->params->source);         

        $this->page->title          = Text::_('GROUPS')[strtoupper($this->params->source)] ?? ucfirst(SEF::decode($this->params->source_alias)); 
        $this->page->folder         = $this->params->folder;        
        $this->page->source         = $this->params->source;
        $this->page->source_alias   = $this->params->source_alias;
        $this->page->data           = $this->_data();
        $this->page->link           = $this->_link();

        if(!$this->page->data){
            $this->page->sendError( Text::_('ERROR') , Text::_('ERROR_FOLDER'));
            return false;
        };        
    
        // Build pagination
        $this->page->pagination = $this->_pagination();      

        // Defered function Factory::Link for performance purposes
        foreach($this->page->data as $e)
        {
            $e->link    = Factory::Link('watch', 
                $this->params->folder , 
                $this->params->source . ':' . $this->params->source_alias, 
                $e->id . ':' . $e->name);            
                
            if(empty($e->remote))
                $e->remote  = Factory::Link('image.remote' , $this->params->folder , $this->params->source , $e->id ,'url=' . base64_encode($e->logo)); 
        }   
        
        parent::display();

    }    

    public function search()
    {
        $this->params->source_alias = $this->params->source_alias ?? SEF::rfind( $this->params->folder , $this->params->source);

        if($this->_data() && $this->params->format==='json'){   
            header('Content-Type: application/json; charset=utf-8');    
            echo $this->_term();
            exit(0);                
        }           
    }

    public function sync()
    {
        if($this->params->folder !== 'dtv' && $this->params->folder !== 'custom'  && $this->params->format==='json')
        {                        
            header('Content-Type: application/json; charset=utf-8');                
            echo $this->_sync();
            exit(0);   
        }          
    }

    protected function _data()
    {               
        switch($this->params->folder){
            case 'categories': 
            case 'countries':               
            case 'languages':  
                $this->link = IPTV::getSource($this->params->folder , $this->params->source);                
                break;                
            case 'custom':
            case 'dtv':              
                $this->link = $this->config->live_site . '/iptv/' . $this->params->folder  . '/' . $this->params->source . '.m3u';                
                break;
            default:                              
                return false;
        }      
              
        // Get the list of channels from the m3u file
        $m3u = new M3U($this->params->folder , $this->params->source , $this->link);        
        $this->data = $m3u->load();
    
        return $this->data;
    }

    protected function _link()
    {
        return $this->link;
    }

    protected function _sync()
    {           
        // Get the SEF
        $this->params->source_alias = $this->params->source_alias ?? SEF::rfind($this->params->folder , $this->params->source);    

        $filename = TV_IPTV . DIRECTORY_SEPARATOR . $this->params->folder . DIRECTORY_SEPARATOR . $this->params->source . '.m3u';

        $data = [];            
        $data['filename'] = $filename;
        $data['title'] = Text::_('RESYNC');
        $data['url'] = Factory::Link('channels', $this->params->folder , $this->params->source . ':' . $this->params->source_alias) ; 

        if(file_exists($filename))   
            $data['error'] = unlink( $filename );         
        else
            $data['error'] = true;

        $data['content'] = $data['error'] === true ? Text::_('RESYNC_SUCCESS') : Text::_('RESYNC_ERROR');            

        return json_encode($data);  
    }    
}