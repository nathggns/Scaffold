<?php

class DatabaseQueryBuilderFunctionRandom extends DatabaseQueryBuilderFunction {

    public function generate_sql($name, $args) {
        return 'RAND(' . implode(', ' . $args) . ')';
    }

    public function generate_sqlite($name, $args) {
        return 'RANDOM(' . implode(', '. $args) . ')';
    }

}