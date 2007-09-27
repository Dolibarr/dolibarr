PHP-Barcode 0.3pl1

PHP-Barcode generates
  - Barcode-Images using libgd (png, jpg,gif),
  - HTML-Images (using 1x1 pixel and html-table)
  - silly Text-Barcodes

PHP-Barcode encodes using
  - a built-in EAN-13/ISBN Encoder
  - genbarcode (by Folke Ashberg), a command line
    barcode-encoder which uses GNU-Barcode
    genbarcode can encode EAN-13, EAN-8, UPC, ISBN, 39, 128(a,b,c),
    I25, 128RAW, CBR, MSI, PLS
    genbarcode is available at www.ashberg.de/bar 

(C) 2001,2002,2003,2004 by Folke Ashberg <folke@ashberg.de>

The newest version can be found at http://www.ashberg.de/bar


INSTALLATION:

 WHAT YOU NEED TO BE ABLE TO USE PHP-BARCODE
   You need php>4 on your system.
   You need the gd2-extension to be able to render images.
   You need a TrueTypeFont if you want to see the CODE below the bars.
   You need genbarcode (from www.ashberg.de/bar) in you want to use
       another encoding than EAN-12/EAN-12/ISBN

 Copy the following files into your html/php-directory
  - php-barcode.php    - main library
  - encode_bars.php    - built-in encoders
    optional:
  - barcode.php        - Sample-File
  - white.png          - for HTML-Images
  - black.png          - for HTML-Images

  FONT-Installation
      UNIX:
      A TrueTypeFont isn't included in this distribution! 
      Copy one into the html/php-directory and change in php-barcode.php
      $font_loc (change the arialbd.tff to your font name).
      arialbd.ttf from Windows looks great.

      WINDOWS:
      If you use Windows the font should be located automatically.

  OPTIONAL - genbarcode:
      If you want to generate not only EAN-12/EAN-13/ISBN-Codes you have to install
      genbarcode, a small unix-commandline tool which uses GNU-Barcode.
      genbarcode is available http://www.ashberg.de/bar , read genbarcodes
      README for installation.
      If you have installed genbarcode not to /usr/bin set the $genbarcode_loc
      in php-barcode.php .

  TESTING
      If everything works fine you should see an image if you call 
      http://localhost/path/barcode.php

      Or call http://localhost/path/barcode.php?code=<CODE>&encoding=<ENCODING>&mode=<png|jpg|gif|html|text>&size=<1,2,3,...>


      
If you need more then the sample barcode.php can do, you need to build your own.
    
FUNCTIONS - API-Reference

--------------------------------------------------------------------------
function barcode_encode(code, encoding)
  encodes $code with $encoding using genbarcode OR built-in encoder
  if you don't have genbarcode only EAN-13/ISBN is possible

You can use the following encodings (when you have genbarcode):
  ANY    choose best-fit (default)
  EAN    8 or 13 EAN-Code
  UPC    12-digit EAN 
  ISBN   isbn numbers (still EAN-13) 
  39     code 39 
  128    code 128 (a,b,c: autoselection) 
  128C   code 128 (compact form for digits)
  128B   code 128, full printable ascii 
  I25    interleaved 2 of 5 (only digits) 
  128RAW Raw code 128 (by Leonid A. Broukhis)
  CBR    Codabar (by Leonid A. Broukhis) 
  MSI    MSI (by Leonid A. Broukhis) 
  PLS    Plessey (by Leonid A. Broukhis)

  return:
   array[encoding] : the encoding which has been used
   array[bars]     : the bars
   array[text]     : text-positioning info

--------------------------------------------------------------------------
function barcode_outimage(text, bars [, scale [, mode [, total_y [, space ]]]] )

 Outputs an image using libgd

   text   : the text-line (<position>:<font-size>:<character> ...)
   bars   : where to place the bars  (<space-width><bar-width><space-width><bar-width>...)
   scale  : scale factor ( 1 < scale < unlimited (scale 50 will produce
                                                  5400x300 pixels when
                                                  using EAN-13!!!))
   mode   : png,gif,jpg, depending on libgd ! (default='png')
   total_y: the total height of the image ( default: scale * 60 )
   space  : space
            default:
     	$space[top]   = 2 * $scale;
     	$space[bottom]= 2 * $scale;
     	$space[left]  = 2 * $scale;
     	$space[right] = 2 * $scale;

--------------------------------------------------------------------------
function barcode_outhtml(text, bars [, scale [, total_y [, space ]]] )

 returns(!) HTML-Code for barcode-image using html-code (using a table and with black.png and white.png)

   text   : the text-line (<position>:<font-size>:<character> ...)
   bars   : where to place the bars  (<space-width><bar-width><space-width><bar-width>...)
   scale  : scale factor ( 1 < scale < unlimited (scale 50 will produce
                                                  5400x300 pixels when
                                                  using EAN-13!!!))
   total_y: the total height of the image ( default: scale * 60 )
   space  : space
            default:
     	$space[top]   = 2 * $scale;
     	$space[bottom]= 2 * $scale;
     	$space[left]  = 2 * $scale;
     	$space[right] = 2 * $scale;
 
--------------------------------------------------------------------------
function barcode_outtext(code, bars)

 Returns (!) a barcode as plain-text
 ATTENTION: this is very silly!

   text   : the text-line (<position>:<font-size>:<character> ...)
   bars   : where to place the bars  (<space-width><bar-width><space-width><bar-width>...)

--------------------------------------------------------------------------
For more function see php-barcode.php
Also see barcode.php or just use them :)

  
  



This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 675 Mass Ave, Cambridge, MA 02139, USA.


