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

    /**
     * Create an instance of pretty framework by given configuation array.
     * WARNING, configs should `NOT` be changed after called.
     * @access public
     * @static
     * @param array $config configuration array
     * @return Framework pretty framework instance
     */
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
        // $webRequest = new WebRequest();
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

    public function onException($exp) {
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

