<?php

namespace Hexagon\system\result;

use Hexagon\Framework;
use Hexagon\system\http\HttpRequest;
use Hexagon\system\http\HttpResponse;

trait ResultHelper {

    /**
     * this result type return a custom type
     *
     * @param mixed $data
     *            Result data
     * @param mixed $meta
     *            Result meta info, like page template location, or jpeg compression rate, see class doc for detail
     * @param string $contentType
     *            Content type, default is <b>application/octet-stream</b>
     * @param callable $callback
     *            Do some special things by this function, usually use in custom return type
     * @return Result
     */
    protected static function _genCustomResult($data, $meta, $contentType = Result::CONTENT_BINARY, callable $callback = NULL) {
        return new Result(Result::TYPE_USER_DEFINE, $data, $meta, $contentType, $callback);
    }

    /**
     * this result type use template engine to build a complete page.
     *
     * @param array $bindArrayData
     *            template tags data
     * @param string $screenLocation
     *            screen relative path, NULL for auto
     * @param string $layoutLocation
     *            template relative path, NULL for auto
     * @param string $contentType
     *            content type
     * @param callable $callback callback function
     *            Do some special things by this function
     * @return Result
     */
    protected static function _genPageResult($bindArrayData = [], $screenLocation = NULL, $layoutLocation = NULL, $contentType = Result::CONTENT_HTML, callable $callback = NULL) {
        return new Result(Result::TYPE_PAGE, $bindArrayData, [
            'screen' => $screenLocation,
            'layout' => $layoutLocation
        ], $contentType, $callback);
    }

    /**
     * this result type return a simple text message to client side
     *
     * @param string $text
     * @param string $contentType
     * @param callable $callback callback function
     * @return Result
     */
    protected static function _genTextResult($text = '', $contentType = Result::CONTENT_TEXT, callable $callback = NULL) {
        return new Result(Result::TYPE_TEXT, $text, NULL, $contentType, $callback);
    }

    /**
     * this result type return a simple json data to client side
     *
     * @param mixed $data array or object
     * @param string $contentType
     * @param callable $callback callback function
     * @return Result
     */
    protected static function _genJSONResult($data = [], $contentType = Result::CONTENT_JSON, callable $callback = NULL) {
        return new Result(Result::TYPE_JSON, $data, NULL, $contentType, $callback);
    }

    /**
     * this result type return a simple xml data to client side
     *
     * @param mixed $data array or object
     * @param string $contentType
     * @param callable $callback callback function
     * @return Result
     */
    protected static function _genXMLResult($data = [], $contentType = Result::CONTENT_XML, callable $callback = NULL) {
        return new Result(Result::TYPE_XML, $data, NULL, $contentType);
    }

    /**
     * this result type return a simple json or xml data to client side by detect client's accept content type
     *
     * @deprecated see _genSmartResult
     * @param mixed $data array or object
     * @param string $type
     * @param callable $callback callback function
     * @return Result
     * @throws UnknownResultType
     */
    protected static function _genRESTResult($data = [], $type = 'AUTO', callable $callback = NULL) {
        $type = strtoupper($type);
        if ($type === 'AUTO') {
            $request = HttpRequest::getCurrentRequest();
            $type = $request->getRestfulRequest();
        }
        if ($type === 'XML' || $type === 'JSON') {
            $func = '_gen' . $type . 'Result';
            $contentType = constant('Hexagon\system\result\Result::CONTENT_' . $type);
            return self::$func($data, $contentType, $callback);
        } else {
            throw new UnknownResultType();
        }
    }

