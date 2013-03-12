<?php defined('SCAFFOLD') or die;

class DatabaseDriverSqlite extends DatabaseDriverPDO {

    function connect() {
        call_user_func_array(['parent', 'connect'], func_get_args());

        $this->query($this->builder->prevent_locking());
    }

    /**
     * Get the structure of a table
     */
    function structure($table) {
        $result = $this->query($this->builder->structure($table))->fetch_all();
        $struct = [];

        foreach ($result as $row) {
            $struct[$row['name']] = [
                'field' => $row['name']
            ];
        }

        return $struct;
    }


}