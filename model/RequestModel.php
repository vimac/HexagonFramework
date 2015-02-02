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

    private $_classNS;
    private $_method;

    public function __construct($classNS = NULL, $method = NULL) {
        $this->_classNS = $classNS;
        $this->_method = $method;
    }

    /**
     * Get requested class full name
     * @return string
     */
    public function getClassNamespace() {
        return $this->_classNS;
    }

    /**
     * Get requested method name
     * @return string
     */
    public function getMethod() {
        return $this->_method;
    }

    /**
     * Determine whether the request method allowed
     * @return bool
     */
    public static final function _checkAllowedMethod() {
        $method = $_SERVER['REQUEST_METHOD'];
        $methodVal = constant('self::REQUEST_METHOD_' . $method);
        return $methodVal & static::$_requestMethod;
    }

    /**
     * Return anything other than TRUE means not passed this check
     *
     * @return bool
     */
    public function _checkParameters() {
        return TRUE;
    }

}