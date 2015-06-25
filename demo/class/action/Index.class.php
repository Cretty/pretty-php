<?php

namespace action;

use \net\shawn_huang\pretty;

/**
 * Classic Pretty Action
 * With extension of .class.php
 */
class Index extends pretty\Action {

    protected function run() {
        $this->put('hello', 'world');
        $this->setView('json');
    }
}