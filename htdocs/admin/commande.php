<?php
/* Copyright (C) 2003-2006 Rodolphe Quiedeville	        <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2013 Laurent Destailleur          <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Sebastien Di Cintio          <sdicintio@ressource-toi.org>
 * Copyright (C) 2004      Benoit Mortier               <benoit.mortier@opensides.be>
 * Copyright (C) 2004      Andre Cianfarani             <acianfa@free.fr>
 * Copyright (C) 2005-2014 Regis Houssin                <regis.houssin@inodbox.com>
 * Copyright (C) 2008 	   Raphael Bertrand (Resultic)  <raphael.bertrand@resultic.fr>
 * Copyright (C) 2011-2013 Juanjo Menent			    <jmenent@2byte.es>
 * Copyright (C) 2011-2016 Philippe Grand			    <philippe.grand@atoo-net.com>
 * Copyright (C) 2013 	   Florian Henry			    <florian.henry@open-concept.pro>
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
 *	\file       htdocs/admin/commande.php
 *	\ingroup    commande
 *	\brief      Setup page of module Order
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php';
require_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/order.lib.php';

// Load translation files required by the page
$langs->loadLangs(array('admin', 'errors', 'orders', 'other'));

if (!$user->admin) accessforbidden();

$action = GETPOST('action', 'alpha');
$value = GETPOST('value', 'alpha');
$label = GETPOST('label', 'alpha');
$scandir = GETPOST('scan_dir', 'alpha');
$type = 'order';


/*
 * Actions
 */

include DOL_DOCUMENT_ROOT.'/core/actions_setmoduleoptions.inc.php';

if ($action == 'updateMask')
{
	$maskconstorder = GETPOST('maskconstorder', 'alpha');
	$maskorder = GETPOST('maskorder', 'alpha');

	if ($maskconstorder) $res = dolibarr_set_const($db, $maskconstorder, $maskorder, 'chaine', 0, '', $conf->entity);

	if (!$res > 0) $error++;

 	if (!$error)
    {
        setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
    }
    else
    {
        setEventMessages($langs->trans("Error"), null, 'errors');
    }
}

