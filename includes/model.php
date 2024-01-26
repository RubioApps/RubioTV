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
use RubioTV\Framework\M3U; 
use RubioTV\Framework\Pagination;
use RubioTV\Framework\Language\Text;

class Model
{
    protected $config;
    protected $params;
    protected $page;    
    protected $data;
    protected $link;
    protected $pagination;                    
    public function __construct($params = null)
    {        
        // Get the parameters
        $this->params   = new \stdClass;
        foreach($params as $k=>$p)
        {
            if(is_object($p) && $k === 'config'){                    
                $this->config = $p; 
            } else { 
                if(strstr($p['value'] , ':') !== false)
                {
                    $alias = $k . '_alias';
                    $parts = explode(':' , $p['value']);
                    $this->params->$k = $parts[0];
                    $this->params->$alias = $parts[1];
                } else {
                    $this->params->$k = $p['value']; 
                }
            }            
        }    

        // Get the page
        $this->page                 = Factory::getPage();   
        $this->page->title          = $this->config->sitename;                      
    }

    public function __destruct()
    {
        unset($this->page);
    }   

    public function display()
    {        
        $this->page->menu   = $this->_menu();      
        return true;                    
    }

    protected function _data()
    {                                          
        return $this->data;
    }    
    
    protected function _link()
    {
        return $this->link;   
    }    

    protected function _menu()
    {
        $menu   = $this->config->menu;
        $array  = [];

        foreach($menu as $v)
        {
                $array[$v]       = new \stdClass();
                $array[$v]->id   = $v; 
                $array[$v]->name = Text::_(strtoupper($v));
                $array[$v]->link = Factory::Link($v);
                $array[$v]->image= Factory::getAssets() . '/images/' . $v . '.png';                                       
        }           
        return $array;
    }      

    protected function _folders( $root = null)
    {
        if(empty($root))
            $root = TV_IPTV;

        $data = [];       
        if ($folders = opendir($root)) {
            while (($f = readdir($folders)) !== false) {        
                if ($f != '.' && $f != '..') {

                    $info = pathinfo($f);
                    $item = new \stdClass();
                    $item->id   = $f;  

                    if(is_dir($root . DIRECTORY_SEPARATOR. $f))
                    {
                        $item->name     = $f;
                        $item->label    = Text::_(strtoupper($item->name));                        
                        $item->link     = Factory::Link($f);

                        if(file_exists(Factory::getTheme() . DIRECTORY_SEPARATOR . 'assets/images' . DIRECTORY_SEPARATOR . $f . '.png'))
                            $item->image = Factory::getAssets() . '/images/' . $f . '.png'; 
                        else
                            $item->image = Factory::getAssets() . '/images/folder.png';                           

                    } else {
                        
                        if($info['extension'] === 'm3u')
                        {
                            $path = explode(DIRECTORY_SEPARATOR , $root);

                            $item->name     = $info['filename'];
                            $item->label    = Text::_(strtoupper($item->name));                            
                            $item->link     = Factory::Link('channels' , $path[count($path) - 1] , $item->name);

                            if(file_exists(Factory::getTheme() . DIRECTORY_SEPARATOR . 'assets/images' . DIRECTORY_SEPARATOR . $info['filename'] . '.png'))
                                $item->image = Factory::getAssets() . '/images/' . $info['filename'] . '.png';
                            else
                                $item->image = Factory::getAssets() . '/images/folder.png';
                        } else {
                            continue;
                        }
                    }
                    $data[$item->name] = $item;
                }
            }
        } 
        ksort($data);
        return $data;             
    }
             
    protected function _pagination()
    {         
        if($this->data){
            $total  = count($this->data);
            $this->page->data = array_slice($this->data , (int) $this->params->offset , (int) $this->params->limit);
            $this->pagination = new Pagination( $total , (int) $this->params->offset, (int) $this->params->limit);
            
            // Clean-up redondant parameters (join id and alias)
            $array = get_object_vars($this->params);       
            foreach($array as $key => $value)
            {
                if(isset($array[$key . '_alias']))
                {
                    $array[$key] .= ':' . $array[$key. '_alias'];
                    unset($array[$key. '_alias']);
                }
            }                  

            // Add the parameters to the pagination
            foreach($array as $key => $value)
                $this->pagination->setAdditionalUrlParam( $key ,$value);

        } else {
            $this->page->data = [];
            $this->pagination = new Pagination( 0 , (int) $this->params->offset, (int) $this->params->limit);
        }
        return $this->pagination;
    }       

    protected function _term()
    {            
        $folder  = $this->params->folder;
        $source  = $this->params->source;    
        $alias   = $this->params->source_alias;
        $term    = $this->params->term;

        $result = [];
        if($term){
            foreach($this->data as $item)
            {
                if(preg_match("/^$term/im" , $item->name , $match)){
                    if($this->params->source !== null)
                        $item->link    = Factory::Link('view', $folder , $source . ':' . $alias  , $item->id . ':' . $item->name);                          
                    else
                        $item->link    = Factory::Link('channels', $folder , $item->id . ':' . $item->name);                          

                    $result[] = $item;
                }        
            }
        } else {
            $result = null;
        }  
        return json_encode($result);   
    }   

    protected function _find( $folder , $source, $id ) 
    {        
        $m3u    = new M3U( $folder , $source);
        $array  = $m3u->load();

        if (!is_array($array))
            return false;

        return isset($array[$id]);

    }    

}

