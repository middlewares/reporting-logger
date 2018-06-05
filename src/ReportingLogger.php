<?php
declare(strict_types = 1);

namespace Middlewares;

use Middlewares\Utils\Factory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;

class ReportingLogger implements MiddlewareInterface
{
    /**
     * @var string
     */
    private $message = 'message';

    /**
     * @var string
     */
    private $path = '/report';

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Configure the path used
     */
    public function path(string $path): self
    {
        $this->path = $path;

        return $this;
    }

    /**
     * Configure the message key used to save the data
     */
    public function message(string $message): self
    {
        $this->message = $message;

        return $this;
    }

    /**
     * Process a request and return a response.
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if ($this->logReport($request)) {
            return Factory::createResponse();
        }

        return $handler->handle($request);
    }

    /**
     * Handle the log reporting
     * Returns true if the request is a report, false otherwise
     */
    private function logReport(ServerRequestInterface $request): bool
    {
        if ($request->getMethod() !== 'POST' || $request->getUri()->getPath() !== $this->path) {
            return false;
        }

        $data = (array) $request->getParsedBody();

        if (empty($data[$this->message])) {
            return false;
        }

        $this->logger->error($data[$this->message], $data);

        return true;
    }
}
