<?php

namespace Hexagon\system\log;

/**
 * This class implements a log based on php error_log
 * @author mac
 */
class ErrorLogAppender {

    public function append($msg) {
        error_log('[HEXAGON] ' . $msg);
    }
}