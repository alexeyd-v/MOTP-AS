<?php

class Authenticator_Motp
{
    public function validate($hexOtp, $hexSecret, $intPin, $intMaxRange = false, $now = false)
    {
        if ($intMaxRange === false) {
            $intMaxRange = 180;
        }

        if ($now === false) {
            $now = strtotime('now');
        }

        $now = substr($now, 0, -1);

        for ($i = 0; $i <= $intMaxRange; $i++) {
            $time = $now - $i;
            $otp = substr(md5($time . $hexSecret . $intPin), 0, 6);
            if ($otp === $hexOtp) {
                $return = new stdClass();
                $return->offset = -$i;
                $return->time = $time;
                $return->now = $now;
                return $return;
            }

            if ($i == 0) {
                continue;
            }

            $time = $now + $i;
            $otp = substr(md5($time . $hexSecret . $intPin), 0, 6);
            if ($otp === $hexOtp) {
                $return = new stdClass();
                $return->offset = $i;
                $return->time = $time;
                $return->now = $now;
                return $return;
            }

        }
        return false;
    }

    public function generate($hexSecret, $intPin, $now = false)
    {
        if ($now === false) {
            $now = strtotime('now');
        }
        $now = substr($now, 0, -1);
        $otp = substr(md5($now . $hexSecret . $intPin), 0, 6);
        return $otp;
    }
}

function checkMOTP ($user, $passcode, $client=FALSE) {
	$now = gmdate("U");
	$client="Client: " . ( $client ? "$client (RADIUS)" : $_SERVER['REMOTE_ADDR']." (Web)" );

	$number = get_motp_data ($user, $userdata, $accountdatas, $devicedatas);
	if (!$number) { /* no user account found */
		log_auth ($user, "failure", "no valid account");
		return FALSE;
	}

	for ($i=0; $i<$number; $i++) {
		$account = $accountdatas[$i];
		$device  = $devicedatas[$i];
		debug("trying user account nr. $i -- $account->pin, $device->secret");
		$time = checkPasscode ($passcode, $account->pin, $device->secret, $device->timezone, $device->offset, $now);
		if ($time > 0) break;
	}
	debug("user: $user, time: $time");

	$passok = (bool) ($time > 0);					debug("passok=$passok");
	$locked = (bool) ($userdata->tries > MAXTRIES);			debug("locked=$locked");
	$replay = (bool) ($passok) && (! ($time > $device->lasttime) );	debug("replay=$replay");

	if ($passok && !$replay && $locked && (LOCK_GRACE_MINS > 0)) {
		$grace_secs = LOCK_GRACE_MINS * 60;
		if ($time > $userdata->llogin + $grace_secs) 
			$locked = FALSE;
	}

	if ($passok && !$replay && !$locked ) {	// ok
		$status = TRUE;
		$userdata->tries=0;
		$userdata->llogin = $now;
	} else
		$status = FALSE;
	if (!$passok)				// wrong passcode
		$userdata->tries++;
	if ($passok && !$replay)		// no replay
		$device->lasttime = $time;

	if ($passok && !$replay)		// adjust offset
		$device->offset = 10* ( $time - intval( ($now + $device->timezone*3600)/10) );

	update_motp_data ($userdata, $device);

	if ($status)
		log_auth ($user, "success", "One Time Password, Device: " . device_full($device) . ", $client");
	else
		log_auth ($user, "failure", "One Time Password, $client; passok: ". ($passok?"y":"n") .", locked: ". ($locked?"y":"n") .", replay: ". ($replay?"y":"n"));

	return $status;
}
