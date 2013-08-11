<?php

namespace net\shawn_huang\pretty;

/**
 * Resolve view in Action.
 * Each type of view should defined in config['view.mappings'] by
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
                $viewType = Config::get('view.defaultViewType', 'json');
                break;
            default:
                $viewType = $view[0];
                break;
        }
        $viewMappings = Config::get('view.mappings');
        if (!isset($viewMappings[$viewType])) {
            throw new PrettyException(
                "Could not render view by type $viewType",
                PrettyException::CODE_PRETTY_VIEW_NOTFOUND);
        }
        $viewName = $viewMappings[$viewType];
        $view = $this->classLoader->load($viewName);
        if (!$view) {
            throw new PrettyException("Could not render view by $viewName, view class not found.",
                PrettyException::CODE_PRETTY_CLASS_NOTFOUND);
        }
        $view->render($res);
    }
}