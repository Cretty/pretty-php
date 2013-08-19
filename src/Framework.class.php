<?php

namespace net\shawn_huang\pretty;

class Framework {

    /**
     * @static
     * @access protected
     * @var array $defaults
     * Default framework configuations
     */
    protected static $defaults = array(
        'class.alias' => array(
            'Router' => '@%SmartRouter',
            'DefaultRouter' => '@%SmartRouter',
            'CacheAdapter' => '@%CacheAdapter',
            'ViewResolver' => '@%ViewResolver',
            'ExceptionHandler' => '@%ExceptionHandler'
        ),
        'class.aliasLimit' => 10,
        'view.mappings' => array(
            'json' => '@%view.JsonView'
        ),
        'view.defaultView' => 'json'
    );

    private static $_instance;
    private $classloader;

    /**
     * Create an instance of pretty framework by given configuation array.
     * WARNING, configs should `NOT` be changed after called.
     * @access public
     * @static
     * @param array $config configuration array
     * @return Framework pretty framework instance
     */
    public static function instance($config = array()) {
        Config::initDefault($config + self::$defaults);
        if (($ns = Config::get('class.namespace')) === null) {
            Config::put('class.namespace', '\\');
        }
        if (!isset($config['class.actionNamespace'])) {
            $ans = $ns == '\\' ? '\action' : "$ns\\action";
            Config::put('class.actionNamespace', $ans);
        }
        if (!isset($config['class.filterNamespace'])) {
            $fns = $ns == '\\' ? '\filter' : "$ns\\filter";
            Config::put('class.filterNamespace', $fns);
        }
        if (!self::$_instance) {
            self::$_instance = new Framework();
        }
        return self::$_instance;
    }

    private function __construct() {
        \set_exception_handler(array($this, 'onException'));
    }

    /**
     * Start pretty framework
     */
    public function start() {
        try {
            $this->processPretty();
        } catch (\Exception $exp) {
            $this->onException($exp);
        }
    }

    /**
     * @access protected
     * Processing framework
     */
    protected function processPretty() {
        $this->classloader = new ClassLoader();
        $webRequest = new WebRequest();
        $guardians = array();
        if ($gds = Config::get('guardians.mappings')) {
            foreach($gds as $key => $value) {
                $guardian = $this->classloader->load($value, true, true);
                if (!$guardian) {
                    throw new Exception("Fail to initialize guardian: $value",
                        Exception::CODE_PRETTY_CLASS_INIT_FAILED);
                }
                $guardians[$key] = $guardian;
            }
            $this->guard($guardians, $webRequest, Config::get('guardians.maxRewinds', 10));
            if ($webRequest->getCode() == WebRequest::REWRITE_TERMINATE) {
                return;
            }
        }
        $router = $this->classloader->load('@*Router', true, true);
        if (!$router) {
            throw new Exception('Framework started failed. Could not load the router.',
                                    Exception::CODE_PRETTY_MISSING_CORE_CLASSES);
        }
        $clzName = $router->findAction($webRequest);
        $filters = $router->findFilters($webRequest);
        $action = $this->classloader->load($clzName, 1, 1);
        $this->runActionAndfilter($webRequest, $action, $filters);
        $this->runForwardAction($action, Config::get('action.forwardLimits', 5));
        $this->display($action);
    }

    /**
     * Display a resource
     * @param WebResource $resource resource
     */
    public function display($resource) {
        $viewResolver = $this->classloader->load('@*ViewResolver', true, true);
        if (!$viewResolver) {
            throw new Exception('Could not load ViewResolver, Action renderation failed.',
                                    Exception::CODE_PRETTY_MISSING_CORE_CLASSES);
        }
        $viewResolver->display($resource);
    }

    /**
     * Run action and filter
     * @access private
     * @param WebRequest $webRequest request
     * @param Action $action action object
     * @param array $filters an array carries filters
     */
    private function runActionAndfilter($webRequest, $action, $filters) {
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
            $fwa = $this->classloader->load($fw, 1, 1);
            if (!$fwa) {
                throw new Exception('Forward Action not found', Exception::CODE_PRETTY_CLASS_NOTFOUND);
            }
            $fwa->copyFrom($action);
            $this->runActionAndfilter($fwa, array());
            if ($fwa->getForward()) {
                $this->runForwardAction($fwa, $remains);
            }
            $action->copyFrom($fwa);
        }
    }

    private function guard($guardians, $webRequest, $remains) {
        if ($remains <= 0) {
            throw new Exception('Too much guradian rewinds. Check dead loops in guardians\' rewriting rule or increase [guardians.maxRewrites]',
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
                # log.
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

