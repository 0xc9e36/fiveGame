<?php

use Clue\React\Redis\StreamingClient;
use Clue\Redis\Protocol\Parser\ParserException;
use Clue\Redis\Protocol\Model\IntegerReply;
use Clue\Redis\Protocol\Model\BulkReply;
use Clue\Redis\Protocol\Model\ErrorReply;
use Clue\Redis\Protocol\Model\MultiBulkReply;
use Clue\React\Redis\Client;
use Clue\Redis\Protocol\Model\StatusReply;

class StreamingClientTest extends TestCase
{
    private $stream;
    private $parser;
    private $serializer;
    private $client;

    public function setUp()
    {
        $this->stream = $this->getMockBuilder('React\Stream\Stream')->disableOriginalConstructor()->setMethods(array('write', 'close', 'resume', 'pause'))->getMock();
        $this->parser = $this->getMock('Clue\Redis\Protocol\Parser\ParserInterface');
        $this->serializer = $this->getMock('Clue\Redis\Protocol\Serializer\SerializerInterface');

        $this->client = new StreamingClient($this->stream, $this->parser, $this->serializer);
    }

    public function testSending()
    {
        $this->serializer->expects($this->once())->method('getRequestMessage')->with($this->equalTo('ping'))->will($this->returnValue('message'));
        $this->stream->expects($this->once())->method('write')->with($this->equalTo('message'));

        $this->client->ping();
    }

    public function testClosingClientEmitsEvent()
    {
        //$this->client->on('close', $this->expectCallableOnce());

        $this->client->close();
    }

    public function testClosingStreamClosesClient()
    {
        $this->client->on('close', $this->expectCallableOnce());

        $this->stream->emit('close');
    }

    public function testReceiveParseErrorEmitsErrorEvent()
    {
        $this->client->on('error', $this->expectCallableOnce());
        //$this->client->on('close', $this->expectCallableOnce());

        $this->parser->expects($this->once())->method('pushIncoming')->with($this->equalTo('message'))->will($this->throwException(new ParserException()));
        $this->stream->emit('data', array('message'));
    }

    public function testReceiveMessageEmitsEvent()
    {
        $this->client->on('data', $this->expectCallableOnce());

        $this->parser->expects($this->once())->method('pushIncoming')->with($this->equalTo('message'))->will($this->returnValue(array(new IntegerReply(2))));
        $this->stream->emit('data', array('message'));
    }

    public function testReceiveThrowMessageEmitsErrorEvent()
    {
        $this->client->on('data', $this->expectCallableOnce());
        $this->client->on('data', function() {
            throw new UnderflowException();
        });

        $this->client->on('error', $this->expectCallableOnce());

        $this->parser->expects($this->once())->method('pushIncoming')->with($this->equalTo('message'))->will($this->returnValue(array(new IntegerReply(2))));
        $this->stream->emit('data', array('message'));
    }

    public function testDefaultCtor()
    {
        $client = new StreamingClient($this->stream);
    }

    public function testPingPong()
    {
        $this->serializer->expects($this->once())->method('getRequestMessage')->with($this->equalTo('ping'));

        $promise = $this->client->ping();

        $this->client->handleMessage(new BulkReply('PONG'));

        $this->expectPromiseResolve($promise);
        $promise->then($this->expectCallableOnce('PONG'));
    }

    /**
     * @expectedException UnderflowException
     */
    public function testInvalidMonitor()
    {
        $this->client->handleMessage(new StatusReply('+1409171800.312243 [0 127.0.0.1:58542] "ping"'));
    }

    public function testMonitor()
    {
        $this->serializer->expects($this->once())->method('getRequestMessage')->with($this->equalTo('monitor'));

        $promise = $this->client->monitor();

        $this->client->handleMessage(new StatusReply('OK'));

        $this->expectPromiseResolve($promise);
        $promise->then($this->expectCallableOnce('OK'));
    }

    public function testMonitorEventFromOtherConnection()
    {
        // enter MONITOR mode
        $client = $this->client;
        $client->monitor();
        $client->handleMessage(new StatusReply('OK'));

        // expect a single "monitor" event when a matching status reply comes in
        $client->on('monitor', $this->expectCallableOnce());
        $client->handleMessage(new StatusReply('1409171800.312243 [0 127.0.0.1:58542] "ping"'));
    }

