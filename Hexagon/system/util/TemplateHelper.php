<?php

namespace Hexagon\system\util;

use Hexagon\Context;
use Hexagon\config\BaseConfig;
use Hexagon\system\log\Logging;
use Hexagon\system\security\Security;
use Hexagon\system\security\cipher\Cipher;

class TemplateHelper {
    use Logging;
    
    /**
     * BaseConfig
     */
    protected $config;
    
    /**
     * @var Helper
     */
    protected static $h = null;
    
    /**
     * @return Helper
     */
    public static function getInstance() {
        $name = get_called_class();
        if (self::$h == null) {
            self::$h = new $name();
        }
        return self::$h;
    }
    
    protected function __construct() {
        $this->config = Context::$appConfig;
    }
    
    public function openForm($action, $method = 'POST', $attrs = []) {
        $text = '<form';
        $attrs['action'] = $action;
        $attrs['method'] = $method;
        foreach ($attrs as $name => $attr) {
            $text .= ' ' . $name . '="' . $attr . '"';
        }
        $text .= '>';
        if ($this->config->csrfProtection) {
            $text .= '<div style="display:none"><input type="hidden" name="' . $this->config->csrfTokenName . '" value="' . Security::getCSRFTokenHash() . '" /></div>';
        }
        echo $text;
    }
    
    public function openFormMultipart($action, $method = 'POST', $attrs = []) {
        $attrs['enctype'] = 'multipart/form-data';
        return $this->openForm($action, $method, $attrs);
    }
    
    public function closeForm() {
        echo '</form>';
    }
}