<?php

namespace Hexagon\system\log;

use Exception;

/**
 * This class implements a log based on php error_log
 * @author mac
 */
class ErrorLogAppender implements ILogAppender {

    use ExceptionTrace;

    public function append($level, $msg, Exception $ex = NULL) {
        error_log('[HEXAGON] ' . $msg);
        if (isset($ex)) {
            error_log($this->_traceException($ex));
        }
    }

}