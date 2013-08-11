<?php

namespace action;
use \net\shawn_huang\pretty\Action;
use \net\shawn_huang\pretty\Exception;

class Err1 extends Action {

    protected function run() {
        $this->setView('json');
        $this->put('foo', 'bar');
        throw Exception::createHttpStatus(500, 'error 1');
    }
}
