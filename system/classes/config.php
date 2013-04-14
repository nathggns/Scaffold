<?php defined('SCAFFOLD') or die;

class Config {
    
    private $config = [];

    public function __construct() {

        $files = get_files('config/*.php');
        
        $config = [];
        foreach ($files['system'] as $file) {
            $name = pathinfo($file, PATHINFO_FILENAME);
            $config[$name] = include($file);
        }

        foreach ($files['application'] as $file) {
            $name = pathinfo($file, PATHINFO_FILENAME);
            if (!isset($config[$name])) $config[$name] = [];
            $config[$name] = recursive_overwrite($config[$name], include($file));
        }

        foreach ($config as $name => $part) {

            $global = [];

            if (isset($part['global'])) {
                $global = $part['global'];
            }

            $real = [];

            if (ENVIROMENT && isset($part[ENVIROMENT])) {
                $real = $part[ENVIROMENT];
            } else if (isset($part['default'])) {
                $real = $part['default'];
            }

            $config[$name] = recursive_overwrite($global, $real);
        }

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