<?php

class DatabaseQueryBuilderFunctionRandom extends DatabaseQueryBuilderFunction {

    public function generate_sql($name, $args) {
        return 'RAND(' . implode(', ' . $args) . ')';
    }

}