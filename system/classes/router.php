<?php defined('SCAFFOLD') or die;

/**
 * Handles routes
 *
 * @author Claudio Albertin <claudio.albertin@me.com>
 */
class Router {

    /**
     * Array of defined routes
     *
     * @var array
     */
    protected $routes  = [];

    /**
     * Array of hooks executed at the end of Router::run()
     *
     * @var array
     */
    protected $hooks = [];

    /**
     * Add a custom GET route
     *
     * @param  string $path     path
     * @param  mixed  $target   target
     * @param  array  $defaults defaults
     * @return Router           this
     */
    public function get($path, $target = null, array $defaults = []) {
        return $this->add_route(Request::GET, $path, $target, $defaults);
    }

    /**
     * Add a custom POST route
     *
     * @param  string $path     path
     * @param  mixed  $target   target
     * @param  array  $defaults defaults
     * @return Router           this
     */
    public function post($path, $target = null, array $defaults = []) {
        return $this->add_route(Request::POST, $path, $target, $defaults);
    }

    /**
     * Add a custom PUT route
     *
     * @param  string $path     path
     * @param  mixed  $target   target
     * @param  array  $defaults defaults
     * @return Router           this
     */
    public function put($path, $target = null, array $defaults = []) {
        return $this->add_route(Request::PUT, $path, $target, $defaults);
    }

    /**
     * Add a custom DELETE route
     *
     * @param  string $path     path
     * @param  mixed  $target   target
     * @param  array  $defaults defaults
     * @return Router           this
     */
    public function delete($path, $target = null, array $defaults = []) {
        return $this->add_route(Request::DELETE, $path, $target, $defaults);
    }

    /**
     * Add a custom HEAD route
     *
     * @param  string $path     path
     * @param  mixed  $target   target
     * @param  array  $defaults defaults
     * @return Router           this
     */
    public function head($path, $target = null, array $defaults = []) {
        return $this->add_route(Request::HEAD, $path, $target, $defaults);
    }

    /**
     * Add a custom CONSOLE route
     *
     * @param  string $path     path
     * @param  mixed  $target   target
     * @param  array  $defaults defaults
     * @return Router           this
     */
    public function console($path, $target = null, array $defaults = []) {
        return $this->add_route(Request::CONSOLE, $path, $target, $defaults);
    }

    /**
     * Add a custom route for all methods
     *
     * @param  string $path     path
     * @param  mixed  $target   target
     * @param  array  $defaults defaults
     * @return Router           this
     */
    public function all($path, $target = null, array $defaults = []) {
        $supported_methods = array_reverse(Request::$supported_methods);

        foreach ($supported_methods as $method) {
            $this->add_route($method, $path, $target, $defaults);
        }

        return $this;
    }

    /**
     * Add a custom route
     *
     * @param  string $method   HTTP method
     * @param  string $path     path
     * @param  mixed  $target   target
     * @param  array  $defaults defaults
     * @return Router           this
     */
    public function add_route($method, $path, $target = null, array $defaults = []) {
        array_unshift($this->routes, [
            'method'   => $method,
            'path'     => $path,
            'target'   => $target,
            'defaults' => $defaults
        ]);

        return $this;
    }

    /**
     * Add hook executed at the end of Router::run()
     *
     * @param  callable $hook hook
     * @return Router        this
     */
    public function add_hook($hook) {
        if (is_callable($hook)) $this->hooks[] = $hook;

        return $this;
    }

    /**
     * Run hooks
     *
     * @param  mixed  $controller controller
     * @return Router             this
     */
    public function run_hooks($controller) {
        foreach ($this->hooks as $hook) {
            call_user_func($hook, $controller);
        }

        return $this;
    }

