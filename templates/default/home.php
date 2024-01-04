<?php defined('_TVEXEC') or die; ?>

<main role="main" class="justify-content-center p-3">
    <div class="tv-channels-grid row row-cols-3 row-cols-sm-4 row-cols-md-6 g-2 g-lg-3 mt-1"> 
        <?php foreach ($router->model as $item):?>   
        <div class="col">
            <div class="card p-3 border bg-light text-center" data-link="<?= $item->link;?>">
                <img src="<?= $item->image;?>" class="card-img-top" title="<?= htmlspecialchars($item->name);?>" alt="<?= htmlspecialchars($item->name);?>">
                <div class="card-body p-0">
                    <h5 class="card-title text-truncate"><?= $item->name;?></h5>
                </div>
            </div>             
        </div>
        <?php endforeach; ?>   
    </div>
</main>

<!-- Click on box -->
<script type="text/javascript">   
jQuery(document).ready(function(){   
    $('.card').on('click' , function(e){
            window.location.href = $(this).attr('data-link');
    });

});                 
</script>  
