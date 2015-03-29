<?php
/* Copyright (C) 2005-2010  Laurent Destailleur  	<eldy@users.sourceforge.net>
 * Copyright (C) 2012-2013	Juanjo Menent			<jmenent@2byte.es>
 * Copyright (C) 2013       Philippe Grand			<philippe.grand@atoo-net.com>
 * Copyright (C) 2015       Alexandre Spangaro		<alexandre.spangaro@gmail.com>
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
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/donation.lib.php';
require_once DOL_DOCUMENT_ROOT.'/don/class/don.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';

$langs->load("admin");
$langs->load("donations");
$langs->load("accountancy");
$langs->load('other');

if (!$user->admin) accessforbidden();

$typeconst=array('yesno','texte','chaine');

$action = GETPOST('action','alpha');

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
            setEventMessage($obj->error,'errors');
            dol_syslog($obj->error, LOG_ERR);
        }
    }
    else
    {
        setEventMessage($langs->trans("ErrorModuleNotFound"),'errors');
        dol_syslog($langs->trans("ErrorModuleNotFound"), LOG_ERR);
    }
}

// Set default model
else if ($action == 'setdoc')
{
	if (dolibarr_set_const($db, "DON_ADDON_MODEL",$value,'chaine',0,'',$conf->entity))
	{
		// La constante qui a ete lue en avant du nouveau set
		// on passe donc par une variable pour avoir un affichage coherent
		$conf->global->DON_ADDON_MODEL = $value;
	}

	// On active le modele
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
	$account = GETPOST('DONATION_ACCOUNTINGACCOUNT');	// No alpha here, we want exact string

    $res = dolibarr_set_const($db, "DONATION_ACCOUNTINGACCOUNT",$account,'chaine',0,'',$conf->entity);

	if (! $res > 0) $error++;

 	if (! $error)
    {
        setEventMessage($langs->trans("SetupSaved"));
    }
    else
    {
        setEventMessage($langs->trans("Error"),'errors');
    }
}

if ($action == 'set_DONATION_MESSAGE')
{
	$freemessage = GETPOST('DONATION_MESSAGE');	// No alpha here, we want exact string

    $res = dolibarr_set_const($db, "DONATION_MESSAGE",$freemessage,'chaine',0,'',$conf->entity);

	if (! $res > 0) $error++;

 	if (! $error)
    {
        setEventMessage($langs->trans("SetupSaved"));
    }
    else
    {
        setEventMessage($langs->trans("Error"),'errors');
    }
}

// Activate an article
else if ($action == 'setart200') {
	$setart200 = GETPOST('value', 'int');
	$res = dolibarr_set_const($db, "DONATION_ART200", $setart200, 'yesno', 0, '', $conf->entity);
	if (! $res > 0)
		$error ++;

	if (! $error) {
		setEventMessage($langs->trans("SetupSaved"), 'mesgs');
	} else {
		setEventMessage($langs->trans("Error"), 'mesgs');
	}
}
else if ($action == 'setart238') {
	$setart238 = GETPOST('value', 'int');
	$res = dolibarr_set_const($db, "DONATION_ART238", $setart238, 'yesno', 0, '', $conf->entity);
	if (! $res > 0)
		$error ++;

	if (! $error) {
		setEventMessage($langs->trans("SetupSaved"), 'mesgs');
	} else {
		setEventMessage($langs->trans("Error"), 'mesgs');
	}
}
else if ($action == 'setart885') {
	$setart885 = GETPOST('value', 'int');
	$res = dolibarr_set_const($db, "DONATION_ART885", $setart885, 'yesno', 0, '', $conf->entity);
	if (! $res > 0)
		$error ++;

	if (! $error) {
		setEventMessage($langs->trans("SetupSaved"), 'mesgs');
	} else {
		setEventMessage($langs->trans("Error"), 'mesgs');
	}
}

/*
 * View
 */

$dir = "../../core/modules/dons/";
$form=new Form($db);

llxHeader('',$langs->trans("DonationsSetup"),'DonConfiguration');
$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';
print_fiche_titre($langs->trans("DonationsSetup"),$linkback,'setup');

$head = donation_admin_prepare_head();

dol_fiche_head($head, 'general', $langs->trans("Donations"), 0, 'payment');

/*
 *  Params
 */
