<?php

namespace my\site\action\foo;
use \net\shawn_huang\pretty as p;

class BarAction extends p\Action {

    public $inc = '\\my\\site\\inc\\MyInc';// Just tell pretty which class to load
    protected function run() {
        $this->put('foo', 'bar');
        $this->put('inc says', $this->inc->say()); // Pretty will automatically load MyInc class if it is found.
    }
}
