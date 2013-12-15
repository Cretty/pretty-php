<?php

namespace net\shawn_huang\pretty;

/**
 * The array helper
 */
class Arrays {

    /**
     * Get the value by given key from target array
     * @param array $arr the target array
     * @param string $key the given key
     * @param mixed $default the return value if given $key is not found in the array
     * @return the found value or the $default
     */
    public static function valueFrom($arr, $key, $default = null) {
        return isset($arr[$key]) ? $arr[$key] : $default;
    }

    /**
     * Put the value by given key into target array only if the key 
     * does NOT exists in the target array.
     * @param array $arr the reference of target array
     * @param string $key the key you set
     * @param mixed $value the value you want to set
     */
    public static function putMissingInto(&$arr, $key, $value) {
        if (isset($arr[$key])) {
            return;
        }
        $arr[$key] = $value;
    }

    /**
     * Push the value into target array
     * @param array $arr the reference of target array
     * @param string $key the key of the array that you want to push into
     * @param mixed $value the value you want to push
     */
    public static function pushInto(&$arr, $key, $value) {
        $arr[$key][] = $value;
    }

    /**
     * Unset the value of given key from target array
     * @param array $arr the reference of target array
     * @param string $key the target key that you want to unset.
     */
    public static function removeFrom(&$arr, $key) {
        if (isset($arr[$key])) {
            unset($arr[$key]);
        }
    }

    private $store;
    private $isReference;

    /**
     * Consturct an instance of Wrapper
     * @param array $resource the source array
     * @param bool $isReference if this param is set to true, the wrapper will
     * link to source array. By default the wrapper will copy the resouce values
     * and store them.
     */
    public function __construct(&$resource, $isReference = false) {
        $this->isReference = $isReference;
        if ($isReference) {
            $this->store = &$resource;
        } else {
            $this->store = $resource;
        }
    }

    /**
     * Get the value by given key from wrapped array
     * @param string $key the given key
     * @param mixed $def the return value if given $key is not found in the array
     * @return the found value or the $def
     */
    public function get($key, $def = null) {
        return self::valueFrom($this->store, $key, $def);
    }

    /**
     * Put things into wrapped value that you want to store.
     * @param string $key the of of value
     * @param mixed $value the value you want to store.
     */
    public function put($key, $value) {
        $this->store[$key] = $value;
        return $this;
    }
    /**
     * Merge your array with wrapped array
     * @param array $data the things you have.
     * @param bool $replace if replace is set to true,
     * this method will replace the exists values with the new ones
     */
    public function mergeWith($data, $replace = true) {
        if ($replace) {
            $tmp = array_replace_recursive($this->store, $data);
        } else {
            $tmp = array_replace_recursive($data, $this->store);
        }
        if($this->isReference) {
            foreach ($tmp as $key => $value) {
                $this->store[$key] = $value;
            }
        } else {
            $this->store = $tmp;
        }
    }

    /**
     * Push the value into target array
     * @param string $key the key of the array that you want to push into
     * @param mixed $value the value you want to push
     */
    public function pushTo($key, $value) {
        $this->store[$key][] = $value;
    }

    /**
     * Unset the value of given key in wrapped value
     * @param string $key the target key that you want to unset.
     */
    public function remove($key) {
        if (isset($this->store[$key])) {
            unset($this->store[$key]);
        }
    }


    /**
     * Do some cleanups
     */
    public function __destruct() {
        if (!$this->isReference) {
            unset($this->store);
        }
    }
}