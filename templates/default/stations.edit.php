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

use \RubioTV\Framework\SEF;
use \RubioTV\Framework\Language\Text;
?>

<?php require_once('search.php'); ?>

<!-- Breadcrumb -->
<div class="tv-breadcrumb">
    <nav class="rounded m-3" aria-label="breadcrumb">
        <ol class="breadcrumb p-2 m-0">
            <li class="breadcrumb-item">
                <a href="<?= $config->live_site; ?>"><?= Text::_('HOME'); ?></a>
            </li>
            <li class="breadcrumb-item">
                <a href="<?= $factory->Link('radio'); ?>"><?= Text::_('RADIO'); ?></a>
            </li>
            <li class="breadcrumb-item" aria-current="page">
                <?= Text::_('GROUPS')[strtoupper($page->source)] ?? SEF::decode($page->source_alias); ?>
            </li>
        </ol>
    </nav>
</div>

<!-- Toolbar -->
<section class="tv-toolbar ps-lg-2 clearfix mb-3">
    <h3 class="float-start ms-3"><?= Text::_('EDIT'); ?></h3>
    <div id="toolbar" class="btn-group float-end me-3" role="group" aria-label="Toolbar">
        <button id="btn-check-all" class="btn btn-primary bi bi-check-all" type="button" aria-label="<?= Text::_('CHECK_ALL'); ?>"></button>
        <button id="btn-remove" class="btn btn-danger bi bi-trash3" type="button" aria-label="<?= Text::_('REMOVE'); ?>"></button>
        <button id="btn-view" class="btn btn-success bi bi-x-circle" type="button" aria-label="<?= Text::_('CANCEL'); ?>"></button>
    </div>
</section>

<?php if (is_array($page->data) && count($page->data)): ?>
    <!-- List of stations -->
    <main role="main" class="justify-content-center">
        <ul id="list-items" class="list-group">
            <?php
            $i = 0;
            foreach ($page->data as $item) {
            ?>
                <li class="list-group-item d-flex" style="cursor:" data-id="<?= $item->id; ?>">
                    <input type="checkbox" class="my-auto p-2 form-check-input me-2" id="cb-<?= $i; ?>" name="id" value="<?= $item->id; ?>" />
                    <label class="form-check-label" for="cb-<?= $i; ?>">
                        <img src="<?= $item->image; ?>" class="img-thumb" width="64" data-remote="<?= $item->remote; ?>" />
                        <?= $item->name; ?>
                    </label>
                </li>
            <?php
                $i++;
            }
            ?>
        </ul>
        <?= $factory->getToken(); ?>
    </main>
    <!-- Pagination -->
    <section class="container mt-3">
        <?= $page->pagination->getPagesLinks(); ?>
    </section>

    <!-- Click on boxes -->
    <script type="text/javascript">
        jQuery(document).ready(function() {
            $('#btn-check-all').on('click', function(e) {
                e.preventDefault();
                $('#list-items input[type=checkbox]').each(function(i) {
                    $(this).attr('checked', !$(this).attr('checked'));
                });
            });

            $('#btn-view').on('click', function(e) {
                e.preventDefault();
                document.location.href = '<?= $factory->Link('radio', $page->folder, $page->source); ?>';
            });

            $('#btn-remove').on('click', function(e) {
                e.preventDefault();
                var url = '<?= $factory->Link('radio.remove', $page->folder, $page->source); ?>';
                var token = $('input#token');
                var name = token.attr('name');
                var data = {
                    'ids': [],
                    [name]: token.val()
                };
                $('#list-items input[type=checkbox]:checked').each(function(i) {
                    data['ids'].push($(this).val());
                });
                var posting = $.post(url, data);

                posting.done(function(data) {
                    if (!data.error) {

                        $('#list-items input[type=checkbox]:checked').each(function(i) {
                            $(this).parent('li').remove();
                        });

                        if ($('#list-items input[type=checkbox]').length == 0)
                            $('.pagination').hide();

                        $.rtv.toast($.rtv.labels['remove_success'], false);
                        setTimeout(function() {
                            location.reload();
                        }, 2000);
                    }
                });
            });
        });
    </script>

<?php else: ?>
    <div class="container">
        <div class="row">
            <div class="p"><?= Text::_('CHANNELS_EMPTY'); ?></div>
        </div>
    </div>
<?php endif; ?>