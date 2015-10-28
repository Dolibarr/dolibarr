<?php
// use the restler auto loader
require_once DOL_DOCUMENT_ROOT.'/includes/restler/framework/Luracast/Restler/AutoLoader.php';
return call_user_func(function () {
    $loader = Luracast\Restler\AutoLoader::instance();
    spl_autoload_register($loader);
    return $loader;
});