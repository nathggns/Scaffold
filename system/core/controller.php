<?php

abstract class Controller
{

    protected $request  = null;
    protected $response = null;

    public function __construct($request, $response) {
        $this->request  = $request;
        $this->response = $response;
    }

    public function before() {}
    public function after() {}
    public function resource() {}

}
