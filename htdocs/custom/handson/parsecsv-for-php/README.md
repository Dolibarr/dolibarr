# ParseCsv
[![Financial Contributors on Open Collective](https://opencollective.com/parsecsv/all/badge.svg?label=financial+contributors)](https://opencollective.com/parsecsv)

ParseCsv is an easy-to-use PHP class that reads and writes CSV data properly. It
fully conforms to the specifications outlined on the on the
[Wikipedia article][CSV] (and thus RFC 4180). It has many advanced features which help make your
life easier when dealing with CSV data.

You may not need a library at all: before using ParseCsv, please make sure if PHP's own `str_getcsv()`, ``fgetcsv()`` or `fputcsv()` meets your needs.

This library was originally created in early 2007 by [jimeh](https://github.com/jimeh) due to the lack of built-in
and third-party support for handling CSV data in PHP.

[csv]: http://en.wikipedia.org/wiki/Comma-separated_values

## Features

* ParseCsv is a complete and fully featured CSV solution for PHP
* Supports enclosed values, enclosed commas, double quotes and new lines.
* Automatic delimiter character detection.
* Sort data by specific fields/columns.
* Easy data manipulation.
* Basic SQL-like _conditions_, _offset_ and _limit_ options for filtering
  data.
* Error detection for incorrectly formatted input. It attempts to be
  intelligent, but can not be trusted 100% due to the structure of CSV, and
  how different programs like Excel for example outputs CSV data.
* Support for character encoding conversion using PHP's
  `iconv()` and `mb_convert_encoding()` functions.
* Supports PHP 5.5 and higher.
  It certainly works with PHP 7.2 and all versions in between.

## Installation

Installation is easy using Composer. Just run the following on the
command line:
```
composer require parsecsv/php-parsecsv
```

If you don't use a framework such as Drupal, Laravel, Symfony, Yii etc.,
you may have to manually include Composer's autoloader file in your PHP
script:
```php
require_once __DIR__ . '/vendor/autoload.php';
```

#### Without composer
Not recommended, but technically possible: you can also clone the
repository or extract the
[ZIP](https://github.com/parsecsv/parsecsv-for-php/archive/master.zip).
To use ParseCSV, you then have to add a `require 'parsecsv.lib.php';` line.

## Example Usage

**Parse a tab-delimited CSV file with encoding conversion**

```php
$csv = new \ParseCsv\Csv();
$csv->encoding('UTF-16', 'UTF-8');
$csv->delimiter = "\t";
$csv->parseFile('data.tsv');
print_r($csv->data);
```

**Auto-detect field delimiter character**

```php
$csv = new \ParseCsv\Csv();
$csv->auto('data.csv');
print_r($csv->data);
```

**Parse data with offset**
* ignoring the first X (e.g. two) rows
```php
$csv = new \ParseCsv\Csv();
$csv->offset = 2;
$csv->parseFile('data.csv');
print_r($csv->data);
```

**Limit the number of returned data rows**
```php
$csv = new \ParseCsv\Csv();
$csv->limit = 5;
$csv->parseFile('data.csv');
print_r($csv->data);
```

**Get total number of data rows without parsing whole data**
* Excluding heading line if present (see $csv->header property)
```php
$csv = new \ParseCsv\Csv();
$csv->loadFile('data.csv');
$count = $csv->getTotalDataRowCount();
print_r($count);
```

**Get most common data type for each column**

```php
$csv = new \ParseCsv\Csv('data.csv');
$csv->getDatatypes();
print_r($csv->data_types);
```

**Modify data in a CSV file**

Change data values:
```php
$csv = new \ParseCsv\Csv();
$csv->sort_by = 'id';
$csv->parseFile('data.csv');
# "4" is the value of the "id" column of the CSV row
$csv->data[4] = array('firstname' => 'John', 'lastname' => 'Doe', 'email' => 'john@doe.com');
$csv->save();
```

Enclose each data value by quotes:
```php
$csv = new \ParseCsv\Csv();
$csv->parseFile('data.csv');
$csv->enclose_all = true;
$csv->save();
```

**Replace field names or set ones if missing**

```php
$csv = new \ParseCsv\Csv();
$csv->fields = ['id', 'name', 'category'];
$csv->parseFile('data.csv');
```

**Add row/entry to end of CSV file**

_Only recommended when you know the exact structure of the file._

```php
$csv = new \ParseCsv\Csv();
$csv->save('data.csv', array(array('1986', 'Home', 'Nowhere', '')), /* append */ true);
```

**Convert 2D array to CSV data and send headers to browser to treat output as
a file and download it**

Your web app users would call this an export.

```php
$csv = new \ParseCsv\Csv();
$csv->linefeed = "\n";
$header = array('field 1', 'field 2');
$csv->output('movies.csv', $data_array, $header, ',');
```

For more complex examples, see the ``tests`` and `examples` directories.

## Test coverage

All tests are located in the `tests` directory. To execute tests, run the following commands:

````bash
composer install
composer run test
````

When pushing code to GitHub, tests will be executed using GitHub Actions. The relevant configuration is in the
file `.github/workflows/ci.yml`. To run the `test` action locally, you can execute the following command:

````bash
make local-ci
````

## Security

If you discover any security related issues, please email ParseCsv@blaeul.de instead of using GitHub issues.

## Credits

* ParseCsv is based on the concept of [Ming Hong Ng][ming]'s [CsvFileParser][]
  class.

[ming]: http://minghong.blogspot.com/
[CsvFileParser]: http://minghong.blogspot.com/2006/07/csv-parser-for-php.html


## Contributors

### Code Contributors

This project exists thanks to all the people who contribute.

Please find a complete list on the project's [contributors][] page.

[contributors]: https://github.com/parsecsv/parsecsv-for-php/graphs/contributors
<a href="https://github.com/parsecsv/parsecsv-for-php/graphs/contributors"><img src="https://opencollective.com/parsecsv/contributors.svg?width=890&button=false" /></a>

### Financial Contributors

Become a financial contributor and help us sustain our community. [[Contribute](https://opencollective.com/parsecsv/contribute)]

#### Individuals

<a href="https://opencollective.com/parsecsv"><img src="https://opencollective.com/parsecsv/individuals.svg?width=890"></a>

#### Organizations

Support this project with your organization. Your logo will show up here with a link to your website. [[Contribute](https://opencollective.com/parsecsv/contribute)]

<a href="https://opencollective.com/parsecsv/organization/0/website"><img src="https://opencollective.com/parsecsv/organization/0/avatar.svg"></a>
<a href="https://opencollective.com/parsecsv/organization/1/website"><img src="https://opencollective.com/parsecsv/organization/1/avatar.svg"></a>
<a href="https://opencollective.com/parsecsv/organization/2/website"><img src="https://opencollective.com/parsecsv/organization/2/avatar.svg"></a>
<a href="https://opencollective.com/parsecsv/organization/3/website"><img src="https://opencollective.com/parsecsv/organization/3/avatar.svg"></a>
<a href="https://opencollective.com/parsecsv/organization/4/website"><img src="https://opencollective.com/parsecsv/organization/4/avatar.svg"></a>
<a href="https://opencollective.com/parsecsv/organization/5/website"><img src="https://opencollective.com/parsecsv/organization/5/avatar.svg"></a>
<a href="https://opencollective.com/parsecsv/organization/6/website"><img src="https://opencollective.com/parsecsv/organization/6/avatar.svg"></a>
<a href="https://opencollective.com/parsecsv/organization/7/website"><img src="https://opencollective.com/parsecsv/organization/7/avatar.svg"></a>
<a href="https://opencollective.com/parsecsv/organization/8/website"><img src="https://opencollective.com/parsecsv/organization/8/avatar.svg"></a>
<a href="https://opencollective.com/parsecsv/organization/9/website"><img src="https://opencollective.com/parsecsv/organization/9/avatar.svg"></a>

## License

(The MIT license)

Copyright (c) 2014 Jim Myhrberg.

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.

[![Build Status](https://travis-ci.org/parsecsv/parsecsv-for-php.svg?branch=master)](https://travis-ci.org/parsecsv/parsecsv-for-php)
