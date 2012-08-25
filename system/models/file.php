<?php defined('SCAFFOLD') or die();

/**
 * A file model
 *
 * Allows us to read and write data from files
 */
class ModelFile extends ModelData {

    private $file;

    public function __construct($file) {
        $this->file = $file;
        $this->data = $this->decode(file_get_contents($this->file));
    }

    public function decode($data) {
        return json_decode($data, true);
    }

    public function encode($data) {
        return json_encode($data);
    }

    public function save() {
        file_put_contents($this->file, $this->encode($this->data));
    }
}
