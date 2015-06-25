<?php

/**
 * New Pretty V style!
 * Simple and clear.
 * Put a callback with a parameter $action into V::run();
 */

V::run(function($a) {
    $a->put('Hi', 'Pretty - V');
    $a->setView('json');
});
