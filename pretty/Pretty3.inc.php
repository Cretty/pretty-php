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
class ClassLoader {

    private $loaded = array();

    public function singleton($name) {
        if (isset($this->loaded[$name])) {
            return $this->loaded[$name];
        }
        if (class_exists($name)) {
            return ($this->loaded[$name] = new $name());
        }
        $this->loadFile($name);
        if (!class_exists($name)) {
            return null;
        }
        return ($this->loaded[$name] = new $name());
    }

    public function loadFile($name) {
        $nsPrefix = Pretty::$CONFIG->getNsPrefix();
        $classPath = Pretty::$CONFIG->getClassPath();
        $prettyNs = '\\' . __NAMESPACE__;
        if ($nsPrefix && StringUtil::startWith($name, $nsPrefix)) {
            $path = str_replace(
                array($nsPrefix, '\\'),
                array('', '/'),
            $name);
        } elseif (StringUtil::startWith($name, $prettyNs)){
            $path = str_replace(array($prettyNs, '\\'),
                array('', '/'),
            $name);
            $classPath = Pretty::$CONFIG->getPrettyPath();
        } else {
            $path = str_replace('\\', '/', $name);
        }
        $file = $classPath . $path . (Pretty::$CONFIG->get('classExt') ?: '.class.php');
        if (is_file($file)) {
            include_once $file;
            Pretty::log("file:$file", true);
            return true;
        }
        Pretty::log("file:$file", false);
        return false;
    }

    public function invokeProperties($obj) {
        $ppts = get_object_vars($obj);
        foreach($ppts as $k => $v) {
            if (!is_string($v)) {
                continue;
            }
            if(!preg_match('/(\\\\([a-z0-9_])+)+/i', $v)) {
                continue;    
            }
            $p = $this->singleton($v);
            if(!is_object($p)) {
                continue;
            }
            $this->invokeProperties($p);
            $obj->$k = $p;
        }
    }
}
interface Filter {
    public function beforeAction(Action $action, FilterChain $chain);
    public function afterAction(Action $action, FilterChain $chain);
}
class FilterChain {

    const TYPE_BEFORE = 1;
    const TYPE_AFTER = 0;
    private $status = true;
    private $filterArray;
    private $type;
    private $action;

    public function __construct($array, $action, $type) {
        $this->filterArray = $array;
        $this->action = $action;
        $this->type = $type;
    }

    public function next() {
        return $this->status ? array_pop($this->filterArray) : null;
    }    

    public function terminate() {
        $this->status = false;
    }

    public function doFilter() {
        $filter = $this->next();
        $fun = $this->type ? 'beforeAction' : 'afterAction';
        if (!$filter) {
            return;
        }
        switch ($this->action->getStatus()) {
            case Action::STATUS_END:
            return;
        }
        $filter->$fun($this->action, $this);
        $this->doFilter();
    }
}
class Pretty {
    
    public static $CONFIG;

    private $filters = array();
    private static $log = array();
    private $classLoader;
    private $viewResolver;
    private static $instance;

    public function __construct() {
        self::$instance = $this;
    }

    public function begin() {
        $this->preload();    
        $this->buildChain();
    }

    private function preload() {
        $this->classLoader = new ClassLoader();
        $this->viewResolver = new ViewResolver();
        $this->viewResolver->classLoader = $this->classLoader;
        self::$CONFIG->get('views.welcome') or self::$CONFIG->set('views.welcome', '\\net\\shawn_huang\\pretty\\view\\WelcomeView');
    }

    private function buildChain() {
        $q = self::getArray($_SERVER, 'PATH_INFO') ?: self::getArray($_SERVER, 'ORIG_PATH_INFO');
        if ($q === null || $q === '/' || $q === '') {
            $q = '/index';
        } else  {
            $q = preg_replace('/(\\..*)$/', '', $q);
        }
        self::log('request.path', $q);
        $arr = explode('/', $q);
        if (count($arr) > self::$CONFIG->get('path.maxdeep')) {
            header('HTTP/1.1 405 request path too deep');
            echo ('request path too deep');
            die();
        }
        $action = null;
        $subRequest = array();
        while(($ends = array_pop($arr)) !== null) {
            if ($ends == '') {
                continue;
            }
            $className = $this->buildActionPath($arr, $ends);
            $action = $this->classLoader->singleton($className);
            if ($action == null && self::$CONFIG->get('action.smartIndex')) {
                self::log("class:$className", false);
                $className = $this->buildActionPath($arr, $ends, true);
                $action = $this->classLoader->singleton($className);
                if($action !== null) array_push($arr, $ends);
            }
            if ($action !== null) {
                $this->classLoader->invokeProperties($action);
                $this->loadFilters($arr);
                self::log("class:$className", true);
                $action->subRequest = $subRequest;
                break;
            }
            self::log("class:$className", false);
            array_unshift($subRequest, $ends);
        }
        if (!$action) {
            $this->fallback($q);
            return;
        }
        $beforeFilter = new FilterChain($this->filters, $action, FilterChain::TYPE_BEFORE);
        $beforeFilter->doFilter();
        $action->startAction();
        $afterFilter = new FilterChain($this->filters, $action, FilterChain::TYPE_AFTER);
        $afterFilter->doFilter();
        $action->getView() || $action->setView(null, 'json');
        $this->viewResolver->render($action);
    }

