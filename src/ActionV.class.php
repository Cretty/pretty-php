<?php

namespace net\shawn_huang\pretty;

class ActionV extends Action {

    public static function loadV($name) {
        $av = new ActionV($name, false);
        $av->classloader = V::c($av->classloader);
        $av->init();
        return $av;
    }

    private $runnable;
    private $exp;
    private $isV;


    public function __construct($exp, $init = true) {
        $this->exp = $exp;
        if ($init) {
            $this->init();
        }
    }

    protected function run() {
        if ($this->runnable == null) {
            return;
        }
        list($func, $args) = $this->runnable;
        $params = array($this);
        foreach ($args as $value) {
            $params[] = $this->classloader->load($value, true, true);
        }
        call_user_func_array($func, $params);
    }

    private function init() {
        $clz = $this->classloader->explainClasses($this->exp);
        if ($clz['isClass']) {
            $path = $clz['file'] . '.v.php';
            if (is_file($path)) {
                require $path;
                $this->runnable = V::getRunnable();
                $this->put(V::data());
                // $this->setView(V::view());
                $view = V::view();
                if ($view) {
                    \call_user_func_array(array($this, 'setView'), V::view());
                } else {
                    $this->setView($view);
                }
                return $this->isV = true;
            }
        } else {
            throw new Exception("Invalid Expression[{$this->exp}]", Exception::CODE_PRETTY_CLASS_NOTFOUND);
        }
        $this->exp = $clz;
        return $this->isV = false;
    }

    public function isActionV() {
        return $this->isV;
    }

    public function getExp() {
        return $this->exp;
    }
}
