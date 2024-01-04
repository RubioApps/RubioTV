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
?>

<?php if(is_array($router->model) && count($router->model)): ?>

<?php require_once('search.php');?> 
<!-- Countries -->
<main role="main" class="p-3 tv-folder-grid">
    <div class="row row-cols-3 row-cols-md-5 row-cols-lg-6 g-2 g-lg-3 mt-1"> 
    <?php foreach ($router->model as $item):?>
        <div class="col ps-1 pe-1">            
            <a class="btn btn-light border d-grid" href="<?= $factory->getTaskURL('channels','countries', strtolower($item->code . ':' . $item->name));?>">
                <h6 class="text-truncate m-1">
                    <span class="me-1"><?= $item->flag;?></span>
                    <?= $item->name;?>
                </h6>
            </a>
        </div>
    <?php endforeach; ?>   
    </div>
</main>
<!-- Pagination -->
<section class="container mt-3">
    <?= $router->pagination->getPagesLinks(); ?> 
</section>

<?php require_once('sourcelink.php');?>

<?php else:?>
    <?php require_once('404.php');?>
<?php endif;?>
