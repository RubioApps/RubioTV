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

defined('_TVEXEC') or die;

use RubioTV\Framework\Language\Text; 

?>
<section class="header-main border-bottom mb-2">
	<div class="container-fluid">
       <div class="row p-2 pt-3 pb-3 d-flex align-items-center">
            <div class="col-md-2">                
            </div>
            <div class="col-md-8">
                <div id="searchbox" class="d-flex form-inputs">
                    <input id="query" name="q" type="search" class="form-control" placeholder="<?= Text::_('SEARCH');?>">
                    <i class="bx bx-search"></i>
                </div>                
            </div>
        </div>               
	</div> 
</section>
<!-- Breadcrumb -->
<div class="tv-breadcrumb">
    <nav class="rounded border bg-light m-3" aria-label="breadcrumb">
        <ol class="breadcrumb p-2 m-0">
            <li class="breadcrumb-item">
                <a href="<?= $config->live_site;?>"><?= Text::_('HOME');?></a>
            </li>    
            <li class="breadcrumb-item">
                <a href="<?= $factory->getTaskURL($router->folder);?>"><?= Text::_(strtoupper($router->folder));?></a>
            </li>
            <li class="breadcrumb-item">
                <a href="<?= $factory->getTaskURL('channels',$router->folder , $router->source);?>"><?= Text::_(ucfirst($router->source));?></a>
            </li>            
            <li class="breadcrumb-item" aria-current="page">
                <?= Text::_('GUIDES');?>
            </li> 
        </ol>
    </nav>
</div>
<!-- Toolbar -->
<section class="tv-toolbar ps-lg-2 clearfix"> 
    <div id="toolbar" class="btn-group float-end me-3" role="group" aria-label="Toolbar">
        <a class="btn btn-primary bi bi-tv" href="<?= $factory->getTaskURL('guides');?>">
            <?= Text::_('DTV'); ?>
        </a>
        <a class="btn btn-success bi bi-bookmark" href="<?= $factory->getTaskURL('guides', 'custom', 'playlist');?>">
            <?= Text::_('PLAYLIST'); ?>
        </a>
        <a class="btn btn-warning bi bi-cloud" href="<?= $factory->getTaskURL('guides', 'custom', 'imported');?>">
            <?= Text::_('IMPORTED'); ?>
        </a>        
    </div>
</section>

<?php if(is_array($router->model) && count($router->model)): ?>
<main role="main" class="justify-content-center m-3">
    <table id="guide-table" class="table table-striped table-responsive">
        <thead>
            <tr>
                <th scope="col" colspan="2"><?= Text::_('CHANNEL');?></th>
                <th scope="col"><?= Text::_('TITLE');?></th>
                <th scope="col"><?= Text::_('START');?></th>
                <th scope="col"><?= Text::_('PROGRESS');?></th>
                <th scope="col"><?= Text::_('END');?></th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($router->model as $item):?>            
            <tr class="channel-row" data-link="<?= $factory->getTaskURL('view', $router->folder , $router->source , $item->id);?>">
                <td><img src="<?= $item->icon;?>" width="32" /></td>
                <td class="h5 text-truncate channel-name" style="max-width: 80px;"><?= $item->name;?></td>
                <td class="text-truncate" style="max-width: 220px;"><?= $item->title;?></td>
                <td><?= $item->start->format('H:i');?></td>
                <td>
                    <div class="progress">
                        <div 
                            class="progress-bar progress-bar-striped text-light" 
                            role="progressbar" 
                            style="width:<?= $item->progress;?>%"  
                            aria-valuenow="<?= $item->progress;?>" 
                            aria-valuemin="0" 
                            aria-valuemax="100">
                            <?= $item->viewed;?>
                        </div>
                    </div>                                        
                </td>
                <td><?= $item->end->format('H:i');?></td>
            </tr>
        <?php endforeach; ?>              
        </tbody>
    </table>     
</main>

<?php require_once('sourcelink.php'); ?>

<script type="text/javascript">   
jQuery(document).ready(function(){   

    // Creates a new jQuery selector non case sensitive
    $.expr[':'].icontains = function(a, i, m) {
        return jQuery(a).text().toUpperCase().indexOf(m[3].toUpperCase()) >= 0;
    };

    $('.channel-row').on('click' , function(e){
        window.location.href = $(this).attr('data-link');
    });     

    $('#query').on('input' , function(){
        $('#guide-table td.channel-name:icontains("' + $(this).val() + '")').parent().show();
        $('#guide-table td.channel-name:not(:icontains("' + $(this).val() + '"))').parent().hide();
    });
});                 
</script>  

<?php else:?>
    <div class="container">
        <div class="row">
            <div class="p"><?= Text::_('GUIDE_EMPTY'); ?></div>
        </div>
    </div>
<?php endif;?>