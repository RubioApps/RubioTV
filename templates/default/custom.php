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

defined('_TVEXEC') or die; 

use \RubioTV\Framework\Language\Text;
?>
<!-- Breadcrumb -->
<div class="tv-breadcrumb">
    <nav class="rounded border ms-0 me-0 mb-3" aria-label="breadcrumb">
        <ol class="breadcrumb p-1 m-0">
            <li class="breadcrumb-item">
                <a href="<?= $config->live_site;?>"><?= Text::_('HOME');?></a>
            </li>    
            <li class="breadcrumb-item" aria-current="page">
                <?= Text::_('CUSTOM')?>
            </li> 
        </ol>
    </nav>
</div>
<!-- Toolbar -->
<section class="tv-toolbar ps-lg-2 clearfix"> 
    <div id="toolbar" class="btn-group float-end me-3" role="group" aria-label="Toolbar">
        <button class="btn btn-primary bi bi-plus" data-bs-toggle="modal" href="#new-modal" id="btn-custom-new" type="button" aria-label="<?= Text::_('NEW'); ?>"></button>
    </div>
</section>
<main role="main" class="justify-content-center p-3">
    <!-- Form New List -->
    <div class="modal modal-md fade" data-bs-backdrop="static" id="new-modal" tabindex="-1" aria-labelledby="new-modal" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">                
                <div class="modal-header fw-bolder"><?= Text::_('NEW_LIST');?></div>
                <div class="modal-body">
                    <form id="new-form" action="<?= $factory->Link('custom.new');?>" method="post">
                    <div class="form-group justify-content-center pb-3">
                        <input type="text" name="listname" class="form-control" id="new-listname" aria-describedby="name-help" placeholder="<?= Text::_('NEW_LIST_TIP');?>">
                        <small id="name-help" class="form-text text-muted"><?= Text::_('NEW_LIST_DESC');?></small>                                            
                    </div>
                    <div class="form-group text-center mx-auto">
                        <button type="submit" class="btn btn-success" id="new-form-submit"><?= Text::_('SUBMIT');?></button>
                        <button type="button" class="btn btn-danger" id="new-form-close"><?= Text::_('CANCEL');?></button>
                    </div>
                    </form>
                </div>
            </div>
        </div>        
    </div>     
    <div class="tv-channels-grid row row-cols-3 row-cols-sm-4 row-cols-md-6 g-2 g-lg-3 mt-1"> 
        <?php foreach ($page->data as $item):?>   
        <div class="col">                
            <div class="card border text-center">  
                <div class="card-header p-1 d-flex">  
                    <div class="col-8">
                        <h5 class="card-title text-truncate mt-1"><?= $item->label;?></h5>
                    </div>
                    <div class="col-4 me-0">  
                        <?php if($item->type === 'system'):?>
                        <button type="button" class="btn btn-secondary bi bi-lock disabled"></button> 
                        <?php else: ?>
                        <button type="button" data-id="<?= $item->name;?>" class="btn-rem-list btn btn-danger bi bi-trash3"></button>                                 
                        <?php endif;?>                    
                    </div>    
                </div>                                    
                <div class="card-body p-0"> 
                    <a href="<?= $item->link;?>">                
                        <img src="<?= $item->image;?>" class="tv-icon card-img-top" alt="<?= htmlspecialchars($item->name);?>">                                                   
                    </a>                      
                </div>
            </div>             
        </div>
        <?php endforeach; ?>   
    </div>
