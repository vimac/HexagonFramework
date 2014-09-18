<?php

namespace Hexagon\intercept;

use Exception;
use Hexagon\Context;
use Hexagon\system\log\Logging;
use Hexagon\system\result\Result;
use ReflectionClass;

class Interceptor {

    use Logging;

    private $preRules = [];
    private $postRules = [];

    /**
     * @var Interceptor
     */
    protected static $i = null;

    /**
     * @return Interceptor
     */
    public static function getInstance() {
        if (self::$i == null) {
            self::$i = new self();
            self::$i->initRules();
        }
        return self::$i;
    }

    /**
     * Read all interceptors rules from Config
     */
    public function initRules() {
        $config = Context::$appConfig;
        if (isset($config->interceptRules['pre'])) {
            $this->preRules = $config->interceptRules['pre'];
        }
        if (isset($config->interceptRules['post'])) {
            $this->postRules = $config->interceptRules['post'];
        }
    }

    /**
     * Actually execute the rules
     *
     * @param string $type 'pre' or 'post'
     * @return \Hexagon\system\result\Result
     * @throws WrongInterceptorRuleReturnType
     * @throws MissingInterceptorRuleMethod
     */
    private function commitRules($type) {
        $uri = Context::$uri;
        $arrayName = $type . 'Rules';
        foreach ($this->$arrayName as $rule) {
            $re = $rule[0];
            $clses = $rule[1];
            if (preg_match($re, $uri) === 1) {
                if (is_string($clses)) {
                    $clses = [$clses];
                }
                foreach ($clses as $cls) {
                    $reflectionClass = new \ReflectionClass($cls);
                    if ($reflectionClass->hasMethod($type)) {
                        try {
                            $instance = $reflectionClass->newInstance();
                            $method = $reflectionClass->getMethod($type);
                            $result = $method->invoke($instance);
                            if (!is_null($result)) {
                                if ($result instanceof Result) {
                                    return $result;
                                } else {
                                    throw new WrongInterceptorRuleReturnType($cls);
                                }
                            }
                        } catch (BreakInterceptor $e) {
                            break 2;
                        }
                    } else {
                        throw new MissingInterceptorRuleMethod($type, $cls);
                    }
                }
            }
        }
    }

    /**
     * Commit pre rules
     */
    public function commitPreRules() {
        return $this->commitRules('pre');
    }

    /**
     * Commit post rules
     */
    public function commitPostRules() {
        return $this->commitRules('post');
    }
}

class MissingInterceptorRuleMethod extends Exception {
    public function __construct($method, $classNS) {
        parent::__construct('Missing method [' . $classNS . '->' . $method . ']', 404);
    }
}

class WrongInterceptorRuleReturnType extends Exception {
    public function __construct($cls) {
        $ref = new ReflectionClass($cls);
        parent::__construct('Wrong return type for interceptor rule, class name: [' . $ref->getName() . ']', 500);
    }
}

class BreakInterceptor extends Exception {
}