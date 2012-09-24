<?php

namespace net\shawn_huang\pretty;

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
        if (is_file($file)) {
            include_once $file;
            Pretty::log("file:$file", true);
            return true;
        }
        Pretty::log("file:$file", false);
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
                continue;
            }
            $this->invokeProperties($p);
            $obj->$k = $p;
        }
    }
}