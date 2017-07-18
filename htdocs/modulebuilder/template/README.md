# MYMODULE FOR DOLIBARR ERP CRM

## Features
MyModuleDescription

Other modules are available on <a href="https://www.dolistore.com" target="_new">Dolistore.com</a>.



### Translations

This module contains a sample configuration for Transifex, under the hidden directory [.tx](.tx), so it is possible to manage translation using this service. 
Translations can be define manually by editing files into directories [langs](langs). 

<!--
For more informations, see the [translator's documentation](https://wiki.dolibarr.org/index.php/Translator_documentation).

There is a [Transifex project](https://transifex.com/projects/p/dolibarr-module-template) for this module.
-->


<!--

Install
-------

### Manually

- Make sure Dolibarr is already installed and configured on your workstation or development server.

- In your Dolibarr installation directory, edit the ```htdocs/conf/conf.php``` file

- Find the following lines:
    ```php
    //$dolibarr_main_url_root_alt ...
    //$dolibarr_main_document_root_alt ...
    ```

- And uncomment these lines (delete the leading ```//```) and assign a sensible value according to your Dolibarr installation

    For example :

    - UNIX:
        ```php
        $dolibarr_main_url_root = 'http://localhost/Dolibarr/htdocs';
        $dolibarr_main_document_root = '/var/www/Dolibarr/htdocs';
        $dolibarr_main_url_root_alt = '/custom';
        $dolibarr_main_document_root_alt = '/var/www/Dolibarr/htdocs/custom';
        ```

    - Windows:
        ```php
        $dolibarr_main_url_root = 'http://localhost/Dolibarr/htdocs';
        $dolibarr_main_document_root = 'C:/My Web Sites/Dolibarr/htdocs';
        $dolibarr_main_url_root_alt = '/custom';
        $dolibarr_main_document_root_alt = 'C:/My Web Sites/Dolibarr/htdocs/custom';
        ```

    For more information about the ```conf.php``` file take a look at the conf.php.example file.

- Clone the repository in ```$dolibarr_main_document_root_alt/mymodule```

```sh
git clone git@github.com:Dolibarr/dolibarr-module-template.git mymodule
```

### <a name="final_steps"></a>Final steps

From your browser:

  - Log into Dolibarr as a super-administrator
  - Go to "Setup" -> "Modules"
  - You should now be able to find and enable the module



## Publishing the module
The de-facto standard for publishing and marketing modules for Dolibarr is the [Dolistore](https://www.dolistore.com).  
Templates for required images and texts are [provided](dev/dolistore).  
Check the dedicated [README](dev/dolistore/README.md) for more informations.

-->


Licenses
--------

### Main code

![GPLv3 logo](img/gplv3.png)

GPLv3 or (at your option) any later version.

See [COPYING](COPYING) for more information.

#### Documentation

All texts and readmes.

![GFDL logo](img/gfdl.png)
