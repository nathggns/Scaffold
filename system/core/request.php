<?php defined('SCAFFOLD') or die();

class Request
{

    const GET    = 'get';
    const POST   = 'post';
    const PUT    = 'put';
    const DELETE = 'delete';
    const HEAD   = 'head';

    public $uri      = null;
    public $segments = [];

    public $query   = [];
    public $params  = [];
    public $body    = [];
    public $headers = [];

    protected $method = null;

    /**
     * Gather data of the request
     *
     * @param string URI
     */
    public function __construct($uri = null) {
        $this->uri = ($uri !== null) ? $uri : self::detect_uri();

        $this->segments = self::parse_uri($this->uri);
        $this->method   = self::detect_request_method();

        $this->params['resource'] = $this->segments[0];

        $this->query   = $_GET;
        $this->body    = self::detect_body();
        $this->headers = self::detect_headers();
    }

    /**
     * Syntactic sugar to get data or a default value
     *
     * @param string $method property name
     * @param array  $arguments array with key and eventually a default value
     * @return mixed data
     */
    public function __call($method, array $arguments) {
        switch (count($arguments)) {
            case 0:
                // Return property
                $value = $this->$method;
                break;

            case 2:
                // Return key or default
                if (empty($this->$method[$arguments[0]])) return $arguments[1];

            case 1:
                // Return key
                $value = $this->$method[$arguments[0]];
                break;

            default:
                $value = null;
        }

        return $value;
    }

    /**
     * Getter for resource, id and method
     *
     * @param string $key property to get
     * @return mixed value
     */
    public function __get($key) {
        switch ($key) {
            case 'resource':
            case 'id':
                return $this->params[$key];

            case 'method':
                return $this->method;

            default:
                return null;
        }
    }

    /**
     * Detect URI
     *
     * @return string URI
     */
    public static function detect_uri() {
        return isset($_SERVER['PATH_INFO']) ? $_SERVER['PATH_INFO'] : '/';
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
