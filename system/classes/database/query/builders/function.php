<?php

class DatabaseQueryBuilderFunction {

    protected $name;
    protected $args;

    protected $fallbacks = [
        'sql' => ['sqlite']
    ];

    public function __construct($name, $args) {
        $this->name = $name;
        $this->args = $args;
    }

    public function generate($type) {
        $methods = get_class_methods(get_class($this));

        if (!in_array('generate_' . $type, $methods)) {

            $found = false;

            foreach ($this->fallbacks as $fallback => $types) {
                if (in_array($type, $types)) {
                    if (in_array('generate_' . $fallback, get_class_methods($this))) {
                        $type = $fallback;
                        $found = true;
                        break;
                    }
                }
            }

            if (!$found) {
                // @todo Throw exception?
                return null;
            }
        }

        return call_user_func_array([$this, 'generate_' . $type], [$this->name, $this->args]);
    }

    public function generate_sql($name, $args) {
        $sql = strtoupper($name) . '(' . implode(', ', $args) . ')';

        return $sql;
    }

}