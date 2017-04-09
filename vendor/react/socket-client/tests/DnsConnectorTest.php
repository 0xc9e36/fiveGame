<?php

namespace React\Tests\SocketClient;

use React\SocketClient\DnsConnector;
use React\Promise;

class DnsConnectorTest extends TestCase
{
    private $tcp;
    private $resolver;
    private $connector;

    public function setUp()
    {
        $this->tcp = $this->getMock('React\SocketClient\ConnectorInterface');
        $this->resolver = $this->getMockBuilder('React\Dns\Resolver\Resolver')->disableOriginalConstructor()->getMock();

        $this->connector = new DnsConnector($this->tcp, $this->resolver);
    }

    public function testPassByResolverIfGivenIp()
    {
        $this->resolver->expects($this->never())->method('resolve');
        $this->tcp->expects($this->once())->method('create')->with($this->equalTo('127.0.0.1'), $this->equalTo(80))->will($this->returnValue(Promise\reject()));

        $this->connector->create('127.0.0.1', 80);
    }

    public function testPassThroughResolverIfGivenHost()
    {
        $this->resolver->expects($this->once())->method('resolve')->with($this->equalTo('google.com'))->will($this->returnValue(Promise\resolve('1.2.3.4')));
        $this->tcp->expects($this->once())->method('create')->with($this->equalTo('1.2.3.4'), $this->equalTo(80))->will($this->returnValue(Promise\reject()));

        $this->connector->create('google.com', 80);
    }

    public function testSkipConnectionIfDnsFails()
    {
        $this->resolver->expects($this->once())->method('resolve')->with($this->equalTo('example.invalid'))->will($this->returnValue(Promise\reject()));
        $this->tcp->expects($this->never())->method('create');

        $this->connector->create('example.invalid', 80);
    }

    public function testCancelDuringDnsCancelsDnsAndDoesNotStartTcpConnection()
    {
        $pending = new Promise\Promise(function () { }, $this->expectCallableOnce());
        $this->resolver->expects($this->once())->method('resolve')->with($this->equalTo('example.com'))->will($this->returnValue($pending));
        $this->tcp->expects($this->never())->method('resolve');

        $promise = $this->connector->create('example.com', 80);
        $promise->cancel();

        $promise->then($this->expectCallableNever(), $this->expectCallableOnce());
    }

    public function testCancelDuringTcpConnectionCancelsTcpConnection()
    {
        $pending = new Promise\Promise(function () { }, $this->expectCallableOnce());
        $this->resolver->expects($this->once())->method('resolve')->with($this->equalTo('example.com'))->will($this->returnValue(Promise\resolve('1.2.3.4')));
        $this->tcp->expects($this->once())->method('create')->with($this->equalTo('1.2.3.4'), $this->equalTo(80))->will($this->returnValue($pending));

        $promise = $this->connector->create('example.com', 80);
        $promise->cancel();

        $promise->then($this->expectCallableNever(), $this->expectCallableOnce());
    }

    public function testCancelClosesStreamIfTcpResolvesDespiteCancellation()
    {
        $stream = $this->getMockBuilder('React\Stream\Stream')->disableOriginalConstructor()->setMethods(array('close'))->getMock();
        $stream->expects($this->once())->method('close');

        $pending = new Promise\Promise(function () { }, function ($resolve) use ($stream) {
            $resolve($stream);
        });

        $this->resolver->expects($this->once())->method('resolve')->with($this->equalTo('example.com'))->will($this->returnValue(Promise\resolve('1.2.3.4')));
        $this->tcp->expects($this->once())->method('create')->with($this->equalTo('1.2.3.4'), $this->equalTo(80))->will($this->returnValue($pending));

        $promise = $this->connector->create('example.com', 80);
        $promise->cancel();

        $promise->then($this->expectCallableNever(), $this->expectCallableOnce());
    }
}
