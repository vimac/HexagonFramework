<?php

namespace Hexagon\system\exception;

Trait ExceptionHandlerSetter {
    
    protected function _setExceptionHandler($cls) {
        ExceptionProcessor::getInstance()->setHandler($cls);
    }
    
}