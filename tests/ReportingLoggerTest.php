<?php

namespace Middlewares\Tests;

use Middlewares\ReportingLogger;
use Middlewares\Utils\Dispatcher;
use Middlewares\Utils\Factory;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;

class ReportingLoggerTest extends TestCase
{
    private function createLogger(): array
    {
        $logs = fopen('php://temp', 'r+');
        $logger = new Logger('test');
        $logger->pushHandler(new StreamHandler($logs));

        return [$logger, $logs];
    }

    public function testReportingLogger()
    {
        list($logger, $logs) = $this->createLogger();

        $request = Factory::createServerRequest([], 'POST', '/report')
            ->withParsedBody(['message' => 'This is an error']);

        $response = Dispatcher::run([
            new ReportingLogger($logger),
            function () {
                return 'no reporting';
            },
        ], $request);

        rewind($logs);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertEmpty((string) $response->getBody());
        $this->assertRegExp('#.* test.ERROR: Reporting .*#', stream_get_contents($logs));
    }

    public function testCustomMessage()
    {
        list($logger, $logs) = $this->createLogger();

        $request = Factory::createServerRequest([], 'POST', '/report')
            ->withParsedBody(['message' => 'This is an error']);

        $response = Dispatcher::run([
            (new ReportingLogger($logger))->message('Csp Error'),
            function () {
                return 'no reporting';
            },
        ], $request);

        rewind($logs);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertEmpty((string) $response->getBody());
        $this->assertRegExp('#.* test.ERROR: Csp Error .*#', stream_get_contents($logs));
    }

    public function testCustomMessageWithVariable()
    {
        list($logger, $logs) = $this->createLogger();

        $request = Factory::createServerRequest([], 'POST', '/report')
            ->withParsedBody(['csp-error' => 'This is a specific error']);

        $response = Dispatcher::run([
            (new ReportingLogger($logger))->message('Error: %{csp-error}'),
            function () {
                return 'no reporting';
            },
        ], $request);

        rewind($logs);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertEmpty((string) $response->getBody());
        $this->assertRegExp('#.* test.ERROR: Error: This is a specific error .*#', stream_get_contents($logs));
    }

    public function testCustomPath()
    {
        list($logger, $logs) = $this->createLogger();

        $request = Factory::createServerRequest([], 'POST', '/custom-path')
            ->withParsedBody(['message' => 'This is an error']);

        $response = Dispatcher::run([
            (new ReportingLogger($logger))->path('/custom-path'),
            function () {
                return 'no reporting';
            },
        ], $request);

        rewind($logs);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertEmpty((string) $response->getBody());
        $this->assertRegExp('#.* test.ERROR: Reporting .*#', stream_get_contents($logs));
    }

    public function testMethod()
    {
        list($logger, $logs) = $this->createLogger();

        $request = Factory::createServerRequest([], 'GET', '/report')
            ->withParsedBody(['message' => 'This is an error']);

        $response = Dispatcher::run([
            new ReportingLogger($logger),
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
            new ReportingLogger($logger),
            function () {
                return 'no reporting';
            },
        ], $request);

        rewind($logs);

        $this->assertEquals('no reporting', (string) $response->getBody());
        $this->assertEmpty(stream_get_contents($logs));
    }

    public function testEmptyData()
    {
        list($logger, $logs) = $this->createLogger();

        $request = Factory::createServerRequest([], 'POST', '/report');

        $response = Dispatcher::run([
            new ReportingLogger($logger),
            function () {
                return 'no reporting';
            },
        ], $request);

        rewind($logs);

        $this->assertEquals('no reporting', (string) $response->getBody());
        $this->assertEmpty(stream_get_contents($logs));
    }

    public function testCspReport()
    {
        list($logger, $logs) = $this->createLogger();

        $request = Factory::createServerRequest([], 'POST', '/report')
            ->withParsedBody([
                'csp-report' => [
                    'document-uri' => 'https://example.com/',
                    'referrer' => 'https://www.google.com/',
                    'violated-directive' => 'script-src',
                    'effective-directive' => 'script-src',
                    'original-policy' => "connect-src 'self'; frame-ancestors 'self'; script-src 'self';",
                    'disposition' => 'enforce',
                    'blocked-uri' => 'https://external.com/assets/script.js',
                    'line-number' => 1,
                    'column-number' => 100,
                    'status-code' => 0,
                    'script-sample' => '',
                ],
            ]);

        $response = Dispatcher::run([
            new ReportingLogger($logger),
            function () {
                return 'no reporting';
            },
        ], $request);

        rewind($logs);

        $this->assertEmpty((string) $response->getBody());
        $this->assertRegExp('#.* test.ERROR: Reporting \{"csp-report"\:\{.*#', stream_get_contents($logs));
    }
}
