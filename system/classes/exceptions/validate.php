<?php defined('SCAFFOLD') or die();

/**
 * Thrown when an error occurs during validation
 *
 * @author Nathaniel Higgins
 */
class ExceptionValidate extends Exception {

    public $errors;

    public function __construct(array $errors) {
        $this->errors = $errors;
    }
}