</main>
<section class="container g-3 mt-5 pt-3 border-top">
    <h3><?= Text::_('IMPORT');?></h3>   
    <p><?= Text::_('IMPORT_DESC');?></p>
    <div class="pb-3">
        <select id="fld-target" class="form-select" aria-label="<?= Text::_('TARGET');?>">
            <option selected><?= Text::_('IMPORT_SELECT_TARGET');?>...</option>
            <?php 
            foreach($page->data as $f) {
                if($f->name !== 'playlist') {?>
                <option value="<?= $f->name;?>"><?= $f->label;?></option>
                <?php }
            }
            ?>
        </select>        
    </div>     
    <div class="accordion" id="import-utils">    
    <div class="accordion-item">
        <h5 class="accordion-header" id="heading-fields">         
            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#import-fields" aria-expanded="false" aria-controls="import-fields">
            <?= Text::_('IMPORT_FIELDS');?>
            </button>
        </h5>
        <div id="import-fields" class="accordion-collapse collapse" aria-labelledby="heading-fields" data-bs-parent="#import-utils">
            <div class="accordion-body">
                <form action="<?= $factory->Link('custom.add');?>" method="post">                
                    <label for="fld-name" class="m-2 form-label"><?= Text::_('NAME');?></label>
                    <input id="fld-name" name="name" class="form-control" type="text" placeholder="<?= Text::_('ENTER_NAME');?>" aria-label="<?= Text::_('ENTER_NAME');?>" />
                    <label for="fld-url" class="m-2 mt-4 form-label"><?= Text::_('URL');?></label>                    
                    <input id="fld-url" name="url" class="form-control" type="text" placeholder="<?= Text::_('ENTER_URL');?>" aria-label="<?= Text::_('ENTER_URL');?>" />
                    <label for="fld-id" class="m-2 mt-4 form-label"><?= Text::_('ID');?></label>
                    <span class="form-text">(<?= Text::_('OPTIONAL');?>)</span>                    
                    <input id="fld-id" name="id" class="form-control" type="text" placeholder="<?= Text::_('ENTER_ID');?>" aria-label="<?= Text::_('ENTER_ID');?>" />                       
                    <label for="fld-logo" class="m-2 mt-4 form-label"><?= Text::_('LOGO');?></label>
                    <span class="form-text">(<?= Text::_('OPTIONAL');?>)</span>                    
                    <input id="fld-logo" name="logo" class="form-control" type="text" placeholder="<?= Text::_('ENTER_LOGO');?>" aria-label="<?= Text::_('ENTER_LOGO');?>" />
                    <input type="submit" class="mt-3 mb-1 form-control btn btn-primary" />                    
                </form>  
            </div>
        </div>
    </div>
    <div class="accordion-item">
        <h5 class="accordion-header" id="heading-text">
            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#import-text" aria-expanded="false" aria-controls="import-text">
                <?= Text::_('IMPORT_TEXT');?>
            </button>
        </h5>
        <div id="import-text" class="accordion-collapse collapse" aria-labelledby="heading-text" data-bs-parent="#import-utils">
            <div class="accordion-body">
                <form action="<?= $factory->Link('custom.brut');?>" method="post">                    
                    <label for="fld-text" class="m-2 form-label"><?= Text::_('ENTER_TEXT');?></label>
                    <textarea id="fld-text" name="brut" class="form-control" rows="3"></textarea>
                    <input type="submit" class="mt-3 mb-1 form-control btn btn-primary" />
                </form>                 
            </div>
        </div>
    </div>
    <div class="accordion-item">
        <h5 class="accordion-header" id="heading-file">
            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#import-file" aria-expanded="false" aria-controls="import-file">
                <?= Text::_('IMPORT_FILE');?>
            </button>
        </h5>
        <div id="import-file" class="accordion-collapse collapse" aria-labelledby="heading-file" data-bs-parent="#import-utils">
            <div class="accordion-body">
                <form action="<?= $factory->Link('custom.upload');?>" method="post" enctype="multipart/form-data">
                    <input id="fld-file" name="file" class="m-2 form-control" type="file" />
                    <input type="submit" class="mt-3 mb-1 form-control btn btn-primary" />
                </form>                  
            </div>
        </div>
    </div>
    </div>
</section>
<?= $factory->getToken();?>

<!-- JS -->
<script type="text/javascript">   
jQuery(document).ready(function(){   
    $.rtv.custom.bind();
});                 
</script> 
