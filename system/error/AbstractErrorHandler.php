<?php

namespace Hexagon\system\error;

use \Exception;
use \Hexagon\Context;
use \Hexagon\system\log\Logging;

abstract class AbstractErrorHandler {
    
    use Logging;
    
    public abstract static function handleError($errno, $errstr, $errfile, $errline, $errcontext);
    
    public abstract static function handleException(Exception $ex);
    
}