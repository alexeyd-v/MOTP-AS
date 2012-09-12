<?php

class Authenticator_Basic_Test extends PHPUnit_Framework_TestCase
{
    public function testClassExists()
    {
        $objBasic = new Authenticator_Basic();
        $this->assertTrue(is_object($objBasic));
    }

    public function testBasicFailsCorrectly()
    {
        $this->assertFalse(Authenticator_Basic::validate('', '', ''));
        $this->assertFalse(Authenticator_Basic::validate('', 'username', 'badpassword'));
    }

    public function testBasicAgainstValidCredentials()
    {
        $this->assertTrue(Authenticator_Basic::validate('username:4f7cdda1fe9c36b54c0747840207ec905d5ed876', 'username', 'password'));
        $this->assertTrue(Authenticator_Basic::validate('admin:afab44051c46c17c44bf4b271319fb7929fe3b2e', 'admin', 'acomplexpassword', 'an additional salt'));
    }

    public function testBasicCreation()
    {
        $hexHash = 'username:3ac21cc8449955efba51682b0fd6808fc684d7fa';
        $hash = Authenticator_Basic::generate('username', 'password', 'salt');
        $this->assertTrue($hash == $hexHash);
    }
}
