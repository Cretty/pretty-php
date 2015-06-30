<?php

namespace action;
use \net\shawn_huang\pretty\Action;

class Index extends Action {

    protected function run () {
        $this->put('foo', 'bar');
        $this->setView('json');
    }
} 