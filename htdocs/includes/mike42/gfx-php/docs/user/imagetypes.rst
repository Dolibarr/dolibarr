Image types
===========

Every raster image in ``gfx-php`` implements the :class:`RasterImage` interface.

There are several classes that implement this interface, which handle different representations of
the image data.

:class:`RgbRasterImage`
  Holds RGB data.
:class:`GrayscaleRasterImage`
  Holds monochrome data.
:class:`BlackAndWhiteRasterImage`
  Holds 1-bit raster data.
:class:`IndexedRasterImage`
  Holds an image and associated palette.

Creating an image
^^^^^^^^^^^^^^^^^

Each of these classes has a static method which can be used to create an image of that type.

These only require a ``width`` and ``height``.

.. code-block:: php

   use Mike42\GfxPhp\BlackAndWhiteRasterImage;
   $image = BlackAndWhiteRasterImage::create(50, 100);

Converting between image types
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

You can convert between image types. This is similar to performing a `color-space conversion` in an image editor.

.. code-block:: php

   use Mike42\GfxPhp\Image;
   $image = Image::load("tux.ppm");
   $image -> toBlackAndWhite();

The methods to use are:

- :meth:`RasterImage::toBlackAndWhite()`
- :meth:`RasterImage::toGrayscale()`
- :meth:`RasterImage::toIndexed()`
- :meth:`RasterImage::toRgb()`

Each of these returns an image of the requested type. They work by instantiating a new image, then copying across the data as accurately as possible. As a result, the original image is unmodified.

Implicit conversions
^^^^^^^^^^^^^^^^^^^^

Some file formats only accept specific types of raster data, so the :meth:`RasterImage::write()` method will need to convert it. For example, this ``.pbm`` will be limited to 2 colors, which is achieved by using :meth:`RasterImage:toBlackAndWhite` in the background:

.. code-block:: php

   use Mike42\GfxPhp\Image;
   $wheel = Image::load("colorwheel.ppm");
   $wheel -> write("wheel.pbm");

Since converting the color space creates a new image, the image stored in ``$wheel`` is unmodified.
