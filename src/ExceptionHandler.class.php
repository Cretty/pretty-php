<?php

namespace net\shawn_huang\pretty;

/**
 * Default pretty exception handler
 */
class ExceptionHandler {

    private $classloader;

    /**
     * Handle exception
     * @param \Exception $exp exception
     */
    public function handleException($exp) {
        if (is_a($exp, '\net\shawn_huang\pretty\Exception')) {
            $this->handlePrettyExcepion($exp);
        } else {
            $this->handleOtherException($exp);
        }
    }

    /**
     * Set class loader
     * @param ClassLoader $loader class loader
     */
    public function setClassLoader($loader) {
        $this->classloader = $loader;
    }

    /**
     * Handle exception of pretty
     * @param Exception $exp the exception
     */
    public function handlePrettyExcepion($exp) {
        switch($exp->getCode()) {
            case Exception::CODE_PRETTY_HTTP_STATUS:
                $statusCode = $exp->getHttpCode();
                @header("http/1.1 {$statusCode}");
                $this->resolveView($exp);
        }
    }

    /**
     * Handle exception that was not from pretty
     * @param Exception $exp the exception
     */
    public function handleOtherException($exp) {
        header('http/1.1 500 Internal Error');
        echo $exp->__toString();
    }

    /**
     * Resovle the view if exception has one
     * @param Exception $exp the exception
     */
    protected function resolveView($exp) {
        $res = $exp->getWebResource();
        if ($res && $res->getView()) {
            try {
                Framework::instance()->display($res);
            } catch (Exception $e) {
                echo $e->__toString();
                echo $exp->__toString();
            }
        } else {
            echo $exp->getMessage();
        }
    }

}