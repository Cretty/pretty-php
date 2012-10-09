<?php

namespace net\shawn_huang\pretty\action;

use \net\shawn_huang\pretty as p;

class NotFoundAction extends p\Action {
    private $myData;
    protected function run() {
        $log = p\Pretty::getLog();
        foreach($log as $k => $v) {
            $arr = explode(':', $k);
            switch (count($arr)) {
                case 2:
                    $this->myData[$arr[0]][$arr[1]] = $v;
                    break;
                case 3:
                    if (PATH_SEPARATOR !== ':') {
                        $this->myData[$arr[0]][$arr[1] . $arr[2]] = $v;
                        break;
                    }
                default:
                    $this->myData[$k] = $v;
                    break;
            }
        }
        $this->setData($this->myData);
        $this->setView('nofound', 'Debug');
    }

}