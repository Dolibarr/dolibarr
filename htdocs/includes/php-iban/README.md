php-iban
========

`php-iban` is a library for parsing, validating and generating IBAN (and IIBAN) bank account information in PHP.

[![Build Status](https://travis-ci.org/globalcitizen/php-iban.png)](https://travis-ci.org/globalcitizen/php-iban)
[![Latest Stable Version](https://poser.pugx.org/globalcitizen/php-iban/v/stable)](https://packagist.org/packages/globalcitizen/php-iban) 
[![License](https://poser.pugx.org/globalcitizen/php-iban/license)](https://packagist.org/packages/globalcitizen/php-iban)

All parts of an IBAN can be retrieved, including country code, checksum, BBAN, financial institution or bank code, account number, and where a fixed-length national system is in use, also branch/sort code. Legacy national checksums may also be retrieved, validated and correctly set, where available, whether they apply to the account number portion, bank and branch identifiers, part or all of the above. IBAN country codes can be converted in to ISO3166-1 alpha-2 and IANA formats, the parent IBAN country acting as registrar for dependent territories may be queried, the official national currency (ISO4217 alpha code format), central bank name and central bank URL may also be queried to ease integration. IBANs may be converted between human and machine representation. IBANs may be obfuscated for presentation to humans in special circumstances such as relative identification. A database of example/test IBANs from different countries is included. Finally, highly accurate suggestions for originally intended input can be made when an incorrect IBAN is detected and is due to mistranscription error.

The parser was built using regular expressions to adapt the contents of the _official_ IBAN registry available from SWIFT then manually modified for special cases such as [errors and omissions in SWIFT's official specifications](https://raw.githubusercontent.com/globalcitizen/php-iban/master/docs/COMEDY-OF-ERRORS).

Various deficiencies in the initial adaptation have since been rectified, and the current version should be a fairly correct and reliable implementation.

Where appropriate, __European Committee for Banking Standards__ (ECBS) recommendations have also been incorporated.

Please bear in mind that because the specification changes frequently, it may not be 100% up to date if a new version has been recently released - I do my best though. We are currently thought to be up to date with [the January 2020 release, ie. PDF release #86](https://www.swift.com/standards/data-standards/iban).

Licensed under LGPL, it is free to use in commercial settings.


Countries Supported
-------------------

The following 116 official and *unofficial* IBAN countries are supported.

* Albania (AL)
* *Algeria* (DZ)
* Andorra (AD)
* *Angola* (AO)
* Austria (AT)
* Azerbaijan (AZ)
* Bahrain (BH)
* Belarus (BY)
* Belgium (BE)
* *Benin* (BJ)
* Bosnia and Herzegovina (BA)
* Brazil (BR)
* British Virgin Islands (VG)
* Bulgaria (BG)
* *Burkina Faso* (BF)
* *Burundi* (BI)
* *Cameroon* (CM)
* *Central African Republic* (CF)
* *Chad* (TD)
* *Cape Verde* (CV)
* *Comoros* (KM)
* *Congo* (CG)
* Costa Rica (CR)
* *Côte d'Ivoire* (CI)
* Croatia (HR)
* Cyprus (CY)
* Czech Republic (CZ)
* Denmark (DK)
 * Faroe Islands (FO)
 * Greenland (GL)
* *Djibouti* (DJ)
* Dominican Republic (DO)
* East Timor (TL)
* *Egypt* (EG)
* El Salvador (SV)
* *Equitorial Guinea* (GQ)
* Estonia (EE)
* Finland (FI)
 * Åland Islands (AX)
* France (FR)
 * French Guiana (GF)
 * French Polynesia (PF)
 * French Southern Territories (TF)
 * Guadelope (GP)
 * Martinique (MQ)
 * Mayotte (YT)
 * New Caledonia (NC)
 * Réunion (RE)
 * Saint Barhélemy (BL)
 * Saint Martin (French Part) (MF)
 * Saint-Pierre and Miquelon (PM)
 * Wallis and Futuna (WF)
* *Gabon* (GA)
* Georgia (GE)
* Germany (DE)
* Gibraltar (GI)
* Greece (GR)
* Guatemala (GT)
* *Guinea-Bissau* (GW)
* *Honduras* (HN)
* Hungary (HU)
* Iceland (IS)
* *IIBAN (Internet)* (AA)
* *Iran* (IR)
* Iraq (IQ)
* Ireland (IE)
* Israel (IL)
* Italy (IT)
* Jordan (JO)
* Kazakhstan (KZ)
* Kosovo (XK)
* Kuwait (KW)
* Latvia (LV)
* Lebanon (LB)
* Liechtenstein (LI)
* Lithuania (LT)
* Luxembourg (LU)
* Macedonia (MK)
* *Madagascar* (MG)
* *Mali* (ML)
* Malta (MT)
* Mauritania (MR)
* Mauritius (MU)
* Moldova (MD)
* Monaco (MC)
* Montenegro (ME)
* *Morocco* (MA)
* *Mozambique* (MZ)
* Netherlands (NL)
* *Nicaragua* (NI)
* *Niger* (NE)
* Norway (NO)
* Pakistan (PK)
* Palestine (PS)
* Poland (PL)
* Portugal (PT)
* Qatar (QA)
* Romania (RO)
* Saint Lucia (LC)
* San Marino (SM)
* São Tomé and Príncipe (ST)
* Saudi Arabia (SA)
* *Senegal* (SN)
* Serbia (RS)
* Seychelles (SC)
* Slovakia (SK)
* Slovenia (SI)
* Spain (ES)
* Sweden (SE)
* Switzerland (CH)
* *Togo* (TG)
* Tunisia (TN)
* Turkey (TR)
* *Ukraine* (UA)
* United Arab Emirates (AE)
* United Kingdom (GB)


Installation via composer
-------------------------

If you use [composer](https://getcomposer.org/) you can simply run `composer require globalcitizen/php-iban` to get going. Reportedly [![Daily Downloads](https://poser.pugx.org/globalcitizen/php-iban/d/daily)](https://packagist.org/packages/globalcitizen/php-iban) (and [![Monthly Downloads](https://poser.pugx.org/globalcitizen/php-iban/d/monthly)](https://packagist.org/packages/globalcitizen/php-iban)) were done via composer.

(If you don't yet have `composer` and wish to install it in an insecure fashion (not recommended, but convenient) you can run `curl -sS https://getcomposer.org/installer | php` or `wget -O- https://getcomposer.org/installer | php`)

Then just add the following to your `composer.json` file:

```js
// composer.json
{
    "require": {
        "globalcitizen/php-iban": "4.1.0"
    }
}
```

Then, you can install the new dependencies by running `composer`'s update command from the directory where your `composer.json` file is located:

```sh
# install
$ php composer.phar install
# update
$ php composer.phar update globalcitizen/php-iban

# or you can simply execute composer command if you set it to
# your PATH environment variable
$ composer install
$ composer update globalcitizen/php-iban
```

You can [see this library on Packagist](https://packagist.org/packages/globalcitizen/php-iban).


Installation via git
--------------------

For a regular install, use the `git clone` command:

```sh
# HTTP
$ git clone https://github.com/globalcitizen/php-iban.git
# SSH
$ git clone git@github.com:globalcitizen/php-iban.git
```


Installation via git submodule
------------------------------

Alternatively, to embed the `php-iban` library in your own `git`-managed repository at a specific revision number, such that it is possible to update the version in a predictable way while maintaining a larger system that depends upon its functionality:
```sh
# enter your project's git repo
$ cd my-existing-project-with-a-git-repo/
# select an appropriate place to create the php-iban subdir
$ cd lib/
# add php-iban as a submodule
$ git submodule add https://github.com/globalcitizen/php-iban.git
# commit new submodule
$ git commit -m 'Add php-iban submodule'
```

Then, when checking out `git` projects with submodules for the first time, normally you need to make a couple of extra steps:
```sh
# check out your project as normal
$ git clone git@your-server.com:your/project.git
# initialize submodules
$ git submodule init
# update submodules
$ git submodule update
```

To skip these steps, add the `--recursive` argument to `git clone` when checking out:
```sh
# check out your project, initialize and update all submodules
$ git clone --recursive git@your-server.com:your/project.git
```

If you later wish to your project to use a newer version of `php-iban`, run:
```sh
# fetch changes
$ git submodule update --remote php-iban
# commit
$ git commit -m 'Update php-iban submodule'
```


Manual installation
-------------------

1. Fetch the latest release from [our github releases page](https://github.com/globalcitizen/php-iban/releases) in either `zip` or `tar.gz` format.
2. Extract the library using your favourite archive utility, for example `unzip filename.zip` on Unix-like platforms.
3. Write your code to depend on the library based upon its relative location to your source code. For example if you wish to include `php-iban` from the parent directory's subdirectory `libraries/php-iban` you could use the following [require_once()](http://php.net/manual/en/function.require-once.php) statement:
```php
<?php
require_once(dirname(__FILE__) . '/../libraries/php-iban/php-iban.php');
# ... your code utilizing php-iban
?>
```


Comparison of PHP IBAN libraries
--------------------------------

The following table compares __php-iban__ to other PHP projects offering IBAN-related functionality, on the basis of general project information and the programming paradigms supported.

| Project                                                    | Lic. | Proc | OO  | Began  | Latest | Star | Watch | Fork | Installs | Home culture | Deps    |
| ---------------------------------------------------------- | ---- | ---- | --- | ------ | ------ | ---- | ----- | ---- | -------- | ------------ | ------- |
| __php-iban__                                               | LGPL | ✔    | ✔   | 2009   | 4.1.1  | 414  | 28    | 98   | ~3.5M*   | Global*      | *none*  |
| [Iban](https://github.com/jschaedl/Iban)                   | MIT  | ✘    | ✔   | 2013   | 1.3.0  | 50   | 9     | 19   | 178.39k  | German       | lots    |
| [IsoCodes](https://github.com/ronanguilloux/IsoCodes)      | GPL3 | ✘    | ✔   | 2012   | 2.1.1  | 466  | 22    | 54   | 145k     | French       | lots    |
| [SepaUtil's](https://github.com/AbcAeffchen/SepaUtilities) | GPL3 | ✘    | ✔   | 2014   | 1.2.3  | 8    | 4     | 3    | 25k      | German       | phpunit |
| [Symfony](https://github.com/symfony/symfony)              | MIT  | ✘    | ✔   | 2013   | 3.3.6  | 15k  | 1214  | 5.6k | 23M+     | French       | lots    |

Notes:
 * Original download records for __php-iban__ releases were hosted on Google Code and are now lost. Prior to establishing a release process on Github, we just expected that people would download the code... so we're really not sure how many installs exist, but this is a fair guess (now over 3M composer installs + all prior google code and Github installs).
 * __php-iban__ also powers:
    * [adm-gravity-iban](https://github.com/InternativeNL/adm-gravity-iban)
    * [Azzana consulting's XML Solver for ISO20022](http://www.azzana-consulting.com/xmlsolver/)
    * [basepa Payment Gateway for WooCommerce](https://github.com/besepa/woocommerce-besepa)
    * [org.civicoop.ibanaccounts extension](https://github.com/CiviCooP/org.civicoop.ibanaccounts) for [CiviCoop](http://www.civicoop.org/)
    * [commerce_sepa](https://github.com/StephanGeorg/commerce_sepa)
    * [contao-haste_plus](https://github.com/heimrichhannot/contao-haste_plus)
    * [Dolibarr ERP &amp; CRM](http://www.dolibarr.org/) ([website](https://github.com/Dolibarr/dolibarr/tree/develop/htdocs/includes/php-iban))
    * [fieldwork: Web forms for cool people](https://github.com/jmversteeg/fieldwork)
    * [IBAN Validator](https://www.drupal.org/project/iban_validator) for Drupal
    * [identity](https://github.com/mpijierro/identity) component for Laravel to check Spanish IDs
    * [lib-bankaccount](https://github.com/majestixx/lib-bankaccount) (conversion to/from legacy German account format)
    * [PHP SEPA XML](http://www.phpclasses.org/package/8179-PHP-Generate-XML-for-the-Single-Euro-Payments-Area.html) class ([github](https://github.com/dmitrirussu/php-sepa-xml-generator))
    * [Project60 SEPA direct debit](https://github.com/Project60/org.project60.sepa)
    * [SEPA Payment Plugin](https://github.com/subs-guru/sepa-payment-plugin) for [SubsGuru](http://subs.guru/)
    * [Silverstripe CMS module](https://github.com/denkfabrik-neueMedien/silverstripe-siteinfo)
    * [statement](https://github.com/hiwye/statement) 
    * [WooCommerce Germanized](http://hookr.io/plugins/woocommerce-germanized/)
    * [WooCommerce SEPA Payment Gateway](https://codecanyon.net/item/woocommerce-sepa-payment-gateway/7963419)
 * php-iban's author is an Australian born, Australia/New Zealand/German citizen based in mainland China, who has formerly also worked and banked in the US, UK, and many Asian countries.
 * The IsoCodes and SepaUtil's projects cover standards other than IBAN so their popularity should be considered in this light. (In essence, there is really only one directly competing library, Iban)

Now let's take a look at features.

|                                                               | +   | ISO | IANA | SEPA | ₶   | UO  | MT  | NC  | ₴   | CB  | H?  | Registry                                                               |
| ------------------------------------------------------------- | --- | --- | ---- | ---- | --- | --- | --- | --- | --- | --- | --- | ---------------------------------------------------------------------- |
| __php-iban__                                                  | ✔   | ✔   |  ✔   | ✔    | ✔   | ✔   | ✔   | ✔   | ✔   | ✔   | ✔   | 116: [full, error-corrected CSV](https://github.com/globalcitizen/php-iban/blob/master/registry.txt) with [open-source toolchain](https://github.com/globalcitizen/php-iban/blob/master/utils/convert-registry.php) and [documentation](https://github.com/globalcitizen/php-iban/blob/master/docs/COMEDY-OF-ERRORS) |
| [Iban](https://github.com/jschaedl/Iban)                      | ✔*  | ✘   |  ✘   | ✘    | ✘   | ✘   | ✘   | ✘   | ✘   | ✘   | ✘   | 54: [partial, hardcoded, dubious origin](https://github.com/jschaedl/Iban/blob/master/library/IBAN/Core/Constants.php#L44)   |
| [IsoCodes](https://github.com/ronanguilloux/IsoCodes)         | ✘   | ✘   |  ✘   | ✘    | ✘   | ✘   | ✘   | ✘   | ✘   | ✘   | ✘   | 66: [partial, hardcoded, dubious origin](https://github.com/ronanguilloux/IsoCodes/blob/master/src/IsoCodes/Iban.php#L25)    |
| [SepaUtil's](https://github.com/AbcAeffchen/SepaUtilities)    | ✘   | ✘   |  ✘   | ✘    | ✘   | ✘   | ✘   | ✘   | ✘   | ✘   | ✘   | 89: [partial, hardcoded, dubious origin](https://github.com/AbcAeffchen/SepaUtilities/blob/master/src/SepaUtilities.php#L89) |
| [Symfony](https://github.com/symfony/symfony)                 | ✘   | ✘   |  ✘   | ✘    | ✘   | ✘   | ✘   | ✘   | ✘   | ✘   | ✘   | 95: [partial, hardcoded](https://github.com/symfony/symfony/blob/09f92ba516b8840f2ee2dc630b75cbccfca5976b/src/Symfony/Component/Validator/Tests/Constraints/IbanValidatorTest.php), [dubious origin](https://github.com/symfony/symfony/blob/a4f3baae3758b0e72005353f624101f089e4302b/src/Symfony/Component/Validator/Constraints/IbanValidator.php)

Note:
 * __+__ refers to the capacity to create checksum-accurate potential IBANs programatically. It is the author's opinion that generation features without IIBAN support (ie. authority) are of dubious use, except in one-off migrations. (See also NC, below)
 * __ISO__ refers to the capacity to convert between IBAN country codes and ISO3166-1 alpha-2 country codes
 * __IANA__ refers to the capacity to convert between IBAN country codes and IANA country codes (eg. 'GB' to '.uk' and vice versa)
 * __SEPA__ refers to the ability to check whether a particular IBAN country is a member of the Single Euro Payments Area (SEPA)
 * __₶__ describes support for IIBAN, the open [proposal](http://www.ifex-project.org/) for decentralized financial endpoint generation by private parties, such as crypto-currency exchanges, whilst maintaining compatibility with the emerging IBAN system. This system has been adopted by major cryptocurrency exchanges such as [Kraken](https://www.kraken.com/).
 * __UO__ refers to support for unofficial countries whose IBAN formats have been [published as in informal use](http://www.nordea.com/en/our-services/cashmanagement/iban-validator-and-information/iban-countries/index.html) by major financial institutions, but are not official SWIFT-published registry entries.
 * __MT__ refers to mistranscription support: the capacity to automatically detect what the user probably meant when they make a transcription error on IBANs, such as those manually written or printed in confusing fonts, for instance writing 'L' instead of 'I' or '1', or vice versa.
 * __NC__ refers to national checksum support: the capacity to verify and, where appropriate, set and extract the national checksum portion of a BBAN, for countries that offered pre-IBAN national checksum algorithms.
 * __₴__ refers to support for querying the official national currency's ISO4217 code for an IBAN country
 * __CB__ refers to support for querying the name and URL of the central bank of an IBAN country
 * __H?__ refers to support for input and output for the human, space-laden or presentation variant of an IBAN, ie. `IBAN XXXX XXXX XXXX XXXX` instead of `XXXXXXXXXXXXXXXX` - a lot more reasonable.

In short, while composer users have apparently lept on rival libraries (particularly Iban), probably due to the time it took us to integrate a composer file, those libraries are often either full-fledged web frameworks or burdensome in dependencies, less mature, fail to hat-tip to the free software foundation, do not support the procedural programming paradigm (for when AbstractProductClassMakerFactories just won't cut it), use data from dubious sources, tend to use licenses that are incompatible with certain commercial uses, and are frankly short on features.

So, fearless user ... __choose php-iban__: the ethical, functional, forward-looking, low-hassle library of choice for IBAN and IIBAN processing. __Choose to win!__ ;)


Your Help Wanted
----------------

 * If you know the URL of __national IBAN, BBAN or national checksum documentation__ from official sources, please let us know at [issue #39](https://github.com/globalcitizen/php-iban/issues/39) and [issue #41](https://github.com/globalcitizen/php-iban/issues/41).
  * __Faroe Islands__ (FO) banks do not respond, neither does the Danish National Bank who referred me to them.
  * __Luxembourg__ (LU) does not seem to conform to any single checksum system. While some IBAN do validate with reasonably common systems, others don't or use others. The suggestion that Luxembourg has a national checksum system may in fact be incorrect. We need some clarification here, hopefully someone can dig up an official statement.
  * __Mauritania__ (MR) has a dual character checksum system but our example IBAN does not match MOD97-10 which would be the expected system. Previously the IBAN here was always fixed to '13' checksum digits, however as of registry v66 it is now dynamic, which suggests a changed or at least now nationally relaxed checksum system.

 * If you are willing to spend some time searching, we could do with some more test IBANs for most countries, especially smaller ones...

News: August 2022
-----------------

__[Version 4.1.1](https://github.com/globalcitizen/php-iban/releases/tag/v4.1.1)__ has been released.
 * Long-standing bug affecting Belgian pre-IBAN national checksum verification fixed - thanks to [Arne Peirs](https://github.com/Olympic1) for a [very well documented pull request](https://github.com/globalcitizen/php-iban/pull/119).

News: July 2021
---------------

__[Version 4.1.0](https://github.com/globalcitizen/php-iban/releases/tag/v4.1.0)__ has been released.
 * New feature to check for EU memberships - thanks to [@julianpollmann](https://github.com/julianpollman)

News: August 2020
-----------------
__[Version 4.0.0](https://github.com/globalcitizen/php-iban/releases/tag/v4.0.0)__ has been released.
 * Major version upgrade to certainly fix missing dot in prior release version string, thus avoiding composer hassles. (See [#108](https://github.com/globalcitizen/php-iban/issues/108)). I am really beginning to hate composer.

__[Version 3.0.3](https://github.com/globalcitizen/php-iban/releases/tag/v3.0.3)__ has been released.
 * Official support for php-7.4

__[Version 3.0.2](https://github.com/globalcitizen/php-iban/releases/tag/v3.0.2)__ has been released.
 * BBAN length fixes for Bahrain and Quatar (thanks to @jledrogo)

News: July 2020
---------------

__[Version 3.0.0](https://github.com/globalcitizen/php-iban/releases/tag/v3.0.0)__ has been released.
 * Same as previous but bump version to fix issues with the addition of namespaces. (See [#104](https://github.com/globalcitizen/php-iban/issues/104))
 * Versions 2.8.x are being removed from the releases.
 * Hopefully this should fix things for users upgrading from earlier versions via composer.

__[Version 2.8.2](https://github.com/globalcitizen/php-iban/releases/tag/v2.8.1)__ has been released.
 * Same as previous but officially drop php-5.2 support due to lack of namespacing.

__[Version 2.8.1](https://github.com/globalcitizen/php-iban/releases/tag/v2.8.1)__ has been released.
 * `TL` BBAN format regex removed extraneous spaces (did not affect IBAN validation). (Thanks to @DanyCorbineauBappli)

News: June 2020
---------------
__[Version 2.8.0](https://github.com/globalcitizen/php-iban/releases/tag/v2.8.0)__ has been released.
 * Object oriented class is now namespaced.

News: May 2020
--------------
__[Version 2.7.5](https://github.com/globalcitizen/php-iban/releases/tag/v2.7.5)__ has been released.
 * Corrections from newer IBAN registry releases
   * Updated Egypt example IBAN and registry entry (disabled French national checksum scheme as this no longer works with the example IBAN provided. Users with insight please check, there are no examples visible online!)
   * Corrections to Polish BBAN length (now fixed, previously spuriously specified as variable)
   * Corrections to Seychelles BBAN and IBAN structure

__[Version 2.7.4](https://github.com/globalcitizen/php-iban/releases/tag/v2.7.4)__ has been released.
 * New function `iban_to_obfsucated_format()` or `ObfuscatedFormat()` to obfuscate IBAN for specific output scenarios (such as relative identification)
   * Thanks to @jaysee for feature request #99

News: November 2019
------------------
__[Version 2.7.3](https://github.com/globalcitizen/php-iban/releases/tag/v2.7.3)__ has been released.
 * Load registry only when used. This creates slightly more overhead in real world use, but nominally substantially reduces load times in the edge case event that you include the library but only want to use a function that does not require the IBAN registry to be loaded.
   * Thanks to @manitu-opensource

__[Version 2.7.2](https://github.com/globalcitizen/php-iban/releases/tag/v2.7.2)__ has been released.
 * Fix composer file to add license.
   * Thanks to @SunMar

News: October 2019
------------------
__[Version 2.7.1](https://github.com/globalcitizen/php-iban/releases/tag/v2.7.1)__ has been released.
 * Update erroneous bank ID stop offset for Costa Rica.
   * Thanks to @thinkpozzitive
 * Minor syntax updates
   * Thanks to @bwurst
 * Add quite a number of Costa Rica example IBANs for confidence in testing.

News: July 2019
---------------

__[Version 2.7.0](https://github.com/globalcitizen/php-iban/releases/tag/v2.7.0)__ has been released.
 * Fixed erroneous Liechtenstein BBAN length.
 * Update National Bank of Greece name/website.

News: August 2018
-----------------

__[Version 2.6.9](https://github.com/globalcitizen/php-iban/releases/tag/v2.6.9)__ has been released.
 * Added national checksum implementation for San Marino (`SM`)
 * Thanks to @francescozanoni 

__[Version 2.6.8](https://github.com/globalcitizen/php-iban/releases/tag/v2.6.8)__ has been released.
 * Added national checksum implementation for Italy (`IT`)
 * Thanks to @francescozanoni 

News: June 2018
---------------

__[Version 2.6.7](https://github.com/globalcitizen/php-iban/releases/tag/v2.6.7)__ has been released.
 * Added national checksum implementation for Slovakia (`SK`)
 * Thanks to @ostrolucky

News: June 2018
---------------

__[Version 2.6.6](https://github.com/globalcitizen/php-iban/releases/tag/v2.6.6)__ has been released.
 * Fixed generation of voluminous errors in environments without `ini_set` enabled
 * Thanks to @agil-NUBBA

News: March 2018
----------------

__[Version 2.6.5](https://github.com/globalcitizen/php-iban/releases/tag/v2.6.5)__ has been released.
 * Fixed spurious warning when `gmp` extension was enabled
 * Thanks to @marcovo

__[Version 2.6.4](https://github.com/globalcitizen/php-iban/releases/tag/v2.6.4)__ has been released.
 * Remove spurious dependency on `bcmath` extension
 * Minor documentation updates

__[Version 2.6.3](https://github.com/globalcitizen/php-iban/releases/tag/v2.6.3)__ has been released.
 * Upgrade travis environment as old one broken
 * Fix test execution under new Travis environment
 * Re-addition of HHVM test environments
 * Addition of PHP-5.2 test environment
 * A few new test IBANs

__[Version 2.6.2](https://github.com/globalcitizen/php-iban/releases/tag/v2.6.2)__ has been released.
 * Update Croatia SEPA status
 * Thanks to @Pappshadow

News: August 2017
-----------------

__[Version 2.6.1](https://github.com/globalcitizen/php-iban/releases/tag/v2.6.1)__ has been released.
 * Fixed missing registry data.
 * Thanks to @monojp

__[Version 2.6.0](https://github.com/globalcitizen/php-iban/releases/tag/v2.6.0)__ has been released.
 * World = conquered.
   * We now have well over 100 supported countries.
   * According to packagist, we are now the most popular IBAN-related project for PHP ... and quite possibly the internet!
 * Addition of official countries
   * Belarus (BY)
   * El Salvador (SV)
   * Iraq (IQ)
 * Addition of unofficial countries
   * Central African Republic (CF)
   * Chad (TD)
   * Comoros (KM)
   * Congo (CG)
   * Djibouti (DJ)
   * Egypt (EG)
   * Equitorial Guinea (GQ)
   * Gabon (GA)
   * Guinea-Bissau (GW)
   * Honduras (HN)
   * Morocco (MA)
   * Nicaragua (NI)
   * Niger (NE)
   * Togo (TG)
 * Additional example Iran (IR) IBANs.
 * As HHVM is no longer supported by Travis we have dropped it from our automated testing, although php-iban should continue to work fine on HHVM.
 * Minor documentation updates


News: October 2016
------------------

__[Version 2.5.9](https://github.com/globalcitizen/php-iban/releases/tag/v2.5.9)__ has been released.
 * Bring us up to date with IBAN registry release #69 from #66
   * Release #67: fixes broken Costa Rica format and disables Croatia SEPA status 
   * Release #69: adds Sao Tome and Principe bank + branch offsets


News: August 2016
-----------------

__[Version 2.5.8](https://github.com/globalcitizen/php-iban/releases/tag/v2.5.8)__ has been released.
 * Fix [issue #52](https://github.com/globalcitizen/php-iban/issues/52) (thanks to [@simeucci](https://github.com/simeucci) for reporting), apologies for the delay!
 * Minor documentation updates


News: June 2016
---------------

__[Version 2.5.7](https://github.com/globalcitizen/php-iban/releases/tag/v2.5.7)__ has been released.
 * Minor changes missed in latest edition (May 2016, version 66) registry release
  * New Seychelles (SC) example IBAN
  * Unfix Mauritania (MR) checksum digits (no functional change)
 * Minor documentation updates


News: May 2016
--------------

__[Version 2.5.6](https://github.com/globalcitizen/php-iban/releases/tag/v2.5.6)__ has been released.
 * Update to conform with latest edition (May 2016, version 66) registry release
  * Many of the corrections we had apparently already resolved on initial data import
  * Moldova (MD): Split 20!c to 2!c18!c
  * Seychelles (SC): Fix IBAN format (SWIFT markup)
  * Tunisia (TN): Remove hardcoded 59 as IBAN checksum (following SWIFT; though inefficient)
 * Minor documentation updates
  * Update stats/figures for php-iban installs/stars/etc.
  * Add new 'powered by'


News: April 2016
----------------

__[Version 2.5.5](https://github.com/globalcitizen/php-iban/releases/tag/v2.5.5)__ has been released.
 * Update to conform with latest edition (April 2016, version 65) registry release
  * Corrected account format for Seychelles (SC) to permit alphabetic characters (formerly numeric only)


News: March 2016
----------------

__[Version 2.5.4](https://github.com/globalcitizen/php-iban/releases/tag/v2.5.4)__ has been released.
 * Update to conform with latest edition (March 2016, version 64) registry release
  * Added Seychelles (SC)
  * The three other changes apparently corrected registry errors we had already caught during record ingestion and testing

__[Version 2.5.3](https://github.com/globalcitizen/php-iban/releases/tag/v2.5.3)__ has been released.
 * Added [Falsehoods Programmers Believe About IBANs](https://github.com/globalcitizen/php-iban/blob/master/docs/FALSEHOODS.md), inspired by...
  * [Falsehoods Programmers Believe About Phone Numbers](https://github.com/googlei18n/libphonenumber/blob/master/FALSEHOODS.md)
  * [Falsehoods Programmers Believe About Names](http://www.kalzumeus.com/2010/06/17/falsehoods-programmers-believe-about-names/)
  * [Falsehoods Programmers Believe About Time](http://infiniteundo.com/post/25326999628/falsehoods-programmers-believe-about-time)
  * [Falsehoods Programmers Believe About Geography](http://wiesmann.codiferes.net/wordpress/?p=15187)
  * [Falsehoods Programmers Believe About Addresses](https://www.mjt.me.uk/posts/falsehoods-programmers-believe-about-addresses/)
 * Additional example IBANs
  * Azerbaijan (AZ)
  * Austria (AT)
  * Angola (AO)
  * San Marino (SM)
 * Various minor changes


News: February 2016
-------------------

__[Version 2.5.2](https://github.com/globalcitizen/php-iban/releases/tag/v2.5.2)__ has been released.
 * Miscellaneous test library updated to validate example IBANs collection.

__[Version 2.5.1](https://github.com/globalcitizen/php-iban/releases/tag/v2.5.1)__ has been released.
 * The 'Narodna banka Srbije' (`908`) bank in Serbia (RS) appears to have multiple live IBANs with broken national checksums, so we ignore all national checksums on accounts from that bank.

__[Version 2.5.0](https://github.com/globalcitizen/php-iban/releases/tag/v2.5.0)__ has been released.
 * All users are encouraged to upgrade; this release is considered long term stable.
 * The following national checksum schemes added in the 2.4.x series are now included and well validated, while invalid assumptions have been removed:
  * Belgium (BE)
  * Spain (ES)
  * Monaco (MC)
  * France (FR)
  * Norway (NO)
  * Montenegro (ME)
  * Macedonia (MK)
  * Netherlands (NL) - including exception for `INGB` (ING Bank) who have dropped the original checksum
  * Portugal (PT)
  * Serbia (RS)
  * Slovenia (SI) - including exception for `01` (Bank of Slovenia) who do not honour checksums
  * Timor Lest (TL)
 * In addition, a library of test IBANs is being maintained under `utils/example-ibans` which has a good number of entries for a good number of countries already. This should simplify future research.
 * Documented [ideas for the enhancement of the mistranscription error correction suggestion function](https://github.com/globalcitizen/php-iban/commit/045f39b33468e04ff4a64a3bd8cba92611149935#diff-61178a0267b9e23c2b5c19c0f4671a22).

__[Version 2.4.20](https://github.com/globalcitizen/php-iban/releases/tag/v2.4.20)__ has been released.
 * Another bugfix release, based on further real world test IBANs from certain countries:
  * Remove Bosnia (BA) national checksum support

__[Version 2.4.19](https://github.com/globalcitizen/php-iban/releases/tag/v2.4.19)__ has been released.
 * Another bugfix release, based on further real world test IBANs from certain countries:
  * Remove Finland (FI) national checksum support

__[Version 2.4.18](https://github.com/globalcitizen/php-iban/releases/tag/v2.4.18)__ has been released.
 * Another bugfix release, based on further real world test IBANs from certain countries:
  * Remove Poland (PL) national checksum support

__[Version 2.4.17](https://github.com/globalcitizen/php-iban/releases/tag/v2.4.17)__ has been released.
 * Bank of Slovenia (bank code `01` under Slovenia (SI)) does not implement the national checksum scheme, as a special case. An exception has been added to the Slovenia national checksum implementation.

__[Version 2.4.16](https://github.com/globalcitizen/php-iban/releases/tag/v2.4.16)__ has been released.
 * Another bugfix release, based on further real world test IBANs from certain countries:
  * Remove Sweden (SE) national checksum support
 * I am now instituting a new rule that if national checksum support has not been tested on 10+ real world IBANs, preferably 20+ across a range of institutions, then it does not get committed. This means that small countries will be impossible to add until research is done beyond web-browsing.

__[Version 2.4.15](https://github.com/globalcitizen/php-iban/releases/tag/v2.4.15)__ has been released.
 * The Netherlands (NL) bank 'INGB' no longer uses the national checksum scheme, and has been excepted from the check. This marks our first bank-specific checksum feature.

__[Version 2.4.14](https://github.com/globalcitizen/php-iban/releases/tag/v2.4.14)__ has been released.
 * Another bugfix release, based on further real world test IBANs from certain countries:
  * Remove Estonia (EE) national checksum support
  * Remove Hungary (HU) national checksum support

__[Version 2.4.13](https://github.com/globalcitizen/php-iban/releases/tag/v2.4.13)__ has been released.
 * This release is mostly about bugfixes, after spending a lot of time gathering IBANs online and using them for further testing.
 * Tunisia (TN) national checksum support has been removed, after additional testing with IBAN gathered from the internet it was found not to be correct. Perils of reverse-engineering!
 * A couple of other bugfixes:
  * The function `iban_mistranscription_suggestions()` now behaves correctly when passed loosely formatted IBAN-like strings
  * The checksum algorithm `_verhoeff()` which supports certain national checksum implementations now behaves correctly when passed invalid input

__[Version 2.4.12](https://github.com/globalcitizen/php-iban/releases/tag/v2.4.12)__ has been released.
 * Tunisia (TN) national checksum support has been added.

__[Version 2.4.11](https://github.com/globalcitizen/php-iban/releases/tag/v2.4.11)__ has been released.
 * It is now possible to query the central bank name and URL for each country, from new registry fields `central_bank_url` and `central_bank_name`, for example:
  * The central bank for New Caledonia (NC) is the 'Overseas Issuing Institute (Institut d'émission d'Outre-Mer)' and their URL is http://www.ieom.fr/
  * The central bank for the British Virgin Islands (BV) is 'The British Virgin Islands Financial Services Commission' and their URL is http://www.bvifsc.vg/
  * There is no central bank for the IIBAN (Internet) (AA).

__[Version 2.4.10](https://github.com/globalcitizen/php-iban/releases/tag/v2.4.10)__ has been released.
 * New registry field `currency_iso4217` stores the official currency of the country in ISO4217 alpha code format, for example:
  * The currency of Iceland (IS) is ISD
  * The currency of Saint-Pierre and Miquelon (PM) is EUR
  * The currency of Wallis and Futuna (WF) is XPF

__[Version 2.4.9](https://github.com/globalcitizen/php-iban/releases/tag/v2.4.9)__ has been released.
 * New registry field `parent_registrar` stores the parent registrar IBAN country of an IBAN country, for example:
  * Åland Islands (AX) parent registrar is Finland (FI)
  * Faroe Islands (FO) parent registrar is Denmark (DK)
  * New Caledonia (NC) parent registrar is France (FR)

__[Version 2.4.8](https://github.com/globalcitizen/php-iban/releases/tag/v2.4.8)__ has been released.
 * Monaco (MC) national checksum support has been added.

__[Version 2.4.7](https://github.com/globalcitizen/php-iban/releases/tag/v2.4.7)__ has been released.
 * Netherlands (NL) national checksum support has been added.

__[Version 2.4.6](https://github.com/globalcitizen/php-iban/releases/tag/v2.4.6)__ has been released.
 * Poland (PL) national checksum support has been added.

__[Version 2.4.5](https://github.com/globalcitizen/php-iban/releases/tag/v2.4.5)__ has been released.
 * Estonia (EE) national checksum support has been added.
 * Finland (FI) national checksum support has been added.
 * Macedonia (MK) national checksum support has been added.
 * Montenegro (ME) national checksum support has been added.
 * Norway (NO) national checksum support has been added.
 * Serbia (RS) national checksum support has been added.
 * Slovenia (SI) national checksum support has been added.
 * Sweden (SE) national checksum support has been added.

__[Version 2.4.4](https://github.com/globalcitizen/php-iban/releases/tag/v2.4.4)__ has been released.
 * Portugal (PT) national checksum support has been added.

__[Version 2.4.3](https://github.com/globalcitizen/php-iban/releases/tag/v2.4.3)__ has been released.
 * Hungary (HU) national checksum support has been added.

__[Version 2.4.2](https://github.com/globalcitizen/php-iban/releases/tag/v2.4.2)__ has been released.
 * Albania (AL) national checksum support has been added.
 * Timor-Leste (TL) national checksum support has been added.

__[Version 2.4.1](https://github.com/globalcitizen/php-iban/releases/tag/v2.4.1)__ has been released.
 * Bosnia (BA) national checksum support has been added.

__[Version 2.4.0](https://github.com/globalcitizen/php-iban/releases/tag/v2.4.0)__ has been released.
 * It is now possible to determine, verify and set the correct national checksums for some countries that offered a pre-IBAN national checksum algorithm via the new functions `iban_{set|find|verify}_nationalchecksum()` and their OO-wrapper equivalents. Presently Belgium (BE), France (FR) and Spain (ES) are supported. If you would like to see your country supported, please see [issue #39](https://github.com/globalcitizen/php-iban/issues/39) and [issue #41](https://github.com/globalcitizen/php-iban/issues/41).


News: January 2016
------------------

__[Version 2.3.1](https://github.com/globalcitizen/php-iban/releases/tag/v2.3.1)__ has been released.
 * Fix paste error in Bosnia IANA code
 * Additional tests for new country functions

__[Version 2.3.0](https://github.com/globalcitizen/php-iban/releases/tag/v2.3.0)__ has been released.
 * All IBAN country records can now be cross-referenced with their corresponding [IANA](https://en.wikipedia.org/wiki/List_of_Internet_top-level_domains#Country_code_top-level_domains) and [ISO3166-1 alpha-2](https://en.wikipedia.org/wiki/ISO_3166-1_alpha-2#Current_codes) codes, if available

__[Version 2.2.0](https://github.com/globalcitizen/php-iban/releases/tag/v2.2.0)__ has been released.
 * Fully up to date with SEPA membership list. (Added new member for 2016, Andorra)
 * Fully up to date with latest SWIFT IBAN registry PDF.
 * Many fixes and new features since 2.1.0
 * All users are encouraged to ugprade.

__[Version 2.1.9](https://github.com/globalcitizen/php-iban/releases/tag/v2.1.9)__ has been released.
 * Example field updates attempting to include what is possible from SWIFT IBAN registry PDF version #63. There persist [significant issues with this release process](https://github.com/globalcitizen/php-iban/blob/master/docs/COMEDY-OF-ERRORS).

__[Version 2.1.8](https://github.com/globalcitizen/php-iban/releases/tag/v2.1.8)__ has been released.
 * National BBAN checksum offset data for Belgium added.

__[Version 2.1.7](https://github.com/globalcitizen/php-iban/releases/tag/v2.1.7)__ has been released.
 * National BBAN checksum offset data added to registry. This can be queried via the new functions `iban_get_nationalchecksum_part()`, `iban_country_get_nationalchecksum_start_offset()` and `iban_country_get_nationalchecksum_stop_offset()` and their OO-wrapper equivalents `$myIban->NationalChecksum()`, `$myCountry->NationalChecksumStartOffset()` and `$mycountry->NationalChecksumStopOffset()`. Test and documentation updated. If you know anything about national checksum algorithms, please lend a hand at [issue #39](https://github.com/globalcitizen/php-iban/issues/39).

__[Version 2.1.6](https://github.com/globalcitizen/php-iban/releases/tag/v2.1.6)__ has been released.
 * OO wrapper and documentation updated for new strict `machine_format_only` validation.

__[Version 2.1.5](https://github.com/globalcitizen/php-iban/releases/tag/v2.1.5)__ has been released.
 * Additional strict `machine_format_only` mode for `verify_iban()` to close [issue #22](https://github.com/globalcitizen/php-iban/issues/22).

__[Version 2.1.4](https://github.com/globalcitizen/php-iban/releases/tag/v2.1.4)__ has been released.
 * Simplified a function using a php4+ builtin.

__[Version 2.1.3](https://github.com/globalcitizen/php-iban/releases/tag/v2.1.3)__ has been released.
 * Behavior of `iban_to_human_format()` has been fixed when passed input already containing spaces.
 * OO-based tests are now executed following successful procedural tests.
 * An additional test library for testing edge-case behavior in general functions is now executed following the main tests.

__[Version 2.1.2](https://github.com/globalcitizen/php-iban/releases/tag/v2.1.2)__ has been released. All known unofficial IBAN country codes are now integrated. As well as minor documentation updates and a shortening of the reported name of Kosovo, this version adds:
 * Ivory Coast (CI)
 * Madagascar (MG)
 * Mali (ML)
 * Mozambique (MZ)
 * Senegal (SN)
 * Ukraine (UA)

__[Version 2.1.1](https://github.com/globalcitizen/php-iban/releases/tag/v2.1.1)__ has been released. Currently unofficial IBAN country codes are being integrated, and the process remains ongoing. This version adds:
 * Burkina Faso (BF)
 * Burundi (BI)
 * Cameroon (CM)
 * Cape Verde (CV)
 * Iran (IR) 

__[Version 2.1.0](https://github.com/globalcitizen/php-iban/releases/tag/v2.1.0)__ has been released.  
Currently unofficial IBAN country codes are being integrated, and the process remains ongoing.  A new flag has been created to check whether a country is an official, SWIFT-issued record or not. The following new countries have therefore been added.
 * Algeria (DZ)
 * Angola (AO)
 * Benin (BJ)

Note also that the IIBAN (AA) record has been marked unofficial, and features listed in `docs/TODO` have been migrated to Github issues and that file deleted.

__[Version 2.0.1](https://github.com/globalcitizen/php-iban/releases/tag/v2.0.1)__ has been released. This is to celebrate real testing, composer support, as well as finally catching up with changes. This version should be up to date with all registry changes to the present, including changes or additions to the countries:
 * IIBAN (AA)
 * Brazil (BR)
 * Costa Rica (CR)
 * Kazakhstan (KZ)
 * Kosovo (XK)
 * Kuwait (KW)
 * Saint Barthelemy (BL)
 * Saint Lucia (LC) 
 * Saint Martin (French Part) (MF)
 * Sao Tome and Principe (ST)
 * Timor Leste (TL)
 * Turkey (TR)

__[Version 1.6.0](https://github.com/globalcitizen/php-iban/releases/tag/v1.6.0)__ has been released. This version features more registry corrections (newly added territories with faulty data, bad checksums in sample IBANs, etc.) as well as enhanced testing routines, extended documentation, and corrected documentation. All users are advised to upgrade. We now have automated test script execution with Travis CI, to provide additional robustness for all committed code. This took longer than expected as unfortunately I picked the exact time Travis broke their build logs - https://www.traviscistatus.com/incidents/fcllblkclgmb - to see what all the fuss was about... proving again that cloud computing is just *great* for breaking things unexpectedly. Because they want to hide things, there was literally no debug output whatsoever, and I was led to believe this was my fault. Fellow programmers, behold: it is the dawning of the age of the mystical fail.

__[Version 1.5.0](https://github.com/globalcitizen/php-iban/releases/tag/v1.5.0)__ has been released. There are no code changes, but we now have http://packagist.org/ integration, hopefully this triggers it to start working. If you use packagist, you can now add the library to your project by just running `composer require globalcitizen/php-iban` (thanks to @acoulton for pointing the way)

__[Version 1.4.9](https://github.com/globalcitizen/php-iban/releases/tag/v1.4.9)__ has been released using the new Github-based release process. Hopefully this provides a solid anchor point for those bundling the library with other software. We also have a contributed composer metadata file to ease integration.
New IBAN registry URLs integrated.
Removed old SVN tag/trunk structure.

News: July 2015
---------------
Corrected SWIFT URL to IBAN page.
Emphasized mistranscription error support.

News: March 2015
----------------
Finally, google has killed `code.google.com` and we have migrated to Github! Once the old `trunk`/`tag` structure (lingering from `svn`) is cleaned up and this document translated from the old wiki format to markdown, a new version will be issued.

News: June 2014
---------------

__Version 1.4.6__ has been released:
 * Fixes for Jordan and Qatar. Turns out both of them have broken TXT registry entries, PDF entries differ and the PDF is the one to go for (familiar story).
 * Some further improvements.

Unfortunately, Google now requires `code.google.com` projects to use Google Drive. I tried to use Google Drive (sign up for a new account, jump through email hoops, get treated as a robot, learn stupid new touchy-feely-friendly interface, get meaningless error messages like 'Your sharing limit has been exceeded' (with 2x290KB files on a new account I was told to create) and lost patience entirely.

So for the moment, you'll just have to download using `git`, instead. I will migrate `php-iban` to Github shortly. Google really is a pain in the ass recently, what with all of this Google+ and Google Drive junk, ruining Picasa, ruining Sketchup by lack of attention, etc. What are they thinking?

News: March 2014
----------------
__Version 1.4.5__ has been released:
 * Addition of Jordan and Qatar
 * Minor changes to documentation and support scripts.

__Version 1.4.4__ has been released:
 * Fix SEPA status of Croatia (HR)
 * Subsqeuent SEPA status audit based upon https://en.wikipedia.org/wiki/Single_Euro_Payments_Area turned up some other status issues (this information is not contained within the official IBAN registry)
    * Faroe Islands, Greenland, San Marino status fixed. Everything else apparently hunky dory.

*The project source code repository has switched from `svn` (ugh) to `git` (yay!)*.
 * This should make future changes less painful.


News: September 2013
--------------------
__Version 1.4.3__ has been released:
 * Add Aland Islands (AX), part of Finland (FI) that is only documented in the SEPA status field of Finland and does not have its own entry or mention elsewhere in the IBAN registry document.
    * Consider but do not add either of the somewhat similar Canary Islands (CI) or Ceuta/Melilla (EA) - both minor territories of Spain (ES) - due to lack of any evidence of usage.
 * Fix SEPA status for Spain (ES), Finland (FI), Porgual (PT) due to registry values being mixed with free text.
    * Document this and further issues with the official IBAN registry document, both as documentation in `docs/COMEDY-OF-ERRORS` and inline within the registry converter.
 * Update human country name of Palestine to better mirror current registry document ("State of" is dropped as is the reigning style, so simply "Palestine" is presented)
 * Updating an outstanding last modified date within the registry from the previous release

News: August 2013
-----------------
__Version 1.4.2__ has been released:
 * Resolve issue #19: incorrect SEPA status of France/French territories due to a parser bug. (Thanks to the reporter)

__Version 1.4.1__ has been released:
 * Requests
    * Attempts to intelligently calculate the 'account' portion of a BBAN based upon the (non-)presence of a branch ID and bank ID portion, by request (for Germany/Austria. Previously this was requested for the Netherlands, however this solution should fix results for everyone!)
    * Add 'IIBAN' prefix removal support to machine format conversion function
    * Add _gmp_ disable flag (`$__disable_iiban_gmp_extension=true;`)
 * Silence warnings on some PHP engine configurations
 * Update Brazil record (minor)
 * No longer redistribute IBAN registry in .txt format
 * Improve inline documentation

News: June 2013
---------------
__Version 1.4.1__ is still being prepared, squashing some bugs and updating the registry ... meanwhile, it has come to my attention that we have been featured in the Code Candy blog!  http://www.codecandies.com/2012/05/30/no-exceptions/ Hooray for the German sense of humour! Hahah.

News: March 2013
----------------
__Version 1.4.0__ has been released:
 * Resolves an issue reported affecting the last few versions when attempting to generate a correct checksum for a checksum-invalid IBAN.
 * Adds `VERSION` file, to include hard version information in source tree, by request.

News: February 2013
-------------------
__Version 1.3.9__ has been released:
 * Resolves issue reported in 1.3.7 re-enables the more efficient PHP _gmp_ library based checksum code (thanks to rpkamp)

__Version 1.3.8__ has been released:
 * An error in checksum processing for some IBANs using the new _gmp_ library based MOD97 routine (_only affects users with php-iban 1.3.7 and the PHP _gmp_ library enabled_) has been reported. As an immediate workaround 1.3.8 is being released with the following changes:
 ** Code from 1.3.6
 ** Registry from 1.3.7

__Version 1.3.7__ has been released:
 * Added Brazil
 * Added two new French overseas territories
 * Reduced 'Moldova' to normalized short-form name
 * Large CPU efficiency improvement in IBAN validation routine (16x if PHP _gmp_ extension is installed, 5x otherwise. Special thanks to algorithmic contributor Chris and to engineers everywhere upholding the Germanic tradition of precision and efficiency! Alas, I am but part-German, for shame...)
 * Minor internal/tool updates
 * Some comedy of errors additions

News: November 2012
-------------------

__Version 1.3.6__ has been released:
 * Update IIBAN format for latest IETF draft.

News: October 2012
------------------

__Version 1.3.5__ has been released:
 * Correct lack of support for lower case alphabetic characters (ie. non ECBS-compliant) in human to machine format conversion function.

__Version 1.3.4__ has been released:
 * Add reference to the latest ECBS recommendations and include them in documentation.

__Version 1.3.3__ has been released:
 * Very minor efficiency improvement.

News: September 2012
--------------------

__Version 1.3.2__ has been released:
 * Registry updates
   * Added Palestinian Territories
   * Moldova fixed its format
   * Finland fixed its bank identifier location
   * Saudi Arabia - remove spurious trailing space in example

News: June 2012
---------------

__Version 1.3.1__ has been released:
 * New countries added
   * Azerbaijan (AZ)
   * Costa Rica (CR)
   * Guatemala (GT)
   * Moldova (MD)
   * Pakistan (PK)
   * British Virgin Islands (VG)
 * Miscellaneous updates
   * Normalize/simplify examples (FI,PT,SA)
   * Normalize/simplify human country name (BH,LI,MK)
   * Documentation updates

News: December 2011
-------------------
__Version 1.3.0__ has been released. This release adds mistranscription error suggestion support.

__Version 1.2.0__ has been released. This release adds Internet International Bank Account Number (IIBAN) support, as per the current IIBAN Internet Draft at http://tools.ietf.org/html/draft-iiban-01

News: September 2011
--------------------
__Version 1.1.2__ has been released. This adds long open tags to the main library file in order to simplify deployment on many default PHP installations.

News: August 2011
-----------------
__Version 1.1.1__ has been released. This fixes a typo in a function call in the new OO wrapper. Non OO users do not need to upgrade.

News: July 2011
---------------
__Version 1.1.0__ has been released. This version adds an object oriented wrapper library and related updates to documentation and test scripts. It is not critical for existing users to upgrade.

__Version 1.0.0__ has been released. This version includes the following changes:
 * *Support for the SEPA flag* ("Is this country a member of the Single Euro Payments Area?"), both in the registry and with a new function `iban_country_is_sepa($iban_country)`
 * *Placeholder support for converting machine format IBAN to human format* (simply adds a space every four characters) with the function `iban_to_human_format($iban)`
 * *Fixed a series of domestic example issues* in the registry file that had been imported from SWIFT's own broken IBAN registry
 * *Normalised example fields* in the registry to better facilitate use in automated contexts (Austria, Germany, etc.)
 * *Updated test code*
 * *Added a significant amount of new documentation*
 * *Reorganised file layout•
 * *Moved to _x.y.z_ format versioning and use of subversion 'tags'* in conjunction with the 1.0.0 release.

---

Earlier in the month... *Small maintenance release*, not critical.
 * The _split()_ function has been replaced with _explode()_ to prevent warnings (or error on _very_ new PHP engines)
 * Resolved an issue on PHP environments configured to display warnings would display a warning when an IBAN input to be validated did not include a prefix that was a valid IBAN country code. (Nobody should be running production PHP environments with such warnings enabled, anyway!) 

News: June 2011
---------------
 * We are now well over 1000 downloads: not bad considering how specific this project is!
 * A *new version* has been released that fixes many important changes to the official registry, plus adds some new features.
   * *Add New French Territories* (GF,GP,MQ,RE)
     Older versions of the specification did not include the GF,GP,MQ,RE French territories, only the PF,TF,YT,NC,PM,WF French territories. The new territories have now been added to the database.
   * *Add New Countries*
     We welcome Bahrain (BH), Dominican Republic (DO), Khazakstan (KZ), United Arab Emirates (AE) to the database.
   * *Format/example updates*
     There have apparently been some minor format/example changes, these have been rolled in to existing countries.
   * *Inclusion of altered IBAN_Registry.txt*
     Errors and omissions have been found within the official IBAN_Registry.txt file, namely the exclusion of Khazakstan (KZ) and only partial information on Kuwait (KW), and errors in both of these countries' PDF specifications. This is SWIFT's fault: shame on them! I suspect they have changed staff recently. Anyway, a version of IBAN_Registry.txt with these problems solved is now distributed along with php-iban.
   * *Fix for Tunisia*
     Strangely I visited Tunisia during the revolution in January this year. Sorry to the Tunisian people for getting their IBAN format wrong! TN59 + 20 digits is the correct format. This is now included in the new registry file.
   * *Fix for Albania*
     The SWIFT format information was updated for Albania. (Did not affect validation, since this uses regular expressions which were already correct)
   * *Additional and revised documentation*
     Further documentation has been added to the project.
   * *Automated IBAN_Registry.txt fix/conversion tool*
     A new _convert-registry_ tool has been added to the project that attempts to automatically normalise/fix problems with the official SWIFT .txt specification as much as possible. Note that this is not enough to get a good registry.txt file (the internal format used by php-iban) as SWIFT's .txt release excludes entire countries in the PDF specification. In addition, there are some errors in the PDF specification that need to be manually resolved at present. These can be seen resolved in the _IBAN_Registry.txt_ file.

News: December 2009
-------------------

*We now have a http://groups.google.com/group/php-iban-users mailing list.  Feel free to post your feedback, queries or suggestions - we'd love to know how you are using the library.  To date, the project has reached over 400 downloads and still going strong, with more than one new user per day - a pretty good showing for a specialised library!

*__version 12__ has been released.  The registry file has been improved, partly as a result of user reports and partly as a result of issues uncovered while performing automated tests against version 11.

 * *Corrected header row*
   Two columns were not represented in the title (`bban_length` and `iban_length`).  They have now been added.

 * *Fixes to registry entries for French Territories* (PF,TF,YT,NC,PM,WF)
   French territories are not explicitly included in the SWIFT specification textfile. 
   They were duplicated from France according to an unstructured comments against 
   that entry.  Example IBANs were then made for illustrative purposes by simply
   modifying the country prefix without regenerating the checksums.  The IBAN 
   examples included for these territories should now be correct.

 * *Gibraltar and Hungary* (GI,HU)
   Fixed a bug where both territories had a superfluous colon appended to their regular expressions after initial document conversion, which was causing validation failures for all IBANs in those countries.

 * *Mauritius* (MU)
   Corrected IBAN length expectation from 31 to 30.

 * *Sweden* (SE)
   Example IBAN had been manually modified from IBAN specification example early in development and did not pass checksum.  The IBAN official example has been restored.

 * *Tunisia* (TN)
   Corrected improper validation strings caused by a bug in initial document conversion (IBAN format-specifier to regular-expression conversion function).
 

Documentation (Procedural/Recommended)
======================================

```php
require_once('php-iban.php');
# ... your code utilising IBAN functions...
```

Validation Functions
--------------------

```php
# Verify an IBAN number.
#  An optional second argument specifies $machine_format_only (default is false)
#  If true, the function will not tolerate unclean inputs
#   (eg. spaces, dashes, leading 'IBAN ' or 'IIBAN ', lower case)
#  If false (default), input can be in either:
#   - printed ('IIBAN xx xx xx...' or 'IBAN xx xx xx...'); or 
#   - machine ('xxxxx')
#  ... string formats.
#  Returns true or false.
if(!verify_iban($iban,$machine_format_only=false)) {
 # ...
}

# Check the checksum of an IBAN - code modified from Validate_Finance PEAR class
if(!iban_verify_checksum($iban)) {
 # ...
}

# Suggest what the user really meant in the case of transcription errors
$suggestions = iban_mistranscription_suggestions($bad_iban);
if(count($suggestions) == 1) {
 print "You really meant " . $suggestions[0] . ", right?\n";
}

# Find the correct checksum for an IBAN
$correct_checksum = iban_find_checksum($iban);

# Set the correct checksum for an IBAN
$fixed_iban = iban_set_checksum($iban);

# Verify the pre-IBAN era, BBAN-level national checksum for those countries that
# have such a system and we have implemented.
# (Returns '' if unimplemented, true or false)
$result = iban_verify_nationalchecksum($iban);
if($result == '') {
 print "National checksum system does not exist or remains unimplemented for the country of IBAN '$iban'.\n";
}
elseif($result == true) {
 print "IBAN '$iban' passes the national checksum algorithm for its country.\n";
}
else {
 print "IBAN '$iban' FAILS the national checksum algorithm for its country.\n";
}

# Set the pre-IBAN era, BBAN-level national checksum for those countries that
# have such a system, where that system results in a dedicated checksum
# substring, and that we have implemented.
# (Returns '' if unimplemented, or the corrected string)
# (NOTE: On success, the function also subsequently recalculates the IBAN-level checksum)
$national_checksum_algorithm_valid_iban = iban_set_nationalchecksum($iban);

# Determine, but do not set, the pre-IBAN era, BBAN-level national checksum 
# for those countries that have such a system, where that system results in
# a dedicated checksum substring, and that we have implemented.
# (Returns '' if unimplemented, or the expected national checksum substring)
$expected_national_checksum = iban_find_nationalchecksum($iban);
```


Utility Functions
-----------------

```php
# Convert an IBAN to machine format.  To do this, we
# remove IBAN from the start, if present, and remove
# non basic roman letter / digit characters
$machine_iban = iban_to_machine_format($iban);

# Convert an IBAN to human format.  To do this, we
# add a space every four characters.
$human_iban = iban_to_human_format($iban);

# Convert an IBAN to obfuscated format for relative
# identification. To do this, we replace all but the
# leading country code and final four characters with
# asterisks.
$obfuscated_iban = iban_to_obfuscated_format($iban);
```


IBAN Country-Level Functions
----------------------------
```php
# Get the name of an IBAN country
$country_name = iban_country_get_country_name($iban_country);

# Get the domestic example for an IBAN country
$country_domestic_example = iban_country_get_domestic_example($iban_country);

# Get the BBAN example for an IBAN country
$country_bban_example = iban_country_get_bban_example($iban_country);

# Get the BBAN format (in SWIFT format) for an IBAN country
$country_bban_format_as_swift = iban_country_get_bban_format_swift($iban_country);

# Get the BBAN format (as a regular expression) for an IBAN country
$country_bban_format_as_regex = iban_country_get_bban_format_regex($iban_country);

# Get the BBAN length for an IBAN country
$country_bban_length = iban_country_get_bban_length($iban_country);

# Get the IBAN example for an IBAN country
$country_iban_example = iban_country_get_iban_example($iban_country);

# Get the IBAN length for an IBAN country
$country_iban_length = iban_country_get_iban_length($iban_country);

# Get the IBAN format (in SWIFT format) for an IBAN country
$country_iban_format_as_swift = iban_country_get_iban_format_swift ($iban_country);

# Get the IBAN format (as a regular expression) for an IBAN country
$country_iban_format_as_regex = iban_country_get_iban_format_regex($iban_country);

# Determine whether an IBAN country is a member of SEPA (Single Euro Payments Area)
if(!iban_country_is_sepa($iban_country)) {
 # ... do something xenophobic ...
}

# Get the bank ID start offset for an IBAN country
$country_bankid_start_offset = iban_country_get_bankid_start_offset($iban_country);

# Get the bank ID stop offset for an IBAN country
$country_bankid_stop_offset = iban_country_get_bankid_stop_offset($iban_country);

# Get the branch ID start offset for an IBAN country
$country_branchid_start_offset = iban_country_get_branchid_start_offset($iban_country);

# Get the branch ID stop offset for an IBAN country
$country_branchid_stop_offset = iban_country_get_branchid_stop_offset($iban_country);

# Get the registry edition for an IBAN country (note: IIBAN country 'AA' returns 'N/A')
$country_registry_edition = iban_country_get_registry_edition($iban_country);

# Determine whether an IBAN country is an official, SWIFT issued country record
if(!iban_country_get_country_swift_official($iban_country)) {
 # ... do something against decentralization ...
}

# Get the IANA code for an IBAN country
$country_iana = iban_country_get_iana($iban_country);

# Get the ISO3166-1 alpha-2 code for an IBAN country
$country_iso3166 = iban_country_get_iso3166($iban_country);

# Get the parent registrar IBAN country of an IBAN country
# (Returns '' in the normal case that the country is independently registered)
$registrar_country = iban_country_get_parent_registrar($iban_country);
if($registrar_country=='') {
 print "The mighty nation of '$iban_country' stands strong and proud...\n";
 print " ... with its own heirarchy of bureaucrats!\n";
}
else {
 print "It has been foretold that the downtrodden natives of '$iban_country' will one day\n";
 print "rise up and throw off the shackles of the evil '$registrar_country' oppressors!\n";
}

# Get the official currency of an IBAN country as an ISO4217 alpha code
# (Returns '' in the Internet (IIBAN) case, ie. no official currency)
$official_currency = iban_country_get_currency_iso4217($iban_country);
if($official_currency == '') {
 print "There is no official currency for the IBAN country '$iban_country'.\n";
}

# Get the URL of an IBAN country's central bank
# (Note: Returns '' if there is no central bank. Also, note that
#        sometimes multiple countries share one central bank)
$central_bank_url = iban_country_get_central_bank_url($iban_country);

# Get the name of an IBAN country's central bank
# (Note: Returns '' if there is no central bank. Also, note that
#        sometimes multiple countries share one central bank)
$central_bank_name = iban_country_get_central_bank_name($iban_country);

# Get the membership type of the country
# There are four types of memberships:
# * EU-Member States (eu_member)
# * EFTA-Member States (efta_member)
# * Other Memberships, which have monetary agreements with the EU (other_member)
# * Non-Members, which don't belong to the EU or have agreements (non_member)
$country_membership = iban_country_get_membership($iban_country);

# Get if the country is a eu member state
# (Note: Returns true, if member state; false otherwise)
$country_membership = iban_country_get_is_eu_member($iban_country);
```


Parsing Functions
-----------------
```php
# Get an array of all the parts from an IBAN
$iban_parts = iban_get_parts($iban);

# Get the country part from an IBAN
$iban_country = iban_get_country_part($iban);

# Get the BBAN part from an IBAN
$bban = iban_get_bban_part($iban);

# Get the Bank ID (institution code) from an IBAN
$bank = iban_get_bank_part($iban);

# Get the Branch ID (sort code) from an IBAN
#  (NOTE: only available for some countries)
$sortcode = iban_get_branch_part($iban);

# Get the (branch-local) account ID from an IBAN
#  (NOTE: only available for some countries)
$account = iban_get_account_part($iban);

# Get the checksum part from an IBAN
$checksum = iban_get_checksum_part($iban);

# Get the national checksum part from an IBAN (if it exists)
$checksum = iban_get_nationalchecksum_part($iban);
```


Documentation (Object Oriented Wrapper/Discouraged)
===================================================

OO use is discouraged as there is a present-day trend to overuse the model.  However, if you prefer OO PHP then by all means use the object oriented wrapper, described below.
```php
require_once('oophp-iban.php');
# ... your code utilising object oriented PHP IBAN functions...
```

Validation Functions
--------------------
```php
# Example instantiation
$iban = 'AZ12345678901234'
$myIban = new IBAN($iban);

# Verify an IBAN number.
#  Tolerates spaces, prefixes "IBAN ...", dashes, lowercase input, etc.
#  Returns true or false.
if(!$myIban->Verify()) {
 # ...
}
# Verify an IBAN number in machine format only.
#  Does not tolerate lowercase input, separators, whitespace or prefixes.
#  Returns true or false.
if(!$myIban->VerifyMachineFormatOnly()) {
 # ...
}

# Check the checksum of an IBAN - code modified from Validate_Finance PEAR class
if(!$myIban->VerifyChecksum()) {
 # ...
}

# Suggest what the user really meant in the case of mistranscription errors
$suggestions = $badIban->MistranscriptionSuggestions();
if(count($suggestions)==1) {
 print "You really meant " . $suggestions[0] . ", right?\n";
}

# Find the correct checksum for an IBAN
$correct_checksum = $myIban->FindChecksum();

# Set the correct checksum for an IBAN
$fixed_iban = $myIban->SetChecksum()

# Verify the pre-IBAN era, BBAN-level national checksum for those countries that
# have such a system and we have implemented.
# (Returns '' if unimplemented, true or false)
$result = $myIban->VerifyNationalChecksum();
if($result == '') {
 print "National checksum system does not exist or remains unimplemented for this IBAN's country.\n";
}
elseif($result == true) {
 print "IBAN passes the national checksum algorithm for its country.\n";
}
else {
 print "IBAN FAILS the national checksum algorithm for its country.\n";
}

# Set the pre-IBAN era, BBAN-level national checksum for those countries that
# have such a system, where that system results in a dedicated checksum
# substring, and that we have implemented.
# (Returns '' if unimplemented, or the corrected string)
# (NOTE: On success, the function also subsequently recalculates the IBAN-level checksum)
$myIban->SetNationalChecksum();

# Determine, but do not set, the pre-IBAN era, BBAN-level national checksum
# for those countries that have such a system, where that system results in
# a dedicated checksum substring, and that we have implemented.
# (Returns '' if unimplemented, or the expected national checksum substring)
$national_checksum = $myIban->FindNationalChecksum();
```

Utility Functions
-----------------

```php
# Convert an IBAN to machine format.  To do this, we
# remove IBAN from the start, if present, and remove
# non basic roman letter / digit characters
$machine_iban = $myIban->MachineFormat();

# Convert an IBAN to human format.  To do this, we
# add a space every four characters.
$human_iban = $myIban->HumanFormat();

# Convert an IBAN to obfuscated format for relative
# identification. To do this, we replace all but the
# leading country code and final four characters with
# asterisks.
$obfsucated_iban = $myIban->ObfuscatedFormat();
```

IBAN Country-Level Functions
----------------------------

```php
# To list countries, use the IBAN Class...
$myIban->Countries();

# ... everything else is in the IBANCountry class.

# Example instantiation
$countrycode = 'DE';
$myCountry = new IBANCountry($countrycode);

# Get the country code of an IBAN country
$country_code = $myCountry->Code();

# Get the name of an IBAN country
$country_name = $myCountry->Name();

# Get the domestic example for an IBAN country
$country_domestic_example = $myCountry->DomesticExample();

# Get the BBAN example for an IBAN country
$country_bban_example = $myCountry->BBANExample();

# Get the BBAN format (in SWIFT format) for an IBAN country
$country_bban_format_as_swift = $myCountry->BBANFormatSWIFT();

# Get the BBAN format (as a regular expression) for an IBAN country
$country_bban_format_as_regex = $myCountry->BBANFormatRegex();

# Get the BBAN length for an IBAN country
$country_bban_length = $myCountry->BBANLength();

# Get the IBAN example for an IBAN country
$country_iban_example = $myCountry->IBANExample();

# Get the IBAN length for an IBAN country
$country_iban_length = $myCountry->IBANLength();

# Get the IBAN format (in SWIFT format) for an IBAN country
$country_iban_format_as_swift = $myCountry->IBANFormatSWIFT();

# Get the IBAN format (as a regular expression) for an IBAN country
$country_iban_format_as_regex = $myCountry->IBANFormatRegex();

# Determine whether an IBAN country is a member of SEPA (Single Euro Payments Area)
if(!$myCountry->IsSEPA()) {
 # ... do something xenophobic ...
}

# Get the bank ID start offset for an IBAN country
$country_bankid_start_offset = $myCountry->BankIDStartOffset();

# Get the bank ID stop offset for an IBAN country
$country_bankid_stop_offset = $myCountry->BankIDStopOffset();

# Get the branch ID start offset for an IBAN country
$country_branchid_start_offset = $myCountry->BranchIDStartOffset();

# Get the branch ID stop offset for an IBAN country
$country_branchid_stop_offset = $myCountry->BranchIDStopOffset();

# Get the national checksum start offset for an IBAN country
$country_nationalchecksum_start_offset = $myCountry->NationalChecksumStartOffset();

# Get the national checksum stop offset for an IBAN country
$country_nationalchecksum_stop_offset = $myCountry->NationalChecksumStopOffset();

# Get the registry edition for an IBAN country (note: IIBAN country 'AA' returns 'N/A')
$country_registry_edition = $myCountry->RegistryEdition();

# Determine whether an IBAN country is an official, SWIFT issued country record
if(!$myCountry->SWIFTOfficial()) {
 # ... do something against decentralization ...
}

# Get the IANA code for an IBAN country
$country_iana = $myCountry->IANA();

# Get the ISO3166-1 alpha-2 code for an IBAN country
$country_iso3166 = $myCountry->ISO3166();

# Get the parent registrar IBAN country of an IBAN country
# (Returns '' in the normal case that the country is independently registered)
$registrar_country = $myCountry->ParentRegistrar();
if($registrar_country=='') {
 print "The mighty nation of '$iban_country' stands strong and proud...\n";
 print " ... with its own heirarchy of bureaucrats!\n";
}
else {
 print "It has been foretold that the downtrodden natives of '$iban_country' will one day\n";
 print "rise up and throw off the shackles of the evil '$registrar_country' oppressors!\n";
}

# Get the official currency of an IBAN country as an ISO4217 alpha code
# (Returns '' in the Internet (IIBAN) case, ie. no official currency)
$official_currency = $myCountry->CurrencyISO4217();
if($official_currency == '') {
 print "There is no official currency for the IBAN country '$iban_country'.\n";
}

# Get the URL of an IBAN country's central bank
# (Note: Returns '' if there is no central bank. Also, note that
#        sometimes multiple countries share one central bank)
$central_bank_url = $myCountry->CentralBankURL();

# Get the name of an IBAN country's central bank
# (Note: Returns '' if there is no central bank. Also, note that
#        sometimes multiple countries share one central bank)
$central_bank_name = $myCountry->CentralBankName();
```


Parsing Functions
-----------------

```php
# Get an array of all the parts from an IBAN
$iban_parts = $myIban->Parts();

# Get the country part from an IBAN
$iban_country = $myIban->Country();

# Get the checksum part from an IBAN
$checksum = $myIban->Checksum();

# Get the BBAN part from an IBAN
$bban = $myIban->BBAN();

# Get the Bank ID (institution code) from an IBAN
$bank = $myIban->Bank();

# Get the Branch ID (sort code) from an IBAN
#  (NOTE: only available for some countries)
$sortcode = $myIban->Branch();

# Get the (branch-local) account ID from an IBAN
#  (NOTE: only available for some countries)
$account = $myIban->Account();

# Get the national checksum part from an IBAN
#  (NOTE: only available for some countries)
$checksum = $myIban->NationalChecksum();
```


IBAN Libraries in Other Languages
---------------------------------
See for yourself how our approach and features compare favourably with all of these libraries...

| Language   | Library
| ---------- | --------------------------------------------------------- 
| C#         | [iban-api-net](https://github.com/aventum-solutions/iban-api-net)
| Java       | [iban-api-java](https://github.com/aventum-solutions/iban-api-java)
| Java       | [iban4j](https://github.com/arturmkrtchyan/iban4j)
| Java       | [java-iban](https://github.com/barend/java-iban)
| Javascript | [iban.js](https://github.com/arhs/iban.js)
| Javascript | [ng-iban](https://github.com/mmjmanders/ng-iban)
| ObjectiveC | [IBAN-Helper](https://github.com/xs4some/IBAN-Helper)
| ObjectiveC | [ibanValidation](https://github.com/smoldovansky/ibanValidation)
| Perl       | [various CPAN libraries](http://search.cpan.org/search?query=iban&mode=all)
| Python     | [django-localflavor](https://github.com/django/django-localflavor)
| Python     | [iban-generator](https://github.com/lkraider/iban-generator)
| Ruby       | [bank](https://github.com/max-power/bank)
| Ruby       | [iban-tools](https://github.com/iulianu/iban-tools)
| Ruby       | [ibandit](https://github.com/gocardless/ibandit)
| Ruby       | [ibanizator](https://github.com/softwareinmotion/ibanizator)
| Ruby       | [iso-iban](https://github.com/apeiros/iso-iban)
