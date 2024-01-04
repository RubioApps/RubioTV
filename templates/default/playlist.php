<?php 

defined('_TVEXEC') or die;

use \RubioTV\Framework\Language\Text;
?>
<?php if(is_array($router->model) && count($router->model)): ?>

<section class="tv-toolbar ps-lg-2 clearfix"> 
    <div id="toolbar" class="btn-group float-end me-3" role="group" aria-label="Toolbar">
        <button id="btn-custom" class="btn btn-primary bi bi-pencil-square" type="button" aria-label="<?= Text::_('CUSTOM'); ?>"></button>
    </div>
</section>    

<!-- Favorites -->
<main role="main" class="justify-content-center">
    <div class="row row-cols-4 row-cols-md-6 row-cols-lg-10 g-2 g-lg-3 mt-1"> 
        <?php foreach ($router->model as $item):?>   
        <div class="col d-flex align-items-stretch">
            <div class="card p-3 bg-light text-center" data-link="<?= $item->link;?>">
                    <img src="<?= $item->image;?>" class="card-img-top"
                    data-remote="<?= $item->remote;?>"  
                    title="<?= htmlspecialchars($item->name);?>" 
                    alt="<?= htmlspecialchars($item->name);?>" />
                <div class="card-body d-flex flex-column">
                    <h5 class="card-title text-truncate"><?= $item->name;?></h5>                
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

<!-- Click on boxes -->
<script type="text/javascript">   
jQuery(document).ready(function(){   

    $('#btn-custom').on('click' , function(e){
            window.location.href = '<?= $factory->getTaskURL('custom', 'favorites', 'custom');?>';
    });     

    $('.card').on('click' , function(e){
            window.location.href = $(this).attr('data-link');
    });      
});                 
</script>  

<?php else:?>
    <div class="container">
        <div class="row">
            <div class="h2"><?= Text::_('FAVORITES'); ?></div>
            <div class="p"><?= Text::_('FAVORITES_EMPTY'); ?></div>
        </div>
    </div>
<?php endif;?>