<?php

namespace net\shawn_huang\pretty;

class action\DebugAction extends Action {

    protected function run() {
        $this->put('request path', $_SERVER['PATH_INFO']);
    }
}