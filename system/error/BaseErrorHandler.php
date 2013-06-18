<?php

namespace Hexagon\system\error;

use Hexagon\Context;
use Hexagon\system\log\Logging;

class BaseErrorHandler {
    
    use Logging;
    
    public static function handleError($errno, $errstr, $errfile, $errline, $errcontext) {
        self::logErr(sprintf('CODE:[%d], MSG:[%s], FILE:[%s:%d]', $errno , $errstr , $errfile , $errline));
    }
    
    public static function handleException(\Exception $ex) {
        self::logErr($ex);
    }
    
}