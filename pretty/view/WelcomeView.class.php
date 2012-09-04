<?php

namespace net\shawn_huang\pretty\view;
use \net\shawn_huang\pretty as p;

class WelcomeView implements p\View {

    public function render(p\Action $action = null)    {
        @header('content-type:text/html;charset=utf-8');
        echo file_get_contents(dirname(__FILE__) . '/html/welcome.html');
    }
}