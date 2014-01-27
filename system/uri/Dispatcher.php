<?php

namespace Hexagon\system\uri;

use \ReflectionClass;
use \ReflectionMethod;
use \ReflectionProperty;
use \Exception;
use \Hexagon\system\log\Logging;
use \Hexagon\Context;
use \Hexagon\system\http\HttpRequest;
use \Hexagon\system\http\HttpResponse;
use \Hexagon\controller\Controller;

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
    
    private function buildObject($classNS, $method) {
        
        if (substr($method, 0, 1) === '_') {
            throw new MethodNameNotAllowed($method, $classNS);
        }
        
        $request = HttpRequest::getCurrentRequest();
        $refCon = new ReflectionClass($classNS);
        
        if ($refCon->hasMethod($method)) {
            $refMethod = $refCon->getMethod($method);
        
            if ($refMethod->getModifiers() & ReflectionMethod::IS_PUBLIC) {
                $refParams = $refMethod->getParameters();

                $params = [];
                $firstParam = current($refParams);
                if (count($refParams) == 1 && $firstParam->getClass() !== NULL && $firstParam->getClass()->isSubclassOf('\Hexagon\model\RequestModel')) {
                    $refClass = $firstParam->getClass();
                    $requestModel = $refClass->newInstance($classNS, $method);
                    
                    $refCheckAllowedMethod = $refClass->getMethod('_checkAllowedMethod');
                    if (!$refCheckAllowedMethod->invoke(NULL)) {
                        throw new MethodNotAllowd($method, $classNS);
                    }
                    
                    $vars = $refClass->getProperties(ReflectionProperty::IS_PUBLIC);
                    foreach ($vars as $var) {
                        $varName = $var->getName();
                        
                        if ($request->hasParameter($varName)) {
                            $requestModel->$varName = $request->getParameter($varName);
                        }
                    }

                    $refCheckParameters = $refClass->getMethod('_checkParameters');
                    $checkResult = $refCheckParameters->invoke($requestModel);
                    if ($checkResult !== TRUE) {
                        throw new MissingParameter($checkResult, $method, $classNS);
                    }
                    
                    $params[] = $requestModel;
                } else {
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
                }
        
                $instance = $refCon->newInstance($request, HttpResponse::getCurrentResponse());
                $this->invokeMagicMethods('pre', $refCon, $instance, $params);
                $ret = $refMethod->invokeArgs($instance, $params);
                $this->invokeMagicMethods('post', $refCon, $instance, $params);
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
    
    private function invokeMagicMethods($name, ReflectionClass $reflect, Controller $instance, $params) {
        $name = '_' . $name;
        if ($reflect->hasMethod($name)) {
            $reflect->getMethod($name)->invokeArgs($instance, $params);
        }
    }
    
    public function invokeTask($uri) {
        $config = Context::$appConfig;
        
        $parts = explode('/', $uri);
        if (empty($parts[0])) {
            array_shift($parts);
        }
        $method = array_pop($parts);
        $class = array_pop($parts);
        $className = ucfirst($class) . 'Task';
        array_unshift($parts, Context::$appNS, 'app', 'task');
        array_push($parts, $className);
        $classNS = join('\\', $parts);
        
        $this->method = $method;
        $this->className = $className;
        $this->classNS = $classNS;
        
        return $this->buildObject($classNS, $method);
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
        
        return $this->buildObject($classNS, $method);
    }
    
}

class MethodNameNotAllowed extends Exception {
    public function __construct($method, $classNS) {
        parent::__construct('You are trying to call "' . $_SERVER['REQUEST_METHOD'] . '" to [' . $classNS . '->' . $method . '] , but the method name [' . $method . '] is not allowed in Hexagon Framework.', 400);
    }
}

class MethodNotAllowd extends Exception {
    public function __construct($method, $classNS) {
        parent::__construct('Current HTTP request method "' . $_SERVER['REQUEST_METHOD'] . '" to [' . $classNS . '->' . $method . '] not allowed.', 400);
    }
}

class MissingMethod extends Exception {
    public function __construct($method, $classNS) {
        parent::__construct('Missing method [' . $classNS . '->' . $method . ']', 404);
    }
}

class MissingParameter extends Exception {
    public function __construct($name, $method, $classNS) {
        parent::__construct('Missing request argument for parameter [' . $name . '] in controller method [' . $classNS . '->' . $method . ']', 404);
    }
}