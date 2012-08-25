<?php defined('SCAFFOLD') or die();

/**
 * A data model
 *
 * Stores non-persistant data.
 */
class ModelData {

    /**
     * Store data
     */
    public $data;

    /**
     * Reference to the object_ids
     */
    private $object_id;


    /**
     * Select an object via it's id.
     */
    public function read($id) {
        if (isset($this->data[$id])) {
            $this->object_id[] = $id;
            return $this;
        }

        return null;
    }

    /**
     * Return one row of data
     */
    public function row() {
        if (is_null($this->object_id)) $this->object_id = array_keys($this->data);
        if (count($this->object_id) < 1) return null;

        $id = array_pop($this->object_id);

        return array($id => $this->data[$id]);
    }

    /**
     * Return all selected data
     */
    public function all() {
        var_dump($this->data);
        $results = array();
        while ($row = $this->row()) {
           $key = key($row);
           $results[$key] = $row[$key];
        }

        $results = array_reverse($results);

        return $results;
    }

    /**
     * Set data
     */
    public function set($data, $value = null) {

        if ($value !== null) return array($data => $value);

        if (!is_array($data)) return $this->set(array($data));

        if (is_null($this->object_id)) {
            $this->object_id[] = count($this->data);
        }

        foreach ($this->object_id as $id) {

            if (!isset($this->data[$id])) $this->data[$id] = array();

            foreach ($data as $key => $value) {
                $this->data[$id][$key] = $value;
            }
        }

        return $this;
    }

}
