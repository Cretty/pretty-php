<?php

namespace net\shawn_huang\pretty;

/**
 * Resolve view in Action.
 * Each type of view should be Pretty expression or
 * be defined in config['view.mappings'] by
 * its name and expression
 * eg:
 * $config['view.mappings'] = array(
 *     'json' => '@%view.JsonView'
 * );
 */
class ViewResolver {

    public $classLoader = '@%ClassLoader';

    /**
     * Render and display the action
     * @param WebResouce $res the target action
     */
    public function display(WebResource $res) {
        $view = $res->getView() ?: array();
        switch(count($view)) {
            case 0:
            case 1:
                $viewType = Config::get(Consts::CONF_VIEW_DEFAULT_TYPE, 'json');
                break;
            default:
                $viewType = $view[0];
                break;
        }
        $viewMappings = Config::get(Consts::CONF_VIEW_MAPPINGS);
        $viewName = Arrays::valueFrom($viewMappings, $viewType, $viewType);
        $view = $this->classLoader->load($viewName, true, false);
        if (!$view) {
            throw new Exception("Could not render view by $viewName, view class not found.",
                Exception::CODE_PRETTY_CLASS_NOTFOUND);
        }
        $view->render($res);
    }
}