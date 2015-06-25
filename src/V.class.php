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
        if (!method_exists($v, $method)) {
            trigger_error("Call undefined static method $name of V", E_USER_ERROR);
            return;
        }
        return call_user_func_array(array($v, $method), $args);
    }

    public function __call($name, $args) {
        $method = "_$name";
        if (!method_exists($this, $method)) {
            trigger_error("Call undefined method $name of V", E_USER_ERROR);
            return;
        }
        return call_user_func_array(array($this, $method), $args);
    }

    private $cl;
    private $meta;
    private $runnable;

    private function __construct() {
        $this->cl = new \net\shawn_huang\pretty\ClassLoader();
    }

    public function _run($callback) {
        $this->runnable = $callback;
    }

    public function _getRunnable() {
        return $this->runnable;
    }

    public function _bind($name = 'V') {
        if ($name) {
            eval("namespace {class $name extends \\net\\shawn_huang\\pretty\\V {} }");
        }
    }

    public function _setClassLoader($cl) {
        $this->cl = $cl;
        $this->meta = $this->cl->load('@%WebRequest');
        return $this;
    }


    public function _meta($key, $default = null) {
        return $this->meta->getExtra($key, $default);
    }

    public function _g($key, $default = null) {
        return Arrays::valueFrom($_GET, $key, $default);
    }

    public function _p($key, $default = null) {
        return Arrays::valueFrom($_GET, $key, $default);
    }

    public function _r($key, $default = null) {
        return Arrays::valueFrom($_GET, $key, $default);
    }

    public function _c($expression, $invoke = true, $warnings = true) {
        return $this->cl->load($expression, $invoke, $warnings);
    }
}
