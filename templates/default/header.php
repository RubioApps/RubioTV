<?php 
defined('_TVEXEC') or die;

?>
<header class="navbar navbar-expand-lg navbar-dark tv-navbar sticky-top">  
  <nav class="container-lg flex-wrap flex-lg-nowrap">  
    <div class="d-flex">  
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainmenu" aria-controls="mainmenu" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
      </button>
    </div>    
    <a class="navbar-brand tv-brand text-center text-truncate" href="<?= $factory->getTaskURL();?>">
      <div class="h3 fw-bold"><?= $config->sitename; ?></div>
    </a>   
    <?php if($task == 'view'):?>
    <div class="tv-navbar-toggler">      
      <button class="navbar-toggler" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebar" aria-controls="sidebar" aria-label="Toggle Channels">
        <div class="h1 align-top top p-0">...</div>
      </button>      
    </div>
    <?php endif;?>    
    <div class="collapse navbar-collapse" id="mainmenu">
      <ul class="navbar-nav me-auto mb-2 mb-lg-0"> 
        <?php 
        foreach($router->getMenu() as $f)
        {
          $task   = $factory->getParam('task');  
          $folder = $factory->getParam('folder');   
          $active = ($task === $f->id) || ($task !== 'guides' && $folder === $f->id);      
        ?> 
        <li class="nav-item">
          <a class="nav-link<?= ($active  ? ' active':'');?>" href="<?= $factory->getTaskURL($f->id);?>">
            <?= $f->name; ?>
          </a>
        </li>  
        <?php } ?>            
      </ul>
    </div>    
  </nav>
</header>
