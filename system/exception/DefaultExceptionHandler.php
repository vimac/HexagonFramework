<?php

namespace Hexagon\system\exception;

use Exception;
use Hexagon\Context;

/**
 * Default Exception Handler
 *
 * @author jim9@KDays
 */
class DefaultExceptionHandler extends ExceptionHandler {

    public function handleException(Exception $ex) {
        $trace = $ex->getTrace();

        if (!isset($trace[0]['file']) || empty($trace[0]['file'])) {
            unset($trace[0]);
            $trace = array_values($trace);
        }

        $file = isset($trace[0]['file']) ? $trace[0]['file'] : 'internal';
        $line = isset($trace[0]['line']) ? $trace[0]['line'] : 1;

        $this->msg($ex->getMessage(), $file, $line, $trace, $ex->getCode());
    }

    public function handleFatal($error, $message, $file, $line) {
        if (ob_get_level() !== 0) {
            ob_end_clean();
        }
        $this->msg($message, $file, $line, [], $error);
    }

    /**
     * output error message
     *
     * @param string $message reason
     * @param string $file file
     * @param int $line line
     * @param array $trace trace
     * @param int $errorcode error code
     */
    public function msg($message, $file, $line, $trace, $errorcode) {
        $log = $message . "\r\n" . str_replace(Context::$appBasePath, '', $file) . ":" . $line . "\r\n";
        list($fileLines, $trace) = self::crash($file, $line, $trace);
        foreach ($trace as $key => $value) {
            $log .= $value . "\r\n";
        }

        $fileLineLog = "";
        foreach ($fileLines as $key => $value) {
            $value = str_replace(["  ", "\t"], "<span class='w-block'></span>", $value);
            if ($key == $line - 1) {
                $fileLineLog .= "<li class='current'>$value</li>";
            } else {
                $fileLineLog .= "<li>$value</li>\n";
            }
        }

        $file = str_replace(Context::$appBasePath, '', $file);
        if (HEXAGON_CLI_MODE) {
            fwrite(STDOUT, date('[Y-m-d H:i:s] ') . $message . "($file:$line)" . PHP_EOL);
        } else {
            require(implode(DIRECTORY_SEPARATOR, [Context::$frameworkPath, 'template', 'exception.php']));
            exit();
        }
    }

    /**
     *
     *
     * @param string $file file path
     * @param string $line line
     * @param array $trace trace
     * @return array
     */
    public static function crash($file, $line, $trace) {
        $msg = '';
        $count = count($trace);
        $padLen = strlen($count);

        foreach ($trace as $key => $call) {
            if (!isset($call['file']) || $call['file'] == '') {
                $call['file'] = 'Internal Location';
                $call['line'] = 'N/A';
            } else {
                $call['file'] = str_replace(Context::$appBasePath, '', $call['file']);
            }
            $traceLine = '#' . str_pad(($count - $key), $padLen, "0", STR_PAD_LEFT) . ' ' . self::getCallLine($call);
            $trace[$key] = $traceLine;
        }

        $fileLines = [];
        if (is_file($file)) {
            $currentLine = $line - 1;

            $fileLines = explode("\n", file_get_contents($file, NULL, NULL, 0, 10000000));
            $topLine = $currentLine - 5;
            $fileLines = array_slice($fileLines, $topLine > 0 ? $topLine : 0, 10, TRUE);

            if (($count = count($fileLines)) > 0) {
                $padLen = strlen($count);
                foreach ($fileLines as $line => &$fileLine) {
                    $fileLine = " <b>" . str_pad($line + 1, $padLen, "0", STR_PAD_LEFT) . "</b> " . htmlspecialchars(str_replace("\t", "    ", rtrim($fileLine)), NULL, 'UTF-8');
                }
            }
        }

        return [$fileLines, $trace];
    }

    /**
     *
     *
     * @param array $call
     * @return string
     */
    private static function getCallLine($call) {
        $call_signature = "";
        if (isset($call['file'])) {
            $call_signature .= $call['file'] . " ";
        }

        if (isset($call['line'])) {
            $call_signature .= ":" . $call['line'] . " ";
        }

        if (isset($call['function'])) {
            $call_signature .= "<span class=\"func\">";
            if (isset($call['class'])) {
                $call_signature .= "$call[class]->";
            }

            $call_signature .= $call['function'] . "(";
            if (isset($call['args'])) {
                foreach ($call['args'] as $arg) {
                    if (is_string($arg)) {
                        $arg = '"' . (strlen($arg) <= 64 ? $arg : substr($arg, 0, 64) . "...") . '"';
                    } elseif (is_object($arg)) {
                        $arg = "[Instance of '" . get_class($arg) . "']";
                    } elseif ($arg === TRUE) {
                        $arg = "true";
                    } elseif ($arg === FALSE) {
                        $arg = "false";
                    } elseif ($arg === NULL) {
                        $arg = "null";
                    } elseif (is_array($arg)) {
                        $arg = '[Array]';
                    } else {
                        $arg = strval($arg);
                    }
                    $call_signature .= $arg . ',';
                }
                $call_signature = trim($call_signature, ',') . ")</span>";
            }
        }
        return $call_signature;
    }

}