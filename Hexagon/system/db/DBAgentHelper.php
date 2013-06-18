<?php

namespace Hexagon\system\db;

trait DBAgentHelper {
    
    /**
     * Get a DBAgent instance
     * @param string $name
     * @return DBAgent
     */
    public static function getDBAgent($name = 'default') {
        return DBAgentFactory::getDBAgent($name);
    }
    
}