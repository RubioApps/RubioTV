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

defined('_TVEXEC') or die;

use RubioTV\Framework\Language\Text;

?>
<header class="navbar navbar-expand-lg navbar-dark bg-dark tv-navbar sticky-top">  
  <nav class="container-lg flex-wrap flex-lg-nowrap">  
    <div class="d-flex">  
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainmenu" aria-controls="mainmenu" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
      </button>
    </div>    
    <a class="navbar-brand tv-brand text-center text-truncate" href="<?= $factory->Link();?>">
      <div class="h3 fw-bold"><?= $config->sitename; ?></div>
    </a>   
    <?php if($factory->getTask() === 'view'):?>
    <div class="tv-navbar-toggler">      
      <button class="navbar-toggler" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebar" aria-controls="sidebar" aria-label="Toggle Channels">
        <div class="h1 align-top top p-0">...</div>
      </button>      
    </div>
    <?php endif;?>    
    <div class="collapse navbar-collapse" id="mainmenu">
      <ul class="navbar-nav me-auto mb-2 mb-lg-0"> 
        <?php 
        foreach($page->menu as $f)
        {
          $task   = $factory->getParam('task');  
          $folder = $factory->getParam('folder');   
          $active = ($task === $f->id) || ($task !== 'guides' && $folder === $f->id);      
        ?> 
        <li class="nav-item">
          <a class="nav-link<?= ($active  ? ' active':'');?>" href="<?= $factory->Link($f->id);?>">
            <?= $f->name; ?>
          </a>
        </li>  
        <?php } ?>    
        <?php if($factory->isLogged() && !$factory->autoLogged()):?> 
          <li class="nav-item">
            <a class="nav-link" href="<?= $factory->Link('login.off'); ?>"><?= Text::_('LOGOUT'); ?></a>
          </li>                   
        <?php endif;?>
      </ul>
    </div>    
  </nav>
</header>
