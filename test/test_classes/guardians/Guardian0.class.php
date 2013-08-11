<?php

namespace guardians;

use \net\shawn_huang\pretty\WebRequest;

class Guardian0 implements \net\shawn_huang\pretty\Guardian {

    public function guard(WebRequest $request) {
        echo 'OK-0';
        $i = $request->getExtra('count', 0);
        $i++;
        $request->putExtra('count', $i);
        if ($i == 5) {
            $request->rewrite($request->getUri(), WebRequest::REWRITE_FORWARD);
            return;
        }
        $request->rewrite($request->getUri(), WebRequest::REWRITE_REWIND);
    }    
}