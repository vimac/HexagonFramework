<?php

namespace Hexagon\system\log;

use Hexagon\Context;

class LogAppender{
    
    private $appender = null;
    
    /**
     * An instance of this class
     * @var LogAppender
     */
    private static $w;
    
    /**
     * Singleton
     * @var LogAppender
     */
    public static function getInstance() {
        if (self::$w == null) {
            self::$w = new self();
        }
        return self::$w;
    }
    
    public function append($msg) {
        $this->appender->append($msg);
    }
    
    private function __construct() {
        $config = Context::$appConfig;
        if (!empty($config->logAppender)) {
            $this->appender = new $config->logAppender();
        } else {
            $this->appender = new NullAppender();
        }
    }
}

/**
 * empty
 */
class NullAppender{
    public function append() {
        
    }
}