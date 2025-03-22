<?php

/**
 +-------------------------------------------------------------------------+
 | RubioTV  - A domestic IPTV Web app browser                              |
 | Version 1.5.1                                                           |
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

use \RubioTV\Framework\Language\Text;
?>
<header class="navbar navbar-expand-lg sticky-top">
    <nav class="container-xxl flex-wrap flex-xxl-nowrap justify-content-center">
        <a class="navbar-brand tv-brand text-center text-truncate" href="<?= $factory->Link(); ?>">
            <div class="h3 fw-bold text-white"><?= $config->sitename; ?></div>
        </a>
    </nav>
</header>
<!-- Login -->
<main role="main" class="container container-md mx-auto my-auto">
    <form>
        <?= $factory->getToken(); ?>
        <input type="hidden" id="url" name="url" value="<?= $factory->Link('login');?>" />
        <div class="row justify-content-center p-3 flex-nowrap mt-5">
            <div class="col-auto border rounded">
                <div class="row p-1">
                    <div class="col mt-2 text-center">
                        <label for="pwd" class="form-label">
                            <?= Text::_('PASSWORD'); ?>
                        </label>
                        <input type="password" id="pwd" name="password" class="form-control" value=""
                            autocomplete="false" />
                    </div>
                </div>
                <div class="row p-1">
                    <div class="col text-center mb-2">
                        <button id="btn-submit" type="button" class="btn btn-primary"><?= Text::_('SUBMIT'); ?></button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</main>
<!-- JS -->
<script type="text/javascript">
    jQuery(document).ready(function() {
        $.rtv.livesite = '<?= $config->live_site;?>';
        $.rtv.logged = <?= $factory->isLogged() ? 'true' : 'false'; ?>;
        $.rtv.login('#btn-submit');
    });
</script>