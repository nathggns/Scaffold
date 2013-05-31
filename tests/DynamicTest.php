<?php

class DT_A {
    public static function B() {
        return func_get_args();
    }
}

class DynamicTest extends PHPUnit_Framework_TestCase {

    public function testSimpleValues() {
        $dynamic = new Dynamic($props = [
            sha1(mt_rand()) => sha1(mt_rand()),
            sha1(mt_rand()) => sha1(mt_rand())
        ]);

        foreach ($props as $key => $val) {
            $this->assertEquals($val, $dynamic->$key);
        }
    }

    public function testMethodsWithArgs() {
        $dynamic = new Dynamic($props = [
            'func' => function() {
                return func_get_args();
            }
        ]);

        $args = [sha1(mt_rand()), sha1(mt_rand()), sha1(mt_rand())];

        $resp = call_user_func_array([$dynamic, 'func'], $args);

        $this->assertEquals($dynamic, array_shift($resp));
        $this->assertCount(count($args), $resp);

        foreach ($args as $key => $val) {
            $this->assertEquals($val, $resp[$key]);
        }
    }

    public function testNormalMethod() {
        $dynamic = new Dynamic($props = [
            'func' => function() {
                return true;
            }
        ]);

        $this->assertTrue($dynamic->func());
    }

    /**
     * @expectedException       Exception
     * @expectedExceptioMessage Method b not found
     */
    public function testMethodNotFound() {
        $dynamic = new Dynamic([
            'a' => function() {
                return true;
            }
        ]);

        $dynamic->b();
    }

    public function testAliasing() {
        $dynamic = new Dynamic(['DT_A', 'B']);

        $parts = ['C', 'D', 'E'];
        $resp = call_user_func_array([$dynamic, 'C'], array_slice($parts, 1));

        $this->assertCount(count($parts), $resp);

        foreach ($parts as $i => $part) {
            $this->assertEquals($part, $resp[$i]);
        }
    }

    public function testUsingThis() {
        $dynamic = new Dynamic([
            'a' => function() {
                $this->b = 'c';
            }
        ]);

        $dynamic->a();

        $this->assertEquals('c', $dynamic->b);
    }

}