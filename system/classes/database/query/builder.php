<?php defined('SCAFFOLD') or die();

abstract class DatabaseQueryBuilder implements DatabaseQueryBuilderInterface {

	protected function extract($options) {
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

}
