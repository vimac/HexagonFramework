<?php

namespace Hexagon\system\exception;

use \Exception;
use \Hexagon\Context;
use \Hexagon\system\log\Logging;
use \Hexagon\system\result\ResultHelper;

abstract class ExceptionHandler {
    
    use Logging, ResultHelper;
    
    public abstract function handleException(Exception $ex);
    
}