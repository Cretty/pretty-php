<?php

namespace net\shawn_huang\pretty;

/**
 * View is a type of action renderer whitch convert the resource
 * data into the right format you need.
 */
interface View {

    /**
     * Render the resource
     * @param WebResource $res the target resource
     */
    public function render(WebResource $res);
}