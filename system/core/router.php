<?php defined('SCAFFOLD') or die();

class Router {

    protected $routes  = [];
    protected $request = null;

    /**
     * Add a custom GET route
     *
     * @param string $path           path
     * @param mixed  $target         target
     * @param array  $values         values for params
     * @param bool   $case_sensitive case sensitive?
     * @return object this
     */
    public function get($path, $target, array $values = [], $case_sensitive = false) {
        return $this->add_route($path, $target, $values, Request::GET, $case_sensitive);
    }

    /**
     * Add a custom POST route
     *
     * @param string $path           path
     * @param mixed  $target         target
     * @param array  $values         values for params
     * @param bool   $case_sensitive case sensitive?
     * @return object this
     */
    public function post($path, $target, array $values = [], $case_sensitive = false) {
        return $this->add_route($path, $target, $values, Request::POST, $case_sensitive);
    }

    /**
     * Add a custom PUT route
     *
     * @param string $path           path
     * @param mixed  $target         target
     * @param array  $values         values for params
     * @param bool   $case_sensitive case sensitive?
     * @return object this
     */
    public function put($path, $target, array $values = [], $case_sensitive = false) {
        return $this->add_route($path, $target, $values, Request::PUT, $case_sensitive);
    }

    /**
     * Add a custom DELETE route
     *
     * @param string $path           path
     * @param mixed  $target         target
     * @param array  $values         values for params
     * @param bool   $case_sensitive case sensitive?
     * @return object this
     */
    public function delete($path, $target, array $values = [], $case_sensitive = false) {
        return $this->add_route($path, $target, $values, Request::DELETE, $case_sensitive);
    }

    /**
     * Add a custom HEAD route
     *
     * @param string $path           path
     * @param mixed  $target         target
     * @param array  $values         values for params
     * @param bool   $case_sensitive case sensitive?
     * @return object this
     */
    public function head($path, $target, array $values = [], $case_sensitive = false) {
        return $this->add_route($path, $target, $values, Request::HEAD, $case_sensitive);
    }

    /**
     * Add a custom route
     *
     * @param string $path           path
     * @param mixed  $target         target
     * @param array  $values         values for params
     * @param string $method         http method
     * @param bool   $case_sensitive case sensitive?
     * @return object this
     */
    public function add_route($path, $target, $values, $method, $case_sensitive) {
        $this->routes[$path] = [
            'path'   => $path,
            'target' => $target,
            'values' => $values,
            'method' => $method,
            'case_sensitive' => $case_sensitive
        ];

        return this;
    }

    /**
     * Finds custom route
     *
     * @return array Route
     */
    public function find_custom_route() {
        // TODO: implement custom routes
        return false;
    }

    /**
     * Calls the corresponding controller
     *
     * @param Request $request Request object
     * @return object this
     */
    public function run(Request $request = null, Response $response = null) {
        $this->request = ($request !== null) ? $request : new Request;

        $response = ($response !== null) ? $response : new Response;
        $method   = $this->request->method;

        if ($route = $this->find_custom_route()) {
            if (is_callable($route['target'], false, $function)) {
                // Target is callable
                call_user_func($route['target'], $this->request, $response);
            } else {
                // Target is dot notated
                $segments   = explode('.', $route['target']);
                $controller = new $segments[0]($this->request, $response);

                // Call before event
                $controller->before();

                if (is_numeric($this->request->segments[1])) {
                    // Call resource loader
                    $controller->resource();
                }

                if (count($segments) > 1) {
                    // Method name is given
                    $callable  = [$controller, $segments[1]];
                    $arguments = array_slice($segments, 2);

                    call_user_func_array($callable, $arguments);
                } else {
                    // Method name is http method
                    $controller->$method();
                }

                // Call after event
                $controller->after();
            }
        } else {
            if (!empty($this->request->segments[1])) {
                $this->request->params['id'] = $this->request->segments[1];
            }

            $name       = 'Controller' . $this->request->resource;
            $controller = new $name;
            $method     = $this->request->method;

            // Call before event
            $controller->before();

            if (!empty($this->request->params['id'])) {
                // Call resource loader
                $controller->resource();
            }

            // Call action
            $controller->$method();

            // Call after event
            $controller->after();
        }

        return $this;
    }

}
