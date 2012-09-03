<?php

#
# Pretty php hello world action.
namespace my\site\action; # set your name space.
use \net\shawn_huang\pretty as p; # Import pretty namespace.

class HelloWorldAction extends p\Action {
    
    protected function run() {
        # The default view will render data into json format.
        # You can add your custom view in ${yoursite}/view/MyView.class.php witch implements net\shawn_huang\pretty\View
        #   then use $this->setView('view name', 'MyView');
        $this->put('hello', 'world'); # Put all your data to View.
    }
}
