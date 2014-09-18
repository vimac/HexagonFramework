<?php

namespace Hexagon\system\security;

use Hexagon\Context;
use Hexagon\system\http\HttpRequest;
use Hexagon\system\http\HttpResponse;
use Hexagon\system\security\cipher\Cipher;

class Security {

    private static $csrfTokenHash = '';

    /**
     * @return Cipher
     */
    public static function getCipher() {
        $name = Context::$appConfig->cipher;
        if ($name) {
            return $name::getInstance();
        } else {
            return NULL;
        }
    }

    public static function vertifyCSRFToken() {
        if (count($_POST) === 0) {
            return self::setCSRFToken();
        }
        $token = Context::$appConfig->csrfTokenName;
        $request = HttpRequest::getCurrentRequest();
        $v = $request->getCSRFTokenHash();
        if (empty($v) || !isset($_COOKIE[$token])) {
            throw new ForbiddenAccess();
        }
        if ($v !== $_COOKIE[$token]) {
            throw new ForbiddenAccess();
        }
        $request->removeCSRFTokenHash();
        unset($_COOKIE[$token]);
        return self::setCSRFToken();
    }

    public static function getCSRFTokenHash() {
        return self::$csrfTokenHash;
    }

    public static function setCSRFToken() {
        $token = Context::$appConfig->csrfTokenName;

        if (!isset($_COOKIE[$token])) {
            $hash = md5(uniqid(rand(), TRUE));
            self::$csrfTokenHash = $hash;
            HttpResponse::getCurrentResponse()->setCookie($token, $hash);
        } else {
            self::$csrfTokenHash = $_COOKIE[$token];
        }
    }

}

class ForbiddenAccess extends \Exception {
    public function __construct($reason = '') {
        parent::__construct($reason, 500);
    }
}