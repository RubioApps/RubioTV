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
           
class Router
{   
    protected $params;     
    protected $model;   
    protected $format; 

    public function __construct( &$params )
    {     
        $this->params = &$params;
    }

    public function __destruct() 
    {
        
    }   

    public function dispatch()
    {   
        $config =   Factory::getConfig();
        $task   =   Factory::getTask();
        $page   =   Factory::getPage();
        $format =   Factory::getParam('format');         
        $action =   Factory::getParam('action');

        if(file_exists(TV_MODELS . DIRECTORY_SEPARATOR . strtolower($task) . '.php'))
        {               
            
            require_once(TV_MODELS . DIRECTORY_SEPARATOR . strtolower($task) . '.php');

            $classname  = '\RubioTV\Framework\model' . ucfirst($task);       
            $func       = $action ?? 'display';

            if(class_exists($classname) && method_exists($classname , $func))
            {
                $this->model = new $classname ($this->params);                
                if($this->model->$func() === false)
                    $this->_notfound();
            } else {
                $this->_notfound();
            }        
        } else {
            $this->_notfound();
        }

        if($format === 'raw')
            return TV_THEMES . DIRECTORY_SEPARATOR . $config->theme . DIRECTORY_SEPARATOR . Factory::getTask() . '.raw.php';
        else
            return TV_THEMES . DIRECTORY_SEPARATOR . $config->theme . DIRECTORY_SEPARATOR . 'index.php';
    }

    protected function _notfound()
    {
        $page   =   Factory::getPage();
        $this->model = new \RubioTV\Framework\Model($this->params);             
        $this->model->display();
        $page->setFile(null);                
    }
   
     
}