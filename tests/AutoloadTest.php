<?php defined('SCAFFOLD') or die;

class AutoloadTest extends PHPUnit_Framework_TestCase {

    static $paths = [];

    public function testNormalAutoloading() {
        $this->assertFalse(class_exists('Test', false));
        file_put_contents($path = SYSTEM . 'classes' . DS . 'test.php', '<?php class Test {}');
        static::$paths[] = $path;
        $this->assertTrue(class_exists('Test'));
    }

    public function testCustomAutoloading() {
        $this->assertFalse(class_exists('TestClass', false));
        file_put_contents($path = SYSTEM . 'classes' . DS . 'test_class.php', '<?php class TestClass {}');
        static::$paths[] = $path;
        Autoload::register(['TestClass' => $path]);
        $this->assertEquals($path, Autoload::$paths['TestClass']);
        $this->assertTrue(class_exists('TestClass'));
    }

    public static function tearDownAfterClass() {
        foreach (static::$paths as $path) unlink($path);
    }

}