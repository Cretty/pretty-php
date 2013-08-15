<?php

namespace net\shawn_huang\pretty;

const REAL_CLASS_PATTERN = '/^\\\\([a-z0-9\\\\_]+)/i';
const CLASS_PATTERN = '/^@|&[%\*#]?\+?([a-z0-9_<>\.])+/i';

const CLASS_TYPE_ABSOLUTE = 1;
const CLASS_TYPE_PRETTY = 2;
const CLASS_TYPE_DOMAIN = 3;

/**
 * The soul of pretty.
 * Loading classes by given expression.
 * Pretty framework using this classloader
 * to do object injections.
 */
class ClassLoader {


    private $pool = array();
    private $ns;
    private $pns;
    private $prettyPath;

    public function __construct() {
        $this->pool['\\' . __CLASS__] = $this;
        $this->ns = Config::get('class.namespace', '\\');
        $this->ns = $this->ns == '\\' ? '\\' : $this->ns . '\\';
        $this->pns = '\\' . __NAMESPACE__;
        $this->prettyPath = __DIR__;
    }

    public function load($clz, $initProperties = false, $warning = true) {
        if (is_string($clz) && isset($this->pool[$clz])) {
            return $this->pool[$clz];
        }
        if (is_string($clz)) {
            $clz = $this->explainClasses($clz);
        }
        if (!$clz['isNew'] && isset($this->pool[$clz['name']])) {
            return $this->pool[$clz['name']];
        }
        if (!$clz['isClass']) {
            return $clz['value'];
        }
        if (!class_exists($clz['name'], 0) && !$this->loadDefinition($clz)) {
            if ($warning) throw new Exception("Class[{$clz['origin']}, {$clz['file']}] not found.", Exception::CODE_PRETTY_CLASS_NOTFOUND);
            return null;
        }
        $name = $clz['name'];
        if ($clz['args']) {
            $class = new \ReflectionClass($name);
            $instance = $class->newInstanceArgs($clz['args']);
        } else {
            $instance = new $name();
        }
        if ($initProperties) {
            $this->loadProperties($instance);
        }
        return $instance;
    }

    public function loadProperties($obj) {
        foreach(get_object_vars($obj) as $key => $value) {
            if (!is_string($value) && !is_array($value)) {
                continue;
            }
            if (is_string($value)) {
                $value = $this->explainClasses($value);
            }
            if (isset($value['isValue']) && $value['isValue']) {
                $obj->$key = $value['value'];
                continue;
            }
            if (!isset($value['isClass']) || !$value['isClass']) {
                continue;
            }
            $ppt = $this->load($value, $value['loadChildren'], false);
            if ($ppt) {
                $obj->$key = $ppt;
            }
        }
    }

    public function loadDefinition($clz, &$detail = null) {
        if (is_string($clz)) {
            $clz = $this->explainClasses($clz);
        }
        $detail = $clz;
        if (!$clz['isClass']) {
            throw new Exception("Class definition of [{$clz['origin']} => {$clz['name']}] not found.", Exception::CODE_PRETTY_CLASS_NOTFOUND);
        }
        if (class_exists($clz['name'], false) && $clz['preloads'] == null) {
            return true;
        }
        switch($clz['type']) {
            case CLASS_TYPE_ABSOLUTE:
                $path = Config::get('class.extraPath', Config::get('class.path')) 
                    . str_replace('\\', '/', $clz['name']);
                break;
            case CLASS_TYPE_DOMAIN:
                $subPath = str_replace(
                    array($this->ns ?: '', ''), 
                    array('\\', '/'),
                    $clz['name']
                );
                $path = Config::get('class.path') . $subPath;
                break;
            case CLASS_TYPE_PRETTY:
                $subPath = str_replace(
                    array($this->pns, ''), 
                    array('\\', '/'),
                    $clz['name']
                );
                $path = __DIR__ . $subPath;
                break;
        }
        $loads = $clz['preloads'];
        $loads[] = $clz['file'];
        foreach ($loads as $key => $value) {
            if (is_file("$value.class.php")) {
                require_once "$value.class.php";
                continue;
            }
            if (is_file("$value.interface.php")) {
                require_once "$value.interface.php";
                continue;
            }
            if (is_file("$value.php")) {
                require_once "$value.php";
                continue;
            }
            return false;
        }
        return true;
    }

    public function realClassName($class) {

    }

    public function fockAutoload() {
        spl_autoload_register(array($this, 'loadDefinition'));
    }

