<?php

namespace Hexagon\system\http;

use Hexagon\Context;
use Hexagon\system\log\Logging;
use Hexagon\system\security\cipher\Cipher;
use Hexagon\system\security\Security;

/**
 * A class packing http response
 * @author Mac Chow, vifix.mac@gmail.com
 */
class HttpResponse {

    use Logging;

    /**
     * Cipher
     * @var Cipher
     */
    private $cipher;

    /**
     * HTTP Header
     * @var array
     */
    protected $header = [];

    /**
     * Cookie Array
     * @var array
     */
    protected $cookie = [];

    /**
     * Output Content
     * @var string
     */
    protected $content = NULL;

    /**
     * Result Values
     * @var array
     */
    protected $values = [];

    /**
     * @var HttpResponse
     */
    protected static $response = NULL;

    /**
     * Return HttpResponse for current session
     * @return HttpResponse
     */
    public static function getCurrentResponse() {
        if (self::$response == NULL) {
            self::$response = new self();
        }
        return self::$response;
    }

    protected function __construct() {
        $this->cipher = Security::getCipher();
    }

    /**
     * @param string $content
     */
    public function setContent($content) {
        $this->content = $content;
    }

    /**
     * @return string
     */
    public function getContent() {
        return $this->content;
    }

    /**
     * @param string $value
     */
    public function setContentType($value) {
        $this->header['Content-Type'] = $value;
    }

    /**
     * @param string $key
     * @param string $value
     */
    public function setHeader($key, $value) {
        $this->header[$key] = $value;
    }

    /**
     * Reset headers, cookies and content
     */
    public function reset() {
        $this->header = [];
        $this->cookie = [];
        $this->content = NULL;
    }

    /**
     * Disable cache
     */
    public function setNoCache() {
        $this->header['Cache-Control'] = 'no-store, no-cache, must-revalidate';
        $this->header['Pragma'] = 'no-cache';
    }

    /**
     * Output http header
     */
    public function outputHeaders() {
        foreach ($this->header as $meta => $value) {
            header($meta . ': ' . $value);
        }
        foreach ($this->cookie as $c) {
            setcookie($c['name'], $c['value'], $c['expire'], $c['path'], $c['domain'], $c['secure']);
        }
    }

    /**
     * Set cookie
     * @param string $name Cookie name
     * @param string $value Cookie value
     * @param mixed $expire Expire time
     * @param string $path Cookie path
     * @param string $domain Cookie domain, NULL for server's http host name
     * @param bool $secure Cookie secure, if TRUE, the cookie will only be transmitted over HTTPS connection
     */
    public function setCookie($name, $value, $expire = NULL, $path = NULL, $domain = NULL, $secure = NULL) {
        $config = Context::$appConfig;

        if ($config->cookieEncryption && $name !== $config->csrfTokenName) {
            $value = $this->cipher->encrypt($value);
        }

        if ($expire === NULL) {
            $expire = $config->cookieLifetime;
        }
        if (is_numeric($expire)) {
            $expire = $_SERVER['REQUEST_TIME'] + $expire;
        } else {
            $expire = strtotime($expire);
        }

        if ($path === NULL) {
            $path = $config->cookiePath;
        }

        if ($domain === NULL) {
            $domain = $config->cookieDomain;
            if (empty($domain)) {
                $domain = $_SERVER['HTTP_HOST'];
            }
        }
        $this->cookie[] = [
            'name' => $name,
            'value' => $value,
            'expire' => $expire,
            'path' => $path,
            'domain' => $domain,
            'secure' => $secure
        ];
    }

    public function getValues() {
        return $this->values;
    }

    public function clearValues() {
        $this->values = [];
    }

    public function bindValue($key, $val) {
        $this->values[$key] = $val;
    }

    public function getValue($key) {
        return $this->values[$key];
    }

}