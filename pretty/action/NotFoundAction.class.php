<?php

namespace net\shawn_huang\pretty\action;

use \net\shawn_huang\pretty as p;

class NotFoundAction extends p\Action {
    private $data;
    protected function run() {
        $log = p\Pretty::getLog();
        foreach($log as $k => $v) {
            $arr = explode(':', $k);
            switch (count($arr)) {
                case 2:
                    $this->data[$arr[0]][$arr[1]] = $v;
                    break;
                default:
                    $this->data[$k] = $v;
                    break;
            }
        }
        $this->setData($this->data);
        $this->setView('nofound', 'Debug');
    }

}