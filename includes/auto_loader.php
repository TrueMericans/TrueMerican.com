<?php

function merican_auto_loader($className)
{
    $possibilities = array(
        APPLICATION_PATH.'includes/'.$className.'.php',
        APPLICATION_PATH.'pages/'.$className.'.php'
    );
    foreach ($possibilities as $file)
    {
        if (file_exists($file))
        {
            require_once($file);
            return true;
        }
    }
    return false;
}
spl_autoload_register('merican_auto_loader');
?>