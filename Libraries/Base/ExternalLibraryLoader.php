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
 * This class performs all loading and version checking of external libraries,
 * such as Smarty.
 *
 * @category Base
 * @package  Motp-as
 * @author   Jon Spriggs <jon@sprig.gs>
 * @license  http://www.gnu.org/licenses/agpl.html AGPLv3
 * @link     https://github.com/MOTP-AS/MOTP-AS GitHub Repo
 */
class Base_ExternalLibraryLoader {
    public static function findLibrary($strLibrary)
    {
        if ($strLibrary == null || $strLibrary == '') {
            throw new InvalidArgumentException("Need the library name to load");
        }
        $strLibraryPath = dirname(__FILE__) . '/../External';
        if (!file_exists($strLibraryPath . '/' . $strLibrary)) {
            throw new InvalidArgumentException('The Library does not exist');
        }
        $versions = array();
        foreach (new DirectoryIterator($strLibraryPath . '/' . $strLibrary) as $filenames) {
            if ($filenames->isDot() || ! $filenames->isDir()) {
                continue;
            }
            $versions[] = $filenames->getFilename();
        }
        if (count($versions) == 0) {
            throw new InvalidArgumentException('There is no available version for this library');
        }
        if (count($versions) == 1) {
            return end($versions);
        }
        sort($versions);
        return end($versions);
    }
}