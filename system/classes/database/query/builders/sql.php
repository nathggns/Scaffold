<?php defined('SCAFFOLD') or die();

/**
 * Build sql queries
 *
 * @todo Document...
 */
class DatabaseQueryBuilderSQL extends DatabaseQueryBuilder {

    protected $operators = ['=', '>', '<', '<>', '!='];
    protected $joins = ['AND', 'OR'];
    protected $default_meta = ['connector' => 'AND', 'operator' => '='];

    public function select($options) {
        extract($this->extract($options));

        if (!is_array($table)) {
            $table = [$table];
        }

        $parts = [];
        foreach ($table as $key => $val) {
            $col = $this->backtick(is_int($key) ? $val : $key);

            if (!is_int($key)) {
                $col .= ' AS ' . $this->backtick($val);
            }

            $parts[] = $col;
        }

        $table = implode(',', $parts);

        $vals = $this->backtick($vals);

        foreach ($vals as $key => $val) {
            if (!is_int($key)) {
                $vals[$key] = $this->backtick($key) . ' AS ' . $val;
            }
        }

        $val = implode(', ', $vals);
        $query = 'SELECT ' . $val . ' FROM ' . $table;

        if (count($conds) > 0) $query .= ' ' . $this->where($conds);
        if (count($group) > 0) $query .= ' ' . $this->group($group);
        if (count($order) > 0) $query .= ' ' . $this->order($order);
        if (count($having) > 0) $query .= ' ' . $this->having($having);
        if (count($limit) > 0) $query .= ' ' . $this->limit($limit);

        $query .= ';';

        return $query;
    }

    public function insert($table, $data) {
        $table = $this->backtick($table);
        $values = $this->escape($data);
        $values = implode(', ', $values);

        $query = 'INSERT INTO ' . $table . ' ';

        if (is_hash($data)) {
            $keys = $this->backtick(array_keys($data));
            $keys = implode(', ', $keys);
            $query .= '(' . $keys . ') ';
        }

        $query .= 'VALUES (' . $values . ');';

        return $query;
    }

    public function update($table, $data, $where = []) {
        $table = $this->backtick($table);
        $keys = $this->backtick(array_keys($data));
        $data = $this->escape($data);
        $query = 'UPDATE ' . $table . ' SET ' . $this->pairs($keys, $data);

        if (count($where) > 0) $query .= ' ' . $this->where($where);

        $query .= ';';

        return $query;
    }

    public function delete($table, $where = []) {
        $table = $this->backtick($table);
        $query = 'DELETE FROM ' . $table;

        if (count($where) > 0) $query .= ' ' . $this->where($where);

        $query .= ';';

        return $query;
    }

    public function structure($table) {
        return 'SHOW FULL COLUMNS FROM ' . $this->backtick($table) . ';';
    }

    protected function where($conds) {
        return $this->conds($conds, 'WHERE');
    }

    protected function pairs($keys, $data = false) {
        if (!$data) {
            $data = $keys;
            $keys = array_keys($data);
        }

        $data = array_values($data);
        $parts = [];

        foreach (range(0, count($keys) - 1) as $i) {
            $key = $keys[$i];
            $part = $data[$i];
            $parts[] = $key . ' = ' . $part;
        }

        return implode(', ', $parts);
    }

    protected function conds($conds, $query = '') {
        return $query . $this->where_part(null, $conds);
    }

    protected function get_meta($obj = false) {

        $arr = $this->default_meta;

        if ($obj) {
            if (property_exists($obj, 'connector')) {
                $arr['connector'] = strtoupper($obj->connector);
            }

            if (property_exists($obj, 'operator')) {
                $arr['operator'] = call_user_func(function() use($obj) {
                    switch ($obj->operator) {
                        case 'gt': return '>';
                        case 'gte': return '>=';
                        case 'lt': return '<';
                        case 'lte': return '<=';
                        case 'equals': return '=';
                    }

                    return false;
                });
            }
        }

        return $arr;
    }

