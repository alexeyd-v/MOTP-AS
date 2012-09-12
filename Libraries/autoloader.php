<?php
function __autoload($className)
{
    $arrClass = explode('_', $className);
    $class_path  = dirname(__FILE__);
    foreach ($arrClass as $class_point) {
        $class_path .= '/' . $class_point;
    }
    if (is_file($class_path . '.php')) {
        include_once $class_path . '.php';
        return true;
    }
    return false;
}

spl_autoload_register('__autoload');
