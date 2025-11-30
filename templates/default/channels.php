<?php 
/**
 +-------------------------------------------------------------------------+
 | RubioTV  - A domestic IPTV Web app browser                              |
 | Version 1.6.1                                                          |
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

use \RubioTV\Framework\SEF;
use \RubioTV\Framework\Language\Text;
?>

<?php require_once('search.php'); ?>

<!-- Breadcrumb -->
<div class="tv-breadcrumb">
    <nav class="rounded m-3" aria-label="breadcrumb">
        <ol class="breadcrumb p-2 m-0">
            <li class="breadcrumb-item">
                <a href="<?= $config->live_site;?>"><?= Text::_('HOME');?></a>
            </li>    
            <li class="breadcrumb-item">
                <a href="<?= $factory->Link($page->folder);?>"><?= Text::_(ucfirst($page->folder));?></a>
            </li>
            <li class="breadcrumb-item" aria-current="page">
                <?= Text::_('GROUPS')[strtoupper($page->source)] ?? SEF::decode($page->source_alias);?>
            </li> 
        </ol>
    </nav>
</div>

<!-- Toolbar -->
<section class="tv-toolbar ps-lg-2 clearfix"> 
    <div id="toolbar" class="btn-group float-end me-3" role="group" aria-label="Toolbar">
        <button id="btn-guide" class="btn btn-warning bi bi-clock" type="button" aria-label="<?= Text::_('GUIDES'); ?>"></button>        
        <?php if($page->folder !== 'dtv' && $page->folder !== 'custom'):?>
        <button id="btn-sync" class="btn btn-primary bi bi-arrow-repeat" type="button" aria-label="<?= Text::_('RESYNC'); ?>"></button>
        <?php else:?>
            <?php if($page->folder === 'custom'):?>
            <button id="btn-custom-edit" class="btn btn-primary bi bi-pencil" type="button" aria-label="<?= Text::_('EDIT'); ?>"></button>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</section>


<?php if(is_array($page->data) && count($page->data)): ?>
<!-- Channels -->
<main role="main" class="justify-content-center p-3">
    <div class="tv-channels-grid row row-cols-2 row-cols-sm-4 row-cols-md-6 row-cols-lg-8 g-2 g-lg-3 mt-1">
        <?php foreach ($page->data as $item):?>   
        <div class="col d-flex align-items-stretch">
            <div class="card p-3 border tv-bg-<?= $page->params['mode'];?> justify-content-center mx-auto w-100">
                <a class="text-center" href="<?= $item->link;?>">
                <img src="<?= $item->image;?>" class="card-img-top mx-auto spinner-border" data-remote="<?= $item->remote;?>" alt="<?= htmlspecialchars($item->name);?>" />
                <div class="card-body p-0 text-center">                 
                    <h5 class="card-title text-truncate">
                        <div class="card-text"><?= $item->name;?></div>
                    </h5>
                </div>
                </a>
            </div>             
        </div>
        <?php endforeach; ?>   
    </div>
</main>
<!-- Pagination -->
<section class="container mt-3">
    <?= $page->pagination->getPagesLinks(); ?> 
</section>    

<?php require_once('link.php');?>

<!-- JS -->
<script type="text/javascript">   
jQuery(document).ready(function(){   
    
    $('#btn-sync').on('click',function(e){
        e.preventDefault();
        const url = '<?= $factory->Link('channels.sync',null,null,null,'format=json');?>';
        $.getJSON( url , function(data){  
            const wrapper = $('#tv-toast');
            const toast   = wrapper.find('.toast').first().clone();
            toast.find('.toast-body').html( '<strong>' + data.title + '</strong><br />' + data.content);
            toast.addClass('bg-success');
            toast.appendTo($('body'));

            if(bootstrap){
                const tbs = bootstrap.Toast.getOrCreateInstance(toast.get(0));
                tbs.show();
            } else {
                toast.show();
            }
            setTimeout( function(){location.reload();}, 3000);                                                           
        });         
    });

    $('#btn-custom-edit').on('click',function(e){
        e.preventDefault();
        document.location.href = '<?= $factory->Link( 'custom.edit', $page->folder, $page->source);?>';
    });    

    $('#btn-guide').on('click',function(e){
        e.preventDefault();
        document.location.href = '<?= $factory->Link( 'guides', $page->folder, $page->source);?>';
    });   

});                 
</script> 
<?php else:?>
    <?php require_once('404.php');?>
<?php endif;?>