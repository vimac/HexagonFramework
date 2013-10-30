<?php

namespace Hexagon\system\http;

use Hexagon\Context;
use Hexagon\system\log\Logging;
use Hexagon\system\security\Security;
use Hexagon\system\security\cipher\Cipher;

/**
 * A simple class packing http request
 * @author Mac Chow, vifix.mac@gmail.com
 */
class HttpRequest {
    
    use Logging;
    
    /**
     * @var Cipher;
     */
    private $cipher;
    
    /**
     * @var string
     */
    private $csrfToken;
    
    /**
     * @var string
     */
    private $csrfTokenHash;
    
    /**
     * @var string
     */
    protected $requestMethod;
    
    /**
     * @var string
     */
    protected $userAgent;
    
    /**
     * @var string
     */
    protected $referrer;
    
    /**
     * @var string
     */
    protected $hostName;
    
    /**
     * @var string
     */
    protected $requestURI;
    
    /**
     * @var string
     */
    protected $remoteIP;
    
    /**
     * @var string
     */
    protected $serverIP;
    
    /**
     * @var string
     */
    protected $serverPort;
    
    /**
     * @var array
     */
    protected $cookies;
    
    /**
     * @var string
     */
    protected $queryString;
    
    /**
     * @var string
     */
    protected $parameters = array();
    
    /**
     * @var string
     */
    protected $scriptName;
    
    
    /**
     * @var string
     */
    protected $requestAction;
    
    /**
     * @var string
     */
    protected $pathInfo;
    
    /**
     * @var string
     */
    protected $accept;
    
    /**
     * @var string
     */
    protected $acceptEncoding;
    
    /**
     * @var string
     */
    protected $acceptLanguage;
    
    /**
     * @var JSON | XML | NULL;
     */
    protected $restfulRequest;
    
    /**
     * @var HttpRequest
     */
    protected static $request = NULL;
    
    /**
     * @return HttpRequest
     */
    public static function getCurrentRequest() {
        if (self::$request == null) {
            self::$request = new self();
        }
        return self::$request;
    }

    /**
     * @return HttpRequest
     */
    private function __construct() {
        if (!HEXAGON_CLI_MODE) {
            $this->requestMethod = $_SERVER['REQUEST_METHOD'];
            $this->userAgent = $_SERVER['HTTP_USER_AGENT'];
            $this->requestURI = $_SERVER['REQUEST_URI'];
            $this->hostName = $_SERVER['HTTP_HOST'];
            $this->remoteIP = $_SERVER['REMOTE_ADDR'];
            $this->serverIP = $_SERVER['SERVER_ADDR'];
            $this->serverPort = $_SERVER['SERVER_PORT'];
            $this->queryString = $_SERVER['QUERY_STRING'];
            if (isset($_SERVER['HTTP_ACCEPT'])) {
                $this->accept = $_SERVER['HTTP_ACCEPT'];
            }
        }
        
        if (array_key_exists('HTTP_REFERER',$_SERVER)) {
            $this->referrer = $_SERVER['HTTP_REFERER'];
        }

        $this->cookies = $_COOKIE;
        
        $this->scriptName = $_SERVER['SCRIPT_NAME'];
        
        if (isset($_SERVER['HTTP_ACCEPT_ENCODING'])) {
            $this->acceptEncoding = $_SERVER['HTTP_ACCEPT_ENCODING'];
        } else {
            $this->acceptEncoding = '';
        }
        if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            $this->acceptLanguage = $_SERVER['HTTP_ACCEPT_LANGUAGE'];
        } else {
            $this->acceptLanguage = '';
        }
        
        if (stripos($this->accept, 'json')) {
            $this->restfulRequest = 'JSON';
        } else {
            $this->restfulRequest = 'XML';
        }
        
        $config = Context::$appConfig;
        if ($config->csrfProtection) {
            $this->csrfToken = $config->csrfTokenName;
        }
        $this->parameters = $_REQUEST;
        
        if (!empty($this->csrfToken) && isset($this->parameters[$this->csrfToken])) {
            $this->csrfTokenHash = $this->parameters[$this->csrfToken];
            unset($this->parameters[$this->csrfToken]);
        }
        
        if (array_key_exists('PATH_INFO', $_SERVER)) {
            $this->pathInfo = $_SERVER['PATH_INFO'];
        }
        
        $this->cipher = Security::getCipher();
    }

    /**
     * @return string 
     */
    public function getRequestMethod() {
        return $this->requestMethod;
    }

    /**
     * @return string
     */
    public function getUserAgent() {
        return $this->userAgent;
    }
    
    /**
     * @return string
     */
    public function getReferrer() {
        return $this->referrer;
    }

    /**
     * @return string
     */
    public function getRequestURI() {
        return $this->requestURI;
    }

    /**
     * @return string
     */
    public function getRemoteIP() {
        return $this->remoteIP;
    }
    
    /**
     * @return string
     */
    public function getServerIP() {
        return $this->serverIP;
    }
    
    /**
     * @return string
     */
    public function getServerPort() {
        return $this->serverPort;
    }
    
    /**
     * @return string
     */
    public function getHostName() {
        return $this->hostName;
    }

    /**
     * @return string
     */
    public function getScriptName() {
        return $this->scriptName;
    }
    
    /**
     * @name name Cookie key
     * @return mixed
     */
    public function getCookie($name) {
        $config = Context::$appConfig;
        if (array_key_exists($name, $this->cookies)) {
            if ($config->cookieEncryption) {
                if ($name === $this->csrf) {
                    return $this->cookies[$name];
                } else {
                    return $this->cipher->decrypt($this->cookies[$name]);
                }
            } else {
                return $this->cookies[$name];
            }
        } else {
            return NULL;
        }
    }
    
    /**
     * @return array
     */
    public function getCookies() {
        $config = Context::$appConfig;
        if ($config->cookieEncryption) {
            $cipher = $this->cipher;
            $result = [];
            foreach ($this->cookies as $k => $v) {
                if ($k === $this->csrfToken) {
                    $result[$k] = $v;
                } else {
                    $result[$k] = $cipher->decrypt($v);
                }
            }
            return $result;
        } else {
            return $this->cookies;
        }
    }
    
    /**
     * @return string
     */
    public function getQueryString() {
        return $this->queryString;
    }
    
    /**
     * @name name
     * @return bool
     */
    public function hasParameter($name) {
        return array_key_exists($name, $this->parameters);
    }
    
    /**
     * @name name
     * @return string
     */
    public function getParameter($name) {
        if (array_key_exists($name, $this->parameters)) {
            return $this->parameters[$name];
        }else{
            return null;
        }
    }
    
    /**
     * @return array
     */
    public function getParameters() {
        return $this->parameters;
    }
    
    /**
     * @return string
     */
    public function getPathInfo() {
        return $this->pathInfo;
    }
    
    /**
     * @return string
     */
    public function getRequestAction() {
        return $this->requestAction;
    }
    
    /**
     * @param array $action
     */
    public function setRequestAction($action) {
        $this->requestAction = $action;
    }
    
    public function getAccept() {
        return $this->accept;
    }
    
    public function getAcceptEncoding() {
        return $this->acceptEncoding;
    }
    
    public function getAcceptLanguage() {
        return $this->acceptLanguage;
    }
    
    public function getRestfulRequest() {
        return $this->restfulRequest;
    }
    
    public function getCSRFTokenHash() {
        return $this->csrfTokenHash;
    }
    
    public function removeCSRFTokenHash() {
        unset($this->csrfTokenHash);
    }
}