<?php defined('_TVEXEC') or die; ?>

<?php if(is_array($router->model) && count($router->model)): ?>

<?php require_once('search.php');?> 
<!-- Countries -->
<main role="main" class="p-3 tv-folder-grid">
    <div class="row row-cols-3 row-cols-md-5 row-cols-lg-6 g-2 g-lg-3 mt-1"> 
    <?php foreach ($router->model as $item):?>
        <div class="col ps-1 pe-1">            
            <a class="btn btn-light border d-grid" href="<?= $factory->getTaskURL('channels','countries', strtolower($item->code . ':' . $item->name));?>">
                <h6 class="text-truncate m-1">
                    <span class="me-1"><?= $item->flag;?></span>
                    <?= $item->name;?>
                </h6>
            </a>
        </div>
    <?php endforeach; ?>   
    </div>
</main>
<!-- Pagination -->
<section class="container mt-3">
    <?= $router->pagination->getPagesLinks(); ?> 
</section>

<?php require_once('sourcelink.php');?>

<?php else:?>
    <?php require_once('404.php');?>
<?php endif;?>