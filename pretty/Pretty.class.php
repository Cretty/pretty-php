<?php

namespace net\shawn_huang\pretty;

class Pretty {
    
    public static $CONFIG;

    private static $log = array();
    private $classLoader;
    private $viewResolver;
    private $router;
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
        $routerClz = self::$CONFIG->get('pretty.router');
        $this->router = $this->classLoader->singleton($routerClz);
        self::$CONFIG->get('views.welcome') or self::$CONFIG->set('views.welcome', '\\net\\shawn_huang\\pretty\\view\\WelcomeView');
    }

    private function buildChain() {
        $q = self::getArray($_SERVER, 'PATH_INFO') ?: self::getArray($_SERVER, 'ORIG_PATH_INFO');
        $action = $this->router->findAction($this->classLoader, $q);
        if (!$action) {
            $this->fallback($q);
            return;
        }
        $filters = $this->router->findFilters($this->classLoader, $q, $action);
        $beforeFilter = new FilterChain($filters, $action, FilterChain::TYPE_BEFORE);
        $beforeFilter->doFilter();
        $action->startAction();
        $afterFilter = new FilterChain($filters, $action, FilterChain::TYPE_AFTER);
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