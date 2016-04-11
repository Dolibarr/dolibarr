# DOLIBARR ERP & CRM

![Build status](https://img.shields.io/travis/Dolibarr/dolibarr/develop.svg) ![Downloads per day](https://img.shields.io/sourceforge/dm/dolibarr.svg)

Dolibarr ERP & CRM is a modern software to manage your organization's activity (contacts, suppliers, invoices, orders, stocks, agenda, ...).

It's an Open Source software (wrote in PHP language) designed for small and medium companies, foundation and freelances.

You can freely use, study, modify or distribute it according to its Free Software licence.

You can use it as a standalone application or as a web application to be able to access it from the Internet or a LAN.

![ScreenShot](http://www.dolibarr.org/images/dolibarr_screenshot1_640x400.png)

## LICENSE

Dolibarr is released under the terms of the GNU General Public License as published by the Free Software Foundation; either version 3 of the License, or (at your option) any later version (GPL-3+).

See the [COPYING](https://github.com/Dolibarr/dolibarr/blob/develop/COPYING) file for a full copy of the license.

Other licenses apply for some included dependencies. See [COPYRIGHT](https://github.com/Dolibarr/dolibarr/blob/develop/COPYRIGHT) for a full list.

## INSTALLING

### Download

Releases can be downloaded from [official website](http://www.dolibarr.org/).

### Simple setup

If you have low technical skills and you're looking to install Dolibarr ERP/CRM in few clicks, you can use one of the packaged versions:

- DoliWamp for Windows
- DoliDeb for Debian or Ubuntu
- DoliRpm for Redhat, Fedora, OpenSuse, Mandriva or Mageia

### Advanced setup

You can use a Web server and a supported database (MySQL recommended) to install the standard version.

- Uncompress the downloaded archive
- Copy directory "dolibarr" and all its files inside your web server root, or copy directory anywhere and set up your web server to use "dolibarr/htdocs" as root for a new web server virtual host (second choice need to be server administrator)
- Create an empty file "htdocs/conf/conf.php" and set permissions for your web server user (write permissions will be removed once install is finished)
- From your browser, go to the dolibarr "install/" page

    The URL will depends on choices made in the first step:

        http://localhost/dolibarr/htdocs/install/
        
    or
    
        http://localhost/dolibarr/install/
        
    or
    
    	http://yourdolibarrvirtualhost/install/
   
- Follow the installer instructions

## UPGRADING

- Overwrite all old files from 'dolibarr' directory with files provided into the new version's package.
- If you're upgrading from version x.y.z to x.y.w (only third number differs), there is no need to run any migration process.
- If you're upgrading from a beta version or from any version x.y.z to any other where x or y number differs, you must call the Dolibarr "install/" page in your browser (this should be done automatically at first dolibarr access) and follow the upgrade process.

*Note: migration process can safely be done multiple times.*

## WHAT'S NEW

See the [ChangeLog](https://github.com/Dolibarr/dolibarr/blob/develop/ChangeLog) file.

## FEATURES

### Main modules (all optional)

- Customers, Prospects and/or Suppliers directory
- Products and/or Services catalog
- Commercial proposals management
- Customer and Supplier Orders management
- Invoices and payment management
- Standing orders management (European SEPA)
- Bank accounts management
- Shared calendar
- Opportunities and/or project management (following project benefit including invoices, expense reports, time spent, ...)
- Projects management
- Contracts management
- Stock management
- Shipping management
- Interventions management
- Agenda with ical and vcal export for third party tools integration
- Electronic Document Management (EDM)
- Foundations members management
- Employee's holidays management
- Mass emailing
- Surveys
- Point of Sale
- …

### Other modules

- Bookmarks management
- Donations management
- Reporting
- Data export/import
- Thirdparties and/or products categories
- Barcodes support
- Margin calculations
- LDAP connectivity
- ClickToDial integration
- RSS integration
- Skype integration
- Payment platforms integration (PayBox, PayPal)
- …

### Other general features
- Multi-Users and groups with finely grained rights
- Localization in most major languages
- Can manage several companies by adding external module multi-company.
- Can manage several currencies by adding external module multi-currency.
- Very user friendly and easy to use
- Highly customizable: enable only the modules you need, add user personalized fields, choose your skin, several menu managers (can be used by internal users as a back-office with a particular menu, or by external users as a front-office with another one)
- Works with PHP 5.3+ and MySQL 4.1+ or PostgreSQL 8.1. (See requirements on the [Wiki](http://wiki.dolibarr.org/index.php/Prerequisite))
- Compatible with all Cloud solutions that match MySQL, PHP or PostgreSQL prerequisites.
- An easy to understand, maintain and code interfaces with your own information system (PHP with no heavy framework; trigger and hook architecture)
- Support for country specific features:
    - Spanish Tax RE and ISPF
    - French NPR VAT rate (VAT called "Non Perçue Récupérable" for DOM-TOM)
    - Canadian double taxes (federal/province) and other countries using cumulative VAT
    - Tunisian tax stamp
    - Compatible with [European directives](http://europa.eu/legislation_summaries/taxation/l31057_en.htm) (2006/112/CE ... 2010/45/UE)
- PDF or ODT generation for invoice, proposals, orders...
- …

### Extending

Dolibarr can be extended with a lot of other external modules from third party developers available at the [DoliStore](http://www.dolistore.com).

## FUTURE

These are features that Dolibarr does **not** yet fully support:

- Double-entry bookkeeping yet (only bank and treasury management)
- Tasks dependencies in projects
- Payroll module
- Webmail
- Dolibarr can't do coffee (yet)

## DOCUMENTATION

Administrator, user, developer and translator's documentations are available along with other community resources on the [Wiki](http://wiki.dolibarr.org).

## CONTRIBUTING

See file [CONTRIBUTING](https://github.com/Dolibarr/dolibarr/blob/develop/CONTRIBUTING.md)

## CREDITS

Dolibarr is the work of many contributors over the years and uses some fine libraries.

See [COPYRIGHT](https://github.com/Dolibarr/dolibarr/blob/develop/COPYRIGHT) file.

## NEWS AND SOCIAL NETWORKS

Follow Dolibarr project on:

- [Facebook](https://www.facebook.com/dolibarr)
- [Google+](https://plus.google.com/+DolibarrOrg)
- [Twitter](http://www.twitter.com/dolibarr)
- [LinkedIn](https://www.linkedin.com/company/association-dolibarr)
- [YouTube](https://www.youtube.com/user/DolibarrERPCRM)
- [GitHub](https://github.com/Dolibarr/dolibarr)
