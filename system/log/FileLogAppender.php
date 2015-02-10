<?php

namespace Hexagon\system\log;

use Exception;
use Hexagon\Context;

/**
 * This class implements a single file log appender
 * @author mac
 */
class FileLogAppender implements ILogAppender {

    use ExceptionTrace;

    private $f;

    public function __construct($filename) {
        $checkDir = FALSE;
        chdir(Context::$appBasePath);
        $dir = dirname($filename);
        if (!is_dir($dir)) {
            $checkDir = mkdir($dir);
        } else {
            $checkDir = TRUE;
        }

        if ($checkDir) {
            $this->logFile = $filename;
        }
    }

    public function append($level, $msg, Exception $ex = NULL) {
        if (!$this->f) {
            @$this->f = fopen($this->logFile, 'a');
        }
        @flock($this->f, LOCK_EX);
        @fwrite($this->f, date('[Y-m-d H:i:s] ') . $msg . PHP_EOL);
        if (isset($ex)) {
            @fwrite($this->f, $this->_traceException($ex) . PHP_EOL);
        }
        @flock($this->f, LOCK_UN);
    }

}