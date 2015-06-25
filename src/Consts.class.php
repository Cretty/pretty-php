<?php

namespace net\shawn_huang\pretty;

class Consts {

    // Class
    const CONF_CLASS_NS = 'class.namespace';
    const CONF_CLASS_PATH = 'class.path';
    const CONF_CLASS_EXTRA_PATH = 'class.extraPath';
    const CONF_CLASS_ALIAS = 'class.alias';
    const CONF_CLASS_ALIAS_LIMIT = 'class.aliasLimit';
    const CONF_CLASS_LIB = 'class.lib';

    // Routers
    const CONF_ROUTER_MAPPINGS = 'router.mappings';
    const CONF_ROUTER_FILTER_LIMIT = 'router.filterLimits';
    const CONF_ROUTER_ACTION_NS = 'class.actionNamespace';
    const CONF_ROUTER_FILTER_NS = 'class.filterNamespace';
    const CONF_ROUTER_FALLBACK_LIMIT = 'class.maxFallbacks';
    const CONF_ROUTER_FORWARD_LIMIT = 'action.forwardLimits';

    // View
    const CONF_VIEW_MAPPINGS = 'view.mappings';
    const CONF_VIEW_DEFAULT = 'view.defaultView';
    const CONF_VIEW_DEFAULT_TYPE = 'view.defaultViewType';

    // Guardians
    const CONF_GUARD_MAPPINGS = 'guardians.mappings';
    const CONF_GUARD_REWIND_LIMIT = 'guardians.maxRewinds';
}

