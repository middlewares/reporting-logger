<?php

namespace Middlewares\Tests;

use Middlewares\ErrorReportingLogger;
use Middlewares\Utils\Dispatcher;
use Middlewares\Utils\Factory;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;

class ErrorReportingLoggerTest extends TestCase
{
    private function createLogger(): array
    {
        $logs = fopen('php://temp', 'r+');
        $logger = new Logger('test');
        $logger->pushHandler(new StreamHandler($logs));

        return [$logger, $logs];
    }

    public function testErrorReportingLogger()
    {
        list($logger, $logs) = $this->createLogger();

        $request = Factory::createServerRequest([], 'POST', '/report')
            ->withParsedBody(['message' => 'This is an error']);

        $response = Dispatcher::run([
            new ErrorReportingLogger($logger),
            function () {
                return 'no reporting';
            },
        ], $request);

        rewind($logs);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertEmpty((string) $response->getBody());
        $this->assertRegExp('#.* test.ERROR: This is an error .*#', stream_get_contents($logs));
    }

    public function testCustomError()
    {
        list($logger, $logs) = $this->createLogger();

        $request = Factory::createServerRequest([], 'POST', '/report')
            ->withParsedBody(['csp-report' => 'This is an error']);

        $response = Dispatcher::run([
            (new ErrorReportingLogger($logger))->message('csp-report'),
            function () {
                return 'no reporting';
            },
        ], $request);

        rewind($logs);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertEmpty((string) $response->getBody());
        $this->assertRegExp('#.* test.ERROR: This is an error .*#', stream_get_contents($logs));
    }

    public function testCustomPath()
    {
        list($logger, $logs) = $this->createLogger();

        $request = Factory::createServerRequest([], 'POST', '/custom-path')
            ->withParsedBody(['message' => 'This is an error']);

        $response = Dispatcher::run([
            (new ErrorReportingLogger($logger))->path('/custom-path'),
            function () {
                return 'no reporting';
            },
        ], $request);

        rewind($logs);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertEmpty((string) $response->getBody());
        $this->assertRegExp('#.* test.ERROR: This is an error .*#', stream_get_contents($logs));
    }

    public function testMethod()
    {
        list($logger, $logs) = $this->createLogger();

        $request = Factory::createServerRequest([], 'GET', '/report')
            ->withParsedBody(['message' => 'This is an error']);

        $response = Dispatcher::run([
            new ErrorReportingLogger($logger),
            function () {
                return 'no reporting';
            },
        ], $request);

        rewind($logs);

        $this->assertEquals('no reporting', (string) $response->getBody());
        $this->assertEmpty(stream_get_contents($logs));
    }

    public function testPath()
    {
        list($logger, $logs) = $this->createLogger();

        $request = Factory::createServerRequest([], 'POST', '/no-report')
            ->withParsedBody(['message' => 'This is an error']);

        $response = Dispatcher::run([
            new ErrorReportingLogger($logger),
            function () {
                return 'no reporting';
            },
        ], $request);

        rewind($logs);

        $this->assertEquals('no reporting', (string) $response->getBody());
        $this->assertEmpty(stream_get_contents($logs));
    }

    public function testMessage()
    {
        list($logger, $logs) = $this->createLogger();

        $request = Factory::createServerRequest([], 'POST', '/no-report')
            ->withParsedBody(['no-message' => 'This is an error']);

        $response = Dispatcher::run([
            new ErrorReportingLogger($logger),
            function () {
                return 'no reporting';
            },
        ], $request);

        rewind($logs);

        $this->assertEquals('no reporting', (string) $response->getBody());
        $this->assertEmpty(stream_get_contents($logs));
    }
}
