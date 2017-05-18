<?php
/* <one line to give the program's name and a brief idea of what it does.>
 * Copyright (C) <year>  <name of author>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * \file    myobject_list.php
 * \ingroup mymodule
 * \brief   Page with list of myobject.
 *
 * List of myobject
 */

//if (! defined('NOREQUIREUSER'))	define('NOREQUIREUSER','1');
//if (! defined('NOREQUIREDB'))		define('NOREQUIREDB','1');
//if (! defined('NOREQUIRESOC'))	define('NOREQUIRESOC','1');
//if (! defined('NOREQUIRETRAN'))	define('NOREQUIRETRAN','1');
//if (! defined('NOCSRFCHECK'))    define('NOCSRFCHECK','1');			// Do not check anti CSRF attack test
//if (! defined('NOSTYLECHECK'))   define('NOSTYLECHECK','1');			// Do not check style html tag into posted data
//if (! defined('NOTOKENRENEWAL')) define('NOTOKENRENEWAL','1');		// Do not check anti POST attack test
//if (! defined('NOREQUIREMENU'))  define('NOREQUIREMENU','1');			// If there is no need to load and show top and left menu
//if (! defined('NOREQUIREHTML'))  define('NOREQUIREHTML','1');			// If we don't need to load the html.form.class.php
//if (! defined('NOREQUIREAJAX'))  define('NOREQUIREAJAX','1');
//if (! defined("NOLOGIN"))        define("NOLOGIN",'1');				// If this page is public (can be called outside logged session)

// Change the following lines to use the correct relative path (../, ../../, etc)

// Load Dolibarr environment
if (false === (@include '../../main.inc.php')) {  // From htdocs directory
	require '../../../main.inc.php'; // From "custom" directory
}

require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
dol_include_once('/mymodule/class/myclass.class.php');

// Load translation files required by the page
$langs->load("mymodule@mymodule");

// Get parameters
$action     = GETPOST('action','alpha');
$massaction = GETPOST('massaction','alpha');
$show_files = GETPOST('show_files','int');
$confirm    = GETPOST('confirm','alpha');
$toselect   = GETPOST('toselect', 'array');

$id			= GETPOST('id','int');
$backtopage = GETPOST('backtopage');
// TODO Add here list of search params
$myparam	= GETPOST('myparam','alpha');

// Load variable for pagination
$limit = GETPOST("limit")?GETPOST("limit","int"):$conf->liste_limit;
$sortfield = GETPOST('sortfield','alpha');
$sortorder = GETPOST('sortorder','alpha');
$page = GETPOST('page','int');
if ($page == -1) { $page = 0; }
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (! $sortfield) $sortfield="t.rowid"; // Set here default search field
if (! $sortorder) $sortorder="ASC";

// Protection if external user
if ($user->societe_id > 0)
{
	//accessforbidden();
}
//$result = restrictedArea($user, 'mymodule', $id);

// Initialize technical object to manage context to save list fields
$contextpage=GETPOST('contextpage','aZ')?GETPOST('contextpage','aZ'):'mymodulelist';

// Initialize technical object to manage hooks. Note that conf->hooks_modules contains array
$hookmanager->initHooks(array('mymodulelist'));
$extrafields = new ExtraFields($db);

// Load object if id or ref is provided as parameter
$object = new MyClass($db);
if (($id > 0 || ! empty($ref)) && $action != 'add') {
	$result = $object->fetch($id, $ref);
	if ($result < 0) dol_print_error($db);
}


/*
 * ACTIONS
 *
 * Put here all code to do according to value of "action" parameter
 */

if (GETPOST('cancel')) { $action='list'; $massaction=''; }
if (! GETPOST('confirmmassaction') && $massaction != 'presend' && $massaction != 'confirm_presend') { $massaction=''; }

$parameters=array();
$reshook=$hookmanager->executeHooks('doActions',$parameters,$object,$action);    // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

if (empty($reshook))
{
    // Selection of new fields
    include DOL_DOCUMENT_ROOT.'/core/actions_changeselectedfields.inc.php';

    // Purge search criteria
    if (GETPOST("button_removefilter_x") || GETPOST("button_removefilter.x") ||GETPOST("button_removefilter")) // All tests are required to be compatible with all browsers
    {
        $search_field1='';
        $search_field2='';
        $search_date_creation='';
        $search_date_update='';
        $toselect='';
        $search_array_options=array();
    }

    // Mass actions
    $objectclass='Skeleton';
    $objectlabel='Skeleton';
    $permtoread = $user->rights->skeleton->read;
    $permtodelete = $user->rights->skeleton->delete;
    $uploaddir = $conf->skeleton->dir_output;
    include DOL_DOCUMENT_ROOT.'/core/actions_massactions.inc.php';
}


/*
 * VIEW
 *
 * Put here all code to build page
 */

llxHeader('', $langs->trans('MyPageName'), '');

$form = new Form($db);

// Put here content of your page
// Example 1: Adding jquery code
print '<script type="text/javascript" language="javascript">
jQuery(document).ready(function() {
	function init_myfunc()
	{
		jQuery("#myid").removeAttr(\'disabled\');
		jQuery("#myid").attr(\'disabled\',\'disabled\');
	}
	init_myfunc();
	jQuery("#mybutton").click(function() {
		init_myfunc();
	});
});
</script>';



// TODO




// Example 2: Adding links to objects
// The class must extend CommonObject for this method to be available
$somethingshown = $form->showLinkedObjectBlock($myobject);

// End of page
llxFooter();
