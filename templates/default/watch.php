<?php

/**
 +-------------------------------------------------------------------------+
 | RubioTV  - A domestic IPTV Web app browser                              |
 | Version 1.5.1                                                           |
 |                                                                         |
 | Copyright (C) The Roundcube Dev Team                                    |
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

use RubioTV\Framework\SEF;
use RubioTV\Framework\Language\Text;

?>
<div class="tv-layout justify-content-center p-3">
    <!-- List of Channels -->
    <aside class="tv-sidebar">
        <div class="offcanvas-lg offcanvas-end" id="sidebar" aria-labelledby="channelsOffcanvasLabel">
            <div class="offcanvas-header border-bottom">
                <h5 class="offcanvas-title" id="channelsOffcanvasLabel"><?= Text::_('CHANNELS'); ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close" data-bs-target="#sidebar"></button>
            </div>
            <div class="offcanvas-body p-0">
                <nav id="channels-list" class="tv-links tv-folder-grid p-1 w-100">
                </nav>
            </div>
        </div>
    </aside>
    <main class="tv-main order-1">
        <!-- Wait -->
        <div class="modal modal-md fade" data-bs-backdrop="static" id="wait" tabindex="-1" aria-labelledby="wait" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header fw-bolder"><?= Text::_('EPG_UPDATE'); ?></div>
                    <div class="modal-body">
                        <p><?= Text::_('CRON_WAIT'); ?></p>
                        <div class="d-flex justify-content-center">
                            <div class="spinner-border spinner-border-lg" role="status"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Breadcrumb -->
        <div class="tv-breadcrumb">
            <nav class="rounded ms-0 me-0 mb-3" aria-label="breadcrumb">
                <ol class="breadcrumb p-1 m-0">
                    <li class="breadcrumb-item">
                        <a href="<?= $config->live_site; ?>"><?= Text::_('HOME'); ?></a>
                    </li>
                    <li class="breadcrumb-item">
                        <a href="<?= $factory->Link($page->folder); ?>">
                            <?= Text::_(strtoupper($page->folder)); ?>
                        </a>
                    </li>
                    <li class="breadcrumb-item">
                        <a href="<?= $factory->Link('channels', $page->folder, $page->source . ':' . $page->source_alias); ?>">
                            <?= Text::_('GROUPS')[strtoupper($page->source)] ?? ucfirst($page->source_alias); ?>
                        </a>
                    </li>
                    <li class="breadcrumb-item d-none d-md-block" aria-current="page"><?= $page->data->name; ?></li>
                </ol>
            </nav>
        </div>
        <!-- Toolbar -->
        <div class="tv-toolbar ps-lg-2 clearfix mb-3">
            <!-- DTV -->
            <?php if (strstr($page->data->url, $config->dtv['host']) !== false) : ?>
                <div id="toolbar" class="btn-group float-end me-3" role="group" aria-label="DTV Toolbar">
                    <button class="btn btn-secondary bi bi-share" id="btn-share" type="button" aria-label="<?= Text::_('SHARE'); ?>"></button>
                    <button class="btn btn-success bi bi-bookmark-plus d-none" id="btn-add-fav" type="button" aria-label="<?= Text::_('FAV_ADD'); ?>"></button>
                    <button class="btn btn-danger bi bi-bookmark-dash d-none" id="btn-rem-fav" type="button" aria-label="<?= Text::_('FAV_REMOVE'); ?>"></button>
                    <button class="btn btn-primary bi bi-fullscreen" id="btn-fullscreen" type="button" aria-label="<?= Text::_('FULLSCREEN'); ?>"></button>
                </div>
                <!-- Playlist -->
            <?php elseif ($page->data->folder === 'custom' && $page->data->source === 'playlist'): ?>
                <div id="toolbar" class="btn-group float-end me-3" role="group" aria-label="Playlist Toolbar">
                    <button class="btn btn-secondary bi bi-share" id="btn-share" type="button" aria-label="<?= Text::_('SHARE'); ?>"></button>
                    <button class="btn btn-danger bi bi-bookmark-dash" id="btn-rem-fav" type="button" aria-label="<?= Text::_('FAV_REMOVE'); ?>"></button>
                    <button class="btn btn-warning bi bi-clock" data-bs-toggle="modal" href="#wait" id="btn-epg" type="button" aria-label="<?= Text::_('GUIDES'); ?>"></button>
                    <button class="btn btn-primary bi bi-fullscreen" id="btn-fullscreen" type="button" aria-label="<?= Text::_('FULLSCREEN'); ?>"></button>
                </div>
                <!-- Default -->
            <?php else: ?>
                <div id="toolbar" class="btn-group float-end me-3" role="group" aria-label="Imported Toolbar">
                    <button class="btn btn-secondary bi bi-share" id="btn-share" type="button" aria-label="<?= Text::_('SHARE'); ?>"></button>
                    <button class="btn btn-success bi bi-bookmark-plus d-none" id="btn-add-fav" type="button" aria-label="<?= Text::_('FAV_ADD'); ?>"></button>
                    <button class="btn btn-danger bi bi-bookmark-dash d-none" id="btn-rem-fav" type="button" aria-label="<?= Text::_('FAV_REMOVE'); ?>"></button>
                    <button class="btn btn-warning bi bi-clock" data-bs-toggle="modal" href="#wait" id="btn-epg" type="button" aria-label="<?= Text::_('GUIDES'); ?>"></button>
                    <button class="btn btn-primary bi bi-fullscreen" id="btn-fullscreen" type="button" aria-label="<?= Text::_('FULLSCREEN'); ?>"></button>
                </div>
            <?php endif; ?>
        </div>
        <div class="tv-content">
            <!-- Info -->
            <h1><?= $page->data->name; ?></h1>
            <?php if ($page->data->playing): ?>
                <p class="fs-3"><?= $page->data->playing->title; ?></p>
            <?php endif; ?>
            <!-- VideoJS -->
            <div id="video-container"
                class="ratio ratio-16x9 bg-dark"
                data-name="<?= $page->data->name; ?>"
                data-title="<?= $page->data->playing->title ?? 'No info'; ?>"
                data-subtitle="<?= $page->data->playing->subtitle ?? 'No info'; ?>"
                data-logo="<?= $page->data->logo; ?>">
                <video id="my-video" controls="" aspectRatio="16:9" class="embed-responsive-item video-js" data-setup="{}">
                    <source src="<?= $page->data->url; ?>" type="<?= $page->data->mime; ?>" />
                </video>
            </div>
            <script type="text/javascript" src="https://vjs.zencdn.net/8.6.1/video.min.js"></script>
            <!-- Description -->
            <?php if ($page->data->playing): ?>
                <p class="fs-4 mt-4 mb-2"><?= $page->data->playing->subtitle; ?></p>
                <p class="mt-2 mb-4"><?= $page->data->playing->desc; ?></p>
            <?php endif; ?>
        </div>
        <div class="tv-guide mt-2 ps-lg-2">
            <!-- Guide TV -->
            <?php if ($page->data->guide): ?>
                <table class="table table-striped table-responsive">
                    <thead>
                        <tr>
                            <th scope="col"><?= Text::_('START'); ?></th>
                            <th scope="col"><?= Text::_('END'); ?></th>
                            <th scope="col"><?= Text::_('GUIDE'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($page->data->guide as $k): ?>
                            <tr>
                                <td class="<?php echo ($k->playnow ? 'text-white bg-primary' : ''); ?>"><?= $k->start->format('H:i'); ?></td>
                                <td class="<?php echo ($k->playnow ? 'text-white bg-primary' : ''); ?>"><?= $k->end->format('H:i'); ?></td>
                                <td class="<?php echo ($k->playnow ? 'text-white bg-primary' : ''); ?>"><?= $k->title; ?></td>
                            </tr>
                            <?php
                            $now    = new \DateTime();
                            if ($k->end > $now->add(new \DateInterval('PT12H'))) {
                                break;
                            }
                            ?>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <?php require_once('link.php'); ?>

    </main>
</div>
<script type="text/javascript">
    jQuery(document).ready(function($) {

        <?php if ($page->saved): ?>
            $('#btn-rem-fav').removeClass('d-none');
        <?php else: ?>
            $('#btn-add-fav').removeClass('d-none');
        <?php endif; ?>

        // Load channels list asynchronously
        $.ajax({
            async: false,
            url: '<?= $factory->Link('channels', $page->folder, $page->source . ':' . $page->source_alias, null, 'format=raw'); ?>',
            success: function(raw) {
                $("#channels-list").html(raw);
            }
        });

        const player = videojs('my-video');
        player.on('play', function() {
            if ('mediaSession' in navigator) {
                navigator.mediaSession.metadata = new MediaMetadata({
                    title: $('#video-container').attr('data-name'),
                    artist: $('#video-container').attr('data-title'),
                    album: $('#video-container').attr('data-subtitle'),
                    artwork: [{
                        src: $('#video-container').attr('data-logo'),
                        sizes: "150x150",
                        type: "image/png",
                    }]
                });
            }
        });

        $('#btn-fullscreen').on('click', function(e) {
            player.requestFullscreen();
        });

        //Add to the playlist
        $('#btn-add-fav').on('click', function(e) {
            e.preventDefault();
            $.ajax({
                url: '<?= $factory->Link('playlist.add', $page->folder, $page->source, $page->data->id, 'format=ajax'); ?>',
                success: function(result) {
                    $('#btn-add-fav').addClass('d-none');
                    $('#btn-rem-fav').removeClass('d-none');
                }
            });
        });

        //Remove from the playlist
        $('#btn-rem-fav').on('click', function(e) {
            e.preventDefault();
            $.ajax({
                url: '<?= $factory->Link('playlist.remove', $page->folder, $page->source, $page->data->id, 'format=ajax'); ?>',
                success: function(result) {
                    <?php if ($page->folder !== 'custom'): ?>
                        $('#btn-rem-fav').addClass('d-none');
                        $('#btn-add-fav').removeClass('d-none');
                    <?php else: ?>
                        $('#channels>li[data-id="<?= $page->data->id; ?>"]').addClass('d-none');
                        window.location.href = '<?= $factory->Link('custom', 'playlist'); ?>';
                    <?php endif; ?>
                }
            });
        });

        //Force the EPG to reload
        $('#btn-epg').on('click', function(e) {

            e.preventDefault();

            $(this).attr('disabled', 'disabled');

            var wrapper = $('#wait .modal-content');
            var title = wrapper.find('.modal-header:first');
            var content = wrapper.find('.modal-body:first');

            var data = {
                'key': '<?= $page->data->epg_key ?? ''; ?>',
                'id': '<?= $page->data->id; ?>'
            };
            var posting = $.post(
                '<?= $factory->Link('watch.cron', $page->folder, $page->source . ':' . $page->source_alias, $page->data->id . ':' . $page->data->name, 'format=json'); ?>',
                data,
                (result) => {
                    // Replace modal dialog content
                    wrapper.addClass(result.success ? 'bg-success text-white' : 'bg-danger text-white');
                    wrapper.find('.modal-header:first').text(result.title);
                    wrapper.find('.modal-body:first').text(result.content);

                    if (result.success) {
                        $('.tv-guide').load('<?= $factory->Link('watch', $page->folder, $page->source . ':' . $page->source_alias, $page->data->id . ':' . $page->data->name, 'format=raw'); ?>');
                    }
                    $('#btn-epg').removeAttr('disabled');                        
                    wrapper.find('.modal-header:first').text(result.title);
                    wrapper.find('.modal-body:first').text(result.content);
                    setTimeout(function() {
                            const modal = bootstrap.Modal.getInstance('#wait');
                            modal.hide();
                        }, 1000);                    
                    return true;
                }
            );
            return true;
        });

        $('#btn-share').on('click', function(e) {
            e.preventDefault();
            $('#api').select();
            document.execCommand('copy');

            var wrapper = $('#tv-toast');
            var toast = wrapper.find('.toast:first').clone();
            toast.find('.toast-body').html('<b><?= Text::_('SHARED'); ?></b><br /><?= Text::_('SHARED_DESC'); ?>');
            toast.addClass('bg-info');
            toast.appendTo('body');

            var tbs = bootstrap.Toast.getOrCreateInstance(toast.get(0));
            tbs.show();

        });

    });
</script>
