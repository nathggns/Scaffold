<?php defined('SCAFFOLD') or die();

class ControllerIndex extends Controller {

    public function get() {
        $this->response->data([
            'version' => '1.0'
        ]);
    }

}
