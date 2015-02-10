<?php

namespace Hexagon\system\log;

use Exception;
use ReflectionClass;

class LogAppender {

    /**
     * @var ILogAppender[]
     */
    private static $appenders = null;

    /**
     * Factory method
     *
     * @param string $appender Appender name
     * @param array $param Appender construct parameters
     * @return ILogAppender
     */
    public static function getInstance($appender, $param) {
        $key = $appender . json_encode($param);
        if (!isset(self::$appenders[$key])) {
            $refAppender = new ReflectionClass($appender);
            $appender = $refAppender->newInstanceArgs($param);
            self::$appenders[$key] = $appender;
        }
        return self::$appenders[$key];
    }

}

interface ILogAppender {

    public function append($level, $msg, Exception $ex = NULL);

}