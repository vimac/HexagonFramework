<?php
namespace Hexagon\config;

class BaseConfig {

    public $appName = 'Nothing';
    public $appUrl = 'http://localhost/';
    
    public $database = [];
    public $logLevel = HEXAGON_LOG_LEVEL_ALL;
    
    public $logPath = ''; // empty for project logs directory
    public $logNameSuffix = '';
    public $logAppender = '\Hexagon\system\log\FileLogAppender';
    
    public $charset = 'UTF-8';
    
    public $uriProtocol = HEXAGON_URI_PROTOCOL_AUTO;
    public $uriDefault = 'welcome/index';
    public $uriSuffix = '';
    
    public $errorHandler = '\Hexagon\system\error\BaseErrorHandler';

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
    
    /**
     * An instance of this class
     * @var BaseConfig
     */
    private static $c;
    
    /**
     * Singleton
     * @var BaseConfig
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
        if (is_array($this->database[0])) {
            if ($cfgName === 'default') {
                return $this->database[0];
            } else {
                return $this->database[$cfgName];
            }
        } else {
            return $this->database;
        }
    }
    
}