    private function loadFilters($arr) {
        while(($name = array_pop($arr)) !== null) {
            $filterName = self::$CONFIG->getNsPrefix() . '\\filter' . implode('\\', $arr) . '\\' . StringUtil::toPascalCase($name) . 'Filter';
            $filter = $this->classLoader->singleton($filterName);
            if ($filter) {
                $this->classLoader->invokeProperties($filter);
                $this->filters[] = $filter;
                pretty::log("class:$filterName", true);
                continue;
            }
            pretty::log("class:$filterName", false);
        }
    }

    private function buildActionPath($arr, $ends, $index = false) {
        $classPrefix = self::$CONFIG->getNsPrefix() . '\\action' . implode('\\', $arr) . '\\'; 
        if ($index) {
            return "{$classPrefix}{$ends}\\IndexAction";
        }
        return $classPrefix . StringUtil::toPascalCase($ends) . 'Action';
    }

    private function fallback($q) {
        if ($q == '/index') {
            include 'view/WelcomeView.class.php';
            $view = new view\WelcomeView();
            $view->render(null);
            return;
        }
        $action = $this->classLoader->singleton(Pretty::$CONFIG->get('action.notfound'));
        $action->startAction();
        $this->viewResolver->render($action);
    }


    public static function getArray($array, $key, $default = null) {
        return isset($array[$key]) ? $array[$key] : $default;
    }

    public static function includePrettyClass($className) {
        if (!class_exists($className)) {
            self::$instance->classLoader->loadFile($className) or die("$className not found in Pretty library");
        }
    }

    public static function log($key, $value, $level = 'debug') {
        self::$log[$key] = $value;
    }

    public static function getLog() {
        return self::$log;
    }
}
class SiteConfig {

    private $nsPrefix;
    private $classPath;
    private $prettyPath;
    private $extra = array(
        'views.json' => '\\net\\shawn_huang\\pretty\\view\\JsonView',
        'views.smarty' => '\\net\\shawn_huang\\pretty\\view\\SmartyView',
        'views.debug' => '\\net\\shawn_huang\\pretty\\view\\DebugView',
        'views.json.jsonp' => null,
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
class StringUtil {

    public static function endsWith($str, $needle) {
        $length = strlen($needle);
        if ($length == 0) {
            return true;
        }
        return (substr($str, -$length) === $needle);
    }

    public static function startWith($str, $needle) {
        $length = strlen($needle);
        return (substr($str, 0, $length) === $needle);
    }

    public static function toPascalCase($ori) {
        return ucfirst(self::toCamelCase($ori));
    }

    public static function toCamelCase($ori) {
        $func = create_function('$c', 'return strtoupper($c[1]);');
        return preg_replace_callback('/_([a-z])/', $func, $ori);
    }

    public static function getCamelTail($str) {
        $exp = '/^(.+)([A-Z][a-z0-9]+)$/';
        preg_match($exp, $str, $result);
        return count($result) == 0 ? array($str, '') : array($result[1], $result[2]);    
    }

}
interface View {
    public function render(Action $action);
}
class ViewResolver {

    public $classLoader;

    public function render(Action $action) {
        list($name, $clz) = $action->getView();
        $view = $this->loadView($clz);
        if($view == null) {
            $view = $this->classLoader->singleton(Pretty::$CONFIG->get('views.debug'));
        }
        $view->render($action);
    }

    private function loadView($viewType) {
        if ($viewClz = Pretty::$CONFIG->get("views.$viewType")) {
            $ret = $this->classLoader->singleton($viewClz);
            if ($ret) {
                Pretty::log("class:$viewClz", true);
                return $ret;
            }
            Pretty::log("class:$viewClz", false);
        }
        $viewClz = Pretty::$CONFIG->getNsPrefix() . "\\view\\" . StringUtil::toPascalCase($viewType) . 'View';
        $ret = $this->classLoader->singleton($viewClz);
        if ($ret) {
            Pretty::log("class:$viewClz", true);
        }
        Pretty::log("class:$viewClz", false);
        return $ret;
    }
}
