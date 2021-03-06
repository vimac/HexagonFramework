<?php

namespace Hexagon\system\log;

use Exception;

/**
 * Implementation of the logging system
 * @author mac
 *
 */
trait Logging {

    /**
     * Log a message
     *
     * @param mixed $msg text message or any object
     * @param int $level log level
     * @param string $strLevel log level name,
     * @param Exception $ex exception obj
     */
    protected static function _log($msg, $level = HEXAGON_LOG_LEVEL_DEBUG, $strLevel = 'DBG', Exception $ex = NULL) {
        $trace = debug_backtrace(FALSE)[2];
        $filter = LogFilter::getInstance();

        $class = $trace['class'];
        $type = $trace['type'];
        $method = $trace['function'];
        $line = isset($trace['line']) ? $trace['line'] : 0;
        $file = isset($trace['file']) ? basename($trace['file']) : '';

        foreach ($filter->getLoggerInfo($class, $method) as $log) {
            $logLevel = $log['level'];
            if ($level & $logLevel) {
                $params = isset($log['params']) ? $log['params'] : [];
                LogAppender::getInstance($log['appender'], $params)->append(
                    $level,
                    '[' . $strLevel . '] ' .
                    '[' . $class . $type . $method . '] ' .
                    ($line > 0 ? '[' . $file . ':' . $line . '] ' : '') .
                    self::_dumpObj($msg),
                    $ex
                );
            }
        }
    }

    /**
     * Log debug level message
     *
     * @param mixed $msg text message or any object
     * @param Exception $ex exception object
     */
    protected static function _logDebug($msg, Exception $ex = NULL) {
        self::_log($msg, HEXAGON_LOG_LEVEL_DEBUG, 'DBG', $ex);
    }

    /**
     * Log info level message
     *
     * @param mixed $msg text message or any object
     * @param Exception $ex exception object
     */
    protected static function _logInfo($msg, Exception $ex = NULL) {
        self::_log($msg, HEXAGON_LOG_LEVEL_INFO, 'INF', $ex);
    }

    /**
     * Log warning level message
     *
     * @param mixed $msg text message or any object
     * @param Exception $ex exception object
     */
    protected static function _logWarn($msg, Exception $ex = NULL) {
        self::_log($msg, HEXAGON_LOG_LEVEL_WARN, 'WRN', $ex);
    }

    /**
     * Log error level message
     *
     * @param mixed $msg text message or any object
     * @param Exception $ex exception object
     */
    protected static function _logErr($msg, Exception $ex = NULL) {
        self::_log($msg, HEXAGON_LOG_LEVEL_ERROR, 'ERR', $ex);
    }

    /**
     * Log fatal level message
     *
     * @param mixed $msg text message or any object
     * @param Exception $ex exception object
     */
    protected static function _logFatal($msg, Exception $ex = NULL) {
        self::_log($msg, HEXAGON_LOG_LEVEL_FATAL, 'FAT', $ex);
    }

    /**
     * Log notice level message
     *
     * @param mixed $msg text message or any object
     * @param Exception $ex exception object
     */
    protected static function _logNotice($msg, Exception $ex = NULL) {
        self::_log($msg, HEXAGON_LOG_LEVEL_NOTICE, 'NTC', $ex);
    }

    /**
     * Log emergency level message
     *
     * @param mixed $msg text message or any object
     * @param Exception $ex exception object
     */
    protected static function _logEmergency($msg, Exception $ex = NULL) {
        self::_log($msg, HEXAGON_LOG_LEVEL_EMERGENCY, 'EMC', $ex);
    }

    /**
     * Convert any simple object or array to text
     * @param mixed $obj
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