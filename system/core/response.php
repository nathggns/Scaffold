<?php

class Response
{

    protected $headers = [];
    protected $body    = null;
    protected $code    = 200;

    public static $codes = [
        100 => "Continue",
        101 => "Switching Protocols",
        200 => "OK",
        201 => "Created",
        203 => "Non-Authoritative Information",
        204 => "No Content",
        205 => "Reset Content",
        206 => "Partial Content",
        300 => "Multiple Choices",
        301 => "Moved Permanently",
        302 => "Found",
        303 => "See Other",
        304 => "Not Modified",
        305 => "Use Proxy",
        306 => "(Unused)",
        307 => "Temporary Redirect",
        400 => "Bad Request",
        401 => "Unauthorized",
        402 => "Payment Required",
        403 => "Forbidden",
        404 => "Not Found",
        405 => "Method Not Allowed",
        406 => "Not Acceptable",
        407 => "Proxy Authentication Required",
        408 => "Request Timeout",
        409 => "Conflict",
        410 => "Gone",
        411 => "Length Required",
        412 => "Precondition Failed",
        413 => "Request Entity Too Large",
        414 => "Request-URI Too Long",
        415 => "Unsupported Media Type",
        416 => "Requested Range Not Satisfiable",
        417 => "Expectation Failed",
        500 => "Internal Server Error",
        501 => "Not Implemented",
        502 => "Bad Gateway",
        503 => "Service Unavailable",
        504 => "Gateway Timeout",
        505 => "HTTP Version Not Supported"
    ];

    protected $sent         = false;
    protected $headers_sent = false;

    /**
     * Send response
     */
    public function send() {
        if ($this->sent) return;

        $this->send_headers();
        echo $this->body;

        $this->sent = true;
    }


    public function header($key, $value) {
        $this->headers[$key] = $value;
        return $this;
    }


    public function send_headers() {
        if ($this->headers_sent) return;

        foreach ($this->headers as $key => $value) {
            header ($key . ': ' . $value);
        }

        header('HTTP/1.1 ' . $this->code . ' ' . self::$codes[$this->code]);

        $this->headers_sent = true;
    }

    public function redirect($location, $code = 302) {
        header('Location: ' . $location, $code);
        exit;
    }

}
