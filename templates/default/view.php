<?php 
defined('_TVEXEC') or die;

use RubioTV\Framework\Helpers;
use RubioTV\Framework\Language\Text;

$item       = $router->model;
$sourcelink = $router->sourcelink;

?>
<div class="tv-layout justify-content-center p-3">
<!-- List of Channels -->
<aside class="tv-sidebar">
    <div class="offcanvas-lg offcanvas-end" id="sidebar" aria-labelledby="channelsOffcanvasLabel">
        <div class="offcanvas-header border-bottom">
          <h5 class="offcanvas-title" id="channelsOffcanvasLabel"><?= Text::_('CHANNELS'); ?></h5>
          <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close" data-bs-target="#sidebar"></button>
        </div>
        <div class="offcanvas-body p-0">
            <nav class="tv-links tv-folder-grid p-1">
                <ul class="tv-links-nav list-unstyled mb-0 pb-3 pb-lg-2 pe-lg-2">
                    <?php foreach($router->getChannels(true) as $k):?>
                    <li class="tv-links w-100 mt-1">
                        <a class="btn btn-light border d-grid" href="<?= $k->link;?>">
                            <div class="text-truncate">
                                <img class="me-2" width="32" src="<?= $k->image;?>" alt="<?= htmlspecialchars($k->name);?>" /><?= $k->name;?>
                            </div>
                        </a>
                    </li>
                    <?php endforeach;?>   
                </ul>
            </nav>
        </div>
    </div>
