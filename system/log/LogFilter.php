<?php

namespace Hexagon\system\log;

use Hexagon\Context;

class LogFilter {

    /**
     * @var LogFilter
     */
    private static $filter;

    private $rule = [];

    private $reRule = [];
    private $reCache = [];

    private $wdRule = [];
    private $wdCache = [];

    private $allCache = [];

    private function __construct() {
        $config = Context::$appConfig;
        foreach ($config->logs as $idx => $log) {
            switch ($log['match']) {
                case HEXAGON_LOG_MATCH_MODE_RE:
                    $this->reRule[] = [$log['class'], $log['method'], $idx];
                    break;
                case HEXAGON_LOG_MATCH_MODE_WILDCARD;
                    $this->wdRule[] = [$log['class'], $log['method'], $idx];
                    break;
                case HEXAGON_LOG_MATCH_ALL:
                    $this->allCache[] = $idx;
            }
        }
    }

    /**
     * @return \Hexagon\system\log\LogFilter
     */
    public static function getInstance() {
        if (!self::$filter) {
            self::$filter = new self();
        }
        return self::$filter;
    }

    public function getLoggerInfo($class, $method) {
        $logIds = array_merge($this->allCache, $this->matchByWD($class, $method), $this->matchByRE($class, $method));
        $config = Context::$appConfig;
        $logs = [];
        foreach ($logIds as $id) {
            $logs[] = $config->logs[$id];
        }
        return $logs;
    }

    private function matchByRE($class, $method) {
        $key = $class . '.' . $method;
        if (!isset($this->reCache[$key])) {
            $result = [];
            foreach ($this->reRule as $rule) {
                if (preg_match($rule[0], $class) === 1 && preg_match($rule[1], $method) === 1) {
                    $result[] = $rule[2];
                }
            }
            $this->reCache[$key] = $result;
        }

        return $this->reCache[$key];
    }

    private function matchByWD($class, $method) {
        $key = $class . '.' . $method;
        if (!isset($this->wdCache[$key])) {
            $result = [];
            foreach ($this->wdRule as $rule) {
                if (fnmatch($rule[0], $class, FNM_NOESCAPE | FNM_PATHNAME) && fnmatch($rule[1], $method, FNM_NOESCAPE | FNM_PATHNAME)) {
                    $result[] = $rule[2];
                }
            }
            $this->wdCache[$key] = $result;
        }
        return $this->wdCache[$key];
    }

}