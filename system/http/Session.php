<?php

namespace Hexagon\system\http;

use \Hexagon\system\log\Logging;

class Session {
    
    use Logging;

    /**
     *
     * @var Session
     */
    private static $session;

    public static function init($sessionId = NULL) {
        self::$session = new Session();
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

}