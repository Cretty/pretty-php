<?php

namespace net\shawn_huang\pretty;

class SmartRouter {

    public $classLoader = "@%ClassLoader";
    const FILTER_LIMIT = 5;

    private $filters;
    private $ext;

    public function findAction(WebRequest $request) {
        $uri = preg_replace('/\\/+/', '/', $request->getUri());
        $clz = $this->findInStatic($uri);
        if ($clz) {
            $av = Actionv::loadV($clz);
            if ($av->isActionV()) {
                return $av;
            }

            if ($this->classLoader->loadDefinition($av->getExp())) {
                return $av->getExp();
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
        return $this->filters ?: array();
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

    private function findActionByNs(WebRequest $request, $uri) {
        $arr = explode('/', $uri);
        $actionNs = Config::get(Consts::CONF_ROUTER_ACTION_NS);
        if (count($arr) > Config::get(Consts::CONF_ROUTER_FILTER_LIMIT, 5)) {
            # filter limits
            return null;
        }
        // By default, disable action fallbacks
        $fallbackLimit = Config::get(Consts::CONF_ROUTER_FALLBACK_LIMIT, 1);
        $subPaths = array();
        while($fallbackLimit--) {
            $subPath = array_pop($arr);
            $clzName = StringUtil::toPascalCase($subPath);
            $ns = $actionNs . implode('\\', $arr);
            $cname = "$ns\\$clzName";

            # Try to find Action V
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
        # limit reached!
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