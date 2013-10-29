<?php

namespace Hexagon\system\result;

trait ValueHelper {
    protected function _bindValue($key, $val) {
        $this->response->bindValue($key, $val);
    }
    
    protected function _getValue($key) {
        return $this->response->getValue($key);
    }
    
    protected function _getValues() {
        return $this->response->getValues();
    }
    
    protected function _clearValues() {
        return $this->response->clearValues();
    }
}