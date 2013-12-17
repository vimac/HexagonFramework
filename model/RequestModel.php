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
    
    public static final function _checkAllowedMethod() {
        $class = get_called_class();
        $method = $_SERVER['REQUEST_METHOD'];
        $methodVal = constant('self::REQUEST_METHOD_' . $method);
        return $methodVal & $class::$_requestMethod;
    }
    
    
}