    protected function where_part($key, $val, $first = false, $level = 0, $meta = false) {

        $query = '';

        // Initial call, loops through all items in the conditions
        if (is_null($key)) {
            // We can't do anything if we don't have conditions
            if (!is_array($val)) return;

            // Loop through each condition and call itself with them
            $first = true;

            foreach ($val as $k => $v) {
                $query .= $this->where_part($k, $v, $first, $level + 1);
                $first = false;
            }

        } else {

            $obj = false;

            // Keep a reference to our object for later
            if (is_object($val)) {
                $obj = $val;
                $val = $obj->val;
            }

            if (!$meta) {

                // If we can get meta from the object?
                if ($obj) {
                    $meta = $this->get_meta($obj);
                // No, so we'll get default stuffs.
                } else {
                    $meta = $this->get_meta();
                }
            }

            extract($meta);

            // Connectors
            if (!$first) {
                $query .= ' ' . $connector;
            }

            // Spacings
            if (!$first || $level < 2) $query .= ' ';

            // Let's escape val and stuff
            if (is_scalar($val)) {
                $val = $this->escape($val);
            }

            // In queries (we'll escape here too!)
            if (is_array($val) && !is_hash($val)) {
                $operator = 'IN';
                $val = '(' . implode(', ', array_map([$this, 'escape'], $val)) . ')';
            }

            // Operator based queries (including IN queries)
            if (is_scalar($val)) {
                // If we have a not special
                if ($obj && property_exists($obj, 'special') && in_array('not', $obj->special)) {
                    $query .= 'NOT ';
                }

                $query .= $this->backtick($key) . ' ' . $operator . ' ' . $val;
            // Grouped queries
            } else {
                $query .= '(' . $this->where_part(null, $val, null, $level + 1) . ')';
            }
        }

        return $query;
    }

    protected function having($conds) {
        return $this->conds($conds, 'HAVING');
    }

    protected function group($group) {
        $query = 'GROUP BY ';
        if (!is_array($group)) $group = [$group];
        $group = $this->backtick($group);
        $group = implode(',', $group);

        return $query . $group;
    }

    protected function order($order) {
        $query = 'ORDER BY ';
        if (!is_array($order)) $order = [$order];
        $parts = [];

        foreach ($order as $part) {
            if (!is_array($part)) $part = [$part, 'ASC'];
            $part[0] = $this->backtick($part[0]);

            $parts[] = $part[0] . ' ' . $part[1];
        }

        $query .= implode(', ', $parts);

        return $query;
    }

    protected function limit($limit) {
        $query = 'LIMIT ';
        if (!is_array($limit)) $limit = [$limit];
        if (count($limit) < 2) $limit = array_merge([0], $limit);

        $query .= implode(', ', $limit);

        return $query;
    }

    protected function backtick($value) {
        if (is_array($value)) return array_map([$this, 'backtick'], $value);
        if ($value === '*') return $value;

        if (strpos($value, '(') !== false && substr($value, -1, 1) === ')') {
            preg_match('/(.*?)\((.*)\)/', $value, $matches);

            $func = $matches[1];
            $inside = $matches[2];
            $parts = $this->split($inside);
            $parts = $this->backtick($parts);

            return $func . '(' . implode(', ', $parts) . ')';
        }

        $value = str_replace('.', '`.`', $value);
        $value = '`' . $value . '`';

        return $value;
    }

    protected function split($value) {
        $parts = [];
        $chrs = str_split($value);
        $buffer = '';
        $inside = false;

        foreach ($chrs as $chr) {
            if (!$inside && $chr === ',') {
                $parts[] = $buffer;
                $buffer = '';
                continue;
            }

            $buffer .= $chr;

            switch ($chr) {
                case '(':
                    $inside = true;
                break;

                case ')':
                    $inside = false;
                break;
            }
        }

        if (strlen($buffer) > '') {
            $parts[] = $buffer;
        }

        return $parts;
    }

    protected function escape($value) {
        if (is_array($value)) {
            return array_map([$this, 'escape'], $value);
        }

        $validator = new Validate(['val' => 'numeric']);

        try {
            $validator->test(['val' => $value]);
        } catch (ExceptionValidate $e) {
            $value = '\'' . $value . '\'';
        }

        return $value;
    }
}
