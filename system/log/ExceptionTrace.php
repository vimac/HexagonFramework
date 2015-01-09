<?php
/**
 * Created by IntelliJ IDEA.
 * User: mac
 * Date: 1/9/15
 * Time: 22:17
 */

namespace Hexagon\system\log;


use Exception;

trait ExceptionTrace {

    /**
     * traceException
     *
     * @param Exception $e
     * @return array of strings, one entry per trace line
     */
    protected function _traceException(Exception $e) {
        $rtn = "";
        $count = 0;
        foreach ($e->getTrace() as $frame) {
            $args = "";
            if (isset($frame['args'])) {
                $args = array();
                foreach ($frame['args'] as $arg) {
                    if (is_string($arg)) {
                        $args[] = "'" . $arg . "'";
                    } elseif (is_array($arg)) {
                        $args[] = "Array";
                    } elseif (is_null($arg)) {
                        $args[] = 'NULL';
                    } elseif (is_bool($arg)) {
                        $args[] = ($arg) ? "TRUE" : "FALSE";
                    } elseif (is_object($arg)) {
                        $args[] = 'Object(' . get_class($arg) . ')';
                    } elseif (is_resource($arg)) {
                        $args[] = get_resource_type($arg);
                    } else {
                        $args[] = $arg;
                    }
                }
                $args = join(", ", $args);
            }
            $rtn .= sprintf("#%s %s%s: %s%s%s(%s)\n",
                $count,
                !empty($frame['file']) ? $frame['file'] : '[internal function]',
                !empty($frame['file']) ? '(' . $frame['line'] . ')' : '',
                isset($frame['class']) ? $frame['class'] : '[not in class]',
                isset($frame['type']) ? $frame['type'] : '',
                $frame['function'],
                $args);
            $count++;
        }
        return $rtn;
    }

}