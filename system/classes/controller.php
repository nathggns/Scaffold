<?php defined('SCAFFOLD') or die();

abstract class Controller {

    protected $request  = null;
    protected $response = null;

    public function __construct($request, $response) {
        $this->request  = $request;
        $this->response = $response;
    }

    public function before() {}
    public function resource() {}

    public function after() {
        $this->response->send();
    }

}
