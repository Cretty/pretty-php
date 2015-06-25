<?php

# First step: load the pretty file.

require_once '../dist/pretty4.inc.php';

use \net\shawn_huang\pretty;

# Make a configuration which at least conains the class
# path of your project.

$config = array(
    'class.path' => __DIR__ . '/class',
);

# Get the instance of framework, then call the start method
# That all, the basic useage of pretty-php ver 4.
pretty\V::bind();
pretty\Framework::instance($config)->start();