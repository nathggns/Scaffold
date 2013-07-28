<?php defined('SCAFFOLD') or die;

/**
 * Validate data
 *
 * @author Nathaniel Higgins
 */
class Validate {

    /**
     * Holds the rules that we are to validate against.
     */
    public $_rules = [];

    /**
     * Our default checks
     */
    private $checks = ['empty', 'email', 'alphanumeric', 'regex', 'is_regex', 'equal', 'url', 'numeric'];

    /**
     * Checks can be prepended with some of these modifiers
     */
    private $modifiers = ['not'];

    /**
     * Test specific checks
     */
    private $test_checks = [];

    /**
     * Test statuses
     */
    const TEST_FAILED = 1;
    const INVALID_DATA = 2;

    /**
     * Global rule
     */
    const GLOBAL_RULE = null;

    /**
     * Set rules from instantiation
     */
    public function __construct($name = null, $value = null) {
        if ($name) $this->set($name, $value);
    }

    /**
     * Set rules
     */
    public function set($name, $value = null) {

        $rules = $this->args($name, $value);

        foreach ($rules as $k => $v) {
            if (!isset($this->_rules[$k])) $this->_rules[$k] = [];
            $this->_rules[$k] = array_merge($this->_rules[$k], $v);
        }

        return $this;
    }

    /**
     * Set global rules
     */
    public function set_global($rules) {
        return $this->set(Validate::GLOBAL_RULE, $rules);
    }

    /**
     * Argument shuffling
     */
    public function args($name, $value = null, $root = true) {

        $rules = [];

        if (is_null($value) && (is_string($name) || (!is_hash($name)))) {
            $value = is_string($name) ? [$name] : $name;
            $name = false;

            $rules[$name] = $value;
        }

        if ((is_null($name) || is_string($name)) && (is_string($value) || !is_hash($value) || is_callable($value) || (is_hash($value) && !$root))) {

            if (!is_array($value)) $value = [$value];
            if (is_null($name)) $name = false;

            $values = [];
            $is_hash = is_array($value);

            foreach ($value as $test_name => $item) {

                if (is_hash($value)) {
                    $this->test_checks[$name][$test_name] = $item;
                    $item = [$test_name];
                } else {
                    $item = is_string($item) ? explode(' ', $item) : [$item];
                }

                $values = array_merge($values, $item);
            }

            $rules[$name] = $values;
        } else if (is_array($name) && is_null($value)) {
            foreach ($name as $k => $v) {
                if ($k === '') $k = null;
                $rules = array_merge($rules, $this->args($k, $v, false));
            }
        }

        return $rules;
    }


    /**
     * Test data against our rules
     *
     * Will only test on a hash.
     */
    public function test($data) {
        $errors = [];

        if (!is_hash($data)) {
            $errors[] = ['errors' => [
                'type' => Validate::INVALID_DATA
            ]];
        } else {
            foreach ($this->_rules as $field => $rules) {
                $c_data = [];

                if (!$field) {
                    foreach ($data as $key => $val) {
                        $c_data[$key] = $val;
                    }
                } else if (isset($data[$field])) {
                    $c_data[$field] = $data[$field];
                } else if (in_array('not_empty', $rules)) {
                    $c_data[$field] = null;
                    $rules = ['not_empty'];
                }

                foreach ($c_data as $key => $value) {
                    $info = [
                        'name' => $key,
                        'tests' => $rules,
                        'value' => $value,
                        'errors' => []
                    ];
                    
                    $results = [];

                    foreach ($rules as $original_rule) {
                        $rule = $original_rule;
                        $rule_name = null;

                        if (isset($this->test_checks[$field][$rule])) {
                            $rule_name = $rule;
                            $rule = $this->test_checks[$field][$rule];
                        }

                        $mods = [];

                        if (is_callable($rule)) {
                            $result = call_user_func($rule, $value);
                            $rule = $rule_name ? $rule_name : 'closure';
                        } else if (is_string($rule)) {
                            if ($this->check_is_regex($rule)) {
                                $rule = 'regex';
                            } else if (strpos($rule, '_') !== false) {
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
        if (is_numeric($value)) $value = (string) $value;

        return ctype_alnum($value);
    }

    /**
     * Numeric test
     */
    public function check_numeric($value) {
        return is_numeric($value);
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
    /**
     * URL test
     */
    public function check_url($value) {
        return filter_var($value, FILTER_VALIDATE_URL) ? true : false;
    }
}
