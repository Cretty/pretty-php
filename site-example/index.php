<?php
#
# Pretty php example site.
# 
include '../pretty/SiteConfig.class.php';

$config = new \net\shawn_huang\pretty\SiteConfig();
$config->setNsPrefix('\\my\\site');
$config->initPretty();
