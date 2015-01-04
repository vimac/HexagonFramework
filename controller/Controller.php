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

    public function isXhr() {
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            return TRUE;
        } else {
            return FALSE;
        }
    }

}