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
<section class="header-main border-bottom mb-2">
	<div class="container">
       <div class="row p-1 pt-3 pb-3 d-flex align-items-center">
            <div class="col">
                <div id="searchbox" class="d-flex form-inputs">
                    <input id="query" name="q" type="search" class="form-control" placeholder="<?= Text::_('SEARCH');?>">
                    <i class="bx bx-search"></i>
                </div>                
            </div>
        </div>               
	</div> 
</section>

<!-- Autocomplete -->
<script type="text/javascript">   
jQuery(document).ready(function(){   
    var cache   = {};
    $('#query').autocomplete({
        autoFocus: true,
        minLength : 3,
        source:  function( request, response ) {
            var term = request.term;
            if ( term in cache ) {
                response( cache[ term ] );
                return;
            }
            $.getJSON('<?= $factory->Link($page->task.'.search', $page->folder, 
                $page->source ? $page->source . ':' . $page->source_alias : null , null, 'format=json');?>', 
                request, function( data, status, xhr ) {
                    cache[ term ] = data;
                    response( data );
                }
            );            
        },
        create: function() {            
            $(this).data('ui-autocomplete')._renderItem = function (ul, item) { 
                var target = item.link;
                ul.addClass("dropdown-menu");
                var line = $('<li>')
                    .addClass('dropdown')
                    .appendTo(ul);                                
                var link = $('<a>')
                    .addClass('btn')                
                    .attr('href' , target)
                    .append(item.flag)
                    .append(' ')
                    .append(item.name);
                return line.append(link);
                };                                                                        
        }
    });
});                 
</script> 
