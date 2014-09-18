<?php

namespace Hexagon\intercept;

use Hexagon\system\http\HttpRequest;
use Hexagon\system\http\HttpResponse;
use Hexagon\system\log\Logging;
use Hexagon\system\result\ResultHelper;
use Hexagon\system\result\ValueHelper;

interface IPreRule {
    public function pre();
}

interface IPostRule {
    public function post();
}

class Rule {

    use ValueHelper, ResultHelper;

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

    /**
     * Stop rules commiting
     *
     * @throws BreakInterceptor
     */
    protected function breakInterceptor() {
        throw new BreakInterceptor();
    }

}