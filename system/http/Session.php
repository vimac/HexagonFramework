<?php

namespace Hexagon\system\http;

use Hexagon\system\log\Logging;

class Session {

    use Logging;

    /**
     * @var Session
     */
    private static $session;

    public static function init($sessionId = NULL) {
        if (!isset(self::$session)) {
            self::$session = new Session();
        }
        if (php_sapi_name() !== 'cli') {
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
                if (isset($sessionId)) {
                    self::$session->sessionId = session_id($sessionId);
                } else {
                    self::$session->sessionId = session_id();
                }
                self::_logDebug('Session activated, sessionId: ' . self::$session->sessionId);
            } else {
                self::$session->sessionId = session_id();
            }
        } else {
            self::_logNotice('Running in CLI mode, session unavailable');
        }
        return self::$session;
    }

    public static function getInstance() {
        return self::init();
    }

    private function __construct() {
        // prevent public access
    }

    public function getSessionId() {
        return $this->sessionId;
    }

    public function isSessionActivated() {
        return session_status() === PHP_SESSION_ACTIVE;
    }

    public function getSessionName() {
        return session_name();
    }

    public function getSessionCookieParams() {
        return session_get_cookie_params();
    }

    public function getSession($key) {
        if (isset($_SESSION[$key])) {
            return $_SESSION[$key];
        } else {
            return NULL;
        }
    }

    public function setSession($key, $value) {
        $_SESSION[$key] = $value;
    }

    public function deleteSession($key) {
        unset($_SESSION[$key]);
    }

    public function convertSessionArrayToModel($fullObjectName) {
        $obj = new $fullObjectName();
        foreach ($_SESSION as $key => $val) {
            $obj->$key = $val;
        }
        return $obj;
    }

}