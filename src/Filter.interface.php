<?php
namespace net\shawn_huang\pretty;


/**
 * A type of interrupter whiching will be called around
 * action execution
 */
interface Filter {
    
    /**
     * Do something before $action->run()
     */
    public function before(Action $action);

    /**
     * Do something after $action->run()
     */
    public function after(Action $action);
}