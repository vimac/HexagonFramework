<?php

namespace Hexagon\system\uri;

use Hexagon\system\log\Logging;
use Hexagon\Context;
use Hexagon\system\http\HttpRequest;
use Hexagon\system\http\HttpResponse;

class Dispatcher {
    use Logging;
    
    public $method = null;
    public $className = null;
    public $classNS = null;
    
    /**
     * @var Dispatcher
     */
    protected static $d = null;
    
    /**
     * @return Dispatcher
     */
    public static function getInstance() {
        if (self::$d == null) {
            self::$d = new self();
        }
        return self::$d;
    }
    
    public function invoke($uri) {
        $config = Context::$appConfig;
        
        $parts = explode('/', $uri);
        if (empty($parts[0])) {
            array_shift($parts);
        }
        $method = array_pop($parts);
        $class = array_pop($parts);
        $className = ucfirst($class) . 'Controller';
        array_unshift($parts, Context::$appNS, 'app', 'controller');
        array_push($parts, $className);
        $classNS = join('\\', $parts);
        
        $this->method = $method;
        $this->className = $className;
        $this->classNS = $classNS;
        
        $request = HttpRequest::getCurrentRequest();
        $refCon = new \ReflectionClass($classNS);
        
        if ($refCon->hasMethod($method)) {
            $refMethod = $refCon->getMethod($method);
            
            if ($refMethod->getModifiers() & \ReflectionMethod::IS_PUBLIC) {
                $refParams = $refMethod->getParameters();
                $params = [];
                foreach ($refParams as $refParam) {
                    $paramName = $refParam->getName();
                    $paramPos = $refParam->getPosition();
                    
                    if (!$refParam->isOptional()) {
                        if (!$request->hasParameter($paramName)) {
                            throw new MissingParameter($paramName, $method, $classNS);
                        }
                        $params[$paramPos] = $request->getParameter($paramName);
                    } else {
                        if ($request->hasParameter($paramName)) {
                            $params[$paramPos] = $request->getParameter($paramName);
                        } else {
                            $params[$paramPos] = $refParam->getDefaultValue();
                        }
                    }
                }
                
                $instance = $refCon->newInstance($request, HttpResponse::getCurrentResponse());
                $ret = $refMethod->invokeArgs($instance, $params);
                if (is_null($ret)) {
                    return NULL;
                } else {
                    return $ret;
                }
            } else {
                throw new MissingMethod($method, $classNS);
            }
        } else {
            throw new MissingMethod($method, $classNS);
        }
    }
    
}

class MissingMethod extends \Exception {
    public function __construct($method, $classNS) {
        parent::__construct('Missing method [' . $classNS . '->' . $method . ']', 404);
    }
}

class MissingParameter extends \Exception {
    public function __construct($name, $method, $classNS) {
        parent::__construct('Missing request argument for parameter [' . $name . '] in controller method [' . $classNS . '->' . $method . ']', 500);
    }
}