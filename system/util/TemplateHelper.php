<?php

namespace Hexagon\system\util;

use Hexagon\Context;
use Hexagon\config\BaseConfig;
use Hexagon\system\log\Logging;
use Hexagon\system\security\Security;
use Hexagon\system\security\cipher\Cipher;
use Hexagon\system\http\HttpRequest;
use Hexagon\system\http\HttpResponse;

class TemplateHelper {
    use Logging;
    
    /**
     * @var BaseConfig
     */
    protected $config;
    
    /**
     * @var string
     */
    protected $panelRoot;

    public $metajs = [];

    public $metacss = [];
    
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
        $this->panelRoot = Context::$appBasePath . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'view' . DIRECTORY_SEPARATOR . 'panel';
    }
    
    public function loadPanel($panelPath) {
        $func = function(HttpRequest $_request, HttpResponse $_response, $_panelPath, $_helper) {
            extract($_response->getValues());
            require($this->panelRoot . DIRECTORY_SEPARATOR . $_panelPath . '.php');
        };
        $func(HttpRequest::getCurrentRequest(), HttpResponse::getCurrentResponse(), $panelPath, $this);
    }
    
    public function formOpen($action, $method = 'POST', $attrs = []) {
        $text = '<form';
        $attrs['action'] = $action;
        $attrs['method'] = $method;
        foreach ($attrs as $name => $attr) {
            $text .= ' ' . $name . '="' . $attr . '"';
        }
        $text .= '>';
        if ($this->config->csrfProtection && strcasecmp($method, 'POST') === 0) {
            $text .= '<div style="display:none"><input type="hidden" name="' . $this->config->csrfTokenName . '" value="' . Security::getCSRFTokenHash() . '" /></div>';
        }
        echo $text;
    }
    
    public function formOpenMultipart($action, $method = 'POST', $attrs = []) {
        $attrs['enctype'] = 'multipart/form-data';
        return $this->formOpen($action, $method, $attrs);
    }
    
    public function formClose() {
        echo '</form>';
    }
    
    public function addJS($file) {
        $this->metajs[] = $file;
    }
    
    public function addCSS($file) {
        $this->metacss[] = $file;
    }
}