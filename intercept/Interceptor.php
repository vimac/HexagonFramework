<?php

namespace Hexagon\intercept;

use Hexagon\system\log\Logging;
use Hexagon\Context;
use Hexagon\system\result\Result;

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
    
    public function initRules() {
        $config = Context::$appConfig;
        if (isset($config->interceptRules['pre'])) {
            $this->preRules = $config->interceptRules['pre'];
        }
        if (isset($config->interceptRules['post'])) {
            $this->postRules = $config->interceptRules['post'];
        }
    }
    
    private function commitRules($type) {
        $uri = Context::$uri;
        $arrayName = $type . 'Rules';
        foreach ($this->$arrayName as $rule) {
            $re = $rule[0];
            $cls = $rule[1];
            if (preg_match($re, $uri) === 1) {
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
                        break;
                    }
                } else {
                    throw new MissingInterceptorRuleMethod($type, $cls);
                }
            }
        }
    }
    
    public function commitPreRules() {
        $this->commitRules('pre');
    }
    
    public function commitPostRules() {
        $this->commitRules('post');
    }
}

class MissingInterceptorRuleMethod extends \Exception {
    public function __construct($method, $classNS) {
        parent::__construct('Missing method [' . $classNS . '->' . $method . ']', 404);
    }
}

class WrongInterceptorRuleReturnType extends \Exception {
    public function __construct($cls) {
        $ref = new \ReflectionClass($cls);
        parent::__construct('Wrong return type for interceptor rule, class name: [' . $ref->getName() . ']', 500);
    }
}

class BreakInterceptor extends \Exception {
}