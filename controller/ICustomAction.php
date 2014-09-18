<?php

namespace Hexagon\controller;

interface ICustomAction {

    /**
     * Implements this interface makes Framework pass all request into _doAction() function
     * when Dispatcher did not find the method request matches.
     *
     * @return mixed
     */
    public function _doAction();

}