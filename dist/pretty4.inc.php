<?php

namespace net\shawn_huang\pretty;
abstract class Action extends WebResource {
    const STATUS_NORMAL = 0;
    const STATUS_READONLY = 1;
    const STATUS_END = -1;
    const STATUS_SKIP = -2;
    private $data = array();
    private $actionStatus = 0;
    private $forward = null;
    private $webRequest;
    public $classloader = '@%ClassLoader';
    public final function startAction() {
        switch($this->actionStatus) {
            case self::STATUS_NORMAL:
            case self::STATUS_READONLY:
                $this->run();
                break;
        }
    }
    protected abstract function run();
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
    public function setForward($exp) {
        $this->forward = $exp;
    }
    public function getForward() {
        return $this->forward;
    }
    public function setWebRequest(WebRequest $request) {
        $this->webRequest = $request;
    }
    public function getWebRequest() {
        return $this->webRequest;
    }
}
class ActionV extends Action {
    public static function loadV($name) {
        $av = new ActionV($name, false);
        $av->classloader = V::c($av->classloader);
        $av->init();
        return $av;
    }
    private $runnable;
    private $exp;
    private $isV;
    public function __construct($exp, $init = true) {
        $this->exp = $exp;
        if ($init) {
            $this->init();
        }
    }
    protected function run() {
        $func = $this->runnable;
        if (is_callable($func)) {
            $func($this);
        } else {
            throw new Exception('No runnable found');
        }
    }
    private function init() {
        $clz = $this->classloader->explainClasses($this->exp);
        if ($clz['isClass']) {
            $path = $clz['file'] . '.v.php';
            if (is_file($path)) {
                require $path;
                $this->runnable = V::getRunnable();
                return $this->isV = true;
            }
        } else {
            throw new Exception("Forward Action [{$this->exp}] not found", Exception::CODE_PRETTY_CLASS_NOTFOUND);
        }
        $this->exp = $clz;
        return $this->isV = false;
    }
    public function isActionV() {
        return $this->isV;
    }
    public function getExp() {
        return $this->exp;
    }
}
class Arrays {
    public static function valueFrom($arr, $key, $default = null) {
        return isset($arr[$key]) ? $arr[$key] : $default;
    }
    public static function putMissingInto(&$arr, $key, $value) {
        if (isset($arr[$key])) {
            return;
        }
        $arr[$key] = $value;
    }
    public static function pushInto(&$arr, $key, $value) {
        $arr[$key][] = $value;
    }
    public static function removeFrom(&$arr, $key) {
        if (isset($arr[$key])) {
            unset($arr[$key]);
        }
    }
    private $store;
    private $isReference;
    public function __construct(&$resource, $isReference = false) {
        $this->isReference = $isReference;
        if ($isReference) {
            $this->store = &$resource;
        } else {
            $this->store = $resource;
        }
    }
    public function get($key, $def = null) {
        return self::valueFrom($this->store, $key, $def);
    }
    public function put($key, $value) {
        $this->store[$key] = $value;
        return $this;
    }
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
    public function pushTo($key, $value) {
        $this->store[$key][] = $value;
    }
    public function remove($key) {
        if (isset($this->store[$key])) {
            unset($this->store[$key]);
        }
    }
    public function __destruct() {
        if (!$this->isReference) {
            unset($this->store);
        }
    }
}
const REAL_CLASS_PATTERN = '/^\\\\([a-z0-9\\\\_]+)/i';
const CLASS_PATTERN = '/^@|&[%\*#]?\+?([a-z0-9_<>\.])+/i';
const CLASS_TYPE_ABSOLUTE = 1;
const CLASS_TYPE_PRETTY = 2;
const CLASS_TYPE_DOMAIN = 3;
class ClassLoader {
    private $pool = array();
    private $ns;
    private $pns;
    private $prettyPath;
    public function __construct() {
        $this->pool['\\' . __CLASS__] = $this;
        $this->ns = Config::get(Consts::CONF_CLASS_NS, '\\');
        $this->ns = $this->ns == '\\' ? '\\' : $this->ns . '\\';
        $this->pns = '\\' . __NAMESPACE__;
        $this->prettyPath = __DIR__;
    }
    public function load($clz, $initProperties = false, $warning = true) {
        if (is_string($clz) && isset($this->pool[$clz])) {
            return $this->pool[$clz];
        }
        if (is_string($clz)) {
            $clz = $this->explainClasses($clz);
        }
        if (!$clz['isNew'] && !$clz['args'] && isset($this->pool[$clz['name']])) {
            return $this->pool[$clz['name']];
        }
        if (!$clz['isClass']) {
            return $clz['value'];
        }
        if (!class_exists($clz['name'], 0) && !$this->loadDefinition($clz)) {
            if ($warning) throw new Exception("Class[{$clz['origin']}, {$clz['file']}.class.php|.interface.php|.php] not found.", Exception::CODE_PRETTY_CLASS_NOTFOUND);
            return null;
        }
        $name = $clz['name'];
        if ($clz['args']) {
            $class = new \ReflectionClass($name);
            $instance = $class->newInstanceArgs($clz['args']);
        } else {
            $instance = new $name();
        }
        if ($initProperties) {
            $this->loadProperties($instance);
        }
        if (!$clz['isNew'] && !$clz['args']) {
            $this->pool[$clz['name']] = $instance;
        }
        return $instance;
    }
    public function loadProperties($obj) {
        foreach(get_object_vars($obj) as $key => $value) {
            if (!is_string($value) && !is_array($value)) {
                continue;
            }
            if (is_string($value)) {
                $value = $this->explainClasses($value);
            }
            if (isset($value['isValue']) && $value['isValue']) {
                $obj->$key = $value['value'];
                continue;
            }
            if (!isset($value['isClass']) || !$value['isClass']) {
                continue;
            }
            $ppt = $this->load($value, $value['loadChildren'], false);
            if ($ppt) {
                $obj->$key = $ppt;
            }
        }
    }
    public function loadDefinition($clz, &$detail = null) {
        if (is_string($clz)) {
            $clz = $this->explainClasses($clz);
        }
        $detail = $clz;
        if (!$clz['isClass']) {
            throw new Exception("Class definition of [{$clz['origin']} => {$clz['name']}] not found.", Exception::CODE_PRETTY_CLASS_NOTFOUND);
        }
        if (class_exists($clz['name'], false) && $clz['preloads'] == null) {
            return true;
        }
        switch($clz['type']) {
            case CLASS_TYPE_ABSOLUTE:
                $path = Config::get(Consts::CONF_CLASS_EXTRA_PATH, Config::get(Consts::CONF_CLASS_PATH))
                    . str_replace('\\', '/', $clz['name']);
                break;
            case CLASS_TYPE_DOMAIN:
                $subPath = str_replace(
                    array($this->ns ?: '', ''),
                    array('\\', '/'),
                    $clz['name']
                );
                $path = Config::get(Consts::CONF_CLASS_PATH) . $subPath;
                break;
            case CLASS_TYPE_PRETTY:
                $subPath = str_replace(
                    array($this->pns, ''),
                    array('\\', '/'),
                    $clz['name']
                );
                $path = __DIR__ . $subPath;
                break;
        }
        $loads = $clz['preloads'];
        $loads[] = $clz['file'];
        foreach ($loads as $key => $value) {
            if (is_file("$value.class.php")) {
                require_once "$value.class.php";
                continue;
            }
            if (is_file("$value.interface.php")) {
                require_once "$value.interface.php";
                continue;
            }
            if (is_file("$value.php")) {
                require_once "$value.php";
                continue;
            }
            return false;
        }
        return true;
    }
    public function realClassName($class) {
    }
    public function fockAutoload() {
        spl_autoload_register(array($this, 'loadDefinition'));
    }
    public function explainClass($desc, $aliasDeep = 0) {
        if (is_array($desc)) {
            return $this->classTemplate('Array', $desc);
        }
        if ($desc{0} == '\\' && preg_match(REAL_CLASS_PATTERN, $desc)) {
            $type = CLASS_TYPE_ABSOLUTE;
            if (strpos($desc, $this->ns) === 0) {
                $type = CLASS_TYPE_DOMAIN;
                $file = $this->parseDomainFile($desc);
            }
            if (strpos($desc, $this->pns)) {
                $type = $this->pns;
                $file = $this->parsePrettyFile($desc);
            }
            if ($type == CLASS_TYPE_ABSOLUTE) {
                $file = $this->parseAbsoluteFile($desc);
            }
            return $this->classTemplate($desc, array(
                'isClass' => true,
                'name' => $desc,
                'type' => $type,
                'file' => $file
            ));
        }
        if ($desc{0} != '@' && $desc{0} != '&') {
            return $this->classTemplate($desc, array('errors' => 'Not starts with @ or &'));
        }
        if (!preg_match(CLASS_PATTERN, $desc)) {
            return $this->classTemplate($desc, array('errors' => 'not match pattern:' . CLASS_PATTERN));
        }
        $prefix = $desc{0};
        $alias = Config::get(Consts::CONF_CLASS_ALIAS);
        $isNew = false;
        switch($desc{1}) {
            case '%':
                if ($desc{2} == '+') {
                    $isNew = true;
                }
                $name = str_replace(
                    array($prefix, '%', '.', '+'),
                    array($this->pns, '\\', '\\', ''),
                    $desc
                );
                $type = CLASS_TYPE_PRETTY;
                $file = $this->parsePrettyFile($name);
                break;
            case '.':
                if ($desc{2} == '+') {
                    $isNew = true;
                }
                $name = str_replace(
                    array("$prefix.", '.', '+'),
                    array($this->ns, '\\', ''),
                    $desc
                );
                $type = CLASS_TYPE_DOMAIN;
                $file = $this->parseDomainFile($name);
                break;
            case '#':
                $key = substr($desc, 2);
                return $this->classTemplate(
                    $desc,
                    array(
                        'isValue' => true,
                        'value' => Config::exists($key) ? Config::get($key) : $desc
                    )
                );
            case '*':
                $aliasLimit = Config::get(Consts::CONF_CLASS_ALIAS_LIMIT);
                if ($aliasDeep >= $aliasLimit) {
                    throw new Exception("Alias too deep, Limit " . Config::get(Consts::CONF_CLASS_ALIAS_LIMIT),
                        Exception::CODE_PRETTY_CLASS_INI_FAILED);
                }
                $target = substr($desc, 2);
                if (!isset($alias[$target])) {
                    return $this->classTemplate($desc);
                }
                return $this->explainClass($alias[$target], $aliasDeep + 1);
            default:
                $name = str_replace(
                    array($prefix, '+', '.'),
                    array('\\', '', '\\'),
                    $desc
                );
                $clz = $this->explainClass($name);
                $clz['isNew'] = $desc{1} == '+' ? true : false;
                $clz['loadChildren'] = $prefix == '@' ? true : false;
                return $clz;
        }
        return $this->classTemplate($desc, array(
            'isClass' => true,
            'type' => $type,
            'isNew' => $isNew,
            'name' => $name,
            'file' => $file,
            'loadChildren' => $prefix == '@' ? true : false
        ));
    }
    public function explainClasses($desc, $aliasDeep = 0) {
        if (is_array($desc)) {
            return $this->classTemplate($desc, $desc);
        }
        if (!isset($desc{1})) {
            return $this->classTemplate($desc);
        }
        $arr = explode('>', $desc);
        $count = count($arr);
        if ($count > 1) {
            for ($i = 0; $i < $count - 1; $i++) {
                $preload = $this->explainClass($arr[$i]);
                if (!$preload['isClass']) {
                    syslog(LOG_DEBUG, "Explain $desc failed, {$arr[$i]} isnt a class or interface");
                    return $this->classTemplate(array('isClass' => false)); # If not match
                }
                $preloads[] = $preload['file'];
            }
            $cname = $arr[$count - 1];
            if (strpos($cname, '<') !== false) {
                list($cname, $args) = explode('<',  $cname);
                $args = explode(',', $args);
            }
            $clz = $this->explainClass($arr[$count - 1]);
            if ($clz['isClass']) {
                $clz['preloads'] = $preloads;
                $clz['origin'] = $desc;
                $clz['args'] = isset($args) ? $args : null;
            }
        } else {
            if (strpos($desc, '<') !== false) {
                list($cname, $args) = explode('<',  $desc);
                $args = explode(',', $args);
            } else {
                $cname = $desc;
            }
            $clz = $this->explainClass($cname);
            $clz['origin'] = $desc;
            $clz['args'] = isset($args) ? $args : null;
        }
        return $clz;
    }
    public function classTemplate($origin, $preset = null) {
        $ret = array(
            'isClass' => false,
            'isValue' => false,
            'name' => null,
            'type' => null,
            'isNew' => false,
            'file' => null,
            'preloads' => null,
            'args' => null,
            'value' => null,
            'errors' => null,
            'loadChildren' => true,
            'origin' => $origin
        );
        if ($preset) {
            return $preset + $ret;
        }
        return $ret;
    }
    private function parsePrettyFile($name) {
        return str_replace(
            array($this->pns, '\\'), array($this->prettyPath, '/'), $name
        );
    }
    private function parseDomainFile($name) {
        return Config::get(Consts::CONF_CLASS_PATH) . str_replace(
            array($this->ns, '\\'),
            array('/', '/'),
            $name
        );
    }
    private function parseAbsoluteFile($name) {
        return (Config::get(Consts::CONF_CLASS_LIB) ?: Config::get(Consts::CONF_CLASS_PATH)) . str_replace('\\', '/', $name);
    }
}
class Config {
    private static $store = array();
    private static $scope = 'default';
    public static function initDefault($array) {
        self::$store[self::$scope] = $array;
    }
    public static function get($key, $default = null) {
        if (isset(self::$store[self::$scope][$key])) {
            return self::$store[self::$scope][$key];
        }
        return $default;
    }
    public static function put($key, $value) {
        self::$store[self::$scope][$key] = $value;
    }
    public static function exists($key) {
        return isset(self::$store[self::$scope][$key]);
    }
    public static function remove($key) {
        if (isset(self::$store[self::$scope][$key])) {
            unset(self::$store[self::$scope][$key]);
        }
    }
    public static function putMissing($key, $value) {
        Arrays::putMissingInto(self::$store[self::$scope], $key, $value);
    }
    public static function mergeWith($arr, $replace = true) {
        self::$store[self::$scope] = $replace ? $arr + self::$store[self::$scope] :
            self::$store[self::$scope] + $arr;
    }
    public static function pushTo($key, $value) {
        self::$store[self::$scope][$key][] = $value;
    }
    public static function pick($scope) {
        if (!isset(self::$store[$scope])) {
            self::$store[$scope] = array();
        }
        return new Arrays(self::$store[$scope], true);
    }
    public static function switchTo($scope) {
        self::$scope = $scope;
    }
}
class Consts {
    const CONF_CLASS_NS = 'class.namespace';
    const CONF_CLASS_PATH = 'class.path';
    const CONF_CLASS_EXTRA_PATH = 'class.extraPath';
    const CONF_CLASS_ALIAS = 'class.alias';
    const CONF_CLASS_ALIAS_LIMIT = 'class.aliasLimit';
    const CONF_CLASS_LIB = 'class.lib';
    const CONF_ROUTER_MAPPINGS = 'router.mappings';
    const CONF_ROUTER_FILTER_LIMIT = 'router.filterLimits';
    const CONF_ROUTER_ACTION_NS = 'class.actionNamespace';
    const CONF_ROUTER_FILTER_NS = 'class.filterNamespace';
    const CONF_ROUTER_FALLBACK_LIMIT = 'class.maxFallbacks';
    const CONF_ROUTER_FORWARD_LIMIT = 'action.forwardLimits';
    const CONF_VIEW_MAPPINGS = 'view.mappings';
    const CONF_VIEW_DEFAULT = 'view.defaultView';
    const CONF_VIEW_DEFAULT_TYPE = 'view.defaultViewType';
    const CONF_GUARD_MAPPINGS = 'guardians.mappings';
    const CONF_GUARD_REWIND_LIMIT = 'guardians.maxRewinds';
}
class Exception extends \Exception {
    const CODE_HTTP_INTERNAL_ERROR              = 500;
    const CODE_HTTP_OK                          = 200;
    const CODE_HTTP_MODE_PERMANENTLY            = 301;
    const CODE_HTTP_NOT_MODIFIED                = 304;
    const CODE_HTTP_NOT_TEMPORARY               = 307;
    const CODE_HTTP_BAD_REQUEST                 = 400;
    const CODE_HTTP_UNAUTHORIZED                = 401;
    const CODE_HTTP_NOT_FOUND                   = 404;
    const CODE_HTTP_METHOD_NOT_ALLOWED          = 405;
    const CODE_HTTP_SERVICE_UNAVAILABLE         = 503;
    const CODE_PRETTY_UNKNOWN                   = 0xF000;
    const CODE_PRETTY_CLASS_NOTFOUND            = 0xF001;
    const CODE_PRETTY_CLASS_INIT_FAILED         = 0xF002;
    const CODE_PRETTY_FILE_NOTFOUND             = 0xF003;
    const CODE_PRETTY_ACTION_NOTFOUND           = 0xF004;
    const CODE_PRETTY_MISSING_CORE_CLASSES      = 0xF005;
    const CODE_PRETTY_VIEW_NOTFOUND             = 0XF006;
    const CODE_PRETTY_ACTION_ERROR              = 0xF007;
    const CODE_PRETTY_HTTP_STATUS               = 0xFFF1;
    private $httpCode = self::CODE_HTTP_OK;
    private $webResource = null;
    private $classLoader = null;
    public static function createHttpStatus($messageBody = 'Internal Error', $httpCode = self::CODE_HTTP_INTERNAL_ERROR, $resource = null, $previous = null){
        $exp = new Exception($messageBody, self::CODE_PRETTY_HTTP_STATUS, $previous);
        $exp->setHttpCode($httpCode);
        $exp->setWebResource($resource);
        return $exp;
    }
    public function setHttpCode($code) {
        $this->httpCode = $code;
    }
    public function getHttpCode() {
        return $this->httpCode;
    }
    public function setWebResource($webResource) {
        $this->webResource = $webResource;
    }
    public function getWebResource() {
        return $this->webResource;
    }
    public function setClassLoader($classLoader) {
        $this->classLoader = $classLoader;
    }
    public function getClassLoader() {
        return $this->classLoader;
    }
}
class ExceptionHandler {
    private $classloader;
    public function handleException($exp) {
        if (is_a($exp, '\net\shawn_huang\pretty\Exception')) {
            $this->handlePrettyExcepion($exp);
        } else {
            $this->handleOtherException($exp);
        }
    }
    public function setClassLoader($loader) {
        $this->classloader = $loader;
    }
    public function handlePrettyExcepion($exp) {
        switch($exp->getCode()) {
            case Exception::CODE_PRETTY_HTTP_STATUS:
                $statusCode = $exp->getHttpCode();
                @header("http/1.1 {$statusCode}");
                $this->resolveView($exp);
                break;
            default:
                echo $exp->__toString();
                break;
        }
    }
    public function handleOtherException($exp) {
        header('http/1.1 500 Internal Error');
        echo $exp->__toString();
    }
    protected function resolveView($exp) {
        $res = $exp->getWebResource();
        if ($res && $res->getView()) {
            try {
                Framework::instance()->display($res);
            } catch (Exception $e) {
                echo $e->__toString();
                echo $exp->__toString();
                echo "\r\n";
            }
        } else {
            echo $exp->getMessage();
            echo "\r\n";
        }
    }
}
interface Filter {
    public function before(Action $action);
    public function after(Action $action);
}
class Framework {
    protected static $defaults = array(
        Consts::CONF_CLASS_ALIAS => array(
            'Router' => '@%SmartRouter',
            'DefaultRouter' => '@%SmartRouter',
            'CacheAdapter' => '@%CacheAdapter',
            'ViewResolver' => '@%ViewResolver',
            'ExceptionHandler' => '@%ExceptionHandler'
        ),
        Consts::CONF_CLASS_ALIAS_LIMIT => 10,
        Consts::CONF_VIEW_MAPPINGS => array(
            'json' => '@%view.JsonView'
        ),
        Consts::CONF_VIEW_DEFAULT => 'json'
    );
    private static $_instance;
    private $classloader;
    public static function instance($config = null) {
        if ($config === null && self::$_instance) {
            return self::$_instance;
        } elseif ($config === null) {
            $config = array();
        }
        Config::initDefault(array_replace_recursive(self::$defaults, $config));
        if (($ns = Config::get(Consts::CONF_CLASS_NS)) === null) {
            Config::put(Consts::CONF_CLASS_NS, '\\');
        }
        if (!isset($config[Consts::CONF_ROUTER_ACTION_NS])) {
            $ans = $ns == '\\' ? '\action' : "$ns\\action";
            Config::put(Consts::CONF_ROUTER_ACTION_NS, $ans);
        }
        if (!isset($config[Consts::CONF_ROUTER_FILTER_NS])) {
            $fns = $ns == '\\' ? '\filter' : "$ns\\filter";
            Config::put(Consts::CONF_ROUTER_FILTER_NS, $fns);
        }
        if (!self::$_instance) {
            self::$_instance = new Framework();
        }
        return self::$_instance;
    }
    private function __construct() {
        \set_exception_handler(array($this, 'onException'));
    }
    public function start() {
        try {
            $this->processPretty();
        } catch (\Exception $exp) {
            $this->onException($exp);
        }
    }
    protected function processPretty() {
        $this->classloader = new ClassLoader();
        V::setClassLoader($this->classloader);
        $webRequest = $this->classloader->load('@%WebRequest');
        $guardians = array();
        if ($gds = Config::get(Consts::CONF_GUARD_MAPPINGS)) {
            foreach($gds as $key => $value) {
                $guardian = $this->classloader->load($value, true, true);
                if (!$guardian) {
                    throw new Exception("Fail to initialize guardian: $value",
                        Exception::CODE_PRETTY_CLASS_INIT_FAILED);
                }
                $guardians[$key] = $guardian;
            }
            $this->guard($guardians, $webRequest, Config::get(Consts::CONF_GUARD_REWIND_LIMIT, 10));
            if ($webRequest->getCode() == WebRequest::REWRITE_TERMINATE) {
                return;
            }
        }
        $router = $this->classloader->load('@*Router', true, true);
        if (!$router) {
            throw new Exception('Framework started failed. Could not load the router.',
                                    Exception::CODE_PRETTY_MISSING_CORE_CLASSES);
        }
        $av = $router->findAction($webRequest);
        $filters = $router->findFilters($webRequest);
        if (is_object($av) && $av->isActionV()) {
            $action = $av;
        } else {
            $action = $this->classloader->load(is_object($av) ? $av->getExp() : $av, 1, 1);
        }
        $this->runActionAndFilter($webRequest, $action, $filters);
        $this->runForwardAction($action, Config::get(Consts::CONF_ROUTER_FORWARD_LIMIT, 5));
        $this->display($action);
    }
    public function display($resource) {
        $viewResolver = $this->classloader->load('@*ViewResolver', true, true);
        if (!$viewResolver) {
            throw new Exception('Could not load ViewResolver, Action renderation failed.',
                                    Exception::CODE_PRETTY_MISSING_CORE_CLASSES);
        }
        $viewResolver->display($resource);
    }
    private function runActionAndFilter($webRequest, $action, $filters) {
        if (!$action) {
            throw Exception::createHttpStatus('The url you requested: [' . $webRequest->getOriginUri() . '] was not found.', 404);
        }
        $action->setWebRequest($webRequest);
        foreach ($filters as $key => $filter) {
            $filter->before($action);
            switch ($action->getStatus()) {
                case Action::STATUS_END:
                return;
            }
        }
        if ($action->getStatus() == Action::STATUS_END) {
            return;
        }
        $action->startAction();
        foreach ($filters as $key => $filter) {
            $filter->after($action);
            switch ($action->getStatus()) {
                case Action::STATUS_END:
                return;
            }
        }
    }
    private function runForwardAction($action, $remains) {
        if ($remains-- <= 0) {
            throw new Exception('Too many action forwards, check dead loop or increase [action.forwardLimits]', Exception::CODE_PRETTY_ACTION_ERROR);
        }
        if ($fw = $action->getForward()) {
            $fwa = ActionV::loadV($fw);
            if (!$fwa->isActionV()) {
                $fwa = $this->classloader->load($fwa->getExp(), 1, 1);
            }
            if (!$fwa) {
                throw new Exception('Forward Action not found', Exception::CODE_PRETTY_CLASS_NOTFOUND);
            }
            $fwa->copyFrom($action);
            $this->runActionAndFilter($action->getWebRequest(), $fwa, array());
            if ($fwa->getForward()) {
                $this->runForwardAction($fwa, $remains);
            }
            $action->copyFrom($fwa);
        }
    }
    private function guard($guardians, $webRequest, $remains) {
        if ($remains <= 0) {
            throw new Exception('Too much guradian rewinds. Check dead loops in guardians\' rewriting rule or increase [CONF_GUARD_REWIND_LIMIT]',
                Exception::CODE_PRETTY_ACTION_ERROR);
        }
        $remains--;
        foreach ($guardians as $key => $guardian) {
            if (!preg_match($key, $webRequest->getUri())) {
                continue;
            }
            $guardian->guard($webRequest);
            if ($webRequest->getCode() == WebRequest::REWRITE_FORWARD) {
                continue;
            }
            break;
        }
        switch ($webRequest->getCode()) {
            case WebRequest::REWRITE_TERMINATE:
                return;
            case WebRequest::REWRITE_LAST:
            case WebRequest::REWRITE_FORWARD:
                break;
            case WebRequest::REWRITE_REWIND:
                $this->guard($guardians, $webRequest, $remains);
                break;
            default:
                $error = 'Unkown status code in WebRequest. Guardian:'
                    . get_class($guardian) . '. Status code:' . $webRequest->getStatus();
                throw new Exception($error,
                    Exception::CODE_PRETTY_ACTION_ERROR);
                break;
        }
    }
    private function onException($exp) {
        if ($this->classloader) {
            $handler = $this->classloader->load('@*ExceptionHandler');
            if ($handler) {
                $handler->setClassLoader($this->classloader);
                $handler->handleException($exp);
                return;
            }
        }
        trigger_error($exp->__toString(), E_USER_ERROR);
        exit();
    }
}
interface Guardian {
    public function guard(WebRequest $request);
}
class SmartRouter {
    public $classLoader = "@%ClassLoader";
    const FILTER_LIMIT = 5;
    private $filters;
    private $ext;
    public function findAction(WebRequest $request) {
        $uri = preg_replace('/\\/+/', '/', $request->getUri());
        $clz = $this->findInStatic($uri);
        if ($clz) {
            if($this->classLoader->loadDefinition($clz, $detail)) {
                return $detail['origin'];
            }
        }
        $dotPos = strpos($uri, '.');
        if ($dotPos !== false) {
            $this->ext = substr($uri, $dotPos + 1);
            $uri = substr($uri, 0, $dotPos);
        }
        return $this->findActionByNs($request, $uri);
    }
    public function findFilters(WebRequest $request) {
        if ($this->filters === null) {
            $this->tearFilters($request);
        }
        return $this->filters;
    }
    private function findInStatic($uri) {
        if (!($mappings = Config::get(Consts::CONF_ROUTER_MAPPINGS))) {
            return null;
        }
        foreach ($mappings as $regex => $clz) {
            if (preg_match($regex, $uri)) {
                return $clz;
            }
        }
        return null;
    }
    private function tearFilters(WebRequest $request) {
        $arr = explode('/', $request->getUri());
        if (count($arr) < Config::get(Consts::CONF_ROUTER_FILTER_LIMIT, self::FILTER_LIMIT)) {
            return;
        }
        foreach ($arr as $key => $value) {
            $filter = $this->classLoader->load($value, true, false);
            if ($filter) {
                $this->filters[] = $filter;
            }
        }
    }
    private function findActionByNs(WebRequest $request, $uri) {
        $arr = explode('/', $uri);
        $actionNs = Config::get(Consts::CONF_ROUTER_ACTION_NS);
        if (count($arr) > Config::get(Consts::CONF_ROUTER_FILTER_LIMIT, 5)) {
            return null;
        }
        $fallbackLimit = Config::get(Consts::CONF_ROUTER_FALLBACK_LIMIT, 1);
        $subPaths = array();
        while($fallbackLimit--) {
            $subPath = array_pop($arr);
            $clzName = StringUtil::toPascalCase($subPath);
            $ns = $actionNs . implode('\\', $arr);
            $cname = "$ns\\$clzName";
            $av = ActionV::loadV($cname);
            if ($av->isActionV()) {
                $this->filters = $this->loadFilters($arr);
                $request->putExtra('subPaths', $subPaths);
                return $av;
            }
            if ($this->classLoader->loadDefinition($av->getExp(), $detail)) {
                $this->filters = $this->loadFilters($arr);
                $request->putExtra('subPaths', $subPaths);
                return $av->getExp();
            }
            array_unshift($subPaths, $subPath);
        }
        return null;
    }
    public function loadFilters($arr) {
        $ret = array();
        $filterNs = Config::get(Consts::CONF_ROUTER_FILTER_NS);
        while(1) {
            $filterName = array_pop($arr);
            if (!$filterName) {
                $filter = $this->classLoader->load($filterNs . '\Filter', 1, 0);
                if ($filter) {
                    array_unshift($ret, $filter);
                }
                break;
            }
            $filterName = $filterNs . implode('\\', $arr) . '\\' . StringUtil::toPascalCase($filterName);
            $filter = $this->classLoader->load($filterName, 1, 0);
            if ($filter) {
                array_unshift($ret, $filter);
            }
        }
        return $ret;
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
    public static function toCamelCase($ori, $exp = '/_([a-z])/') {
        $func = create_function('$c', 'return strtoupper($c[1]);');
        return preg_replace_callback($exp, $func, $ori);
    }
    public static function getCamelTail($str, $exp = '/^(.+)([A-Z][a-z0-9]+)$/') {
        preg_match($exp, $str, $result);
        return count($result) == 0 ? array($str, '') : array($result[1], $result[2]);
    }
}
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
interface View {
    public function render(WebResource $res);
}
class ViewResolver {
    public $classLoader = '@%ClassLoader';
    public function display(WebResource $res) {
        $view = $res->getView() ?: array();
        switch(count($view)) {
            case 0:
            case 1:
                $viewType = Config::get(Consts::CONF_VIEW_DEFAULT_TYPE, 'json');
                break;
            default:
                $viewType = $view[0];
                break;
        }
        $viewMappings = Config::get(Consts::CONF_VIEW_MAPPINGS);
        if (!isset($viewMappings[$viewType])) {
            throw new Exception(
                "Could not render view by type $viewType",
                Exception::CODE_PRETTY_VIEW_NOTFOUND);
        }
        $viewName = $viewMappings[$viewType];
        $view = $this->classLoader->load($viewName, true);
        if (!$view) {
            throw new Exception("Could not render view by $viewName, view class not found.",
                Exception::CODE_PRETTY_CLASS_NOTFOUND);
        }
        $view->render($res);
    }
}
class WebRequest {
    const REWRITE_LAST = 0;
    const REWRITE_FORWARD = 1;
    const REWRITE_REWIND = -1;
    const REWRITE_TERMINATE = -2;
    private $originUri;
    private $uri;
    private $code = self::REWRITE_FORWARD;
    private $guardians;
    private $extra = array();
    public function __construct() {
        $this->initUri();
        $ip = Arrays::valueFrom($_SERVER, 'X-Forward-Ip');
        if (!$ip) {
            $ip = Arrays::valueFrom($_SERVER, 'REMOTE_ADDR', '');
        }
        $this->extra['ip'] = $ip;
    }
    private function initUri() {
        $uri = Arrays::valueFrom($_SERVER, 'PATH_INFO') ?:
            Arrays::valueFrom($_SERVER, 'ORIG_PATH_INFO');
        if ($uri === null) {
            if (isset($_SERVER['REQUEST_URI'])) {
                if(preg_match('/\.php(\/?.*)/', $_SERVER['REQUEST_URI'], $matchers)) {
                    $uri = $matchers[1] ?: '/';
                } else {
                    $uri = $_SERVER['REQUEST_URI'];
                }
            } else {
                throw Exception::createHttpStatus('Cannot build request, none of these environments[PATH_INFO, ORIG_PATH_INFO, REQUEST_URI] exists.', 404);
            }
        }
        $this->originUri = $uri;
        if ($uri == '/') {
            $this->uri = Config::get('site.index', '/index');
        } else {
            $this->uri = $uri;
        }
    }
    public function rewrite($uri, $code = self::REWRITE_FORWARD) {
        $this->uri = $uri;
        $this->code = $code;
    }
    public function terminate() {
        $this->code = self::REWRITE_TERMINATE;
    }
    public function httpError($code, $messageBody) {
        header("http/1.1 $code");
        echo $messageBody;
    }
    public function getCode() {
        return $this->code;
    }
    public function getOriginUri() {
        return $this->originUri;
    }
    public function getUri() {
        return $this->uri;
    }
    public function putExtra($key, $value) {
        $this->extra[$key] = $value;
    }
    public function getExtra($key, $default = null) {
        if ($key === null) {
            return $this->extra;
        }
        return Arrays::valueFrom($this->extra, $default);
    }
}
class WebResource {
    private $data = array();
    private $readonly;
    private $view = null;
    private $webRequest;
    public function get($key, $default = null) {
        return Arrays::valueFrom($_GET, $key, $default);
    } 
    public function getRequest($key, $default = null) {
        return Arrays::valueFrom($_REQUEST, $key, $default);
    }
    public function getPost($key, $default = null) {
        return Arrays::valueFrom($_POST, $key, $default);
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
    public function setData($data) {
        if ($this->isReadonly()) {
            return;
        }
        $this->data = $data;
    }
    public function getData($key = null) {
        return $key !== null ? Arrays::valueFrom($this->data, $key) : $this->data;
    }
    public function isReadonly() {
        return $this->readonly;
    }
    public function setReadonly($readonly) {
        $this->readonly = $readonly ? true : false;
    }
    public function copyFrom(WebResource $res) {
        $this->put($res->getData());
    }
    public function setView() {
        $this->view = func_get_args();
    }
    public function getView() {
        return $this->view;
    }
    public function setWebRequest(WebRequest $request) {
        $this->webRequest = $request;
    }
    public function getWebRequest() {
        return $this->webRequest;
    }
}

