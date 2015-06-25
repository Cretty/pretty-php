<?php

# First step: load the pretty file.

## For production
// require_once '../dist/v.inc.php';

# For debugging
require_once '../src/v.inc.php';

use \net\shawn_huang\pretty;
use \net\shawn_huang\pretty\Consts;

# Make a configuration which at least conains the class
# path of your project.
$config = array(
    Consts::CONF_CLASS_PATH => __DIR__ . '/class',
);


# Binding Pretty V into root namespace so that you can use V::xxx directly.
# If you like to bind other Class name instead of "V"
# just use pretty\V::bind('TheNameYouLike');
pretty\V::bind();

# Get the instance of framework, then call the start method
# That all, the basic useage of pretty-php ver 4.
pretty\Framework::instance($config)->start();