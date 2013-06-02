<?php

class ResponseTest extends PHPUnit_Framework_Testcase {

    public function setUp() {
        $this->response = new Response();
    }

    /**
     * @covers Response::encoder
     */
    public function testEncoder() {
        $this->response->encoder('test');
        $this->assertEquals($this->response->encoder, 'test');
    }

    /**
     * @covers Response::data
     */
    public function testData() {
        $this->response->data([
            'foo' => 'bar',
            'baz' => 'bla'
        ]);

        $this->response->data([
            'baz' => 'test',
            'int' => 5
        ]);

        $this->assertEquals($this->response->data, [
            'foo' => 'bar',
            'baz' => 'test',
            'int' => 5
        ]);
    }

    /**
     * @covers  Response::encode
     * @depends testEncoder
     * @depends testData
     */
    public function testEncode() {
        $data = ['foo', 'bar'];
        $json = json_encode($data);
        $this->response->data($data);

        $body = $this->response->encode(false);
        $this->assertEquals($json, $body);

        $body = $this->response->encoder(function($data) {
            return json_encode($data);
        });

        $body = $this->response->encode(false);
        $this->assertEquals($json, $body);

        $this->assertEquals($json, $this->response->encode()->body);
    }

    /**
     * @covers  Response::send
     * @depends testEncode
     * @depends testData
     */
    public function testSend() {
        $data = ['foo' => 'bar', 'baz' => 5];

        $this->expectOutputString(json_encode($data));

        $this->response->data($data);
        $this->response->send();
    }

    /**
     * @covers  Response::send
     * @depends testData
     * @depends testSend
     */
    public function testSendWithBody() {
        $data = ['foo' => 'bar', 'baz' => 5];

        $this->expectOutputString('test');

        $this->response->body = 'test';
        $this->response->data($data);
        $this->response->send();
    }

    /**
     * @covers  Response::send
     * @depends testEncode
     * @depends testData
     */
    public function testSendTwice() {
        $this->expectOutputString('');

        $this->response->body = '';
        $this->response->send();

        $this->response->body = 'test';
        $this->response->send();
    }

}
