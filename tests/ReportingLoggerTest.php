<?php

declare(strict_types = 1);

namespace Middlewares\Tests;

use Middlewares\ReportingLogger;
use Middlewares\Utils\Dispatcher;
use Middlewares\Utils\Factory;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class ReportingLoggerTest extends TestCase
{
    /**
     * phpunit 8 support
     */
    public static function assertMatchesRegularExpression(string $pattern, string $string, string $message = ''): void
    {
        if (method_exists(parent::class, 'assertMatchesRegularExpression')) {
            parent::assertMatchesRegularExpression($pattern, $string, $message);

            return;
        }

        self::assertRegExp($pattern, $string, $message);
    }

    //     * @return list{0: resource, 1: LoggerInterface}
    /**
     * @return resource[]|LoggerInterface[]
     */
    private function createLogger(): array
    {
        $logs = fopen('php://temp', 'r+');
        $logger = new Logger('test');
        $logger->pushHandler(new StreamHandler($logs));

        return [$logger, $logs];
    }

    public function testReportingLogger(): void
    {
        list($logger, $logs) = $this->createLogger();

        $request = Factory::createServerRequest('POST', '/report')
            ->withParsedBody(['message' => 'This is an error']);

        $response = Dispatcher::run([
            new ReportingLogger($logger),
            function () {
                return 'no reporting';
            },
        ], $request);

        rewind($logs);

        self::assertSame(200, $response->getStatusCode());
        self::assertEmpty((string) $response->getBody());
        self::assertMatchesRegularExpression('#.* test.ERROR: Reporting .*#', stream_get_contents($logs));
    }

    public function testCustomMessage(): void
    {
        list($logger, $logs) = $this->createLogger();

        $request = Factory::createServerRequest('POST', '/report')
            ->withParsedBody(['message' => 'This is an error']);

        $response = Dispatcher::run([
            (new ReportingLogger($logger))->message('Csp Error'),
            function () {
                return 'no reporting';
            },
        ], $request);

        rewind($logs);

        self::assertSame(200, $response->getStatusCode());
        self::assertEmpty((string) $response->getBody());
        self::assertMatchesRegularExpression('#.* test.ERROR: Csp Error .*#', stream_get_contents($logs));
    }

    public function testCustomMessageWithVariable(): void
    {
        list($logger, $logs) = $this->createLogger();

        $request = Factory::createServerRequest('POST', '/report')
            ->withParsedBody(['var1' => 'Foo', 'var2' => null, 'var3' => [1, 2, 3]]);

        $response = Dispatcher::run([
            (new ReportingLogger($logger))->message('Error: %{var1} in %{var2} and %{var3}'),
            function () {
                return 'no reporting';
            },
        ], $request);

        rewind($logs);

        self::assertSame(200, $response->getStatusCode());
        self::assertEmpty((string) $response->getBody());
        self::assertMatchesRegularExpression(
            '#.* test.ERROR: Error: Foo in %{var2} and \[1,2,3\] .*#',
            stream_get_contents($logs)
        );
    }

    public function testCustomPath(): void
    {
        list($logger, $logs) = $this->createLogger();

        $request = Factory::createServerRequest('POST', '/custom-path')
            ->withParsedBody(['message' => 'This is an error']);

        $response = Dispatcher::run([
            (new ReportingLogger($logger))->path('/custom-path'),
            function () {
                return 'no reporting';
            },
        ], $request);

        rewind($logs);

        self::assertSame(200, $response->getStatusCode());
        self::assertEmpty((string) $response->getBody());
        self::assertMatchesRegularExpression('#.* test.ERROR: Reporting .*#', stream_get_contents($logs));
    }

    public function testMethod(): void
    {
        list($logger, $logs) = $this->createLogger();

        $request = Factory::createServerRequest('GET', '/report')
            ->withParsedBody(['message' => 'This is an error']);

        $response = Dispatcher::run([
            new ReportingLogger($logger),
            function () {
                return 'no reporting';
            },
        ], $request);

        rewind($logs);

        self::assertEquals('no reporting', (string) $response->getBody());
        self::assertEmpty(stream_get_contents($logs));
    }

    public function testPath(): void
    {
        list($logger, $logs) = $this->createLogger();

        $request = Factory::createServerRequest('POST', '/no-report')
            ->withParsedBody(['message' => 'This is an error']);

        $response = Dispatcher::run([
            new ReportingLogger($logger),
            function () {
                return 'no reporting';
            },
        ], $request);

        rewind($logs);

        self::assertEquals('no reporting', (string) $response->getBody());
        self::assertEmpty(stream_get_contents($logs));
    }

    public function testEmptyData(): void
    {
        list($logger, $logs) = $this->createLogger();

        $request = Factory::createServerRequest('POST', '/report');

        $response = Dispatcher::run([
            new ReportingLogger($logger),
            function () {
                return 'no reporting';
            },
        ], $request);

        rewind($logs);

        self::assertEquals('no reporting', (string) $response->getBody());
        self::assertEmpty(stream_get_contents($logs));
    }

    public function testCspReport(): void
    {
        list($logger, $logs) = $this->createLogger();

        $request = Factory::createServerRequest('POST', '/report')
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

        self::assertEmpty((string) $response->getBody());
        self::assertMatchesRegularExpression(
            '#.* test.ERROR: Reporting \{"csp-report"\:\{.*#',
            stream_get_contents($logs)
        );
    }
}
