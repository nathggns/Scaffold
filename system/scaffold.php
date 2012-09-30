<?php defined('SCAFFOLD') or die();

/**
 * Allow the use of Scaffold via a class interface
 */
class Scaffold {

    public static function get($route, $data = []) {
        return Scaffold::request('get', $route, $data);
    }

    public static function post($route, $data = []) {
        return Scaffold::request('post', $route, $data);
    }

    public static function put($route, $data = []) {
        return Scaffold::request('put', $route, $data);
    }

    public static function delete($route, $data = []) {
        return Scaffold::request('delete', $route, $data);
    }

    public static function head($route, $data = []) {
        return Scaffold::request('head', $route, $data);
    }

    /**
     * The actual powerhorse of this class
     */
    public static function request($method, $route, $data = []) {
        // @TODO: Write this request method
    }

}