<?php

namespace net\shawn_huang\pretty;

interface Router {
    public function findAction(ClassLoader $classLoader, $pathInfo);
    public function findFilters(ClassLoader $classLoader, $pathInfo, $action = null);
}