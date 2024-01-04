<?php defined('_TVEXEC') or die;?>
<footer class="container-md p-1 mt-5 mx-auto w-100 bg-light">
    <div class="tv-footer">
    <nav class="navbar navbar-expand">
        <ul class="navbar-nav d-flex">
            <?php foreach ($config->links as $k=>$v):?>
            <li class="nav-item">
                <a class="nav-link" href="<?= $v; ?>" target="_blank"><?= htmlspecialchars($k); ?></a>
            </li>            
            <?php endforeach; ?>
        </ul>
    </nav>
    </div>
</footer>

