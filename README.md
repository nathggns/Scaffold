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
    $validator = new Validate(array(
        'name' => ['not_empty', 'alphanumeric'],
        'email' => ['not_empty', 'email']
    ));
    $validator->set('password', 'not_empty');
    
    $validator->test(array('name' => 'Bob', 'email' => 'scaffold.is.awesome@gmail.com', 'password' => 'scaffold'));
    // Returns true
    
    $validator->test(array('name' => '', 'email' => 'scaffold'));
    // Raises ExceptionValidate


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

`type`, at the moment, is always going to be `1`, which represents `Validate::TEST_FAILED`. In the future, there may be other types of errors, which is why it is included.