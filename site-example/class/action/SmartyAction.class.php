<?php

namespace my\site\action;
use \net\shawn_huang\pretty as p;

class SmartyAction extends p\Action {

    protected function run() {
        $this->setView('welcome');
        $this->put('welcome', 'This page was rendered by <a href="http://smarty.net">smarty template</a>');
    }
}