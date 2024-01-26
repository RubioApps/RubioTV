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

class modelStreams extends Model
{

    public function display()
    {
        $this->page->title      = Text::_('STREAMS');
        $this->page->folder     = 'streams';
        $this->page->alias      = $this->params->source_alias ?? SEF::rfind($this->params->folder , $this->params->source);   
        $this->page->data       = $this->_data();
        $this->page->pagination = $this->_pagination();
        $this->page->link       = $this->_link();          

        foreach($this->page->data as $e)
            $e->link    = $e->url; //Factory::Link('channels', 'streams' , $e->id . ($e->channel ? ':' . $e->channel : ''));         
  
        parent::display();
    }

    public function search()
    {
        $this->params->folder     = 'streams';
        
        if($this->_data() && $this->params->format==='json'){   
            header('Content-Type: application/json; charset=utf-8');    
            echo $this->_term();
            exit(0);                
        }           
    }
    
    protected function _data()
    {            
        $filename = TV_IPTV . DIRECTORY_SEPARATOR . 'streams' . DIRECTORY_SEPARATOR . 'streams.xml';
        
        if(!file_exists($filename))
            if (!$this->_create())
                return false;
    
        $xml    = simplexml_load_file($filename);
        $root   = $xml->xpath('//streams');            
        $array  = [];

        $array  = IPTV::getStreams();
        $array = array_slice($array ,0,10);
        print_r($array);
        //die();

        $channels = IPTV::getChannels();
        $channels = array_slice($channels,0,10);
        print_r($channels);
        die();

        foreach($root[0] as $s)
        {   
            $item = new \stdClass();            
            $id                     = $s->attributes()->id->__toString();
            $item->id               = $id;
            $item->url              = $s->url->__toString();
            $item->channel          = $s->channel->__toString();
            $item->timeshift        = $s->timeshift->__toString();
            $item->http_referrer    = $s->http_referrer->__toString();
            $item->user_agent       = $s->user_agent->__toString();

            $key = array_search($item->url , $channels);
            $array[$id] = $item;
        }    
        die();
        $this->data = $array;
        return $this->data;  
    }        

    protected function _create()
    {            
        $filename = TV_IPTV . DIRECTORY_SEPARATOR . 'streams' . DIRECTORY_SEPARATOR . 'streams.xml';
        
        if(!file_exists($filename))
        {
            $header = <<<XML
            <?xml version="1.0" encoding="UTF-8" ?>
                <streams></streams>
            XML;
            $xml = new \SimpleXMLElement($header);                    
            $xml->asXML($filename);
            unset($xml);
        } 
    
        $xml    = simplexml_load_file($filename);
        $root   = $xml->xpath('//streams');            
        $array  = IPTV::getStreams();

        if($root)
        {          
            foreach($array as $stream)
            {                
                $node = $root[0]->addChild('stream');
                $node->addAttribute('id' , md5($stream->url)); 
                $node->addChild('url' , $stream->url); 
                $node->addChild('channel' , $stream->channel ?? ''); 
                $node->addChild('timeshift' , $stream->timeshift ?? ''); 
                $node->addChild('http_referrer' , $stream->http_referrer ?? ''); 
                $node->addChild('user_agent' ,  $stream->user_agent ?? ''); 
            }                        
        }     
        return $xml->asXML($filename);         
    }   


    protected function _link()
    {
        $this->link = IPTV::getURL();
        return $this->link;
    }
}