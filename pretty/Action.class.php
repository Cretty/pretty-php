<?php

namespace net\shawn_huang\pretty;

abstract class Action {

    const STATUS_NORMAL = 0;
    const STATUS_READONLY = 1;
    const STATUS_END = -1;
    const STATUS_SKIP = -2;

    private $view = null;
    private $data = array();
    private $actionStatus = 0;
    public $subRequest;

    public final function startAction() {
        switch($this->actionStatus) {
            case self::STATUS_NORMAL:
            case self::STATUS_READONLY:
                $this->run();
                break;
        }
    }

    protected abstract function run();

    public function setView($viewname, $type = 'smarty') {
        $this->view = array($viewname, $type);
    }

    public function getView() {
        return $this->view;
    }

    public function get($key, $default = null) {
        return Pretty::getArray($_GET, $key, $default);
    } 

    public function getRequest($key, $default = null) {
        return Pretty::getArray($_REQUEST, $key, $default);
    }

    public function getPost($key, $default = null) {
        return Pretty::getArray($_POST, $key, $default);
    }

    public function put($key, $value = null) {
        if ($this->isReadonly()) {
            return;
        }
        if (is_array($key)) {
            $this->data = array_merge($this->data, $key);
            return;
        }
        $this->data[$key] = $value;
    }

    public function setResult($result, $key = 'result') {
        $this->put($key, $result);
    }

    public function setError($err) {
        $this->put('error', $err);
    }

    public function isReadonly() {
        switch($this->actionStatus) {
            case self::STATUS_READONLY:
                return true;
        }
        return false;
    }

    public function setStatus($status) {
        $this->actionStatus = $status;
    }

    public function getStatus() {
        return $this->actionStatus;
    }

    public function setData($data) {
        if ($this->isReadonly()) {
            return;
        }
        $this->data = $data;
    }

    public function getData($key = null) {
        return $key !== null ? Pretty::getArray($this->data, $key) : $this->data;
    }
}