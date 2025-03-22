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

use RubioTV\Framework\Language\Text;

?>
<div class="tv-layout justify-content-center p-3">
    <!-- List of Stations -->
    <aside class="tv-sidebar">
        <div class="offcanvas-lg offcanvas-end" id="sidebar" aria-labelledby="stationsOffcanvasLabel">
            <div class="offcanvas-header border-bottom">
                <h5 class="offcanvas-title" id="stationsOffcanvasLabel"><?= Text::_('STATIONS'); ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close" data-bs-target="#sidebar"></button>
            </div>
            <div class="offcanvas-body p-0">
                <nav id="stations-list" class="tv-links tv-folder-grid p-1 w-100">
                </nav>
            </div>
        </div>
    </aside>
    <main class="tv-main order-1">
        <!-- Breadcrumb -->
        <div class="tv-breadcrumb">
            <nav class="rounded ms-0 me-0 mb-3" aria-label="breadcrumb">
                <ol class="breadcrumb p-1 m-0">
                    <li class="breadcrumb-item">
                        <a href="<?= $config->live_site; ?>"><?= Text::_('HOME'); ?></a>
                    </li>
                    <li class="breadcrumb-item">
                        <a href="<?= $factory->Link('radio'); ?>">
                            <?= Text::_('RADIO'); ?>
                        </a>
                    </li>
                    <li class="breadcrumb-item">
                        <a href="<?= $factory->Link('radio', $page->folder, $page->source . ':' . $page->source_alias); ?>">
                            <?= Text::_('GROUPS')[strtoupper($page->source)] ?? ucfirst($page->source_alias); ?>
                        </a>
                    </li>
                    <li class="breadcrumb-item d-none d-md-block" aria-current="page"><?= $page->data->name; ?></li>
                </ol>
            </nav>
        </div>
        <!-- Name -->
        <div class="row">
            <div class="col h2 mt-3 mb-1"><?= $page->data->name; ?></div>
        </div>
        <!-- Player -->     
        <audio id="audioFile"></audio>   
        <div id="player-container" class="row row-cols-3 mx-auto rounded border mb-1 g-0">
            <div class="col-2 col-md-2 p-0 rounded-start">
                <!-- Logo -->
                <img id="player-icon" class="img-fluid rounded-start" src="<?= $page->data->image; ?>" />
            </div>
            <div class="col-8 col-md-8 my-auto p-0 m-0">
                <!-- Currently playing -->
                <div class="row g-0"> 
                    <div class="col-2 col-md-1 my-auto text-center">
                        <button id="volume" class="btn fs-3 p-0 text-muted" data-bs-toggle="tooltip" title="<?= Text::_('PLAYER_VOLUME'); ?>">
                            <i class="bi bi-volume-up"></i>                                
                        </button>                                                                                                              
                    </div>
                    <div class="col-6 col-md-6 my-auto text-center">
                        <div id="volume-slider" class="g-0">
                            <div id="volume-value" data-value="100">
                                <i id="volume-handler" class="bi bi-square-fill"></i>
                            </div>
                        </div> 
                    </div>                          
                    <div class="col-4 col-md-5 my-auto text-center p-0 m-0">                          
                        <canvas id="equalizer" width="0" height="0" class="d-none my-auto"></canvas>       
                        <div id="loading-buffer" class="p-1 spinner-border my-auto d-none"></div>                                          
                    </div>                    
                </div>
            </div>                    
            <!-- Play -->
            <div class="col-2 col-md-2 text-center my-auto p-0 m-0">
                <button id="play" class="btn text-muted" data-bs-toggle="tooltip" title="<?= Text::_('PLAYER_PLAY'); ?>">
                    <i class="bi bi-play"></i>
                </button>
            </div>                    
        </div>
        <div id="player-title" class="mb-1"></div>
        <div class="row row-cols-2">
            <div class="col text-start">
                <button class="btn btn-outline-secondary text-muted text-nowrap d-none" id="record" data-bs-toggle="tooltip" title="<?= Text::_('PLAYER_RECORD'); ?>">
                    <i class="bi bi-record-fill"></i>
                    <span><?= Text::_('PLAYER_RECORD'); ?></span>
                </button>
            </div>
            <div class="col text-end">
                <span class="btn bi bi-clock d-none" id="record-timer"></span>
                <div class="btn btn-outline-primary bi bi-download d-none" id="record-download">
                    <span class="ps-1"><?= Text::_('DOWNLOAD'); ?></span>
                </div>
            </div>
        </div>
        <div class="mt-4 mb-5">
            <small>
                <ul id="player-info" class="list-group">
                    <li class="list-group-item text-muted">
                        <span><?= Text::_('PLAYER_LATENCY'); ?>:</span>
                        <span class="me-3" id="player-latency">0s.</span>
                    </li>
                    <li class="list-group-item text-muted">
                    <span><?= Text::_('PLAYER_BUFFER_LENGTH'); ?>:</span>
                    <span id="player-buffer-length">0s.</span>
                    </li>
                </ul>
            </small>
        </div>
        <div id="player-icecast" class="mt-1"></div>
        <?php require_once('link.php'); ?> 
    </main>       
</div>
<script type="text/javascript" src="<?= $factory->getAssets() . '/player.js'; ?>"></script>
<script type="text/javascript">
    jQuery(document).ready(function($) {

        let list = {
            station: '<?= $page->data->name; ?>',
            logo: '<?= $page->data->image; ?>',
            mime: 'audio/mpeg',
            sources: []
        };

        // Load stations asynchronously
        $.ajax({
            async: false,
            url: '<?= $factory->Link('stations', $page->folder, $page->source . ':' . $page->source_alias, null, 'format=raw'); ?>',
            success: function(raw) {
                $("#stations-list").html(raw);
            }
        });

        $.player.init('<?= $page->data->sync; ?>');
    });
</script>