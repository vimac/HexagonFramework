<?php

namespace Hexagon;

class TestBootstrap {
    
    private static function getNSRootByFilename($ns, $file) {
        $fileDir = dirname($file);
        
        $nsStack = explode('\\', $ns);
        $pathStack = explode(DIRECTORY_SEPARATOR, $fileDir);
        
        while (array_pop($nsStack) === ($dir = array_pop($pathStack))) {
            //just an empty while loop to make $dir as app root namespace
        } 
        
        $appDir = implode(DIRECTORY_SEPARATOR, array_merge($pathStack, [$dir]));
        
        return $appDir;
    }
    
    public static function initForTest($namespace, $configClass = NULL) {
        require __DIR__ . DIRECTORY_SEPARATOR . 'Framework.php';
        $arrayNS = explode('\\', $namespace);
        $appNS = array_shift($arrayNS);
        $trace = debug_backtrace();
        $appDir = array_shift($trace)['file'];
        $appBasePath = self::getNSRootByFilename($namespace, $appDir);
        Framework::getInstance()->initApp($appNS, $appBasePath, $configClass, TRUE);
    }
    
}