<?php

#
# New Pretty V style!
# Extension .v.php
# Example:
#   New V style with the same function of Index.class.php
#

V::run(function($a) {
    $a->put('hello', 'world');
    $a->setView('json');
});
