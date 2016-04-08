# ESC/POS Print Driver for PHP

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

- Bixolon SRP-350III
- Citizen CBM1000-II
- EPOS TEP 220M
- Epson TM-T88III
- Epson TM-T88IV
- Epson TM-T70
- Epson TM-T82II
- Epson TM-T20
- Epson TM-T70II
- Epson TM-U220
- Epson FX-890 (requires `feedForm()` to release paper).
- Excelvan HOP-E58 (connect through powered hub)
- Okipos 80 Plus III
- P-822D
- P85A-401 (make unknown)
- SEYPOS PRP-300 (Also marketed as TYSSO PRP-300)
- Silicon SP-201 / RP80USE
- Star TSP-650
- Star TUP-592
- Xprinter XP-Q800
- Zijang NT-58H
- Zijang ZJ-5870
- Zijang ZJ-5890T (Marketed as POS 5890T)

If you use any other printer with this code, please [let us know](https://github.com/mike42/escpos-php/issues/new) so that it can be added to the list.

## Basic usage

### Include the library

#### Composer
If you are using composer, then add `mike42/escpos-php` as a dependency:

````
composer require mike42/escpos-php
````

In this case, you would include composer's auto-loader at the top of your source files:

````
<?php
require __DIR__ . '/vendor/autoload.php';
````

#### Manually
If you don't have composer available, then simply download the code and include `autoload.php`:

````
git clone https://github.com/mike42/escpos-php vendor/mike42/escpos-php
````

````php
<?php
require __DIR__ . '/vendor/mike42/escpos-php/autoload.php');
````

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
````
php hello-world.php | nc 10.x.x.x. 9100
````

A USB local printer connected with `usblp` on Linux has a device file (Includes USB-parallel interfaces):
````
php hello-world.php > /dev/usb/lp0
````

A computer installed into the local `cups` server is accessed through `lp` or `lpr`:
````
php hello-world.php > foo.txt
lpr -o raw -H localhost -P printer foo.txt
````

A local or networked printer on a Windows computer is mapped in to a file, and generally requires you to share the printer first:

````
php hello-world.php > foo.txt
net use LPT1 \\server\printer
copy foo.txt LPT1
del foo.txt
```

If you have troubles at this point, then you should consult your OS and printer system documentation to try to find a working print command.

### Using a PrintConnector

To print receipts from PHP, use the most applicable [PrintConnector](https://github.com/mike42/escpos-php/tree/master/src/Mike42/Escpos/PrintConnectors) for your setup. The connector simply provides the plumbing to get data to the printer.

For example, a `NetworkPrintConnector` accepts an IP address and port:

````php
use Mike42\Escpos\PrintConnectors\NetworkPrintConnector;
use Mike42\Escpos\Printer;
$connector = new NetworkPrintConnector("10.x.x.x", 9100);
$printer = new Printer($connector);
try {
    // ... Print stuff
} finally {
    $printer -> close();
}
````

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

When trying out a new brand of printer, it's a good idea to use the `SimpleCapabilityProfile`, which instructs the driver to avoid the use of advanced features (generally simpler image handling, ASCII-only text).

```php
use Mike42\Escpos\PrintConnectors\FilePrintConnector;
use Mike42\Escpos\CapabilityProfiles\SimpleCapabilityProfile;
$connector = new WindowsPrintConnector("smb://computer/printer");
$printer = new Printer($connector, $profile);
```

As another example, Star-branded printers use different commands:

```php
use Mike42\Escpos\PrintConnectors\FilePrintConnector;
use Mike42\Escpos\CapabilityProfiles\StarCapabilityProfile;
$connector = new WindowsPrintConnector("smb://computer/printer");
$printer = new Printer($connector, $profile);
```

Further developing this mechanism is a priority for future releases.

### Tips & examples
On Linux, your printer device file will be somewhere like `/dev/lp0` (parallel), `/dev/usb/lp1` (USB), `/dev/ttyUSB0` (USB-Serial), `/dev/ttyS0` (serial).

On Windows, the device files will be along the lines of `LPT1` (parallel) or `COM1` (serial). Use the `WindowsPrintConnector` to tap into system printing on Windows (eg. [Windows USB](https://github.com/mike42/escpos-php/tree/master/example/interface/windows-usb.php), [SMB](https://github.com/mike42/escpos-php/tree/master/example/interface/smb.php) or [Windows LPT](https://github.com/mike42/escpos-php/tree/master/example/interface/windows-lpt.php)) - this submits print jobs via a queue rather than communicating directly with the printer.

A complete real-world receipt can be found in the code of [Auth](https://github.com/mike42/Auth) in [ReceiptPrinter.php](https://github.com/mike42/Auth/blob/master/lib/misc/ReceiptPrinter.php). It includes justification, boldness, and a barcode.

Other examples are located in the [example/](https://github.com/mike42/escpos-php/blob/master/example/) directory.

## Available methods

### __construct(PrintConnector $connector, AbstractCapabilityProfile $profile)
Construct new print object.

Parameters:
- `PrintConnector $connector`: The PrintConnector to send data to.
- `AbstractCapabilityProfile $profile` Supported features of this printer. If not set, the DefaultCapabilityProfile will be used, which is suitable for Epson printers.

See [example/interface/]("https://github.com/mike42/escpos-php/tree/master/example/interface/) for ways to open connections for different platforms and interfaces.

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
$img = new EscposImage("logo.png");
$printer -> graphics($img);
```

