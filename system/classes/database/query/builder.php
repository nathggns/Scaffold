<?php defined('SCAFFOLD') or die;

abstract class DatabaseQueryBuilder implements DatabaseQueryBuilderInterface {

    const MODE_SINGLE = 1;
    const MODE_CHAINED = 2;

    protected $mode;
    protected $query_opts;
    protected $query_mode;
    protected $where_mode;

    /* config functions */

    public function __construct() {
        $this->mode = static::MODE_SINGLE;
    }

    public function start($type = null, $opts = []) {
        $this->mode = static::MODE_CHAINED;
        $this->query_opts = $opts;
        $this->query_mode = $type;
        $this->where_mode = [];

        return $this;
    }

    public function end() {
        $this->mode = static::MODE_SINGLE;

        return call_user_func([$this, $this->query_mode], $this->query_opts);
    }

    /**
     * We should return the query if we're typecasted to a string
     */
    public function __toString() {
        return $this->end();
    }

    /**
     * Return query_opts['conds'], if they exist
     */
    public function get_conds() {
        return isset($this->query_opts['conds']) ?
            $this->query_opts['conds'] :
            null;
    }

    /* Filtering functions */

    public function distinct() {
        $this->query_opts['distinct'] = true;

        return $this;
    }

    public function where($key, $val = null) {

        if (is_null($val)) {
            if (is_array($key)) {
                $val = $key;
                $key = null;    
            } else if (is_callable($key)) {
                
                $instance = new $this;
                $key = $key->bindTo($instance);
                $key();
                
                if (!($val = $instance->get_conds())) {
                    return $this;
                }

                $key = null;


            }
        }

        while (count($this->where_mode) > 0 && $func = array_pop($this->where_mode)) {
            $val = call_user_func(['Database', 'where_' . $func], $val);
        }

        $this->where_mode = [];
        
        if (!isset($this->query_opts['conds'])) {
            $this->query_opts['conds'] = [];
        }

        if (!is_null($key)) {
            $this->query_opts['conds'][$key] = $val;
        } else {
            $this->query_opts['conds'][] = $val;
        }

        return $this;
    }

    public function __call($name, $args) {
        if (preg_match('/^where_/i', $name)) {
            $names = array_slice(explode('_', $name), 1);
            $this->where_mode = array_merge($this->where_mode, $names);

            if (count($args) > 0) {
                call_user_func_array([$this, 'where'], $args);
            }

            return $this;
        }

        throw new Exception('No such function');
    }

    public function group() {
        $part = func_get_args();

        if (!isset($this->query_opts['group'])) $this->query_opts['group'] = [];

        $this->query_opts['group'] = array_merge_recursive($this->query_opts['group'], $part);

        return $this;
    }

    public function order($col, $dir = false) {
        $part = [$col];
        if ($dir) $part[] = $dir;

        if (!isset($this->query_opts['order'])) $this->query_opts['order'] = [];

        $this->query_opts['order'][] = $part;

        return $this;
    }

    public function limit($start, $end = null) {
        $part = [$start];
        if (!is_null($end)) $part[] = $end;

        if (!isset($this->query_opts['limit'])) $this->query_opts['limit'] = [];

        $this->query_opts['limit'] = array_merge($this->query_opts['limit'], $part);

        return $this;
    }

    public function set($key, $value) {
        if (!in_array($this->query_mode, ['insert', 'update'])) {
            throw new InvalidArgumentException('Cannot use with this type of query');
        }

        if (!isset($this->query_opts['data'])) {
            $this->query_opts['data'] = [];
        }

        $this->query_opts['data'][$key] = $value;

        return $this;
    }

    public function offset($offset) {
        $this->query_opts['offset'] = $offset;

        return $this;
    }

    public function val($val, $clear_star = true) {
        if (is_array($val)) {
            while ($item = array_shift($val)) {
                $this->val($item, $clear_star);
            }
        } else {

            if ($clear_star && $this->query_opts['vals'] === ['*']) {
                $this->query_opts['vals'] = [];
            }

            $this->query_opts['vals'][] = $val;
        }

        return $this;
    }


    /* Utility functions for the subclass */

    protected function extract_select() {

        $options = call_user_func_array([$this, 'extract_shuffle_select'], func_get_args());

        $args = [];
        $required = ['table'];
        $optional = [
            'vals' => ['*'],
            'conds' => [],
            'group' => [],
            'order' => [],
            'having' => [],
            'limit' => [],
            'distinct' => false,
            'count' => false,
            'offset' => false
        ];

        $keys = array_keys($options);

        foreach ($required as $req) {
            if (!in_array($req, $keys)) {
                throw new InvalidArgumentException('Missing ' . $req);
            } else {
                $args[$req] = $options[$req];
            }
        }

        foreach ($optional as $name => $value) {
            if (in_array($name, $keys)) {
                $value = $options[$name];
            }

            $args[$name] = $value;
        }

        return $args;
    }

    protected function extract_update() {

        $args = $this->extract_shuffle([
            'table'
        ], [
            'table',
            'data',
            'where',
            'conds',
            'limit',
            'order',
            'offset'
        ], func_get_args());

        if (isset($args['conds'])) {
            $args['where'] = $args['conds'];
            unset($args['conds']);
        }

        return $args;
    }

    protected function extract_delete() {
        $args = $this->extract_shuffle([
            'table'
        ], [
            'table',
            'where',
            'conds',
            'limit',
            'order',
            'offset'
        ], func_get_args());

        if (isset($args['conds'])) {
            $args['where'] = $args['conds'];
            unset($args['conds']);
        }

        return $args;
    }

    protected function extract_shuffle($required, $keys, $options) {
        if (count($options) === 1 && is_array(reset($options)) && is_hash(reset($options))) {
            $args = reset($options);
        } else {
            $args = [];

            foreach ($keys as $i => $key) {
                $args[$key] = isset($options[$i]) ? $options[$i] : null;
            }
        }

        foreach ($required as $req) {
            if (!isset($args[$req])) {
                throw new InvalidArgumentException('Missing ' . $req);
            }
        }

        return $args;
    }

    protected function extract_shuffle_select($table = null, $options = []) {
        // Argument shuffling
        if (is_array($table)) {
            $options = $table;

            if (isset($options['table'])) {
                $table = $options['table'];
            } else {
                $table = null;
            }
        }

        if (!is_null($table)) {
            $options['table'] = $table;
        }

        return $options;
    }

    protected function chained($data = null) {
        return $this->mode === static::MODE_CHAINED ||
            (!is_null($data) && count($data) === 1 && is_string(current($data)));
    }

}
