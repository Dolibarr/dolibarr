<?php
/* Copyright (C) 2008-2016	Laurent Destailleur     <eldy@users.sourceforge.net>
<<<<<<< HEAD
 * Copyright (C) 2011		Regis Houssin           <regis.houssin@capnetworks.com>
=======
 * Copyright (C) 2011		Regis Houssin           <regis.houssin@inodbox.com>
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
 * Copyright (C) 2011-2017  Juanjo Menent           <jmenent@2byte.es>
 * Copyright (C) 2015		Jean-François Ferry	    <jfefe@aternatik.fr>
 * Copyright (C) 2016		Charlie Benke		    <charlie@patas-monkey.com>
 * Copyright (C) 2017       Open-DSI                <support@open-dsi.fr>
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
 *	    \file       htdocs/admin/agenda_other.php
 *      \ingroup    agenda
 *      \brief      Autocreate actions for agenda module setup page
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/agenda.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formactions.class.php';

if (!$user->admin)
    accessforbidden();

// Load translation files required by the page
<<<<<<< HEAD
$langs->loadLangs(array('admin', 'other', 'agenda'));

$action = GETPOST('action','alpha');
$value = GETPOST('value','alpha');
$param = GETPOST('param','alpha');
$cancel = GETPOST('cancel','alpha');
$scandir = GETPOST('scan_dir','alpha');
=======
$langs->loadLangs(array('admin', 'other', 'agenda', 'users'));

$action = GETPOST('action', 'alpha');
$value = GETPOST('value', 'alpha');
$param = GETPOST('param', 'alpha');
$cancel = GETPOST('cancel', 'alpha');
$scandir = GETPOST('scan_dir', 'alpha');
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

$wikihelp='EN:Module_Agenda_En|FR:Module_Agenda|ES:Módulo_Agenda';
llxHeader('', $langs->trans("AgendaSetup"),$wikihelp);

$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1">'.$langs->trans("BackToModuleList").'</a>';
print load_fiche_titre($langs->trans("AgendaSetup"),$linkback,'title_setup');
=======
$dirmodels=array_merge(array('/'), (array) $conf->modules_parts['models']);

$wikihelp='EN:Module_Agenda_En|FR:Module_Agenda|ES:Módulo_Agenda';
llxHeader('', $langs->trans("AgendaSetup"), $wikihelp);

$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1">'.$langs->trans("BackToModuleList").'</a>';
print load_fiche_titre($langs->trans("AgendaSetup"), $linkback, 'title_setup');
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9



$head=agenda_prepare_head();

dol_fiche_head($head, 'other', $langs->trans("Agenda"), -1, 'action');


/*
 *  Documents models for supplier orders
 */


// Define array def of models
$def = array();

$sql = "SELECT nom";
$sql.= " FROM ".MAIN_DB_PREFIX."document_model";
$sql.= " WHERE type = 'action'";
$sql.= " AND entity = ".$conf->entity;

$resql=$db->query($sql);
if ($resql)
{
    $i = 0;
    $num_rows=$db->num_rows($resql);
    while ($i < $num_rows)
    {
        $array = $db->fetch_array($resql);
        array_push($def, $array[0]);
        $i++;
    }
}
else
{
    dol_print_error($db);
}

