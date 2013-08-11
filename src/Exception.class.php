<?php

namespace net\shawn_huang\pretty;

class Exception extends \Exception {

    const CODE_HTTP_INTERNAL_ERROR              = 500;
    
    const CODE_HTTP_OK                          = 200;
    const CODE_HTTP_MODE_PERMANENTLY            = 301;
    const CODE_HTTP_NOT_MODIFIED                = 304;
    const CODE_HTTP_NOT_TEMPORARY               = 307;
    const CODE_HTTP_BAD_REQUEST                 = 400;
    const CODE_HTTP_UNAUTHORIZED                = 401;
    const CODE_HTTP_NOT_FOUND                   = 404;
    const CODE_HTTP_METHOD_NOT_ALLOWED          = 405;
    const CODE_HTTP_SERVICE_UNAVAILABLE         = 503;

    const CODE_PRETTY_UNKNOWN                   = 0xF000;
    const CODE_PRETTY_CLASS_NOTFOUND            = 0xF001;
    const CODE_PRETTY_CLASS_INIT_FAILED         = 0xF002;
    const CODE_PRETTY_FILE_NOTFOUND             = 0xF003;
    const CODE_PRETTY_ACTION_NOTFOUND           = 0xF004;
    const CODE_PRETTY_MISSING_CORE_CLASSES      = 0xF005;
    const CODE_PRETTY_VIEW_NOTFOUND             = 0XF006;
    const CODE_PRETTY_ACTION_ERROR              = 0xF007;

    const CODE_PRETTY_HTTP_STATUS               = 0xFFF1;

    private $httpCode = self::CODE_HTTP_OK;
    private $webResource = null;
    private $classLoader = null;

    /**
     * Create an http status exception which isnt 200
     * @param string $messageBody messages that will be sent to browser or client
     * @param int $code http status code
     * @return Exception instance of Exception
     */
    public static function createHttpStatus($httpCode = self::CODE_HTTP_INTERNAL_ERROR, $messageBody = 'Internal Error', $resource = null, $previous = null){
        $exp = new Exception($messageBody, self::CODE_PRETTY_HTTP_STATUS, $previous);
        $exp->setHttpCode($httpCode);
        $exp->setWebResource($resource);
        return $exp;
    }

    /**
     * Set http status code
     * @param int $code http status code
     */
    public function setHttpCode($code) {
        $this->httpCode = $code;
    }

    /**
     * Get http status code from this exception
     * @return int http status code
     */
    public function getHttpCode() {
        return $this->httpCode;
    }

    /**
     * Set target web resources
     * @param WebResource $webResource resouce
     */
    public function setWebResource($webResource) {
        $this->webResource = $webResource;
    }

    /**
     * Get target web resources
     * @return WebResource $webResource resouce
     */
    public function getWebResource() {
        return $this->webResource;
    }

    /**
     * Set current class loader if exits
     * @param ClassLoader $classLoader current classloader
     */
    public function setClassLoader($classLoader) {
        $this->classLoader = $classLoader;
    }

    /**
     * Get current class loader
     * @return ClassLoader class loader
     */
    public function getClassLoader() {
        return $this->classLoader;
    }

}