Examples
--------

This folder contains a collectoion of feature examples.
Generally, demo.php is the fastest way to find out which features your
printer supports.

## Subfolders
- `interface/` - contains examples for output interfaces: eg, parallel, serial, USB, network, file-based.
- `specific/` - examples made in response to issues & questions. These cover specific languages, printers and interfaces, so hit narrower use cases.

## List of examples

Each example prints to standard output, so either edit the print connector, or redirect the output to your printer to see it in action. They are designed for developers: open them in a text editor before you run them!

- `bit-image.php` - Prints a images to the printer using the older "bit image" commands.
- `demo.php` - Demonstrates output using a large subset of availale features.
- `qr-code.php` - Prints QR codes, if your printer supports it.
- `character-encodings.php` - Shows available character encodings. Change from the DefaultCapabilityProfile to get more useful output for your specific printer.
- `graphics.php` - The same output as `bit-image.php`, printed with the newer graphics commands (not supported on many non-Epson printers)
- `receipt-with-logo.php` - A simple receipt containing a logo and basic formating.
- `character-encodings-with-images.php` - The same as `character-encodings.php`, but also prints each string using an `ImagePrintBuffer`, showing compatibility gaps.
- `print-from-html.php` - Runs `wkhtmltoimage` to convert HTML to an image, and then prints the image. (This is very slow)
- `character-tables.php` - Prints a compact character code table for each available character set. Used to debug incorrect output from `character-encodings.php`.
- `print-from-pdf.php` - Loads a PDF and prints each page in a few different ways (very slow as well)

