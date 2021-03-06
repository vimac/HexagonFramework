<?php

namespace Hexagon\system\exception;

use ErrorException;
use Exception;
use Hexagon\system\log\Logging;
use Hexagon\system\result\Processor;

class ExceptionProcessor {

    use Logging;

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

    public function processFatal() {
        $lastError = error_get_last();
        $dispArr = [E_ERROR, E_PARSE, E_CORE_ERROR];

        if (!empty($lastError) && isset($this->handlerClass)) {
            if (in_array($lastError['type'], $dispArr)) {
                $handler = new $this->handlerClass();

                if (method_exists($handler, "handleFatal")) {
                    $result = $handler->handleFatal($lastError['type'], $lastError['message'],
                        $lastError['file'], $lastError['line']);

                    Processor::getInstance()->processResult($result);
                }
            }
        }
    }

    public function setHandler($handlerClass) {
        /**
         * set error handler if never set handler
         */
        if (!isset($this->handlerClass)) {
            set_error_handler([self::$p, 'processError'], error_reporting());
            set_exception_handler([self::$p, 'processException']);
            register_shutdown_function([self::$p, 'processFatal']);
        }

        $this->handlerClass = $handlerClass;
    }

    public function getHandler() {
        return $this->handlerClass;
    }
}