if ($conf->global->MAIN_FEATURES_LEVEL >= 2)
{
<<<<<<< HEAD
    print load_fiche_titre($langs->trans("AgendaModelModule"),'','');
=======
    print load_fiche_titre($langs->trans("AgendaModelModule"), '', '');
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9

    print '<table class="noborder" width="100%">'."\n";
    print '<tr class="liste_titre">'."\n";
    print '<td width="100">'.$langs->trans("Name").'</td>'."\n";
    print '<td>'.$langs->trans("Description").'</td>'."\n";
<<<<<<< HEAD
    print '<td align="center" width="60">'.$langs->trans("Status").'</td>'."\n";
    print '<td align="center" width="60">'.$langs->trans("Default").'</td>'."\n";
    print '<td align="center" width="40">'.$langs->trans("ShortInfo").'</td>';
    print '<td align="center" width="40">'.$langs->trans("Preview").'</td>';
=======
    print '<td class="center" width="60">'.$langs->trans("Status").'</td>'."\n";
    print '<td class="center" width="60">'.$langs->trans("Default").'</td>'."\n";
    print '<td class="center" width="40">'.$langs->trans("ShortInfo").'</td>';
    print '<td class="center" width="40">'.$langs->trans("Preview").'</td>';
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
    print '</tr>'."\n";

    clearstatcache();

    foreach ($dirmodels as $reldir)
    {
    	$dir = dol_buildpath($reldir."core/modules/action/doc/");

        if (is_dir($dir))
        {
            $handle=opendir($dir);
            if (is_resource($handle))
            {
                while (($file = readdir($handle))!==false)
                {
<<<<<<< HEAD
                    if (preg_match('/\.modules\.php$/i',$file) && preg_match('/^(pdf_|doc_)/',$file))
=======
                    if (preg_match('/\.modules\.php$/i', $file) && preg_match('/^(pdf_|doc_)/', $file))
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
                    {
            			$name = substr($file, 4, dol_strlen($file) -16);
            			$classname = substr($file, 0, dol_strlen($file) -12);

            			require_once $dir.'/'.$file;
            			$module = new $classname($db, new ActionComm($db));

            			print '<tr class="oddeven">'."\n";
            			print "<td>";
            			print (empty($module->name)?$name:$module->name);
            			print "</td>\n";
            			print "<td>\n";
            			require_once $dir.$file;
<<<<<<< HEAD
            			$module = new $classname($db,$specimenthirdparty);
            			if (method_exists($module,'info'))
=======
            			$module = new $classname($db, $specimenthirdparty);
            			if (method_exists($module, 'info'))
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
            				print $module->info($langs);
            			else
            				print $module->description;
            			print "</td>\n";

            			// Active
            			if (in_array($name, $def))
            			{

<<<<<<< HEAD
            			print '<td align="center">'."\n";
            			if ($conf->global->ACTION_EVENT_ADDON_PDF != "$name")
            			{
            				print '<a href="'.$_SERVER["PHP_SELF"].'?action=del&amp;value='.$name.'&amp;scan_dir='.$module->scandir.'&amp;label='.urlencode($module->name).'&amp;type=action">';
            				print img_picto($langs->trans("Enabled"),'switch_on');
=======
            			print '<td class="center">'."\n";
            			if ($conf->global->ACTION_EVENT_ADDON_PDF != "$name")
            			{
            				print '<a href="'.$_SERVER["PHP_SELF"].'?action=del&amp;value='.$name.'&amp;scan_dir='.$module->scandir.'&amp;label='.urlencode($module->name).'&amp;type=action">';
            				print img_picto($langs->trans("Enabled"), 'switch_on');
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
            				print '</a>';
            			}
            			else
            			{
<<<<<<< HEAD
            				print img_picto($langs->trans("Enabled"),'switch_on');
=======
            				print img_picto($langs->trans("Enabled"), 'switch_on');
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
            			}
            				print "</td>";
            			}
            			else
            			{
<<<<<<< HEAD
            				print '<td align="center">'."\n";
            				print '<a href="'.$_SERVER["PHP_SELF"].'?action=setmodel&amp;value='.$name.'&amp;scan_dir='.$module->scandir.'&amp;label='.urlencode($module->name).'&amp;type=action">'.img_picto($langs->trans("Disabled"),'switch_off').'</a>';
=======
            				print '<td class="center">'."\n";
            				print '<a href="'.$_SERVER["PHP_SELF"].'?action=setmodel&amp;value='.$name.'&amp;scan_dir='.$module->scandir.'&amp;label='.urlencode($module->name).'&amp;type=action">'.img_picto($langs->trans("Disabled"), 'switch_off').'</a>';
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
            				print "</td>";
            			}

            			// Default
<<<<<<< HEAD
            			print '<td align="center">';
            			if ($conf->global->ACTION_EVENT_ADDON_PDF == "$name")
            			{
            				print img_picto($langs->trans("Default"),'on');
            			}
            			else
            			{
            				print '<a href="'.$_SERVER["PHP_SELF"].'?action=setdoc&amp;value='.$name.'&amp;scan_dir='.$module->scandir.'&amp;label='.urlencode($module->name).'&amp;type=action"" alt="'.$langs->trans("Default").'">'.img_picto($langs->trans("Disabled"),'off').'</a>';
=======
            			print '<td class="center">';
            			if ($conf->global->ACTION_EVENT_ADDON_PDF == "$name")
            			{
            				print img_picto($langs->trans("Default"), 'on');
            			}
            			else
            			{
            				print '<a href="'.$_SERVER["PHP_SELF"].'?action=setdoc&amp;value='.$name.'&amp;scan_dir='.$module->scandir.'&amp;label='.urlencode($module->name).'&amp;type=action"" alt="'.$langs->trans("Default").'">'.img_picto($langs->trans("Disabled"), 'off').'</a>';
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
            			}
            			print '</td>';

            			// Info
            			$htmltooltip =    ''.$langs->trans("Name").': '.$module->name;
            			$htmltooltip.='<br>'.$langs->trans("Type").': '.($module->type?$module->type:$langs->trans("Unknown"));
            			$htmltooltip.='<br>'.$langs->trans("Width").'/'.$langs->trans("Height").': '.$module->page_largeur.'/'.$module->page_hauteur;
            			$htmltooltip.='<br><br><u>'.$langs->trans("FeaturesSupported").':</u>';
<<<<<<< HEAD
            			$htmltooltip.='<br>'.$langs->trans("Logo").': '.yn($module->option_logo,1,1);
            			print '<td align="center">';
            			print $form->textwithpicto('',$htmltooltip,1,0);
            			print '</td>';
            			print '<td align="center">';
            			print '<a href="'.$_SERVER["PHP_SELF"].'?action=specimen&amp;module='.$name.'">'.img_object($langs->trans("Preview"),'order').'</a>';
=======
            			$htmltooltip.='<br>'.$langs->trans("Logo").': '.yn($module->option_logo, 1, 1);
            			print '<td class="center">';
            			print $form->textwithpicto('', $htmltooltip, 1, 0);
            			print '</td>';
            			print '<td class="center">';
            			print '<a href="'.$_SERVER["PHP_SELF"].'?action=specimen&amp;module='.$name.'">'.img_object($langs->trans("Preview"), 'order').'</a>';
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
            			print '</td>';

            			print "</tr>\n";
                    }
                }
                closedir($handle);
            }
        }
    }
    print '</table><br>';
}

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

// Manual or automatic

print '<tr class="oddeven">'."\n";
print '<td>'.$langs->trans("AGENDA_USE_EVENT_TYPE").'</td>'."\n";
<<<<<<< HEAD
print '<td align="center">&nbsp;</td>'."\n";
print '<td align="right">'."\n";
//print ajax_constantonoff('AGENDA_USE_EVENT_TYPE');	Do not use ajax here, we need to reload page to change other combo list
if (empty($conf->global->AGENDA_USE_EVENT_TYPE))
{
	print '<a href="'.$_SERVER['PHP_SELF'].'?action=set_AGENDA_USE_EVENT_TYPE">'.img_picto($langs->trans("Disabled"),'switch_off').'</a>';
}
else
{
	print '<a href="'.$_SERVER['PHP_SELF'].'?action=del_AGENDA_USE_EVENT_TYPE">'.img_picto($langs->trans("Enabled"),'switch_on').'</a>';
=======
print '<td class="center">&nbsp;</td>'."\n";
print '<td class="right">'."\n";
//print ajax_constantonoff('AGENDA_USE_EVENT_TYPE');	Do not use ajax here, we need to reload page to change other combo list
if (empty($conf->global->AGENDA_USE_EVENT_TYPE))
{
	print '<a href="'.$_SERVER['PHP_SELF'].'?action=set_AGENDA_USE_EVENT_TYPE">'.img_picto($langs->trans("Disabled"), 'switch_off').'</a>';
}
else
{
	print '<a href="'.$_SERVER['PHP_SELF'].'?action=del_AGENDA_USE_EVENT_TYPE">'.img_picto($langs->trans("Enabled"), 'switch_on').'</a>';
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
}
print '</td></tr>'."\n";

// AGENDA_DEFAULT_VIEW
print '<tr class="oddeven">'."\n";
<<<<<<< HEAD
print '<td>'.$langs->trans("AGENDA_DEFAULT_VIEW").'</td>'."\n";
print '<td align="center">&nbsp;</td>'."\n";
print '<td align="right">'."\n";
$tmplist=array('show_list'=>$langs->trans("ViewList"), 'show_month'=>$langs->trans("ViewCal"), 'show_week'=>$langs->trans("ViewWeek"), 'show_day'=>$langs->trans("ViewDay"), 'show_peruser'=>$langs->trans("ViewPerUser"));
=======
$htmltext=$langs->trans("ThisValueCanOverwrittenOnUserLevel", $langs->transnoentitiesnoconv("UserGUISetup"));
print '<td>'.$form->textwithpicto($langs->trans("AGENDA_DEFAULT_VIEW"), $htmltext).'</td>'."\n";
print '<td class="center">&nbsp;</td>'."\n";
print '<td class="right">'."\n";
$tmplist=array(''=>'&nbsp;', 'show_list'=>$langs->trans("ViewList"), 'show_month'=>$langs->trans("ViewCal"), 'show_week'=>$langs->trans("ViewWeek"), 'show_day'=>$langs->trans("ViewDay"), 'show_peruser'=>$langs->trans("ViewPerUser"));
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
print $form->selectarray('AGENDA_DEFAULT_VIEW', $tmplist, $conf->global->AGENDA_DEFAULT_VIEW);
print '</td></tr>'."\n";

if (! empty($conf->global->AGENDA_USE_EVENT_TYPE))
{

    print '<!-- AGENDA_USE_EVENT_TYPE_DEFAULT -->';
    print '<tr class="oddeven">'."\n";
    print '<td>'.$langs->trans("AGENDA_USE_EVENT_TYPE_DEFAULT").'</td>'."\n";
<<<<<<< HEAD
    print '<td align="center">&nbsp;</td>'."\n";
    print '<td align="right" class="nowrap">'."\n";
=======
    print '<td class="center">&nbsp;</td>'."\n";
    print '<td class="right nowrap">'."\n";
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
    $formactions->select_type_actions($conf->global->AGENDA_USE_EVENT_TYPE_DEFAULT, "AGENDA_USE_EVENT_TYPE_DEFAULT", 'systemauto', 0, 1);
    print '</td></tr>'."\n";
}

// AGENDA_DEFAULT_FILTER_TYPE
print '<tr class="oddeven">'."\n";
print '<td>'.$langs->trans("AGENDA_DEFAULT_FILTER_TYPE").'</td>'."\n";
<<<<<<< HEAD
print '<td align="center">&nbsp;</td>'."\n";
print '<td align="right" class="nowrap">'."\n";
=======
print '<td class="center">&nbsp;</td>'."\n";
print '<td class="right nowrap">'."\n";
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
$formactions->select_type_actions($conf->global->AGENDA_DEFAULT_FILTER_TYPE, "AGENDA_DEFAULT_FILTER_TYPE", '', (empty($conf->global->AGENDA_USE_EVENT_TYPE) ? 1 : -1), 1);
print '</td></tr>'."\n";

// AGENDA_DEFAULT_FILTER_STATUS
// TODO Remove to use the default generic feature
print '<tr class="oddeven">'."\n";
print '<td>'.$langs->trans("AGENDA_DEFAULT_FILTER_STATUS").'</td>'."\n";
<<<<<<< HEAD
print '<td align="center">&nbsp;</td>'."\n";
print '<td align="right">'."\n";
=======
print '<td class="center">&nbsp;</td>'."\n";
print '<td class="right">'."\n";
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
$formactions->form_select_status_action('agenda', $conf->global->AGENDA_DEFAULT_FILTER_STATUS, 1, 'AGENDA_DEFAULT_FILTER_STATUS', 1, 2, 'minwidth100');
print '</td></tr>'."\n";

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
