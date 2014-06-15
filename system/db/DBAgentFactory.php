<?php

namespace Hexagon\system\db;

use Hexagon\Context;
use Hexagon\system\log\Logging;

class DBAgentFactory {
    use Logging;
    
    public static $agents = [];
    
    /**
     * Get a DBAgent instance
     *
     * @param string $name
     * @return DBAgent
     */
    public static function getDBAgent($name = 'default') {
        $params = Context::$appConfig->getDBConfig($name);
        $keys = array_keys(self::$agents);
        if (!in_array($name, $keys)) {
            self::$agents[$name] = new DBAgent($params);
        }
        return self::$agents[$name];
    }
}