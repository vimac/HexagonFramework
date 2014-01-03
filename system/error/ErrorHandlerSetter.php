<?php

namespace Hexagon\system\error;

use \Hexagon\Framework;

Trait ErrorHandlerSetter {
    
    protected function _setErrorHandler($cls) {
        Framework::getInstance()->setErrorHandler($cls);
    }
    
}