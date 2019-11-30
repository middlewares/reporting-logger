# middlewares/reporting-logger

[![Latest Version on Packagist][ico-version]][link-packagist]
[![Software License][ico-license]](LICENSE)
[![Build Status][ico-travis]][link-travis]
[![Quality Score][ico-scrutinizer]][link-scrutinizer]
[![Total Downloads][ico-downloads]][link-downloads]

Middleware to log server-side reportings, like CSP messages or any javascript error. More info about [how collect javascript errors](https://developer.mozilla.org/en-US/docs/Web/API/GlobalEventHandlers/onerror).
You may need also the [middlewares/payload](https://github.com/middlewares/payload) (or any other middleware with the same purpose) to parse the json of the body.

## Requirements

* PHP >= 7.2
* A [PSR-7 http library](https://github.com/middlewares/awesome-psr15-middlewares#psr-7-implementations)
* A [PSR-15 middleware dispatcher](https://github.com/middlewares/awesome-psr15-middlewares#dispatcher)
* A [PSR-3 logger library](https://www.php-fig.org/psr/psr-3/)

## Installation

This package is installable and autoloadable via Composer as [middlewares/reporting-logger](https://packagist.org/packages/middlewares/reporting-logger).

```sh
composer require middlewares/reporting-logger
```

## Example

Register a error handler in your javascript code:

```js
window.onerror = function (message, file, lineNo, colNo) {
    const error = { message, file, lineNo, colNo };
    const blob = new Blob([ JSON.stringify(error) ], { type: 'application/json' });

    navigator.sendBeacon('/report', blob);
}
```

```php
Dispatcher::run([
    new Middlewares\JsonPayload(),
    new Middlewares\ReportingLogger($logger)
]);
```

## Usage

You need a `Psr\Log\LoggerInterface` instance to handle the logs, for example, [monolog](https://github.com/Seldaek/monolog)

```php
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

$logger = new Logger('access');
$logger->pushHandler(new StreamHandler('data/logs.txt'));

Dispatcher::run([
    new Middlewares\ReportingLogger($logger)
]);
```

Optionally, you can provide a `Psr\Http\Message\ResponseFactoryInterface` as the second argument, that will be used to create the responses returned after handle the reporting. If it's not defined, [Middleware\Utils\Factory](https://github.com/middlewares/utils#factory) will be used to detect it automatically.

```php
$responseFactory = new MyOwnResponseFactory();

$reporting = new Middlewares\ReportingLogger($logger, $responseFactory);
```

### path

The uri path where the logs will be reported. By default is `/report`.

```js
// In front-end: send the error to "/log-reporting" path
navigator.sendBeacon('/log-reporting', error);
```

```php
// In back-end: configure to collect all reportings send to the same path
$reporting = (new Middlewares\ReportingLogger($logger))->path('/log-reporting')
```

### message

The message used to save the logs. You can use the strings `%{varname}` to generate dinamic messages using the reporting data. For example:

```php
$reporting = (new Middlewares\ReportingLogger($logger))
    ->message('New error: "%{message}" in line %{lineNumber}, column %{colNumber}')
]);
```

---

Please see [CHANGELOG](CHANGELOG.md) for more information about recent changes and [CONTRIBUTING](CONTRIBUTING.md) for contributing details.

The MIT License (MIT). Please see [LICENSE](LICENSE) for more information.

[ico-version]: https://img.shields.io/packagist/v/middlewares/reporting-logger.svg?style=flat-square
[ico-license]: https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square
[ico-travis]: https://img.shields.io/travis/middlewares/reporting-logger/master.svg?style=flat-square
[ico-scrutinizer]: https://img.shields.io/scrutinizer/g/middlewares/reporting-logger.svg?style=flat-square
[ico-downloads]: https://img.shields.io/packagist/dt/middlewares/reporting-logger.svg?style=flat-square

[link-packagist]: https://packagist.org/packages/middlewares/reporting-logger
[link-travis]: https://travis-ci.org/middlewares/reporting-logger
[link-scrutinizer]: https://scrutinizer-ci.com/g/middlewares/reporting-logger
[link-downloads]: https://packagist.org/packages/middlewares/reporting-logger
