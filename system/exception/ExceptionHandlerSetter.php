<?php

namespace Hexagon\system\exception;

Trait ExceptionHandlerSetter {

    /**
     * Set default exception handler
     *
     * @param string $cls Class full name
     */
    protected function _setExceptionHandler($cls) {
        ExceptionProcessor::getInstance()->setHandler($cls);
    }
    
}