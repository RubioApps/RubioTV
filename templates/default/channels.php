<?php 
defined('_TVEXEC') or die;

use \RubioTV\Framework\Language\Text;
?>

<?php require_once('search.php'); ?>

<!-- Breadcrumb -->
<div class="tv-breadcrumb">
    <nav class="rounded border bg-light m-3" aria-label="breadcrumb">
        <ol class="breadcrumb p-2 m-0">
            <li class="breadcrumb-item">
                <a href="<?= $config->live_site;?>"><?= Text::_('HOME');?></a>
            </li>    
            <li class="breadcrumb-item">
                <a href="<?= $factory->getTaskURL($router->folder);?>"><?= Text::_(strtoupper($router->folder));?></a>
            </li>
            <li class="breadcrumb-item" aria-current="page">
                <?= Text::_(ucfirst($router->source));?>
            </li> 
        </ol>
    </nav>
</div>

<!-- Toolbar -->
<section class="tv-toolbar ps-lg-2 clearfix"> 
    <div id="toolbar" class="btn-group float-end me-3" role="group" aria-label="Toolbar">
        <button id="btn-guide" class="btn btn-warning bi bi-clock" type="button" aria-label="<?= Text::_('GUIDES'); ?>"></button>        
        <?php if($router->folder !== 'dtv' && $router->folder !== 'custom'):?>
        <button id="btn-sync" class="btn btn-primary bi bi-arrow-repeat" type="button" aria-label="<?= Text::_('RESYNC'); ?>"></button>
        <?php else:?>
            <?php if($router->folder === 'custom'):?>
            <button id="btn-custom-edit" class="btn btn-primary bi bi-pencil" type="button" aria-label="<?= Text::_('EDIT'); ?>"></button>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</section>


<?php if(is_array($router->model) && count($router->model)): ?>
<!-- Channels -->
<main role="main" class="justify-content-center p-3">
    <div class="tv-channels-grid row row-cols-4 row-cols-md-6 row-cols-lg-8 g-2 g-lg-3 mt-1">
        <?php foreach ($router->model as $item):?>   
        <div class="col d-flex align-items-stretch">
            <div class="card p-3 border bg-light justify-content-center w-100" data-link="<?= $item->link;?>">
                <img src="<?= $item->image;?>" class="card-img-top mx-auto spinner-border m-5" data-remote="<?= $item->remote;?>" alt="<?= htmlspecialchars($item->name);?>" />
                <div class="card-body p-0 text-center">
                    <h5 class="card-title text-truncate">
                        <div class="card-text"><?= $item->name;?></div>
                    </h5>
                </div>
            </div>             
        </div>
        <?php endforeach; ?>   
    </div>
</main>
<!-- Pagination -->
<section class="container mt-3">
    <?= $router->pagination->getPagesLinks(); ?> 
</section>    

<?php require_once('sourcelink.php');?>

<!-- JS -->
<script type="text/javascript">   
jQuery(document).ready(function(){   

    <?php if($config->notify_cache):?>
    var notify = function( img , data){
        img.attr('src', data.logo);                               
        var wrapper = $('#tv-toast');
        var toast   = wrapper.find('.toast').first().clone();
        toast.find('.toast-body').html( data.message + '<br />' + data.id );
        toast.addClass( data.error ? 'bg-danger' : 'bg-success');
        toast.appendTo(wrapper);

        if(bootstrap){
            const tbs = bootstrap.Toast.getOrCreateInstance(toast.get(0));
            tbs.show();
        } else {
            toast.show();
            setTimeout(function() { 
                toast.remove();
            }, 5000);                                 
        }
    };
    <?php else:?>

    var notify = function( img , data ){
        img.attr('src', data.logo);
    }
    <?php endif;?>

    // Asynchronus image loading            
    $('img[data-remote^=http]').each(function(){        
        var img     = $(this);
        var src     = $(this).attr('src');  
        var url     = $(this).attr('data-remote');  
        var title   =  $(this).attr('title');                           
        if(url && url !== src ){                                      
            $.getJSON(url , function(data){
                notify( img , data);       
                img.removeClass('spinner-border m-5');                         
            });
        } else {
            img.removeClass('spinner-border m-5');            
        }
    });

    $('.card').on('click' , function(e){
        window.location.href = $(this).attr('data-link');
    });        
    
    $('#btn-sync').on('click',function(e){
        e.preventDefault();
        var url = '<?= $factory->getTaskURL('channels.sync', $router->folder, 
            $router->source . (!empty($router->sourcename) ? ':' . $router->sourcename : '')) . '&format=json';?>';
        $.getJSON( url , function(data){  
            var wrapper = $('#tv-toast');
            var toast   = wrapper.find('.toast').first().clone();
            toast.find('.toast-body').html( '<strong>' + data.title + '</strong><br />' + data.content);
            toast.addClass('bg-success');
            toast.appendTo($('body'));

            if(bootstrap){
                const tbs = bootstrap.Toast.getOrCreateInstance(toast.get(0));
                tbs.show();
            } else {
                toast.show();
            }
            setTimeout( function(){location.reload();}, 3000);                                                           
        });         
    });

    $('#btn-custom-edit').on('click',function(e){
        e.preventDefault();
        document.location.href = '<?= $factory->getTaskURL( 'custom.edit', $router->folder, $router->source);?>';
    });    

    $('#btn-guide').on('click',function(e){
        e.preventDefault();
        document.location.href = '<?= $factory->getTaskURL( 'guides', $router->folder, $router->source);?>';
    });   

});                 
</script> 
<?php else:?>
    <?php require_once('404.php');?>
<?php endif;?>