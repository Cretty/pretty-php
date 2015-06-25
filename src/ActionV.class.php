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
        $func = $this->runnable;
        if (is_callable($func)) {
            $func($this);
        }
    }

    private function init() {
        $clz = $this->classloader->explainClasses($this->exp);
        if ($clz['isClass']) {
            $path = $clz['file'] . '.v.php';
            if (is_file($path)) {
                require $path;
                $this->runnable = V::getRunnable();
                $this->put(V::data());
                $this->setView(V::view());
                return $this->isV = true;
            }
        } else {
            throw new Exception("Forward Action [{$this->exp}] not found", Exception::CODE_PRETTY_CLASS_NOTFOUND);
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
