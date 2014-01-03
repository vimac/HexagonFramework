<?php

namespace Hexagon\system\exception;

use \Exception;
use \ErrorException;
use \Hexagon\system\result\Processor;

class ExceptionProcessor {
    
    /**
     * @var string
     */
    protected $handlerClass;
    
    /**
     * @var ExceptionProcessor
     */
    public static $p;
    
    public static function getInstance() {
        if (!isset(self::$p)) {
            self::$p = new self();
        }
        return self::$p;
    }
    
    public function processError($errno, $errstr, $errfile, $errline, $errcontext) {
        throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
    }
    
    public function processException(Exception $ex) {
        if (isset($this->handlerClass)) {
            if (ob_get_level() !== 0) {
                ob_end_clean();
            }
            
            $className = $this->handlerClass;
            
            $handler = new $className();
            $result = $handler->handleException($ex);
            
            Processor::getInstance()->processResult($result);
        } else {
            throw $ex;
        }
    }
    
    public function setHandler($handlerClass) {
        /**
         * set error handler if never set handler
         */
        if (!isset($this->handlerClass)) {
            set_error_handler([self::$p, 'processError']);
            set_exception_handler([self::$p, 'processException']);
        }
        
        $this->handlerClass = $handlerClass;
    }
    
    public function getHandler() {
        return $this->handlerClass;
    }
}