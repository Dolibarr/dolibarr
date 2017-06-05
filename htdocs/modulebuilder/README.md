Dolibarr Module Template (aka My Module)
========================================

This is a full featured module template for Dolibarr.
It's a tool for module developers to kickstart their project and give an hands-on sample of which features Dolibarr has to offer for module development.

If you're not a module developer you have no use for this.

Documentation
-------------

[Module tutorial](http://wiki.dolibarr.org/index.php/Module_development)

[Dolibarr development](http://wiki.dolibarr.org/index.php/Developer_documentation)

### Translations

Dolibarr uses [Transifex](http://transifex.com) to manage it's translations.

This template also contains a sample configuration for Transifex managed translations under the hidden [.tx](.tx) directory.

For more informations, see the [translator's documentation](http://wiki.dolibarr.org/index.php/Translator_documentation).

There is a [Transifex project](http://transifex.com/projects/p/dolibarr-module-template) for this module.

Install
-------

### Manually

- Make sure Dolibarr (>= 3.3.x) is already installed and configured on your workstation or development server.

- In your Dolibarr installation directory, edit the ```htdocs/conf/conf.php``` file

- Find the following lines:
    ```php
    //$dolibarr_main_url_root_alt ...
    //$dolibarr_main_document_root_alt ...
    ```

- Uncomment these lines (delete the leading ```//```) and assign a sensible value according to your Dolibarr installation

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

*Note that for Dolibarr versions before 3.5, the ```$dolibarr_main_url_root_alt``` has to be an absolute path*

- Clone the repository in ```$dolibarr_main_document_root_alt/mymodule```

*(You may have to create the ```htdocs/custom``` directory first if it doesn't exist yet.)*
```sh
git clone git@github.com:Dolibarr/dolibarr-module-template.git mymodule
```

- Install [Composer](https://getcomposer.org) dependencies:
```sh
composer install
```

Follow the [final steps](#final_steps).

### Using [Composer](https://getcomposer.org)
Require this repository from Dolibarr's composer:
```json
{
  "repositories": [
    {
      "type": "vcs",
      "url": "https://github.com/dolibarr/dolibarr-module-template"
    }
  ],
  "require": {
    "dolibarr/mymodule": "dev-master"
  }
}
```

Run
```sh
composer update
```

Follow the [final steps](#final_steps).

### <a name="final_steps"></a>Final steps

From your browser:

  - Log into Dolibarr as a super-administrator
  - Under "Setup" -> "Other setup", set ```MAIN_FEATURES_LEVEL``` to ```2```
  - Go to "Setup" -> "Modules"
  - The module is under one of the tabs
  - You should now be able to enable the new module and start coding ;)

Provided tools
--------------

### Starting a new module

A [script](dev/newmodule.sh) allows you to rename most of the code to your own module name.  
It requires ```find```, ```sed``` and ```rename``` commands on your system.  
Just make sure you provide a CamelCase name.
```sh
./dev/newmodule.sh [NewName]
```

Some work still has to be done manually:
- Rename the directory holding the code
- Maybe rename some other bits (Search for 'my' in filenames and code)
- Update your module ID in the module descriptor
- Update your language files
    - Keywords based on the module ID
    - String referencing the template
- Remove the features you don't plan to use
- Fill the copyright notices at the top of each file
- Add your logo: see [images README](dev/img/README.md) for specifications
- Start a new GIT history 
```
git checkout --orphan [new_branch_name]
```
- Build an awesome module ;)

### Composer scripts

Only the main commands are listed here.  
See the [composer comments](composer-comments.md) or the [composer.json](composer.json) itself for more informations.

#### Check

Run a linter, a PHP compatibility version checker and checks coding style.
```sh
composer check
```

#### Test
  
Run unit and functional tests.
```sh
composer test
```

#### Doc
Build code and user documentation.

#### Release

Run the checks and tests then build a distribution ZIP.
```sh
composer release
```

#### Git hooks

Optional [GIT hooks](https://git-scm.com/book/it/v2/Customizing-Git-Git-Hooks) are provided.
These are just wrappers calling composer scripts.  
They ensure best practices are followed during module development.  

Install:
```sh
composer git_hooks_install
```

Remove:
```sh
composer git_hooks_remove
```

## Publishing the module
The de-facto standard for publishing and marketing modules for Dolibarr is the [Dolistore](https://www.dolistore.com).  
Templates for required images and texts are [provided](dev/dolistore).  
Check the dedicated [README](dev/dolistore/README.md) for more informations.

Contributions
-------------

Feel free to contribute and report defects on our [issue tracker](http://github.com/Dolibarr/dolibarr-module-template/issues).

Licenses
--------

### Main code

![GPLv3 logo](img/gplv3.png)

GPLv3 or (at your option) any later version.

See [COPYING](COPYING) for more information.

### Other Licenses

#### [Parsedown](http://parsedown.org/)

Used to display this README in the module's about page.  
Licensed under MIT.

#### [GNU Licenses logos](https://www.gnu.org/graphics/license-logos.html)

Public domain

#### Documentation

All texts and readmes.

![GFDL logo](img/gfdl.png)