print_titre($langs->trans("Options"));

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td colspan="3">'.$langs->trans("Parameters").'</td>';
//print '<td width="80">&nbsp;</td>';
print "</tr>\n";
$var=true;

print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'" />';
print '<input type="hidden" name="action" value="set_DONATION_ACCOUNTINGACCOUNT" />';

$var=! $var;
print '<tr '.$bc[$var].'>';

print '<td width="50%">';
$label = $langs->trans(AccountAccounting);
print '<label for="'.$langs->trans(AccountAccounting).'">' . $label . '</label></td>';
print '<td>';
print '<input type="text" size="10" id="DONATION_ACCOUNTINGACCOUNT" name="DONATION_ACCOUNTINGACCOUNT" value="' . $conf->global->DONATION_ACCOUNTINGACCOUNT . '">';
print '</td><td align="right">';
print '<input type="submit" class="button" value="'.$langs->trans("Modify").'" />';
print "</td></tr>\n";
print '</form>';

print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'" />';
print '<input type="hidden" name="action" value="set_DONATION_MESSAGE" />';

$var=! $var;
print '<tr '.$bc[$var].'><td colspan="2">';
print $langs->trans("FreeTextOnDonations").'<br>';
print '<textarea name="DONATION_MESSAGE" class="flat" cols="120">'.$conf->global->DONATION_MESSAGE.'</textarea>';
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
	print_titre($langs->trans("FrenchOptions"));

	print '<table class="noborder" width="100%">';
	print '<tr class="liste_titre">';
	print '<td colspan="3">' . $langs->trans('Parameters') . '</td>';
	print "</tr>\n";

	$var=!$var;
	print "<tr " . $bc[$var] . ">";
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

	$var=!$var;
	print "<tr " . $bc[$var] . ">";
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

	$var=!$var;
	print "<tr " . $bc[$var] . ">";
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

// Document templates
print '<br>';
print_titre($langs->trans("DonationsModels"));

// Defini tableau def de modele
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

$var=True;
if (is_resource($handle))
{
    while (($file = readdir($handle))!==false)
    {
        if (preg_match('/\.modules\.php$/i',$file))
        {
            $var = !$var;
            $name = substr($file, 0, dol_strlen($file) -12);
            $classname = substr($file, 0, dol_strlen($file) -12);

            require_once $dir.'/'.$file;
            $module=new $classname($db);

            // Show modules according to features level
            if ($module->version == 'development'  && $conf->global->MAIN_FEATURES_LEVEL < 2) continue;
            if ($module->version == 'experimental' && $conf->global->MAIN_FEATURES_LEVEL < 1) continue;

            if ($module->isEnabled())
            {
                print '<tr '.$bc[$var].'><td width=\"100\">';
                echo $module->name;
                print '</td>';
                print '<td>';
                print $module->description;
                print '</td>';

                // Active
                if (in_array($name, $def))
                {
                    print "<td align=\"center\">\n";
                    if ($conf->global->DON_ADDON_MODEL == $name)
                    {
                        print img_picto($langs->trans("Enabled"),'switch_on');
                    }
                    else
                    {
                        print '&nbsp;';
                        print '</td><td align="center">';
                        print '<a href="dons.php?action=setdoc&value='.$name.'&amp;scandir='.$module->scandir.'&amp;label='.urlencode($module->name).'">'.img_picto($langs->trans("Enabled"),'switch_on').'</a>';
                    }
                    print '</td>';
                }
                else
                {
                    print "<td align=\"center\">\n";
                    print '<a href="'.$_SERVER["PHP_SELF"].'?action=set&amp;value='.$name.'&amp;scandir='.$module->scandir.'&amp;label='.urlencode($module->name).'">'.img_picto($langs->trans("Disabled"),'switch_off').'</a>';
                    print "</td>";
                }

                // Defaut
                print "<td align=\"center\">";
                if ($conf->global->DON_ADDON_MODEL == "$name")
                {
                    print img_picto($langs->trans("Default"),'on');
                }
                else
                {
                    print '<a href="'.$_SERVER["PHP_SELF"].'?action=setdoc&amp;value='.$name.'&amp;scandir='.$module->scandir.'&amp;label='.urlencode($module->name).'" alt="'.$langs->trans("Default").'">'.img_picto($langs->trans("Disabled"),'off').'</a>';
                }
                print '</td>';

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

print '</table>';


print "<br>";


$db->close();

llxFooter();
