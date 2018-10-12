<?php
declare(strict_types = 1);

namespace Middlewares;

use Middlewares\Utils\Traits\HasResponseFactory;
use Middlewares\Utils\Factory;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;

class ReportingLogger implements MiddlewareInterface
{
    use HasResponseFactory;

    /**
     * @var string
     */
    private $message = 'Reporting';

    /**
     * @var string
     */
    private $path = '/report';

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(LoggerInterface $logger, ResponseFactoryInterface $responseFactory = null)
    {
        $this->logger = $logger;
        $this->responseFactory = $responseFactory ?: Factory::getResponseFactory();
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
     * Configure the message used to save the data
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
            return $this->createResponse();
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

        if (empty($data)) {
            return false;
        }

        $this->logger->error(self::getMessage($this->message, $data), $data);

        return true;
    }

    /**
     * Search and replace all %{varname} with the values from the reporting data
     */
    private static function getMessage(string $message, array $data): string
    {
        return preg_replace_callback(
            '/%\{([^\}]+)\}/',
            function (array $matches) use ($data) {
                $val = $data[$matches[1]] ?? $matches[0];

                if (is_scalar($val)) {
                    return $val;
                }

                return json_encode($val);
            },
            $message
        );
    }
}
