<?php
/**
 * Hexagon Framework
 * BSD License
 * 
 * @author mac
 */

namespace Hexagon;

require 'Common.php';

use \Exception;

/**
 * Application Enviroment Context
 */
final class Context {
    public static $frameworkPath = __DIR__;
    public static $nsPaths = ['Hexagon' => __DIR__];
    public static $nsNames = ['Hexagon'];
    public static $appNS = '';
    public static $appBasePath = '';
    public static $appEntryName = '';
    public static $uri = '';
    public static $developmentMode = FALSE;
    
    public static $targetClassNamespace;
    public static $targetClassName;
    public static $targetClassMethod;
    
    /**
     * @var \Hexagon\config\BaseConfig
     */
    public static $appConfig = null;
    
    public static function autoload($cls) {
        $clsNS = explode('\\', $cls);
        $base = array_shift($clsNS);
        $name = implode(DIRECTORY_SEPARATOR, $clsNS);
        $path = self::$nsPaths[$base];
        $clsFile = $path . DIRECTORY_SEPARATOR . $name . '.php';
        
        if (substr($name, -10) === 'Controller') {
            $isController = TRUE;
        } else {
            $isController = FALSE;
        }
        
        if (file_exists($clsFile)) {
            require $clsFile;
        } else {
            if ($isController) {
                $lowName = strtolower(substr($name, 0, -10));
                $clsNS = explode('/', $lowName);
                $filename = ucfirst($clsNS[count($clsNS) - 1]);

                $name = implode(DIRECTORY_SEPARATOR, $clsNS);
                $dirClsFile = $path . DIRECTORY_SEPARATOR . $name . DIRECTORY_SEPARATOR . $filename . '.php';
                
                if (file_exists($dirClsFile)) {
                    require $dirClsFile;
                    return;
                } else {
                    trigger_error('Request [' . $_SERVER['REQUEST_URI'] . '] failed, cause class [' . $cls . '] not found, try to include file: [' . $clsFile . ', ' . $dirClsFile . '] namespace paths: [' . implode(', ', Context::$nsPaths) . ']', E_USER_ERROR);
                }
            }
            trigger_error('Class [' . $cls . '] not found, try to include file: [' . $clsFile . '] namespace paths: [' . implode(', ', Context::$nsPaths) . ']', E_USER_ERROR);
        }
    }
    
    public static function registerNS($baseNS, $basePath) {
        self::$nsPaths[$baseNS] = $basePath;
        self::$nsNames[] = $baseNS;
    }
    
    public static function getResourcePath($nsPath) {
        $ns = explode('\\', trim($nsPath));
        $root = array_shift($ns);
        if (in_array($root, self::$nsNames)) {
            $path = self::$nsPaths[$root] . DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, $ns);
            return $path;
        } else {
            throw new Exception('Namespace [' . $root . '] not found');
        }
    }
}

spl_autoload_register([__NAMESPACE__ . '\Context', 'autoload']);

use \Hexagon\system\log\Logging;
use \Hexagon\system\uri\Router;
use \Hexagon\system\uri\Dispatcher;
use \Hexagon\system\http\HttpResponse;
use \Hexagon\intercept\Interceptor;
use \Hexagon\system\result\Processor;
use \Hexagon\system\result\Result;
use \Hexagon\system\security\Security;
use \Hexagon\system\exception\ExceptionProcessor;

final class Framework {
    
    use Logging;
    
    /**
     * An instance of this class
     * @var Framework
     */
    private static $f;
    
    /**
     * Singleton
     * @return Framework
     */
    public static function getInstance() {
        if (self::$f == null) {
            self::$f = new self();
        }
        return self::$f;
    }
    
    /**
     * initialize the application
     * @param string $appNS application root namespace name
     * @param string $appBasePath application root path
     * @param string $defConfig defined configclass fullname (must include the namespace), NULL for default
     */
    public function initApp($appNS, $appBasePath, $defConfig = NULL) {
        Context::registerNS($appNS, $appBasePath);
        Context::$appNS = $appNS;
        Context::$appBasePath = $appBasePath;
        Context::$appEntryName = basename($_SERVER['SCRIPT_FILENAME']);
        Context::$developmentMode = file_exists($appBasePath . DIRECTORY_SEPARATOR . 'dev.lock');
        
        if (isset($defConfig)) {
            $configClass = $defConfig;
        } else {
            if (Context::$developmentMode &&
            file_exists(
                    $appBasePath . DIRECTORY_SEPARATOR . 'app' .
                    DIRECTORY_SEPARATOR . 'config' .
                    DIRECTORY_SEPARATOR . 'DevConfig.php')) {
                $configClass = $appNS . '\app\config\DevConfig';
            } else {
                $configClass = $appNS . '\app\config\Config';
            }
        }
        $config = $configClass::getInstance();
        Context::$appConfig = $config;
        
        $this->setDefaultErrorHandler();
        
        self::_logDebug('Request for ' . Context::$appConfig->appName . ' start');
        
        return $this;
    }
    
    private function setDefaultErrorHandler() {
        if (isset(Context::$appConfig->defaultErrorHandler)) {
            ExceptionProcessor::getInstance()->setHandler(Context::$appConfig->defaultErrorHandler);
        }
    }
    
    /**
     * @param string $uri defined uri, usually used in cli or maintenance mode
     * @param bool $outputBuffer enable or disable output buffer
     * @return Framework
     */
    public function run($uri = NULL, $outputBuffer = TRUE) {
        $config = Context::$appConfig;
        if ($config->csrfProtection) {
            Security::vertifyCSRFToken();
        }
        
        if (!$uri) {
            $router = Router::getInstance();
            $uri = $router->resolveURI();
        }
        Context::$uri = $uri;

        if (!HEXAGON_CLI_MODE) {
            session_start();
        }
        
        if ($outputBuffer) {
            ob_start();
        }
        
        $interceptResult = Interceptor::getInstance()->commitPreRules();
        
        // check pre interceptor rule results
        if (!isset($interceptResult)) {
            $dispatcher = Dispatcher::getInstance();
            if (HEXAGON_CLI_MODE) {
                $conResult = $dispatcher->invokeTask($uri);
            } else {
                $conResult = $dispatcher->invoke($uri);
            }
            
            Context::$targetClassMethod = $dispatcher->method;
            Context::$targetClassName = $dispatcher->className;
            Context::$targetClassNamespace = $dispatcher->classNS;
            
            $interceptResult = Interceptor::getInstance()->commitPostRules();
        }
        
        $processor = Processor::getInstance();
        
        // check post interceptor rule results
        if (isset($interceptResult)) {
            $processor->processResult($interceptResult);
        } else {
            if ($conResult) {
                $processor->processResult($conResult);
            } else {
                if (!HEXAGON_CLI_MODE) {
                    //default page result
                    $processor->processResult(new Result());
                }
            }
        }
        
        if ($outputBuffer) {
            ob_flush();
        }
        
        return $this;
    }
    
    public function stop($code = 0, $msg = '', $func = NULL) {
        if (!empty($msg)) {
            self::_logDebug('End the response. msg: ' . $msg);            
        } else {
            self::_logDebug('End the response.');
        }
        die;
    }
    
    private function __construct() {
        // do nothing
    }
    
    public function __destruct() {
        self::_logDebug('Request ' . Context::$appConfig->appName . ' processed, total time: ' . (microtime(true) - $_SERVER['REQUEST_TIME_FLOAT']) . ' secs' );
    }
}