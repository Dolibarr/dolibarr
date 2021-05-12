<?php
/* Copyright (C) 2017	Laurent Destailleur     <eldy@users.sourceforge.net>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *	    \file       htdocs/admin/agenda_reminder.php
 *      \ingroup    agenda
 *      \brief      Page to setup agenda reminder options
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/agenda.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formactions.class.php';

if (!$user->admin)
    accessforbidden();

// Load translation files required by the page
$langs->loadLangs(array("admin","other","agenda"));

<<<<<<< HEAD
$action = GETPOST('action','alpha');
$value = GETPOST('value','alpha');
$param = GETPOST('param','alpha');
$cancel = GETPOST('cancel','alpha');
$scandir = GETPOST('scandir','alpha');
=======
$action = GETPOST('action', 'alpha');
$value = GETPOST('value', 'alpha');
$param = GETPOST('param', 'alpha');
$cancel = GETPOST('cancel', 'alpha');
$scandir = GETPOST('scandir', 'alpha');
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
$type = 'action';


/*
 *	Actions
 */

include DOL_DOCUMENT_ROOT.'/core/actions_setmoduleoptions.inc.php';

<<<<<<< HEAD
if (preg_match('/set_([a-z0-9_\-]+)/i',$action,$reg))
=======
if (preg_match('/set_([a-z0-9_\-]+)/i', $action, $reg))
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
{
	$code=$reg[1];
	$value=(GETPOST($code, 'alpha') ? GETPOST($code, 'alpha') : 1);
	if (dolibarr_set_const($db, $code, $value, 'chaine', 0, '', $conf->entity) > 0)
	{
		Header("Location: ".$_SERVER["PHP_SELF"]);
		exit;
	}
	else
	{
		dol_print_error($db);
	}
}

