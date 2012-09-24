<?php

namespace net\shawn_huang\pretty;

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