    /**
     * Turn a route into a regex
     *
     * @param  string $route route
     * @return string        regex
     */
    public static function prepare_route($route) {
        $escaped_route = preg_quote($route, '/');
        $escaped_route = str_replace('\:', ':', $escaped_route);
        $escaped_route = str_replace('\?', '?', $escaped_route);

        $allowed = ['\w', '\-', '_', '\.', '\!', '\~', '\*', '\'', '\(', '\)'];
        $default = '[' . implode('', $allowed) . ']+';
        $regex = preg_replace('/\\\\\/:\?([a-z]+)/', '(?:\/(' . $default . '))?', $escaped_route);
        $regex = preg_replace('/:([a-z]+)/', '(' . $default . ')', $regex);
        $regex = '/^' . $regex . '$/';

        return $regex;
    }

    /**
     * Find a route that matches the given URI
     *
     * @param  string $uri    URI
     * @param  string $method HTTP method
     * @return array          route
     */
    public function find_route($uri, $method = null) {
        if ($method === null) {
            // $uri is a request object
            $method = $uri->method;
            $uri    = $uri->uri;
        }

        // last matching route takes priority
        $routes = array_reverse($this->routes);

        foreach ($routes as $route) {
            // skip route if method doesn't match
            if ($route['method'] !== $method) continue;

            // get regex of route
            $regex = static::prepare_route($route['path']);

            // return route if it matches
            if (preg_match($regex, $uri)) return $route;
        }

        return false;
    }

    /**
     * Parse a URI with a given route
     *
     * @param  string $uri   URI
     * @param  string $route route
     * @return array         params
     */
    public static function parse_uri($uri, $route) {
        // get regex of route
        $regex = static::prepare_route($route);

        // search for matches
        preg_match_all($regex, $uri, $values);
        preg_match_all('/:\??([a-z]+)/', $route, $names);

        // remove full matches
        array_shift($values);
        $names = $names[1];

        // extract values and cast numbers to integers
        $values = array_map(function($item) {
            return is_numeric($item[0]) ? (int) $item[0] : (empty($item[0]) ? null : $item[0]);
        }, $values);

        return array_combine($names, $values);
    }

    /**
     * Call the corresponding controller
     *
     * @param  Request $request Request
     * @return Router           this
     */
    public function run(Request $request = null, Response $response = null) {
        $request  = ($request !== null) ? $request : Service::get('request');
        $response = ($response !== null) ? $response : Service::get('response');

        $route = $this->find_route($request);

        if (!$route) static::throw_error($request->method, $request->uri);

        // parse URI and add defaults
        $params = static::parse_uri($request->uri, $route['path']);
        foreach ($route['defaults'] as $key => $val) {
            if (!isset($params[$key])) $params[$key] = $val;
        }

        $request->params = $params;

        if (is_callable($route['target'], false)) {
            // target is callable
            $controller = call_user_func($route['target'], $request, $response);

            $this->run_hooks($controller);
            return $controller;
        } else {
            if ($route['target'] === null) {
                if (isset($request->params['controller'])) {
                    $controller = $request->params['controller'];

                    if (isset($request->params['resource'])) {
                        $controller = Inflector::singularize($controller) . ucfirst($request->params['resource']);
                    }

                    $action = $request->method;
                } else {
                    static::throw_error($request->method, $request->method);
                }
            } else {
                // target is dot notated
                $segments = explode('.', $route['target']);

                $controller = $segments[0];
                $action     = (isset($segments[1])) ? $segments[1] : $request->method;
            }

            // invoke controller
            $instance = Service::get('controller', $controller, $request, $response);

            if (!$instance) {
                static::throw_error($request->method, $request->uri);
            }

            // call `before` event
            $instance->before();

            if (isset($request->params['id'])) {
                // call resource loader
                $resource = $instance->resource($request->params['id']);
                if ($resource) $instance->$controller = $resource;
            }

            // call action
            $instance->$action();

            // call `after` event
            $instance->after();

            $this->run_hooks($instance);
            return $instance;
        }
    }

    /**
     * Throw routing error
     *
     * @param  string    $method HTTP method
     * @param  string    $uri    URI
     * @throws ExceptionRouting
     */
    public static function throw_error($method, $uri) {
        throw new ExceptionRouting('Cannot ' . strtoupper($method) . ' ' . $uri);
    }

}
