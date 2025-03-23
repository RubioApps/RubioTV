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

use \RubioTV\Framework\Language\Text;

?>
<section class="border-top mt-5">
    <div class="row g-1 pt-3 pb-3 align-items-center fst-italic">
        <div class="col-auto">
            <label for="api" class="col-form-label"><?= Text::_('SOURCE');?>:</label>
        </div>
        <div class="col-auto w-50 w-md-75">
            <input id="api" name="api" type="text" class="form-control" value="<?= $page->link ;?>" readonly />
        </div>  
        <div class="col-auto">
            <button type="button" id="btn-copy" class="btn btn-secondary bi bi-copy" aria-label="<?= Text::_('COPY');?>"></button>
        </div>   
        <?php if($task == 'channels'):?>
        <div class="col-auto">
            <button type="button" id="btn-export" class="btn btn-primary bi bi-download" aria-label="<?= Text::_('EXPORT');?>"></button>
        </div>                   
    </div>
</section>
