<?php

class Request
{

    const GET    = 'get';
    const POST   = 'post';
    const PUT    = 'put';
    const DELETE = 'delete';
    const HEAD   = 'head';

    protected $uri      = null;
    protected $segments = [];

    protected $query   = [];
    protected $params  = [];
    protected $body    = [];
    protected $headers = [];

    protected $resource = null;
    protected $method   = null;

    /**
     * Gather data of the request
     *
     * @param string URI
     */
    public function __construct($uri = null) {
        $this->uri = ($uri !== null) ? $uri : self::detect_uri();

        $this->segments = self::parse_uri($this->uri);
        $this->resource = $this->segments[0];
        $this->method   = self::detect_request_method();

        $this->query   = $_GET;
        $this->body    = self::detect_body();
        $this->headers = self::detect_headers();
    }



    /**
     * Detect URI
     *
     * @return string URI
     */
    public static function detect_uri() {
        if (!empty($_SERVER['PATH_INFO'])) {
            $uri = $_SERVER['PATH_INFO'];
        } else {
            if (isset($_SERVER['REQUEST_URI'])) {
                $uri = rawurldecode($uri);
            } elseif (isset($_SERVER['PHP_SELF'])) {
                $uri = $_SERVER['PHP_SELF'];
            } elseif (isset($_SERVER['REDIRECT_URL'])) {
                $uri = $_SERVER['REDIRECT_URL'];
            }

            $base_url = parse_url(Kohana::$base_url, PHP_URL_PATH);

            if (strpos($uri, $base_url) === 0) {
                $uri = substr($uri, strlen($base_url));
            }
        }

        return $uri;
    }

    /**
     * Detect headers of request
     *
     * @return array Headers
     */
    public static function detect_headers() {
        if (function_exists('apache_request_headers')) return apache_request_headers();

        $headers = [];

        foreach ($_SERVER as $key => $value) {
            if (substr($key, 0, 5) !== 'HTTP_') continue;

            $key = str_replace(' ', '-', ucwords(str_replace('_', ' ', strtolower(substr($key, 5)))));
            $headers[$key] = $value;
        }

        return $headers;
    }

    /**
     * Detect request method
     *
     * @return string Request method
     */
    public static function detect_request_method() {
        return strtolower($_SERVER['REQUEST_METHOD']);
    }

    /**
     * Detect body of request
     *
     * @return array Body
     */
    public static function detect_body() {
        $method = self::detect_request_method();

        switch ($method) {
            case 'post':
                return $_POST;

            case 'get':
                return [];

            case 'post':
            case 'put':
            case 'delete':
            default:
                $body  = [];
                $input = file_get_contents('php://input');
                parse_str($input, $body);
                return $body;
        }
    }

}
