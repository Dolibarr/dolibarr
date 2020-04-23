# DOLIBARR ERP & CRM custom directory for external modules

This directory is dedicated to store external modules.
To use it, just copy here the directory of the module into this directory.

Note: On linux or MAC systems, it is better to unzip/store the external module directory into
a different place than this directory and just adding a symbolic link here to the htdocs directory
of the module.

For example on Linux OS: Get the module from the command

`mkdir ~/git; cd ~/git`

`git clone https://git.framasoft.org/p/newmodule/newmodule.git`

Then create the symbolic link

`ln -fs ~/git/newmodule/htdocs /path_to_dolibarr/htdocs/custom/newmodule`

WARNING !!!
Check also that the /custom directory is active by adding into dolibarr `conf/conf.php` file the following
two lines, so dolibarr will also scan /custom directory to find external external modules:

```php
$dolibarr_main_url_root_alt='/custom';
$dolibarr_main_document_root_alt='/path_to_dolibarr/htdocs/custom/';
```
