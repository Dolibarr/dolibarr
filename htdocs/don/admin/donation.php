<?php
/* Copyright (C) 2005-2010  Laurent Destailleur  	<eldy@users.sourceforge.net>
 * Copyright (C) 2012-2015  Juanjo Menent			<jmenent@2byte.es>
 * Copyright (C) 2013-2017  Philippe Grand			<philippe.grand@atoo-net.com>
 * Copyright (C) 2015-2017  Alexandre Spangaro		<aspangaro.dolibarr@gmail.com>
 * Copyright (C) 2015       Benoit Bruchard			<benoitb21@gmail.com>
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
 *      \file       htdocs/don/admin/dons.php
 *		\ingroup    donations
 *		\brief      Page to setup the donation module
 */
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/donation.lib.php';
require_once DOL_DOCUMENT_ROOT . '/don/class/don.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/doleditor.class.php';
if (! empty($conf->accounting->enabled)) require_once DOL_DOCUMENT_ROOT . '/core/class/html.formaccounting.class.php';

// Load translation files required by the page
$langs->loadLangs(array('admin', 'donations', 'accountancy', 'other'));

if (!$user->admin) accessforbidden();

$typeconst=array('yesno','texte','chaine');

$action = GETPOST('action','alpha');
$value = GETPOST('value');
$label = GETPOST('label','alpha');
$scandir = GETPOST('scan_dir','alpha');

$type='donation';


/*
 * Action
 */

if ($action == 'specimen')
{
    $modele=GETPOST('module','alpha');

    $don = new Don($db);
    $don->initAsSpecimen();

    // Search template files
    $dir = DOL_DOCUMENT_ROOT . "/core/modules/dons/";
    $file = $modele.".modules.php";
    if (file_exists($dir.$file))
    {
        $classname = $modele;
        require_once $dir.$file;

        $obj = new $classname($db);

        if ($obj->write_file($don,$langs) > 0)
        {
            header("Location: ".DOL_URL_ROOT."/document.php?modulepart=donation&file=SPECIMEN.html");
            return;
        }
        else
        {
            setEventMessages($obj->error, $obj->errors, 'errors');
            dol_syslog($obj->error, LOG_ERR);
        }
    }
    else
    {
        setEventMessages($langs->trans("ErrorModuleNotFound"), null, 'errors');
        dol_syslog($langs->trans("ErrorModuleNotFound"), LOG_ERR);
    }
}

// Set default model
else if ($action == 'setdoc')
{
	if (dolibarr_set_const($db, "DON_ADDON_MODEL",$value,'chaine',0,'',$conf->entity))
	{
		// The constant that was read before the new set
		// So we go through a variable for a coherent display
		$conf->global->DON_ADDON_MODEL = $value;
	}

	// It enables the model
	$ret = delDocumentModel($value, $type);
	if ($ret > 0)
	{
		$ret = addDocumentModel($value, $type, $label, $scandir);
	}
}

// Activate a model
else if ($action == 'set')
{
	$ret = addDocumentModel($value, $type, $label, $scandir);
}

else if ($action == 'del')
{
	$ret = delDocumentModel($value, $type);
	if ($ret > 0)
	{
        if ($conf->global->DON_ADDON_MODEL == "$value") dolibarr_del_const($db, 'DON_ADDON_MODEL',$conf->entity);
	}
}

// Options
if ($action == 'set_DONATION_ACCOUNTINGACCOUNT')
{
	$account = GETPOST('DONATION_ACCOUNTINGACCOUNT','alpha');

    $res = dolibarr_set_const($db, "DONATION_ACCOUNTINGACCOUNT",$account,'chaine',0,'',$conf->entity);

	if (! $res > 0) $error++;

 	if (! $error)
    {
        setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
    }
    else
    {
        setEventMessages($langs->trans("Error"), null, 'errors');
    }
}

if ($action == 'set_DONATION_MESSAGE')
{
	$freemessage = GETPOST('DONATION_MESSAGE','none');	// No alpha here, we want exact string

    $res = dolibarr_set_const($db, "DONATION_MESSAGE",$freemessage,'chaine',0,'',$conf->entity);

	if (! $res > 0) $error++;

 	if (! $error)
    {
        setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
    }
    else
    {
        setEventMessages($langs->trans("Error"), null, 'errors');
    }
}

// Activate an article
else if ($action == 'setart200') {
	$setart200 = GETPOST('value', 'int');
	$res = dolibarr_set_const($db, "DONATION_ART200", $setart200, 'yesno', 0, '', $conf->entity);
	if (! $res > 0)
		$error ++;

	if (! $error) {
		setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
	} else {
		setEventMessages($langs->trans("Error"), null, 'mesgs');
	}
}
else if ($action == 'setart238') {
	$setart238 = GETPOST('value', 'int');
	$res = dolibarr_set_const($db, "DONATION_ART238", $setart238, 'yesno', 0, '', $conf->entity);
	if (! $res > 0)
		$error ++;

	if (! $error) {
		setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
	} else {
		setEventMessages($langs->trans("Error"), null, 'mesgs');
	}
}
else if ($action == 'setart885') {
	$setart885 = GETPOST('value', 'int');
	$res = dolibarr_set_const($db, "DONATION_ART885", $setart885, 'yesno', 0, '', $conf->entity);
	if (! $res > 0)
		$error ++;

	if (! $error) {
		setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
	} else {
		setEventMessages($langs->trans("Error"), null, 'mesgs');
	}
}

