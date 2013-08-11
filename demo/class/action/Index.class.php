<?php

namespace action;

use \net\shawn_huang\pretty;

class Index extends pretty\Action {

    protected function run() {
        $this->put('hello', 'world');
        $this->setView('json');
    }
}