    public function explainClass($desc, $aliasDeep = 0) {
        if (is_array($desc)) {
            return $this->classTemplate('Array', $desc);
        }
        if ($desc{0} == '\\' && preg_match(REAL_CLASS_PATTERN, $desc)) {
            $type = CLASS_TYPE_ABSOLUTE;
            if (strpos($desc, $this->ns) === 0) {
                $type = CLASS_TYPE_DOMAIN;
                $file = $this->parseDomainFile($desc);
            }
            if (strpos($desc, $this->pns)) {
                $type = $this->pns;
                $file = $this->parsePrettyFile($desc);
            }
            if ($type == CLASS_TYPE_ABSOLUTE) {
                $file = $this->parseAbsoluteFile($desc);
            }
            return $this->classTemplate($desc, array(
                'isClass' => true,
                'name' => $desc,
                'type' => $type,
                'file' => $file
            ));
        }
        if ($desc{0} != '@' && $desc{0} != '&') {
            return $this->classTemplate($desc, array('errors' => 'Not starts with @ or &'));
        }
        if (!preg_match(CLASS_PATTERN, $desc)) {
            return $this->classTemplate($desc, array('errors' => 'not match pattern:' . CLASS_PATTERN));
        }
        $prefix = $desc{0};
        $alias = Config::get('class.alias');
        $isNew = false;
        switch($desc{1}) {
            case '%':
                if ($desc{2} == '+') {
                    $isNew = true;
                }
                $name = str_replace(
                    array($prefix, '%', '.', '+'),
                    array($this->pns, '\\', '\\', ''),
                    $desc
                ); 
                $type = CLASS_TYPE_PRETTY;
                $file = $this->parsePrettyFile($name);
                break;
            case '.':
                if ($desc{2} == '+') {
                    $isNew = true;
                }
                $name = str_replace(
                    array("$prefix.", '.', '+'),
                    array($this->ns, '\\', ''),
                    $desc
                );
                $type = CLASS_TYPE_DOMAIN;
                $file = $this->parseDomainFile($name);
                break;
            case '#':
                return $this->classTemplate(
                    $desc,
                    array(
                        'isValue' => true,
                        'value' => Config::get(substr($desc, 2))
                    )
                );
            case '*':
                $aliasLimit = Config::get('class.aliasLimit');
                if ($aliasDeep >= $aliasLimit) {
                    throw new Exception("Alias too deep, Limit " . Config::get('class.aliasLimit'),
                        Exception::CODE_PRETTY_CLASS_INI_FAILED);
                }
                $target = substr($desc, 2);
                if (!isset($alias[$target])) {
                    return $this->classTemplate($desc);
                }
                return $this->explainClass($alias[$target], $aliasDeep + 1);
            default:
                $name = str_replace(
                    array($prefix, '+', '.'),
                    array('\\', '', '\\'),
                    $desc
                );
                $clz = $this->explainClass($name);
                $clz['isNew'] = $desc{1} == '+' ? true : false;
                $clz['loadChildren'] = $prefix == '@' ? true : false;
                return $clz;
        }
        return $this->classTemplate($desc, array(
            'isClass' => true,
            'type' => $type,
            'isNew' => $isNew,
            'name' => $name,
            'file' => $file,
            'loadChildren' => $prefix == '@' ? true : false
        ));
    }

    public function explainClasses($desc, $aliasDeep = 0) {
        if (is_array($desc)) {
            return $this->classTemplate($desc, $desc);
        }
        if (!isset($desc{1})) {
            return $this->classTemplate($desc);
        }
        $arr = explode('>', $desc);
        $count = count($arr);
        if ($count > 1) {
            for ($i = 0; $i < $count - 1; $i++) { 
                $preload = $this->explainClass($arr[$i]);
                if (!$preload['isClass']) {
                    syslog(LOG_DEBUG, "Explain $desc failed, {$arr[$i]} isnt a class or interface");
                    return $this->classTemplate(array('isClass' => false)); # If not match
                }
                $preloads[] = $preload['file'];
            }
            $cname = $arr[$count - 1];
            if (strpos($cname, '<') !== false) {
                list($cname, $args) = explode('<',  $cname);
                $args = explode(',', $args);
            }
            $clz = $this->explainClass($arr[$count - 1]);
            if ($clz['isClass']) {
                $clz['preloads'] = $preloads;
                $clz['origin'] = $desc;
                $clz['args'] = isset($args) ? $args : null;
            }
        } else {
            if (strpos($desc, '<') !== false) {
                list($cname, $args) = explode('<',  $desc);
                $args = explode(',', $args);
            } else {
                $cname = $desc;
            }
            $clz = $this->explainClass($cname);
            $clz['origin'] = $desc;
            $clz['args'] = isset($args) ? $args : null;
        }
        return $clz;
    }

    private function classTemplate($origin, $preset = null) {
        $ret = array(
            'isClass' => false,
            'isValue' => false,
            'name' => null,
            'type' => null,
            'isNew' => false,
            'file' => null,
            'preloads' => null,
            'args' => null,
            'value' => null,
            'errors' => null,
            'loadChildren' => true,
            'origin' => $origin
        );
        if ($preset) {
            return $preset + $ret;
        }
        return $ret;
    }

    private function parsePrettyFile($name) {
        return str_replace(
            array($this->pns, '\\'), array($this->prettyPath, '/'), $name
        );
    }

    private function parseDomainFile($name) {
        return Config::get('class.path') . str_replace(
            array($this->ns, '\\'),
            array('/', '/'),
            $name
        );
    }

    private function parseAbsoluteFile($name) {
        return Config::get('class.lib') . str_replace('\\', '/', $name);
    }


}

