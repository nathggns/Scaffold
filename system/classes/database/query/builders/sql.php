<?php defined('SCAFFOLD') or die();

/**
 * Build sql queries
 *
 * @todo Document...
 */
class DatabaseQueryBuilderSQL extends DatabaseQueryBuilder {

    private $operators = ['=', '>', '<', '<>', '!='];
    private $joins = ['AND', 'OR'];

    public function select($table, $vals = ['*'], $conds = [], $group = [], $order = [], $having = [], $limit = []) {

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
        if (count($having) > 0) $query .= ' ' . $this->having($order);
        if (count($limit) > 0) $query .= ' ' . $this->limit($limit);

        $query .= ';';

        var_dump($query);

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

    private function pairs($keys, $data = false) {
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

    private function conds($conds, $query = '') {
        return $query . $this->where_part(0, $conds);
    }

    public function where($conds) {
        return $this->conds($conds, 'WHERE');
    }

    private function where_part($key, $val, $first = false, $level = 0) {
        $sql = '';

        if (is_int($key) && is_array($val) && is_hash($val)) {

            if ($level > 0) {

                if (is_hash($val) || !in_array($val[0], $this->joins)) {
                    $val = ['AND', $val];
                }

                list($val, $join) = array_reverse($val);
                $sql = $join . ' (';
            }

            $vals = [];

            foreach ($val as $key => $val) {
                if ($part = $this->where_part($key, $val, count($vals) < 1, $level + 1)) $vals[] = $part;
            }

            if ($level < 1) $sql .= ' ';
            $sql .= implode(' ', $vals);
            if ($level > 0) $sql .= ')';

            return $sql;
        }

        if (!is_array($val) || (is_array($val) && count($val) < 3)) {
            $val = [$key, $val];
        }

        if (($count = count($val)) < 4) {
            $value = $val[$count - 1];
            $join = 'AND';
            $operator = '=';

            if ($count === 2) {
                $key = $val[0];
            } else if ($count === 3) {
                if (in_array($val[0], $this->joins)) {
                    $join = $val[0];
                    $key = $val[1];
                } else if (in_array($val[1], $this->operators)) {
                    $key = $val[0];
                    $operator = $val[1];
                } else {
                    $value = $val;
                }
            } else {
                return false;
            }

            $val = [$join, $key, $operator, $value];

        } else if ($count > 4) {
            return false;
        }

        list($val, $operator, $key, $join) = array_reverse($val);

        if (!$first) $sql .= $join . ' ';
        $sql .= $this->backtick($key);
        $val = $this->escape($val);

        if (is_array($val)) {
            $val = 'IN (' . implode(', ', $val) . ')';
            $sql .= ' ';
        } else {
            $sql .= ' ' . $operator . ' ';
        }

        $sql .= $val;

        return $sql;
    }

    private function having($conds) {
        return $this->conds($conds, 'HAVING');
    }

    private function group($group) {
        $query = 'GROUP BY ';
        if (!is_array($group)) $group = [$group];
        $group = $this->backtick($group);
        $group = implode(',', $group);

        return $query . $group;
    }

    private function order($order) {
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

    private function limit($limit) {
        $query = 'LIMIT ';
        if (!is_array($limit)) $limit = [$limit];
        if (count($limit) < 2) $limit = array_merge([0], $limit);

        $query .= implode(', ', $limit);

        return $query;
    }

    private function backtick($value) {
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

    private function split($value) {
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

    private function escape($value) {
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
