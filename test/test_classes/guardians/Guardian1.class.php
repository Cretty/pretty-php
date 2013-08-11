<?php

namespace guardians;

use \net\shawn_huang\pretty\WebRequest;

class Guardian1 implements \net\shawn_huang\pretty\Guardian {

    public function guard(WebRequest $request) {
        echo 'OK - 2';
        $request->terminate();
    }    
}