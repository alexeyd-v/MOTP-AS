<?php

class Authenticator_Basic
{
    public function validate($strHash, $strUsername, $strPassword, $strSalt = false)
    {
        return $strHash == Authenticator_Basic::generate($strUsername, $strPassword, $strSalt);
    }

    public function generate($strUsername, $strPassword, $strSalt = false)
    {
        if ($strSalt === false) {
            $strSalt = 'Just an ordinary salt, never mind what is going to be in here - you will change it, right?';
        }

        $strSalt .= $strUsername;

        $result = $strUsername . ':' . sha1(sha1($strSalt . sha1($strPassword . $strSalt)) . $strSalt);
        return $result;
    }
}