</aside>      
<main class="tv-main order-1">  
    <!-- Wait -->
    <div class="modal modal-md fade" data-bs-backdrop="static" id="wait" tabindex="-1" aria-labelledby="wait" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header fw-bolder"><?= Text::_('EPG_UPDATE');?></div>
                <div class="modal-body">
                    <p><?= Text::_('CRON_WAIT');?></p>
                    <div class="d-flex justify-content-center">
                        <div class="spinner-border spinner-border-lg" role="status"></div>
                    </div>
                </div>
            </div>
        </div>        
    </div>   
    
    <!-- Breadcrumb -->    
    <div class="tv-breadcrumb">
        <nav class="rounded border bg-light m-3" aria-label="breadcrumb">
            <ol class="breadcrumb p-2 m-0">
                <li class="breadcrumb-item">
                    <a href="<?= $config->live_site;?>"><?= Text::_('HOME');?></a>
                </li>    
                <li class="breadcrumb-item">
                    <a href="<?= $factory->getTaskURL($item->folder);?>">
                        <?= Text::_(strtoupper($item->folder));?>
                    </a>
                </li>
                <li class="breadcrumb-item" aria-current="page">
                    <a href="<?= $factory->getTaskURL('channels', $item->folder , $item->source);?>">
                        <?= Text::_(ucfirst($item->source));?></a>
                </li>
                <li class="breadcrumb-item" aria-current="page"><?= $item->name;?></li>    
            </ol>
        </nav>
    </div>
    <!-- Toolbar --> 
    <div class="tv-toolbar ps-lg-2 clearfix mb-1"> 
        <!-- DTV -->
        <?php if(strstr($item->url , $config->dtv['host']) !== false) :?>
        <div id="toolbar" class="btn-group float-end me-3" role="group" aria-label="DTV Toolbar">                                                                                                       
            <button class="btn btn-secondary bi bi-share" id="btn-share" type="button" aria-label="<?= Text::_('SHARE'); ?>"></button>                
            <button class="btn btn-success bi bi-bookmark-plus" id="btn-add-fav" type="button" aria-label="<?= Text::_('FAV_ADD'); ?>"></button>
            <button class="btn btn-danger bi bi-bookmark-dash" id="btn-rem-fav" type="button"  aria-label="<?= Text::_('FAV_REMOVE'); ?>"></button>               
            <button class="btn btn-primary bi bi-fullscreen" id="btn-fullscreen" type="button" aria-label="<?= Text::_('FULLSCREEN'); ?>"></button>
        </div>
        <!-- Playlist -->
        <?php elseif($item->folder === 'custom' && $item->source === 'playlist'):?>
        <div id="toolbar" class="btn-group float-end me-3" role="group" aria-label="Playlist Toolbar">  
            <button class="btn btn-secondary bi bi-share" id="btn-share" type="button" aria-label="<?= Text::_('SHARE'); ?>"></button>                
            <button class="btn btn-danger bi bi-bookmark-dash" id="btn-rem-fav" type="button"  aria-label="<?= Text::_('FAV_REMOVE'); ?>"></button>
            <button class="btn btn-warning bi bi-clock" data-bs-toggle="modal" href="#wait" id="btn-epg" type="button" aria-label="<?= Text::_('GUIDES'); ?>"></button>
            <button class="btn btn-primary bi bi-fullscreen" id="btn-fullscreen" type="button" aria-label="<?= Text::_('FULLSCREEN'); ?>"></button>
        </div>
        <!-- Default -->
        <?php else:?>
        <div id="toolbar" class="btn-group float-end me-3" role="group" aria-label="Imported Toolbar">              
            <button class="btn btn-secondary bi bi-share" id="btn-share" type="button" aria-label="<?= Text::_('SHARE'); ?>"></button>                
            <button class="btn btn-success bi bi-bookmark-plus" id="btn-add-fav" type="button" aria-label="<?= Text::_('FAV_ADD'); ?>"></button>
            <button class="btn btn-danger bi bi-bookmark-dash" id="btn-rem-fav" type="button"  aria-label="<?= Text::_('FAV_REMOVE'); ?>"></button>            
            <button class="btn btn-warning bi bi-clock" data-bs-toggle="modal" href="#wait" id="btn-epg" id="btn-epg" type="button" aria-label="<?= Text::_('GUIDES'); ?>"></button>
            <button class="btn btn-primary bi bi-fullscreen" id="btn-fullscreen" type="button" aria-label="<?= Text::_('FULLSCREEN'); ?>"></button>
        </div>
        <?php endif; ?>                                             
    </div>
    <div class="tv-content">      
        <!-- Info -->
        <h1><?= $item->name;?></h1>
        <?php if($item->playing): ?>
        <p class="h3"><?= $item->playing->title;?></p>
        <?php endif;?>         
        <!-- VideoJS -->
        <div class="ratio ratio-16x9 bg-dark">
            <video id="my-video" controls="" aspectRatio="16:9" class="embed-responsive-item video-js" data-setup="{}">
                <source src="<?= $item->url;?>" type="<?= $item->mime; ?>" />          
            </video>            
        </div>               
        <!-- Description -->         
        <?php if($item->playing): ?>
            <p class="h4"><?= $item->playing->subtitle;?></p>
            <p><?= $item->playing->desc;?></p>
        <?php endif;?>          
    </div>
    <div class="tv-guide ps-lg-2"> 
        <!-- Guide TV -->
        <?php if($item->guide): ?>
        <table class="table table-striped table-responsive">
            <thead>
                <tr>
                    <th scope="col"><?= Text::_('START');?></th>
                    <th scope="col"><?= Text::_('END');?></th>
                    <th scope="col"><?= Text::_('GUIDE');?></th>
                </tr>
            </thead>      
            <tbody>
                <?php foreach($item->guide as $k):?>               
                <tr>
                    <td class="<?php echo ($k->playnow ? 'text-white bg-primary' : '');?>"><?= $k->start->format('H:i');?></td>
                    <td class="<?php echo ($k->playnow ? 'text-white bg-primary' : '');?>"><?= $k->end->format('H:i');?></td>
                    <td class="<?php echo ($k->playnow ? 'text-white bg-primary' : '');?>"><?= $k->title;?></td>                
                </tr>
                <?php 
                $now    = new \DateTime();
                if($k->end > $now->add(new \DateInterval('PT12H'))){
                    break;
                }
                ?>             
                <?php endforeach;?>
            </tbody>
        </table>
        <?php endif;?>       
    </div>

    <?php 
    $router->sourcelink = $sourcelink;
    require_once('sourcelink.php'); 
    ?>

