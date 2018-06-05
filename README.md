# middlewares/reporting-logger

[![Latest Version on Packagist][ico-version]][link-packagist]
[![Software License][ico-license]](LICENSE)
[![Build Status][ico-travis]][link-travis]
[![Quality Score][ico-scrutinizer]][link-scrutinizer]
[![Total Downloads][ico-downloads]][link-downloads]
[![SensioLabs Insight][ico-sensiolabs]][link-sensiolabs]

Middleware to log server-side reportings, like CSP messages or any javascript error.

## Requirements

* PHP >= 7.0
* A [PSR-7](https://packagist.org/providers/psr/http-message-implementation) http message implementation ([Diactoros](https://github.com/zendframework/zend-diactoros), [Guzzle](https://github.com/guzzle/psr7), [Slim](https://github.com/slimphp/Slim), etc...)
* A [PSR-15 middleware dispatcher](https://github.com/middlewares/awesome-psr15-middlewares#dispatcher)

## Installation

This package is installable and autoloadable via Composer as [middlewares/reporting-logger](https://packagist.org/packages/middlewares/reporting-logger).

```sh
composer require middlewares/reporting-logger
```

## Example

Register a error handler in your javascript code:

```js
window.onerror = function (message, file, lineNo, colNo) {
    const error = {message, file, lineNo, colNo};
    navigator.sendBeacon('/report', JSON.stringify(err));
}
```

```php
$dispatcher = new Dispatcher([
    new Middlewares\ReportingLogger($logger)
]);

$response = $dispatcher->dispatch(new ServerRequest());
```

## Options

#### `__construct(Psr\Log\LoggerInterface $log)`

A PSR logger implementation used to save the logs.

#### `path(string $path)`

The path where the logs will be reported. By default is '/report'.

### `message(strign $message)`

The key used to get the log message from the parsed body. By default is `message`. If the key does not exists in the parsed body, the data wont be saved.

---

Please see [CHANGELOG](CHANGELOG.md) for more information about recent changes and [CONTRIBUTING](CONTRIBUTING.md) for contributing details.

The MIT License (MIT). Please see [LICENSE](LICENSE) for more information.

[ico-version]: https://img.shields.io/packagist/v/middlewares/reporting-logger.svg?style=flat-square
[ico-license]: https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square
[ico-travis]: https://img.shields.io/travis/middlewares/reporting-logger/master.svg?style=flat-square
[ico-scrutinizer]: https://img.shields.io/scrutinizer/g/middlewares/reporting-logger.svg?style=flat-square
[ico-downloads]: https://img.shields.io/packagist/dt/middlewares/reporting-logger.svg?style=flat-square
[ico-sensiolabs]: https://img.shields.io/sensiolabs/i/{project_id_here}.svg?style=flat-square

[link-packagist]: https://packagist.org/packages/middlewares/reporting-logger
[link-travis]: https://travis-ci.org/middlewares/reporting-logger
[link-scrutinizer]: https://scrutinizer-ci.com/g/middlewares/reporting-logger
[link-downloads]: https://packagist.org/packages/middlewares/reporting-logger
[link-sensiolabs]: https://insight.sensiolabs.com/projects/{project_id_here}
