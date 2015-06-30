<?php

namespace filter;
use \net\shawn_huang\pretty;

class Filter implements pretty\Filter {

    public function before(pretty\Action $action) {
        $action->put('foo', 'bar');
    }
    
    public function after(pretty\Action $action) {
        $action->put('holy', 'crap');
        $action->setStatus(pretty\Action::STATUS_READONLY);
    }
}