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
        $actionNs = Config::get(Consts::CONF_ROUTER_ACTION_NS);
        $pathInfo = $this->parseUrl($uri);

        if (!$pathInfo) return null;

        list($clz, $params, $filters) = $pathInfo;

        $cname = "$actionNs\\$clz";

        $av = ActionV::loadV($cname);
        if ($av->isActionV()) {
            $this->filters = $this->loadFilters($filters);
            $request->putExtra('params', $params);
            return $av;
        }

        if ($this->classLoader->loadDefinition($cname, $detail)) {
            $this->filters = $this->loadFilters($filters);
            $request->putExtra('params', $params);
            return $detail['name'];
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

    private function parseUrl ($url) {
        $regex = '/\/([a-z][\w\-]*)(\/((\d+)|(:\w+)|({[a-z0-9\-]+})))*/i';
        if (!preg_match_all($regex, $url, $matches)) {
            return null;
        }

        $urlFragments = $matches[0];
        $params = [];
        $filters = [];

        $newUrl = implode(
            '\\',
            array_map(function ($p, $i) use (
                &$params, &$filters, $urlFragments
            ) {
                $paths = explode('/', $p);

                $urlPath = $paths[1];
                $args = array_slice($paths, 2);
                $args = array_map(function ($arg) {
                    if (is_numeric($arg)) {
                        return $arg + 0;
                    } else {
                        if ($arg[0] === ':') {
                            return substr($arg, 1);
                        } else {
                            return substr($arg, 1, strlen($arg) - 2);
                        }
                    }
                }, $args);
                if (count($args) === 1) $args = $args[0];
                if (isset($params[$urlPath])) {
                    $exists = $params[$urlPath];
                    if (is_array($exists)) {
                        $params[$urlPath][] = $args;
                    } else {
                        $params[$urlPath] = [
                            $exists, $args
                        ];
                    }
                } else if (!empty($args)) {
                    $params[$urlPath] = $args;
                }

                if ($i === count($urlFragments) - 1) {
                    return StringUtil::toPascalCase($urlPath);
                } else {
                    $filters[] = $urlPath;
                    return $urlPath;
                }

            }, $urlFragments, array_keys($urlFragments))
        );

        return [$newUrl, $params, $filters];
    }
}