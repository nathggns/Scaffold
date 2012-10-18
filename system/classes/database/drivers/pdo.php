<?php defined('SCAFFOLD') or die();

class DatabaseDriverPDO extends DatabaseDriver {

    private $conn = false;
    private $query = false;
    private $type = false;

    const SELECT = 1;

    /**
     * Connect to the database via PDO
     */
    public function connect() {
        $exports = array('type', 'host', 'username', 'password', 'database');
        $vals = arguments($exports, $this->config);
        extract($vals);

        $connstring = strtolower($type) . ':host=' . $host .';dbname=' . $database;
        $this->connection = new PDO($connstring, $username, $password);

        return $this;
    }

    /**
     * Find a row
     *
     * @param string $table Table to search
     * @param array $options Options for the find
     *
     * @return DatabaseDriverPDO $this DatabaseDriverPDO instance
     */
    public function find($table, $options = false) {
        $this->type = static::SELECT;

        if (!$options) {
            $options = [];
        } else if (!is_array($options)) {
            $options = ['where' => ['id' => $options]];
        }

        $values = [
            'from' => $table,
            'vals' => ['*'],
            'where' => [],
            'order' => [],
            'group' => [],
            'having' => [],
            'limit' => []
        ];

        foreach ($options as $key => $val) {
            if (isset($values[$key])) {
                $values[$key] = $val;
            }
        }

        $query = call_user_func_array([$this->builder, 'select'], $values);

        return $this->query($query);
    }

    public function insert($table, $data) {
        $query = $this->builder->insert($table, $data);

        return $this->query($query);
    }

    /**
     * Fetch one row.
     *
     * @return array Associative array of data
     */
    public function fetch($table = null, $options = null) {
        if (!is_null($table) && !is_null($options)) $this->find($table, $options);

        return $this->query->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Fetch all rows
     *
     * @return array Array of associative arrays of data
     */
    public function fetch_all($table = null, $options = null) {
        if (!is_null($table) && !is_null($options)) $this->find($table, $options);

        return $this->query->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get the count from the last query ran.
     * Simply an alias for select_count, if used on a select statement.
     *
     * @return int|bool Count of affected rows from the last query, or false.
     */
    public function count() {
        if (!$this->query) return false;
        if ($this->type === static::SELECT) {
            return $this->select_count();
        }

        return $this->query->rowCount();
    }

    /**
     * Get the count from the last select statement ran.
     * Does not use PDOStatement::rowCount
     * SEE: http://stackoverflow.com/questions/883365/count-with-pdo
     *
     * @return int|bool Count of rows in last select statement, or false
     */
    public function select_count() {
        if (!$this->query || $this->type !== static::SELECT) return false;

        $count = 0;
        while ($this->fetch()) $count++;

        return $count;
    }

    /**
     * Run a query.
     *
     * @param string $sql sql to run
     */
    private function query($sql) {
        if ($this->connection) {
            $this->query = $this->connection->query($sql);

            return $this;
        }

        return false;
    }
}
