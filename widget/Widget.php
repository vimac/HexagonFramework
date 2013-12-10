<?php

namespace Hexagon\widget;

use Hexagon\Context;
use Hexagon\system\log\Logging;

abstract class Widget {
    
    use Logging;
    
    /**
     * @return array
     */
    public abstract function execute();
    
    /**
     * load widget and its default template
     * @param array $userData
     * @param Widget $instance for reuse
     */
    public static function load($userData, Widget $instance = NULL) {
        $className = get_called_class();
        $nameParts = explode('\\', $className);
        $name = array_slice($nameParts, 3);
        $relativeTemplate = join('/', $name);
        return self::loadWithTemplate($relativeTemplate, $instance);
    }
    
    /**
     * load widget and its default template
     * @param array $userData
     * @param string $relativeTemplate relative widget template path
     * @param Widget $instance for reuse
     */
    public static function loadWithTemplate($userData, $relativeTemplate, Widget $instance = NULL) {
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
            $func = function($_widget, $_className, $_templatePath, $_userData) {
                extract($_userData);
                extract($_widget->execute());
                require $_templatePath;
            };
            $func($w, $className, $absoluteTemplate, $userData);
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