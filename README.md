# Laravel Platform Manager

[![Latest Version on Packagist][ico-version]][link-packagist]
[![Software License][ico-license]](LICENSE.md)
[![Total Downloads][ico-downloads]][link-downloads]

## Installation

You can install the package via composer:

```bash
composer require mindtwo/laravel-platform-manager
```

## How to use?

### Publish config

To publish the modules config file simply run

```bash
php artisan vendor:publish config
```
This publishes the `platform-resolver.php` config file to your projects config folder.
Inside the config you can specify your Platform model which will be used by the package.

If you want to use the `Platform` model provided by this package you must run

```bash
php artisan migrate
```

### With Laravel Sanctum

To use the platforms with Laravel's Sanctum package you should add the middleware
`mindtwo\LaravelPlatformManager\Middleware\StatefulPlatformDomais` to your project's
`Kernel.php`. To be concrete to the middlewareGroup `api` or your equivalent.
This middleware adds the plaform's hostnames to Sanctums Stateful Domains.

### Retrieve a platform

To receive the current platform you are working in simply inject `mindtwo\LaravelPlatformManager\Services\PlatformResolver`
to your service, controller, middleware, etc. Via the method `getCurrentPlatform()` you can receive your platform model.

### Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

### Security

If you discover any security related issues, please email info@mindtwo.de instead of using the issue tracker.

## Credits

- [mindtwo GmbH][link-author]
- [All Other Contributors][link-contributors]

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

[ico-version]: https://img.shields.io/packagist/v/mindtwo/laravel-platform-manager.svg?style=flat-square
[ico-license]: https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square
[ico-downloads]: https://img.shields.io/packagist/dt/mindtwo/laravel-platform-manager.svg?style=flat-square
[link-packagist]: https://packagist.org/packages/mindtwo/laravel-platform-manager
[link-downloads]: https://packagist.org/packages/mindtwo/laravel-platform-manager
[link-author]: https://github.com/mindtwo
[link-contributors]: ../../contributors
