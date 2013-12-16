<?php

namespace Hexagon\model;

use \Hexagon\system\log\Logging;

abstract class RequestModel extends Model {
    const REQUEST_METHOD_HEAD = 0b00001;
    const REQUEST_METHOD_GET = 0b00010;
    const REQUEST_METHOD_POST = 0b00100;
    const REQUEST_METHOD_PUT = 0b01000;
    const REQUEST_METHOD_DELETE = 0b10000;
    
    const REQUEST_METHOD_ALL_TYPE = 0b11111;
    const REQUEST_METHOD_GET_AND_POST = 0b00110;
    
    public $_requestMethod = self::REQUEST_METHOD_GET;
    
    public function checkAllowedMethod() {
        //$method = $_SERVER['REQUEST_METHOD'];
    }
    
    
}