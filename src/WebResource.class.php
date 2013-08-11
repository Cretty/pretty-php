<?php

namespace net\shawn_huang\pretty;


/**
 * Web resource contains some function for getting params from
 * $_GET, $_POST, $_REQUEST. Also, a model contains custom data that can pass through
 * actions and views.
 */
class WebResource {

    private $data = array();
    private $readonly;
    private $view = null;
    private $webRequest;

     /**
     * Get parameters form $_GET.
     * @param string $key the key of parameter.
     * @param mixed $default returns $default if $key does not exists in $_GET
     */
    public function get($key, $default = null) {
        return Arrays::get($_GET, $key, $default);
    } 

    /**
     * Get parameters form $_REQUEST.
     * @param string $key the key of parameter.
     * @param mixed $default returns $default if $key does not exists in $_REQUEST
     */
    public function getRequest($key, $default = null) {
        return Arrays::get($_REQUEST, $key, $default);
    }

    /**
     * Get parameters form $_POST.
     * @param string $key the key of parameter.
     * @param mixed $default returns $default if $key does not exists in $_POST
     */
    public function getPost($key, $default = null) {
        return Arrays::get($_POST, $key, $default);
    }

    /**
     * Put arguments into $data that will be sent to view.
     * Notice that this method will no work when the action is readonly.
     * @param mixed $key if $key is an array, $this method will merge this array into $data, otherwise, $key will the array index name of $value.
     * @param mixed $value any thing you want to put into $data.
     */
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

    /**
     * Replace the stored data by given $data.
     * This method will replace the original data in the action.
     * @param object $data the data
     */
    public function setData($data) {
        if ($this->isReadonly()) {
            return;
        }
        $this->data = $data;
    }

    /**
     * Get the stored data of the action.
     * If $key is null, this method will return the whole $data that stored
     * @param string $key the key of argument you want to get
     * @return the data, null if the $key does not exits.
     */
    public function getData($key = null) {
        return $key !== null ? Arrays::get($this->data, $key) : $this->data;
    }

    /**
     * Check if this action is read only.
     * If it is true, $action->put, $action->setData will not work.
     * $action->setStatus will NOT be effected by read only status.
     * @return boolean true if this action is read only.
     */
    public function isReadonly() {
        return $this->readonly;
    }

    /**
     * Set the readonly status
     * @param boolean $readonly state
     */
    public function setReadonly($readonly) {
        $this->readonly = $readonly ? true : false;
    }

    /**
     * Merge data from given resource
     * @param WebResource source
     */
    public function copyFrom(WebResource $res) {
        $this->put($res->getData());
    }

    /**
     * Tell ViewResolver how to render this Action
     * @param string $viewtype view type alias
     * @param mixed $viewparam the params that passing to view
     */
    public function setView() {
        $this->view = func_get_args();
    }

    /**
     * Get current action view.
     * @return array returns the view.
     */
    public function getView() {
        return $this->view;
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