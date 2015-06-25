<?php

namespace net\shawn_huang\pretty;

/**
 * The main Configuration.
 * You can set config scope to store your staffs.
 * By default, the scope will be set to 'default'.
 * By switching scopes will affect ALL the pretty framework, becasue, pretty
 * uses Config::get to get each value.
 */
class Config {

    private static $store = array();
    private static $scope = 'default';

    /**
     * COPY values into current scope
     * @param array $array the things you want to put into config.
     */
    public static function initDefault($array) {
        self::$store[self::$scope] = $array;
    }

    /**
     * Get the value of given key from current scope.
     * @param string $key the key
     * @param mixed $default the return value if the key doesnt exists.
     * @return mixed the target value or $default if not found.
     */
    public static function get($key, $default = null) {
        if (isset(self::$store[self::$scope][$key])) {
            return self::$store[self::$scope][$key];
        }
        return $default;
    }

    /**
     * Put things into current scope that you want to store.
     * @param string $key the of of value
     * @param mixed $value the value you want to store.
     */
    public static function put($key, $value) {
        self::$store[self::$scope][$key] = $value;
    }

    /**
     * Check if given key exists in current scope.
     * @param string $key the key you want to check
     * @return bool true if exists
     */
    public static function exists($key) {
        return isset(self::$store[self::$scope][$key]);
    }


    /**
     * Unset the value of given key in current scope
     * @param string $key the target key that you want to unset.
     */
    public static function remove($key) {
        if (isset(self::$store[self::$scope][$key])) {
            unset(self::$store[self::$scope][$key]);
        }
    }

    /**
     * Put missing things into current scope that you want to store. That means if
     * the key has already been set, this method wont do anything.
     * @param string $key the of of value
     * @param mixed $value the value you want to store.
     */
    public static function putMissing($key, $value) {
        Arrays::putMissingInto(self::$store[self::$scope], $key, $value);
    }

    /**
     * Merge your array with current scope
     * @param array $arr the things you have.
     * @param bool $replace if replace is set to true,
     * this method will replace the exists values with the new ones
     */
    public static function mergeWith($arr, $replace = true) {
        self::$store[self::$scope] = $replace ? $arr + self::$store[self::$scope] :
            self::$store[self::$scope] + $arr;
    }
    /**
     * Push the value into config array
     * @param string $key the key of the array that you want to push into
     * @param mixed $value the value you want to push
     */
    public static function pushTo($key, $value) {
        self::$store[self::$scope][$key][] = $value;
    }

    /**
     * Pick a scope that you want to have.
     * @param string $scope the name of target scope
     * @return Arrays an Arrays object refer to the scope value.
     */
    public static function pick($scope) {
        if (!isset(self::$store[$scope])) {
            self::$store[$scope] = array();
        }
        return new Arrays(self::$store[$scope], true);
    }

    /**
     * Switch current scope to target scope
     * @param string $scope the target scope
     */
    public static function switchTo($scope) {
        self::$scope = $scope;
    }
}
