<?php

namespace Hexagon\system\log;

use \ReflectionClass;

class LogAppender{
    
    private static $appenders = null;
    
    /**
     * Factory
     */
    public static function getInstance($appender, $param) {
        $key = $appender . json_encode($param);
        var_dump($key);
        if (!isset(self::$appenders[$key])) {
            $refAppender = new ReflectionClass($appender);
            $appender = $refAppender->newInstanceArgs($param);
            self::$appenders[$key] = $appender;
        }
        return self::$appenders[$key];
    }
}