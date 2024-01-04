<?php defined('_TVEXEC') or die;?>

<?php 
use \RubioTV\Framework\Factory;
use \RubioTV\Framework\Language\Text;
?>
<main role="main" class="justify-content-center">
    <div class="row p-0 mt-0">
        <div class="col mx-auto text-center">
            <h3><?= Text::_('PAGE_NOT_FOUND');?></h3>
        </div>
    <div class="row"> 
        <div class="col mx-auto text-center">  
            <img class="w-25" src="<?= Factory::getAssets() . '/images/404.jpg';?>" alt="<?= Text::_('PAGE_NOT_FOUND');?>" />            
        </div>
    </div>
</main>
