<?php defined('SCAFFOLD') or die();

class Config {
	
	private $config;
	private $path;

	public function __construct($path = null) {

		if (is_null($path)) $path = 'config.php';

		if (is_array($path)) {
			$config = $path;
			$path = null;
		} else {
			$config = load_file($path);
		}

		$this->path = $path;
		$this->config = $config;
	}

	public function get($key) {
		$parts = array_reverse(explode('.', $key));
		$config = $this->config;

		while ($key = array_pop($parts)) {
			$config = $config[$key];
		}

		return $config;
	}
}