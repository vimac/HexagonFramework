<?php

namespace Hexagon\intercept;

use Hexagon\system\log\Logging;
use Hexagon\system\http\HttpRequest;
use Hexagon\system\http\HttpResponse;

interface IPreRule {
    public function pre();
}

interface IPostRule {
    public function post();
}

class Rule {
    
    /**
     * @var HttpRequest
     */
    protected $request;
    
    /**
     * @var HttpResponse
     */
    protected $response;
    
    public function __construct() {
        $this->request = HttpRequest::getCurrentRequest();
        $this->response = HttpResponse::getCurrentResponse();
    }
    
    protected function breakInterceptor() {
        throw new BreakInterceptor();
    }
    
    protected function _bindValue($key, $val) {
        $this->response->bindValue($key, $val);
    }
    
    protected function _getValue($key) {
        return $this->response->getValue($key);
    }
    
    protected function _getValues() {
        return $this->response->getValues();
    }

}