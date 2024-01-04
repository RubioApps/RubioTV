<?php defined('_TVEXEC') or die;?>
<!doctype html>
<html lang="<?= $language->getTag(); ?>"<?= ($language->isRtl() ? ' dir="rtl"' : ''); ?>>
    <head>
	<meta charset="utf-8">
        <title><?= $router->getPageTitle() . ' - ' . $config->sitename;?></title>    
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
        <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
        <link rel="icon" type="image/png" sizes="16x16" href="<?= $factory->getAssets() ;?>/favicons/favicon-16x16.png" />
	    <link rel="icon" type="image/png" sizes="32x32" href="<?= $factory->getAssets() ;?>/favicons/favicon-32x32.png" />
	    <link rel="icon" type="image/png" sizes="48x48" href="<?= $factory->getAssets() ;?>/favicons/favicon-48x48.png" />     
        <link rel="icon" type="image/png" sizes="64x64" href="<?= $factory->getAssets() ;?>/favicons/favicon-64x64.png" />    
        <link rel="icon" type="image/png" sizes="128x128" href="<?= $factory->getAssets() ;?>/favicons/favicon-128x128.png" />    
        <meta name="robots" content="noindex,nofollow">
        <meta name="keywords" content="brave, search">
        <meta name="description" content="brave, search">
        <meta name="referrer" content="strict-origin-when-cross-origin">                                     
        <!-- Basic Jquery -->
        <?= $factory->addCDN('js','https://code.jquery.com/jquery-3.7.1.min.js','sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=','anonymous');?>          
        <?= $factory->addCDN('js','https://code.jquery.com/ui/1.13.2/jquery-ui.min.js','sha256-lSjKY0/srUM9BE3dPm+c4fBo1dky2v27Gdjm2uoZaL0=','anonymous');?>          
        <?= $factory->addCDN('css','https://vjs.zencdn.net/8.6.1/video-js.css');?> 
        <!-- Bootstrap v5 -->        
        <?= $factory->addCDN('css','https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css','sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN','anonymous');?>
        <?= $factory->addCDN('js','https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js','sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL','anonymous');?>
        <?= $factory->addCDN('css','https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.min.css');?>               
        <!-- Additional styles -->        
        <?= $factory->addCDN('css',$factory->getAssets() . '/default.css');?>        
    </head>    
    <body>                                 
        <?php require_once $factory->getPage('header'); ?>                               
        <div class="tv-main container-lg mt-3 my-3 mb-5 pb-3">
            <?php require_once $factory->getPage(); ?> 
        </div> 
        <?php require_once $factory->getPage('footer'); ?>                                               
        <?php require_once $factory->getPage('toast'); ?> 
        <?php echo $factory->getJScripts();?>                 
    </body>                              
</html>                             