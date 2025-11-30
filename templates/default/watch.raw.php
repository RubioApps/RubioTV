<?php

/**
 +-------------------------------------------------------------------------+
 | RubioTV  - A domestic IPTV Web app browser                              |
 | Version 1.6.1                                                          |
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
