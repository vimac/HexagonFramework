<?php

namespace Hexagon\system\log;

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
    public static function log($msg, $level = HEXAGON_LOG_LEVEL_DEBUG, $strLevel = 'DBG') {
        if (defined('HEXAGON_LOG_LEVEL')) {
            $logLevel = HEXAGON_LOG_LEVEL;
        } else {
            $logLevel = HEXAGON_LOG_LEVEL_ALL;
        }
        
        if ($level & $logLevel) {
            $trace = debug_backtrace(false)[2]; //0,1 for this class self, so use 2
            LogAppender::getInstance()->append(
                '[' . $strLevel . '] ' .
                '[' . $trace['class'] . $trace['type'] . $trace['function'] . '] ' .
                self::dump($msg)
            );
        }
    }
    
    /**
     * Log debug level message
     * @param $msg text message or any object
     */
    public static function logDebug($msg) {
        self::log($msg, HEXAGON_LOG_LEVEL_DEBUG, 'DBG');
    }
    
    /**
     * Log info level message
     * @param $msg text message or any object
     */
    public static function logInfo($msg) {
        self::log($msg, HEXAGON_LOG_LEVEL_INFO, 'INF');
    }
    
    /**
     * Log warning level message
     * @param $msg text message or any object
     */
    public static function logWarn($msg) {
        self::log($msg, HEXAGON_LOG_LEVEL_WARN, 'WRN');
    }
    
    /**
     * Log error level message
     * @param $msg text message or any object
     */
    public static function logErr($msg) {
        self::log($msg, HEXAGON_LOG_LEVEL_ERROR, 'ERR');
    }
    
    /**
     * Log fatal level message
     */
    public static function logFatal($msg) {
        self::log($msg, HEXAGON_LOG_LEVEL_FATAL, 'FAT');
    }
    
    /**
     * Convert any simple object or array to text
     * @param unknown_type $obj
     */
    private static function dump($obj) {
        if (is_object($obj) || is_array($obj)) {
            $text = print_r($obj, true);
            $text = preg_replace('/\s+/', " ", $text);
            return $text;
        } else {
            return $obj;
        }
    }
}