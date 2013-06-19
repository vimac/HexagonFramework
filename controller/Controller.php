<?php
namespace Hexagon\controller;

use Hexagon\system\log\Logging;
use Hexagon\system\http\HttpRequest;
use Hexagon\system\http\HttpResponse;

class Controller{
    use Logging;
    
    /**
     * @var HttpRequest
     */
    protected $request;
    
    /**
     * @var HttpResponse
     */
    protected $response;
    
    public function __construct(HttpRequest $req, HttpResponse $res) {
        $this->request = $req;
        $this->response = $res;
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