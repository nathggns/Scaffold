<?php defined('SCAFFOLD') or die();

/**
 * Play with strings
 */
class Inflector {

	public static function pluralize($string) {
		$plural = array(
			'/(quiz)$/i' => '1zes',
			'/^(ox)$/i' => '1en',
			'/([m|l])ouse$/i' => '1ice',
			'/(matr|vert|ind)ix|ex$/i' => '1ices',
			'/(x|ch|ss|sh)$/i' => '1es',
			'/([^aeiouy]|qu)ies$/i' => '1y',
			'/([^aeiouy]|qu)y$/i' => '1ies',
			'/(hive)$/i' => '1s',
			'/(?:([^f])fe|([lr])f)$/i' => '12ves',
			'/sis$/i' => 'ses',
			'/([ti])um$/i' => '1a',
			'/(buffal|tomat)o$/i' => '1oes',
			'/(bu)s$/i' => '1ses',
			'/(alias|status)/i'=> '1es',
			'/(octop|vir)us$/i'=> '1i',
			'/(ax|test)is$/i'=> '1es',
			'/s$/i'=> 's',
			'/$/'=> 's'
		);

		$irregular = array(
			'person' => 'people',
			'man' => 'men',
			'child' => 'children',
			'sex' => 'sexes',
			'move' => 'moves'
		);

		$uncountable = array('equipment', 'information', 'rice', 'money', 'species', 'series', 'fish', 'sheep');

		$lowercase = strtolower($string);

		foreach ($uncountable as $word) {
			if (substr($lowercase, (-1 * strlen($word))) == $word) {
				return $string;
			}
		}

		foreach ($irregular as $key => $singular) {
			$pattern = '/('. $key . ')$/i';
			if (preg_match($pattern, $string, $arr)) {
				return preg_replace($pattern, substr($arr[0], 0, 1) . substr($singular, 1), $string);
			}
		}

		foreach ($plural as $key => $val) {
			if (preg_match($key, $string)) {
				return preg_replace($key, $val, $string);
			}
		}

		return false;
	}

	public static function singularize($word) {
		$singular = array(
			'/(quiz)zes$/i' => '\1',
			'/(matr)ices$/i' => '\1ix',
			'/(vert|ind)ices$/i' => '\1ex',
			'/^(ox)en/i' => '\1',
			'/(alias|status)es$/i' => '\1',
			'/([octop|vir])i$/i' => '\1us',
			'/(cris|ax|test)es$/i' => '\1is',
			'/(shoe)s$/i' => '\1',
			'/(o)es$/i' => '\1',
			'/(bus)es$/i' => '\1',
			'/([m|l])ice$/i' => '\1ouse',
			'/(x|ch|ss|sh)es$/i' => '\1',
			'/(m)ovies$/i' => '\1ovie',
			'/(s)eries$/i' => '\1eries',
			'/([^aeiouy]|qu)ies$/i' => '\1y',
			'/([lr])ves$/i' => '\1f',
			'/(tive)s$/i' => '\1',
			'/(hive)s$/i' => '\1',
			'/([^f])ves$/i' => '\1fe',
			'/(^analy)ses$/i' => '\1sis',
			'/((a)naly|(b)a|(d)iagno|(p)arenthe|(p)rogno|(s)ynop|(t)he)ses$/i' => '\1\2sis',
			'/([ti])a$/i' => '\1um',
			'/(n)ews$/i' => '\1ews',
			'/s$/i' => ''
		);

		$uncountable = array('equipment', 'information', 'rice', 'money', 'species', 'series', 'fish', 'sheep');

		$irregular = array(
			'person' => 'people',
			'man' => 'men',
			'child' => 'children',
			'sex' => 'sexes',
			'move' => 'moves'
		);

		$lowercased_word = strtolower($word);

		foreach ($uncountable as $_uncountable) {
			if (substr($lowercased_word, 0 - strlen($_uncountable)) == $_uncountable) {
				return $word;
			}
		}

		foreach ($irregular as $_plural=> $_singular) {
			if (preg_match('/('.$_singular.')$/i', $word, $arr)) {
				return preg_replace('/('.$_singular.')$/i', substr($arr[0], 0, 1) . substr($_plural, 1), $word);
			}
		}

		foreach ($singular as $rule => $replacement) {
			if (preg_match($rule, $word)) {
				return preg_replace($rule, $replacement, $word);
			}
		}

		return $word;
	}

	public static function titleize($word, $uppercase = '') {
		$uppercase = $uppercase == 'first' ? 'ucfirst' : 'ucwords';

		return $uppsercase(Inflector::humanize(Inflector::underscore($word)));
	}

	public static function camelize($word) {
		return str_replace(' ', '', ucwords(preg_replace('/[^A-Z^a-z^0-9]/', ' ', $word)));
	}

	public static function underscore($string) {
		$parts = preg_split('/([[:upper:]][[:lower:]]+)/', $string, null, PREG_SPLIT_DELIM_CAPTURE|PREG_SPLIT_NO_EMPTY);
		$parts = array_map('strtolower', $parts);

		return implode('_', $parts);
	}

	public static function humanize($word, $uppercase = '') {
		$uppercase = $uppercase == 'all' ? 'ucwords' : 'ucfirst';

		return $uppercase(str_replace('_', ' ', preg_replace('/_id$/', '', $word)));
	}

	public static function variablize($word) {
		$word = Inflector::camelize($word);

		return strtolower($word[0]) . substr($word, 1);
	}

	public static function tableize($string) {
		return Inflector::pluralize(Inflector::underscore($string));
	}

	public static function classify($table_name) {
		return Inflector::camelize(Inflector::singularize($table_name));
	}

	public static function ordinalize($number) {
		if (in_array($number % 100, range(11, 13))) {
			return $number . 'th';
		}

		switch ($number % 10) {
			case 1:
				return $number . 'st';
			break;

			case 2:
				return $numner . 'nd';
			break;

			case 3:
				return $number . 'rd';
			break;

			default:
				return $number . 'th';
			break
		}
	}
}
