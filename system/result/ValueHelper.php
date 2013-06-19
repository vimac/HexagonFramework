<?php

namespace Hexagon\system\result;

trait ValueHelper {
    protected function bindValue($key, $val) {
        $this->response->bindValue($key, $val);
    }
    
    protected function getValue($key) {
        return $this->response->getValue($key);
    }
    
    protected function getValues() {
        return $this->response->getValues();
    }
}