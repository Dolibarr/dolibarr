<?php
/* Copyright (C) 2006      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2008-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2012-2013 Juanjo Menent		<jmenent@2byte.es>
 * Copyright (C) 2013-2018 Philippe Grand       <philippe.grand@atoo-net.com>
 * Copyright (C) 2013      Florian Henry        <florian.henry@open-concept.pro>
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
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 *	\file       htdocs/admin/stock.php
 *	\ingroup    stock
 *	\brief      Page d'administration/configuration du module gestion de stock
 */
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/stock.lib.php';

// Load translation files required by the page
$langs->loadLangs(array("admin", "stocks"));

// Securit check
if (!$user->admin) accessforbidden();

$action = GETPOST('action', 'alpha');
$value = GETPOST('value', 'alpha');
$label = GETPOST('label', 'alpha');
$scandir = GETPOST('scan_dir', 'alpha');
$type = 'stock_movement';

$error = 0;

$nomessageinsetmoduleoptions = 1;
include DOL_DOCUMENT_ROOT.'/core/actions_setmoduleoptions.inc.php';


/*
 * Action
 */
if (preg_match('/set_([a-z0-9_\-]+)/i', $action, $reg))
{
    $code = $reg[1];
    if (dolibarr_set_const($db, $code, 1, 'chaine', 0, '', $conf->entity) > 0)
    {
        header("Location: ".$_SERVER["PHP_SELF"]);
        exit;
    }
    else
    {
        dol_print_error($db);
    }
}

if (preg_match('/del_([a-z0-9_\-]+)/i', $action, $reg))
{
    $code = $reg[1];
    if (dolibarr_del_const($db, $code, $conf->entity) > 0)
    {
        header("Location: ".$_SERVER["PHP_SELF"]);
        exit;
    }
    else
    {
        dol_print_error($db);
    }
}

// Activate a model
if ($action == 'set')
{
	$ret = addDocumentModel($value, $type, $label, $scandir);
}

if ($action == 'del')
{
	$ret = delDocumentModel($value, $type);
	if ($ret > 0)
	{
		if ($conf->global->STOCK_MOVEMENT_ADDON_PDF_ODT_PATH == "$value") dolibarr_del_const($db, 'STOCK_MOVEMENT_ADDON_PDF_ODT_PATH', $conf->entity);
	}
}

// Set default model
if ($action == 'setdoc')
{
	if (dolibarr_set_const($db, "STOCK_MOVEMENT_ADDON_PDF", $value, 'chaine', 0, '', $conf->entity))
	{
		// La constante qui a ete lue en avant du nouveau set
		// on passe donc par une variable pour avoir un affichage coherent
		$conf->global->STOCK_MOVEMENT_ADDON_PDF = $value;
	}

	// On active le modele
	$ret = delDocumentModel($value, $type);
	if ($ret > 0)
	{
		$ret = addDocumentModel($value, $type, $label, $scandir);
	}
}

if ($action)
{
    if (!$error)
    {
	    setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
    }
    else
    {
	    setEventMessages($langs->trans("SetupNotError"), null, 'errors');
    }
}

/*
 * View
 */

llxHeader('', $langs->trans("StockSetup"));

$linkback = '<a href="'.DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1">'.$langs->trans("BackToModuleList").'</a>';
print load_fiche_titre($langs->trans("StockSetup"), $linkback, 'title_setup');

$head = stock_admin_prepare_head();

dol_fiche_head($head, 'movement', $langs->trans("StockSetup"), -1, 'stock');

$form = new Form($db);

// Module to build doc
$def = array();
$sql = "SELECT nom";
$sql .= " FROM ".MAIN_DB_PREFIX."document_model";
$sql .= " WHERE type = '".$type."'";
$sql .= " AND entity = ".$conf->entity;
$resql = $db->query($sql);
if ($resql)
{
	$i = 0;
	$num_rows = $db->num_rows($resql);
	while ($i < $num_rows)
	{
		$array = $db->fetch_array($resql);
		array_push($def, strtolower($array[0]));
		$i++;
	}
}
else
{
	dol_print_error($db);
}

print load_fiche_titre($langs->trans("StockMovementDocumentTemplates"), '', '');

