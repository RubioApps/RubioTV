<?php
/**
 +-------------------------------------------------------------------------+
 | RubioTV  - A domestic IPTV Web app browser                              |
 | Version 1.0.0                                                           |
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

use RubioTV\Framework\IPTV; 
use RubioTV\Framework\EPG; 
use RubioTV\Framework\Helpers; 
use RubioTV\Framework\Pagination; 
use RubioTV\Framework\Language\Text; 
           
class Router
{   
    public $config;    
    public $task;
    public $action;    
    public $folder;    
    public $source; 
    public $sourcename;    
    public $sourcelink;   
    public $id;
    public $format;   
    public $offset;   
    public $limit;    
    public $term;   
    public $model;    
    public $list;   
    public $pagination;      
    public $pagetitle;   
    public $epg;

    public function __construct( $params )
    {        
        foreach($params as $k=>$p)
        {
            if(property_exists($this , $k)){
                if(is_array($p) && isset($p['value'])){
                    $this->$k = $p['value']; 
                } 
                if(is_object($p) && $k == 'config'){                    
                    $this->$k = $p; 
                }
            }
        }          
        $this->pagetitle  = $this->config->sitename;

        if(isset($this->source)){
            $parts = explode(':',$this->source,2);
            if(count($parts)==2){
                $this->source = $parts[0];
                $this->sourcename = $parts[1];
            }
        }
        
    }

    public function __destruct() {
        //print "Destroying " . __CLASS__ . "\n";
    }    

    public function dispatch()
    {   
        $func = 'get' . ucfirst(Factory::getTask());              
        if(method_exists($this , $func)){
            $this->model = $this->$func(); 
        } else {
            Factory::setPage(null);
        }
        return TV_THEMES . DIRECTORY_SEPARATOR . $this->config->theme . DIRECTORY_SEPARATOR . 'index.php';
    }

    public function getPageTitle()    
    {    
        return $this->pagetitle;
    }

    public function getHome()
    {
        $this->pagetitle    = Text::_('HOME');
        return $this->getMenu();
    }

    public function getFolders( $root = null)
    {
        if(empty($root))
            $root = TV_IPTV;

        $data = [];       
        if ($folders = opendir($root)) {
            while (($f = readdir($folders)) !== false) {        
                if ($f != '.' && $f != '..') {

                    $item = new \stdClass();
                    $item->id   = $f;                    

                    if(is_dir($root . DIRECTORY_SEPARATOR. $f))
                    {
                        $item->name = Text::_(strtoupper($f));
                        $item->link = $this->config->live_site . '/?task=' . $f;
                        $item->image = Factory::getAssets() . '/images/' . $f . '.png';                           
                    } else {
                        $info = pathinfo($f);
                        if($info['extension'] === 'm3u')
                        {
                            $path = explode(DIRECTORY_SEPARATOR , $root);
                            $item->name = Text::_(strtoupper($info['filename']));
                            $item->link = Factory::getTaskURL('channels' , $path[count($path) - 1] , $info['filename']);
                            $item->image = Factory::getAssets() . '/images/' . $info['filename'] . '.png';                       
                        } else {
                            continue;
                        }
                    }
                    $data[$f] = $item;
                }
            }
        } 
        ksort($data);
        return $data;
    }

    public function getMenu()
    {
        $menu       = $this->config->menu;
        $data       = [];

        // IPTV folders
        $folders    = $this->getFolders();
        foreach($menu as $e){
            if(array_key_exists($e , $folders))
                $data[$e] = $folders[$e];
        }
        // EPG
        $data['guides'] = new \stdClass();
        $data['guides']->id   = 'guides';    
        $data['guides']->name = Text::_('GUIDES');
        $data['guides']->link = Factory::getTaskURL('guides');
        $data['guides']->image = Factory::getAssets() . '/images/guides.png';         
        return $data;
    }

    public function getCategories()
    {
        $this->pagetitle    = Text::_('CATEGORIES');
        $data               = IPTV::getCategories();
        $this->sourcelink   = IPTV::getURL();

        if($this->action == 'search' && $this->format === 'json'){            
            header('Content-Type: application/json; charset=utf-8');    
            echo $this->_searchTerm($data);
            exit(0);            
        }
        $this->_buildPagination($data);
        return $this->list;     
    } 

    public function getCountries()
    {
        $this->pagetitle    = Text::_('COUNTRIES');
        $data               = IPTV::getCountries();
        $this->sourcelink   = IPTV::getURL();

        if($this->action == 'search' && $this->format==='json'){   
            header('Content-Type: application/json; charset=utf-8');    
            echo $this->_searchTerm($data);
            exit(0);                
        }
        $this->_buildPagination($data);        
        return $this->list;     
    }

    public function getLanguages()
    {
        $this->pagetitle    = Text::_('LANGUAGES');
        $data               = IPTV::getLanguages();
        $this->sourcelink   = IPTV::getURL();

        if($this->action == 'search' && $this->format==='json'){
            header('Content-Type: application/json; charset=utf-8');    
            echo $this->_searchTerm($data);
            exit(0);
        }
        $this->_buildPagination($data);          
        return $this->list;     
    }

    public function getChannels( $all = false)
    {
        // Force resync from iptv-org
        if($this->folder !== 'dtv' && $this->folder !== 'custom'  && $this->action === 'sync' && $this->format==='json')
        {            
            $this->_resyncChannel();
            return;
        }   

        if(!empty($this->sourcename))
            $this->pagetitle = ucfirst($this->sourcename);
        else
            $this->pagetitle = Text::_(ucfirst($this->folder)) . ' - ' . ucfirst($this->source);
        
        switch($this->folder){
            case 'countries':
            case 'categories':                
            case 'languages':                
                $url = IPTV::getSource($this->folder , $this->source);
                break;  
            case 'custom':
                $url = $this->config->live_site . '/iptv/' . $this->folder  . '/' . $this->source . '.m3u';
                break;
            case 'dtv':                 
                $url = $this->config->dtv['host'] . $this->config->dtv['channels'];
                break;
            default:                              
                Factory::sendError( Text::_('ERROR') , Text::_('ERROR_FOLDER'));
                return false;
        }  

        $this->sourcelink = $url;

        // Get the list of channels from the m3u file
        $data = Helpers::getChannelsFromFile($url , $this->folder , $this->source);

        if(!$data){
            Factory::sendError( Text::_('ERROR') , Text::_('ERROR_FOLDER'));
            return false;
        };

        // If there is a search
        if($this->action == 'search' && $this->format == 'json'){    
            header('Content-Type: application/json; charset=utf-8');    
            echo $this->_searchTerm($data);
            exit(0);
        }  

        // Build pagination
        $this->_buildPagination($data);  
        return $all ? $data : $this->list;     

    }

    public function getView( $id = null)
    {      
        if(empty($id))
            $id = $this->id;

        switch($this->action)
        {
            case 'cron':
                $result = [
                    'success'   => false,
                    'title'     => Text::_('GUIDE'),
                    'content'   => ''
                ];
                if($this->format === 'json' && isset($_POST['key']))
                {                             
                    $this->epg    = new EPG();                                                    
                    if($this->epg->Unlock( $_POST['key'] ))
                    {    
                        $result['success'] = $this->epg->Cron($id);
                    }                                       
                }
                if($result['success']){
                    $result['title'] = Text::_('SUCCESS');
                    $result['content'] = Text::_('CRON_SUCCESS');
                } else {
                    $result['title'] = Text::_('ERROR');
                    $result['content'] = Text::_('CRON_ERROR');
                }
                header('Content-Type: application/json; charset=utf-8');    
                echo json_encode($result);                  
                exit(0);                
        }               

        $data = $this->getChannels(true);
        $item = new \stdClass();     
        foreach ($data as $k) 
        { 
            if($k->id === $this->id) {                
                $item = $k;
                break;
            }
        }              

        // Check the channel exists
        if(!isset($item->url)) {
            Factory::sendError( Text::_('ERROR') , Text::_('ERROR_FOLDER'));
            Factory::setPage('404.php');    
            return false;            
        } 

        // We have valid source!
        $this->sourcelink   = $item->url;

        // Does the URL channel exist?
        if(Helpers::channelExists($item))
        {
            // For IPTV sources (non DTV)
            if(strstr($item->url , $this->config->dtv['host']) === false)
            {
                // Get a temporary key to unlock the cron for EPG
                $this->epg  = new EPG();
                $item->epg_key = $this->epg->getCronId();
                $url = $this->epg->getXMLTV($item->id , $item->tvg_id);

                //Notify EPG pending or missing
                if((bool) $this->config->epg['notify'])
                {
                    if($url === true)
                        Factory::sendSuccess( Text::_('GUIDES') , 'Pending XMLTV for ' . $item->tvg_id ); 
                    elseif($url === false)
                        Factory::sendMessage( Text::_('GUIDES') , 'Missing XMLTV from ' . $item->tvg_id , 'info');  
                    else
                        Factory::DoNothing(); 
                } 
            // For DTV, take the fixed url by the config
            } else {
                $url = $this->config->dtv['host'] . $this->config->dtv['xmltv'];
            }
        // Channel does not exist
        } else {
            Factory::setPage('404.php');  
            Factory::sendError( Text::_('ERROR') , Text::_('ERROR_SOURCE'));                              
            header('Refresh: 3;url=' . Factory::getTaskURL('channels', $this->folder, $this->source));                      
            return false;
        } 
 
        // If a URL is set
        if(isset($url)) 
        {
            if(filter_var($url, FILTER_VALIDATE_URL))
            {
                $this->epg  = new EPG($url); 
                list($item->guide , $item->playing) = $this->epg->getGuide($item->tvg_id);
            } else {                    
                list($item->guide , $item->playing) = array(null ,null);
            }
        }

        //Set the page title        
        $this->pagetitle = $item->name;          
        return $item;
    }

    public function getGuides()
    {
        if(!$this->folder)
            $this->folder = 'dtv';

        if(!$this->source)
            $this->source = $this->config->dtv['filename'];            

        $this->epg = new EPG(); 
        if(!$this->epg->MergeXMLTV( $this->folder , $this->source))
            return false;        
        
        $this->list =  $this->epg->getPlayingNow();        

        $this->pagetitle= Text::_('GUIDES');
        $this->sourcelink = $this->config->live_site . '/guides/' . $this->folder . '.' . $this->source . '.xmltv';

        return $this->list;     
    }

    public function getCustom()
    {
        if($this->action){                      
            switch($this->action){
                case 'add':
                case 'brut':                    
                case 'upload':
                    $result = Helpers::addImported($this->action);                        
                    switch($result)
                    {
                        case ERR_IMPORT_NONE:
                            Factory::sendSuccess( Text::_('IMPORT') , Text::_('IMPORT_SUCCESS'));
                            break;      
                        case ERR_IMPORT_EMPTY_FIELD:
                            Factory::sendError( Text::_('IMPORT') , Text::_('IMPORT_EMPTY_FIELD'));
                            break;                                                         
                        case ERR_IMPORT_INVALID_URL:
                            Factory::sendError( Text::_('IMPORT') , Text::_('IMPORT_INVALID_URL'));
                            break;                                                         
                        case ERR_IMPORT_INVALID_FILE:
                            Factory::sendError( Text::_('IMPORT') , Text::_('IMPORT_INVALID_FILE'));
                            break;                             
                        case ERR_IMPORT_ANY:                            
                            Factory::sendError( Text::_('IMPORT') , Text::_('IMPORT_ERROR'));
                            break;      
                    }
                    break;

                case 'edit':                                        
                    if(isset($_POST['remove']) && isset($_POST['ids']))
                    {                      
                        if (!is_array($_POST['ids']))
                            $list = array($_POST['ids']);
                        else
                            $list = $_POST['ids'];
                      
                        // The POST['id'] is an array of items encoded with base64
                        // item = base64_encode( id . chr(0) . url)

                        $success = Helpers::removeChannels( $list , 'custom', $this->source);
                        header('Content-Type: application/json; charset=utf-8');    
                        echo json_encode(['error' => !$success]);
                        exit(0);                        
                    }
                    return $this->_editCustomSource();  
                                        
                case 'edit.search': 
                    return $this->_editCustomSource();  
            }             
        }  
        $this->pagetitle= Text::_('CUSTOM');
        $folders = $this->getFolders(TV_IPTV . DIRECTORY_SEPARATOR . 'custom');        
        return $folders;   
    }

    private function _editCustomSource()
    {
        
        Factory::setPage('edit.php');   
        $this->pagetitle= Text::_( $this->source);
        $url = $this->config->live_site . '/iptv/custom/' . $this->source . '.m3u';
        $data = Helpers::getChannelsFromFile($url , 'custom' , $this->source);

        if($this->format==='json'){    
            header('Content-Type: application/json; charset=utf-8');    
            echo $this->_searchTerm($data);
            exit(0);                    
        }

        $this->_buildPagination($data);  
        return $this->list;   
    }

    public function getPlaylist()
    {
        if($this->action && $this->id){           
            $item = $this->getView();
            switch($this->action){
                case 'add':
                    Helpers::addToPlaylist($item);
                    exit(0);
                case 'remove':
                    Helpers::removeFromPlaylist($item);      
                    exit(0);
                case 'edit':
                    Factory::setPage('edit.php');                
            }             
        }               

        $this->pagetitle= Text::_('PLAYLIST');
        $url = $this->config->live_site . '/iptv/custom/playlist.m3u';
        $data = Helpers::getChannelsFromFile($url , 'custom' , 'playlist');
        $this->sourcelink = $url;

        if($this->action == 'search' && $this->format==='json'){    
            header('Content-Type: application/json; charset=utf-8');    
            echo $this->_searchTerm($data);
            exit(0);
        }
        $this->_buildPagination($data);  
        return $this->list;             
    }


    public function getImported()
    {              
        if($this->action){           
            switch($this->action){
                case 'edit':
                    Factory::setPage('edit.php');
            }             
        }     

        $this->pagetitle= Text::_('IMPORTED');
        $url = $this->config->live_site . '/iptv/custom/imported.m3u';
        $data = Helpers::getChannelsFromFile($url , 'custom' , 'imported');        

        if($this->action == 'search' && $this->format==='json'){    
            header('Content-Type: application/json; charset=utf-8');    
            echo $this->_searchTerm($data);
            exit(0);
        }
        $this->_buildPagination($data);  
        return $this->list;   
    }    
    public function getLoadRemoteImage()
    {
        $url    = urldecode($_GET['url']);
        $id     = $_GET['id'];
        $image  =  Helpers::loadRemoteImage($url , $id);

        $data   = [];  
        $data['url']        = $url;
        $data['id']         = $id;
        $data['timestamp']  = microtime();

        if($image)
        {
            $data['logo']   = $image;
            $data['message'] = Text::_('CACHE_SUCCESS');
            $data['error'] = false;
        } else{
            $data['logo'] = Factory::getAssets() . '/images/notfound.png';
            $data['message'] = Text::_('CACHE_ERROR');
            $data['error'] = true;
        }
        header('Content-Type: application/json; charset=utf-8');    
        echo json_encode($data);
        exit(0);                
    }

    /**
     * Download and refresh the source from the remote folders
     */
    private function _resyncChannel()
    {           
        $filename = TV_IPTV . DIRECTORY_SEPARATOR . $this->folder . DIRECTORY_SEPARATOR . $this->source . '.m3u';

        $data = [];            
        $data['filename'] = $filename;
        $data['title'] = Text::_('RESYNC');

        if($this->sourcename) $this->source .= ':' . $this->sourcename;            
        $data['url'] = $this->config->live_site . '/?task=channels&folder=' . $this->folder . '&source=' . $this->source ; 

        if(file_exists($filename))   
            $data['error'] = unlink( $filename );         
        else
            $data['error'] = true;

        $data['content'] = $data['error'] === true ? Text::_('RESYNC_SUCCESS') : Text::_('RESYNC_ERROR');            

        header('Content-Type: application/json; charset=utf-8');    
        echo json_encode($data);
        exit(0);                  
    }

    /**
     * Build the pagination for the channels view
     */
    private function _buildPagination( $data )
    {
        if($data){
            $total = count($data);
            $this->list = array_slice($data , $this->offset , $this->limit);

            $this->pagination = new Pagination( $total , $this->offset, $this->limit);
            $this->pagination->setAdditionalUrlParam('task',$this->task);
            $this->pagination->setAdditionalUrlParam('folder', $this->folder);
            $this->pagination->setAdditionalUrlParam('source', $this->source . (!empty($this->sourcename) ? ':' . $this->sourcename :''));
            $this->pagination->setAdditionalUrlParam('limit', $this->limit);  
        } else {
            $this->list = array();
            $this->pagination = new Pagination( 0 , $this->offset, $this->limit);
        }
    }

    private function _searchTerm( $data )
    {                
        $result = array();
        $term  = $this->term;
        if($term){
            foreach($data as $item){
                if(preg_match("/^$term/i" , $item->name , $match)){
                    $result[] = $item;
                }        
            }
        } else {
            $result = $data;
        }  
        return json_encode($result);   
    }

    private function _sortList($data , $index)
    {
        $ret = [];
        foreach($data as $item){
            if($item->$index){

            }
        }
    }
          
}
