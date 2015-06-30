<?php

namespace action\sub;
use net\shawn_huang\pretty\Action;

class Index extends Action {

    protected function run() {
        $this->put([
            'holy' => 'shit'
        ]);
    }
}