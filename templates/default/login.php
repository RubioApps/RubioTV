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

<!-- Login -->
<main role="main" class="container container-md mx-auto my-auto">         
    <form><?= $factory::getToken();?></form>
    <div class="row justify-content-center p-3 flex-nowrap mt-5">                    
        <div class="col-auto bg-light border rounded">
            <div class="row p-1">
                <div class="col text-center mt-2">
                    <label for="pwd" class="col-form-label fw-bolder"><?= Text::_('PASSWORD');?></label>
                </div>
            </div>
            <div class="row p-1">
                <div class="col">
                    <input type="password" id="pwd" name="password" class="form-control" value="" />
                </div>
            </div>
            <div class="row p-1">
                <div class="col text-center mb-2">                  
                    <button id="btn-submit" type="button" class="btn btn-primary"><?= Text::_('SUBMIT'); ?></button>
                </div>
            </div>
        </div>
    </div>
</main>
<!-- JS -->
<script type="text/javascript">   
jQuery(document).ready(function(){   

    $('#btn-submit').on('click',function(e){
        e.preventDefault(); 

        var pwd     = $('input#pwd').val();
        var token   = $('input#token').attr('name');
        var sid     = $('input#token').val();        

        data = {'password' : pwd , [token] : sid};
        var posting = $.post('<?= $factory->Link('login');?>',data);
        posting.done(function(result){
            raise( result.message , result.error);
            if(result.error){
                $.get('<?= $factory->Link('login.token');?>').done(function(data){
                    var token   = $('input#token').attr('name');
                    var sid     = $('input#token').val();                      
                    token.attr('name',data.token);
                    token.val(data.sid);
                });                  
            } else {                          
                setTimeout(1000, top.location='<?= $factory->Link();?>');                 
            }       
        });
    });

    function raise( text , error )
    {
        var wrapper = $('#tv-toast');
        var toast   = wrapper.find('.toast:first').clone();
        toast.find('.toast-body').html(text);
        toast.addClass(error ? 'bg-danger' : 'bg-success');
        toast.appendTo('body');
        const tbs = bootstrap.Toast.getOrCreateInstance(toast.get(0));
        tbs.show();        
    }    

});
</script>