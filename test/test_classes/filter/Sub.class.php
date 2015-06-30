<?php

namespace filter;
use \net\shawn_huang\pretty;

class Sub implements pretty\Filter {

    public function before(pretty\Action $action) {
        $action->put('foo', 'foo');
    }
    
    public function after(pretty\Action $action) {
        $action->put('holy', 'poop'); 
    }
}
