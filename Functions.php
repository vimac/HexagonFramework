<?php
/**
 * Created by IntelliJ IDEA.
 * User: mac
 * Date: 6/14/14
 * Time: 10:27
 */

namespace Hexagon;

function formOpen($action, $method = 'POST', $attrs = []) {
    $config = Context::$appConfig;

    $text = '<form';
    $attrs['action'] = $action;
    $attrs['method'] = $method;
    foreach ($attrs as $name => $attr) {
        $text .= ' ' . $name . '="' . $attr . '"';
    }
    $text .= '>';
    if ($config->csrfProtection && strcasecmp($method, 'POST') === 0) {
        $text .= '<div style="display:none"><input type="hidden" name="'
            . $config->csrfTokenName . '" value="' . Security::getCSRFTokenHash()
            . '" /></div>';
    }
    return $text;
}

function formOpenMultipart($action, $method = 'POST', $attrs = []) {
    $attrs['enctype'] = 'multipart/form-data';
    return formOpen($action, $method, $attrs);
}

function formClose() {
    return '</form>';
}