elseif ($action == 'specimen')
{
	$modele = GETPOST('module', 'alpha');

	$commande = new Commande($db);
	$commande->initAsSpecimen();

	// Search template files
	$file = ''; $classname = ''; $filefound = 0;
	$dirmodels = array_merge(array('/'), (array) $conf->modules_parts['models']);
	foreach ($dirmodels as $reldir)
	{
	    $file = dol_buildpath($reldir."core/modules/commande/doc/pdf_".$modele.".modules.php", 0);
		if (file_exists($file))
		{
			$filefound = 1;
			$classname = "pdf_".$modele;
			break;
		}
	}

	if ($filefound)
	{
		require_once $file;

		$module = new $classname($db);

		if ($module->write_file($commande, $langs) > 0)
		{
			header("Location: ".DOL_URL_ROOT."/document.php?modulepart=commande&file=SPECIMEN.pdf");
			return;
		}
		else
		{
			setEventMessages($module->error, null, 'errors');
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
elseif ($action == 'set')
{
	$ret = addDocumentModel($value, $type, $label, $scandir);
}

elseif ($action == 'del')
{
	$ret = delDocumentModel($value, $type);
	if ($ret > 0)
	{
        if ($conf->global->COMMANDE_ADDON_PDF == "$value") dolibarr_del_const($db, 'COMMANDE_ADDON_PDF', $conf->entity);
	}
}

// Set default model
elseif ($action == 'setdoc')
{
	if (dolibarr_set_const($db, "COMMANDE_ADDON_PDF", $value, 'chaine', 0, '', $conf->entity))
	{
		// The constant that was read before the new set
		// We therefore requires a variable to have a coherent view
		$conf->global->COMMANDE_ADDON_PDF = $value;
	}

	// On active le modele
	$ret = delDocumentModel($value, $type);
	if ($ret > 0)
	{
		$ret = addDocumentModel($value, $type, $label, $scandir);
	}
}

elseif ($action == 'setmod')
{
	// TODO Check if numbering module chosen can be activated
	// by calling method canBeActivated

	dolibarr_set_const($db, "COMMANDE_ADDON", $value, 'chaine', 0, '', $conf->entity);
}

elseif ($action == 'set_COMMANDE_DRAFT_WATERMARK')
{
	$draft = GETPOST("COMMANDE_DRAFT_WATERMARK");
	$res = dolibarr_set_const($db, "COMMANDE_DRAFT_WATERMARK", trim($draft), 'chaine', 0, '', $conf->entity);

	if (!$res > 0) $error++;

 	if (!$error)
    {
        setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
    }
    else
    {
        setEventMessages($langs->trans("Error"), null, 'errors');
    }
}

elseif ($action == 'set_ORDER_FREE_TEXT')
{
	$freetext = GETPOST("ORDER_FREE_TEXT", 'none'); // No alpha here, we want exact string

	$res = dolibarr_set_const($db, "ORDER_FREE_TEXT", $freetext, 'chaine', 0, '', $conf->entity);

	if (!$res > 0) $error++;

 	if (!$error)
    {
        setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
    }
    else
    {
        setEventMessages($langs->trans("Error"), null, 'errors');
    }
}
elseif ($action == "setshippableiconinlist") {
    // Activate Set Shippable Icon In List
    $setshippableiconinlist = GETPOST('value', 'int');
    $res = dolibarr_set_const($db, "SHIPPABLE_ORDER_ICON_IN_LIST", $setshippableiconinlist, 'yesno', 0, '', $conf->entity);
    if (!$res > 0) $error++;
    if (!$error) {
        setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
    } else {
        setEventMessages($langs->trans("Error"), null, 'errors');
    }
}
elseif ($action == 'setribchq')
{
	$rib = GETPOST('rib', 'alpha');
	$chq = GETPOST('chq', 'alpha');

	$res = dolibarr_set_const($db, "FACTURE_RIB_NUMBER", $rib, 'chaine', 0, '', $conf->entity);
	$res = dolibarr_set_const($db, "FACTURE_CHQ_NUMBER", $chq, 'chaine', 0, '', $conf->entity);

	if (!$res > 0) $error++;

	if (!$error)
	{
		setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
	}
	else
	{
		setEventMessages($langs->trans("Error"), null, 'errors');
	}
}

// Activate ask for payment bank
elseif ($action == 'set_BANK_ASK_PAYMENT_BANK_DURING_ORDER')
{
    $res = dolibarr_set_const($db, "BANK_ASK_PAYMENT_BANK_DURING_ORDER", $value, 'chaine', 0, '', $conf->entity);

    if (!$res > 0) $error++;

    if (!$error)
    {
        setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
    }
    else
    {
        setEventMessages($langs->trans("Error"), null, 'errors');
    }
}

// Activate ask for warehouse
elseif ($action == 'set_WAREHOUSE_ASK_WAREHOUSE_DURING_ORDER')
{
    $res = dolibarr_set_const($db, "WAREHOUSE_ASK_WAREHOUSE_DURING_ORDER", $value, 'chaine', 0, '', $conf->entity);

    if (!$res > 0) $error++;

    if (!$error)
    {
        setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
    }
    else
    {
        setEventMessages($langs->trans("Error"), null, 'errors');
    }
}


/*
 * View
 */

$form = new Form($db);

$dirmodels = array_merge(array('/'), (array) $conf->modules_parts['models']);

llxHeader("", $langs->trans("OrdersSetup"));

$linkback = '<a href="'.DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1">'.$langs->trans("BackToModuleList").'</a>';
print load_fiche_titre($langs->trans("OrdersSetup"), $linkback, 'title_setup');

$head = order_admin_prepare_head();

dol_fiche_head($head, 'general', $langs->trans("Orders"), -1, 'order');

/*
 * Orders Numbering model
 */

print load_fiche_titre($langs->trans("OrdersNumberingModules"), '', '');

print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Name").'</td>';
print '<td>'.$langs->trans("Description").'</td>';
print '<td class="nowrap">'.$langs->trans("Example").'</td>';
print '<td class="center" width="60">'.$langs->trans("Status").'</td>';
print '<td class="center" width="16">'.$langs->trans("ShortInfo").'</td>';
print '</tr>'."\n";

clearstatcache();

foreach ($dirmodels as $reldir)
{
	$dir = dol_buildpath($reldir."core/modules/commande/");

	if (is_dir($dir))
	{
		$handle = opendir($dir);
		if (is_resource($handle))
		{
			while (($file = readdir($handle)) !== false)
			{
				if (substr($file, 0, 13) == 'mod_commande_' && substr($file, dol_strlen($file) - 3, 3) == 'php')
				{
					$file = substr($file, 0, dol_strlen($file) - 4);

					require_once $dir.$file.'.php';

					$module = new $file($db);

					// Show modules according to features level
					if ($module->version == 'development' && $conf->global->MAIN_FEATURES_LEVEL < 2) continue;
					if ($module->version == 'experimental' && $conf->global->MAIN_FEATURES_LEVEL < 1) continue;

					if ($module->isEnabled())
					{
						print '<tr class="oddeven"><td>'.$module->name."</td><td>\n";
						print $module->info();
						print '</td>';

                        // Show example of numbering model
                        print '<td class="nowrap">';
                        $tmp = $module->getExample();
                        if (preg_match('/^Error/', $tmp)) print '<div class="error">'.$langs->trans($tmp).'</div>';
                        elseif ($tmp == 'NotConfigured') print $langs->trans($tmp);
                        else print $tmp;
                        print '</td>'."\n";

						print '<td class="center">';
						if ($conf->global->COMMANDE_ADDON == $file)
						{
							print img_picto($langs->trans("Activated"), 'switch_on');
						}
						else
						{
							print '<a href="'.$_SERVER["PHP_SELF"].'?action=setmod&amp;value='.$file.'">';
							print img_picto($langs->trans("Disabled"), 'switch_off');
							print '</a>';
						}
						print '</td>';

						$commande = new Commande($db);
						$commande->initAsSpecimen();

						// Info
						$htmltooltip = '';
						$htmltooltip .= ''.$langs->trans("Version").': <b>'.$module->getVersion().'</b><br>';
						$commande->type = 0;
						$nextval = $module->getNextValue($mysoc, $commande);
                        if ("$nextval" != $langs->trans("NotAvailable")) {  // Keep " on nextval
                            $htmltooltip .= ''.$langs->trans("NextValue").': ';
                            if ($nextval) {
                                if (preg_match('/^Error/', $nextval) || $nextval == 'NotConfigured')
                                    $nextval = $langs->trans($nextval);
                                $htmltooltip .= $nextval.'<br>';
                            } else {
                                $htmltooltip .= $langs->trans($module->error).'<br>';
                            }
                        }

						print '<td class="center">';
						print $form->textwithpicto('', $htmltooltip, 1, 0);
						print '</td>';

						print "</tr>\n";
					}
				}
			}
			closedir($handle);
		}
	}
}
print "</table><br>\n";


/*
 * Document templates generators
 */

print load_fiche_titre($langs->trans("OrdersModelModule"), '', '');

// Load array def with activated templates
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
		array_push($def, $array[0]);
		$i++;
	}
}
else
{
	dol_print_error($db);
}


print "<table class=\"noborder\" width=\"100%\">\n";
print "<tr class=\"liste_titre\">\n";
print '<td>'.$langs->trans("Name").'</td>';
print '<td>'.$langs->trans("Description").'</td>';
print '<td class="center" width="60">'.$langs->trans("Status")."</td>\n";
print '<td class="center" width="60">'.$langs->trans("Default")."</td>\n";
print '<td class="center" width="38">'.$langs->trans("ShortInfo").'</td>';
print '<td class="center" width="38">'.$langs->trans("Preview").'</td>';
print "</tr>\n";

clearstatcache();

foreach ($dirmodels as $reldir)
{
    foreach (array('', '/doc') as $valdir)
    {
    	$realpath = $reldir."core/modules/commande".$valdir;
    	$dir = dol_buildpath($realpath);

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

	                            // Default
	                            print '<td class="center">';
	                            if ($conf->global->COMMANDE_ADDON_PDF == $name)
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
			                    $htmltooltip .= '<br>'.$langs->trans("Path").': '.preg_replace('/^\//', '', $realpath).'/'.$file;

			                    $htmltooltip .= '<br><br><u>'.$langs->trans("FeaturesSupported").':</u>';
					    		$htmltooltip .= '<br>'.$langs->trans("Logo").': '.yn($module->option_logo, 1, 1);
					    		$htmltooltip .= '<br>'.$langs->trans("PaymentMode").': '.yn($module->option_modereg, 1, 1);
					    		$htmltooltip .= '<br>'.$langs->trans("PaymentConditions").': '.yn($module->option_condreg, 1, 1);
					    		$htmltooltip .= '<br>'.$langs->trans("MultiLanguage").': '.yn($module->option_multilang, 1, 1);
					    		//$htmltooltip .= '<br>'.$langs->trans("Discounts").': '.yn($module->option_escompte,1,1);
					    		//$htmltooltip .= '<br>'.$langs->trans("CreditNote").': '.yn($module->option_credit_note,1,1);
					    		$htmltooltip .= '<br>'.$langs->trans("WatermarkOnDraftOrders").': '.yn($module->option_draft_watermark, 1, 1);


	                            print '<td class="center">';
	                            print $form->textwithpicto('', $htmltooltip, 1, 0);
	                            print '</td>';

	                            // Preview
	                            print '<td class="center">';
	                            if ($module->type == 'pdf')
	                            {
	                                print '<a href="'.$_SERVER["PHP_SELF"].'?action=specimen&module='.$name.'">'.img_object($langs->trans("Preview"), 'bill').'</a>';
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
    }
}

print '</table>';


/*
 *  Payment mode
 */

print '<br>';
print load_fiche_titre($langs->trans("SuggestedPaymentModesIfNotDefinedInOrder"), '', '');

print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
print '<input type="hidden" name="token" value="'.newToken().'" />';

print '<table class="noborder centpercent">';

print '<tr class="liste_titre">';
print '<td>';
print '<input type="hidden" name="action" value="setribchq">';
print $langs->trans("PaymentMode").'</td>';
print '<td align="right">';
if (empty($conf->facture->enabled)) {
	print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
}
print '</td>';
print "</tr>\n";

print '<tr class="oddeven">';
print "<td>".$langs->trans("SuggestPaymentByRIBOnAccount")."</td>";
print "<td>";
if (empty($conf->facture->enabled))
{
	if (!empty($conf->banque->enabled))
	{
		$sql = "SELECT rowid, label";
		$sql .= " FROM ".MAIN_DB_PREFIX."bank_account";
		$sql .= " WHERE clos = 0";
		$sql .= " AND courant = 1";
		$sql .= " AND entity IN (".getEntity('bank_account').")";
		$resql = $db->query($sql);
		if ($resql)
		{
			$num = $db->num_rows($resql);
			$i = 0;
			if ($num > 0)
			{
				print '<select name="rib" class="flat" id="rib">';
				print '<option value="0">'.$langs->trans("DoNotSuggestPaymentMode").'</option>';
				while ($i < $num)
				{
					$row = $db->fetch_row($resql);

					print '<option value="'.$row[0].'"';
					print $conf->global->FACTURE_RIB_NUMBER == $row[0] ? ' selected' : '';
					print '>'.$row[1].'</option>';

					$i++;
				}
				print "</select>";
			}
			else
			{
				print "<i>".$langs->trans("NoActiveBankAccountDefined")."</i>";
			}
		}
	}
	else
	{
		print '<span class="opacitymedium">'.$langs->trans("BankModuleNotActive").'</span>';
	}
}
else {
	print '<span class="opacitymedium">'.$langs->trans("SeeSetupOfModule", $langs->transnoentitiesnoconv("Module30Name")).'</span>';
}
print "</td></tr>";

print '<tr class="oddeven">';
print "<td>".$langs->trans("SuggestPaymentByChequeToAddress")."</td>";
print "<td>";
if (empty($conf->facture->enabled))
{
	print '<select class="flat" name="chq" id="chq">';
	print '<option value="0">'.$langs->trans("DoNotSuggestPaymentMode").'</option>';
	print '<option value="-1"'.($conf->global->FACTURE_CHQ_NUMBER ? ' selected' : '').'>'.$langs->trans("MenuCompanySetup").' ('.($mysoc->name ? $mysoc->name : $langs->trans("NotDefined")).')</option>';

	$sql = "SELECT rowid, label";
	$sql .= " FROM ".MAIN_DB_PREFIX."bank_account";
	$sql .= " WHERE clos = 0";
	$sql .= " AND courant = 1";
	$sql .= " AND entity IN (".getEntity('bank_account').")";

	$resql = $db->query($sql);
	if ($resql)
	{
		$num = $db->num_rows($resql);
		$i = 0;
		while ($i < $num)
		{
			$row = $db->fetch_row($resql);

			print '<option value="'.$row[0].'"';
			print $conf->global->FACTURE_CHQ_NUMBER == $row[0] ? ' selected' : '';
			print '>'.$langs->trans("OwnerOfBankAccount", $row[1]).'</option>';

			$i++;
		}
	}
	print "</select>";
}
else {
	print '<span class="opacitymedium">'.$langs->trans("SeeSetupOfModule", $langs->transnoentitiesnoconv("Module30Name")).'</span>';
}
print "</td></tr>";
print "</table>";
print "</form>";


print '<br>';

/*
 * Other options
 */

print load_fiche_titre($langs->trans("OtherOptions"), '', '');
print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Parameter").'</td>';
print '<td class="center" width="60">'.$langs->trans("Value").'</td>';
print "<td>&nbsp;</td>\n";
print "</tr>\n";

$substitutionarray = pdf_getSubstitutionArray($langs, null, null, 2);
$substitutionarray['__(AnyTranslationKey)__'] = $langs->trans("Translation");
$htmltext = '<i>'.$langs->trans("AvailableVariables").':<br>';
foreach ($substitutionarray as $key => $val)	$htmltext .= $key.'<br>';
$htmltext .= '</i>';

print '<form action="'.$_SERVER["PHP_SELF"].'" method="post">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="set_ORDER_FREE_TEXT">';
print '<tr class="oddeven"><td colspan="2">';
print $form->textwithpicto($langs->trans("FreeLegalTextOnOrders"), $langs->trans("AddCRIfTooLong").'<br><br>'.$htmltext, 1, 'help', '', 0, 2, 'freetexttooltip').'<br>';
$variablename = 'ORDER_FREE_TEXT';
if (empty($conf->global->PDF_ALLOW_HTML_FOR_FREE_TEXT))
{
    print '<textarea name="'.$variablename.'" class="flat" cols="120">'.$conf->global->$variablename.'</textarea>';
}
else
{
    include_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
    $doleditor = new DolEditor($variablename, $conf->global->$variablename, '', 80, 'dolibarr_notes');
    print $doleditor->Create();
}
print '</td><td class="right">';
print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
print "</td></tr>\n";
print '</form>';

//Use draft Watermark

print "<form method=\"post\" action=\"".$_SERVER["PHP_SELF"]."\">";
print '<input type="hidden" name="token" value="'.newToken().'">';
print "<input type=\"hidden\" name=\"action\" value=\"set_COMMANDE_DRAFT_WATERMARK\">";
print '<tr class="oddeven"><td>';
print $form->textwithpicto($langs->trans("WatermarkOnDraftOrders"), $htmltext, 1, 'help', '', 0, 2, 'watermarktooltip').'<br>';
print '</td><td>';
print '<input class="flat minwidth200" type="text" name="COMMANDE_DRAFT_WATERMARK" value="'.$conf->global->COMMANDE_DRAFT_WATERMARK.'">';
print '</td><td class="right">';
print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
print "</td></tr>\n";
print '</form>';

// Shippable Icon in List
/* Kept as hidden feature for the moment, result seems bugged.
Whet is definition of "shippable" according to all different STOCK_CALCULATE_... options ?

print '<tr class="oddeven">';
print '<td>'.$langs->trans("ShippableOrderIconInList").'</td>';
print '<td>&nbsp</td>';
print '<td class="center">';
if (!empty($conf->global->SHIPPABLE_ORDER_ICON_IN_LIST)) {
    print '<a href="'.$_SERVER['PHP_SELF'].'?action=setshippableiconinlist&value=0">';
    print img_picto($langs->trans("Activated"),'switch_on');
} else {
    print '<a href="'.$_SERVER['PHP_SELF'].'?action=setshippableiconinlist&value=1">';
    print img_picto($langs->trans("Disabled"),'switch_off');
}
print '</a></td>';
print '</tr>';

/*
// Seems to be not so used. So kept hidden for the moment to avoid dangerous options inflation.
// Ask for payment bank during order
if ($conf->banque->enabled)
{

    print '<tr class="oddeven"><td>';
    print $langs->trans("BANK_ASK_PAYMENT_BANK_DURING_ORDER").'</td><td>&nbsp</td><td class="center">';
    if (! empty($conf->use_javascript_ajax))
    {
        print ajax_constantonoff('BANK_ASK_PAYMENT_BANK_DURING_ORDER');
    }
    else
    {
        if (empty($conf->global->BANK_ASK_PAYMENT_BANK_DURING_ORDER))
        {
            print '<a href="'.$_SERVER['PHP_SELF'].'?action=set_BANK_ASK_PAYMENT_BANK_DURING_ORDER&amp;value=1">'.img_picto($langs->trans("Disabled"),'switch_off').'</a>';
        }
        else
        {
            print '<a href="'.$_SERVER['PHP_SELF'].'?action=set_BANK_ASK_PAYMENT_BANK_DURING_ORDER&amp;value=0">'.img_picto($langs->trans("Enabled"),'switch_on').'</a>';
        }
    }
    print '</td></tr>';
}
else
{

    print '<tr class="oddeven"><td>';
    print $langs->trans("BANK_ASK_PAYMENT_BANK_DURING_ORDER").'</td><td>&nbsp;</td><td class="center">'.$langs->trans('NotAvailable').'</td></tr>';
}

// Ask for warehouse during order
if ($conf->stock->enabled)
{

    print '<tr class="oddeven"><td>';
    print $langs->trans("WAREHOUSE_ASK_WAREHOUSE_DURING_ORDER").'</td><td>&nbsp</td><td class="center">';
    if (! empty($conf->use_javascript_ajax))
    {
        print ajax_constantonoff('WAREHOUSE_ASK_WAREHOUSE_DURING_ORDER');
    }
    else
    {
        if (empty($conf->global->WAREHOUSE_ASK_WAREHOUSE_DURING_ORDER))
        {
            print '<a href="'.$_SERVER['PHP_SELF'].'?action=set_WAREHOUSE_ASK_WAREHOUSE_DURING_ORDER&amp;value=1">'.img_picto($langs->trans("Disabled"),'switch_off').'</a>';
        }
        else
        {
            print '<a href="'.$_SERVER['PHP_SELF'].'?action=set_WAREHOUSE_ASK_WAREHOUSE_DURING_ORDER&amp;value=0">'.img_picto($langs->trans("Enabled"),'switch_on').'</a>';
        }
    }
    print '</td></tr>';
}
else
{

    print '<tr class="oddeven"><td>';
    print $langs->trans("WAREHOUSE_ASK_WAREHOUSE_DURING_ORDER").'</td><td>&nbsp;</td><td class="center">'.$langs->trans('NotAvailable').'</td></tr>';
}
*/

print '</table>';
print '<br>';


/*
 * Notifications
 */

print load_fiche_titre($langs->trans("Notifications"), '', '');
print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Parameter").'</td>';
print '<td class="center" width="60"></td>';
print '<td width="80">&nbsp;</td>';
print "</tr>\n";

print '<tr class="oddeven"><td colspan="2">';
print $langs->trans("YouMayFindNotificationsFeaturesIntoModuleNotification").'<br>';
print '</td><td class="right">';
print "</td></tr>\n";

print '</table>';

// End of page
llxFooter();
$db->close();
