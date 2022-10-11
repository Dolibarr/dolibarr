# DOLIBARR ERP & CRM

![Downloads per day](https://img.shields.io/sourceforge/dw/dolibarr.svg)
![Build status](https://img.shields.io/travis/Dolibarr/dolibarr/develop.svg)
[![Minimum PHP Version](https://img.shields.io/badge/php-%3E%3D%205.6-8892BF.svg?style=flat-square)](https://php.net/)
[![GitHub release](https://img.shields.io/github/v/release/Dolibarr/dolibarr)](https://github.com/Dolibarr/dolibarr)
[![CII Best Practices](https://bestpractices.coreinfrastructure.org/projects/5521/badge)](https://bestpractices.coreinfrastructure.org/projects/5521)

Dolibarr ERP & CRM is a modern software package that helps manage your organization's activity (contacts, suppliers, invoices, orders, stocks, agenda…).

It's an Open Source Software suite (written in PHP with optional JavaScript enhancements) designed for small, medium or large companies, foundations and freelancers.

You can freely use, study, modify or distribute it according to its licence.

You can use it as a standalone application or as a web application to access it from the Internet or a LAN.

Dolibarr has a large community ready to help you, free forums and [preferred partners ready to offer commercial support should you need it](https://partners.dolibarr.org)

![ScreenShot](https://www.dolibarr.org/medias/dolibarr_screenshot1_1920x1080.jpg)

## LICENSE

Dolibarr is released under the terms of the GNU General Public License as published by the Free Software Foundation; either version 3 of the License, or (at your option) any later version (GPL-3+).

See the [COPYING](https://github.com/Dolibarr/dolibarr/blob/develop/COPYING) file for a full copy of the license.

Other licenses apply for some included dependencies. See [COPYRIGHT](https://github.com/Dolibarr/dolibarr/blob/develop/COPYRIGHT) for a full list.

## INSTALLING

### Simple setup

If you have low technical skills and you're looking to install Dolibarr ERP/CRM in just a few clicks, you can use one of the packaged versions:

- [DoliWamp for Windows](https://wiki.dolibarr.org/index.php/Dolibarr_for_Windows_(DoliWamp))
- [DoliDeb for Debian](https://wiki.dolibarr.org/index.php/Dolibarr_for_Ubuntu_or_Debian)
- DoliRpm for Redhat, Fedora, OpenSuse, Mandriva or Mageia

Releases can be downloaded from [official website](https://www.dolibarr.org/).

### Advanced setup

You can use a web server and a supported database (MariaDB, MySQL or PostgreSQL) to install the standard version.

On GNU/Linux, first check if your distribution has already packaged Dolibarr.

#### Generic install steps

- Check that your installed PHP version is supported [see PHP support](https://wiki.dolibarr.org/index.php/Releases).

- Uncompress the downloaded .zip archive to copy the "dolibarr/htdocs" directory and all its files inside your web server root or get the files directly from GitHub (recommanded if you know git as it makes it easier if you want to upgrade later):

  `git clone https://github.com/dolibarr/dolibarr -b x.y`     (where x.y is main version like 3.6, 9.0, ...)

- Set up your web server to use "*dolibarr/htdocs*" as root if your web server does not have an already defined directory to point to.

- Create an empty `htdocs/conf/conf.php` file and set *write* permissions for your web server user (*write* permission will be removed once install is finished)

- From your browser, go to the dolibarr "install/" page

  The URL will depends on how you web setup was setup to point to your dolibarr installation. It may looks like:

  `http://localhost/dolibarr/htdocs/install/`

  or

  `http://localhost/dolibarr/install/`

  or

  `http://yourdolibarrvirtualhost/install/`

- Follow the installer instructions

### Saas/Cloud setup

If you don't have time to install it yourself, you can try some commercial 'ready to use' Cloud offers (See [https://saas.dolibarr.org](https://saas.dolibarr.org)). However, this third solution is not free.

## UPGRADING

Dolibarr supports upgrading, usually without the need for any (commercial) support (depending on if you use any commercial extensions). It supports upgrading all the way from any version after 2.8 without breakage. This is unique in the ERP ecosystem and a benefit our users highly appreciate!

- At first make a backup of your Dolibarr files & then [see](https://wiki.dolibarr.org/index.php/Installation_-_Upgrade#Upgrade_Dolibarr)
- Check that your installed PHP version is supported by the new version [see PHP support](https://wiki.dolibarr.org/index.php/Releases).
- Overwrite all old files from 'dolibarr' directory with files provided into the new version's package.
- At first next access, Dolibarr will redirect you to the "install/" page to follow the upgrade process.
  If an `install.lock` file exists to lock any other upgrade process, the application will ask you to remove the file manually (you should find the `install.lock` file in the directory used to store generated and uploaded documents, in most cases, it is the directory called "*documents*").

## WHAT'S NEW

See the [ChangeLog](https://github.com/Dolibarr/dolibarr/blob/develop/ChangeLog) file.

## FEATURES

### Main application/modules (all optional)

- Third-Parties Management: Customers, Prospects (Leads) and/or Suppliers + Contacts
- Members/Membership/Foundation management

 Product Management

- Products and/or Services catalog
- Stock / Warehouse management + Inventory
- Barcodes
- Batches / Lots / Serials
- Product Variants
- Bill of Materials (BOM)
- Manufacturing Orders

 Customer/Sales Management

- Customers/Prospects + Contacts management
- Opportunities or Leads management
- Commercial proposals management (online signing)
- Customer Orders management
- Contracts/Subscription management
- Interventions management
- Ticket System
- Shipping management
- Customer Invoices/Credit notes and payment management
- Point of Sale (POS)

 Supplier/Purchase Management

- Suppliers/Vendors + Contacts
- Supplier (price) requests
- Purchase Orders management
- Delivery/Receiption
- Supplier Invoices/credit notes and payment management
- INCOTERMS

 Finance/Accounting

- Invoices/Payments
- Bank accounts management
- Direct debit and Credit transfer management (European SEPA)
- Accounting management
- Donations management
- Loan management
- Margins
- Reports

 Collaboration

- Shared calendar/agenda (with ical and vcal import/export for third party tools integration)
- Projects & Tasks management
- Ticket System
- Surveys

 HR

- Employee's leaves management
- Expense reports
- Recruitment management
- Timesheets

### Other application/modules

- Electronic Document Management (EDM)
- Bookmarks
- Reporting
- Data export/import
- Barcodes
- LDAP connectivity
- ClickToDial integration
- Mass emailing
- RSS integration
- Social platforms linking
- Payment platforms integration (PayPal, Stripe, Paybox...)
- Email-Collector

(around 100 modules available by default, 1000+ on the addon market place)

### Other general features

- Multi-Language Support (Localization in most major languages)
- Multi-Users and groups with finely grained rights
- Multi-Currency
- Multi-Company (by adding of an external module)
- Very user friendly and easy to use
- Customizable dashboards
- Highly customizable: enable only the modules you need, add user personalized fields, choose your skin, several menu managers (can be used by internal users as a back-office with a particular menu, or by external users as a front-office with another one)
- APIs (REST, SOAP)
- Code that is easy to understand, maintain and develop (PHP with no heavy framework; trigger and hook architecture)
- Support a lot of country specific features:
  - Spanish Tax RE and ISPF
  - French NPR VAT rate (VAT called "Non Perçue Récupérable" for DOM-TOM)
  - Canadian double taxes (federal/province) and other countries using cumulative VAT
  - Tunisian tax stamp
  - Argentina invoice numbering using A,B,C...
  - ZATCA e-invoicing QR-Code
  - Compatible with [European directives](https://europa.eu/legislation_summaries/taxation/l31057_en.htm) (2006/112/CE ... 2010/45/UE)
  - Compatible with data privacy rules (europe GDPR, ...)
  - ...
- Flexible PDF & ODT generation for invoices, proposals, orders...
- ...

### System Environment / Requirements

- PHP
- MariaDB, MySQL or PostgreSQL
- Compatible with all Cloud solutions that match PHP & MySQL or PostgreSQL prerequisites.

See exact requirements on the [Wiki](https://wiki.dolibarr.org/index.php/Prerequisite)

### Extending

Dolibarr can be extended with a lot of other external application or modules from third party developers available at the [DoliStore](https://www.dolistore.com).

## WHAT DOLIBARR CAN'T DO YET

These are features that Dolibarr does **not** yet fully support:

- Tasks dependencies in projects
- Payroll module
- No native embedded Webmail, but you can send email to contacts in Dolibarr with e.g. offers, invoices, etc.
- Dolibarr can't do coffee (yet)

## DOCUMENTATION

Administrator, user, developer and translator's documentations are available along with other community resources in the [Wiki](https://wiki.dolibarr.org).

## CONTRIBUTING

This project exists thanks to all the people who contribute.
Please read the instructions how to contribute (report a bug/error, a feature request, send code ...)  [[Contribute](https://github.com/Dolibarr/dolibarr/blob/develop/.github/CONTRIBUTING.md)]

A view on Contributors:

[![Dolibarr](https://opencollective.com/dolibarr/contributors.svg?width=890&button=false)](https://github.com/Dolibarr/dolibarr/graphs/contributors)

## CREDITS

Dolibarr is the work of many contributors over the years and uses some fine PHP libraries.

See [COPYRIGHT](https://github.com/Dolibarr/dolibarr/blob/develop/COPYRIGHT) file.

## NEWS AND SOCIAL NETWORKS

Follow Dolibarr project on:

- [Facebook](https://www.facebook.com/dolibarr)
- [Twitter](https://www.twitter.com/dolibarr)
- [LinkedIn](https://www.linkedin.com/company/association-dolibarr)
- [Reddit](https://www.reddit.com/r/Dolibarr_ERP_CRM/)
- [YouTube](https://www.youtube.com/user/DolibarrERPCRM)
- [GitHub](https://github.com/Dolibarr/dolibarr)

### Sponsors

Support this project by becoming a sponsor. Your logo will show up here. 🙏 [[Become a sponsor/backer](https://opencollective.com/dolibarr#backer)]
