# DOPPELUNGENENTDECKEN FOR [DOLIBARR ERP CRM](https://www.dolibarr.org)

## Features

Finds double entries in thirdparties.

Other external modules are available on [Dolistore.com](https://www.dolistore.com).

## Translations

Translations can be completed manually by editing files into directories *langs*.

## Installation

### From the ZIP file and GUI interface

- If you get the module in a zip file (like when downloading it from the market place [Dolistore](https://www.dolistore.com)), go into
menu ```Home - Setup - Modules - Deploy external module``` and upload the zip file.

Note: If this screen tell you there is no custom directory, check your setup is correct:

- In your Dolibarr installation directory, edit the ```htdocs/conf/conf.php``` file and check that following lines are not commented:

    ```php
    //$dolibarr_main_url_root_alt ...
    //$dolibarr_main_document_root_alt ...
    ```

- Uncomment them if necessary (delete the leading ```//```) and assign a sensible value according to your Dolibarr installation

    For example :

    - UNIX:
        ```php
        $dolibarr_main_url_root_alt = '/custom';
        $dolibarr_main_document_root_alt = '/var/www/Dolibarr/htdocs/custom';
        ```

    - Windows:
        ```php
        $dolibarr_main_url_root_alt = '/custom';
        $dolibarr_main_document_root_alt = 'C:/My Web Sites/Dolibarr/htdocs/custom';
        ```

<!--### From a GIT repository

- Clone the repository in ```$dolibarr_main_document_root_alt/doppelungenentdecken```

```sh
cd ....../custom
git clone git@github.com:gitlogin/doppelungenentdecken.git doppelungenentdecken
```
-->
### <a name="final_steps"></a>Final steps

In file societe/card.php add the following:
 - Before the first occurence of:
    ```php 
        if ($action == 'update') {
    ```
   add following code, so it looks like this:
    ```php
    if (!$error) {
     $parameters=array('name' => GETPOST('name'), 'col' => 'nom');
     $reshook=$hookmanager->executeHooks('findDupl',$parameters,$object,$action); 
     if ($action == 'update') {
    ```
- Add parameters at the end of the URLs in the else part of ```  if (!empty($backtopage)) { ``` so it looks like this
    ```php 
    $url = $_SERVER["PHP_SELF"]."?socid=".$object->id."&showDuplWarning=".$object->showwarning; // Old method
    if (($object->client == 1 || $object->client == 3) && empty($conf->global->SOCIETE_DISABLE_CUSTOMERS)) $url = DOL_URL_ROOT."/comm/card.php?socid=".$object->id."&showDuplWarning=".$object->showwarning;
    ```
- After ``` print dol_get_fiche_end(); ``` add
    ```php
  	if($_GET['showDuplWarning'] == "1") {
  		echo "<script>showWarning();</script>";
  	}
    ```
From your browser:

  - Log into Dolibarr as a super-administrator
  - Go to "Setup" -> "Modules"
  - You should now be able to find and enable the module



## Licenses

### Main code

GPLv3 or (at your option) any later version. See file COPYING for more information.

### Documentation

All texts and readmes are licensed under GFDL.
