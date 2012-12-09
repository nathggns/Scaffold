<?php defined('SCAFFOLD') or die();

class DatabaseDriverPDO extends DatabaseDriver {

    private $conn = false;
    public $query = false;
    private $type = false;

    /**
     * Connect to the database via PDO
     *
     * @return DatabaseDriverPDO this
     */
    public function connect() {
        // Extract arguments from the config into our scope.
        $exports = array('type', 'host', 'username', 'password', 'database');
        $vals = arguments($exports, $this->config);
        extract($vals);

        // Set the driver connection string for PDO
        $connstring = strtolower($type) . ':host=' . $host .';dbname=' . $database;

        // Create our connection
        $this->connection = new PDO($connstring, $username, $password);

        // Return ourself to allow chaining.
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
        // Inform the object that the last query was a SELECT query.
        $this->type = static::SELECT;

        // If we don't have actually options, imitate them.
        if (!$options) {
            $options = [];
        } else if (!is_array($options)) {
            $options = ['where' => ['id' => $options]];
        }

        // Set a default param array
        $values = [
            'from' => $table,
            'vals' => ['*'],
            'where' => [],
            'order' => [],
            'group' => [],
            'having' => [],
            'limit' => []
        ];

        // Add our options to the param array
        foreach ($options as $key => $val) {
            if (isset($values[$key])) {
                $values[$key] = $val;
            }
        }

        // Call the builder select function
        $query = $this->builder->select([
            'table' => $values['from'],
            'vals' => $values['vals'],
            'conds' => $values['where'],
            'order' => $values['order'],
            'group' => $values['group'],
            'having' => $values['having'],
            'limit' => $values['limit']
        ]);

        // Return the query
        return $this->query($query);
    }

    /**
     * Insert a row
     *
     * @param string $table Table to insert into
     * @param array $data Data to insert
     */
    public function insert($table, $data) {
        // Store the query type in the object.
        $this->type = static::INSERT;

        // Ask the builder for the query.
        $query = $this->builder->insert($table, $data);

        // Execute the query
        return $this->query($query);
    }

    /**
     * Update a row
     * If you have previously selected a item, you can simple pass
     * the values to update instead of any where or table values.
     *
     * @param string $table Table to update
     * @param array $data Data to set
     * @param array $where Limit the update
     */
    public function update($table, $data = false, $where = []) {
        // Check if we can generate table & where data from a previous select statement, if it's missing.
        if ($this->query && $this->type === static::SELECT && is_array($table) && !$data && $used = $this->table()) {
            // Swap the data and table around
            $data = $table;
            $table = $used;

            // Generate the where for the tables selecte
            $ids = [];
            while ($row = $this->fetch()) $ids[] = $row['id'];
            $where = ['id' => $ids];

        } else if (!$data) {
            // If we only have a table, we can't do anything
            return false;
        }

        // Set the type to update
        $this->type = static::UPDATE;

        // Generate the query
        $query = $this->builder->update($table, $data, $where);

        // Execute the query
        return $this->query($query);
    }

    /**
     * Fetch one row.
     *
     * @return array Associative array of data
     */
    public function fetch($table = null, $options = null) {
        // If we're using it as a shortcut for find, call find
        if (!is_null($table) && !is_null($options)) $this->find($table, $options);

        // Fetch a row from the query
        return $this->query->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Fetch all rows
     *
     * @return array Array of associative arrays of data
     */
    public function fetch_all($table = null, $options = null) {
        // If we're using it as a shortcut for find, call find
        if (!is_null($table) && !is_null($options)) $this->find($table, $options);

        // Fetch all the rows from the query
        return $this->query->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get the count from the last query ran.
     * Simply an alias for select_count, if used on a select statement.
     *
     * @return int|bool Count of affected rows from the last query, or false.
     */
    public function count() {
        // If we don't have a query to operate from, die.
        if (!$this->query) return false;

        // If we're trying to get the count for a select statement, call the dedicated function
        if ($this->type === static::SELECT) {
            return $this->select_count();
        }

        // Return the count
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
        // If we don't have a query, or it isn't a select query, die.
        if (!$this->query || $this->type !== static::SELECT) return false;

        // Loop through all the rows, adding to the count
        $count = 0;
        while ($this->fetch()) $count++;

        // Return the count
        return $count;
    }

    /**
     * Get the structure of a table
     */
    public function structure($table) {
        return $this->query($this->builder->structure($table));
    }

    /**
     * Get the last insert id
     */
    public function id() {
        return $this->connection->lastInsertId();
    }

    public function delete($table, $where = []) {
        $query = $this->builder->delete($table, $where);

        return $this->query($query);
    }

    /**
     * Run a query.
     *
     * @param string $sql sql to run
     */
    private function query($sql) {
        // Die if we're not connected
        if (!$this->connection) return false;

        // Run the query, and save it.
        $this->query = $this->connection->query($sql);

        // Return ourself for chaining
        return $this;
    }

    /**
     * Get the table that the last query was ran on.
     * This will have to be extended for different drivers that this isn't
     * supported on.
     *
     * @return string Table the last query was ran on.
     */
    private function table() {
        return $this->query->getColumnMeta(0)['table'];
    }
}
