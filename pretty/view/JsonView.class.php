<?php
namespace net\shawn_huang\pretty\view;
use \net\shawn_huang\pretty as p;

class JsonView implements p\View {

    public function render(p\Action $action) {
        $this->echoJson($action->getData());
    }

    public function echoHeader($jsonp = false) {
        $jsonp ? header('Content-Type:text/javascript')
        : header('Content-Type:application/json');
    }

    public function echoJson($params) {
        // $this->echoHeader(false);
        echo json_encode($params);
    }

    public function echoJsonp($params, $callback) {
        $this->echoJsonp(true);
        echo $callback, '(', json_encode($params), ');';
    }
}