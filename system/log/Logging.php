<?php

namespace Hexagon\system\log;

use \Hexagon\Context;
use \Exception;

/**
 * Implementation of the logging system
 * @author mac
 * 
 */
trait Logging {
    
    /**
     * Log a message
     * @param $msg text message or any object
     */
    protected static function _log($msg, $level = HEXAGON_LOG_LEVEL_DEBUG, $strLevel = 'DBG') {
        $config = Context::$appConfig;
        $trace = debug_backtrace(false)[2];
        $filter = LogFilter::getInstance();

        $class = $trace['class'];
        $type = $trace['type'];
        $method = $trace['function'];
        $line = isset($trace['line']) ? $trace['line'] : 0;
        $file = isset($trace['file']) ? basename($trace['file']) : '';
        
        $logs = $filter->getLoggerInfo($class, $method);
        
        foreach ($logs as $log) {
            $logLevel = $log['level'];
            if ($level & $logLevel) {
                LogAppender::getInstance($log['appender'], $log['params'])->append(
                    '[' . $strLevel . '] ' .
                    '[' . $class . $type . $method . '] ' .
                    ($line > 0 ? '[' . $file . ':' . $line .'] ' : '' ) .
                    self::_dumpObj($msg)
                );
            }
        }
    }
    
    /**
     * Log debug level message
     * @param $msg text message or any object
     */
    protected static function _logDebug($msg) {
        self::_log($msg, HEXAGON_LOG_LEVEL_DEBUG, 'DBG');
    }
    
    /**
     * Log info level message
     * @param $msg text message or any object
     */
    protected static function _logInfo($msg) {
        self::_log($msg, HEXAGON_LOG_LEVEL_INFO, 'INF');
    }
    
    /**
     * Log warning level message
     * @param $msg text message or any object
     */
    protected static function _logWarn($msg) {
        self::_log($msg, HEXAGON_LOG_LEVEL_WARN, 'WRN');
    }
    
    /**
     * Log error level message
     * @param $msg text message or any object
     */
    protected static function _logErr($msg) {
        self::_log($msg, HEXAGON_LOG_LEVEL_ERROR, 'ERR');
    }
    
    /**
     * Log fatal level message
     */
    protected static function _logFatal($msg) {
        self::_log($msg, HEXAGON_LOG_LEVEL_FATAL, 'FAT');
    }
    
    /**
     * Log notice level message
     */
    protected static function _logNotice($msg) {
        self::_log($msg, HEXAGON_LOG_LEVEL_NOTICE, 'NOTICE');
    }
    
    /**
     * Log emergency level message
     */
    protected static function _logEmergency($msg) {
        self::_log($msg, HEXAGON_LOG_LEVEL_NOTICE, 'EMERGENCY');
    }
    
    /**
     * Convert any simple object or array to text
     * @param unknown_type $obj
     * @return string
     */
    protected static function _dumpObj($obj) {
        if (is_object($obj) || is_array($obj)) {
            if ($obj instanceof Exception) {
                $text = $obj->getMessage() . PHP_EOL . $obj->getTraceAsString();
            } else {
                $text = print_r($obj, TRUE);
                $text = preg_replace('/\s+/', " ", $text);
            }
            return $text;
        } else {
            return $obj;
        }
    }
}