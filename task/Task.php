<?php
namespace Hexagon\task;

use Hexagon\system\http\HttpRequest;
use Hexagon\system\http\HttpResponse;
use Hexagon\system\log\Logging;
use Hexagon\system\result\ResultHelper;
use Hexagon\system\result\ValueHelper;

class Task {
    use Logging, ValueHelper, ResultHelper;

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