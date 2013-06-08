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
     * @var string
     */
    protected static $argv_pattern = <<<'EOT'
/
    (?:                                                     # start parameter
        (?:                                                     # start key value section
            (?<keys>                                                # start key
                (?:                                                     # start long key
                    --[^=\s]+                                               # long key
                )                                                       # end long key
                |
                (?:                                                     # start short key
                    -[^=]                                                   # short key
                )                                                       # end short key
            )                                                       # end key

            (?:                                                     # start value
                [=\s]                                                   # key value seperator
                (?<values>                                               # start real value
                    (?:                                                     # start simple values (not inside quotes)
                        \\.                                                     # escaped characters
                        |
                        [^\s\-"']                                               # normal characters
                    )+                                                      # end simple values
                    |
                    (?:                                                     # start advances values (inside quotes)
                        (["'])                                                  # start quote
                        (?:
                            \\.                                                     # escaped characters
                            |
                            (?!\3)                                                  # any character
                            .                                                       # besides end quote
                        )*?  
                        \3                                                      # end quote
                    )                                                       # end adances
                )
            )?                                                       # end value
        )                                                        # end key val section
        |
        (?<others>                                               # start keyless parameter section
            (?:                                                      # start simple section (not inside quotes)
                (?:
                    \\.                                                  # escaped characters
                    |
                    [^\s\-"']                                            # normal characters
                )+
            )                                                        # end simple section
            |
            (?:                                                      # start advanced values (inside quotes)
                (["'])                                                   # start quote
                (?:
                    \\.                                                      # escaped character
                    |
                    (?!\5)                                                   # anything but end quote
                    .
                )*?
                \5                                                       # end quote
            )                                                        # end advanced values
        )                                                        # end key less parameter section
    )                                                        # end parameter

    \s?                                                      # parameter seperator
/x
EOT;

    /**
     * The function to handle the results from the regex pattern matching
     *
     * @var Closure
     */
    protected static $argv_callback;

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
     *
     * @param string $pattern Expression used to decode arguments.
     */
    public static function detect_argv($pattern = null, $closure = null) {

        if (!CONSOLE) return [];

        if (is_null($closure)) {
            $closure = static::get_argv_callback();
        }

        if (is_null($pattern)) {
            $pattern = static::get_argv_pattern();
        }

        $argv = array_slice($_SERVER['argv'], 1);
        $string = implode(' ', $argv);

        preg_match_all($pattern, $string, $matches);

        $params = call_user_func($closure, $matches);
       
        return $params;
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

    /**
     * Getter for argv_pattern
     *
     * @return string argv_pattern
     */
    public static function get_argv_pattern() {
        return static::$argv_pattern;
    }

    /**
     * Setter for argv_pattern
     *
     * @param string $pattern New argv_pattern
     */
    public static function set_argv_pattern($pattern) {
        static::$argv_pattern = $pattern;
    }

    /**
     * Getter for argv_callback
     * 
     * @return Closure argv_callback
     */
    public static function get_argv_callback() {
        if (!static::$argv_callback) {
            static::$argv_callback = function($matches) {
                $matches['keys'] = array_map(function($item) {
                    return ltrim($item, '-');
                }, $matches['keys']);

                $matches['others'] = array_filter($matches['others'], function($item) {
                    return !empty($item);
                });

                $matches['values'] = array_map(function($item) {

                    if (
                        ($item[0] === '"' || $item[0] === chr(39)) &&
                        $item[0] === substr($item, -1)
                    ) {
                        $item = substr($item, 1, strlen($item) - 2);
                    }

                    $item = preg_replace('/\\\\(.)/', '$1', $item);

                    return $item;
                }, $matches['values']);

                $matches['others'] = array_map(function($item) {
                    $item = preg_replace('/\\\\(.)/', '$1', $item);

                    return $item;
                }, $matches['others']);

                $params = array_merge($matches['others'], array_combine($matches['keys'], $matches['values']));

                unset($params['']);

                return $params;
            };
        }

        return static::$argv_callback;
    }

    /**
     * Setter for argv_callback
     *
     * @param closure Closure new argv_callback
     */
    public static function set_argv_callback($callback) {
        static::$argv_callback = $callback;
    }
}
