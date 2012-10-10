<?php

namespace net\shawn_huang\pretty;

class NsRouter implements Router {

    private $filterCache;

    public function findAction(ClassLoader $classLoader, $pathInfo) {
        if ($pathInfo === null || $pathInfo === '/' || $pathInfo === '') {
            $q = '/index';
        } else  {
            $q = preg_replace('/(\\..*)$/', '', $pathInfo);
        }
        Pretty::log('request.path', $q);
        $arr = explode('/', $q);
        if (count($arr) > Pretty::$CONFIG->get('path.maxdeep')) {
            header('HTTP/1.1 405 request path too deep');
            echo ('request path too deep');
            die();
        }
        // start
        $action = null;
        $subRequest = array();
        while(($ends = array_pop($arr)) !== null) {
            if ($ends == '') {
                continue;
            }
            $className = $this->buildActionPath($arr, $ends);
            $action = $classLoader->singleton($className);
            if ($action == null && Pretty::$CONFIG->get('action.smartIndex')) {
                Pretty::log("class:$className", false);
                $className = $this->buildActionPath($arr, $ends, true);
                $action = $classLoader->singleton($className);
                if($action !== null) array_push($arr, $ends);
            }
            if ($action !== null) {
                $classLoader->invokeProperties($action);
                $this->loadFilters($arr, $classLoader);
                Pretty::log("class:$className", true);
                $action->subRequest = $subRequest;
                break;
            }
            Pretty::log("class:$className", false);
            array_unshift($subRequest, $ends);
        }
        return $action;
    }

    public function findFilters(ClassLoader $classLoader, $pathInfo, $action = null) {
        return $this->filterCache;
    }

    private function loadFilters($arr, $classLoader) {
        while(($name = array_pop($arr)) !== null) {
            $filterName = Pretty::$CONFIG->getNsPrefix() . '\\filter' . implode('\\', $arr) . '\\' . StringUtil::toPascalCase($name) . 'Filter';
            $filter = $classLoader->singleton($filterName);
            if ($filter) {
                $classLoader->invokeProperties($filter);
                $this->filterCache[] = $filter;
                pretty::log("class:$filterName", true);
                continue;
            }
            pretty::log("class:$filterName", false);
        }
    }

    private function buildActionPath($arr, $ends, $index = false) {
        $classPrefix = Pretty::$CONFIG->getNsPrefix() . '\\action' . implode('\\', $arr) . '\\'; 
        if ($index) {
            return "{$classPrefix}{$ends}\\IndexAction";
        }
        return $classPrefix . StringUtil::toPascalCase($ends) . 'Action';
    }
}