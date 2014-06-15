<?php

namespace Hexagon\system\security\cipher;

/**
 * <p><font color="red">
 * WARNING: BASE64 IS ACTUALLY NOT A ENCRYPTION ALGORITHM!!<br />
 * DO NOT USE THIS IN PRODUCTION ENVIROMENT!!
 * </font></p>
 */
class Base64Cipher extends Cipher{
    
    public function encrypt($text) {
        return base64_encode($text);
    }
    
    public function decrypt($text) {
        return base64_decode($text, TRUE);
    }
    
}