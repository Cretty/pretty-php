<?php

namespace net\shawn_huang\pretty;
class SiteConfig {

    private $nsPrefix;
    private $classPath;
    private $prettyPath;
    private $extra = array(
        'views.json' => '\\net\\shawn_huang\\pretty\\view\\JsonView',
        'views.json.jsonp' => null,
        'views.smarty' => '\\net\\shawn_huang\\pretty\\view\\SmartyView',
        'action.notfound' => '\\net\\shawn_huang\\pretty\\action\\NotFoundAction',
        'action.smartIndex' => 'Index',
        'path.maxdeep' => 10
    );

    public function __construct($classPath = null, $prettyPath = null) {
        $this->classPath = $classPath ?: realpath('./class');
        $this->prettyPath = $prettyPath ?: dirname(__FILE__);
        $this->extra['cache.path'] = $classPath . '/cache';
        $this->nsPrefix = null;
    }

    public function initPretty($version = 3) {
        require "{$this->prettyPath}/Pretty{$version}.inc.php";
        Pretty::$CONFIG = $this;
        $pretty = new Pretty();
        $pretty->begin();
    }

    public function setNsPrefix($prefix) {
        $this->nsPrefix = $prefix;
    }
    public function getNsPrefix() {
        return $this->nsPrefix;
    }

    public function getPrettyPath() {
        return $this->prettyPath;
    }

    public function getClassPath() {
        return $this->classPath;
    }

    public function set($key, $value) {
        $this->extra[$key] = $value;
    }

    public function get($key, $default = null) {
        return isset($this->extra[$key]) ? $this->extra[$key] : $default;
    }
}
