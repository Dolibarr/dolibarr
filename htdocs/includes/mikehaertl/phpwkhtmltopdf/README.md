PHP WkHtmlToPdf
===============

[![Build Status](https://secure.travis-ci.org/mikehaertl/phpwkhtmltopdf.png)](http://travis-ci.org/mikehaertl/phpwkhtmltopdf)
[![Latest Stable Version](https://poser.pugx.org/mikehaertl/phpwkhtmltopdf/v/stable.svg)](https://packagist.org/packages/mikehaertl/phpwkhtmltopdf)
[![Total Downloads](https://poser.pugx.org/mikehaertl/phpwkhtmltopdf/downloads)](https://packagist.org/packages/mikehaertl/phpwkhtmltopdf)
[![Latest Unstable Version](https://poser.pugx.org/mikehaertl/phpwkhtmltopdf/v/unstable.svg)](https://packagist.org/packages/mikehaertl/phpwkhtmltopdf)
[![License](https://poser.pugx.org/mikehaertl/phpwkhtmltopdf/license.svg)](https://packagist.org/packages/mikehaertl/phpwkhtmltopdf)

PHP WkHtmlToPdf provides a simple and clean interface to ease PDF and image creation with
[wkhtmltopdf](http://wkhtmltopdf.org). **The `wkhtmltopdf` and - optionally - `wkhtmltoimage`
command must be installed and working on your system.** See the section below for details.

## Installation

Install the package through [composer](http://getcomposer.org):

```
composer require mikehaertl/phpwkhtmltopdf
```

Make sure, that you include the composer [autoloader](https://getcomposer.org/doc/01-basic-usage.md#autoloading)
somewhere in your codebase.

### Single page PDF

```php
use mikehaertl\wkhtmlto\Pdf;

// You can pass a filename, a HTML string, an URL or an options array to the constructor
$pdf = new Pdf('/path/to/page.html');

// On some systems you may have to set the path to the wkhtmltopdf executable
// $pdf->binary = 'C:\...';

if (!$pdf->saveAs('/path/to/page.pdf')) {
    echo $pdf->getError();
}
```

### Multi page PDF with Toc and Cover page


```php
use mikehaertl\wkhtmlto\Pdf;

$pdf = new Pdf;
$pdf->addPage('/path/to/page.html');
$pdf->addPage('<html>....</html>');
$pdf->addPage('http://www.example.com');

// Add a cover (same sources as above are possible)
$pdf->addCover('/path/to/mycover.html');

// Add a Table of contents
$pdf->addToc();

// Save the PDF
$pdf->saveAs('/path/to/report.pdf');

// ... or send to client for inline display
$pdf->send();

// ... or send to client as file download
$pdf->send('report.pdf');

// ... or you can get the raw pdf as a string
$content = $pdf->toString();
```

### Creating an image

```php
use mikehaertl\wkhtmlto\Image;

// You can pass a filename, a HTML string, an URL or an options array to the constructor
$image = new Image('/path/to/page.html');
$image->saveAs('/path/to/page.png');

// ... or send to client for inline display
$image->send();

// ... or send to client as file download
$image->send('page.png');
```

## Setting options

The `wkhtmltopdf` shell command accepts different types of options:

 * global options (e.g. to set the document's DPI or the default page options)
 * page options (e.g. to supply a custom CSS file for a page)
 * toc options (e.g. to set a TOC header)

Please see `wkhtmltopdf -H` for a full explanation. All options are passed as array, for example:

```php
$options = array(
    'no-outline',           // option without argument
    'encoding' => 'UTF-8',  // option with argument

    // Option with 2 arguments
    'cookie' => array('name'=>'value'),

    // Repeatable options with single argument
    'run-script' => array(
        '/path/to/local1.js',
        '/path/to/local2.js',
    ),

    // Repeatable options with 2 arguments
    'replace' => array(
        '{page}' => $page++,
        '{title}' => $pageTitle,
    ),
);
```

Options can be passed to several methods for PDFs:

```php
$pdf = new Pdf($globalOptions);         // Set global PDF options
$pdf->setOptions($globalOptions);       // Set global PDF options (alternative)
$pdf->addPage($page, $pageOptions);     // Add page with options
$pdf->addCover($page, $pageOptions);    // Add cover with options
$pdf->addToc($tocOptions);              // Add TOC with options
```

> Note, that you can also use page options in the global PDF options. `wkhtmltopdf`
> will apply them to all pages unless you override them when you add a page.

For `wkhtmltoimage` there's only one set of options:

```php
$image = new Image($options);   // Set image options
$image->setOptions($options);   // Set image options (alternative)
```

### Wrapper options

The wrapper itself is configured by the following special options that can be passed
to the constructor, set as object properties or via `setOptions()`:

 * `binary`: Full path to the `wkhtmltopdf` command. Default is `wkhtmltopdf` which assumes that the
   command is in your shell's search path.
 * `commandOptions`: Options to pass to https://github.com/mikehaertl/php-shellcommand.
 * `tmpDir`: Path to tmp directory. Defaults to the PHP temp dir.
 * `ignoreWarnings`: Whether to ignore any errors if a PDF file was still created. Default is `false`.
 * `version9`: Whether to use command line syntax for older wkhtmltopdf versions.

In addition to the `binary`, `commandOptions`, `tmpDir` and `ignoreWarnings` options above,
the `Image` class also has a `type` option:

 * `type`: The image type. Default is `png`. You can also use `jpg` or `bmp`.

`commandOptions` can be used to set environment variables for `wkhtmltopdf`. For example, if you
want to pass UTF-8 encoded arguments, you may have to set the `LANG` environment variable.

```php
$pdf = new Pdf(array(
    'binary' => '/obscure/path/to/wkhtmltopdf',
    'ignoreWarnings' => true,
    'commandOptions' => array(
        'useExec' => true,      // Can help if generation fails without a useful error message
        'procEnv' => array(
            // Check the output of 'locale' on your system to find supported languages
            'LANG' => 'en_US.utf-8',
        ),
    ),
));
```

## Error handling

`send()`, `saveAs()` and `toString()` will return `false` on error. In this case the detailed error message is
available from `getError()`:

```php
if (!$pdf->send()) {
    throw new Exception('Could not create PDF: '.$pdf->getError());
}

$content = $pdf->toString();
if ($content === false) {
    throw new Exception('Could not create PDF: '.$pdf->getError());
}
```

## Note for Windows users

If you use double quotes (`"`) or percent signs (`%`) as option values, they may get converted to spaces.
In this case you can disable argument escaping in the [command](https://github.com/mikehaertl/php-shellcommand).
There are also two interesting options to `proc_open()` that you may want to use:

```php
$pdf = new Pdf(array(
    'commandOptions' => array(
        'escapeArgs' => false,
        'procOptions' => array(
            // This will bypass the cmd.exe which seems to be recommended on Windows
            'bypass_shell' => true,
            // Also worth a try if you get unexplainable errors
            'suppress_errors' => true,
        ),
    ),
    ...
));
```

But then you have to take care of proper argument escaping yourself. In some cases it may be neccessary to
surround your argument values with extra double quotes.

I also found that some options don't work on Windows (tested with wkhtmltopdf 0.11 rc2), like the
`user-style-sheet` option used in the example below.


## Installation of wkhtmltopdf

It's recommended that you download the latest wkhtmltopdf from their website:

 http://wkhtmltopdf.org/downloads.html

These versions should run out of the box.

If for some reason you can't do so, you may run into an issue with the dynamically linked version of
`wkhtmltopdf`. This is what you get for example on Ubuntu 12.04 LTS if you install the wkhtmltopdf package.
It will work, but to use all features it requires an X server which is usually not available on headless
webservers.

We therefore provide two Xvfb based workarounds. You can either use

 * the built in Xvfb support or
 * a standalone Xvfb server.

Both require the Xvfb package to be installed on the system and both also have some drawbacks.

#### Built in Xvfb support

This wraps each call to `wkhtmltopdf` with [xvfb-run](http://manpages.ubuntu.com/manpages/lucid/man1/xvfb-run.1.html).
`xvfb-run` will run any given command in a X environment without all the overhead of a full X session.
The drawback with this solution is, that there's still a new session fired up for each an every PDF you create,
which will create quite some extra load on your CPU. So this setup is only recommended for low frequency sites.

To use the built in support you have to set `enableXvfb` in the `commandOptions`. There are also some options you can set.

```php
$pdf = new Pdf(array(
    // Explicitly tell wkhtmltopdf that we're using an X environment
    'use-xserver',

    // Enable built in Xvfb support in the command
    'commandOptions' => array(
        'enableXvfb' => true,

        // Optional: Set your path to xvfb-run. Default is just 'xvfb-run'.
        // 'xvfbRunBinary' => '/usr/bin/xvfb-run',

        // Optional: Set options for xfvb-run. The following defaults are used.
        // 'xvfbRunOptions' =>  '--server-args="-screen 0, 1024x768x24"',
    ),
));
```

#### Standalone Xvfb

It's better to start a Xvfb process once and reuse it for all your PHP requests
(thanks to Larry Williamson for [the original idea](https://coderwall.com/p/tog9eq)).
This requires that you have root access to your machine as you have to add a startup script
for that process. We have provided an example [init script](https://gist.github.com/eusonlito/7889622)
for Ubuntu (thanks eusonlito). You can put it to `/etc/init.d/xvfb` and add it to your startup files with
`update-rc.d xvfb defaults 10`.

If your system is based on systemd [this config](https://gist.github.com/nkm/91006178753df6f503c1)
should help (thanks nkm).

If your `Xvfb` process is running, you just have to tell the class to use this X display for
rendering. This is done via an environment variable.

```php
$pdf = new Pdf(array(
    'use-xserver',
    'commandOptions' => array(
        // You can change ':0' to whatever display you pick in your daemon script
        'procEnv' => array( 'DISPLAY' => ':0' ),
    ),
));
```

## Full example

For me `wkhtmltopdf` seems to create best results with smart shrinking turned off.
But then I had scaling issues which went away after I set all margins to zero and instead
added the margins through CSS. You can also use `cm` or `in` in CSS as this is more apropriate for print styles.

```php
use mikehaertl\wkhtmlto\Pdf;

// Create a new Pdf object with some global PDF options
$pdf = new Pdf(array(
    'no-outline',         // Make Chrome not complain
    'margin-top'    => 0,
    'margin-right'  => 0,
    'margin-bottom' => 0,
    'margin-left'   => 0,

    // Default page options
    'disable-smart-shrinking',
    'user-style-sheet' => '/path/to/pdf.css',
));

// Add a page. To override above page defaults, you could add
// another $options array as second argument.
$pdf->addPage('/path/to/demo.html');

$pdf->send();
```

**demo.html**
```html
<!DOCTYPE html>
<html>
<head>
</head>
<body>

    <div id="print-area">
        <div id="header">
            This is an example header.
        </div>
        <div id="content">
            <h1>Demo</h1>
            <p>This is example content</p>
        </div>
        <div id="footer">
            This is an example footer.
        </div>
    </div>

</body>
</html>
```

**pdf.css**
```css
/* Define page size. Requires print-area adjustment! */
body {
    margin:     0;
    padding:    0;
    width:      21cm;
    height:     29.7cm;
}

/* Printable area */
#print-area {
    position:   relative;
    top:        1cm;
    left:       1cm;
    width:      19cm;
    height:     27.6cm;

    font-size:      10px;
    font-family:    Arial;
}

#header {
    height:     3cm;

    background: #ccc;
}
#footer {
    position:   absolute;
    bottom:     0;
    width:      100%;
    height:     3cm;

    background: #ccc;
}
```

## Links

Also check out my [php-pdftk](https://github.com/mikehaertl/php-pdftk) wrapper around `pdftk`
which brings the full power of `pdftk` to PHP.
