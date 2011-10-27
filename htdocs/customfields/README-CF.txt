==================================================
*				CUSTOMFIELDS MODULE				 *
*			by Stephen Larroque (lrq3000)		 *
*				   version	1.2.2                *
*               for Dolibarr >= 3.2.0    		 *
*			 release date 2011/10/20			 *
==================================================

===== DESCRIPTION =====

This module will enable the user to create custom fields for the supported modules. You can choose the datatype, the size, the label(s), the possible values, the value by default, and even constraints (links to other tables) and custom sql definitions and custom sql statements!

CustomFields has been made with the intention of being as portable, flexible, modular and reusable as possible, so that it can be adapted to any Dolibarr's module, and to (almost) any user's need (even if something isn't implemented, you can most probably just use a custom sql statement, the rest will be managed automatically, even with custom statements!).

===== INSTALL =====

Just as any Dolibarr's module, just unzip the contents of this package inside your dolibarr's folder (you should be asked to overwrite some files if done right).

ATTENTION: you need a MySQL database with INNODB. With MySQL >= 5.5, INNODB is default, for MySQL < 5.5 it is MyISAM so you'll have to manually switch to InnoDB. PostgreSQL MAY be supported, we just didn't try yet.

===== HOW TO ADD THE SUPPORT OF A NEW MODULE =====

== NEW WAY (simpler)

0/ Preliminary work
You will need 3 things here: the module's table name, the module's context and the module's trigger action(s).

To get these informations, you can take a look inside the code of the module's files you want to implement:
* module's context: search for "callHooks(" without the quotes or take a look at the wiki: http://wiki.dolibarr.org/index.php/Hooks_system
or you can get it by printing it in the /htdocs/customfields/class/actions_customfields.class.php by adding print_r($parameters) and search for $parameters->context
* module's trigger actions: search for "run_triggers(" or take a look at the wiki: http://wiki.dolibarr.org/index.php/Triggers#List_of_known_triggers_actions
* module's table name: either find it by yourself in the database (looks like llx_themodulename) or by printing it in actions_customfields.class.php by adding print_r($parameters) and search for $parameters->table_element

1/ With these values, edit the config file (/htdocs/customfields/conf/conf_customfields.lib.php), particularly the $modulesarray and $triggersarray variables:

A- Add the context and module's table name in the modulesarray:
$modulesarray = array("invoicecard"=>"facture",
                                            "propalcard"=>"propal",
                                            "productcard"=>"product",
                                            "ordercard"=>"commande",
											"yourmodulecontext"=>"yourmoduletablename"); // Edit me to add the support of another module - NOTE: Lowercase only!

B- Add the triggers (there can be several triggers):
$triggersarray = array("order_create"=>"commande",
						"yourmoduletriggeraction1"=>"yourmoduletablename",
						"yourmoduletriggeraction2"=>"yourmoduletablename");

Note: generally you will be looking to implement the _CREATE and _PREBUILDDOC actions.

Done, now the module should be fully supported! If that's not the case, try the following:

- If you can save and create but cannot generate the document, see the 4th step for implementing the PREBUILDDOC trigger.
- If you can neither save nor create the fields, you just see them but changes are not committed when submitting, try to implement the support via the old way (which should work all the time for any case).

== OLD WAY (still supported and more customizable but more complicated)

We will take as an example the way propal module support was added :

0/ Preliminary work : Take a look at the database to see what table is managing this module, and take a look at the php files and class that are managing the logical functions for this module (you can try to use this module in Dolibarr and take a look at the URL to see what php file is called).

1/ Add the module support in customfields (auto management of the customfields schema definitions)
Why: The goal here is to let customfields module know that we want to support and manage custom fields for a new module.

For propales, in /htdocs/customfields/conf/conf_customfields.lib.php, edit $modulesarray from :

$modulesarray = array("invoicecard"=>"facture");
to
$modulesarray = array("invoicecard"=>"facture", "propalcard"=>"propal");

Format: $modulesarray = array($context=>$modulename) // $modulename = $object->table_element

Note : it is very important to understand that the name of the module must be chosen carefully, it must be the name of the table managing the module, not just some random name.
Eg: for the propal module, the table managing the propals is called "llx_propal", so we name the module "propal". We can later on change the label of the tab shown in the customfields module in the langs file.
Eg2: if we had a module named "mymod" with the corresponding sql table "llx_thisismymodule", you should write in $modulesarray("facture","propal","thisismymodule") and not "mymod".

Note2: see the next step to implement a hook (and set the hook root name, if you want to find the hook root name of an already existing module with hooks just search for the callHooks() function).

IMPORTANT: Be careful: do not forget to DISABLE then RENABLE the module in the admin panel to accept the now const values, because these constants are only added to the database when enabling the module.
And please note that the const are updated because there is the remove() function (in the same file) that tells Dolibarr to remove the constants when disabling the module, else Dolibarr would not update the constants if they were not removed first (even if the values changed).

Done !

Result: Now just login into the admin configuration interface of the customfields module to initialize the customfields for this module and you can already add/edit/manage your customfields!
Please try to do so before proceeding to the next step.

Now we will proceed to show them on the creation page of the module :

2/ Show the fields at creation page and in the datasheet, as well as their management and editing (for Dolibarr > 3.2.0)
Why: We need to set where the custom fields are to be shown. Basically you just have to select the place by copy-pasting a few lines, then everything will be automatically managed (printing, creation, edition, deletion, constraints, etc...).

You need to place a hook at all the places where you want the customfields to appear.

If you are trying to implement the customfields support for a core Dolibarr module, chances are that this hook is already implemented (and it's a good idea to read these already implemented codes so that you can see a clear example of what it should be).

If you are implementing CF for a third-party module (your own module?), then you will have to add the following hook to your code:
	// Initialize technical object to manage hooks of thirdparties. Note that conf->hooks_modules contains array array
	include_once(DOL_DOCUMENT_ROOT.'/core/class/hookmanager.class.php');
	$hookmanager=new HookManager($db);
	$hookmanager->callHooks(array('productcard'));
	
	// Insert hooks
	    $parameters=array();
	    $reshook=$hookmanager->executeHooks('formObjectOptions',$parameters,$object,$action);    // Note that $action and $object may have been modified by hook

Note: you only have to instanciate the HookManager once, then you can copy the Insert Hooks part several time in your code.

Now you just have to configure the case in /htdocs/customfields/class/actions_customfields.class.php, in the customfields_print_forms() function, in the big if/elseif block, add a new elseif for your module:
		elseif ($parameters->context == 'propalcard' or $object->table_element == 'propal') {
                $currentmodule = 'propal'; // EDIT ME: var to edit for each module
                $idvar = 'id'; // EDIT ME: the name of the POST or GET variable that contains the id of the object (look at the URL for something like module.php?modid=3&... when you edit a field)
                $rights = 'propale'; // EDIT ME: try first to put it null, then if it doesn't work try to find the right name (search in the same file for something like $user->rights->modname where modname is the string you must put in $rights).
            }

Done!

Result: Now you should see the custom fields in the datasheet or at the creation form, and there should be an edit button on the datasheet to be able to edit the datas.
However, editing a custom field should not work yet. You can now directly jump to the triggers (this is what will manage the saving actions).

2/ Show the fields in the creation page. DEPRECATED - FOR DOLIBARR <= 3.1.x ONLY
Why: The goal here is to find the place where the modules print the creation form, so that we can append our own custom fields at the end (or near the end)

Add the creation code into the php file that creates new propals from nothing : in /htdocs/comm/addpropal.php, search for // Model, then just _above_ copy/paste the following :

	// CustomFields : print fields at creation
    if ($conf->global->MAIN_MODULE_CUSTOMFIELDS) { // if the customfields module is activated...
	$currentmodule = 'propal'; // EDIT THIS: var to edit for each module

	include_once(DOL_DOCUMENT_ROOT.'/customfields/lib/customfields.lib.php');
	customfields_print_creation_form($currentmodule);
    }

Note1: of course you must edit the $currentmodule variable to the value you chose in the first step.
Note2: if you cannot find the place, try to search for $action == 'create' or $action == 'add' and find the right place inside the code (generally before </table> tag). Or you can try to search for the <form tag (without >).

Done !

Result: You should see your customfields in the creation page of the module you're making the support. But you WON'T be able to save the values, nor you will see them in the resulting datasheet (this will be in the next step).
Please try to do so before proceeding to the next step.

Now we will proceed to show them on the main page (datasheet) of the module.

3/ Add the main code required to show and edit the records of customfields. DEPRECATED - FOR DOLIBARR <= 3.1.x ONLY
Why: The goal here is to show the customfields in the main page of the module (the datasheet generally) and permit the edition of the values.

Add the main management code into the php file that manages every propals (the module that show the infos of a propal and enables to edit them) : in /htdocs/comm/propal.php, search for /* Lines and copy paste the following code _above_ :

	// CUSTOMFIELDS : Main form printing and editing functions
	if ($conf->global->MAIN_MODULE_CUSTOMFIELDS) { // if the customfields module is activated...
	$currentmodule = 'propal'; // EDIT ME: var to edit for each module
	$idvar = 'id'; // EDIT ME: the name of the POST or GET variable that contains the id of the object (look at the URL for something like module.php?modid=3&... when you edit a field)
	$rights = 'propale'; // EDIT ME: try first to put it null, then if it doesn't work try to find the right name (search in the same file for something like $user->rights->modname where modname is the string you must put in $rights).

	include_once(DOL_DOCUMENT_ROOT.'/customfields/lib/customfields.lib.php');
	customfields_print_main_form($currentmodule, $object, $action, $user, $idvar, $rights);

	}

Note1: of course you must edit the $currentmodule variable to the value you chose in the first step.
Note2: you must edit the $idvar too with the right post or get variable (look at the URL for something like module.php?modid=3&... when you edit a field).
Note3: if you cannot find the place, try to search for $action == 'edit' and find the right place inside the code. Or you can try to search for the <form tag (without >). Or just above dol_fiche_end()
Note4: if get the following error :
		Warning: Attempt to assign property of non-object in C:\xampp\htdocs\dolibarr\htdocs\customfields\lib\customfields.lib.php on line 114
Then you have to modify the $object variable in the code above to another name (you must find it in the code). Eg: for the products module, one had to use $product instead of $object.
		
Done !

Result: You should now see your customfields in the datasheet, their values, and you should be able to edit them but the edits WON'T be saved.
Please try to do so before proceeding to the next step.

4/ Optional: Add a PREBUILDDOC trigger that will be triggered just prior to generating the document
Why: The goal is to add our customfields to the object just before it is passed to the document generation procedure.
Optional: You need this only if the module generate documents, if not (eg: products module) just pass on.

Open /htdocs/includes/modules/propale/modules_propale.php and search for writefile( function. Just _above_ copy and paste the following code:

// Appel des triggers
include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
$interface=new Interfaces($db);
$result=$interface->run_triggers('PROPAL_PREBUILDDOC',$object,$user,$langs,$conf); // EDIT ME: editi PROPAL to the module's name
if ($result < 0) { $error++; $this->errors=$interface->errors; }
// Fin appel triggers

Done.

Result: Later on, you will be able to use your customfields in your pdf or odt template thank's to this step, but for now it won't work because you need to make the associated trigger (next step).

5/ Add the Triggers, the actions managers.
Why: Triggers are used to synchronize an action to another action. To be as generic as possible, CustomFields module use triggers to activate saving/cloning/any action when the module that is to be supported is doing itself an action. This is a very portable way to synchronize CustomFields actions to any module's actions.

Edit /htdocs/includes/triggers/interface_modCustomFields_SaveFields.class.php :

-> For the creation action, add the following code:
elseif ($action == 'PROPAL_CREATE') { // EDIT ME: edit the PROPAL name into the module's name trigger (see dolibarr's wiki for triggers list)
	dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);

	$action = 'CUSTOMFIELDS_CREATE';
	$object->currentmodule = 'propal'; // EDIT ME: edit this value with your currentmodule value (see the first step)
	return $this->run_trigger($action,$object,$user,$langs,$conf);
}

-> Clone action:
elseif ($action == 'PROPAL_CLONE') { // EDIT ME: edit the PROPAL name into the module's name trigger (see dolibarr's wiki for triggers list)
	dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);

	$action = 'CUSTOMFIELDS_CLONE';
	$object->currentmodule = 'propal'; // EDIT ME: edit this value with your currentmodule value (see the first step)
	$object->origin_id = GETPOST('id'); // EDIT ME: change 'id' into the $idvar value you've used in step 3.
	return $this->run_trigger($action,$object,$user,$langs,$conf);
}

-> Documents generation (PDF) action:
elseif($action == 'PROPAL_PREBUILDDOC') { // EDIT ME: edit the PROPAL name into the module's name trigger (see dolibarr's wiki for triggers list)
	dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);

	$action = 'CUSTOMFIELDS_PREBUILDDOC';
	$object->currentmodule = 'propal'; // EDIT ME: edit this value with your currentmodule value (see the first step)
	return $this->run_trigger($action,$object,$user,$langs,$conf);
}

Note: as you can notice, there is no trigger for modify nor deletion. This is because they are both handled automatically elsewhere : deletion by the SGBD (sql) cascading, modify by the customfields.lib.php file (the customfields_print_main_form() function does all the handling of edition).
Note2: you can find the (almost) full list of dolibarr's triggers at http://wiki.dolibarr.org/index.php/Interfaces_Dolibarr_vers_exterieur or http://wiki.dolibarr.org/index.php/Interfaces_Dolibarr_toward_foreign_systems

Result: You should now have a fully fonctional customfields support : try to edit the values and save them, and try to generate a pdf or odt document.
If things don't go as expected but all previous steps were successful, then proceed onto the next optional steps. Else, if everything works well, you're done.
	
===== PORTING THE CODE AND CHANGES =====
If dolibarr's core files gets updated in the future without including the changes I made to these, you can easily find what codes I added by just searching for "customfields" (without the quotes), because I tried to comment every code I added for this purpose, so you can consider it to be a sort of tag to easily find what have been changed and port the code.

===== HOW TO ADD A NEW DATA TYPE MANAGED NATIVELY =====

Here you will learn how to add the native support for a data type.

1/ Add the data type support in the CustomFields admin page.
Why: to show this data type as a choice in the admin page.

Open /htdocs/admin/customfields.php and search for $sql_datatypes (at the beginning of the file).

Edit the $sql_datatypes to add your own field : the key being the sql data type definition (must be sql valid), the value being the name that will be shown to the user (you can choose whatever you want).
Eg: 'boolean' => $langs->trans("TrueFalseBox"),

Note: you can set a size or value for the sql data type definition.
Eg: 'enum(\'Yes\',\'No\')' => $langs->trans("YesNoBox"), // will produce an enum with the values Yes and No.
Eg2: 'int(11) => $langs->trans("SomeNameYouChoose"), // will produce an int field with size 11 bits

Done.

Result: now the CustomFields module know that you intend to support a new data type, and you can asap use it in the admin page: try to add a custom field with this data type, it should work (if your sql definition is correct). You must now tell it how to manage(edit) it and how to print it.

2/ Manage the new data type (implement the html input field)
Why: CustomFields must know how to manage this particular datatype you've just added.

Open /htdocs/customfields/class/customfields.class.php and edit the showInputField() function. Plenty of examples and comments are provided inside, it should be pretty easy.
As a guide, you should probably take a look below the // Normal non-constrained fields first, these are the simplest data types (above it concerns only constrained fields which is more dynamic and more complicated).

Result: when going to the datasheet of a module supported by CustomFields, try to edit the custom field you created with this data type: you should see the input you've just implemented.

3/ Print correctly the data type (implement a printing function that will best represent the data type when not editing, just viewing the data in the datasheet).
Why: At this stage, your data type should be printed as it is in the database, but you may want to print it differently so that it is more meaningful to a user (eg: for the TrueFalseBox, it's way better to show True or False than 1 or 0).

Open /htdocs/customfields/class/customfields.class.php and edit the printField() function. Comments will guide you.

Result: now your data type prints a meaningful representation of the data in the datasheet.

4/ Optional: translate the name of the data type and the values
Why: CustomFields fully supports multilanguage, so you can easily translate or put a longer description with the langs files.

You can find them at /htdocs/customfields/langs/code_CODE/customfields.lang or customfields-user.lang

===== HOW TO SET/TRANSLATE A LABEL FOR MY OWN CUSTOM FIELD =====

User defined custom fields can easily be labeled or translated using the provided lang file.

Just edit /htdocs/customfields/langs/code_CODE/customfields-user.lang and add inside the variable name of your custom field as show in the admin panel.

Eg: let's say your custom field is named "user_ref", the resulting variable will be "cf_user_ref". In the customfields-user.lang file just add:
cf_user_ref=The label you want. You can even write a very very very long sentence here.

===== HOW TO MAKE A LINKED/CONSTRAINED CUSTOM FIELD =====
Let's you want to make a custom field that let you choose among all the users of Dolibarr.

With CustomFields, that's very easy: at the customfield creation, just select the table you want to link in Constraints. In our example, you'd just have to select "llx_users", and click Create button.

All the rest is done for you, everything is managed automatically.

PowerTip1: if you want your constrained field to show another value than the rowid, just prefix your custom field's name to the name of the remote field you want to show.
Eg: let's say you want to show the name of the users in the llx_users table, not the rowid. Just create a table with the "name_" prefix, for example "name_myref_or_any_other_after_the_prefix" and it will automatically show the name fields instead of the rowid. And don't forget, in the PDF and ODT, you can access all the remote fields, not only name, but firstname, phone number, email, etc..

PowerTip2: What is great is that you are not limited to Dolibarr's tables: if a third-party module or even another software share this same database as Dolibarr, you can select their tables as well and everything will be managed the same way.

PowerTip3: If that's still not enough to satisfy your needs, you can create more complex sql fields by using the Custom SQL field at the creation or update page, the sql statement that you will put there will be executed just after the creation/update of the field, so that you can create view, execute procedures. And the custom field will still be fully managed by CustomFields core without any edit to the core code!

===== HOW TO CREATE COMPLEX CONSTRAINED CUSTOM FIELDS =====

What is nice with CustomFields is that it mainly relies on the SGBD to do all the work. This means that you can edit the database by yourself, and use any SQL statement to deepen the complexity of your setup until it ultimately fits your needs.
To fully manage the database, you have the following choices:
- Either use the included "Custom SQL" field to issue your own statements. It's not very versatile, but it enables you to add some quick constraints (CHECK) or triggers (TRIGGER) without having to directly access to the SGBD.
- With your favourite SGBD manager (eg: phpMyAdmin for MySQL), you can directly edit the customfields tables (appended by _customfields) and the new fields you create will still be automatically managed by CustomFields afterwards. Just remember to leave untouched the first two columns (rowid and fk_someid). You can edit all the rest: add fields, add domain constraints, add triggers, add views, add foreign keys, etc..

===== HOW TO CHANGE THE DEFAULT VARIABLE PREFIX =====
A prefix is automatically added to each custom field's name in the code (not in the database!), to avoid any collision with other core variables or fields in the Dolibarr core code.

By default, the prefix is "cf_", so if you have a custom field named "user_ref" you will get "cf_user_ref".

This behaviour can easily be changed by editing the $varprefix value in /htdocs/customfields/class/customfields.class.php (it's at the beginning of the file, just after "class CustomFields").

===== ARCHITECTURE OF THE CUSTOMFIELDS MODULE =====
Here is a full list of the CustomFields packaged files with a short description (for a more in-depth view just crawl the source files, they are full of comments):

== Core files
files that are necessary for the CustomFields to work, they contains the core functions

/htdocs/admin/customfields.php --- Administrator's configuration panel : this is where you create and manage the custom fields definitions
/htdocs/customfields/class/actions_customfields.class.php --- Hooks class : used to hook into Dolibarr core modules without altering any core file (can be used to hook into your own modules too)
/htdocs/customfields/class/customfields.class.php --- Core class : every database action is made here in this class. You can find some printing functions because they are very generic.
/htdocs/customfields/conf/conf_customfields.lib.php --- Configuration file : contains the main configurable variables to adapt CustomFields to your needs or to expand its support and native sql types.
/htdocs/customfields/langs/code_CODE/customfields.lang --- Core language file : this is where you can translate the admin config panel (data types names, labels, descriptions, etc.)
/htdocs/customfields/langs/code_CODE/customfields-user.lang --- User defined language file : this is where you can store the labels and values of your custom fields (see the related chapter)
/htdocs/customfields/lib/customfields.lib --- Core printing library for records : contains only printing functions, there's no really core functions but it permits to manage the printing of the custom fields records and their editing
/htdocs/customfields/sql/* --- Unused (the tables are created directly via a function in the customfields.class.php)
/htdocs/customfields/includes/triggers/interface_modCustomFields_SaveFields.class --- Core triggers file : this is where the actions on records are managed. This is an interface between other modules and CustomFields management. This is where you must add the actions of other modules you'd want to support (generic customfields triggers actions are provided so you just have to basically do a copy/paste, see the related chapter).
/htdocs/includes/modules/modCustomFields.class --- Dolibarr's module definition file : this is a core file necessary for Dolibarr to recognize the module and to declare the hooks to Dolibarr (but it does not store anything else than meta-informations).
/htdocs/includes/modules/substitutions/functions_customfields.lib.php --- CustomFields substitution class for ODT generation : necessary to support customfields tags in ODT files

== Invoice module support
files that are necessary to support the Invoice module

/htdocs/includes/modules/facture/modules_facture.php --- class managing the PDF template generation for invoices (this is not the template). Just a small edit to add the PREBUILDDOC trigger that is necessary to generate PDF docs with custom fields.
/htdocs/includes/modules/facture/doc/pdf_customfields.modules.php --- example template to show how to print custom fields in a PDF template, not needed

== Propal module support
files that are necessary to support the Propal module

/htdocs/includes/modules/facture/modules_propale.php --- class managing the PDF template generation for propales (this is not the template). Just a small edit to add the PREBUILDDOC trigger that is necessary to generate PDF docs with custom fields.
/htdocs/includes/modules/facture/pdf_propale_customfields.modules.php --- example template to show how to print custom fields in a PDF template, not needed

== Products/Services module support
everything is handled in the hooks and triggers classes of CustomFields.

== Commands module support
everything is handled in the hooks class of CustomFields and config file.

===== HOW TO USE MY CUSTOMFIELDS IN MY PDF OR ODT DOCUMENT =====

== PDF

Nothing is easier ! You can directly access them like any other standard property of the module's object.

* To access the field's value:

$object->variable_name
by default (with the default varprefix of "cf_")
$object->cf_mycustomfield

* To print it with FPDF (the default PDF generation library):

$pdf->MultiCell(0,3, $object->cf_monchamp, 0, 'L'); // printing the customfield

* To print it with beautified formatting (eg: for constained fields or truefalsebox):

// Init and main vars
include_once(DOL_DOCUMENT_ROOT.'/customfields/class/customfields.class.php');
$customfields = new CustomFields($this->db, '');
 
// Getting the beautifully formatted value of the field
$myvalue = $customfields->simpleprintFieldPDF('mycustomfield', $object->cf_mycustomfield);
 
// Printing the field
$pdf->MultiCell(0,3, $myvalue, 0, 'L');

// old way to do it:
// $customfields->printFieldPDF($object->customfields->variable_name, $object->variable_name);

* And if you want to print the multilanguage label of this field :
$mylabel = $customfields->findLabel("mycustomfield", $outputlangs); // where $outputlangs is the language the PDF should be outputted to

== ODT

To use it in an ODT, it is even easier !
Just use the shown variable name in the configuration page as a tag.

Eg: for a customfield named user_ref, you will get the variable name cf_user_ref. In your ODT, just type {cf_user_ref} and you will get the value of this field!

What's more exciting is that it fully supports constrained fields, so that if you have a constraint, it will automatically fetch all the linked values of the referenced tables and you will be able to use them with tags!

Eg: let's take the same customfield as the previous example and say it is constained to the llx_users table. If you type {cf_user_ref} you will only get the id of the user, but maybe you'd prefer to get its firstname, lastname and phone number. You can access all the values of the llx_users table just like any tags. You just have to type {cf_user_ref_name} {cf_user_ref_firstname} {cf_user_ref_user_mobile}
As you can see, you just need to append '_' and the name of the column you want to access to show the corresponding value! Pretty easy heh?

===== HOW TO MANUALLY FETCH CUSTOMFIELDS IN MY OWN CODE AND MODULES =====

One of the main features of the CustomFields module is that it offers a generic way to access, add, edit and view custom fields from your own code. You can easily develop your own modules accepting user's inputs based on CustomFields.

First, you necessarily have to instanciate the CustomFields class:
		// Init and main vars
		include_once(DOL_DOCUMENT_ROOT.'/customfields/class/customfields.class.php');
		$customfields = new CustomFields($this->db, $currentmodule); // where $currentmodule is the current module, you can replace it by '' if you just want to use printing functions and fetchAny.

Secondly, you have the fetch the records:
		$records = $customfields->fetchAll();

Thirdly, you can now print all your records this way:
		if (!is_null($records)) { // verify that we have at least one result
			foreach ($records as $record) { // in our list of records we walk each record
					foreach ($record as $label => $value) { // for each record, we extract the label and the value
							print $label.' has value: '.$value; // Simple printing, with no beautify nor multilingual support
							print $customfields->findLabel($customfields->varprefix.$label).' has value: '.$customfields->simpleprintField($label, $value); // Full printing method with multilingual and beautified printing of the values. Note: We need to add the varprefix for the label to be found.  For printField, we need to provide the meta-informations of the current field to print the value from, depending on these meta-informations the function will choose the right presentation.
					}
			}
		}

Full final code:
		// Init and main vars
		include_once(DOL_DOCUMENT_ROOT.'/customfields/class/customfields.class.php');
		$customfields = new CustomFields($this->db, $currentmodule); // where $currentmodule is the current module, you can replace it by '' if you just want to use printing functions and fetchAny.
		// Fetch all records
		$records = $customfields->fetchAll();
		// Walk and print the records
		if (!is_null($records)) { // verify that we have at least one result
			foreach ($records as $record) { // in our list of records we walk each record
					foreach ($record as $label => $value) { // for each record, we extract the label and the value
							print $label.' has value: '.$value; // Simple printing, with no beautify nor multilingual support
							print $customfields->findLabel($customfields->varprefix.$label).' has value: '.$customfields->simpleprintField($label, $value); // Full printing method with multilingual and beautified printing of the values. Note: We need to add the varprefix for the label to be found.  For printField, we need to provide the meta-informations of the current field to print the value from, depending on these meta-informations the function will choose the right presentation.
					}
			}
		}
Done.

Now, if you want to fetch only a particular record:
		$record = $customfields->fetch($id); // Where id is of course the id of the record to fetch.

		foreach ($record as $label => $value) { // for each record, we extract the label and the value
				print $label.' has value: '.$value; // Simple printing, with no beautify nor multilingual support
				print $customfields->findLabel($customfields->varprefix.$label).' has value: '.$customfields->simpleprintField($label, $value); // Full printing method with multilingual and beautified printing of the values. Note: We need to add the varprefix for the label to be found.  For printField, we need to provide the meta-informations of the current field to print the value from, depending on these meta-informations the function will choose the right presentation.
		}

----

Just for your information (and in case you crawl some of the old parts of the code of this module), here is the old way to do it, with the very same results and performances (just as many sql requests):

Same 1st and 2nd steps as above.

(optionnal: if you want to use the generic beautified printing functions for the values, else if you want to manage the printing by yourselves you can skip this step)
Thirdly, we fetch the custom fields definitions, because we need the meta-data associated to the custom fields structure to properly print the values (particularly important for constrained fields, for the other types it's less important)
		$fields = $customfields->fetchAllCustomFields();

Fourthly, you can now walk on the $records array to get all the records values. For this purpose, the CustomFields provides some functions to print the label with multilingual support as well as for the values:
		foreach ($records as $record) { // in our list of records we walk each record
			foreach ($fields as $field) { // for each record, we walk each field, but we walk in the order of the $fields array so that we can easily pass on the current field's meta informations
				$label = $field->column_name;
				$value = $record->$label;
				print $label.' has value: '.$value; // Simple printing, with no beautify nor multilingual support
				print $customfields->findLabel($customfields->varprefix.$label).' has value: '.$customfields->printField($field, $value); // Full printing method with multilingual and beautified printing of the values. Note: We need to add the varprefix for the label to be found.  For printField, we need to provide the meta-informations of the current field to print the value from, depending on these meta-informations the function will choose the right presentation.
			}
		}

===== TROUBLESHOOTING =====

= Q: I'm trying to edit a constrained customfield parameters in the admin configuration page, but everytime I change the constraint it goes back to None ?
A: This is behaviour is probably due to some of your records containing an illegal value for the new constraint. For example, if you switch your customfield's constraint from your products' table containing 100 products to your you choose the llx_users table containing 2 users, the database won't know what to do with the illegal values higher than 2, so it won't accept the new constraint and set to None.
In this case, just edit yourself the illegal values, either by fixing them or just deleting all the values for this customfields (but in this case you can just delete the customfields and recreate it).

===== TO DO =====

Should do :
* Add an AJAX select box for constained values : when a constrained type is selected and a table is selected, a hidden select box would show up with the list of the fields of this table to choose the values that will be printed as the values for this customfield (eg: for table llx_users you could select the "nom" field and then it would automatically prepend "nom_" to the field's name).
* Add a javascript options values generator for the enum type (a hidden input that would be shown only when DropdownBox type is selected and that would permit to add options by clicking a plus button).
* Add support for other modules
* Add native support for date and datetime fields
* Button to reorder the appearance of fields in editing mode (they currently appear in the same order as they were created)

Known bugs :
* in product and service modules, if you edit a field, the proposals and other fields below won't be shown, you need to refresh the page. This problem resides in Dolibarr I think (since we are simply using a hook).

Never/Maybe one day :
* Add Upload field type (almost useless since we can attach files).
* Add support for repeatable (predefined) invoices (the way it is currently managed makes it very difficult to manage this without making a big exception, adding specific functions in customfields modules that would not at all will be reusable anywhere else, when customfields has been designed to be as generic as possible to support any module and any version of dolibarr, because it's managed by a totally different table while it's still managed by the same module, CustomFields work with the paradigm: one module, one table).
* Add support for clonable propal at creation (same as for repeatable invoices).
* Add variables to access products or services customfields from tags (is it really useful ? How to use them without modifying the lines printing function ?)
