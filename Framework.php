<?php
/**
 * Hexagon Framework
 * BSD License
 *
 * @author mac
 */

namespace Hexagon;

require 'Common.php';
require 'Functions.php';

use Exception;
use Hexagon\config\BaseConfig;
use Hexagon\event\EventDispatcherInterface;
use Hexagon\intercept\Interceptor;
use Hexagon\system\exception\ExceptionProcessor;
use Hexagon\system\log\Logging;
use Hexagon\system\result\Processor;
use Hexagon\system\result\Result;
use Hexagon\system\security\Security;
use Hexagon\system\uri\Dispatcher;
use Hexagon\system\uri\Router;

/**
 * Application Environment Context
 */
final class Context {

    public static $frameworkPath = __DIR__;
    public static $nsPaths = ['Hexagon' => __DIR__];
    public static $nsNames = ['Hexagon'];
    public static $appNS = '';
    public static $appBasePath = '';
    public static $appEntryName = '';
    public static $uri = '';
    public static $mode = FALSE;

    public static $targetClassNamespace;
    public static $targetClassName;
    public static $targetClassMethod;

    public static $testing = FALSE;

    /**
     * @var EventDispatcherInterface
     */
    public static $eventDispatcher;

    /**
     * @var BaseConfig
     */
    public static $appConfig = NULL;

    public static function autoload($cls) {
        $clsNS = explode('\\', $cls);
        $base = array_shift($clsNS);
        $name = implode(DIRECTORY_SEPARATOR, $clsNS);
        @$path = self::$nsPaths[$base];
        if (!empty($path)) {
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
            throw new \Exception('Namespace [' . $root . '] not found');
        }
    }

    public static function initVendorAutoload() {
        static $loaded = FALSE;
        if (!$loaded) {
            $vendorPath = [
                self::$appBasePath . DIRECTORY_SEPARATOR
                . 'app' . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR
                . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php',
                self::$appBasePath . DIRECTORY_SEPARATOR
                . 'external' . DIRECTORY_SEPARATOR
                . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php'
            ];
            foreach ($vendorPath as $path) {
                if (file_exists($path)) {
                    require $path;
                }
            }
        }
    }
}

spl_autoload_register([__NAMESPACE__ . '\Context', 'autoload']);

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
        if (self::$f == NULL) {
            self::$f = new self();
        }
        return self::$f;
    }

    /**
     * initialize the application
     * @param string $appNS application root namespace name
     * @param string $appBasePath application root path
     * @param string $defConfig defined configclass fullname (must include the namespace), NULL for default
     * @param boolean $testMode defined whether running in test
     * @return Framework
     */
    public function initApp($appNS, $appBasePath, $defConfig = NULL, $testMode = FALSE) {
        Context::registerNS($appNS, $appBasePath);
        Context::$appNS = $appNS;
        Context::$appBasePath = $appBasePath;
        Context::$appEntryName = basename($_SERVER['SCRIPT_FILENAME']);
        Context::$testing = $testMode;

        $mode = $this->getMode();
        Context::$mode = $mode;
        if ($mode) {
            $configClassPrefix = ucfirst($mode);
        } else {
            $configClassPrefix = '';
        }

        if (isset($defConfig)) {
            $configClass = $defConfig;
        } else {
            if ($mode && file_exists(implode(DIRECTORY_SEPARATOR, [
                    $appBasePath, 'app', 'config', $configClassPrefix . 'Config.php'
                ]))
            ) {
                $configClass = $appNS . '\app\config\\' . $configClassPrefix . 'Config';
            } else {
                $configClass = $appNS . '\app\config\Config';
            }
        }

        $config = $configClass::getInstance();
        Context::$appConfig = $config;

        if (isset($config->timezone)) {
            date_default_timezone_set($config->timezone);
        } else {
            date_default_timezone_set(@date_default_timezone_get());
        }

        self::_logDebug('Request for ' . Context::$appConfig->appName . ' start');

        Context::initVendorAutoload();

        if (!$testMode) {
            $this->setDefaultErrorHandler();
        }

        if (isset($config->defaultEventDispatcher)) {
            $dispatcherName = $config->defaultEventDispatcher;
            Context::$eventDispatcher = new $dispatcherName();
        }

        if (isset($config->defaultEventSubscriber) && isset(Context::$eventDispatcher)) {
            $eventSubscriberName = $config->defaultEventSubscriber;
            Context::$eventDispatcher->addSubscriber($eventSubscriberName);
        }

        Context::$eventDispatcher->dispatch('HF::onInited');

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

        if ($outputBuffer) {
            ob_start();
        }

        /**
         * FIXME
         * need refactor
         */
        $interceptResult = NULL;
        Context::$eventDispatcher->addListener('HF::controllerWillInit', function() use (&$interceptResult) {
            $interceptResult = Interceptor::getInstance()->commitPreRules();
        });

        // check pre interceptor rule results
        if (!isset($interceptResult)) {
            $dispatcher = Dispatcher::getInstance();
            if (HEXAGON_CLI_MODE) {
                $conResult = $dispatcher->invoke($uri, Dispatcher::TYPE_CLI_TASK);
            } else {
                $conResult = $dispatcher->invoke($uri, Dispatcher::TYPE_WEB_CONTROLLER);
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
        Context::$eventDispatcher->dispatch('HF::onStop');
        if (!empty($msg)) {
            self::_logInfo('End the response. msg: ' . $msg);
        } else {
            self::_logInfo('End the response.');
        }
        exit($code);
    }

    /**
     * Get application enviroment
     *
     * @return mixed "dev", "production", "test", "local" or any user defined value, or false when no set
     */
    public function getMode() {
        if (isset($_SERVER['HEXAGON_MODE'])) {
            $mode = $_SERVER['HEXAGON_MODE'];
        } else {
            $modeLock = current(glob(Context::$appBasePath . DIRECTORY_SEPARATOR . '*.lock'));
            if ($modeLock) {
                $mode = ucfirst(pathinfo($modeLock, PATHINFO_FILENAME));
            } else {
                $mode = FALSE;
            }
        }
        return $mode;
    }

    private function __construct() {
        // do nothing
    }

    public function __destruct() {
        Context::$eventDispatcher->dispatch('HF::onExit');
        @self::_logDebug('Request ' . Context::$appConfig->appName . ' processed, total time: ' . (microtime(TRUE) - $_SERVER['REQUEST_TIME_FLOAT']) . ' secs');
    }
}