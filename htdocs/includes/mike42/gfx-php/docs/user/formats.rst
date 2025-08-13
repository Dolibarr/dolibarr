File formats
============

.. contents::
   :local:

Input formats
-------------

Files are read from a file or URL by using the :meth:`Image::fromFile()` function:

.. code-block:: php
  
  use Mike42\GfxPhp\Image;
  $tux = Image::fromFile("tux.png")

If the your image is not being read from a file, then :meth:`Image::fromBlob()` can load it from a binary string:

.. code-block:: php

  use Mike42\GfxPhp\Image;
  $tuxStr = "...";
  $tux = Image::fromBlob($tuxStr, "tux.png");

In either case, the input format is determined using the file's `magic number`_.

.. _magic number: https://en.wikipedia.org/wiki/Magic_number_(programming)

PNG
^^^

The PNG codec is used where the input has the ``png`` file extension.

All valid PNG files can be read, including:

- RGB or RGBA images
- Indexed images
- Monochrome images, from 1 to 16 bits per pixel
- Interlaced images

This library currently has limited support for transparency, and will discard any alpha channel from a PNG file when it is loaded.

GIF
^^^

The GIF codec is used where the input has the ``gif`` file extension. Any well-formed GIF file can be read, but there are some limitations:

- If a GIF file contains multiple images, then only the first one will be loaded
- If a transparent color is present, then this will be mixed to white

A GIF image will always be loaded into an instance of :class:`IndexedRasterImage`, which makes palette information available.

BMP
^^^

The BMP codec is used where the input has the ``bmp`` or ``dib`` file extensions. Most uncompressed or run-length encoded bitmap files can be read by this library.

The returned object will be an instance of instance of :class:`IndexedRasterImage` if the color depth is 8 or lower.

24-bit, 16-bit and 32-bit bitmap images are also supported, and will be loaded into an instance of :class:`RgbRasterImage`. If an alpha channel is used, then it will be mixed to white.

Netpbm Formats
^^^^^^^^^^^^^^

The Netpbm formats are a series of uncompressed bitmap formats, which can represent most types of image. These formats can be read by ``gfx-php``:

:PNM: This is a file extension only. Files carrying ``.pnm`` extension can carry any of the below formats.
:PPM: This is a color raster format. A PPM file is identified by the P6 magic number, and will be loaded into an instance of :class:`RgbRasterImage`.
:PGM: This is a monochrome raster format. A PGM file is identified by the P5 magic number, and will be loaded instance of :class:`GrayscaleRasterImage`.
:PBM: This is a 1-bit bitmap format. A PBM file is identified by the P4 header, and loaded into an instance of :class:`BlackAndWhiteRasterImage`.

Each of these formats has both a binary and text encoding. ``gfx-php`` only supports the binary encodings at this stage.

WBMP
^^^

The WBMP codec is used where the input has the ``wbmp`` file extension. A WBMP image will always be loaded into a :class:`BlackAndWhiteRasterImage` object.

Output formats
--------------

When you write a :class:`RasterImage` to a file, you need to specify a filename. The extension on this file is used to determine the desired output format.

There is currently no mechanism to write a file directly to a string.

PNG
^^^

The PNG format is selected by using the ``png`` file extension when you call :func:`RasterImage::write()`.

.. code-block:: php

  $tux -> write("tux.png");

This library will currently output PNG files as RGB data. If you write to PNG from an instance of :class:`RgbRasterImage`, then no conversion has to be done, so the output is significantly faster.

GIF
^^^

The GIF format is selected by using the ``gif`` file extension.

.. code-block:: php

  $tux -> write("tux.gif");

This format is limited to using a 256-color palette.

- If your image is not an `IndexedRasterImage`, then it is indexed when you write.
- If the image uses more than 256 colors, then it will be converted to an 8-bit RGB representation (3 bits red, 3 bits green, 2 bits blue), which introduces some distortions.

When you are creating GIF images, then you can avoid these conversions by using a :class:`IndexedRasterImage` with a palette of fewer than 256 colors.

There is no encoder for multi-image GIF files at this stage.

BMP
^^^

The BMP format is selected by using the ``bmp`` file extension.

.. code-block:: php
  
  $tux -> write("tux.bmp");

This library will currently output BMP files using an uncompressed 24-bit RGB representation of the image.

WBMP
^^^

The WBMP format is selected by using the ``wbmp`` file extension.

.. code-block:: php

  $tux -> write("tux.wbmp");

The image will be converted to a 1-bit monochrome representation, which is the only type of image supported by WBMP.

Netpbm Formats
^^^^^^^^^^^^^^

The Netpbm formats can be used for output. Each format is identified by their respective file extension:

.. code-block:: php

  $tux -> write("tux.ppm");
  $tux -> write("tux.pgm";
  $tux -> write("tux.pbm");

Since each of these formats has a different raster data representation, you should be aware that 

:PPM: For this output format, the file is converted to a :class:`RgbRasterImage` and typically written with a 24 bit color depth. In some cases, a 48 bit color depth will be used.
:PGM: The file is converted to a :class:`GrayscaleRasterImage` and written with a depth of 8 or 16 bits per pixel.
:PPM: The file is converted to a :class:`BlackAndWhiteRasterImage` and written with 1 bit per pixel.

If you want to avoid these conversions, then you should use the ``pnm`` extension to write your files. Since files with this extension can hold any of the above formats, the output encoder will avoid converting the raster data where possible.

.. code-block:: php

  $tux -> write("tux.pnm");

