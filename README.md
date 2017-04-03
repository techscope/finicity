# finicity

[![Latest Version on Packagist][ico-version]][link-packagist]
[![Software License][ico-license]](../../../../../../Downloads/finicity-master/LICENSE.md)
[![Build Status][ico-travis]][link-travis]
[![Coverage Status][ico-scrutinizer]][link-scrutinizer]
[![Quality Score][ico-code-quality]][link-code-quality]
[![Total Downloads][ico-downloads]][link-downloads]

`techscope/finicity` is a simple Laravel wrapper for accessing the Finicity API.

DISCLAIMER: TechScope LLC is in no way affiliated with Fincity Corporation. This package
is NOT supported by officially or unofficially by Finicity. It is intended only as a Laravel wrapper to perform
calls through their API.

THIS PACKAGE IS CURRENTLY IN ITS INFANT STAGES AND SHOULD NOT YET BE USED.

## Structure

If any of the following are applicable to your project, then the directory structure should follow industry best practises by being named the following.

```
bin/        
config/
src/
tests/
vendor/
```


## Install

Via Composer

``` bash
$ composer require techscope/finicity
```

## Usage

``` php
$skeleton = new Techscope\Finicity();
echo $skeleton->echoPhrase('Hello, League!');
```

## Change log

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Testing

``` bash
$ composer test
```

## Contributing

Please see [CONTRIBUTING](../../../../../../Downloads/finicity-master/CONTRIBUTING.md) and [CONDUCT](../../../../../../Downloads/finicity-master/CONDUCT.md) for details.

## Security

If you discover any security related issues, please email christian.soseman@techscopellc.com instead of using the issue tracker.

## Credits

- [TechScope LLC][link-author]
- [All Contributors][link-contributors]

## License

The MIT License (MIT). Please see [License File](../../../../../../Downloads/finicity-master/LICENSE.md) for more information.

[ico-version]: https://img.shields.io/packagist/v/techscope/finicity.svg?style=flat-square
[ico-license]: https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square
[ico-travis]: https://img.shields.io/travis/techscope/finicity/master.svg?style=flat-square
[ico-scrutinizer]: https://img.shields.io/scrutinizer/coverage/g/techscope/finicity.svg?style=flat-square
[ico-code-quality]: https://img.shields.io/scrutinizer/g/techscope/finicity.svg?style=flat-square
[ico-downloads]: https://img.shields.io/packagist/dt/techscope/finicity.svg?style=flat-square

[link-packagist]: https://packagist.org/packages/techscope/finicity
[link-travis]: https://travis-ci.org/techscope/finicity
[link-scrutinizer]: https://scrutinizer-ci.com/g/techscope/finicity/code-structure
[link-code-quality]: https://scrutinizer-ci.com/g/techscope/finicity
[link-downloads]: https://packagist.org/packages/techscope/finicity
[link-author]: https://github.com/techscope
[link-contributors]: ../../contributors
