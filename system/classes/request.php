<?php defined('SCAFFOLD') or die;

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
    const CONSOLE = 'console';

    /**
     * HTTP methods supported by this class
     *
     * @var array
     */
    public static $supported_methods = [
        self::GET,
        self::POST,
        self::PUT,
        self::DELETE,
        self::HEAD,
        self::CONSOLE
    ];

    /**
     * Request URI
     *
     * @var string
     */
    protected $uri = null;

    /**
     * Query parameters
     *
     * @var array
     */
    protected $query = [];

    /**
     * Parsed CLI arguments
     *
     * @var array
     */
    protected $argv = [];

    /**
     * The regex pattern used to parse CLI arguments.
     *
     * Group one should be key, group two should be value
     *
     * @var string
     */
    protected static $argv_pattern = '/^(?:--(.*?)=)?(.+)$/i';

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
    protected $body = [];

    /**
     * HTTP request headers
     *
     * @var array
     */
    protected $headers = [];

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
    public function __construct($uri = null, $method = null) {
        $this->uri      = ($uri !== null) ? $uri : static::detect_uri();
        $this->method   = ($method !== null) ? $method : static::detect_request_method();
        $this->query    = static::detect_query();
        $this->body     = static::detect_body();
        $this->headers  = static::detect_headers();
        $this->argv     = static::detect_argv();
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
                // return property
                return $this->$method;

            case 2:
                // return element or default (also see next case)
                if (!array_key_exists($arguments[0], $this->$method)) return $arguments[1];

            case 1:
                // return element
                return $this->$method[$arguments[0]];

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

        if (isset($_SERVER['PATH_INFO'])) {
            return $_SERVER['PATH_INFO'];
        }

        if (CONSOLE) {
            $argv = static::detect_argv();

            if (isset($argv['uri'])) {
                return $argv['uri'];
            }
        }

        return '/';
    }

    /**
     * Detect and parse command line arguments
     */
    public static function detect_argv() {

        if (!CONSOLE) return [];

        $argv = array_slice($_SERVER['argv'], 1);

        $pattern = static::$argv_pattern;

        $parts = array_map(function($item) use ($pattern) {
            preg_match($pattern, $item, $matches);

            return array_slice($matches, 1);
        }, $argv);

        $args = [];

        foreach ($parts as $part) {
            if (!empty($part[0])) {
                $args[$part[0]] = $part[1];
            } else {
                $args[] = $part[1];
            }
        }

        return $args;
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
        if (isset($_SERVER['REQUEST_METHOD'])) {
            return strtolower($_SERVER['REQUEST_METHOD']);
        }

        if (CONSOLE) {
            return 'console';
        }

        return 'get';
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
            case 'console':
                // GET and CONSOLE requests don't have a body
                return [];

            case 'post':
            case 'put':
            case 'delete':
            default:
                $input = static::raw_body();
                parse_str($input, $body);
                return $body;
        }
    }

    /**
     * Get raw body of request
     *
     * @return string raw body
     */
    public static function raw_body() {
        return file_get_contents('php://input');
    }

    /**
     * Read-only access to properties
     *
     * @param  string $property property
     * @return mixed            value
     */
    public function __get($property) {
        return $this->$property;
    }

}
