<?php

namespace my\site\filter;
use \net\shawn_huang\pretty as p;

class FooFilter implements p\Filter {

    public function beforeAction(p\Action $action, p\FilterChain $chain) {
        $action->put('filter before', 'before');
    }
    public function afterAction(p\Action $action, p\FilterChain $chain) {
        $action->put('filter after', 'after');
    }
}