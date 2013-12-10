<?php

namespace Hexagon\system\result;

use Hexagon\Context;
use Hexagon\system\log\Logging;
use Hexagon\system\result\Result;
use Hexagon\system\http\HttpResponse;
use Hexagon\system\http\HttpRequest;
use Hexagon\system\util\TemplateHelper;

class Processor {
    use Logging;
    
    protected $layoutRoot = '';
    protected $screenRoot = '';
    
    /**
     * @var Processor
     */
    protected static $p = null;
    
    /**
     * @return Processor
     */
    public static function getInstance() {
        if (self::$p == null) {
            self::$p = new self();
        }
        return self::$p;
    }
    
    protected function __construct() {
        $this->layoutRoot = Context::$appBasePath . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'view' . DIRECTORY_SEPARATOR . 'layout';
        $this->screenRoot = Context::$appBasePath . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'view' . DIRECTORY_SEPARATOR . 'screen';
    }
    
    protected function processPAGE(Result $result) {
        $request = HttpRequest::getCurrentRequest();
        $response = HttpResponse::getCurrentResponse();
        
        $isDefinedScreen = isset($result->meta['screen']);
        
        if ($isDefinedScreen) {
            $relativeScreen = $result->meta['screen'];
        } else {
            $uri = Context::$uri;
            $relativeScreen = str_replace('/', DIRECTORY_SEPARATOR, $uri);
        }
        
        if ($relativeScreen[0] !== DIRECTORY_SEPARATOR) {
            $relativeScreen = DIRECTORY_SEPARATOR . $relativeScreen;
        }
        $absoluteScreen = $this->screenRoot . $relativeScreen . '.php';
        
        if (!file_exists($absoluteScreen)) {
            if ($isDefinedScreen) {
                throw new ViewTemplateNotFound($absoluteScreen);
            } else {
                $uriParts = explode('/', $uri);
                while(count($uriParts) > 0) {
                    $absoluteScreen = $this->screenRoot . join(DIRECTORY_SEPARATOR, $uriParts) . DIRECTORY_SEPARATOR . 'default.php';
                    if (file_exists($absoluteScreen)) {
                        break;
                    }
                    array_pop($uriParts);
                }
            }
        }
        $this->_logDebug('screen path: ' . $absoluteScreen);
        
        $isDefinedLayout = isset($result->meta['layout']);
        
        if ($isDefinedLayout) {
            $relativeLayout = $result->meta['layout'];
        } else {
            $uri = Context::$uri;
            $relativeLayout = str_replace('/', DIRECTORY_SEPARATOR, $uri);
        }
        
        if ($relativeLayout[0] !== DIRECTORY_SEPARATOR) {
            $relativeLayout = DIRECTORY_SEPARATOR . $relativeLayout;
        }
        $absoluteLayout = $this->layoutRoot . $relativeLayout . '.php';
        
        if (!file_exists($absoluteLayout)) {
            if ($isDefinedLayout) {
                throw new ViewTemplateNotFound($absoluteLayout);
            } else {
                $uriParts = explode('/', $uri);
                while(count($uriParts) > 0) {
                    $absoluteLayout = $this->layoutRoot . join(DIRECTORY_SEPARATOR, $uriParts) . DIRECTORY_SEPARATOR . 'default.php';
                    if (file_exists($absoluteLayout)) {
                        break;
                    }
                    array_pop($uriParts);
                }
            }
        }
        $this->_logDebug('layout path: ' . $absoluteLayout);
        
        $data = $result->data;
        foreach ($data as $k => $v) {
            $response->bindValue($k, $v);
        }
        
        $func = function(HttpRequest $_request, HttpResponse $_response, Result $_result, TemplateHelper $_helper, $_screen, $_layout) {
            extract($_response->getValues(), EXTR_PREFIX_SAME, 'h_');
            ob_start();
            require $_screen;
            $_screenHolder = ob_get_contents();
            ob_end_clean();
            
            require $_layout;
        };
        $func($request, $response, $result, TemplateHelper::getInstance(), $absoluteScreen, $absoluteLayout);
    }
    
    protected function processTEXT(Result $result) {
        echo $result->data;
    }
    
    protected function processHTML(Result $result) {
        echo $result->data;
    }
    
    protected function processNONE(Result $result) {
        // do nothing
    }
    
    protected function processJSON(Result $result) {
        echo json_encode($this->mergeResult($result)->data);
    }
    
    protected function processXML(Result $result) {
        $root = Context::$targetClassMethod ? Context::$targetClassMethod : 'root';
        $xml = new \SimpleXMLElement('<' . $root . '/>');
        self::arrayToXML($this->mergeResult($result)->data, $xml);
        echo $xml->asXML();
    }
    
    protected function processPNG(Result $result) {
        imagepng($result->data);
        imagedestroy($result->data);
    }
    
    protected function processJPEG(Result $result) {
        if (isset($result->meta['quality'])) {
            imagejpeg($result->data, $result->meta['quality']);
        } else {
            imagejpeg($result->data);
        }
        imagedestroy($result->data);
    }
    
    protected function processGIF(Result $result) {
        imagegif($result->data);
        imagedestroy($result->data);
    }
    
    private function mergeResult(Result $result) {
        $result->data = array_merge($result->data, HttpResponse::getCurrentResponse()->getValues());
        return $result;
    }
    
    private static function arrayToXML($arrayData, &$xml, &$parent = NULL){
        foreach ($arrayData as $key => $value) {
            if (is_array($value)) {
                if (is_numeric($key)) {
                    $subnode = $xml->addChild('item');
                    self::arrayToXML($value, $xml, $subnode);
                } else {
                    $subnode = $xml->addChild($key);
                    self::arrayToXML($value, $subnode);
                }
            } else {
                if (isset($parent)) {
                    $parent->addChild($key, $value);
                } else {
                    $xml->addChild($key, $value);
                }
            }
        }
    }
    
    public function processResult(Result $result) {
        $response = HttpResponse::getCurrentResponse();
        $response->setContentType($result->contentType);
        $response->outputHeaders();
        $func = 'process' . $result->type;
        return $this->$func($result);
    }
}

class ViewTemplateNotFound extends \Exception {
    public function __construct($screenName) {
        parent::__construct('Screen file [' . $screenName . '] not found.', 500);
    }
}