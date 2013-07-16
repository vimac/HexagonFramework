<?php

namespace Hexagon\system\result;

use Hexagon\system\http\HttpRequest;
use Hexagon\system\http\HttpResponse;

class Result {
    
    const TYPE_PAGE = 'PAGE';
    const TYPE_HTML = 'HTML';
    const TYPE_TEXT = 'TEXT';
    
    const TYPE_PNG = 'PNG';
    const TYPE_JPEG = 'JPEG';
    const TYPE_GIF = 'GIF';
    
    const TYPE_JSON = 'JSON';
    const TYPE_XML = 'XML';
    
    const TYPE_USER_DEFINE = 'CUSTOM';
    const TYPE_NONE = 'NONE';
    
    const CONTENT_HTML = 'text/html';
    const CONTENT_TEXT = 'text/plain';
    const CONTENT_PNG = 'image/png';
    const CONTENT_GIF = 'image/gif';
    const CONTENT_JPEG = 'image/jpeg';
    const CONTENT_JSON = 'application/json';
    const CONTENT_XML = 'application/xml';
    const CONTENT_BINARY = 'application/octet-stream';
    
    public $type = NULL;
    public $data = NULL;
    public $meta = NULL;
    public $contentType = NULL;
    
    /**
     * @param int $type Result type, use \Hexagon\system\result\Result\TYPE_* consts value
     * @param mixed $data Result data
     * @param mixed $meta Result meta info, like page template location, or jpeg compression rate, see class doc for detail
     * @param string $contentType Content type, default is <b>text/html</b>
     * @param function $lambda Do some special things by this function, usually use in custom return type
     * @return Result 
     */
    public function __construct($type, $data, $meta, $contentType = self::CONTENT_HTML, $lambda = NULL) {
        $this->type = $type;
        $this->data = $data;
        $this->meta = $meta;
        
        if ($contentType) {
            $this->contentType = $contentType;
        } else {
            $this->contentType = self::CONTENT_BINARY;
        }
        
        if ($lambda) {
            $lambda($self);
        }
    }
    
    /**
     * this result type return a custom type
     * @param $type int Result type, use \Hexagon\system\result\Result\TYPE_* consts value
     * @param $data mixed Result data
     * @param $meta mixed Result meta info, like page template location, or jpeg compression rate, see class doc for detail
     * @param string $contentType Content type, default is <b>application/octet-stream</b>
     * @param $lambda function Do some special things by this function, usually use in custom return type
     * @return Result 
     */
    public static function genCustomResult($data, $meta, $contentType = self::CONTENT_BINARY, $lambda = NULL) {
        return new self(self::TYPE_USER_DEFINE, $data, $meta, $contentType, $lambda);
    }
    
    /**
     * this result type use template engine to build a complete page.
     * @param array $bindArrayData template tags data
     * @param string $screenLocation screen relative path, NULL for auto
     * @param string $layoutLocation template relative path, NULL for auto
     * @param string $contentType content type
     * @param function $lambda Do some special things by this function
     * @return Result
     */
    public static function genPageResult($bindArrayData, $screenLocation = NULL, $layoutLocation = NULL, $contentType = self::CONTENT_HTML, $lambda = NULL) {
        return new self(self::TYPE_PAGE, $bindArrayData, ['screen' => $screenLocation, 'layout' => $layoutLocation], $contentType, $lambda);        
    }
    
    /**
     * this result type return a simple text message to client side
     * @param string $text
     * @param string $contentType
     * @param function $lambda
     * @return Result
     */
    public static function genTextResult($text, $contentType = self::CONTENT_TEXT, $lambda = NULL) {
        return new self(self::TYPE_TEXT, $text, NULL, $contentType, $lambda);
    }
    
    /**
     * this result type return a simple text message to client side
     * @param string $text
     * @param string $contentType
     * @param function $lambda
     * @return Result
     */
    public static function genHTMLResult($html, $contentType = self::CONTENT_HTML, $lambda = NULL) {
        return new self(self::TYPE_HTML, $html, NULL, $contentType, $lambda);
    }
    
    /**
     * this result type return a simple json data to client side
     * @param string $text
     * @param string $contentType
     * @param function $lambda
     * @return Result
     */
    public static function genJSONResult($data, $contentType = self::CONTENT_JSON, $lambda = NULL) {
        return new self(self::TYPE_JSON, $data, NULL, $contentType, $lambda);
    }
    
    /**
     * this result type return a simple xml data to client side
     * @param string $text
     * @param string $contentType
     * @return Result
     */
    public static function genXMLResult($data, $contentType = self::CONTENT_XML, $lambda = NULL) {
        return new self(self::TYPE_XML, $data, NULL, $contentType);
    }
    
    /**
     * this result type return a simple json or xml data to client side by detect client's accept content type
     * @param string $text
     * @param string $contentType
     * @param function $lambda
     * @return Result
     */
    public static function genRESTResult($data, $type = 'AUTO', $lambda = NULL) {
        $type = strtoupper($type);
        if ($type === 'AUTO') {
            $request = HttpRequest::getCurrentRequest();
            $type = $request->getRestfulRequest();
        }
        if ($type === 'XML' || $type === 'JSON') {
            $func = 'gen' . $type . 'Result';
            $contentType = constant('self::CONTENT_' . $type);
            return self::$func($data, $contentType, $lambda);
        } else {
            throw new UnknownResultType();
        }
    }
    
    /**
     * this result type return a png image generated by GD library
     * @param resource $gdRes
     * @param string $contentType
     * @param function $lambda
     * @return Result
     */
    public static function genPNGResult($gdRes, $contentType = self::CONTENT_PNG, $lambda = NULL) {
        return new self(self::TYPE_PNG, $gdRes, NULL, $contentType, $lambda);
    }
    
    /**
     * this result type return a jpeg image generated by GD library
     * @param resource $gdRes
     * @param string $contentType
     * @param number $jpegQuality
     * @param function $lambda
     * @return Result
     */
    public static function genJPEGResult($gdRes, $contentType = self::CONTENT_JPEG, $jpegQuality = 75, $lambda = NULL) {
        return new self(self::TYPE_JPEG, $gdRes, ['quality' => $jpegQuality], $contentType, $lambda);
    }
    
    /**
     * this result type return a gif image generated by GD library
     * @param resource $gdRes
     * @param string $contentType
     * @param function $lambda
     * @return Result
     */
    public static function genGIFResult($gdRes, $contentType = self::CONTENT_GIF, $lambda = NULL) {
        return new self(self::TYPE_GIF, $gdRes, NULL, $contentType, $lambda);
    }
    
    /**
     * this result is nothing, usually use in a cli task
     * @return Result
     */
    public static function genNoneResult() {
        return new self(self::TYPE_NONE, NULL, NULL, NULL, NULL);
    }
    
    /**
     * redirect to another url
     * @param string $url
     * @param number $method number in 3xx: <br />
     *                       301 - Moved Permanently <br />
     *                       302 - Found <br />
     *                       303 - See Other <br />
     *                       307 - Temporary Redirect <br />
     */
    public static function redirect($uri, $code = 302) {
        header('Location: ' . $uri, TRUE, $code);
        return self::genNoneResult();
    }
    
    public static function __callstatic($name, $argv) {
        throw new UnknownResultType();
    }
}

class UnknownResultType extends \Exception {
}