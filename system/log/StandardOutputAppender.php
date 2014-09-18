<?php

namespace Hexagon\system\log;

/**
 * This class implements a standard out log appender
 *
 * @author mac
 */
class StandardOutputAppender {

    public function append($msg) {
        fwrite(STDOUT, date('[Y-m-d H:i:s] ') . $msg . PHP_EOL);
    }

}