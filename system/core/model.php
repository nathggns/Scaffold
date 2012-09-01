<?php defined('SCAFFOLD') or die();

/**
 * Base model class.
 *
 * Doesn't do much beside validation
 *
 * @author Nathaniel Higgins
 */
class Model {

	/**
	 * Associative array mapping keys to some sort
	 * of validation test. 
	 */
	protected $_rules = array();

	/**
	 * Store data
	 */
	public $data = array();

	/**
	 * Reference to the object_ids
	 */
	private $object_id;

	/**
	 * Validate before saving.
	 *
	 * At this point, the data that is supposed to be saved should be in $data.
	 * Doesn't actually save anything, this should be implemented by an extending
	 * class.
	 */
	public function save() {
		$validator = new Validate($this->_rules);

		foreach ($this->data as $piece) {
			$validator->test($piece);
		}

		return true;
	}

}