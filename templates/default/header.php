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

use RubioTV\Framework\Request;

?>
<header class="navbar navbar-expand-lg navbar-dark tv-navbar sticky-top">
  <nav class="container-lg flex-wrap flex-lg-nowrap">
    <div class="d-flex">
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainmenu" aria-controls="mainmenu" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
      </button>
    </div>
    <a class="navbar-brand tv-brand text-center text-truncate" href="<?= $factory->Link(); ?>">
      <div class="h3 fw-bold"><?= $config->sitename; ?></div>
    </a>
    <?php if ($factory->getTask() === 'watch' || $factory->getTask() === 'listen'): ?>
      <div class="tv-navbar-toggler">
        <button class="navbar-toggler" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebar" aria-controls="sidebar" aria-label="Toggle Channels">
          <div class="h1 align-top top p-0">...</div>
        </button>
      </div>
    <?php endif; ?>
    <div class="collapse navbar-collapse" id="mainmenu">
      <ul class="navbar-nav me-auto mb-2 mb-lg-0">
        <?php
        foreach ($page->menu as $f) {
          $task   = $factory->getTask();
          $folder = Request::getVar('folder', '', 'GET');
          $active = ($task === $f->id) || ($task !== 'guides' && $folder === $f->id);
        ?>
          <li class="nav-item">
            <a class="nav-link<?= ($active  ? ' active' : ''); ?>" href="<?= $factory->Link($f->id); ?>">
              <div class="text-truncate"><?= $f->name; ?></div>
            </a>
          </li>
        <?php } ?>
        <li class="nav-item p-2 d-inline d-xl-none"></li>
      </ul>
      <div class="d-flex mt-1 mb-2">
        <button id="btn-theme-switch"
          class="btn bi <?= $page->params['mode'] != 'dark' ? 'btn-primary bi-moon-stars' : 'btn-warning bi-sun'; ?>"
          data-mode="<?= $page->params['mode']; ?>">
        </button>
        <a class="d-lg-none btn bi bi-house bg-success ms-1" href="<?= $config->live_site; ?>"></a>
        <?php if ($factory->isLogged() && !$factory->autoLogged()): ?>
          <a class="nav-link p-0 mt-0 ms-1" href="<?= $factory->Link('login.off'); ?>">
            <div class="btn btn-secondary">
              <span class="bi bi-power"></span>
            </div>
          </a>
        <?php endif; ?>
      </div>
    </div>
  </nav>
</header>