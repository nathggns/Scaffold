<?php defined('SCAFFOLD') or die();

/**
 * Validate data
 *
 * @author Nathaniel Higgins
 */
class Validate {

    /**
     * Holds the rules that we are to validate against.
     */
    public $_rules;

    /**
     * Our default checks
     */
    private $checks = ['empty', 'email', 'alphanumeric', 'regex', 'is_regex', 'equal'];

    /**
     * Checks can be prepended with some of these modifiers
     */
    private $modifiers = ['not'];

    const TEST_FAILED = 1;

    /**
     * Set rules from instantiation
     */
    public function __construct($name = false, $value = null) {
        $rules = $this->args($name, $value);

        // Apply the rules if they exist
        if (is_array($rules)) {
            foreach ($rules as $key => $value) {
                $this->set($key, $value);
            }
        }

    }

    /**
     * Argument shuffling
     */
    public function args($name, $value) {
        // Argument shuffling
        if (is_array($name) && is_array($value)) {
            $rules = array_combine($name, $value);
        } else if (is_string($name) && !is_null($value)) {
            $rules = [$name => $value];
        } else if (is_array($name) && is_null($value)) {
            $rules = $name;
        } else {
            $rules = false;
        }

        return $rules;
    }

    /**
     * Set a single rule
     */
    public function set($name = false, $value = null) {
        if (is_array($name) || !$value) {
            foreach ($this->args($name) as $key => $value) {
                $this->set($key, $value);
            }
        } else {
            if (!is_array($value)) $value = [$value];

            $this->_rules[$name] = $value;
        }

        return $this;
    }

    /**
     * Test data against our rules
     */
    public function test($data) {

        $errors = [];

        foreach ($data as $key => $value) {

            if (isset($this->_rules[$key])) {
                $rules = $this->_rules[$key];
                $info = [
                    'name' => $key,
                    'tests' => $rules,
                    'value' => $value,
                    'errors' => []
                ];
                $results = [];

                foreach ($rules as $original_rule) {
                    $rule = $original_rule;
                    $mods = [];

                    if (is_callable($rule)) {
                        $result = $rule($value);
                        $rule = 'custom';
                    } else if (is_string($rule)) {
                        if ($this->check_is_regex($rule)) {
                            $rule = 'regex';
                        } else if (strpos($rule, '_')) {
                            $parts = explode('_', $rule);
                            $last = end($parts);
                            reset($parts);

                            foreach ($parts as $part) {
                                if ($last === $part || !in_array($part, $this->modifiers)) break;
                                $mods[] = $part;
                            }

                            if (count($mods) > 0) {
                                $rule = $last;
                            }
                        }
                    }

                    if (in_array($rule, $this->checks)) {
                        $funcname = 'check_' . $rule;
                        $result = $this->$funcname($value, $original_rule);
                    }

                    if (!isset($result)) {
                        $result = $this->check_equal($value, $rule);
                        $rule = 'equal';
                    }

                    foreach ($mods as $mod) {
                        $funcname = 'modifier_' . $mod;
                        $result = $this->$funcname($result, $original_rule);
                    }

                    $rule_pref = implode('_', $mods);
                    if ($rule_pref != '') $rule_pref .= '_';
                    $rule = $rule_pref . $rule;

                    $results[] = [
                        'result' => $result,
                        'rule' => $rule,
                        'value' => $value
                    ];
                }

                foreach ($results as $result) {
                    if (!$result['result']) {
                        $result['type'] = Validate::TEST_FAILED;
                        $info['errors'][] = $result;
                    }
                }

                if (count($info['errors']) > 0) {
                    $errors[] = $info;
                }
            }

        }

        if (count($errors) > 0) {
            throw new ExceptionValidate($errors);
        }

        return true;
    }

    /**
     * Empty Test
     */
    public function check_empty($value) {
        return !$value || $value == '';
    }

    /**
     * Alphanumeric test
     */
    public function check_alphanumeric($value) {
        return ctype_alnum($value);
    }

    /**
     * Email test
     */
    public function check_email($value) {
        return filter_var($value, FILTER_VALIDATE_EMAIL) ? true : false;
    }

    /**
     * Is Regex test
     */
    public function check_is_regex($value) {
        return @preg_match($value, '') !== false;
    }

    /**
     * Regex match test
     */
    public function check_regex($value, $rule) {
        return preg_match_all($rule, $value, $matches) === strlen($value);
    }

    /**
     * Equal test
     */
    public function check_equal($value, $rule) {
        return $value == $rule ? true : false;
    }

    /**
     * Not modifier
     */
    public function modifier_not($value) {
        return !$value;
    }
}