<<<<<<< HEAD
if (preg_match('/del_([a-z0-9_\-]+)/i',$action,$reg))
=======
if (preg_match('/del_([a-z0-9_\-]+)/i', $action, $reg))
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
{
	$code=$reg[1];
	if (dolibarr_del_const($db, $code, $conf->entity) > 0)
	{
		Header("Location: ".$_SERVER["PHP_SELF"]);
		exit;
	}
	else
	{
		dol_print_error($db);
	}
}
if ($action == 'set')
{
	dolibarr_set_const($db, 'AGENDA_USE_EVENT_TYPE_DEFAULT', GETPOST('AGENDA_USE_EVENT_TYPE_DEFAULT'), 'chaine', 0, '', $conf->entity);
    dolibarr_set_const($db, 'AGENDA_DEFAULT_FILTER_TYPE', GETPOST('AGENDA_DEFAULT_FILTER_TYPE'), 'chaine', 0, '', $conf->entity);
    dolibarr_set_const($db, 'AGENDA_DEFAULT_FILTER_STATUS', GETPOST('AGENDA_DEFAULT_FILTER_STATUS'), 'chaine', 0, '', $conf->entity);
	dolibarr_set_const($db, 'AGENDA_DEFAULT_VIEW', GETPOST('AGENDA_DEFAULT_VIEW'), 'chaine', 0, '', $conf->entity);
}
<<<<<<< HEAD
else if ($action == 'specimen')  // For orders
{
    $modele=GETPOST('module','alpha');
=======
elseif ($action == 'specimen')  // For orders
{
    $modele=GETPOST('module', 'alpha');
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9

    $commande = new CommandeFournisseur($db);
    $commande->initAsSpecimen();
    $commande->thirdparty=$specimenthirdparty;

    // Search template files
    $file=''; $classname=''; $filefound=0;
<<<<<<< HEAD
    $dirmodels=array_merge(array('/'),(array) $conf->modules_parts['models']);
    foreach($dirmodels as $reldir)
    {
    	$file=dol_buildpath($reldir."core/modules/action/doc/pdf_".$modele.".modules.php",0);
=======
    $dirmodels=array_merge(array('/'), (array) $conf->modules_parts['models']);
    foreach($dirmodels as $reldir)
    {
    	$file=dol_buildpath($reldir."core/modules/action/doc/pdf_".$modele.".modules.php", 0);
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
    	if (file_exists($file))
    	{
    		$filefound=1;
    		$classname = "pdf_".$modele;
    		break;
    	}
    }

    if ($filefound)
    {
    	require_once $file;

<<<<<<< HEAD
    	$module = new $classname($db,$commande);

    	if ($module->write_file($commande,$langs) > 0)
=======
    	$module = new $classname($db, $commande);

    	if ($module->write_file($commande, $langs) > 0)
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
    	{
    		header("Location: ".DOL_URL_ROOT."/document.php?modulepart=action&file=SPECIMEN.pdf");
    		return;
    	}
    	else
    	{
    		setEventMessages($module->error, $module->errors, 'errors');
    		dol_syslog($module->error, LOG_ERR);
    	}
    }
    else
    {
    	setEventMessages($langs->trans("ErrorModuleNotFound"), null, 'errors');
    	dol_syslog($langs->trans("ErrorModuleNotFound"), LOG_ERR);
    }
}

// Activate a model
<<<<<<< HEAD
else if ($action == 'setmodel')
=======
elseif ($action == 'setmodel')
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
{
	//print "sssd".$value;
	$ret = addDocumentModel($value, $type, $label, $scandir);
}

<<<<<<< HEAD
else if ($action == 'del')
=======
elseif ($action == 'del')
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
{
	$ret = delDocumentModel($value, $type);
	if ($ret > 0)
	{
<<<<<<< HEAD
        if ($conf->global->ACTION_EVENT_ADDON_PDF == "$value") dolibarr_del_const($db, 'ACTION_EVENT_ADDON_PDF',$conf->entity);
=======
        if ($conf->global->ACTION_EVENT_ADDON_PDF == "$value") dolibarr_del_const($db, 'ACTION_EVENT_ADDON_PDF', $conf->entity);
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	}
}

// Set default model
<<<<<<< HEAD
else if ($action == 'setdoc')
{
	if (dolibarr_set_const($db, "ACTION_EVENT_ADDON_PDF",$value,'chaine',0,'',$conf->entity))
=======
elseif ($action == 'setdoc')
{
	if (dolibarr_set_const($db, "ACTION_EVENT_ADDON_PDF", $value, 'chaine', 0, '', $conf->entity))
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	{
		// La constante qui a ete lue en avant du nouveau set
		// on passe donc par une variable pour avoir un affichage coherent
		$conf->global->ACTION_EVENT_ADDON_PDF = $value;
	}

	// On active le modele
	$ret = delDocumentModel($value, $type);
	if ($ret > 0)
	{
		$ret = addDocumentModel($value, $type, $label, $scandir);
	}
}


/**
 * View
 */

$formactions=new FormActions($db);
<<<<<<< HEAD
$dirmodels=array_merge(array('/'),(array) $conf->modules_parts['models']);
llxHeader();

$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1">'.$langs->trans("BackToModuleList").'</a>';
print load_fiche_titre($langs->trans("AgendaSetup"),$linkback,'title_setup');
=======
$dirmodels=array_merge(array('/'), (array) $conf->modules_parts['models']);
llxHeader();

$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1">'.$langs->trans("BackToModuleList").'</a>';
print load_fiche_titre($langs->trans("AgendaSetup"), $linkback, 'title_setup');
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9



$head=agenda_prepare_head();

dol_fiche_head($head, 'reminders', $langs->trans("Agenda"), -1, 'action');

print '<form action="'.$_SERVER["PHP_SELF"].'" name="agenda">';
print '<input type="hidden" name="action" value="set">';

print '<table class="noborder allwidth">'."\n";
print '<tr class="liste_titre">'."\n";
print '<td>'.$langs->trans("Parameters").'</td>'."\n";
<<<<<<< HEAD
print '<td align="center">&nbsp;</td>'."\n";
print '<td align="right">'.$langs->trans("Value").'</td>'."\n";
=======
print '<td class="center">&nbsp;</td>'."\n";
print '<td class="right">'.$langs->trans("Value").'</td>'."\n";
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
print '</tr>'."\n";


// AGENDA REMINDER EMAIL
<<<<<<< HEAD
if ($conf->global->MAIN_FEATURES_LEVEL > 0)
{
	print '<tr class="oddeven">'."\n";
	print '<td>'.$langs->trans('AGENDA_REMINDER_EMAIL', $langs->transnoentities("Module2300Name")).'</td>'."\n";
	print '<td align="center">&nbsp;</td>'."\n";
	print '<td align="right">'."\n";

	if (empty($conf->global->AGENDA_REMINDER_EMAIL)) {
		print '<a href="'.$_SERVER['PHP_SELF'].'?action=set_AGENDA_REMINDER_EMAIL">'.img_picto($langs->trans('Disabled'),'switch_off').'</a>';
		print '</td></tr>'."\n";
	} else {
		print '<a href="'.$_SERVER['PHP_SELF'].'?action=del_AGENDA_REMINDER_EMAIL">'.img_picto($langs->trans('Enabled'),'switch_on').'</a>';
=======
if ($conf->global->MAIN_FEATURES_LEVEL == 2)
{
	print '<tr class="oddeven">'."\n";
	print '<td>'.$langs->trans('AGENDA_REMINDER_EMAIL', $langs->transnoentities("Module2300Name")).'</td>'."\n";
	print '<td class="center">&nbsp;</td>'."\n";
	print '<td class="right">'."\n";

	if (empty($conf->global->AGENDA_REMINDER_EMAIL)) {
		print '<a href="'.$_SERVER['PHP_SELF'].'?action=set_AGENDA_REMINDER_EMAIL">'.img_picto($langs->trans('Disabled'), 'switch_off').'</a>';
		print '</td></tr>'."\n";
	} else {
		print '<a href="'.$_SERVER['PHP_SELF'].'?action=del_AGENDA_REMINDER_EMAIL">'.img_picto($langs->trans('Enabled'), 'switch_on').'</a>';
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
		print '</td></tr>'."\n";
	}
}

// AGENDA REMINDER BROWSER
<<<<<<< HEAD
if ($conf->global->MAIN_FEATURES_LEVEL > 0)
{
    print '<tr class="oddeven">'."\n";
    print '<td>'.$langs->trans('AGENDA_REMINDER_BROWSER').'</td>'."\n";
    print '<td align="center">&nbsp;</td>'."\n";
    print '<td align="right">'."\n";

    if (empty($conf->global->AGENDA_REMINDER_BROWSER)) {
        print '<a href="'.$_SERVER['PHP_SELF'].'?action=set_AGENDA_REMINDER_BROWSER">'.img_picto($langs->trans('Disabled'),'switch_off').'</a>';
        print '</td></tr>'."\n";
    } else {
        print '<a href="'.$_SERVER['PHP_SELF'].'?action=del_AGENDA_REMINDER_BROWSER">'.img_picto($langs->trans('Enabled'),'switch_on').'</a>';
=======
if ($conf->global->MAIN_FEATURES_LEVEL == 2)
{
    print '<tr class="oddeven">'."\n";
    print '<td>'.$langs->trans('AGENDA_REMINDER_BROWSER').'</td>'."\n";
    print '<td class="center">&nbsp;</td>'."\n";
    print '<td class="right">'."\n";

    if (empty($conf->global->AGENDA_REMINDER_BROWSER)) {
        print '<a href="'.$_SERVER['PHP_SELF'].'?action=set_AGENDA_REMINDER_BROWSER">'.img_picto($langs->trans('Disabled'), 'switch_off').'</a>';
        print '</td></tr>'."\n";
    } else {
        print '<a href="'.$_SERVER['PHP_SELF'].'?action=del_AGENDA_REMINDER_BROWSER">'.img_picto($langs->trans('Enabled'), 'switch_on').'</a>';
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
        print '</td></tr>'."\n";

        print '<tr class="oddeven">'."\n";
        print '<td>'.$langs->trans('AGENDA_REMINDER_BROWSER_SOUND').'</td>'."\n";
<<<<<<< HEAD
        print '<td align="center">&nbsp;</td>'."\n";
        print '<td align="right">'."\n";

        if (empty($conf->global->AGENDA_REMINDER_BROWSER_SOUND)) {
            print '<a href="'.$_SERVER['PHP_SELF'].'?action=set_AGENDA_REMINDER_BROWSER_SOUND">'.img_picto($langs->trans('Disabled'),'switch_off').'</a>';
        } else {
            print '<a href="'.$_SERVER['PHP_SELF'].'?action=del_AGENDA_REMINDER_BROWSER_SOUND">'.img_picto($langs->trans('Enabled'),'switch_on').'</a>';
=======
        print '<td class="center">&nbsp;</td>'."\n";
        print '<td class="right">'."\n";

        if (empty($conf->global->AGENDA_REMINDER_BROWSER_SOUND)) {
            print '<a href="'.$_SERVER['PHP_SELF'].'?action=set_AGENDA_REMINDER_BROWSER_SOUND">'.img_picto($langs->trans('Disabled'), 'switch_off').'</a>';
        } else {
            print '<a href="'.$_SERVER['PHP_SELF'].'?action=del_AGENDA_REMINDER_BROWSER_SOUND">'.img_picto($langs->trans('Enabled'), 'switch_on').'</a>';
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
        }

        print '</td></tr>'."\n";
    }
}

print '</table>';

dol_fiche_end();

print '<div class="center"><input class="button" type="submit" name="save" value="'.dol_escape_htmltag($langs->trans("Save")).'"></div>';

print '</form>';

print "<br>";

<<<<<<< HEAD
llxFooter();

=======
// End of page
llxFooter();
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
$db->close();
