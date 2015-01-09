<?php
namespace Hexagon\controller;

use Hexagon\system\exception\ExceptionHandlerSetter;
use Hexagon\system\http\HttpRequest;
use Hexagon\system\http\HttpResponse;
use Hexagon\system\log\Logging;
use Hexagon\system\result\ResultHelper;
use Hexagon\system\result\ValueHelper;

abstract class Controller {
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

    /**
     * should never add a method here with public access
     * because the public method here will be accessed by web request
     * use protected with "_" prefix instead, for example:
     *
     * public function _doSomething(){}
     */

}