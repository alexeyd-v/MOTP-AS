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
 * This class provides the Basic Authentication algorythm checking.
 *
 * @category Authenticators
 * @package  Motp-as
 * @author   Jon Spriggs <jon@sprig.gs>
 * @license  http://www.gnu.org/licenses/agpl.html AGPLv3
 * @link     https://github.com/MOTP-AS/MOTP-AS GitHub Repo
 */
class Authenticator_Basic
{
    /**
     * This function validates a stored username and hashed password against a
     * supplied username and password, assuming a supplied (or not) salt value.
     *
     * @param string $strHash     The hash (username:Sha1OfSaltPassword)
     * @param string $strUsername The supplied username
     * @param string $strPassword The supplied password
     * @param string $strSalt     The salt to use in generating the hash
     * 
     * @return boolean
     */
    public static function validate($strHash, $strUsername, $strPassword, $strSalt = false)
    {
        return $strHash == Authenticator_Basic::generate($strUsername, $strPassword, $strSalt);
    }

    /**
     * A function to calcuate a hash for processing
     *
     * @param string $strUsername The username to use in the hash
     * @param string $strPassword The password to use for hashing
     * @param string $strSalt     (Optional) The salt to use when hashing the 
     * value.
     * 
     * @return string 
     */
    public static function generate($strUsername, $strPassword, $strSalt = false)
    {
        if ($strSalt === false) {
            $strSalt = 'Just an ordinary salt, never mind what is going to be ' .
                       'in here - you will change it, right?';
        }

        $strSalt .= strtolower($strUsername);

        $result = strtolower($strUsername) . ':' . sha1(sha1($strSalt . sha1($strPassword . $strSalt)) . $strSalt);
        return $result;
    }
}
