<?php

namespace Hexagon\system\log;

use Exception;

/**
 * This class implements a log based on php error_log
 * @author mac
 */
class ErrorLogAppender implements ILogAppender {

    public function append($msg, Exception $ex = NULL) {
        error_log('[HEXAGON] ' . $msg);
        if (isset($ex)) {
            error_log($ex->getTraceAsString());
        }
    }

}