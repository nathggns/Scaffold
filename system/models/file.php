<?php defined('SCAFFOLD') or die();

/**
 * A file model
 *
 * Allows us to read and write data from files
 */
class ModelFile extends ModelData {

    public $file;

    public function __construct($file = false) {
        if ($file) $this->file = $file;

        $this->load();
    }

    public function load() {
        if ($this->file)
            $this->data = $this->decode(file_get_contents($this->file));

        else return false;

        return $this;
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
