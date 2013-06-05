<?php

class RequestTest extends PHPUnit_Framework_Testcase {

    public function setUp() {
        $this->request = new Request();
    }

    /**
     * @covers Request::detect_uri
     */
    public function testDetectUri() {
        $_SERVER['PATH_INFO'] = '/foo/bar';
        $this->assertEquals(Request::detect_uri(), '/foo/bar');

        unset($_SERVER['PATH_INFO']);
        $this->assertEquals(Request::detect_uri(), '/');
    }

    /**
     * @covers Request::detect_query()
     */
    public function testDetectQuery() {
        $_GET['foo'] = 'bar';

        $this->assertEquals(Request::detect_query(), [
            'foo' => 'bar'
        ]);
    }

    /**
     * @covers Request::detect_headers
     */
    public function testDetectHeaders() {
        $_SERVER['HTTP_X_Test'] = 'test-header';

        $headers = Request::detect_headers();

        $this->assertArrayHasKey('X-Test', $headers);
        $this->assertEquals($headers['X-Test'], 'test-header');
    }

    /**
     * @covers Request::detect_request_method
     */
    public function testDetectRequestMethod() {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $this->assertEquals(Request::detect_request_method(), 'post');

        unset($_SERVER['REQUEST_METHOD']);
        $this->assertEquals(Request::detect_request_method(), 'console');
    }

    /**
     * @covers  Request::detect_body
     * @depends testDetectRequestMethod
     */
    public function testDetectBody() {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $this->assertEquals(Request::detect_body(), []);

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $this->assertEquals(Request::detect_body(), $_POST);
    }

    /**
     * @covers Request::__call
     * @covers Request::__get
     */
    public function testGetter() {
        $this->assertInternalType('string', $this->request->uri());
        $this->assertEquals($this->request->params('foo', 'default'), 'default');
        $this->assertNull($this->request->params(1, 2, 3));

        $this->assertInternalType('string', $this->request->method);
    }

    /**
     * @covers Request::__construct
     */
    public function testConstructor() {
        $uri     = '/one/two/three';
        $method  = 'delete';
        $request = new Request($uri, $method);

        $this->assertEquals($request->uri, $uri);
        $this->assertEquals($request->method, $method);
    }

    /**
     * @covers Request::detect_argv
     */
    public function testArgv() {

        $_argv = $_SERVER['argv'];

        $_SERVER['argv'] = [
            'THIS GETS REMOVED',
            'abcdefg',
            '--ab=def',
            '--ab=fe',
            'acdef',
            '--xz=cy'
        ];

        $argv = Request::detect_argv();

        $_SERVER['argv'] = $_argv;

        $this->assertEquals([
            'abcdefg',
            'ab' => 'fe',
            'acdef',
            'xz' => 'cy'
        ], $argv);
    }

}
