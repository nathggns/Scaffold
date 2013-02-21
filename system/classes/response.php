<?php defined('SCAFFOLD') or die();

/**
 * Represents an HTTP response
 *
 * @author Claudio Albertin <claudio.albertin@me.com>
 */
class Response {

    /**
     * HTTP headers to send
     *
     * @var array
     */
    public $headers = [];

    /**
     * HTTP response body
     *
     * @var string
     */
    public $body = null;

    /**
     * Data to send
     *
     * @var mixed
     */
    public $data = null;

    /**
     * HTTP status code
     *
     * @var integer
     */
    public $code = 200;

    /**
     * Encoder used to build response body from data
     *
     * @var callable
     */
    public $encoder = 'json_encode';

    /**
     * HTTP status codes and their names
     *
     * @var array
     */
    public static $codes = [
        100 => 'Continue',
        101 => 'Switching Protocols',
        200 => 'OK',
        201 => 'Created',
        203 => 'Non-Authoritative Information',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found',
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        306 => '(Unused)',
        307 => 'Temporary Redirect',
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Timeout',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Request Entity Too Large',
        414 => 'Request-URI Too Long',
        415 => 'Unsupported Media Type',
        416 => 'Requested Range Not Satisfiable',
        417 => 'Expectation Failed',
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Timeout',
        505 => 'HTTP Version Not Supported'
    ];

    /**
     * Response sent or not
     *
     * @var boolean
     */
    protected $sent = false;

    /**
     * HTTP headers sent or not
     *
     * @var boolean
     */
    protected $headers_sent = false;

    /**
     * Add data to the response
     *
     * @param  mixed $data data
     * @return Response    this
     */
    public function data($data) {
        if (is_array($this->data) && is_array($data)) {
            $data = array_merge($this->data, $data);
        }

        $this->data = $data;

        return $this;
    }

    /**
     * Set data encoder
     *
     * @param  callable $encoder encoder
     * @return Response          this
     */
    public function encoder($encoder) {
        $this->encoder = $encoder;
        return $this;
    }

    /**
     * Run encoding
     *
     * @param  boolean $set set body
     * @return string       encoded data
     */
    public function encode($set = true) {
        $body = call_user_func($this->encoder, $this->data);

        if ($set) {
            $this->body = $body;
            return $this;
        } else {
            return $body;
        }
    }

    /**
     * Send response
     *
     * @return Response this
     */
    public function send() {
        // send only once
        if ($this->sent) return;

        // only encode data if no body is set
        if ($this->body === null) $this->encode();

        $this->header('Content-Type', 'application/json', false);

        $this->send_headers();
        echo $this->body;

        $this->sent = true;

        return $this;
    }

    /**
     * Set header
     *
     * @param  string   $key      name
     * @param  string   $value    value
     * @param  boolean  $override override
     * @return Response           this
     */
    public function header($key, $value, $override = true) {
        if ($override || !array_key_exists($key, $this->headers)) {
            $this->headers[$key] = $value;
        }

        return $this;
    }

    /**
     * Send headers
     *
     * @return Response this
     */
    public function send_headers() {
        // send only once
        if ($this->headers_sent || headers_sent()) return;

        foreach ($this->headers as $key => $value) {
            header($key . ': ' . $value);
        }

        header('HTTP/1.1 ' . $this->code . ' ' . self::$codes[$this->code]);

        $this->headers_sent = true;

        return $this;
    }

    /**
     * Redirect and exit script
     *
     * @param string  $location location
     * @param integer $code     HTTP status code
     */
    public function redirect($location, $code = 302) {
        header('Location: ' . $location, $code);
        exit;
    }

    /**
     * Send an error based on code.
     *
     * @param int $errco The http error code
     * @param string $message The message to send
     */
    public function error($errco, $message = false, $debug = false) {
        if (!$message) $message = static::$codes[$errco];
        $this->code = $errco;

        $body = [
            'error' => [
                'code' => $errco,
                'message' => $message
            ]
        ];

        if ($debug) {
            $body['error']['debug'] = [
                'type' => $debug[0],
                'file' => abs2rel($debug[2]) . ':' . $debug[3],
                'eror' => $debug[1]
            ];
        }

        $this->data($body);

        return $this;
    }
    

}
