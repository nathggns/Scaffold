<?php defined('SCAFFOLD') or die();

/**
 * Represents an HTTP request
 *
 * @author Claudio Albertin <claudio.albertin@me.com>
 */
class Request {

    const GET    = 'get';
    const POST   = 'post';
    const PUT    = 'put';
    const DELETE = 'delete';
    const HEAD   = 'head';

    /**
     * HTTP methods supported by this class
     *
     * @var array
     */
    public static $supported_methods = [
        static::GET,
        static::POST,
        static::PUT,
        static::DELETE,
        static::HEAD
    ];

    /**
     * Request URI
     *
     * @var string
     */
    public $uri = null;

    /**
     * Query parameters
     *
     * @var array
     */
    public $query = [];

    /**
     * Route parameters
     *
     * @var array
     */
    public $params = [];

    /**
     * HTTP request body
     *
     * @var array
     */
    public $body = [];

    /**
     * HTTP request headers
     *
     * @var array
     */
    public $headers = [];

    /**
     * HTTP method
     *
     * @var string
     */
    protected $method = null;

    /**
     * Gather data of the request
     *
     * @param string URI
     */
    public function __construct($uri = null) {
        $this->uri      = ($uri !== null) ? $uri : static::detect_uri();
        $this->method   = static::detect_request_method();
        $this->query    = static::detect_query();
        $this->body     = static::detect_body();
        $this->headers  = static::detect_headers();
    }

    /**
     * Syntactic sugar to get data or a default value
     *
     * @param  string $method    property name
     * @param  array  $arguments array with key and eventually a default value
     * @return mixed             data
     */
    public function __call($method, array $arguments) {
        switch (count($arguments)) {
            case 0:
                // Return property
                return $this->$method;

            case 2:
                // Return key or default (also see next case)
                if (empty($this->$method[$arguments[0]])) return $arguments[1];

            case 1:
                // Return key
                return $this->$method[$arguments[0]];

            default:
                return null;
        }
    }

    /**
     * Getter for resource, id and method
     *
     * @param  string $key property to get
     * @return mixed       value
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
     * Detect query
     *
     * @return array query
     */
    public static function detect_query() {
        return $_GET;
    }

    /**
     * Detect headers of request
     *
     * @return array headers
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
     * @return array body
     */
    public static function detect_body() {
        $method = static::detect_request_method();

        switch ($method) {
            case 'post':
                return $_POST;

            case 'get':
                // GET requests don't have a body
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
