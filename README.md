Scaffold
========

Light Weight PHP API Framework

Requirements
============

 - PHP **5.4+**

Features
========

Scaffold, despite it's ultimate speed and ease of use, is just jam-packed with features.

## Autoloader

At the heart of Scaffold, is it's Autoloader. No longer do you need to care about loading different classes for use in your application. But that doesn't mean your application will be slowed down with useless resources it doesn't need: **Scaffold operates on a *there when you need it, out of the way when you don't* basis**. 

In order to use a class, you just use it:

    <?php
    $validator = new Validate();
    $validator->set('name', 'not_empty');
    $validator->test($data);

But that doesn't mean that's the only way to do things, Scaffold provides a method for manually loading a class.

    <?php
    Autoload::load('Validate');
    Autoload::load('Router');
    // etc

## Validator

You saw a little sneak peek at our validator in the previous section. Scaffold's validator is about as simple as it gets, while still remaining extremely powerful.

### Example

    <?php
    $validator = new Validate([
        'name' => ['not_empty', 'alphanumeric'],
        'email' => ['not_empty', 'email']
    ]);
    $validator->set('password', 'not_empty');
    
    $validator->test(['name' => 'Bob', 'email' => 'scaffold.is.awesome@gmail.com', 'password' => 'scaffold']);
    // Returns true
    
    $validator->test(['name' => '', 'email' => 'scaffold']);
    // Raises ExceptionValidate

### Global Rules

There are a few ways of setting global rules, but under the hood, they all equal the same thing: The field name equaling null.

    <?php
    // All of these are global rules
    $validator = new Validate('not_empty');
    $validator->set('not_empty');
    $validator->set(['not_empty']);
    $validator->set(null, 'not_empty');
    $validator->set([null => 'not_empty']);

### List of Rules

#### empty

`empty` tests for if a value is `'' || false`. In addition, it will also return true if you haven't passed a value for this rule. However, you're unlikely to use this rule yourself. You are more likely to use it in conjuction with the `not` modifier, to make `not_empty`. This can be used to make required fields.

#### email

`email` uses PHP's built in email checking (`filter_var($val, FILTER_VALIDATE_EMAIL)`) systems in order to check if the value is a valid email address.

#### alphanumeric

`alphanumeric` allows any character in the range `[a-zA-Z0-9]`. And character outside of that will make the rest fail.

#### Others

There are a few other rules that are used *behind the scenes*. These are `regex`, `is_regex` and `equal`. There is no way that you can use `is_regex` in your checks, however, you can use `regex` and `equal`.

If the rule name is a valid regex pattern, and doesn't match an existing rule name, then Validate will run a test for the value against that pattern. If it is not, then Validate will simply check if the value matches the rule name.

### List of Modifiers

To use a modifier, you just prepend it's name, followed by an `_` to the rule name.

### not

`not` is simple modifier, it simply reverses the output of the rule.

Take `not_empty` for example. Run `empty` against `''`, and you get `true`. Run `not_empty` against `''`, and you get `false`.

### ExceptionValidate

When a validation error occours, `Validate` throws an `ExceptionValidate` exception. This is almost exactly the same as a normal `Exception`, but you can access the exception errors as `$e->errors`. This array contains all the information you could possibly need: Which tests failed, what tests were ran on what, etc. The following is one of the errors from the previous example.

    array(4) {
      ["name"]=>
        string(4) "name"
      ["tests"]=>
        array(2) {
          [0]=>
            string(9) "not_empty"
          [1]=>
            string(12) "alphanumeric"
        }
      ["value"]=>
        string(0) ""
      ["errors"]=>
        array(2) {
          [0]=>
            array(4) {
              ["result"]=>
                bool(false)
              ["rule"]=>
                string(9) "not_empty"
              ["value"]=>
                string(0) ""
              ["type"]=>
                int(1)
            }
          [1]=>
            array(4) {
              ["result"]=>
                bool(false)
              ["rule"]=>
                string(12) "alphanumeric"
              ["value"]=>
                string(0) ""
              ["type"]=>
                int(1)
            }
      }
    }
    
    
The bit we care about is the errors property, which should be self explanatory, besides `type` and `result`.

`result` is the raw response from the `check` function called, after all the `modifier` functions are called. Check functions are your standard `email`, `alphanumeric` checks and `modifiers` are the bits prepended with an `_`, the only current one being `not`.

`type` represents the type of validation error that has occured. This can either be `Validate::TEST_FAILED`, for if a test actually fails, or `Validate::INVALID_DATA` for if you give test a numeric array instead of an associative array. 