    public function testMonitorEventFromPingMessage()
    {
        // enter MONITOR mode
        $client = $this->client;
        $client->monitor();
        $client->handleMessage(new StatusReply('OK'));

        // expect a single "monitor" event when a matching status reply comes in
        // ignore the status reply for the command executed (ping)
        $client->on('monitor', $this->expectCallableOnce());
        $client->ping();
        $client->handleMessage(new StatusReply('1409171800.312243 [0 127.0.0.1:58542] "ping"'));
        $client->handleMessage(new StatusReply('PONG'));
    }

    public function testErrorReply()
    {
        $promise = $this->client->invalid();

        $err = new ErrorReply("ERR unknown command 'invalid'");
        $this->client->handleMessage($err);

        $this->expectPromiseReject($promise);
        $promise->then(null, $this->expectCallableOnce($err));
    }

    public function testClosingClientRejectsAllRemainingRequests()
    {
        $promise = $this->client->ping();
        $this->assertTrue($this->client->isBusy());

        $this->client->close();

        $this->expectPromiseReject($promise);
        $this->assertFalse($this->client->isBusy());
    }

    public function testClosedClientRejectsAllNewRequests()
    {
        $this->client->close();

        $promise = $this->client->ping();

        $this->expectPromiseReject($promise);
        $this->assertFalse($this->client->isBusy());
    }

    public function testEndingNonBusyClosesClient()
    {
        $this->assertFalse($this->client->isBusy());

        $this->client->on('close', $this->expectCallableOnce());
        $this->client->end();
    }

    public function testEndingBusyClosesClientWhenNotBusyAnymore()
    {
        // count how often the "close" method has been called
        $closed = 0;
        $this->client->on('close', function() use (&$closed) {
            ++$closed;
        });

        $promise = $this->client->ping();
        $this->assertEquals(0, $closed);

        $this->client->end();
        $this->assertEquals(0, $closed);

        $this->client->handleMessage(new BulkReply('PONG'));
        $promise->then($this->expectCallableOnce('PONG'));
        $this->assertEquals(1, $closed);
    }

    public function testClosingMultipleTimesEmitsOnce()
    {
        $this->client->on('close', $this->expectCallableOnce());

        $this->client->close();
        $this->client->close();
    }

    public function testReceivingUnexpectedMessageThrowsException()
    {
        $this->setExpectedException('UnderflowException');
        $this->client->handleMessage(new BulkReply('PONG'));
    }

    public function testPubsubSubscribe()
    {
        $promise = $this->client->subscribe('test');
        $this->expectPromiseResolve($promise);

        $this->client->on('subscribe', $this->expectCallableOnce());
        $this->client->handleMessage(new MultiBulkReply(array(new BulkReply('subscribe'), new BulkReply('test'), new IntegerReply(1))));

        return $this->client;
    }

    /**
     * @depends testPubsubSubscribe
     * @param Client $client
     */
    public function testPubsubPatternSubscribe(Client $client)
    {
         $promise = $client->psubscribe('demo_*');
         $this->expectPromiseResolve($promise);

         $client->on('psubscribe', $this->expectCallableOnce());
         $client->handleMessage(new MultiBulkReply(array(new BulkReply('psubscribe'), new BulkReply('demo_*'), new IntegerReply(1))));

        return $client;
    }

    /**
     * @depends testPubsubPatternSubscribe
     * @param Client $client
     */
    public function testPubsubMessage(Client $client)
    {
        $client->on('message', $this->expectCallableOnce());
        $client->handleMessage(new MultiBulkReply(array(new BulkReply('message'), new BulkReply('test'), new BulkReply('payload'))));
    }

    public function testPubsubSubscribeSingleOnly()
    {
        $this->expectPromiseReject($this->client->subscribe('a', 'b'));
        $this->expectPromiseReject($this->client->unsubscribe('a', 'b'));
        $this->expectPromiseReject($this->client->unsubscribe());
    }
}
