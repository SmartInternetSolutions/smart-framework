<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

class SF_Model_Observer
{
    protected $_events = array();

    public function dispatchEvent($eventname, array $data = array())
    {
        if (!isset($this->_events[$eventname])) {
            $this->_events[$eventname] = array();
        }

        foreach ($this->_events[$eventname] as $callback) {
            call_user_func_array($callback, $data);
        }

        return $this;
    }

    public function observeEvent($eventname, array $callback)
    {
        if (!isset($this->_events[$eventname])) {
            $this->_events[$eventname] = array();
        }

        $this->_events[$eventname][] = $callback;

        return $this;
    }
}