/*
 * View
 */

$dir = "../../core/modules/dons/";
$form=new Form($db);
if (! empty($conf->accounting->enabled)) $formaccounting = new FormAccounting($db);

llxHeader('',$langs->trans("DonationsSetup"),'DonConfiguration');
$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1">'.$langs->trans("BackToModuleList").'</a>';
print load_fiche_titre($langs->trans("DonationsSetup"),$linkback,'title_setup');

$head = donation_admin_prepare_head();

dol_fiche_head($head, 'general', $langs->trans("Donations"), -1, 'payment');


// Document templates
print load_fiche_titre($langs->trans("DonationsModels"), '', '');

// Defined the template definition table
$type='donation';
$def = array();
$sql = "SELECT nom";
$sql.= " FROM ".MAIN_DB_PREFIX."document_model";
$sql.= " WHERE type = '".$type."'";
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

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Name").'</td>';
print '<td>'.$langs->trans("Description").'</td>';
print '<td align="center" width="60">'.$langs->trans("Activated").'</td>';
print '<td align="center" width="60">'.$langs->trans("Default").'</td>';
print '<td align="center" width="80">'.$langs->trans("ShortInfo").'</td>';
print '<td align="center" width="80">'.$langs->trans("Preview").'</td>';
print "</tr>\n";

clearstatcache();

$handle=opendir($dir);

if (is_resource($handle))
{
	while (($file = readdir($handle))!==false)
	{
		if (preg_match('/\.modules\.php$/i',$file))
		{
			$name = substr($file, 0, dol_strlen($file) -12);
			$classname = substr($file, 0, dol_strlen($file) -12);

			require_once $dir.'/'.$file;
			$module=new $classname($db);

			// Show modules according to features level
			if ($module->version == 'development'  && $conf->global->MAIN_FEATURES_LEVEL < 2) continue;
			if ($module->version == 'experimental' && $conf->global->MAIN_FEATURES_LEVEL < 1) continue;

			if ($module->isEnabled())
			{
				print '<tr class="oddeven"><td width=\"100\">';
				echo $module->name;
				print '</td>';
				print '<td>';
				print $module->description;
				print '</td>';

				// Active
				if (in_array($name, $def))
				{
					if ($conf->global->DON_ADDON_MODEL == $name)
					{
						print "<td align=\"center\">\n";
						print img_picto($langs->trans("Enabled"),'switch_on');
						print '</td>';
					}
					else
					{
						print "<td align=\"center\">\n";
						print '<a href="'.$_SERVER["PHP_SELF"].'?action=setdoc&value='.$name.'&amp;scan_dir='.$module->scandir.'&amp;label='.urlencode($module->name).'">'.img_picto($langs->trans("Enabled"),'switch_on').'</a>';
						print '</td>';
					}
				}
				else
				{
					print "<td align=\"center\">\n";
					print '<a href="'.$_SERVER["PHP_SELF"].'?action=set&amp;value='.$name.'&amp;scan_dir='.$module->scandir.'&amp;label='.urlencode($module->name).'">'.img_picto($langs->trans("Disabled"),'switch_off').'</a>';
					print "</td>";
				}

				// Default
				if ($conf->global->DON_ADDON_MODEL == "$name")
				{
					print "<td align=\"center\">";
					print img_picto($langs->trans("Default"),'on');
					print '</td>';
				}
				else
				{
					print "<td align=\"center\">";
					print '<a href="'.$_SERVER["PHP_SELF"].'?action=setdoc&amp;value='.$name.'&amp;scan_dir='.$module->scandir.'&amp;label='.urlencode($module->name).'" alt="'.$langs->trans("Default").'">'.img_picto($langs->trans("Disabled"),'off').'</a>';
					print '</td>';
				}

				// Info
				$htmltooltip =    ''.$langs->trans("Name").': '.$module->name;
				$htmltooltip.='<br>'.$langs->trans("Type").': '.($module->type?$module->type:$langs->trans("Unknown"));
				if ($module->type == 'pdf')
				{
					$htmltooltip.='<br>'.$langs->trans("Width").'/'.$langs->trans("Height").': '.$module->page_largeur.'/'.$module->page_hauteur;
				}
				$htmltooltip.='<br><br><u>'.$langs->trans("FeaturesSupported").':</u>';
				$htmltooltip.='<br>'.$langs->trans("Logo").': '.yn($module->option_logo,1,1);
				$htmltooltip.='<br>'.$langs->trans("MultiLanguage").': '.yn($module->option_multilang,1,1);
				print '<td align="center">';
				print $form->textwithpicto('',$htmltooltip,-1,0);
				print '</td>';

				// Preview
				print '<td align="center">';
				print '<a href="'.$_SERVER["PHP_SELF"].'?action=specimen&module='.$name.'" target="specimen">'.img_object($langs->trans("Preview"),'generic').'</a>';
				print '</td>';

				print "</tr>\n";
			}
		}
	}
	closedir($handle);
}

