<?php defined('SCAFFOLD') or die();

/**
 * A data model
 *
 * Stores non-persistant data.
 */
class ModelData extends Model {

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

        return [$id => $this->data[$id]];
    }

    /**
     * Return all selected data
     */
    public function all() {
        $results = [];

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

        if (!is_null($value)) return $this->set([$data => $value]);

        if (!is_array($data)) return $this->set([$data]);

        if (is_null($this->object_id)) {
            $this->object_id[] = count($this->data);
        }

        foreach ($this->object_id as $id) {

            if (!isset($this->data[$id])) $this->data[$id] = [];

            if (!isset($this->data[$id]['id'])) {
                $this->data[$id]['id'] = $id;
            }

            foreach ($data as $key => $value) {
                $this->data[$id][$key] = $value;
            }
        }

        return $this;
    }

}
