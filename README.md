# DOLIBARR ERP & CRM

![Downloads per day](https://img.shields.io/sourceforge/dw/dolibarr.svg)
[![Reviewed by Hound](https://img.shields.io/badge/Reviewed_by-Hound-8E64B0.svg)](https://houndci.com)

|6|7|8|9|develop|
|----------|----------|----------|----------|----------|
|![Build status](https://img.shields.io/travis/Dolibarr/dolibarr/6.0.svg)|![Build status](https://img.shields.io/travis/Dolibarr/dolibarr/7.0.svg)|![Build status](https://img.shields.io/travis/Dolibarr/dolibarr/8.0.svg)|![Build status](https://img.shields.io/travis/Dolibarr/dolibarr/9.0.svg)|![Build status](https://img.shields.io/travis/Dolibarr/dolibarr/develop.svg)|

Dolibarr ERP & CRM is a modern software package to manage your organization's activity (contacts, suppliers, invoices, orders, stocks, agenda…).

It's an Open Source Software (written in PHP language) designed for small, medium or large companies, foundations and freelances.

You can freely use, study, modify or distribute it according to its Free Software licence.

You can use it as a standalone application or as a web application to be able to access it from the Internet or a LAN.

![ScreenShot](https://www.dolibarr.org/images/dolibarr_screenshot1_640x400.png)


## LICENSE

Dolibarr is released under the terms of the GNU General Public License as published by the Free Software Foundation; either version 3 of the License, or (at your option) any later version (GPL-3+).

See the [COPYING](https://github.com/Dolibarr/dolibarr/blob/develop/COPYING) file for a full copy of the license.

Other licenses apply for some included dependencies. See [COPYRIGHT](https://github.com/Dolibarr/dolibarr/blob/develop/COPYRIGHT) for a full list.


## INSTALLING

### Simple setup

If you have low technical skills and you're looking to install Dolibarr ERP/CRM in just a few clicks, you can use one of the packaged versions:

- DoliWamp for Windows
- DoliDeb for Debian or Ubuntu
- DoliRpm for Redhat, Fedora, OpenSuse, Mandriva or Mageia

Releases can be downloaded from [official website](https://www.dolibarr.org/).

### Advanced setup

You can use a Web server and a supported database (MariaDB, MySQL or PostgreSQL) to install the standard version.

- Uncompress the downloaded .zip archive to copy the "dolibarr/htdocs" directory and all its files inside your web server root or get the files directly from GitHub (recommanded if you known git): 

  git clone https://github.com/dolibarr/dolibarr -b x.y     (where x.y is main version like 3.6, 9.0, ...)

- Set up your web server to use "*dolibarr/htdocs*" as root if your web server does not have an already defined directory to point to. 
 
- Create an empty `htdocs/conf/conf.php` file and set *write* permissions for your web server user (*write* permission will be removed once install is finished)

- From your browser, go to the dolibarr "install/" page

    The URL will depends on how you web setup was setup to point to your dolibarr installation. It may looks like:

        http://localhost/dolibarr/htdocs/install/
        
    or
    
        http://localhost/dolibarr/install/
        
    or
    
    	http://yourdolibarrvirtualhost/install/
   
- Follow the installer instructions

### Saas/Cloud setup

If you don't have time to install it yourself, you can try some commercial 'ready to use' Cloud offers (See https://saas.dolibarr.org). However, this third solution is not free.



## UPGRADING

- Overwrite all old files from 'dolibarr' directory with files provided into the new version's package.
- At first next access, Dolibarr will redirect your to the "install/" page to follow the upgrade process.
  If an `install.lock` file exists to lock any other upgrade process, the application will ask you to remove the file manually (you should find the `install.lock` file into the directory used to store generated and uploaded documents, in most cases, it is the directory called "*documents*").

*Note: migration process can be safely done multiple times by calling the `/install/index.php` page*


## WHAT'S NEW

See the [ChangeLog](https://github.com/Dolibarr/dolibarr/blob/develop/ChangeLog) file.


## FEATURES

### Main application/modules (all optional)

- Customers, Prospects and/or Suppliers directory
- Products and/or Services catalog
- Commercial proposals management
- Customer and Supplier Orders management
- Invoices and payment management
- Standing orders management (European SEPA)
- Bank accounts management
- Shared calendar/agenda (with ical and vcal export for third party tools integration)
- Opportunities and/or project management
- Projects management
- Contracts management
- Stock management
- Shipping management
- Interventions management
- Employee's leave requests management
- Expense reports
- Timesheets
- Electronic Document Management (EDM)
- Foundations members management
- Mass emailing
- Surveys
- Point of Sale
- …

### Other application/modules

- Bookmarks management
- Donations management
- Reporting
- Data export/import
- Barcodes support
- Margin calculations
- LDAP connectivity
- ClickToDial integration
- RSS integration
- Skype integration
- Payment platforms integration (PayPal, Stripe, Paybox...)
- …

### Other general features

- Localization in most major languages.
- Multi-Users and groups with finely grained rights.
- Multi-currency.
- Multi-company by adding of an external module.
- Very user friendly and easy to use.
- Highly customizable: enable only the modules you need, add user personalized fields, choose your skin, several menu managers (can be used by internal users as a back-office with a particular menu, or by external users as a front-office with another one)
- Works with PHP 5.5+ and MariaDB 5.0.3+, MySQL 5.0.3+ or PostgreSQL 8.1.4+ (See requirements on the [Wiki](https://wiki.dolibarr.org/index.php/Prerequisite))
- Compatible with all Cloud solutions that match MySQL, PHP or PostgreSQL prerequisites.
- APIs.
- An easy to understand, maintain and develop code (PHP with no heavy framework; trigger and hook architecture)
- Support a lot of country specific features:
    - Spanish Tax RE and ISPF
    - French NPR VAT rate (VAT called "Non Perçue Récupérable" for DOM-TOM)
    - Canadian double taxes (federal/province) and other countries using cumulative VAT
    - Tunisian tax stamp
    - Argentina invoice numbering using A,B,C... 
    - Compatible with [European directives](http://europa.eu/legislation_summaries/taxation/l31057_en.htm) (2006/112/CE ... 2010/45/UE)
    - Compatible with European GDPR rules
    - ...
- PDF or ODT generation for invoice, proposals, orders...
- …

### Extending

Dolibarr can be extended with a lot of other external application or modules from third party developers available at the [DoliStore](https://www.dolistore.com).


## WHAT DOLIBARR CAN'T DO YET

These are features that Dolibarr does **not** yet fully support:

- Tasks dependencies in projects
- Payroll module
- No native embedded Webmail
- Dolibarr can't do coffee (yet)


## DOCUMENTATION

Administrator, user, developer and translator's documentations are available along with other community resources on the [Wiki](https://wiki.dolibarr.org).


## CONTRIBUTING

This project exists thanks to all the people who contribute. [[Contribute](https://github.com/Dolibarr/dolibarr/blob/develop/.github/CONTRIBUTING.md)].
<a href="https://github.com/Dolibarr/dolibarr/graphs/contributors"><img src="https://opencollective.com/dolibarr/contributors.svg?width=890&button=false" /></a>


## CREDITS

Dolibarr is the work of many contributors over the years and uses some fine libraries.

See [COPYRIGHT](https://github.com/Dolibarr/dolibarr/blob/develop/COPYRIGHT) file.


## NEWS AND SOCIAL NETWORKS

Follow Dolibarr project on:

- [Facebook](https://www.facebook.com/dolibarr)
- [Google+](https://plus.google.com/+DolibarrOrg)
- [Twitter](https://www.twitter.com/dolibarr)
- [LinkedIn](https://www.linkedin.com/company/association-dolibarr)
- [YouTube](https://www.youtube.com/user/DolibarrERPCRM)
- [GitHub](https://github.com/Dolibarr/dolibarr)


### Sponsors

Support this project by becoming a sponsor. Your logo will show up here. 🙏 [[Become a sponsor/backer](https://opencollective.com/dolibarr#backer)]
