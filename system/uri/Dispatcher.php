<?php

namespace Hexagon\system\uri;

use Exception;
use Hexagon\Context;
use Hexagon\system\http\HttpRequest;
use Hexagon\system\http\HttpResponse;
use Hexagon\system\log\Logging;
use ReflectionClass;
use ReflectionMethod;
use ReflectionParameter;
use ReflectionProperty;


/**
 * Class Dispatcher
 *
 * @package Hexagon\system\uri
 * @desc This class read URI analysed from Router and call instance of Controller or Task
 */
class Dispatcher {
    use Logging;

    public $method = null;

    public $className = null;

    public $classNS = null;

    const TYPE_WEB_CONTROLLER = 0;

    const TYPE_CLI_TASK = 1;

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

    /**
     * Execute the default action
     *
     * @param string $classNS target class namespace
     * @param string $method method name
     * @return mixed
     * @throws MethodNameNotAllowed
     * @throws MissingMethod
     */
    private function doAction($classNS, $method) {
        if ($method[0] === '_') {
            throw new MethodNameNotAllowed($method, $classNS);
        }
        $refCon = new ReflectionClass($classNS);

        if ($refCon->hasMethod($method)) {
            return $this->invokeMethod($method, $classNS, $refCon);
        } else {
            if ($refCon->implementsInterface('Hexagon\controller\ICustomAction')) {
                return $this->invokeMethod('_doAction', $classNS, $refCon);
            } else {
                throw new MissingMethod($method, $classNS);
            }
        }
    }


    /**
     * Execute the controller method
     *
     * @param string $method target method name
     * @param string $classNS target class namespace
     * @param ReflectionClass $refCon the reflection
     * @return mixed
     * @throws MissingMethod
     */
    private function invokeMethod($method, $classNS, ReflectionClass $refCon) {
        $refMethod = $refCon->getMethod($method);

        $request = HttpRequest::getCurrentRequest();

        if ($refMethod->getModifiers() & ReflectionMethod::IS_PUBLIC) {
            $refParams = $refMethod->getParameters();
            $firstParam = current($refParams);

            if (count($refParams) === 1 && $firstParam->getClass() !== NULL && $firstParam->getClass()->isSubclassOf('\Hexagon\model\RequestModel')) {
                $params = $this->buildMethodRequestModelParameters($firstParam, $method, $classNS, $request);
            } else {
                $params = $this->buildMethodArrayParameters($refParams, $method, $classNS, $request);
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
    }

    /**
     * Build the method normal parameters
     *
     * @param ReflectionParameter[] $refParams ReflectionParameter[]
     * @param string $method Method name
     * @param string $classNS Class namespace
     * @param HttpRequest $request Http request instance
     * @return array
     * @throws MissingParameter
     */
    private function buildMethodArrayParameters(array $refParams, $method, $classNS, HttpRequest $request) {
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
        return $params;
    }

    /**
     * Build the method RequestModel parameter
     *
     * @param ReflectionParameter $firstParam The reflection object
     * @param string $method target method
     * @param string $classNS target namespace
     * @param HttpRequest $request the request instance
     * @return array
     * @throws CheckNotPassed
     * @throws MethodNotAllowed
     */
    private function buildMethodRequestModelParameters(ReflectionParameter $firstParam, $method, $classNS, HttpRequest $request) {
        $refClass = $firstParam->getClass();
        $requestModel = $refClass->newInstance($classNS, $method);

        $refCheckAllowedMethod = $refClass->getMethod('_checkAllowedMethod');
        if (!$refCheckAllowedMethod->invoke(NULL)) {
            throw new MethodNotAllowed($method, $classNS);
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
            throw new CheckNotPassed($method, $classNS);
        }

        return [$requestModel];
    }

    /**
     * Execute the magic methods
     *
     * @param string $name name
     * @param ReflectionClass $reflect ReflectionClass object
     * @param object $instance Controller or Task instance
     * @param array $params Parameters
     */
    private function invokeMagicMethods($name, ReflectionClass $reflect, $instance, $params) {
        $name = '_' . $name;
        if ($reflect->hasMethod($name)) {
            $reflect->getMethod($name)->invokeArgs($instance, $params);
        }
    }

    /**
     * Invoke
     *
     * @param string $uri target uti
     * @param int $type type
     * @return mixed
     * @throws Exception
     */
    public function invoke($uri, $type = Self::TYPE_WEB_CONTROLLER) {
        $parts = explode('/', $uri);
        if (empty($parts[0])) {
            array_shift($parts);
        }
        $method = array_pop($parts);
        $class = array_pop($parts);
        $className = '';
        if ($type === self::TYPE_WEB_CONTROLLER) {
            $className = ucfirst($class) . 'Controller';
            array_unshift($parts, Context::$appNS, 'app', 'controller');
        } else if ($type === self::TYPE_CLI_TASK) {
            $className = ucfirst($class) . 'Task';
            array_unshift($parts, Context::$appNS, 'app', 'task');
        }
        if (empty($className)) {
            throw new Exception('Not available type');
        }
        array_push($parts, $className);
        $classNS = join('\\', $parts);

        $this->method = $method;
        $this->className = $className;
        $this->classNS = $classNS;

        return $this->doAction($classNS, $method);
    }

}

class MethodNameNotAllowed extends Exception {

    public function __construct($method, $classNS) {
        parent::__construct('You are trying to call "' . $_SERVER['REQUEST_METHOD'] . '" to [' . $classNS . '->' . $method . '] , but the method name [' . $method . '] is not allowed in Hexagon Framework.', 400);
    }

}

class MethodNotAllowed extends Exception {

    public function __construct($method, $classNS) {
        parent::__construct('Current HTTP request method "' . $_SERVER['REQUEST_METHOD'] . '" to [' . $classNS . '->' . $method . '] not allowed.', 405);
        $response = HttpResponse::getCurrentResponse();
        $response->setResponseCode(405);
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

class CheckNotPassed extends Exception {

    public function __construct($method, $classNS) {
        parent::__construct('Parameter check not passed in controller method [' . $classNS . '->' . $method . ']', 404);
    }

}
