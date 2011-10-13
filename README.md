# DOLIBARR ERP/CRM

Dolibarr ERP/CRM is a modern software to manage your company or foundation activity (contacts, suppliers, invoices, orders, stocks, agenda, ...).It's an opensource software (wrote with PHP language) designed for small and medium companies, foundation and freelances. You can freely install, use and distribute it as a standalone application or as a web application to use it from every internet access and media.


## INSTALL

If you have no technical knowledge, and you are looking for an autoinstaller to install Dolibarr ERP/CRM in few clicks, you must download DoliWamp (the all-in-one package of Dolibarr for Windows), DoliDeb (the all-in-one package of Dolibarr for Debian or Ubuntu) or DoliRpm (the all-in-one package of Dolibarr for Fedora, Redhat, Opensue, Mandriva or Mageia).

You can download this at: [Official Website] (http://www.dolibarr.org/downloads/)

If you already have installed a Web server and a Mysql database, you can install this version like this:

- Copy directory "dolibarr" and all its files inside your web server root, or copy directory anywhere and set up your web server to use "dolibarr/htdocs" as root for a new web server virtual host (second choice need to be server administrator).
  
- Create an empty file "htdocs/conf/conf.php" and set permissions for your web server user (write permissions will be removed once install is finished).
  
- From your browser, call the dolibarr "install/" page.

Url depends on choice made on first step:

	http://localhost/dolibarr/htdocs/install/
or

	http://yourdolibarrvirtualhost/install/
   
- Follow instructions provided by installer...



## UPGRADE

To upgrade Dolibarr from an old version to this one:

- Overwrite all old files inside old 'dolibarr' directory by files provided into new version package.
  
- If you came from version x.y.z to x.y.w (only third number differ), there is no need to run any migrate process.
  
- If you came from a beta version or from any version x.y.z to any other where x or y number differs, you must call the Dolibarr "install/" page in your browser (this should be done automatically at first dolibarr access).

This URL should looks like:

	http://localhost/dolibarr/htdocs/install/
or

	http://yourdolibarrhost/install/index.php

Then choose the "update" option according to your case.
Note: Migrate process can be ran safely several times.
  


## WHAT'S NEW

See ChangeLog file.



## WHAT DOLIBARR CAN DO

### Main modules/features:

- Products and services catalog
- Customers, Prospects or Suppliers directory
- Address book
- Stock management
- Bank accounts management
- Orders management with PDF export
- Commercial proposals management with PDF export
- Contracts management
- Invoices management with PDF export
- Payments management
- Standing orders management
- Shipping management
- ECM (Electronic Content Management)
- EMailings
- Agenda with ical,vcal export for third tools integration
- Management of foundation members
- Donation management

### Other modules:

- Bookmarks management
- Can reports Dolibarr events inside Webcalendar or Phenix
- Data export tools
- LDAP connectivity
- Third parties or products categories 
- ClickToDial phone numbers
- RSS

### Miscellanous:

- Mutli-user, with several permissions levels for each feature.
- Serveral menu managers (can be used by internal users, as a back-office, with a particular menu, or by external users, as a front-office, with another menu and permissions).
- Very user friendly and easy to use.
- Optional WYSIWYG forms, optional Ajax forms.
- Several skins.
- Code is highly customizable (a lot of use of modules and submodules).
- Works with Mysql 3.1 or higher, experimental support for PostgreSql.
- Works with PHP 4.3 or higher.
- An easy to understand and maintain code (PHP with no heavy frameworks).
- A trigger architecture to allow you to make Dolibarr business events run PHP code to update your own information system.
- "NPR VAT Rate" (French particularity for managing VAT in DOM-TOM called "Non Perçue Récupérable").



## WHAT DOLIBARR CAN'T DO YET (TODO LIST)

This is features that Dolibarr does not support completely yet:

- Accountancy.
- Dolibarr can manage only one currency.
- Dolibarr does not support the double Canadian Tax.
- Dolibarr does not make coffee (not yet).
- Stock management with option increase/decrease on invoice works on warehouse number one only (no way to choose warehouse).
- Stock management with option increase/decrease on orders works on warehouse number one only (no way to choose warehouse).
