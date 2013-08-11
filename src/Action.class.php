<?php

namespace net\shawn_huang\pretty;
/**
 * An Action is an url handler, process request by an entrance named "run".
 * Other than a handler, the action also play as model which can passing data
 * between service.
 */
abstract class Action extends WebResource {

    /**
     * Normal status.
     */
    const STATUS_NORMAL = 0;
    /**
     * Read only status.
     * The "run" method will be called, but the Action's data can't not be modify.
     */
    const STATUS_READONLY = 1;
    /**
     * Terminate status.
     * The "run" method will NOT be called, and action will be mark as read only.
     */
    const STATUS_END = -1;
    /**
     * Ignored status.
     * The "run" method will NOT be called.
     */
    const STATUS_SKIP = -2;

    private $data = array();
    private $actionStatus = 0;
    private $forward = null;
    private $webRequest;

    /**
     * Start action. This function will be call by Router.
     * Check the action status and decides weather or not call the "run" method.
     */
    public final function startAction() {
        switch($this->actionStatus) {
            case self::STATUS_NORMAL:
            case self::STATUS_READONLY:
                $this->run();
                break;
        }
    }

    /**
     * The entrance of process.
     * Notice that if the action status belows 0(which means action status has been set to STATUS_END or STATUS SKIP), this function will no be called.
     */
    protected abstract function run();


    /**
     * Set the proccess result.
     * This operation equals $action->put($key, $result);
     * @param boolean $result true when anything is fine.
     * @param string $key the key of result, default is 'result'
     */
    public function setResult($result, $key = 'result') {
        $this->put($key, $result);
    }

    /**
     * Set the error messages.
     * This operation equals $action->put('error', $result);
     * @param mixed $error error messages.
     */
    public function setError($err) {
        $this->put('error', $err);
    }
    /**
     * Check if this action is read only.
     * If it is true, $action->put, $action->setData will not work.
     * $action->setStatus will NOT be effected by read only status.
     * @return boolean true if this action is read only.
     */
    public function isReadonly() {
        switch($this->actionStatus) {
            case self::STATUS_READONLY:
                return true;
        }
        return false;
    }

    /**
     * Set the status of action.
     * This method will NOT be effected by read only status.
     * @param integer $status the status.
     */
    public function setStatus($status) {
        $this->actionStatus = $status;
    }

    /**
     * Get the status of action.
     * @return integer the status.
     */
    public function getStatus() {
        return $this->actionStatus;
    }

    /**
     * Set the forward action
     * @param string $exp the target action expression
     */
    public function setForward($exp) {
        $this->forward = $exp;
    }

    /**
     * Get the forward action name
     * @return string target action
     */
    public function getForward() {
        return $this->forward;
    }

    /**
     * Set the web request
     * @param WebRequest $request the web request
     */
    public function setWebRequest(WebRequest $request) {
        $this->webRequest = $request;
    }

    /**
     * Get the web request
     * @return WebRequest the web request
     */
    public function getWebRequest() {
        return $this->webRequest;
    }

}