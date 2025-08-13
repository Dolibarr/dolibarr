# gfx-php - The pure PHP graphics library

[![Build Status](https://travis-ci.org/mike42/gfx-php.svg?branch=master)](https://travis-ci.org/mike42/gfx-php) [![Latest Stable Version](https://poser.pugx.org/mike42/gfx-php/v/stable)](https://packagist.org/packages/mike42/gfx-php)
[![Total Downloads](https://poser.pugx.org/mike42/gfx-php/downloads)](https://packagist.org/packages/mike42/gfx-php)
[![License](https://poser.pugx.org/mike42/gfx-php/license)](https://packagist.org/packages/mike42/gfx-php) [![Coverage Status](https://coveralls.io/repos/github/mike42/gfx-php/badge.svg?branch=master)](https://coveralls.io/github/mike42/gfx-php?branch=master)

This library implements input, output and processing of raster images in pure PHP, so that image
processing extensions (Gd, Imagick) are not required.

This allows developers to eliminate some portability issues from their applications.

### Features

- Format support includes PNG, GIF, BMP and the Netpbm formats (See docs: [File formats](https://gfx-php.readthedocs.io/en/latest/user/formats.html)).
- Support for scaling, cropping, format conversion and colorspace transformations (See docs: [Image operations](https://gfx-php.readthedocs.io/en/latest/user/operations.html)).
- Pure PHP: This library does not require Gd, ImageMagick or GraphicsMagick extensions.

## Quick start

### Requirements

- PHP 7.0 or newer.
- `zlib` extension, for reading PNG files.

### Installation

Install `gfx-php` with composer:

```bash
composer install mike42/gfx-php
```

### Basic usage

The basic usage is like this:

```php
<?php
use Mike42\GfxPhp\Image;
$img = Image::fromFile("colorwheel256.png");
$img -> write("test.gif");
```

### Further reading

- Read of the documentation at [gfx-php.readthedocs.io](https://gfx-php.readthedocs.io/)
- See the `examples/` sub-folder for snippets.

## Contributing

This project is open to all kinds of contributions, including suggestions, documentation fixes, examples, formats and image processing algorithms.

Some ideas for improvement listed in [the issue tracker](https://travis-ci.org/mike42/gfx-php). Code contributions must be releasable under the LGPLv3 or later.

### Scope

As a small project, we can't do everything. In particular, `gfx-php` is not likely to ever perform non-raster operations:

- vector image formats (PDF, SVG, EPS, etc).
- anything involving vector fonts

### Acknowledgements

This repository uses test files from other projects:

- [BMP Suite](http://entropymine.com/jason/bmpsuite/) by Jason Summers.
- [PyGIF](https://github.com/robert-ancell/pygif) test suite by Robert Ancell.
- [pngsuite](http://www.schaik.com/pngsuite/) by Willem van Schaik.

## Similar projects

- [Imagine](https://github.com/avalanche123/Imagine), which wraps available libraries.
