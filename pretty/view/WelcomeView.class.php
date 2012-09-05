<?php

namespace net\shawn_huang\pretty\view;
use \net\shawn_huang\pretty as p;

class WelcomeView implements p\View {

    public function render(p\Action $action = null)    {
        @header('content-type:text/html;charset=utf-8');
        $host = $_SERVER['SERVER_NAME'];
        $protocol = preg_match('/https/i', $_SERVER['SERVER_PROTOCOL']) ? 'https://' : 'http://';
        $prefix = $protocol . $host;
        include(dirname(__FILE__) . '/html/welcome.html');
    }
}