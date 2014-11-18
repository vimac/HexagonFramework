<?php

namespace Hexagon\system\uri;

use Hexagon\Context;
use Hexagon\system\http\HttpRequest;
use Hexagon\system\log\Logging;

class Router {

    use Logging;

    /**
     * An instance of this class
     * @var Router
     */
    private static $r;

    /**
     * Singleton
     * @var Router
     * @return \Hexagon\system\uri\Router
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
            $uri = substr($uri, 0, -$queryStringLen - 1);
        }

        $scriptName = Context::$appEntryName;
        $scriptNameLen = strlen($scriptName);
        if (substr($uri, 1, $scriptNameLen) === $scriptName) {
            $uri = substr($uri, $scriptNameLen + 1);
        }

        return $uri;
    }

    private function parseURI() {
        $config = Context::$appConfig;
        $request = HttpRequest::getCurrentRequest();
        $uri = NULL;

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

        $urlInfo = parse_url($config->appUrl);
        if (isset($urlInfo['path']) && $urlInfo['path'] != '/') {
            $uriPrefix = rtrim($urlInfo['path'], '/');
            $uriPrefixLength = strlen($uriPrefix);
            if (substr($uri, 0, $uriPrefixLength) === $uriPrefix) {
                $uri = substr($uri, $uriPrefixLength);
            }
        }

        return $uri;
    }

    private function checkInvalid($uri) {
        static $reAllowedChars = '/[^a-zA-Z0-9_\/]/';
        $matches = [];
        if (preg_match($reAllowedChars, $uri, $matches) > 0) {
            throw new InvalidURI($uri);
        }
    }

    /**
     * Resolve
     *
     * @param string $uri
     * @throws InvalidURI
     * @return array
     */
    public function resolveURI($uri = NULL) {
        Context::$eventDispatcher->dispatch('HF::onResolveURI');

        $config = Context::$appConfig;

        if (!isset($uri)) {
            $uri = $this->parseURI();
        }

        $this->checkInvalid($uri);

        $uri = preg_replace('/\/+/', '/', $uri);

        if (!$uri || $uri === '/' || $uri === '/' . Context::$appEntryName) {
            $uri = $config->uriDefault;
        }

        $uriParts = explode('/', $uri);
        if (count($uriParts) < 3) {
            $uri = dirname($config->uriDefault) . '/' . array_pop($uriParts);
        }

        if ($uri[0] !== '/') {
            $uri = '/' . $uri;
        }

        if (substr($uri, -1) === '/') {
            $uri = $uri . 'index';
        } else {
            if (!empty($config->uriSuffix)) {
                $suffix = substr($uri, -strlen($config->uriSuffix));
                if ($suffix === $config->uriSuffix) {
                    $uri = substr($uri, 0, -strlen($config->uriSuffix));
                } else {
                    throw new InvalidURI($uri);
                }
            }
        }

        $this->_logDebug('URI: ' . $uri);

        return $uri;
    }
}

class InvalidURI extends \Exception {
    public function __construct($uri) {
        parent::__construct('Invalid URI [' . $uri . ']');
    }
}