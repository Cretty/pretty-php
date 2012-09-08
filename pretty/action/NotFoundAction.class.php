<?php

namespace net\shawn_huang\pretty\action;

use \net\shawn_huang\pretty as p;

class NotFoundAction extends p\Action {

    protected function run() {
        $this->setView('nofound', 'Debug');
    }

}