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

use RubioTV\Framework\Language\Text;

?>
<section class="header-main border-bottom mb-2">
    <div class="container-fluid">
        <div class="row p-2 pt-3 pb-3 d-flex align-items-center">
            <div class="col-md-2">
            </div>
            <div class="col-md-8">
                <div id="searchbox" class="d-flex form-inputs">
                    <input id="query" name="q" type="search" class="form-control" placeholder="<?= Text::_('SEARCH'); ?>">
                    <i class="bx bx-search"></i>
                </div>
            </div>
        </div>
    </div>
</section>
<!-- Breadcrumb -->
<div class="tv-breadcrumb">
    <nav class="rounded ms-0 me-0 mb-3" aria-label="breadcrumb">
        <ol class="breadcrumb p-1 m-0">
            <li class="breadcrumb-item">
                <a href="<?= $factory::Link(); ?>"><?= Text::_('HOME'); ?></a>
            </li>
            <li class="breadcrumb-item">
                <a href="<?= $factory->Link($page->folder); ?>"><?= Text::_(strtoupper($page->folder)); ?></a>
            </li>
            <li class="breadcrumb-item">
                <a href="<?= $factory->Link('channels', $page->folder, $page->source . ':' . $page->source_alias); ?>"><?= Text::_(ucfirst($page->source)); ?></a>
            </li>
            <li class="breadcrumb-item" aria-current="page">
                <?= Text::_('GUIDES'); ?>
            </li>
        </ol>
    </nav>
</div>
<!-- Toolbar -->
<section class="tv-toolbar ps-lg-2 clearfix">
    <div id="toolbar" class="btn-group float-end me-3" role="group" aria-label="Toolbar">
        <a class="btn btn-primary bi bi-tv" href="<?= $factory->Link('guides'); ?>">
            <?= Text::_('DTV'); ?>
        </a>
        <a class="btn btn-success bi bi-bookmark" href="<?= $factory->Link('guides', 'custom', 'playlist'); ?>">
            <?= Text::_('PLAYLIST'); ?>
        </a>
    </div>
</section>

<?php if (is_array($page->data) && count($page->data)): ?>
    <main role="main" class="justify-content-center m-3">
        <div id="guide-table">
            <?php foreach ($page->data as $item): ?>
                <div role="button" class="channel-row row p-2 rounded border mb-2 tv-bg-<?= $page->params['mode'];?>" data-link="<?= $item->link; ?>">
                    <div class="col-12 col-md-8">
                        <div class="row justify-content-start">
                            <div class="col-2 col-md-1">
                                <img class="img-fluid" src="<?= $item->logo;?>" data-remote="<?= $item->remote;?>" alt="<?= htmlspecialchars($item->name);?>" />
                            </div>
                            <div class="col-10 col-md-11">
                                <div class="row">
                                    <div class="h5 p-0 mb-0 channel-name font-weight-bold text-truncate"><?= $item->name; ?></div>
                                </div>
                                <div class="row pt-0 mt-0"><?= $item->title; ?></div>
                                <div class="row d-block d-md-none text-primary">
                                    <?= $item->start->format('H:i'); ?> - <?= $item->end->format('H:i'); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 d-none d-md-block my-auto">
                        <div class="row">
                            <div class="col-3"><?= $item->start->format('H:i'); ?></div>
                            <div class="col-6">
                                <div class="progress">
                                    <div
                                        class="progress-bar progress-bar-striped text-light"
                                        role="progressbar"
                                        style="width:<?= $item->progress; ?>%"
                                        aria-valuenow="<?= $item->progress; ?>"
                                        aria-valuemin="0"
                                        aria-valuemax="100">
                                        <?= $item->viewed; ?>
                                    </div>
                                </div>
                            </div>
                            <div class="col-3"><?= $item->end->format('H:i'); ?></div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </main>

    <?php require_once('link.php'); ?>

    <script type="text/javascript">
        jQuery(document).ready(function() {

            // Creates a new jQuery selector non case sensitive
            $.expr[':'].icontains = function(a, i, m) {
                return jQuery(a).text().toUpperCase().indexOf(m[3].toUpperCase()) >= 0;
            };

            $('.channel-row').on('click', function(e) {
                window.location.href = $(this).attr('data-link');
            });

            $('#query').on('input', function() {
                $('#guide-table .channel-name:icontains("' + $(this).val() + '")').parents('.channel-row').show();
                $('#guide-table .channel-name:not(:icontains("' + $(this).val() + '"))').parents('.channel-row').hide();
            });
        });
    </script>

<?php else: ?>
    <div class="container">
        <div class="row">
            <div class="p"><?= Text::_('GUIDE_EMPTY'); ?></div>
        </div>
    </div>
<?php endif; ?>