</main> 
</div>

<script type="text/javascript"> 
jQuery(document).ready(function(){  

    // Load video-js asynchronously
    $.ajax({
        async: false,
        url: 'https://vjs.zencdn.net/8.6.1/video.min.js',
        dataType: "script"
    });    
    
    $('#btn-fullscreen').on('click',function(e){
        var player = videojs('my-video');            
        player.requestFullscreen();
    });

    <?php if(Helpers::searchChannel('custom','playlist',$item->id)): ?>
    $('#btn-add-fav').hide();         
    $('#btn-rem-fav').show(); 
    <?php else: ?>
    $('#btn-rem-fav').hide();        
    $('#btn-add-fav').show();        
    <?php endif; ?>

    //Add to the playlist
    $('#btn-add-fav').on('click', function(e){
        e.preventDefault();
        $.ajax({
            url: '<?= $factory->getTaskURL('playlist.add' , $item->folder, $item->source, $item->id) . '&format=ajax';?>',
                success: function(result){
                    $('#btn-add-fav').hide();
                    $('#btn-rem-fav').show();                    
                }       
        });
    });
    
    //Remove from the playlist
    $('#btn-rem-fav').on('click', function(e){
        e.preventDefault();
        $.ajax({
            url: '<?= $factory->getTaskURL('playlist.remove', $item->folder, $item->source, $item->id) . '&format=ajax';?>',
                success: function(result){                    
                    <?php if($item->folder !== 'custom'): ?>                    
                    $('#btn-rem-fav').hide();                         
                    $('#btn-add-fav').show();                                       
                    <?php else: ?>
                    $('#channels>li[data-id="<?= $item->id; ?>"]').hide();
                    window.location.href = '<?= $factory->getTaskURL('custom');?>';
                    <?php endif; ?>                        
                }
        });        
    });   

    //Force the EPG to reload
    $('#btn-epg').on('click', function(e){

        e.preventDefault();     
          
        $(this).attr('disabled','disabled');

        var wrapper = $('#wait .modal-content');            
        var title   = wrapper.find('.modal-header:first').text();
        var content = wrapper.find('.modal-body:first').text();

        var data = {'key': '<?= $item->epg_key;?>'};        
        var posting = $.post('<?= $factory->getTaskURL('view.cron') . '&id=' . $item->id . '&format=json';?>', data);
        posting.done(function(result){   

            // Replace modal dialog content
            wrapper.addClass(result.success ? 'bg-success text-white' : 'bg-danger text-white');
            wrapper.find('.modal-header:first').text(result.title);
            wrapper.find('.modal-body:first').text(result.content);

            if(result.success) {                                         
                setTimeout( function(){location.reload();} , 1000 );
            } else {
                $('#btn-epg').removeAttr('disabled');          
                // Restore modal content
                wrapper.removeClass(result.success ? 'bg-success text-white' : 'bg-danger text-white');
                wrapper.find('.modal-header:first').text(title);
                wrapper.find('.modal-body:first').text(content);   

                var modal = bootstrap.Modal($('#wait').get(0));                
                modal.hide();
            }                             
        });

    }); 

    $('#btn-share').on('click',function(e){
        e.preventDefault();        
        $('#api').select();
        document.execCommand('copy');

        var wrapper = $('#tv-toast');
        var toast   = wrapper.find('.toast:first').clone();
        toast.find('.toast-body').html('<b><?= Text::_('SHARED');?></b><br /><?= Text::_('SHARED_DESC');?>');
        toast.addClass( 'bg-info');
        toast.appendTo('body');

        var tbs = bootstrap.Toast.getOrCreateInstance(toast.get(0));
        tbs.show();

    });    
    
});      
</script>  

