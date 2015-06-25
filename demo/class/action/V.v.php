<?php

V::run(function($a) {
    $a->put('Hello', 'Pretty - V');
    $a->setView('json');
});
