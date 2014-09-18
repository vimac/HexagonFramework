<?php

namespace Hexagon\system\security\cipher;

use Hexagon\Context;
use Hexagon\system\log\Logging;

class Rijndael256Cipher extends Cipher {
    use Logging;

    protected $cipher = MCRYPT_RIJNDAEL_256;
    protected $mode = MCRYPT_MODE_CBC;
    protected $key = '';
    protected $iv = NULL;

    protected function __construct() {
        $this->key = md5(Context::$appConfig->encryptionKey);
        $this->iv = Context::$appConfig->cipherIv;
    }

    public function encrypt($text) {
        $re = mcrypt_encrypt($this->cipher, $this->key, trim($text), $this->mode, $this->iv);
        return $re;
    }

    public function decrypt($text) {
        $re = trim(mcrypt_decrypt($this->cipher, $this->key, $text, $this->mode, $this->iv));
        return $re;
    }

}