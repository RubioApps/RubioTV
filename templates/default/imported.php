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

use \RubioTV\Framework\Language\Text;
?>
<section class="tv-toolbar ps-lg-2 clearfix"> 
    <div id="toolbar" class="btn-group float-end me-3" role="group" aria-label="Toolbar">
        <button id="btn-add-custom" class="btn btn-success bi bi-plus-square" type="button" aria-label="<?= Text::_('ADD_CUSTOM'); ?>"></button>
        <button id="btn-rem-custom" class="btn btn-danger bi bi-x-square" type="button" aria-label="<?= Text::_('REMOVE_CUSTOM'); ?>"></button>
        <button id="btn-edt-custom" class="btn btn-warning bi bi-pencil-square" type="button" aria-label="<?= Text::_('EDIT_CUSTOM'); ?>"></button>
    </div>
</section>
  
<?php if(is_array($router->model) && count($router->model)): ?>
<!-- Favorites -->
<main role="main" class="justify-content-center">
    <div class="row row-cols-4 row-cols-md-6 row-cols-lg-10 g-2 g-lg-3 mt-1"> 
        <?php foreach ($router->model as $item):?>   
        <div class="col d-flex align-items-stretch">
            <div class="card p-3 bg-light text-center" data-link="<?= $item->link;?>">
                    <img src="<?= $item->image;?>" class="card-img-top"
                    data-remote="<?= $item->remote;?>"  
                    title="<?= htmlspecialchars($item->name);?>" 
                    alt="<?= htmlspecialchars($item->name);?>" />
                <div class="card-body d-flex flex-column">
                    <h5 class="card-title text-truncate"><?= $item->name;?></h5>                
                </div>
            </div>             
        </div>
        <?php endforeach; ?>   
    </div>
</main>
<!-- Pagination -->
<section class="container mt-3">
    <?= $router->pagination->getPagesLinks(); ?> 
</section>   

<!-- Click on boxes -->
<script type="text/javascript">   
jQuery(document).ready(function(){   
    $('.card').on('click' , function(e){
            window.location.href = $(this).attr('data-link');
    });      
});                 
</script>  

<?php else:?>
    <div class="container">
        <div class="row">
            <div class="h2"><?= Text::_('CUSTOM'); ?></div>
            <div class="p"><?= Text::_('CUSTOM_EMPTY'); ?></div>
        </div>
    </div>
<?php endif;?>