print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Name").'</td>';
print '<td>'.$langs->trans("Description").'</td>';
print '<td class="center" width="60">'.$langs->trans("Status")."</td>\n";
print '<td class="center" width="60">'.$langs->trans("Default")."</td>\n";
print '<td class="center" width="80">'.$langs->trans("ShortInfo").'</td>';
print '<td class="center" width="80">'.$langs->trans("Preview").'</td>';
print "</tr>\n";

clearstatcache();

$dir = dol_buildpath("/core/modules/stock/doc/movement");
if (is_dir($dir))
{
    $handle = opendir($dir);
    if (is_resource($handle))
    {
        while (($file = readdir($handle)) !== false)
        {
            $filelist[] = $file;
        }
        closedir($handle);
        arsort($filelist);

        foreach ($filelist as $file)
        {
            if (preg_match('/\.modules\.php$/i', $file) && preg_match('/^(pdf_|doc_)/', $file))
            {
                if (file_exists($dir.'/'.$file))
                {
                    $name = substr($file, 4, dol_strlen($file) - 16);
                    $classname = substr($file, 0, dol_strlen($file) - 12);

                    require_once $dir.'/'.$file;
                    $module = new $classname($db);

                    $modulequalified = 1;
                    if ($module->version == 'development' && $conf->global->MAIN_FEATURES_LEVEL < 2) $modulequalified = 0;
                    if ($module->version == 'experimental' && $conf->global->MAIN_FEATURES_LEVEL < 1) $modulequalified = 0;

                    if ($modulequalified)
                    {
                        print '<tr class="oddeven"><td width="100">';
                        print (empty($module->name) ? $name : $module->name);
                        print "</td><td>\n";
                        if (method_exists($module, 'info')) print $module->info($langs);
                        else print $module->description;
                        print '</td>';

                        // Active
                        if (in_array($name, $def))
                        {
                            print '<td class="center">'."\n";
                            print '<a href="'.$_SERVER["PHP_SELF"].'?action=del&value='.$name.'">';
                            print img_picto($langs->trans("Enabled"), 'switch_on');
                            print '</a>';
                            print '</td>';
                        }
                        else
                        {
                            print '<td class="center">'."\n";
                            print '<a href="'.$_SERVER["PHP_SELF"].'?action=set&value='.$name.'&amp;scan_dir='.$module->scandir.'&amp;label='.urlencode($module->name).'">'.img_picto($langs->trans("Disabled"), 'switch_off').'</a>';
                            print "</td>";
                        }

                        // Defaut
                        print '<td class="center">';
                        if ($conf->global->STOCK_MOVEMENT_ADDON_PDF == $name)
                        {
                            print img_picto($langs->trans("Default"), 'on');
                        }
                        else
                        {
                            print '<a href="'.$_SERVER["PHP_SELF"].'?action=setdoc&value='.$name.'&amp;scan_dir='.$module->scandir.'&amp;label='.urlencode($module->name).'" alt="'.$langs->trans("Default").'">'.img_picto($langs->trans("Disabled"), 'off').'</a>';
                        }
                        print '</td>';

                        // Info
                        $htmltooltip = ''.$langs->trans("Name").': '.$module->name;
                        $htmltooltip .= '<br>'.$langs->trans("Type").': '.($module->type ? $module->type : $langs->trans("Unknown"));
                        if ($module->type == 'pdf')
                        {
                            $htmltooltip .= '<br>'.$langs->trans("Width").'/'.$langs->trans("Height").': '.$module->page_largeur.'/'.$module->page_hauteur;
                        }
                        $htmltooltip .= '<br><br><u>'.$langs->trans("FeaturesSupported").':</u>';
                        $htmltooltip .= '<br>'.$langs->trans("Logo").': '.yn($module->option_logo, 1, 1);
                        $htmltooltip .= '<br>'.$langs->trans("MultiLanguage").': '.yn($module->option_multilang, 1, 1);


                        print '<td class="center">';
                        print $form->textwithpicto('', $htmltooltip, 1, 0);
                        print '</td>';

                        // Preview
                        print '<td class="center">';
                        if ($module->type == 'pdf')
                        {
                            print '<a href="'.$_SERVER["PHP_SELF"].'?action=specimen&module='.$name.'">'.img_object($langs->trans("Preview"), 'contract').'</a>';
                        }
                        else
                        {
                            print img_object($langs->trans("PreviewNotAvailable"), 'generic');
                        }
                        print '</td>';

                        print "</tr>\n";
                    }
                }
            }
        }
    }
}

print '</table>';

// End of page
llxFooter();
$db->close();
