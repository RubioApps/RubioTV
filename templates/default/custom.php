<?php 
defined('_TVEXEC') or die; 

use \RubioTV\Framework\Language\Text;
?>

<main role="main" class="justify-content-center p-3">
    <div class="row row-cols-3 row-cols-md-5 row-cols-lg-6 g-2 g-lg-3 mt-1"> 
        <?php foreach ($router->model as $item):?>   
        <div class="col">
            <div class="card p-3 border bg-light text-center" data-link="<?= $item->link;?>">
                <img src="<?= $item->image;?>" class="card-img-top" title="<?= htmlspecialchars($item->name);?>" alt="<?= htmlspecialchars($item->name);?>">
                <div class="card-body p-0">
                    <h6 class="card-title text-truncate"><?= $item->name;?></h6>
                </div>
            </div>             
        </div>
        <?php endforeach; ?>   
    </div>
</main>
<section class="container g-3 mt-5 pt-3 border-top">
    <h3><?= Text::_('IMPORT');?></h3>
    <p><?= Text::_('IMPORT_DESC');?></p>
    <div class="accordion" id="import-utils">
    <div class="accordion-item">
        <h5 class="accordion-header" id="heading-fields">         
            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#import-fields" aria-expanded="false" aria-controls="import-fields">
            <?= Text::_('IMPORT_FIELDS');?>
            </button>
        </h5>
        <div id="import-fields" class="accordion-collapse collapse" aria-labelledby="heading-fields" data-bs-parent="#import-utils">
            <div class="accordion-body">
                <form action="<?= $factory->getTaskURL('custom.add');?>" method="post">                
                    <label for="fld-name" class="m-2 form-label"><?= Text::_('NAME');?></label>
                    <input id="fld-name" name="name" class="form-control" type="text" placeholder="<?= Text::_('ENTER_NAME');?>" aria-label="<?= Text::_('ENTER_NAME');?>" />
                    <label for="fld-url" class="m-2 mt-4 form-label"><?= Text::_('URL');?></label>                    
                    <input id="fld-url" name="url" class="form-control" type="text" placeholder="<?= Text::_('ENTER_URL');?>" aria-label="<?= Text::_('ENTER_URL');?>" />
                    <label for="fld-id" class="m-2 mt-4 form-label"><?= Text::_('ID');?></label>
                    <span class="form-text">(<?= Text::_('OPTIONAL');?>)</span>                    
                    <input id="fld-id" name="id" class="form-control" type="text" placeholder="<?= Text::_('ENTER_ID');?>" aria-label="<?= Text::_('ENTER_ID');?>" />                       
                    <label for="fld-logo" class="m-2 mt-4 form-label"><?= Text::_('LOGO');?></label>
                    <span class="form-text">(<?= Text::_('OPTIONAL');?>)</span>                    
                    <input id="fld-logo" name="logo" class="form-control" type="text" placeholder="<?= Text::_('ENTER_LOGO');?>" aria-label="<?= Text::_('ENTER_LOGO');?>" />
                    <input type="submit" class="mt-3 mb-1 form-control btn btn-primary" />
                </form>  
            </div>
        </div>
    </div>
    <div class="accordion-item">
        <h5 class="accordion-header" id="heading-text">
            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#import-text" aria-expanded="false" aria-controls="import-text">
                <?= Text::_('IMPORT_TEXT');?>
            </button>
        </h5>
        <div id="import-text" class="accordion-collapse collapse" aria-labelledby="heading-text" data-bs-parent="#import-utils">
            <div class="accordion-body">
                <form action="<?= $factory->getTaskURL('custom.brut');?>" method="post">
                    <label for="fld-text" class="m-2 form-label"><?= Text::_('ENTER_TEXT');?></label>
                    <textarea id="fld-text" name="brut" class="form-control" rows="3"></textarea>
                    <input type="submit" class="mt-3 mb-1 form-control btn btn-primary" />
                </form>                 
            </div>
        </div>
    </div>
    <div class="accordion-item">
        <h5 class="accordion-header" id="heading-file">
            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#import-file" aria-expanded="false" aria-controls="import-file">
                <?= Text::_('IMPORT_FILE');?>
            </button>
        </h5>
        <div id="import-file" class="accordion-collapse collapse" aria-labelledby="heading-file" data-bs-parent="#import-utils">
            <div class="accordion-body">
                <form action="<?= $factory->getTaskURL('custom.upload');?>" method="post" enctype="multipart/form-data">
                    <input id="fld-file" name="file" class="m-2 form-control" type="file" />
                    <input type="submit" class="mt-3 mb-1 form-control btn btn-primary" />
                </form>                  
            </div>
        </div>
    </div>
    </div>
</section>

<!-- Click on box -->
<script type="text/javascript">   
jQuery(document).ready(function(){   
    $('.card').on('click' , function(e){
            window.location.href = $(this).attr('data-link');
    });

});                 
</script>  
