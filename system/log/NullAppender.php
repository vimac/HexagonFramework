<?php

namespace Hexagon\system\log;

use Exception;

/**
 * This class implements a standard out log appender
 *
 * @author mac
 */
class NullAppender implements ILogAppender {

    public function append($level, $msg, Exception $ex = NULL) {
    }

}