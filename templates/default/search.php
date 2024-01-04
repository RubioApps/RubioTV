<?php 
/**
 +-------------------------------------------------------------------------+
 | RubioTV  - A domestic IPTV Web app browser                              |
 | Version 1.0.0                                                           |
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
	<div class="container-fluid">
       <div class="row p-2 pt-3 pb-3 d-flex align-items-center">
            <div class="col-md-2">                
            </div>
            <div class="col-md-8">
                <div id="searchbox" class="d-flex form-inputs">
                    <input id="query" name="q" type="search" class="form-control" placeholder="<?= Text::_('SEARCH');?>">
                    <input name="url" type="hidden" value="<?= $config->live_site;?>" />
                    <input name="task" type="hidden" value="<?= $factory->getParam('task');?>" />
                    <input name="folder" type="hidden" value="<?= $factory->getParam('folder');?>" />
                    <input name="source" type="hidden" value="<?= $factory->getParam('source');?>" />
                    <i class="bx bx-search"></i>
                </div>                
            </div>
        </div>               
	</div> 
</section>

<!-- Autocomplete -->
<script type="text/javascript">   
jQuery(document).ready(function(){   
    var url     = $('#searchbox input[name="url"]').val();
    var task    = $('#searchbox input[name="task"]').val();
    var folder  = $('#searchbox input[name="folder"]').val();
    var source  = $('#searchbox input[name="source"]').val();
    var cache   = {};
    $('#query').autocomplete({
        autoFocus: true,
        minLength : 2,
        source:  function( request, response ) {
            var term = request.term;
            if ( term in cache ) {
                response( cache[ term ] );
                return;
            }
            $.getJSON( url + '/?task=' + task + '.search&folder=' + folder + '&source=' + source + '&format=json', request, function( data, status, xhr ) {
                cache[ term ] = data;
                response( data );
            });            
        },
        create: function() {            
            $(this).data('ui-autocomplete')._renderItem = function (ul, item) { 
                if(!item.id){
                    var target = url + '/?task=channels&folder=' + task + '&source=' + item.code.toLowerCase() + ':' + item.name.toLowerCase();
                } else {
                    var target = url + '/?task=view&folder=' + folder + '&source=' + source + '&id=' + item.id;
                }
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
    $('#query').focus();        
});                 
</script> 
