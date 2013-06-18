<?php

namespace Hexagon\system\security\cipher;

interface ICipher {
    /**
     * @param string $text
     * @return string encrypted text
     */
    public function encrypt($text);

    /**
     * @param string $text
     * @return string decrypted text
    */
    public function decrypt($text);
}