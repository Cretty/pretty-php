<?php

namespace my\site\action\foo;
use \net\shawn_huang\pretty as p;

class BarAction extends p\Action {

    protected function run() {
        $this->put('foo', 'bar');
    }
}
