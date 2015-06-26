<?php

namespace net\shawn_huang\pretty;
/**
 * The http request wrapper, which provides several operation that
 * the framework need.
 */
class WebRequest {

    /**
     * The rewrite mode: ignore the following guardians.
     */
    const REWRITE_LAST = 0;

    /**
     * The rewrite mode: go on matching the rewriter from the rest
     * guardians.
     */
    const REWRITE_FORWARD = 1;

    /**
     * The rewrite mode: Go back to and try to match the rewriter
     * from the first guardians.
     */
    const REWRITE_REWIND = -1;

    /**
     * The rewrite mode: Terminate the whole process.
     * The target action and filters even if available will not be
     * activate.
     */
    const REWRITE_TERMINATE = -2;

    private $originUri;
    private $uri;
    private $code = self::REWRITE_FORWARD;

    private $guardians;
    private $extra = array();

    /**
     * Default constructor, try to find the real client
     * ip and store it in $this->extra['ip']
     */
    public function __construct() {
        $this->initUri();
        $ip = Arrays::valueFrom($_SERVER, 'X-Forward-Ip');
        if (!$ip) {
            $ip = Arrays::valueFrom($_SERVER, 'REMOTE_ADDR', '');
        }
        $this->extra['ip'] = $ip;
    }

    /**
     * Parse the request uri. First PATH_INFO, then ORIG_PATH_INFO, otherwise REQUEST_URI
     */
    private function initUri() {
        $uri = Arrays::valueFrom($_SERVER, 'PATH_INFO') ?:
            Arrays::valueFrom($_SERVER, 'ORIG_PATH_INFO');
        if ($uri === null) {
            if (isset($_SERVER['REQUEST_URI'])) {
                if(preg_match('/\.php(\/?.*)/', $_SERVER['REQUEST_URI'], $matchers)) {
                    $uri = $matchers[1] ?: '/';
                } else {
                    $uri = $_SERVER['REQUEST_URI'];
                }
            } else {
                throw Exception::createHttpStatus('Cannot build request, none of these environments[PATH_INFO, ORIG_PATH_INFO, REQUEST_URI] exists.', 404);
            }
        }
        $this->originUri = $uri;
        if ($uri == '/') {
            $this->uri = Config::get('site.index', '/index');
        } else {
            $this->uri = $uri;
        }
    }

    /**
     * Rewrite the request uri
     * @param string $uri the forward uri
     * @param int $code the rewrite code mode
     */
    public function rewrite($uri, $code = self::REWRITE_FORWARD) {
        $this->uri = $uri;
        $this->code = $code;
    }

    /**
     * Terminate the framework process
     * The same as $this->rewirte(this->uri, self::REWRITE_TERMINATE)
     */
    public function terminate() {
        $this->code = self::REWRITE_TERMINATE;
    }

    /**
     * Output the HTTP status code and the message body
     * @param $code the HTTP status code
     * @param $messageBody message body
     */
    public function httpError($code, $messageBody) {
        header("http/1.1 $code");
        echo $messageBody;
    }

    /**
     * Get the rewrite code
     * @return int rewrite code, by default, this code
     * is set to be REWRITE_FORWARD
     */
    public function getCode() {
        return $this->code;
    }

    /**
     * The uri before rewrite
     * @return string the uri
     */
    public function getOriginUri() {
        return $this->originUri;
    }

    /**
     * The uri after rewrite
     * @return string the uri
     */
    public function getUri() {
        return $this->uri;
    }


    public function putExtra($key, $value) {
        $this->extra[$key] = $value;
    }

    public function getExtra($key, $default = null) {
        if ($key === null) {
            return $this->extra;
        }
        return Arrays::valueFrom($this->extra, $key, $default);
    }

}