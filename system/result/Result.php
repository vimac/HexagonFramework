<?php

namespace Hexagon\system\result;

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
    public function __construct($type = NULL, $data = NULL, $meta = NULL, $contentType = self::CONTENT_HTML, $lambda = NULL) {
        $this->type = isset($type) ? $type : 'PAGE';
        $this->data = isset($data) ? $data : [];
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
}

class UnknownResultType extends \Exception {
}