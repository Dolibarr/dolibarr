# ESC/POS Print Driver for PHP
[![Build Status](https://travis-ci.org/mike42/escpos-php.svg?branch=master)](https://travis-ci.org/mike42/escpos-php) [![Latest Stable Version](https://poser.pugx.org/mike42/escpos-php/v/stable)](https://packagist.org/packages/mike42/escpos-php)
[![Total Downloads](https://poser.pugx.org/mike42/escpos-php/downloads)](https://packagist.org/packages/mike42/escpos-php)
[![License](https://poser.pugx.org/mike42/escpos-php/license)](https://packagist.org/packages/mike42/escpos-php)
[![Coverage Status](https://coveralls.io/repos/github/mike42/escpos-php/badge.svg?branch=development)](https://coveralls.io/github/mike42/escpos-php?branch=development)

This project implements a subset of Epson's ESC/POS protocol for thermal receipt printers. It allows you to generate and print receipts with basic formatting, cutting, and barcodes on a compatible printer.

The library was developed to add drop-in support for receipt printing to any PHP app, including web-based point-of-sale (POS) applications.

## Compatibility

### Interfaces and operating systems
This driver is known to work with the following OS/interface combinations:

<table>
<tr>
<th>&nbsp;</th>
<th>Linux</th>
<th>Mac</th>
<th>Windows</th>
</tr>
<tr>
<th>Ethernet</th>
<td><a href="https://github.com/mike42/escpos-php/tree/master/example/interface/ethernet.php">Yes</a></td>
<td><a href="https://github.com/mike42/escpos-php/tree/master/example/interface/ethernet.php">Yes</a></td>
<td><a href="https://github.com/mike42/escpos-php/tree/master/example/interface/ethernet.php">Yes</a></td>
</tr>
<tr>
<th>USB</th>
<td><a href="https://github.com/mike42/escpos-php/tree/master/example/interface/linux-usb.php">Yes</a></td>
<td>Not tested</td>
<td><a href="https://github.com/mike42/escpos-php/tree/master/example/interface/windows-usb.php">Yes</a></td>
</tr>
<tr>
<th>USB-serial</th>
<td>Yes</td>
<td>Yes</td>
<td>Yes</td>
</tr>
<tr>
<th>Serial</th>
<td>Yes</td>
<td>Yes</td>
<td>Yes</td>
</tr>
<tr>
<th>Parallel</th>
<td><a href="https://github.com/mike42/escpos-php/tree/master/example/interface/windows-lpt.php">Yes</a></td>
<td>Not tested</td>
<td>Yes</td>
</tr>
<tr>
<th>SMB shared</th>
<td><a href="https://github.com/mike42/escpos-php/tree/master/example/interface/smb.php">Yes</a></td>
<td>No</td>
<td><a href="https://github.com/mike42/escpos-php/tree/master/example/interface/smb.php">Yes</a></td>
</tr>
<tr>
<th>CUPS hosted</th>
<td><a href="https://github.com/mike42/escpos-php/tree/master/example/interface/cups.php">Yes</a></td>
<td><a href="https://github.com/mike42/escpos-php/tree/master/example/interface/cups.php">Yes</a></td>
<td>No</td>
</tr>
</table>

### Printers
Many thermal receipt printers support ESC/POS to some degree. This driver has been known to work with:

- 3nStar RPT-008
- Approx APPPOS80AM
- AURES ODP-333
- AURES ODP-500
- Bematech-4200-TH
- Bematech LR2000E
- Birch PRP-085III
- Bixolon SRP-350III
- Bixolon SRP-350Plus
- Black Copper BC-85AC
- CHD TH-305N
- Citizen CBM1000-II
- Citizen CT-S310II
- Dapper-Geyi Q583P 
- Daruma DR800
- DR-MP200 (manufacturer unknown)
- EPOS TEP 220M
- Elgin i9
- Epson EU-T332C
- Epson FX-890 (requires `feedForm()` to release paper).
- Epson TM-T20
- Epson TM-T20II
- Epson TM-T70
- Epson TM-T70II
- Epson TM-T81
- Epson TM-T82II
- Epson TM-T88II
- Epson TM-T88III
- Epson TM-T88IV
- Epson TM-T88V
- Epson TM-U220
- Epson TM-U295 (requires `release()` to release slip).
- Epson TM-U590 and TM-U590P
- Equal (EQ-IT-001) POS-58
- Everycom EC-58
- Excelvan HOP-E200 
- Excelvan HOP-E58
- Excelvan HOP-E801
- Gainscha GP-2120TF
- Gainscha GP-5890x (Also marketed as EC Line 5890x)
- Gainscha GP-U80300I (Also marketed as gprinter GP-U80300I)
- gprinter GP-U80160I
- HOIN HOP-H58
- Ithaca iTherm 28
- Hasar HTP 250
- Metapace T-1
- Metapace T-25
- Nexa PX700
- Nyear NP100
- OKI RT322
- OKI 80 Plus III
- Orient BTP-R580
- P-822D
- P85A-401 (make unknown)
- Partner Tech RP320
- POSLIGNE ODP200H-III-G
- QPOS Q58M
- Rongta RP326US
- Rongta RP58-U
- Rongta RP80USE
- SAM4S GIANT-100DB
- Senor TP-100
- Sewoo SLK-TS400
- SEYPOS PRP-96
- SEYPOS PRP-300 (Also marketed as TYSSO PRP-300)
- SNBC BTP-R880NPIII
- Solux SX-TP-88300
- Sicar POS-80
- Silicon SP-201 / RP80USE
- SPRT SP-POS88V
- Star BSC10
- Star TSP100 ECO
- Star TSP100III FuturePRNT
- Star TSP-650
- Star TUP-592
- TVS RP45 Shoppe
- Venus V248T
- Xeumior SM-8330
- Xprinter F-900
- Xprinter XP-365B
- Xprinter XP-58 Series
- Xprinter XP-80C
- Xprinter XP-90
- XPrinter XP-Q20011
- Xprinter XP-Q800
- Zjiang NT-58H
- Zjiang ZJ-5870
- Zjiang ZJ-5890 (Also sold as POS-5890 by many vendors; ZJ-5890K, ZJ-5890T also work).
- Zjiang ZJ-8220 (Also marketed as Excelvan ZJ-8220)
- Zjiang ZJ-8250

If you use any other printer with this code, please [let us know](https://github.com/mike42/escpos-php/issues/new) so that it can be added to the list.

## Basic usage

### Include the library

#### Composer
If you are using composer, then add `mike42/escpos-php` as a dependency:

```bash
composer require mike42/escpos-php
```

In this case, you would include composer's auto-loader at the top of your source files:

```php
<?php
require __DIR__ . '/vendor/autoload.php';
```

#### Manually
If you don't have composer available, then simply download the code and include `autoload.php`:

```bash
git clone https://github.com/mike42/escpos-php vendor/mike42/escpos-php
```

```php
<?php
require __DIR__ . '/vendor/mike42/escpos-php/autoload.php';
```

#### Requirements

To maintain compatibility with as many systems as possible, this driver has few
hard dependencies:

- PHP 5.4 or above.
- `mbstring` extension, since the driver accepts UTF-8 encoding.

It is also suggested that you install either `imagick` or `gd`, so that you can
print images.

A number of optional packages can be added to enable more specific features. These
are described in the "suggest" section of [composer.json](https://github.com/mike42/escpos-php/tree/master/composer.json).

### The 'Hello World' receipt

To make use of this driver, your server (where PHP is installed) must be able to communicate with your printer. Start by generating a simple receipt and sending it to your printer using the command-line.

```php
<?php
/* Call this file 'hello-world.php' */
require __DIR__ . '/vendor/autoload.php';
use Mike42\Escpos\PrintConnectors\FilePrintConnector;
use Mike42\Escpos\Printer;
$connector = new FilePrintConnector("php://stdout");
$printer = new Printer($connector);
$printer -> text("Hello World!\n");
$printer -> cut();
$printer -> close();
```

Some examples are below for common interfaces.

Communicate with a printer with an Ethernet interface using `netcat`:

```bash
php hello-world.php | nc 10.x.x.x. 9100
```

A USB local printer connected with `usblp` on Linux has a device file (Includes USB-parallel interfaces):

```bash
php hello-world.php > /dev/usb/lp0
```

A computer installed into the local `cups` server is accessed through `lp` or `lpr`:

```bash
php hello-world.php > foo.txt
lpr -o raw -H localhost -P printer foo.txt
```

A local or networked printer on a Windows computer is mapped in to a file, and generally requires you to share the printer first:

```
php hello-world.php > foo.txt
net use LPT1 \\server\printer
copy foo.txt LPT1
del foo.txt
```

If you have troubles at this point, then you should consult your OS and printer system documentation to try to find a working print command.

### Using a PrintConnector

To print receipts from PHP, use the most applicable [PrintConnector](https://github.com/mike42/escpos-php/tree/master/src/Mike42/Escpos/PrintConnectors) for your setup. The connector simply provides the plumbing to get data to the printer.

For example, a `NetworkPrintConnector` accepts an IP address and port:

```php
use Mike42\Escpos\PrintConnectors\NetworkPrintConnector;
use Mike42\Escpos\Printer;
$connector = new NetworkPrintConnector("10.x.x.x", 9100);
$printer = new Printer($connector);
try {
    // ... Print stuff
} finally {
    $printer -> close();
}
```

While a serial printer might use:
```php
use Mike42\Escpos\PrintConnectors\FilePrintConnector;
use Mike42\Escpos\Printer;
$connector = new FilePrintConnector("/dev/ttyS0");
$printer = new Printer($connector);
```

For each OS/interface combination that's supported, there are examples in the compatibility section of how a `PrintConnector` would be constructed. If you can't get a `PrintConnector` to work, then be sure to include the working print command in bug.

### Using a CapabilityProfile

Support for commands and code pages varies between printer vendors and models. By default, the driver will accept UTF-8, and output commands that are suitable for Epson TM-series printers.

When trying out a new brand of printer, it's a good idea to use the "simple" `CapabilityProfile`, which instructs the driver to avoid the use of advanced features (generally simpler image handling, ASCII-only text).

```php
use Mike42\Escpos\PrintConnectors\WindowsPrintConnector;
use Mike42\Escpos\CapabilityProfile;
$profile = CapabilityProfile::load("simple");
$connector = new WindowsPrintConnector("smb://computer/printer");
$printer = new Printer($connector, $profile);
```

As another example, Star-branded printers use different commands:

```php
use Mike42\Escpos\PrintConnectors\WindowsPrintConnector;
use Mike42\Escpos\CapabilityProfile;
$profile = CapabilityProfile::load("SP2000")
$connector = new WindowsPrintConnector("smb://computer/printer");
$printer = new Printer($connector, $profile);
```

For a list of available profiles, or to have support for your printer improved, please see the upstream [receipt-print-hq/escpos-printer-db](https://github.com/receipt-print-hq/escpos-printer-db) project.

### Tips & examples
On Linux, your printer device file will be somewhere like `/dev/lp0` (parallel), `/dev/usb/lp1` (USB), `/dev/ttyUSB0` (USB-Serial), `/dev/ttyS0` (serial).

On Windows, the device files will be along the lines of `LPT1` (parallel) or `COM1` (serial). Use the `WindowsPrintConnector` to tap into system printing on Windows (eg. [Windows USB](https://github.com/mike42/escpos-php/tree/master/example/interface/windows-usb.php), [SMB](https://github.com/mike42/escpos-php/tree/master/example/interface/smb.php) or [Windows LPT](https://github.com/mike42/escpos-php/tree/master/example/interface/windows-lpt.php)) - this submits print jobs via a queue rather than communicating directly with the printer.

A complete real-world receipt can be found in the code of [Auth](https://github.com/mike42/Auth) in [ReceiptPrinter.php](https://github.com/mike42/Auth/blob/master/lib/misc/ReceiptPrinter.php). It includes justification, boldness, and a barcode.

Other examples are located in the [example/](https://github.com/mike42/escpos-php/blob/master/example/) directory.

## Available methods

### __construct(PrintConnector $connector, CapabilityProfile $profile)
Construct new print object.

Parameters:
- `PrintConnector $connector`: The PrintConnector to send data to.
- `CapabilityProfile $profile` Supported features of this printer. If not set, the "default" CapabilityProfile will be used, which is suitable for Epson printers.

See [example/interface/](https://github.com/mike42/escpos-php/tree/master/example/interface/) for ways to open connections for different platforms and interfaces.

### barcode($content, $type)
Print a barcode.

Parameters:

- `string $content`: The information to encode.
- `int $type`: The barcode standard to output. If not specified, `Printer::BARCODE_CODE39` will be used.

Currently supported barcode standards are (depending on your printer):

- `BARCODE_UPCA`
- `BARCODE_UPCE`
- `BARCODE_JAN13`
- `BARCODE_JAN8`
- `BARCODE_CODE39`
- `BARCODE_ITF`
- `BARCODE_CODABAR`

Note that some barcode standards can only encode numbers, so attempting to print non-numeric codes with them may result in strange behaviour.

### bitImage(EscposImage $image, $size)
See [graphics()](#graphicsescposimage-image-size) below.

### cut($mode, $lines)
Cut the paper.

Parameters:

- `int $mode`: Cut mode, either `Printer::CUT_FULL` or `Printer::CUT_PARTIAL`. If not specified, `Printer::CUT_FULL` will be used.
- `int $lines`: Number of lines to feed before cutting. If not specified, 3 will be used.

### feed($lines)
Print and feed line / Print and feed n lines.

Parameters:

- `int $lines`: Number of lines to feed

### feedForm()
Some printers require a form feed to release the paper. On most printers, this command is only useful in page mode, which is not implemented in this driver.

### feedReverse($lines)
Print and reverse feed n lines.

Parameters:

- `int $lines`: number of lines to feed. If not specified, 1 line will be fed.

### graphics(EscposImage $image, $size)
Print an image to the printer.

Parameters:

- `EscposImage $img`: The image to print.
- `int $size`: Output size modifier for the image.

Size modifiers are:

- `IMG_DEFAULT` (leave image at original size)
- `IMG_DOUBLE_WIDTH`
- `IMG_DOUBLE_HEIGHT`

A minimal example:

```php
<?php
$img = EscposImage::load("logo.png");
$printer -> graphics($img);
```

See the [example/](https://github.com/mike42/escpos-php/blob/master/example/) folder for detailed examples.

The function [bitImage()](#bitimageescposimage-image-size) takes the same parameters, and can be used if your printer doesn't support the newer graphics commands. As an additional fallback, the `bitImageColumnFormat()` function is also provided.

### initialize()
Initialize printer. This resets formatting back to the defaults.

### pdf417Code($content, $width, $heightMultiplier, $dataColumnCount, $ec, $options)
Print a two-dimensional data code using the PDF417 standard.

Parameters:

- `string $content`: Text or numbers to store in the code
- `number $width`: Width of a module (pixel) in the printed code. Default is 3 dots.
- `number $heightMultiplier`: Multiplier for height of a module. Default is 3 times the width.
- `number $dataColumnCount`: Number of data columns to use. 0 (default) is to auto-calculate. Smaller numbers will result in a narrower code, making larger pixel sizes possible. Larger numbers require smaller pixel sizes.
- `real $ec`: Error correction ratio, from 0.01 to 4.00. Default is 0.10 (10%).
- `number $options`: Standard code `Printer::PDF417_STANDARD` with start/end bars, or truncated code `Printer::PDF417_TRUNCATED` with start bars only.

### pulse($pin, $on_ms, $off_ms)
Generate a pulse, for opening a cash drawer if one is connected. The default settings (0, 120, 240) should open an Epson drawer.

Parameters:

- `int $pin`: 0 or 1, for pin 2 or pin 5 kick-out connector respectively.
- `int $on_ms`: pulse ON time, in milliseconds.
- `int $off_ms`: pulse OFF time, in milliseconds.

### qrCode($content, $ec, $size, $model)
Print the given data as a QR code on the printer.

- `string $content`: The content of the code. Numeric data will be more efficiently compacted.
- `int $ec` Error-correction level to use. One of `Printer::QR_ECLEVEL_L` (default), `Printer::QR_ECLEVEL_M`, `Printer::QR_ECLEVEL_Q` or `Printer::QR_ECLEVEL_H`. Higher error correction results in a less compact code.
- `int $size`: Pixel size to use. Must be 1-16 (default 3)
- `int $model`: QR code model to use. Must be one of `Printer::QR_MODEL_1`, `Printer::QR_MODEL_2` (default) or `Printer::QR_MICRO` (not supported by all printers).

### selectPrintMode($mode)
Select print mode(s).

Parameters:

- `int $mode`: The mode to use. Default is `Printer::MODE_FONT_A`, with no special formatting. This has a similar effect to running `initialize()`.

Several MODE_* constants can be OR'd together passed to this function's `$mode` argument. The valid modes are:

- `MODE_FONT_A`
- `MODE_FONT_B`
- `MODE_EMPHASIZED`
- `MODE_DOUBLE_HEIGHT`
- `MODE_DOUBLE_WIDTH`
- `MODE_UNDERLINE`

### setBarcodeHeight($height)
Set barcode height.

Parameters:

- `int $height`: Height in dots. If not specified, 8 will be used.

### setBarcodeWidth($width)
Set barcode bar width.

Parameters:

- `int $width`: Bar width in dots. If not specified, 3 will be used. Values above 6 appear to have no effect.

### setColor($color)
Select print color - on printers that support multiple colors.

Parameters:

- `int $color`: Color to use. Must be either `Printer::COLOR_1` (default), or `Printer::COLOR_2`

### setDoubleStrike($on)
Turn double-strike mode on/off.

Parameters:

- `boolean $on`: true for double strike, false for no double strike.

### setEmphasis($on)
Turn emphasized mode on/off.

Parameters:

- `boolean $on`: true for emphasis, false for no emphasis.

### setFont($font)
Select font. Most printers have two fonts (Fonts A and B), and some have a third (Font C).

Parameters:

- `int $font`: The font to use. Must be either `Printer::FONT_A`, `Printer::FONT_B`, or `Printer::FONT_C`.

### setJustification($justification)
Select justification.

Parameters:

- `int $justification`: One of `Printer::JUSTIFY_LEFT`, `Printer::JUSTIFY_CENTER`, or `Printer::JUSTIFY_RIGHT`.

### setLineSpacing($height)

Set the height of the line.

Some printers will allow you to overlap lines with a smaller line feed.

Parameters:

- `int	$height`:	The height of each line, in dots. If not set, the printer will reset to its default line spacing.

### setPrintLeftMargin($margin)

Set print area left margin. Reset to default with `Printer::initialize()`.

Parameters:

- `int $margin`: The left margin to set on to the print area, in dots.

### setPrintWidth($width)

Set print area width. This can be used to add a right margin to the print area. Reset to default with `Printer::initialize()`.

Parameters:

- `int $width`: The width of the page print area, in dots.

### setReverseColors($on)
Set black/white reverse mode on or off. In this mode, text is printed white on a black background.

Parameters:

- `boolean $on`: True to enable, false to disable.

### setTextSize($widthMultiplier, $heightMultiplier)
Set the size of text, as a multiple of the normal size.

Parameters:

- `int $widthMultiplier`: Multiple of the regular height to use (range 1 - 8).
- `int $heightMultiplier`: Multiple of the regular height to use (range 1 - 8).

### setUnderline($underline)
Set underline for printed text.

Parameters:

- `int $underline`: Either `true`/`false`, or one of `Printer::UNDERLINE_NONE`, `Printer::UNDERLINE_SINGLE` or `Printer::UNDERLINE_DOUBLE`. Defaults to `Printer::UNDERLINE_SINGLE`.

### text($str)
Add text to the buffer. Text should either be followed by a line-break, or `feed()` should be called after this.

Parameters:

- `string $str`: The string to print.

# Further notes
Posts I've written up for people who are learning how to use receipt printers:

* [What is ESC/POS, and how do I use it?](https://mike42.me/blog/what-is-escpos-and-how-do-i-use-it), which documents the output of `example/demo.php`.
* [Setting up an Epson receipt printer](https://mike42.me/blog/2014-20-26-setting-up-an-epson-receipt-printer)
* [Getting a USB receipt printer working on Linux](https://mike42.me/blog/2015-03-getting-a-usb-receipt-printer-working-on-linux)

# Development

This code is MIT licensed, and you are encouraged to contribute any modifications back to the project.

For development, it's suggested that you load `imagick`, `gd` and `Xdebug` PHP exensions, and install `composer`.

The tests are executed on [Travis CI](https://travis-ci.org/mike42/escpos-php) over PHP 5.4, 5.5, 5.6, 7.0, 7.1 and 7.2, plus the latest LTS version of HHVM, 3.21. Older versions of PHP are not supported in current releases.

Fetch a copy of this code and load dependencies with composer:

    git clone https://github.com/mike42/escpos-php
    cd escpos-php/
    composer install

Execute unit tests via `phpunit`:

    php vendor/bin/phpunit --coverage-text

This project uses the PSR-2 standard, which can be checked via [PHP_CodeSniffer](https://github.com/squizlabs/PHP_CodeSniffer):

    php vendor/bin/phpcs --standard=psr2 src/ -n

The developer docs are build with [doxygen](https://github.com/doxygen/doxygen). Re-build them to check for documentation warnings:

    make -C doc clean && make -C doc

Pull requests and bug reports welcome.
