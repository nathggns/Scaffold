<?php defined('SCAFFOLD') or die;

class AutoloadTest extends PHPUnit_Framework_TestCase {

    public function testNormalAutoloading() {
        $this->assertFalse(class_exists('Test', false));

        file_put_contents($path = SYSTEM . 'classes' . DS . 'test.php', '<?php class Test {}');

        $this->assertTrue(class_exists('Test'));

        unlink($path);
    }

    public function testCustomAutoloading() {
        $this->assertFalse(class_exists('TestClass', false));

        file_put_contents($path = SYSTEM . 'classes' . DS . 'test_class.php', '<?php class TestClass {}');
        Autoload::register(['TestClass' => $path]);

        $this->assertEquals($path, Autoload::$paths['TestClass']);
        $this->assertTrue(class_exists('TestClass'));

        unlink($path);
    }

    public function testThatFileExistingButClassNotReturnsFalse() {

        $id = uniqid();

        $this->assertFalse(class_exists('TestClass_' . $id, false));
        $this->assertFalse(Autoload::load('TestClass_' . $id));

        file_put_contents($path = SYSTEM . 'classes' . DS . 'test_' . $id . '.php', '');
        
        $this->assertFalse(class_exists('TestClass_' . $id, false));
        $this->assertFalse(Autoload::load('TestClass_' . $id));

        unlink($path);
    }

}