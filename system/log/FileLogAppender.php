<?php

namespace Hexagon\system\log;

use Hexagon\Context as Context;

/**
 * This class implements a single file log appender
 * @author mac
 */
class FileLogAppender{
    
    private $f;
    
    public function __construct() {
        $config = Context::$appConfig;
        if (empty($config->logPath)) {
            $config->logPath = Context::$appBasePath . DIRECTORY_SEPARATOR . 'logs';
        }
        $checkDir = FALSE;
        if (!is_dir($config->logPath)) {
            $checkDir = mkdir($config->logPath);
        } else {
            $checkDir = TRUE;
        }
        
        if ($checkDir) {
            $this->logFile = $config->logPath . DIRECTORY_SEPARATOR . Context::$appNS . $config->logNameSuffix;
        }
    }
    
    public function append($msg) {
        if (!$this->f) {
            @$this->f = fopen($this->logFile, 'a');
        }
        @fwrite($this->f, date('[Y-m-d H:i:s] ') . $msg . "\n");
    }
    
    /**
     * 析构方法
     */
    public function __destruct(){
        @fclose($this->f);
    }
}