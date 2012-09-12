<?php

class Authenticator_Motp_Test extends PHPUnit_Framework_TestCase
{
    public function testClassExists()
    {
        $objMotp = new Authenticator_Motp();
        $this->assertTrue(is_object($objMotp));
    }

    public function testMotpFailsCorrectly()
    {
        $this->assertFalse(Authenticator_Motp::validate('', '', '', ''));
        $this->assertFalse(Authenticator_Motp::validate('', '0000', '1234567890abcdef', 0, strtotime('now')));
    }

    public function testMotpAgainstValidExactTimes()
    {
        date_default_timezone_set('UTC');
        $intNow = strtotime('1970-01-01 00:00:01');

        $intPin = '0000';
        $hexSecret = '1234567890abcdef';
        $hexOtp = '906133';
        $result = Authenticator_Motp::validate($hexOtp, $hexSecret, $intPin, 0, $intNow);
        $this->assertTrue(is_object($result));
        $this->assertTrue($result->offset == 0);
        $this->assertTrue($result->time == 0);

        $intPin = '0000';
        $hexSecret = '1a2b3c4d5e6f7890';
        $hexOtp = 'a73b3e';
        $this->assertTrue(is_object(Authenticator_Motp::validate($hexOtp, $hexSecret, $intPin, 0, $intNow)));

        $intPin = '1234';
        $hexSecret = '1234567890abcdef';
        $hexOtp = 'e3a63b';
        $this->assertTrue(is_object(Authenticator_Motp::validate($hexOtp, $hexSecret, $intPin, 0, $intNow)));

        $intNow = strtotime('2010-01-01 00:00:00');

        $intPin = '0000';
        $hexSecret = '1234567890abcdef';
        $hexOtp = '5db93a';
        $this->assertTrue(is_object(Authenticator_Motp::validate($hexOtp, $hexSecret, $intPin, 0, $intNow)));

        date_default_timezone_set('Etc/GMT+1');
        $intNow = strtotime('2010-01-01 00:00:00');

        $intPin = '0000';
        $hexSecret = '1234567890abcdef';
        $hexOtp = '6fdfbb';
        $this->assertTrue(is_object(Authenticator_Motp::validate($hexOtp, $hexSecret, $intPin, 0, $intNow)));
    }

    public function testMotpAgainstValidDriftTimes()
    {
        date_default_timezone_set('UTC');
        $intNow = strtotime('1970-01-01 00:00:00');

        $intPin = '0000';
        $hexSecret = '1234567890abcdef';
        $hexOtp = '308c3c';
        $result = Authenticator_Motp::validate($hexOtp, $hexSecret, $intPin, 180, $intNow);
        $this->assertTrue(is_object($result));
        $this->assertTrue($result->offset == -180);
        $this->assertTrue($result->time == -180);
        $this->assertTrue($result->now == 0);

        $hexOtp = 'ae2f6e';
        $result = Authenticator_Motp::validate($hexOtp, $hexSecret, $intPin, 180, $intNow);
        $this->assertTrue(is_object($result));
        $this->assertTrue($result->offset == 180);
        $this->assertTrue($result->time == 180);
        $this->assertTrue($result->now == 0);

        $intNow = strtotime('2010-01-01 00:00:00');
        $intPin = '0000';
        $hexSecret = '1234567890abcdef';
        $hexOtp = '26c786';
        $result = Authenticator_Motp::validate($hexOtp, $hexSecret, $intPin, 180, $intNow);
        $this->assertTrue(is_object($result));
        $this->assertTrue($result->offset == 180);
        $this->assertTrue($result->time == 126230580);
        $this->assertTrue($result->now == 126230400);
    }

    public function testMotpCreation()
    {
        date_default_timezone_set('UTC');
        $intNow = strtotime('1970-01-01 00:00:00');

        $intPin = '0000';
        $hexSecret = '1234567890abcdef';
        $hexOtp = 'affcf2';
        $otp = Authenticator_Motp::generate($hexSecret, $intPin, $intNow);
        $this->assertTrue($otp == $hexOtp);
    }
}
