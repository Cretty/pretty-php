<?php

#
# This is a Filter example.
#
namespace my\site\filter;
use \net\shawn_huang\pretty as p;

# The class name equels to you sub directory in action.
# This is the global filter.
# You can set a FooFilter to interrupt all the actions in action/foo/
class Filter implements p\Filter {

    public function beforeAction(p\Action $action, p\FilterChain $chain) {
        # you can change action status here
        # like $action->setStatus(p\Action::STATUS_END); to prevent excution.
        $action->put('before', 'we add something in fileter/Filter.class.php before action execution.');
        # also you can terminate the following filters by $chain->teminate();
    }    

    public function afterAction(p\Action $action, p\FilterChain $chain) {
        $action->put('after', 'we add something in fileter/Filter.class.php after action execution.');
    }    
}