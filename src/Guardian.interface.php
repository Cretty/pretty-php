<?php

namespace net\shawn_huang\pretty;

/**
 * An guadian interface that starts before every process.
 */
interface Guardian {

    /**
     * Pre-process request
     * @param WebRequest $request request
     */
    public function guard(WebRequest $request);
}