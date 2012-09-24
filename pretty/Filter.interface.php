<?php

namespace net\shawn_huang\pretty;

interface Filter {
    public function beforeAction(Action $action, FilterChain $chain);
    public function afterAction(Action $action, FilterChain $chain);
}