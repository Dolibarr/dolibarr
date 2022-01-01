# Frequently Asked Questions (FAQ)

## Can I print to File Format X with this?

If you are trying to generate XPS, PDF or DOCX or HTML files from PHP, then you are most likely in the wrong place.

The purpose of this driver it to generate binary ESC/POS code, which is understood by many embedded thermal receipt and impact printers.

## I have Printer X. Can I use this driver?

If the printer understands ESC/POS, and you know how to get raw binary data to it, then yes. Otherwise, no.

The [list of printers that are known to work](https://github.com/mike42/escpos-php/blob/development/README.md#printers) is crowd-sourced. We appreciate it when developers try out the driver, then [file information on the bug tracker](https://github.com/mike42/escpos-php/issues/new) with some information about which features worked on their model of printer.

To see how well your printer works, first check that it supports ESC/POS, then begin by attempting to send the text "Hello World" to your printer on the command-line, from the computer that will run PHP.

Once you solve this, [try to do the same from PHP](https://github.com/mike42/escpos-php/blob/development/README.md#basic-usage) using the default profile. Further details are in the [README](https://github.com/mike42/escpos-php/blob/development/README.md) file.

## Can you add support for Printer X?

Features vary between printers, so we collaborate on an ESC/POS printer compatibility database to collect known differences: [receipt-print-hq/escpos-printer-db](https://github.com/receipt-print-hq/escpos-printer-db).

If you encounter garbage output when you try to print images or special characters, then please submit a test page and a link to vendor documentation to the `escpos-printer-db` project, so that support can be improved for future versions.

## I have a printer that does not understand ESC/POS. Can I use this driver?

No. The purpose of this driver it to generate binary ESC/POS code. If your printer doesn't understand that, then this code wont be much use to you.

Some printers do have an emulation mode for ESC/POS. The vendor docs will tell if this is the case, and how to enable it.

## Why do I get this error when I try to print?

Start by testing that you can send text to your printer outside of escpos-php. The examples linked to in the README are commented with some commands to get you started.

Generally, initial setup problems seem to have one of these causes:

1. You are writing to the wrong place. Writing to `LPT1` does not output to parallel port on Linux, and `/dev/ttyS0` is not a serial printer on Windows.
2. The printer has not been set up to accept printing the way you expect. This means permissions on Linux, network printers being configured, and shared printers having user accounts and firewalls set up correctly on the print server.
3. Your printer actually doesn't work (rare but possible).

To be clear, these are not escpos-php issues: No amount of PHP code can set up your printer for you. Instead, the driver relies on developers determining how their setup is going to work before using a connector to transport data to their printer.

Once you have a working command to send text to your printer (from the PHP server), you are ready to use escpos-php. You can try to use a PrintConnector now, based on your operating system and printer interface. A table is located in the README to help you select the right one.

The connectors are-

- `FilePrintConnector` and `NetworkPrintConnector` directly use files or network sockets.
- `WindowsPrintConnector` and `CupsPrintConnector` tie in with Windows and Unix system printing.
- `DummyPrintConnector` does not connect to a real printer, and can be used to save ESC/POS receipts to a database, for example.

At this point, you might find that the way you would like to print is not supported by escpos-php. You can post your printing command as a feature request on the issue tracker.

Lastly, you may run in to the final common trap:

4. Your PHP is not running with the same sort of permissions as your login account. Again, no amount of PHP code can fix this. For example, on LAMP, your `www-data` user needs to be in the `lp` group, while on WAMP, `Local Service` account may run in to problems. SELinux and firewalls are also worth a look.

When printing fails, you can expect a PHP Exception that explains what went wrong. They are all clues:

- `Warning: copy(\\pc\printer): failed to open stream: Permission denied`
- `/dev/usb/lp0: Permission denied`
- `User name or password is incorrect`

Ensure that while you are developing, you configure PHP to show error messages, so that you can see these problems.

Please file a bug if you think that there is a specific situation which escpos-php could provide better error messages for.

## Can I print over the network?

Certainly, as long as your printer is available over the network.

- `NetworkPrintConnector` will speak directly to an Ethernet-connected printer on port 9100.

For USB or Serial printers, you need to install the printer on a computer and then share it, so that it becomes network-accessible.

- `WindowsPrintConnector` will connect to Windows shared printers from Windows or Linux (Linux users will need Samba).
- `CupsPrintConnector` will connect to CUPS-shared printers from Linux or Mac.

Always start by testing your shared printer setup outside of escpos-php. The examples linked to in the README are commented with some example commands to get you started. Typically, networks, firewalls and permissions need to be set up.

Once you have a working command to send text to your printer (from the PHP server), you are ready to use escpos-php.

If you have any issues at this stage, please ask on the issue tracker, and include the commands that you used to verify your setup.

## Can I print from my server on the Internet?

Since PHP is a server-side language, escpos-php is a server-side print library. The driver is able to transport data between a server and a printer in a few different ways, all of them server-side. For example, you may print to a USB printer *connected to the server running PHP*, or an Ethernet printer *on a network accessible to the server*.

Many developers dream of having an application that is hosted on the public Internet, with POS terminals accessing it, and printing via a web browser. Because the webserver cannot see the printer in this sort of setup, a server-side print driver is not much use.

Because of this, there are no cut-and-paste recipes available, but here are two top-level approaches you could take:

1. Architect your application so that the server can see your printer
2. Use an application which runs client-side to deliver print data instead

### Option 1: Allow the server to print

Server-side printing is viable if the server can get to the printer. Here are some ways it could work:

- Run your server on the LAN instead, and read the section above about printing over the network
- Set up a VPN so that your cloud-hosted server can also access the LAN
- Expose the printer via some other secure tunnel to the server, via SSH or TLS

Please do your own research to determine how these may apply to your setup- the escpos-php issue tracker is not a place where you should be requesting network support.

### Option 2: Use client software to print

If you aren't able to set up some network infrastructure to implement the above, then you cannot use a server-side print driver.

Here are some browser-based printing tools which you may like to consider instead.

- Use system printing with a vendor driver, and some good `@media print` CSS
- [Chrome Raw Print](https://github.com/receipt-print-hq/chrome-raw-print) app
- [qz](https://qz.io/)
- [ePOS-Device SDK for JavaScript](https://reference.epson-biz.com/modules/ref_epos_device_js_en/index.php?content_id=139). Requires network interface card that supports ePOS (UB-E04/R04)

Please direct queries about client-side printing products to the appropriate project.

## Why is image printing slow?

Three things tend to slow down the image processing:

1. Slow PHP code
2. Data link
3. The printer itself

First, ensure you have the Imagick plugin loaded. The driver will avoid a slower image processing implementation once you've got it.

Next, connect over a faster interface. Serial printers have a low bit-rate, and the printer spends a lot of time waiting for data. If you have USB or Ethernet, then use it (note: storing graphics to the printer memory is not currently implemented).

Lastly, the printer will go faster if you use less pixels. Since images are two-dimensional, scaling down by 50% removes 75% of the pixels. The driver can then print at a half the density, so that your lower resolution image appears the same size when printed.

## How can I get the status of the printer?

This feature is not implemented, but a solution for some Epson printers is planned.

Only `FilePrintConnector` or `NetworkPrintConnector` will support reading from the printer, ensure that you migrate to those if you would like these features.

## How do I produce this complex layout?

ESC/POS "page mode" is not currently supported, which would allow some printers to render some more complex layouts natively

Since the output is raster anyway, it is suggested that you render your output to an image and print that instead. The driver supports PDF printing via Imagick, and an example that uses `wkhtmltoimage` is available in the repository.
