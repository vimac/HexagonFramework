<?php
namespace Hexagon\controller;

use \Hexagon\system\log\Logging;
use \Hexagon\system\http\HttpRequest;
use \Hexagon\system\http\HttpResponse;
use \Hexagon\system\result\ValueHelper;
use \Hexagon\system\result\ResultHelper;
use \Hexagon\system\exception\ExceptionHandlerSetter;

class Controller{
    use Logging, ValueHelper, ResultHelper, ExceptionHandlerSetter;
    
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
    
    
}