See the [example/](https://github.com/mike42/escpos-php/blob/master/example/) folder for detailed examples.

The function [bitImage()](#bitimageescposimage-image-size) takes the same parameters, and can be used if your printer doesn't support the newer graphics commands. As an additional fallback, the `bitImageColumnFormat()` function is also provided.

### initialize()
Initialize printer. This resets formatting back to the defaults.

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

* [What is ESC/POS, and how do I use it?](http://mike.bitrevision.com/blog/what-is-escpos-and-how-do-i-use-it), which documents the output of test.php.
* [Setting up an Epson receipt printer](http://mike.bitrevision.com/blog/2014-20-26-setting-up-an-epson-receipt-printer)
* [Getting a USB receipt printer working on Linux](http://mike.bitrevision.com/blog/2015-03-getting-a-usb-receipt-printer-working-on-linux)

# Development

This code is MIT licensed, and you are encouraged to contribute any modifications back to the project.

For development, it's suggested that you load `imagick` and `gd` `Xdebug` PHP modules, and install `composer` and `phpunit`.

The tests are executed on [Travis CI](https://travis-ci.org/mike42/escpos-php) over versions of PHP from 5.3 up to 5.6, 7, and HHVM. Earlier versions of PHP are not supported.

Fetch a copy of this code and load idependencies with composer:

    git clone https://github.com/mike42/escpos-php
    cd escpos-php/
    composer install

Execute unit tests via `phpunit`:

    phpunit --configuration test/phpunit.xml --coverage-text

Pull requests and bug reports welcome.

<!-- ## Other versions
TODO: Some notes about related OSS projects will go here.
Some forks of this project have been developed by others for specific use cases. Improvements from the following projects have been incorporated into escpos-php:

- [wdoyle/EpsonESCPOS-PHP](https://github.com/wdoyle/EpsonESCPOS-PHP)
- [ronisaha/php-esc-pos](https://github.com/ronisaha/php-esc-pos)-->

<!--
TODO: A table of printer models vs programming guides available via the web would be good, but should go outside this README
## Vendor documentation
Epson notes that not all of its printers support all ESC/POS features, and includes a table in their documentation:

* [FAQ about ESC/POS from Epson](http://content.epson.de/fileadmin/content/files/RSD/downloads/escpos.pdf)

Note that many printers produced by other vendors use the same standard, and are compatible by varying degrees.
-->
