<?php
namespace net\shawn_huang\pretty\view;
use \net\shawn_huang\pretty as p;
use \net\shawn_huang\pretty\Config;

class JsonView implements p\View {

    public function render(p\WebResource $action) {
        $view = $action->getView();
        $jsonp = Config::get('view.json.jsonp');
        if (isset($view[1])) {
            $jsonp = $view[1] ?: null;
        }
        if ($jsonp && ($callback = $action->get($jsonp))) {
            $this->echoJsonp($action->getData(), $callback);
        } else {
            $this->echoJson($action->getData());
        }
    }

    public function echoHeader($jsonp = false) {
        $jsonp ? @header('Content-Type:text/javascript')
        : @header('Content-Type:application/json');
    }

    public function echoJson($params) {
        $this->echoHeader(false);
        echo json_encode($params);
    }

    public function echoJsonp($params, $callback) {
        $this->echoHeader(true);
        echo $callback, '(', json_encode($params), ');';
    }
}