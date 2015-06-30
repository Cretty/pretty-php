<?php

namespace net\shawn_huang\pretty;

class SmartRouter {

    public $classLoader = "@%ClassLoader";

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
        return $this->filters ?: array();
    }

    private function findInStatic($uri) {
        if (!($mappings = Config::get('router.mappings'))) {
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
        $actionNs = Config::get('class.actionNamespace');
        if (count($arr) > Config::get('router.filterLimits', 5)) {
            # filter limits
            return null;
        }
        $fallbackLimit = Config::get('router.maxFallbacks', 2);
        $subPaths = array();
        while($fallbackLimit--) {
            $subPath = array_pop($arr);
            $clzName = StringUtil::toPascalCase($subPath);
            $ns = $actionNs . implode('\\', $arr);
            $cname = "$ns\\$clzName";
            if ($this->classLoader->loadDefinition($cname, $detail)) {
                $this->filters = $this->loadFilters($arr);
                $request->putExtra('subPaths', $subPaths);
                return $detail['name'];
            }
            array_unshift($subPaths, $subPath);
        }
        # limit reached!
        return null;
    }

    public function loadFilters($arr) {
        $ret = array();
        $filterNs = Config::get('class.filterNamespace');
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