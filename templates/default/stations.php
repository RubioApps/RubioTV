<?php 
/**
 +-------------------------------------------------------------------------+
 | RubioTV  - A domestic IPTV Web app browser                              |
 | Version 1.5.1                                                           |
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
                <a href="<?= $factory->Link('radio');?>"><?= Text::_('RADIO');?></a>
            </li>            
            <li class="breadcrumb-item" aria-current="page">
                <?= Text::_('GROUPS')[strtoupper($page->source)] ?? SEF::decode($page->source_alias);?>
            </li> 
        </ol>
    </nav>
</div>

<?php if(is_array($page->data) && count($page->data)): ?>
<!-- Toolbar -->
<section class="tv-toolbar ps-lg-2 clearfix"> 
    <div id="toolbar" class="btn-group float-end me-3" role="group" aria-label="Toolbar">
        <button id="btn-radio-edit" class="btn btn-primary bi bi-pencil" type="button" aria-label="<?= Text::_('EDIT'); ?>"></button>
    </div>
</section>
<!-- Stations -->
<main role="main" class="justify-content-center p-3">
    <div class="tv-stations-grid row row-cols-2 row-cols-sm-4 row-cols-md-6 row-cols-lg-8 g-2 g-lg-3 mt-1">
        <?php foreach ($page->data as $item):?>   
        <div class="col d-flex align-items-stretch">
            <div class="card p-3 border tv-bg-<?= $page->params['mode'];?> justify-content-center mx-auto w-100">
                <a class="text-center" href="<?= $item->link;?>">
                <img src="<?= $item->image;?>" class="card-img-top mx-auto spinner-border" data-remote="<?= $item->remote;?>" alt="<?= htmlspecialchars($item->name);?>" />
                <div class="card-body p-0 text-center">                 
                    <div class="card-title">
                        <div class="card-text text-truncate"><?= $item->name;?></div>
                    </div>
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

<?php else:?>
    <?php require_once('404.php');?>
<?php endif;?>

<!-- JS -->
<script type="text/javascript">   
jQuery(document).ready(function(){   

$('#btn-radio-edit').on('click',function(e){
        e.preventDefault();
        document.location.href = '<?= $factory->Link( 'radio.edit', $page->folder, $page->source);?>';
    });    

});                 
</script> 