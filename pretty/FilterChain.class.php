<?php

namespace net\shawn_huang\pretty;

class FilterChain {

    const TYPE_BEFORE = 1;
    const TYPE_AFTER = 0;
    private $status = true;
    private $filterArray;
    private $type;
    private $action;

    public function __construct($array, $action, $type) {
        $this->filterArray = $array;
        $this->action = $action;
        $this->type = $type;
    }

    public function next() {
        return $this->status ? array_pop($this->filterArray) : null;
    }    

    public function terminate() {
        $this->status = false;
    }

    public function doFilter() {
        $filter = $this->next();
        $fun = $this->type ? 'beforeAction' : 'afterAction';
        if (!$filter) {
            return;
        }
        switch ($this->action->getStatus()) {
            case Action::STATUS_END:
            return;
        }
        $filter->$fun($this->action, $this);
        $this->doFilter();
    }
}