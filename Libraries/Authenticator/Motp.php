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
 * This class provides the M-OTP algorythm checking.
 *
 * @category Authenticators
 * @package  Motp-as
 * @author   Jon Spriggs <jon@sprig.gs>
 * @license  http://www.gnu.org/licenses/agpl.html AGPLv3
 * @link     https://github.com/MOTP-AS/MOTP-AS GitHub Repo
 */

class Authenticator_Motp
{
    /**
     * This function validates a M-OTP generated string, by re-generating that
     * string using the known secret and known PIN for each of the times in the
     * appropriate range
     *
     * @param string $hexOtp      The OTP string to validate
     * @param string $hexSecret   The known secret
     * @param int    $intPin      The known PIN
     * @param int    $intMaxRange The maximum range of times to validate against
     * @param int    $intTimeNow  (Optional) The time now - for unit testing 
     * purposes.
     * 
     * @return stdClass 
     */
    public static function validate($hexOtp, $hexSecret, $intPin, $intMaxRange = false, $intTimeNow = false)
    {
        if ($intMaxRange === false) {
            $intMaxRange = 180;
        }

        if ($intTimeNow === false) {
            $intTimeNow = strtotime('now');
        }

        $intTimeNow = substr($intTimeNow, 0, -1);

        for ($i = 0; $i <= $intMaxRange; $i++) {
            $time = $intTimeNow - $i;
            $otp = substr(md5($time . $hexSecret . $intPin), 0, 6);
            if ($otp === $hexOtp) {
                $return = new stdClass();
                $return->offset = -$i;
                $return->time = $time;
                $return->now = $intTimeNow;
                return $return;
            }

            if ($i == 0) {
                continue;
            }

            $time = $intTimeNow + $i;
            $otp = substr(md5($time . $hexSecret . $intPin), 0, 6);
            if ($otp === $hexOtp) {
                $return = new stdClass();
                $return->offset = $i;
                $return->time = $time;
                $return->now = $intTimeNow;
                return $return;
            }
        }
        return false;
    }

    /**
     * Given a known secret, a known pin and the time now, generate the M-OTP
     * code for now.
     *
     * @param string $hexSecret The secret for the M-OTP token
     * @param int    $intPin    The PIN for the M-OTP code
     * @param int    $now       (Optional) The timestamp for now
     * 
     * @return string 
     */
    public static function generate($hexSecret, $intPin, $now = false)
    {
        if ($now === false) {
            $now = strtotime('now');
        }
        $now = substr($now, 0, -1);
        $otp = substr(md5($now . $hexSecret . $intPin), 0, 6);
        return $otp;
    }
}