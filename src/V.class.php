<?php

namespace net\shawn_huang\pretty;

class V {

    private static $instance;

    private static function getInstance() {
        if (self::$instance == null) {
            self::$instance = new \net\shawn_huang\pretty\V();
        }
        return self::$instance;
    }

    public static function __callStatic($name, $args) {
        $v = self::getInstance();
        $method = "_$name";

        if (method_exists($v, $method)) {
            return call_user_func_array(array($v, $method), $args);
        }

        return $v->_callFacade($name, $args);
    }

    public function __call($name, $args) {
        $method = "_$name";
        if (!method_exists($this, $method)) {
            trigger_error("Call undefined method $name of V", E_USER_ERROR);
            return;
        }
    }

    private $cl;
    private $meta;
    private $runnable;
    private $data;
    private $view;
    public $facades;

    private function __construct() {
        $this->data = array();
    }

    public function _run($callback, $dependings = []) {
         $this->runnable = array($callback, $dependings);
        return $this;
    }

    public function _getRunnable() {
        return $this->runnable;
    }

    public function _on($method, $callback, $dependings = []) {
        if (strtolower($method) === strtolower($_SERVER['REQUEST_METHOD'])) {
            $this->_run($callback, $dependings);
        }
    }

    public function _bind($name = 'V') {
        if ($name) {
            eval("namespace {class $name extends \\net\\shawn_huang\\pretty\\V {} }");
        }
        return $this;
    }

    public function _setClassLoader($cl) {
        $this->cl = $cl;
        $this->meta = $this->cl->load('@%WebRequest');
        $this->facades = $this->cl->load('@#v.facades');
        return $this;
    }


    public function _meta($key, $default = null) {
        return $this->meta->getExtra($key, $default);
    }

    public function _g($key, $default = null) {
        return Arrays::valueFrom($_GET, $key, $default);
    }

    public function _p($key, $default = null) {
        return Arrays::valueFrom($_POST, $key, $default);
    }

    public function _r($key, $default = null) {
        return Arrays::valueFrom($_REQUEST, $key, $default);
    }

    public function _c($expression, $invoke = true, $warnings = true) {
        return $this->cl->load($expression, $invoke, $warnings);
    }

    public function _find($expression) {
        $this->cl->loadDefinition($expression);
    }

    public function _put($key, $v = null) {
        if (is_array($key)) {
            $this->data = $key + $this->data;
            return $this;
        }
        $this->data[$key] = $v;
        return $this;
    }

    public function _data($key = null, $default = null) {
        if ($key === false || $key === null) {
            return $this->data;
        } else {
            return Arrays::valueFrom($this->data, $key, $default);
        }
    }

    public function _view() {
        if (func_num_args()) {
            $this->view = func_get_args();
            return $this;
        } else {
            return $this->view;
        }
    }

    public function _forkAutoload() {
        $this->cl->forkAutoload();
        return $this;
    }

    public function _callFacade($name, $args) {
        if (!isset($this->facades[$name])) {
            trigger_error("Call undefined facade $name", E_USER_ERROR);
        }
        $facadeName = $this->facades[$name];
        $facade = $this->cl->load($name, true);
        if (!$facades) {
            trigger_error("$name is not a valid facade", E_USER_ERROR);
        }

        return call_user_func([$facade, $name], $args);
    }
}
