<?php

namespace net\shawn_huang\pretty;

class ViewResolver {

    public $classLoader;

    public function render(Action $action) {
        list($name, $clz) = $action->getView();
        $view = $this->loadView($clz);
        if($view == null) {
            $view = $this->classLoader->singleton(Pretty::$CONFIG->get('views.debug'));
        }
        $view->render($action);
    }

    private function loadView($viewType) {
        if ($viewClz = Pretty::$CONFIG->get("views.$viewType")) {
            $ret = $this->classLoader->singleton($viewClz);
            if ($ret) {
                Pretty::log("class:$viewClz", true);
                return $ret;
            }
            Pretty::log("class:$viewClz", false);
        }
        $viewClz = Pretty::$CONFIG->getNsPrefix() . "\\view\\" . StringUtil::toPascalCase($viewType) . 'View';
        $ret = $this->classLoader->singleton($viewClz);
        if ($ret) {
            Pretty::log("class:$viewClz", true);
        }
        Pretty::log("class:$viewClz", false);
        return $ret;
    }
}