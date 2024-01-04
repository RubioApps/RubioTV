<?php 
/**
 +-------------------------------------------------------------------------+
 | RubioTV  - A domestic IPTV Web app browser                              |
 | Version 1.0.0                                                           |
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

use \RubioTV\Framework\Factory;
use \RubioTV\Framework\Language\Text;
?>
<main role="main" class="justify-content-center">
    <div class="row p-0 mt-0">
        <div class="col mx-auto text-center">
            <h3><?= Text::_('PAGE_NOT_FOUND');?></h3>
        </div>
    <div class="row"> 
        <div class="col mx-auto text-center">  
            <img class="w-25" src="<?= Factory::getAssets() . '/images/404.jpg';?>" alt="<?= Text::_('PAGE_NOT_FOUND');?>" />            
        </div>
    </div>
</main>
