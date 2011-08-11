README (english)
----------------

EFC/XFSS - Enhanced File Crypt/Extended File Stealth System.
Web:         	http://www.phpclasses.org/browse/package/1297.html
Licence:     	GNU/LGPL v3
Author:      	Humaneasy Exp
Modified by:	Regis Houssin
Version:     	1.0.1
Last change: 	2008-04-27

-- SUMMARY --

The main idea behind "EFC/XFSS - Enhanced File Crypt/Extended File Stealth System" is to have your uploaded files safe in the server in a way that, even if someone can get them, no one can read them without knowing a few details to decrypt the files.

The class uses a random trick to select the encryption method that is used. This will always generate diferent encrypted files.

The file names are also obfuscated, so a sneaker will not know what the original format was.

This class was mainly developed to be used with GPL'ed Care2002 Medical Information System (www.care2x.org). However, its use was postponed because most of the files uploaded were images and most of them do not have any personal identifiable info on them. 

This class, in a broader sense, has yet a long way to go. For now it is simply a sub-class of part of the RC4Crypt class. It allows an easy process of encryption and decryption of uploaded files. 

The next challenge will be to encrypt and decrypt the files at client side, perhaps with Javascript, for those that cannot have an SSL connection, and also the creation of a replacement class for those that do not have the possibility to use libmcrypt.

-- REQUIREMENTS --

* It requires libmcrypt support and, when possible, an (optional) 
  SSL internet connection to be used. 
* The class needs mcrypt PHP functions setup. 
* This class (still) uses PHP 4 and was not tested with PHP 5.

-- INSTALLATION --

Unpack the files included.

The only files that you need to look at into are index.php, srcefc.php, mkconfig.php and .htaccess (the last one to use in the secured directory for strict security if you can not put it outside Web document tree). 

Developer documentation is included inside the PHP scripts.

-- CONFIGURATION --

* IMPORTANT! Check that you have mcrypt support installed in your PHP and that you are using PHP 4.

* You also need to search for the definition of __SECURE_PATH__, and modify the path in the above PHP files.

-- TROUBLESHOOTING --

See http://www.phpclasses.org/discuss/package/1297/ for help.
We do not support the product besides own code errors.

-- CONTACT --

Current maintainers:
* Lopo Lencastre de Almeida (humaneasy) - http://drupal.org/user/26117

This project has been sponsored by:
* iPublicis
  Consulting and planning of Drupal powered sites, we offer installation, development, 
  theming, customization, SEO planning and hosting to get you started. 
  Besides Drupal, advertising and FLOSS consulting.
  Visit http://www.ipublicis.com to contact us.


-- NOTICE --

"EFC/XFSS - Enhanced File Crypt/Extended File Stealth System" is relased under the GNU/LGPL version 3 or above.

THIS SOFTWARE IS PROVIDED BY THE PROJECT AND CONTRIBUTORS ``AS IS'' AND Redistribution and use in source and binary forms, with or without modification, are permitted provided that the following conditions are met:

 1. Redistributions of source code must retain the above copyright
    notice, this list of conditions and the following disclaimer.
 
 2. Redistributions in binary form must reproduce the above copyright
    notice, this list of conditions and the following disclaimer in the
    documentation and/or other materials provided with the distribution.
 
 3. Neither the name of the project nor the names of its contributors
    may be used to endorse or promote products derived from this software
    without specific prior written permission.
 
THIS SOFTWARE IS PROVIDED BY THE PROJECT AND CONTRIBUTORS ``AS IS'' AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED.  IN NO EVENT SHALL THE PROJECT OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
