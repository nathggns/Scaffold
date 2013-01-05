<?php

class RouterTest extends PHPUnit_Framework_Testcase {

    protected $router = null;

    public function setUp() {
        $this->router = new Router();
    }

    /**
     * @covers Router::add_hook
     * @covers Router::run_hooks
     */
    public function testHooks() {
        $controller = (object) [
            'test' => 'untouched'
        ];

        $this->router->add_hook(function($controller) {
            $this->assertInstanceOf('stdClass', $controller);
            $controller->test = 'touched';
        });

        $this->router->run_hooks($controller);

        $this->assertEquals($controller->test, 'touched');
    }

    /**
     * @covers Router::prepare_route
     */
    public function testPrepareRoute() {
        $route = '/:one/:two/:three.:four';
        $regex = Router::prepare_route($route);

        $section = '([\w\-_\.\!\~\*\'\(\)]+)';
        $match = '/^' . str_repeat('\/' . $section, 3) . '\.' . $section . '$/';

        $this->assertEquals($regex, $match);
    }

    /**
     * @covers  Router::parse_uri
     * @depends testPrepareRoute
     */
    public function testParseUri() {
        $route = '/:one/:two/:three.:four';
        $uri   = '/foo/5/baz.bla';

        $params = Router::parse_uri($uri, $route);

        $this->assertEquals($params, [
            'one'   => 'foo',
            'two'   => 5,
            'three' => 'baz',
            'four'  => 'bla'
        ]);
    }

    /**
     * @covers                   Router::throw_error
     * @expectedException        ExceptionRouting
     * @expectedExceptionMessage Cannot GET /
     */
    public function testThrowError() {
        Router::throw_error('get', '/');
    }

    /**
     * @covers  Router::run
     * @covers  Router::get
     * @covers  Router::post
     * @covers  Router::put
     * @covers  Router::delete
     * @covers  Router::head
     * @covers  Router::add_route
     * @depends testParseUri
     * @depends testHooks
     */
    public function testRun() {
        foreach (Request::$supported_methods as $method) {
            $this->router->$method('/', function($request, $response) use ($method) {
                $this->assertInstanceOf('Request', $request);
                $this->assertInstanceOf('Response', $response);

                return $method;
            });
        }

        foreach (Request::$supported_methods as $method) {
            $request  = new Request('/', $method);
            $response = $this->router->run($request);

            $this->assertEquals($method, $response);
        }
    }

}
