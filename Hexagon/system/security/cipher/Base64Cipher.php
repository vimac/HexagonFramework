<?php

namespace Hexagon\system\security\cipher;

/**
 * <p><font color="red">
 * WARNING: BASE64 IS ACTUALLY NOT A ENCRYPTION ALGORITHM!!<br />
 * DO NOT USE THIS IN PRODUCTION MODE!! 
 * </font></p>
 */
class Base64Cipher extends Cipher{
    
    public function encrypt($text) {
        echo base64_encode($text);
    }
    
    public function decrypt($text) {
        echo base64_decode($text, TRUE);
    }
    
}