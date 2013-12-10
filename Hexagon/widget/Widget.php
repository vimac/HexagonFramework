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
    
    public static function load (Widget $instance = NULL) {
        $className = get_called_class();
        $nameParts = explode('\\', $className);
        $name = array_slice($nameParts, 3);
        $relativeTemplate = join('/', $name);
        return self::loadWithTemplate($relativeTemplate, $instance);
    }
    
    public static function loadWithTemplate ($relativeTemplate, Widget $instance = NULL) {
        $className = get_called_class();
        if (isset($instance)) {
            $w = $instance;
        } else {
            $w = new $className();
        }
        //$relativeTemplate = str_replace('/', DIRECTORY_SEPARATOR, $relativeTemplate); //this convert is not important on most OS
        $absoluteTemplate = Context::$appBasePath . DIRECTORY_SEPARATOR .
                            'app' . DIRECTORY_SEPARATOR .
                            'view' . DIRECTORY_SEPARATOR .
                            'widget' . DIRECTORY_SEPARATOR .
                            $relativeTemplate . '.php';
        if (file_exists($absoluteTemplate)) {
            $func = function($_widget, $_className, $_templatePath) {
                extract($_widget->execute());
                require $_templatePath;
            };
            $func($w, $className, $absoluteTemplate);
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