print '</table><br>';

/*
 *  Params
 */
print load_fiche_titre($langs->trans("Options"), '', '');

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Parameters").'</td>';
print '<td width="60" align="center">'.$langs->trans("Value")."</td>\n";
print '<td></td>';
print "</tr>\n";

print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'" />';
print '<input type="hidden" name="action" value="set_DONATION_ACCOUNTINGACCOUNT" />';

print '<tr class="oddeven">';

print '<td>';
$label = $langs->trans("AccountAccounting");
print '<label for="DONATION_ACCOUNTINGACCOUNT">' . $label . '</label></td>';
print '<td>';
if (! empty($conf->accounting->enabled))
{
	print $formaccounting->select_account($conf->global->DONATION_ACCOUNTINGACCOUNT, 'DONATION_ACCOUNTINGACCOUNT', 1, '', 1, 1);
}
else
{
	print '<input type="text" size="10" id="DONATION_ACCOUNTINGACCOUNT" name="DONATION_ACCOUNTINGACCOUNT" value="' . $conf->global->DONATION_ACCOUNTINGACCOUNT . '">';
}
print '</td><td align="right">';
print '<input type="submit" class="button" value="'.$langs->trans("Modify").'" />';
print "</td></tr>\n";
print '</form>';

print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'" />';
print '<input type="hidden" name="action" value="set_DONATION_MESSAGE" />';

print '<tr class="oddeven"><td colspan="2">';
print $langs->trans("FreeTextOnDonations").' '.img_info($langs->trans("AddCRIfTooLong")).'<br>';
print '<textarea name="DONATION_MESSAGE" class="flat" cols="80">'.$conf->global->DONATION_MESSAGE.'</textarea>';
print '</td><td align="right">';
print '<input type="submit" class="button" value="'.$langs->trans("Modify").'" />';
print "</td></tr>\n";

print "</table>\n";
print '</form>';

/*
 *  French params
 */
if (preg_match('/fr/i',$conf->global->MAIN_INFO_SOCIETE_COUNTRY))
{
	print '<br>';
	print load_fiche_titre($langs->trans("FrenchOptions"), '', '');

	print '<table class="noborder" width="100%">';
	print '<tr class="liste_titre">';
	print '<td colspan="3">' . $langs->trans('Parameters') . '</td>';
	print "</tr>\n";

	print '<tr class="oddeven">';
	print '<td width="80%">' . $langs->trans("DONATION_ART200") . '</td>';
	if (! empty($conf->global->DONATION_ART200)) {
		print '<td align="center" colspan="2"><a href="' . $_SERVER['PHP_SELF'] . '?action=setart200&value=0">';
		print img_picto($langs->trans("Activated"), 'switch_on');
		print '</a></td>';
	} else {
		print '<td align="center" colspan="2"><a href="' . $_SERVER['PHP_SELF'] . '?action=setart200&value=1">';
		print img_picto($langs->trans("Disabled"), 'switch_off');
		print '</a></td>';
	}
	print '</tr>';

	print '<tr class="oddeven">';
	print '<td width="80%">' . $langs->trans("DONATION_ART238") . '</td>';
	if (! empty($conf->global->DONATION_ART238)) {
		print '<td align="center" colspan="2"><a href="' . $_SERVER['PHP_SELF'] . '?action=setart238&value=0">';
		print img_picto($langs->trans("Activated"), 'switch_on');
		print '</a></td>';
	} else {
		print '<td align="center" colspan="2"><a href="' . $_SERVER['PHP_SELF'] . '?action=setart238&value=1">';
		print img_picto($langs->trans("Disabled"), 'switch_off');
		print '</a></td>';
	}
	print '</tr>';

	print '<tr class="oddeven">';
	print '<td width="80%">' . $langs->trans("DONATION_ART885") . '</td>';
	if (! empty($conf->global->DONATION_ART885)) {
		print '<td align="center" colspan="2"><a href="' . $_SERVER['PHP_SELF'] . '?action=setart885&value=0">';
		print img_picto($langs->trans("Activated"), 'switch_on');
		print '</a></td>';
	} else {
		print '<td align="center" colspan="2"><a href="' . $_SERVER['PHP_SELF'] . '?action=setart885&value=1">';
		print img_picto($langs->trans("Disabled"), 'switch_off');
		print '</a></td>';
	}
	print '</tr>';
	print "</table>\n";
}

llxFooter();

$db->close();
