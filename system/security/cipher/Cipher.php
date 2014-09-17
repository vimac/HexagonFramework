<?php

namespace Hexagon\system\security\cipher;

abstract class Cipher implements ICipher {

    /**
     * @var Cipher
     */
    protected static $d = null;

    /**
     * @return Cipher
     */
    public static function getInstance() {
        $name = get_called_class();
        if (self::$d == null) {
            self::$d = new $name();
        }
        return self::$d;
    }

}
