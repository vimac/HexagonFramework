<?php

namespace Hexagon\system\log;

use Exception;

/**
 * This class implements a standard out log appender
 *
 * @author mac
 */
class StandardOutputAppender {

    public function append($level, $msg, Exception $ex = NULL) {
        fwrite(STDOUT, date('[Y-m-d H:i:s] ') . $msg . PHP_EOL);
        if (isset($ex)) {
            fwrite(STDOUT, $ex->getTraceAsString());
        }
    }

}