    /**
     * Generate a smart result, which relay the request content type
     * (support PageResult, JSONResult, not support XML yet)
     *
     * @param mixed $data array or object
     * @param string $screenLocation screen location (only available in PageResult)
     * @param string $layoutLocation layout location (only available in PageResult)
     * @param string $contentType content type
     * @param callable $callback call back
     * @return Result
     */
    protected static function _genSmartResult($data = [], $screenLocation = NULL, $layoutLocation = NULL, $contentType = NULL, Callable $callback = NULL) {
        if (stripos(HttpRequest::getCurrentRequest()->getAccept(), 'json') > -1) {
            $contentType = empty($contentType) ? Result::CONTENT_JSON : $contentType;
            return self::_genJSONResult($data, $contentType, $callback);
        } else {
            $contentType = empty($contentType) ? Result::CONTENT_HTML : $contentType;
            return self::_genPageResult($data, $screenLocation, $layoutLocation, $contentType, $callback);
        }
    }

    /**
     * this result type return a png image generated by GD library
     *
     * @param resource $gdRes
     * @param string $contentType
     * @param callable $callback callback function
     * @return Result
     */
    protected static function _genPNGResult($gdRes, $contentType = Result::CONTENT_PNG, callable $callback = NULL) {
        return new Result(Result::TYPE_PNG, $gdRes, NULL, $contentType, $callback);
    }

    /**
     * this result type return a jpeg image generated by GD library
     *
     * @param resource $gdRes
     * @param string $contentType
     * @param int $jpegQuality
     * @param callable $callback callback function
     * @return Result
     */
    protected static function _genJPEGResult($gdRes, $contentType = Result::CONTENT_JPEG, $jpegQuality = 75, callable $callback = NULL) {
        return new Result(Result::TYPE_JPEG, $gdRes, [
            'quality' => $jpegQuality
        ], $contentType, $callback);
    }

    /**
     * this result type return a gif image generated by GD library
     *
     * @param resource $gdRes
     * @param string $contentType
     * @param callable $callback callback function
     * @return Result
     */
    protected static function _genGIFResult($gdRes, $contentType = Result::CONTENT_GIF, callable $callback = NULL) {
        return new Result(Result::TYPE_GIF, $gdRes, NULL, $contentType, $callback);
    }

    /**
     * redirect to another url
     *
     * @param string $uri
     * @param int $code
     *            number in 3xx: <br />
     *            301 - Moved Permanently <br />
     *            302 - Found <br />
     *            303 - See Other <br />
     *            307 - Temporary Redirect <br />
     * @return Result
     */
    protected static function _redirect($uri, $code = 302) {
        HttpResponse::getCurrentResponse()->setResponseCode($code);
        header('Location: ' . $uri, TRUE, $code);
        
        return self::_genNoneResult();
    }

    /**
     * this result is nothing, usually use in a cli task
     *
     * @param string @contentType
     * @return Result
     */
    protected static function _genNoneResult($contentType = Result::TYPE_HTML) {
        return new Result(Result::TYPE_NONE, NULL, NULL, $contentType, NULL);
    }

    /**
     * do nothing and stop framework running
     *
     * @return void
     */
    protected static function _ignoreFrameworkResult() {
        Framework::getInstance()->stop(0);
    }

    /**
     * alert and redirect
     *
     * @param string $msg
     * @param string $url
     * @param string $encoding
     * @param string $contentType
     * @return Result
     */
    protected static function _alertRedirect($msg, $url = '', $encoding = 'utf-8', $contentType = Result::CONTENT_HTML) {
        $str = '<!DOCTYPE html><html><head><meta charset="' . $encoding . '"></head><body>';
        $str .= '<script type="text/javascript">';
        $str .= "alert('" . $msg . "');";

        if (!empty($url)) {
            $str .= "window.location.href='{$url}';";
        } else {
            $str .= "window.history.back();";
        }
        $str .= '</script></body></html>';

        return self::_genHTMLResult($str, $contentType);
    }

    /**
     * this result type return a simple text message to client side
     *
     * @param string $html
     * @param string $contentType
     * @param callable $callback callback function
     * @return Result
     */
    protected static function _genHTMLResult($html, $contentType = Result::CONTENT_HTML, callable $callback = NULL) {
        return new Result(Result::TYPE_HTML, $html, NULL, $contentType, $callback);
    }

}