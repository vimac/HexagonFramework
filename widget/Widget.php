<?php

namespace Hexagon\widget;

use Hexagon\Context;
use Hexagon\system\log\Logging;
use Hexagon\system\http\HttpResponse;

abstract class Widget {
    
    use Logging;
    
    /**
     * Widget main logic
     *
     * @param array $userData
     * @return array
     */
    public abstract function execute(array $userData = NULL);
    
    /**
     * load widget and its default template
     *
     * @param array $userData
     * @param Widget $instance for reuse
     * @return Widget
     */
    public static function load($userData = NULL, Widget $instance = NULL) {
        $className = get_called_class();
        $nameParts = explode('\\', $className);
        $name = array_slice($nameParts, 3);
        array_push($name, lcfirst(array_pop($name)));
        $relativeTemplate = join('/', $name);
        self::_logDebug('Use widget template: ' . $relativeTemplate);
        return self::loadWithTemplate($userData, $relativeTemplate, $instance);
    }
    
    /**
     * load widget and custom template
     *
     * @param array $userData
     * @param string $relativeTemplate relative widget template path
     * @param Widget $instance for reuse
     * @return Widget instance of Widget subclass
     * @throws WidgetTemplateNotFound
     */
    public static function loadWithTemplate($userData = NULL, $relativeTemplate, Widget $instance = NULL) {
        $className = get_called_class();
        if (isset($instance)) {
            $w = $instance;
        } else {
            $w = new $className();
        }
        
        if (!is_array($userData)) {
            if (is_null($userData)) {
                $userData = [];
            } elseif (is_string($userData) || is_numeric($userData) || is_bool($userData)) {
                $userData = ['userData' => $userData];
            }
        }
        
        //$relativeTemplate = str_replace('/', DIRECTORY_SEPARATOR, $relativeTemplate); //this convert is not important on most OS
        $absoluteTemplate = Context::$appBasePath . DIRECTORY_SEPARATOR .
                            'app' . DIRECTORY_SEPARATOR .
                            'view' . DIRECTORY_SEPARATOR .
                            'widget' . DIRECTORY_SEPARATOR .
                            $relativeTemplate . '.php';
        if (file_exists($absoluteTemplate)) {
            $func = function(Widget $_widget, $_className, $_templatePath, $_userData) {
                extract(HttpResponse::getCurrentResponse()->getValues());
                $_result = $_widget->execute($_userData);
                if (is_array($_result)) {
                    extract($_result);
                }
                require $_templatePath;
            };
            $func($w, $className, $absoluteTemplate, $userData);
            
            return $w;
        } else {
            throw new WidgetTemplateNotFound($className);
        }
    }
}


class WidgetTemplateNotFound extends \Exception {
    public function __construct($name) {
        parent::__construct('Widget must have a template file, widget: [' . $name . ']');
    }
}