<?php defined('SCAFFOLD') or die;

class ET_EventedTestClass {
  use Events;
}

class EventsTest extends PHPUnit_Framework_TestCase {

  protected $test;
  protected $callback;
  protected $triggered = false;

  public function setUp() {
    $this->callback = function($value) {
      $this->triggered = $value;
    };

    $this->test = new ET_EventedTestClass();
  }

  /**
   * @covers Events::on
   * @covers Events::trigger
   */
  public function testTriggerEvent() {
    $this->test->on('foo', $this->callback);
    $this->test->trigger('foo', true);

    $this->assertTrue(true);
  }

  /**
   * @covers Events::on
   * @covers Events::trigger
   */
  public function testTriggerSubEvent() {
    $this->test->on('foo.bar', $this->callback);
    $this->test->trigger('foo.bar', true);

    $this->assertTrue(true);
  }

  /**
   * @covers Events::on
   * @covers Events::trigger
   */
  public function testTriggerSuperiorEvent() {
    $this->test->on('foo.bar', $this->callback);
    $this->test->trigger('foo', true);

    $this->assertTrue(true);
  }

  /**
   * @covers Events::on
   * @covers Events::trigger
   */
  public function testTriggerGeneralEvent() {
    $this->test->on($this->callback);
    $this->test->trigger('foo', true);

    $this->assertTrue(true);
  }

  /**
   * @covers Events::on
   * @covers Events::trigger
   */
  public function testTriggerMultipleEvents() {
    $triggers = 0;

    $callback = function($step) use (&$triggers) {
      $triggers += $step;
    };

    $this->test->on('foo', $callback);
    $this->test->on('foo', $callback);
    $this->test->on('foo', $callback);
    $this->test->on('foo', $callback);

    $this->test->trigger('foo', 1);

    $this->assertEquals(4, $triggers);
  }

  /**
   * @covers Events::off
   */
  public function testOff() {
    $this->test->on('foo', $this->callback);
    $this->test->off('foo');

    $events = $this->test->getEvents('foo');

    $this->assertEmpty($events);
  }

  /**
   * @covers Events::off
   */
  public function testOffWithSubEvent() {
    $this->test->on('foo.bar', $this->callback);
    $this->test->off('foo.bar');

    $events = $this->test->getEvents('foo.bar');

    $this->assertEmpty($events);
  }

  /**
   * @covers Events::off
   */
  public function testOffWithSuperiorEvent() {
    $this->test->on('foo.bar', $this->callback);
    $this->test->off('foo');

    $events = $this->test->getEvents('foo');

    $this->assertEmpty($events);
  }

  /**
   * @covers Events::off
   */
  public function testOffWithCallback() {
    $this->test->on('foo', $this->callback);
    $this->test->on('foo', function() {});

    $this->test->off('foo', $this->callback);

    $callbacks = $this->test->getCallbacks();

    $this->assertCount(1, $callbacks);
  }

  /**
   * @covers Events::off
   */
  public function testOffWithoutEvent() {
    $this->test->on($this->callback);
    $this->test->on('foo', $this->callback);
    $this->test->off();

    $events = $this->test->getEvents();

    $this->assertCount(1, $events);
  }

  /**
   * @covers Events::getEvents
   */
  public function testGetEvents() {
    $this->test->on($this->callback);
    $this->test->on('foo', $this->callback);
    $this->test->on('foo.bar', $this->callback);
    $this->test->on('foo.bar', $this->callback);
    $this->test->on('foo.bar.baz', $this->callback);
    $this->test->on('bar', $this->callback);
    $this->test->on('bar.foo', $this->callback);

    $events = $this->test->getEvents('foo');

    $this->assertEquals(['foo', 'foo.bar', 'foo.bar.baz'], $events);
  }

  /**
   * @covers Events::getEvents
   */
  public function testGetEventsWithoutSearch() {
    $this->test->on($this->callback);
    $this->test->on('foo', $this->callback);
    $this->test->on('foo.bar', $this->callback);
    $this->test->on('foo.bar', $this->callback);
    $this->test->on('foo.bar.baz', $this->callback);
    $this->test->on('bar', $this->callback);
    $this->test->on('bar.foo', $this->callback);

    $events = $this->test->getEvents();

    $this->assertEquals(['', 'foo', 'foo.bar', 'foo.bar.baz', 'bar', 'bar.foo'], $events);
  }

  /**
   * @covers Events::getCallbacks
   */
  public function testGetCallbacks() {
    $this->test->on($this->callback);
    $this->test->on('foo', $this->callback);
    $this->test->on('foo.bar', $this->callback);

    $callbacks = $this->test->getCallbacks();

    $this->assertCount(3, $callbacks);
  }

}
