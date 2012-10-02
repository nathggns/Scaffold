<?php

class InflectorTest extends PHPUnit_Framework_Testcase {

    public $words = [
        'money' => 'money', // Uncountable
        'man' => 'men', // Irregular
        'lap' => 'laps', // ends in voiceless consonant
        'kiss' => 'kisses',  // Ends in sibilant sound
        'hero' => 'heroes', // Ends in [constanant]o
        'cherry' => 'cherries', // Ends in y
        'dog' => 'dogs', // other
    ];

    public $others = [
        'User' => 'users',
        'UserFriend' => 'user_friends',
        'Person' => 'people'
    ];

    public function test_pluralize() {
        foreach ($this->words as $k => $v) {
            $this->assertEquals($v, Inflector::pluralize($k));
        }
    }

    public function test_singularize() {
        foreach (array_flip($this->words) as $k => $v) {
            $this->assertEquals($v, Inflector::singularize($k));
        }
    }

    public function test_camelize() {
        $words = [
            'just_a_thing' => 'JustAThing', // underscore,
            'justAThing' => 'JustAThing', // camelCased
            'Just A Thing' => 'JustAThing' // ordinary
        ];

        foreach ($words as $k => $v) {
            $this->assertEquals($v, Inflector::camelize($k));
        }
    }

    public function test_underscore() {
        $words = [
            'JustAThing' => 'just_a_thing', // CamelCased
            'wantSomeMore' => 'want_some_more', // camelCased
            'I love dogs!!1!' => 'i_love_dogs_1' // ordinary
        ];

        foreach ($words as $k => $v) {
            $this->assertEquals($v, Inflector::underscore($k));
        }
    }

    public function test_humanize() {
        $words = [
            'just_a_thing' => 'Just a thing', // underscore
            'WantSomeMore' => 'Want some more', // CamelCased
            'iLoveDogs' => 'I love dogs' // camelCased
        ];
        foreach ($words as $k => $v) {
            $this->assertEquals($v, Inflector::humanize($k));
        }
    }

    public function test_titleize() {
        $words = [
            'just_a_thing' => 'Just A Thing', // underscore
            'WantSomeMore' => 'Want Some More', // CamelCased
            'iLoveDogs' => 'I Love Dogs' // camelCased
        ];

        foreach ($words as $k => $v) {
            $this->assertEquals($v, Inflector::titleize($k));
        }
    }

    public function test_tableize() {
        foreach ($this->others as $k => $v) {
            $this->assertEquals($v, Inflector::tableize($k));
        }
    }

    public function test_classify() {
        foreach (array_flip($this->others) as $k => $v) {
            $this->assertEquals($v, Inflector::classify($k));
        }
    }

    public function test_ordinalize() {
        $words = [
            1 => '1st',
            22 => '22nd',
            333 => '333rd',
            4444 => '4444th'
        ];

        foreach ($words as $k => $v) {
            $this->assertEquals($v, Inflector::ordinalize($k));
        }
    }
}
