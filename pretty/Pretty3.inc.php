<?php
#vim set et,ts=4, sw=4, sts=4
namespace net\shawn_huang\pretty;

class Pretty {
    
    public static $CONFIG;

    private $filters;
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
        $q = self::getArray($_SERVER, 'PATH_INFO');
        if ($q === null || $q === '/') {
            $q = '/index';
        } else  {
            $q = preg_replace('/(\\..*)$/', '', $q);
        }
        $arr = explode('/', $q);
        if (count($arr) > self::$CONFIG->get('path.maxdeep')) {
        	header('HTTP/1.1 405 request path too deep');
        	echo ('request path too deep');
        	die();
        }
        $action = null;
        while(($ends = array_pop($arr)) !== null) {
            if ($ends == '') {
                continue;
            }
            $className = $this->buildActionPath($arr, $ends);
            $action = $this->classLoader->singleton($className);
            if ($action !== null) {
	            $this->classLoader->invokeProperties($action);
                $this->loadFilters($arr);
                break;
            }
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
            }
        }
    }

    private function buildActionPath($arr, $ends) {
        return self::$CONFIG->getNsPrefix() . '\\action' . implode('\\', $arr) . '\\' . StringUtil::toPascalCase($ends) . 'Action';
    }

    private function fallback($q) {
        if ($q == '/index') {
            include 'view/WelcomeView.class.php';
            $view = new view\WelcomeView();
            $view->render(null);
            return;
        }
        include 'view/DebugView.class.php';
        $view = new view\DebugView();
        $view->render(null);
    }


    public static function getArray($array, $key, $default = null) {
        return isset($array[$key]) ? $array[$key] : $default;
    }

    public static function includePrettyClass($className) {
        if (!class_exists($className)) {
            self::$instance->classLoader->loadFile($className) or die("$className not found in Pretty library");
        }
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
            //不产生任何异常
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
        if (file_exists($file)) {
            include_once $file;
            return true;
        }
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
    			return;
    		}
    		$this->invokeProperties($p);
    		$obj->$k = $p;
    	}
    }

}

class ViewResolver {

	public $classLoader;

    public function render(Action $action) {
        list($name, $clz) = $action->getView();
        $viewClass = Pretty::$CONFIG->get("views.$clz") ?: '\\net\\shawn_huang\\pretty\\view\\DebugView';
        $view = $this->classLoader->singleton($viewClass);
        if($view == null) {
        	die('Cannot render action:' . get_class($action) . ', View:' . $viewClass . ' not found.');
        }
        $view->render($action);
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

    public static function endsWith2($str, $search) {
        return preg_match("/$search\\$/", $str);
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

abstract class Action {

	const STATUS_NORMAL = 0;
	const STATUS_READONLY = 1;
	const STATUS_END = -1;
	const STATUS_SKIP = -2;

    private $view = null;
    private $data = array();
    private $actionStatus = 0;

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

    public function getGET($key, $default = null) {
        return Pretty::getArray($_GET, $key, $default);
    } 

    public function getPOST($key, $default = null) {
        return Pretty::getArray($_POST, $key, $default);
    }

    public function put($key, $value) {
    	if ($this->isReadonly()) {
    		return;
    	}
    	$this->data[$key] = $value;
    }

    public function setResult($result, $key = 'result') {
    	$this->put($key, $result);
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


    public function getData() {
        return $this->data;
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

	public function teminate() {
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

interface View {
    public function render(Action $action);
}