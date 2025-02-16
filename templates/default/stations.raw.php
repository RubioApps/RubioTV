
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

?>

<div class="d-none d-lg-block">
    <?php require_once('search.php');?>
</div>

<ul class="tv-links-nav list-unstyled mb-0 mt-2 pb-3 pb-lg-2 pe-lg-2">
    <?php foreach($page->data as $k):?>
    <li class="tv-links mt-1">
        <a class="btn d-grid" href="<?= $k->link;?>">
            <div class="text-truncate">
                <img src="<?= $k->image;?>" class="me-2" width="32" data-remote="<?= $k->remote;?>" alt="<?= htmlspecialchars($k->name);?>" />
                <span><?= htmlspecialchars($k->name);?></span>
            </div>
        </a>
    </li>
    <?php endforeach;?>   
</ul>
<section>
    <?= $page->pagination->getPagesLinks(true); ?>     
</section>

<script type="text/javascript"> 
jQuery(document).ready(function($){         

    //Pagination on the sidebar has to be modified to target to the div
    $('#stations-list a.page-link').on('click', function(event){
        event.preventDefault();
        var url = $(this).attr('href');
        $.get(url , function(data){
            $("#stations-list").html(data);                
        });
    });
});
</script>