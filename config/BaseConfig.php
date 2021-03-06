<?php
namespace Hexagon\config;

class BaseConfig {

    public $appName = 'Nothing';
    public $appUrl = 'http://localhost/';

    public $database = [];

    public $logs = [
        [
            'level' => HEXAGON_LOG_LEVEL_ALL,
            'match' => HEXAGON_LOG_MATCH_ALL,
            'appender' => '\Hexagon\system\log\NullAppender',
            'params' => []
        ],
    ];

    public $charset = 'UTF-8';
    public $timezone = 'Asia/Shanghai';

    public $uriProtocol = HEXAGON_URI_PROTOCOL_AUTO;
    public $uriDefault = 'welcome/index';
    public $uriSuffix = '';

    public $defaultErrorHandler = '\Hexagon\system\exception\DefaultExceptionHandler';

    public $csrfProtection = TRUE;
    public $csrfTokenName = '_hexagon_csrf';

    public $encryptionKey = 'The answer to life, the universe and everything';

    public $cookieEncryption = FALSE;
    public $cookieLifetime = '1 hour';
    public $cookiePath = '/';
    public $cookieDomain = '';
    public $cookieSecure = FALSE;

    public $cipher = '\Hexagon\system\security\cipher\Rijndael256Cipher';
    public $cipherIv = '50a2fabfdd276f573ff97ace8b11c5f4';

    public $interceptRules = [];

    public $defaultEventDispatcher = '\Hexagon\event\EventDispatcher';
    public $defaultEventSubscriber = NULL;

    /**
     * An instance of this class
     * @var BaseConfig
     */
    private static $c;

    /**
     * Singleton
     * @return BaseConfig
     */
    public static function getInstance() {
        $name = get_called_class();
        if (self::$c == null) {
            self::$c = new $name();
        }
        return self::$c;
    }

    /**
     * @param $cfgName string Name in $this->database array
     * @return array
     */
    public function getDBConfig($cfgName = 'default') {
        if (is_array(current($this->database))) {
            if ($cfgName === 'default') {
                return current($this->database);
            } else {
                return $this->database[$cfgName];
            }
        } else {
            return $this->database;
        }
    }

    public function __construct() {
        // do nothing
    }

}