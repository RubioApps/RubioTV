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
defined('_TVEXEC') or die; ?>

<main role="main" class="justify-content-center p-3">
    <div class="tv-channels-grid row row-cols-3 row-cols-sm-4 row-cols-md-6 g-2 g-lg-3 mt-1"> 
        <?php foreach ($router->model as $item):?>   
        <div class="col">
            <div class="card p-3 border bg-light text-center" data-link="<?= $item->link;?>">
                <img src="<?= $item->image;?>" class="card-img-top" title="<?= htmlspecialchars($item->name);?>" alt="<?= htmlspecialchars($item->name);?>">
                <div class="card-body p-0">
                    <h5 class="card-title text-truncate"><?= $item->name;?></h5>
                </div>
            </div>             
        </div>
        <?php endforeach; ?>   
    </div>
</main>

<!-- Click on box -->
<script type="text/javascript">   
jQuery(document).ready(function(){   
    $('.card').on('click' , function(e){
            window.location.href = $(this).attr('data-link');
    });

});                 
</script>  
