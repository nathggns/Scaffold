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
        $table = $this->backtick($table);
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

    private function conds($conds, $query) {
        $vals = [];

        foreach ($conds as $key => $val) {
            $part = $this->where_part($key, $val, $vals);
            $vals = array_merge($vals, $part);
        }

        $query .= ' ' . implode(' ', $vals);

        return $query;
    }

    private function where($conds) {
        return $this->conds($conds, 'WHERE');
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

    private function where_part($key, $val, $vals, $get_arr = false) {
        if (is_int($key)) {
            $parts = [];
            $firstKey = key($val);
            $firstVal = $val[$firstKey];
            $join = 'AND';

            if (!is_array($firstVal) || !is_string(key($firstVal))) {
                $val[$firstKey] = $this->where_part($firstKey, $firstVal, $parts, true);
                $join = $val[$firstKey][1];
            }

            foreach ($val as $k => $part) {
                $parts = array_merge($parts, $this->where_part($k, $part, $parts));
            }

            $parts[0] = '(' . $parts[0];

            if (count($vals) > 0) {
                $parts[0] = $join . ' ' . $parts[0];
            }

            $parts[count($parts)-1] .= ')';

            return $parts;
        }

        if (!is_array($val)) {
            $val = ['=', 'AND', $val];
        }

        if (count($val) < 3) {
            $first = $val[0];

            if (in_array($first, $this->joins)) {
                $val = array_merge(['='], $val);
            } else if (in_array($first, $this->operators)) {
                $val = [$first, 'AND', $val[1]];
            } else {
                $val = ['=', 'AND', $val];
            }
        }

        if ($get_arr) return $val;

        // @SEE: http://php.net/list - Notes
        list($val, $join, $op) = array_reverse($val);

        $key = $this->backtick($key);
        $val = $this->escape($val);
        $op = ' ' . $op . ' ';

        if (is_array($val)) {
            $part = $key . ' IN (' . implode(', ', $val) . ')';
        } else {
            $part = $key . $op . $val;
        }

        if (count($vals) > 0) {
            $part = $join . ' ' . $part;
        }

        return [$part];
    }

    public function backtick($value) {
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

    public function escape($value) {
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
