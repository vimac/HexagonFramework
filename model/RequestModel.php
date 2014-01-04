<?php

namespace Hexagon\model;

abstract class RequestModel extends Model {
    const REQUEST_METHOD_HEAD = 0b00001;
    const REQUEST_METHOD_GET = 0b00010;
    const REQUEST_METHOD_POST = 0b00100;
    const REQUEST_METHOD_PUT = 0b01000;
    const REQUEST_METHOD_DELETE = 0b10000;
    
    const REQUEST_METHOD_ALL_TYPE = 0b11111;
    const REQUEST_METHOD_GET_AND_POST = 0b00110;
    const REQUEST_METHOD_NONE = 0b00000;
    
    protected static $_requestMethod = self::REQUEST_METHOD_ALL_TYPE;
    
    private $classNS;
    private $method;
    
    public function __construct($classNS, $method) {
        $this->classNS = $classNS;
        $this->method = $method;
    }
    
    public function getRequestClassNamespace() {
        return $this->classNS;
    }
    
    public function getRequestMethod() {
        return $this->method;
    }
    
    public static final function _checkAllowedMethod() {
        $method = $_SERVER['REQUEST_METHOD'];
        $methodVal = constant('self::REQUEST_METHOD_' . $method);
        return $methodVal & static::$_requestMethod;
    }
    
    /**
     * return anything other than TRUE means not passed this check
     */
    public function _checkParameters() {
        return TRUE;
    }
    
}