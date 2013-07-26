<?php

namespace Hexagon\system\uri;

use Hexagon\system\log\Logging;
use Hexagon\Context;
use Hexagon\system\http\HttpRequest;
use Hexagon\system\http\HttpResponse;

class Router {
    
    use Logging;
    
    /**
     * @var \Hexagon\config\BaseConfig
     */
    private $config;
    
    /**
     * @var HttpRequest
     */
    private $request;
    
    /**
     * An instance of this class
     * @var Router
     */
    private static $r;
    
    /**
     * Singleton
     * @var Router
     */
    public static function getInstance() {
        if (self::$r == null) {
            self::$r = new self();
        }
        return self::$r;
    }
    
    private function cleanURI($uri) {
        $queryString = $_SERVER['QUERY_STRING'];
        $queryStringLen = strlen($queryString);
        if ($queryStringLen > 0) {
            $uri = substr($uri, 0, - $queryStringLen - 1);
        }
        
        $scriptName = Context::$appEntryName;
        $scriptNameLen = strlen($scriptName);
        if (substr($uri, 1, $scriptNameLen) === $scriptName) {
            $uri = substr($uri, $scriptNameLen + 1);
        }
        
        return $uri;
    }
    
    /**
     * Resolve
     * 
     * @return array
     */
    public function resolveURI() {
        $config = Context::$appConfig;
        $request = HttpRequest::getCurrentRequest();
        
        $this->config = $config;
        $this->request = $request;
        
        $uri = null;
        
        if ($config->uriProtocol === HEXAGON_URI_PROTOCOL_AUTO) {
            $uri = $request->getPathInfo();
            if (empty($uri)) {
                array_key_exists('uri', $_GET) && $uri = $_GET['uri'];
                if (empty($uri)) {
                    $uri = $this->cleanURI($request->getRequestURI());
                }
            }
        } else {
            switch ($config->uriProtocol) {
                case HEXAGON_URI_PROTOCOL_PATH_INFO:
                    $uri = $request->getPathInfo();
                    break;
                case HEXAGON_URI_PROTOCOL_QUERY_STRING:
                    array_key_exists('uri', $_GET) && $uri = $_GET['uri'];
                    break;
                case HEXAGON_URI_PROTOCOL_REQUEST_URI:
                    $uri = $this->cleanURI($request->getRequestURI());
                    break;
            }
        }

        $uri = preg_replace('/\/+/', '/', $uri);
        
        if (!$uri || $uri === '/' || $uri === '/'. Context::$appEntryName) {
            $uri = $config->uriDefault;
        }
        
        $uriParts = explode('/', $uri);
        if (count($uriParts) < 3) {
            $uri = dirname($config->uriDefault) . '/' . array_pop($uriParts);
        }
        
        if (!empty($config->uriSuffix)) {
            $suffix = substr($uri, -strlen($config->uriSuffix));
            if ($suffix === $config->uriSuffix) {
                $uri = substr($uri, 0, -strlen($config->uriSuffix));
            } else {
                throw new InvalidURI($uri);
            }
        }
        
        $this->_logDebug('URI: ' . $uri);
        
        return $uri;
    }
}

class InvalidURI extends \Exception{
    public function __construct($uri) {
        parent::__construct('Invalid URI [' . $uri  . ']');
    }
}