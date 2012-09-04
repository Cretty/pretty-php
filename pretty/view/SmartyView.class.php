<?php

namespace net\shawn_huang\pretty\view;
use \net\shawn_huang\pretty as p;
use \net\shawn_huang\pretty\Pretty;

class SmartyView implements p\View {

    protected function initConfig($smarty, $action) {
        $smarty->setTemplateDir(p\Pretty::$CONFIG->getClassPath() . '/view/tpl');
        $smarty->setCacheDir(p\Pretty::$CONFIG->getClassPath() . '/cache');
        $smarty->setCompileDir(p\Pretty::$CONFIG->getClassPath() . '/view/compiled');
        $smarty->assign($action->getData());
    }

    public function render(p\Action $action) {
        $smartyDir = Pretty::$CONFIG->get('smarty.dir');
        if (!is_dir($smartyDir)) {
            echo 'Can not found smarty lib, please set "smarty.dir" in SiteConfig';
        }
        require_once $smartyDir . '/Smarty.class.php';
        $smarty = new \Smarty();
        $this->initConfig($smarty, $action);
        $this->output($smarty, $action);
    }

    protected function output($smarty, $action) {
        list($viewname, $type) = $action->getView();
        echo $smarty->fetch($viewname . '.tpl');
    }
}