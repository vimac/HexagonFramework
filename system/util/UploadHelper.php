<?php

namespace Hexagon\system\util;

use \Exception;

final class UploadHelper {

    /**
     * Ignore new file when old file exists
     */
    const UPLOAD_FILE_SAME_SKIP = -1;

    /**
     * New file override old file
     */
    const UPLOAD_FILE_OVERRIDES_OLD = 0;

    /**
     * Auto rename new file by add number suffix
     */
    const UPLOAD_FILE_AUTO_RENAME = 1;

    /**
     * Auto counted file name
     */
    const UPLOAD_FILE_NAME_AUTO_INCREMENT = 2;

    /**
     * Auto generate random filename 
     */
    const UPLOAD_FILE_AUTO_RANDOM_NAME = 3;

    /**
     *
     * @var UploadHelper
     */
    protected static $h = null;

    /**
     *
     * @return UploadHelper
     */
    public static function getInstance() {
        $name = get_called_class();
        if (self::$h == null) {
            self::$h = new $name();
        }
        return self::$h;
    }

    private function getOriginalFilename($dir, $file) {
        return $dir . DIRECTORY_SEPARATOR . $file;
    }

    private function getAutoRenamedFilename($dir, $file, $index = 1) {
        $ori = $this->getOriginalFilename($dir, $file);
        if (file_exists($ori)) {
            $info = pathinfo($file);
            $ext = empty($info['extension']) ? '' : '.' . $info['extension'];
            $new = $dir . DIRECTORY_SEPARATOR . $info['filename'] . '_' . $index . $ext;
            if (file_exists($new)) {
                return $this->getAutoRenamedFilename($dir, $file, $index + 1);
            } else {
                return ($new);
            }
        } else {
            return $ori;
        }
    }

    private function getAutoIncrementFilename($dir, $file) {
        $ext = pathinfo($file, PATHINFO_EXTENSION);
        $ext = empty($ext) ? '' : '.' . $ext;
        $allFiles = glob($dir . DIRECTORY_SEPARATOR . '*' . $ext);
        $count = count($allFiles) + 1;
        return $dir . DIRECTORY_SEPARATOR . $count . $ext;
    }

    private function getRandomFilename($dir, $file) {
        $ext = pathinfo($file, PATHINFO_EXTENSION);
        $ext = empty($ext) ? '' : '.' . $ext;
        $random = hash('MD5', $file . microtime(TRUE)) . $ext;
        if (file_exists($random)) {
            return $this->getRandomFilename($dir, $file);
        } else {
            return $dir . DIRECTORY_SEPARATOR . $random;
        }
    }

    private function getSkipFilename($dir, $file) {
        $ori = $this->getOriginalFilename($dir, $file);
        if (file_exists($ori)) {
            return NULL;
        } else {
            return $ori;
        }
    }

    public function checkUploads() {
        if (empty($_FILES) || count($_FILES) === 0) {
            return FALSE;
        } else {
            return TRUE;
        }
    }

    public function moveFilesToDirectory($dir, $namePolicy = self::UPLOAD_FILE_OVERRIDES_OLD, $allowedExtensions = [], Callable $filterFunc = NULL) {
        if (!file_exists($dir)) {
            @mkdir($dir);
        }
        
        $dir = realpath($dir);
        
        if (!file_exists($dir) || !is_writable($dir)) {
            throw new UploadDirectoryCannotBeAccess();
        }
        
        switch ($namePolicy) {
            case self::UPLOAD_FILE_SAME_SKIP:
                $func = [$this, 'getSkipFilename'];
                break;
            case self::UPLOAD_FILE_AUTO_RENAME:
                $func = [$this, 'getAutoRenamedFilename'];
                break;
            case self::UPLOAD_FILE_NAME_AUTO_INCREMENT:
                $func = [$this, 'getAutoIncrementFilename'];
                break;
            case self::UPLOAD_FILE_AUTO_RANDOM_NAME:
                $func = [$this, 'getRandomFilename'];
                break;
            default:
                $func = [$this, 'getOriginalFilename'];
        }
        
        $result = [];
        foreach ($_FILES as $key => $file) {
            if (!empty($allowedExtensions)) {
                $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                if (!in_array($ext, $allowedExtensions)) {
                    break;
                }
            }
            if (isset($filterFunc)) {
                if (!$filterFunc($file)) {
                    break;
                }
            }
            $newpath = call_user_func($func, $dir, $file['name']);
            if (!empty($newpath)) {
                if (move_uploaded_file($file['tmp_name'], $newpath)) {
                    $result[$key] = $newpath;
                } else {
                    throw new UploadFileCannotBeMoved();
                }
            }
        }
        
        return $result;
    }
    
    
    public function moveFileToDirectory($dir, $key, $file, $namePolicy = self::UPLOAD_FILE_OVERRIDES_OLD, $allowedExtensions = [], Callable $filterFunc = NULL) {
    	if (!file_exists($dir)) {
    		@mkdir($dir);
    	}
    
    	$dir = realpath($dir);
    
    	if (!file_exists($dir) || !is_writable($dir)) {
    		throw new UploadDirectoryCannotBeAccess();
    	}
    
    	switch ($namePolicy) {
    		case self::UPLOAD_FILE_SAME_SKIP:
    			$func = [$this, 'getSkipFilename'];
    			break;
    		case self::UPLOAD_FILE_AUTO_RENAME:
    			$func = [$this, 'getAutoRenamedFilename'];
    			break;
    		case self::UPLOAD_FILE_NAME_AUTO_INCREMENT:
    			$func = [$this, 'getAutoIncrementFilename'];
    			break;
    		case self::UPLOAD_FILE_AUTO_RANDOM_NAME:
    			$func = [$this, 'getRandomFilename'];
    			break;
    		default:
    			$func = [$this, 'getOriginalFilename'];
    	}
    
    	$result = NULL;
    	if (!empty($allowedExtensions)) {
    		$ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    		if (!in_array($ext, $allowedExtensions)) {
    			return NULL;
    		}
    	}
    	if (isset($filterFunc)) {
    		if (!$filterFunc($file)) {
    			return NULL;
    		}
    	}
    	$newpath = call_user_func($func, $dir, $file['name']);
    	if (!empty($newpath)) {
    		if (move_uploaded_file($file['tmp_name'], $newpath)) {
    			$result =  $newpath;
    			} else {
    			throw new UploadFileCannotBeMoved();
    		}
    	}
    	
    	return $result;
    }

}

class UploadDirectoryCannotBeAccess extends Exception {

    public function __construct() {
        parent::__construct('Upload directory cannot be access?');
    }

}

class UploadFileCannotBeMoved extends Exception {

    public function __construct() {
        parent::__construct('Upload file cannot be moved?');
    }

}