<h1 align=center>NuSOAP</h1>

<p align=center>
NuSOAP is a rewrite of SOAPx4, provided by NuSphere and Dietrich Ayala. It is a set of PHP classes - no PHP extensions required - that allow developers to create and consume web services based on SOAP 1.1, WSDL 1.1 and HTTP 1.0/1.1.
</p>

<p align=center>
🕹 <a href="https://f3l1x.io">f3l1x.io</a> | 💻 <a href="https://github.com/f3l1x">f3l1x</a> | 🐦 <a href="https://twitter.com/xf3l1x">@xf3l1x</a>
</p>

<p align=center>
  All credits belongs to official authors, take a look at <a href="https://sourceforge.net/projects/nusoap/">sourceforge.net/projects/nusoap/</a>
</p>

<p align=center>
  <a href="https://github.com/pwnlabs/nusoap/actions"><img src="https://badgen.net/github/checks/pwnlabs/nusoap/master?cache=300"></a>
  <a href="https://coveralls.io/r/pwnlabs/nusoap"><img src="https://badgen.net/coveralls/c/github/pwnlabs/nusoap?cache=300"></a>
  <a href="https://packagist.org/packages/econea/nusoap"><img src="https://badgen.net/packagist/dm/econea/nusoap"></a>
  <a href="https://packagist.org/packages/econea/nusoap"><img src="https://badgen.net/packagist/dt/econea/nusoap"></a>
  <a href="https://packagist.org/packages/econea/nusoap"><img src="https://badgen.net/packagist/v/econea/nusoap"></a>
</p>

-----

## Info

- Supported PHP: [5.4 - 8.2](https://packagist.org/packages/econea/nusoap)
- Official project: https://sourceforge.net/projects/nusoap/

## Installation

To install this library use [Composer](https://getcomposer.org/).

```
composer require econea/nusoap
```

## Usage

```php
// Config
$client = new nusoap_client('example.com/api/v1', 'wsdl');
$client->soap_defencoding = 'UTF-8';
$client->decode_utf8 = FALSE;

// Calls
$result = $client->call($action, $data);
```

## Development

See [how to contribute](https://contributte.org/contributing.html) to this package.

This package is currently maintained by these authors.

<a href="https://github.com/f3l1x">
    <img width="80" height="80" src="https://avatars2.githubusercontent.com/u/538058?v=3&s=80">
</a>

-----

Consider to [support](https://github.com/sponsors/f3l1x) **f3l1x**. Also thank you for using this package.
