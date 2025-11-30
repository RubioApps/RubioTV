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

use RubioTV\Framework\Language\Text;
?>

<main role="main" class="justify-content-center p-3">
    <section class="d-sm-none mt-2 mb-5">
        <?php foreach ($page->menu as $item): ?>
            <div class="row row-cols-2 p-2 g-2 mb-1">            
                <div class="col-2">
                    <img src="<?= $item->image; ?>" class="img-fluid tv-icon" title="<?= htmlspecialchars($item->name); ?>" alt="<?= htmlspecialchars($item->name); ?>">
                </div>
                <div class="col-10">
                    <a href="<?= $item->link; ?>">                            
                        <p class="fs-3 text-truncate"><?= $item->name; ?></p>
                    </a>
                </div>                        
            </div>
        <?php endforeach; ?>
    </section>
    <section class="d-none d-sm-inline mt-2 mb-5">
        <div class="tv-channels-grid row row-cols-3 row-cols-sm-4 row-cols-md-6 g-2 g-lg-3 mt-1">
            <?php foreach ($page->menu as $item): ?>
                <div class="col">
                    <div class="card p-3 border text-center">
                        <a href="<?= $item->link; ?>">
                            <img src="<?= $item->image; ?>" class="tv-icon card-img-top" title="<?= htmlspecialchars($item->name); ?>" alt="<?= htmlspecialchars($item->name); ?>">
                            <div class="card-body p-0">
                                <h5 class="card-title text-truncate"><?= $item->name; ?></h5>
                            </div>
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <?php if($page->data['playing']):?>
    <section class="mt-2 mb-5">
        <div class="h3 mt-5 mb-3"><?= Text::_('NOW'); ?>...</div>
        <div class="owl-carousel tv-slider d-none">
            <?php foreach ($page->data['playing'] as $row): ?>
                <div class="card mb-3">
                    <div class="card-header fs-2" data-icon="<?= $row->icon;?>"><?= $row->name; ?></div>
                    <div class="row g-0">
                        <div class="col-md-5 tv-snapshot" <?= $row->getshot ? 'data-url="' . $row->url . '"': '';?> >
                            <a href="<?= $row->link;?>">
                            <img src="<?= $row->snapshot;?>" class="img-fluid h-100 mx-auto" />
                            </a>
                        </div>
                        <div class="col-md-7">                            
                            <div class="card-body">
                                <h5 class="card-title fs-4 text-truncate"><?= $row->title; ?></h5>
                                <p class="card-text">
                                    <small class="text-muted fs-6"><?= date_format($row->start,'H:i'); ?>-<?= date_format($row->end,'H:i');?></small>
                                </p>
                                <div class="card-text">
                                    <div class="tv-guide-subtitle"><?= $row->subtitle; ?></div>
                                    <div class="float-end">
                                        <a class="btn bg-primary text-white mt-2 mb-2" href="<?= $row->link;?>"><?= Text::_('WATCH');?></a>
                                    </div>                               
                                </div>                                                                
                            </div>                         
                        </div>                        
                    </div>                   
                </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif;?>
</main>

<!-- Carousel -->
<script type="text/javascript">
    jQuery(document).ready(function() {

        $.getScript('<?= $factory->getAssets() . '/owl.carousel.min.js'; ?>', function() {

            $('<style type="text/css"></style>')
                .html('@import url("<?= $factory->getAssets() . '/owl.carousel.min.css'; ?>")')
                .appendTo('head');

            const carousel = $('.tv-slider');
            carousel.removeClass('d-none');           

            //Preserve 16:9
            $('.tv-snapshot').each(function(){
                $(this).height($(this).width() * 9/16 - 10);
            });

            carousel.owlCarousel({
                center: false,
                items: 1,
                loop: false,
                stagePadding: 5,
                margin: 15,
                smartSpeed: 500,
                autoplay: false,
                nav: false,
                dots: true,
                pauseOnHover: true
            });

            carousel.find('.owl-item.active').each( function(){
                const item = $(this).find('.tv-snapshot').first();
                let url = item.attr('data-url');
                if(url) $.rtv.snapshot(item);             
            });

            $('.card-header').each( function(i) {
                const uri = $(this).attr('data-icon');
                const icon = $('<img></img>');
                icon
                    .attr('src',uri)
                    .css('width', $(this).height())
                    .addClass('img-fluid float-start me-3 my-auto')
                    .prependTo($(this));
            });
        });
    });
</script>