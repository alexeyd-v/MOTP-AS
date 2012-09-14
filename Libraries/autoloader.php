<?php
/**
 * Mobile OTP Authentication Server - MOTP-AS
 * 
 * This project strives to bring you the best implementation of the M-OTP
 * authentication system, integrated with Radius.
 *
 * PHP version 5
 *
 * @category Core
 * @package  Motp-as
 * @author   Jon Spriggs <jon@sprig.gs>
 * @license  http://www.gnu.org/licenses/agpl.html AGPLv3
 * @link     https://github.com/MOTP-AS/MOTP-AS GitHub Repo
 */

/**
 * This function acts as the autoloader for all subsequent calls.
 *
 * @param string $className Class to load
 * 
 * @return boolean 
 */
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
