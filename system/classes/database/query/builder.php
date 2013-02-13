<?php defined('SCAFFOLD') or die();

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

	public function start() {
		$this->mode = static::MODE_CHAINED;
		$this->query_opts = [];
		$this->query_mode = null;
		$this->where_mode = [];

		return $this;
	}

	/**
	 * @todo Return the query...
	 */
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

	/* Filtering functions */
	public function where($key, $val) {
		foreach ($this->where_mode as $where_mode) {
			$func = 'where_' . $where_mode;
			$val = call_user_func(['Database', $func], $val);
		}

		$this->where_mode = [];

		if (!isset($this->query_opts['conds'])) {
			$this->query_opts['conds'] = [];
		}

		$this->query_opts['conds'][$key] = $val;

		return $this;
	}

	public function __call($name, $args) {
		if (preg_match('/^where_/i', $name)) {
			$name = substr($name, strlen('where_'));
			$this->where_mode[] = $name;

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

	/* Utility functions for the subclass */

	protected function extract() {

		$options = call_user_func_array([$this, 'extract_shuffle'], func_get_args());

		$args = [];
		$required = ['table'];
		$optional = [
		    'vals' => ['*'],
		    'conds' => [],
		    'group' => [],
		    'order' => [],
		    'having' => [],
		    'limit' => []
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

	protected function extract_shuffle($table = null, $options = []) {
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

	protected function chained() {
		return $this->mode === static::MODE_CHAINED;
	}

}
