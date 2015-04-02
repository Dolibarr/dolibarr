<?php
/* Copyright (C) 2004      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004      Eric Seigne          <eric.seigne@ryxeo.com>
 * Copyright (C) 2005-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2011-2012 Juanjo Menent        <jmenent@2byte.es>
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
 *	\file       htdocs/societe/admin/societe.php
 *	\ingroup    company
 *	\brief      Third party module setup page
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';

$langs->load("admin");
$langs->load('other');

$action=GETPOST('action','alpha');
$value=GETPOST('value','alpha');

if (!$user->admin) accessforbidden();



/*
 * Actions
 */
if ($action == 'setcodeclient')
{
	if (dolibarr_set_const($db, "SOCIETE_CODECLIENT_ADDON",$value,'chaine',0,'',$conf->entity) > 0)
	{
		header("Location: ".$_SERVER["PHP_SELF"]);
		exit;
	}
	else
	{
		dol_print_error($db);
	}
}

if ($action == 'setcodecompta')
{
	if (dolibarr_set_const($db, "SOCIETE_CODECOMPTA_ADDON",$value,'chaine',0,'',$conf->entity) > 0)
	{
		header("Location: ".$_SERVER["PHP_SELF"]);
		exit;
	}
	else
	{
		dol_print_error($db);
	}
}

if ($action == 'updateoptions')
{
	if (GETPOST('COMPANY_USE_SEARCH_TO_SELECT'))
	{
		$companysearch = GETPOST('activate_COMPANY_USE_SEARCH_TO_SELECT','alpha');
		$res = dolibarr_set_const($db, "COMPANY_USE_SEARCH_TO_SELECT", $companysearch,'chaine',0,'',$conf->entity);
		if (! $res > 0) $error++;
		if (! $error)
	    {
		    setEventMessage($langs->trans("SetupSaved"));
	    }
	    else
	    {
		    setEventMessage($langs->trans("Error"), 'errors');
		}
	}

	if (GETPOST('CONTACT_USE_SEARCH_TO_SELECT'))
	{
		$contactsearch = GETPOST('activate_CONTACT_USE_SEARCH_TO_SELECT','alpha');
		$res = dolibarr_set_const($db, "CONTACT_USE_SEARCH_TO_SELECT", $contactsearch,'chaine',0,'',$conf->entity);
		if (! $res > 0) $error++;
		if (! $error)
		{
			setEventMessage($langs->trans("SetupSaved"));
		}
		else
		{
			setEventMessage($langs->trans("Error"), 'errors');
		}
	}
}

// Define constants for submodules that contains parameters (forms with param1, param2, ... and value1, value2, ...)
if ($action == 'setModuleOptions')
{
	$post_size=count($_POST);

	$db->begin();

	for($i=0;$i < $post_size;$i++)
    {
    	if (array_key_exists('param'.$i,$_POST))
    	{
    		$param=GETPOST("param".$i,'alpha');
    		$value=GETPOST("value".$i,'alpha');
    		if ($param) $res = dolibarr_set_const($db,$param,$value,'chaine',0,'',$conf->entity);
	    	if (! $res > 0) $error++;
    	}
    }
	if (! $error)
    {
        $db->commit();
	    setEventMessage($langs->trans("SetupSaved"));
    }
    else
    {
        $db->rollback();
	    setEventMessage($langs->trans("Error"), 'errors');
	}
}

// Activate a document generator module
if ($action == 'set')
{
	$label = GETPOST('label','alpha');
	$scandir = GETPOST('scandir','alpha');

	$type='company';
	$sql = "INSERT INTO ".MAIN_DB_PREFIX."document_model (nom, type, entity, libelle, description)";
	$sql.= " VALUES ('".$db->escape($value)."','".$type."',".$conf->entity.", ";
	$sql.= ($label?"'".$db->escape($label)."'":'null').", ";
	$sql.= (! empty($scandir)?"'".$db->escape($scandir)."'":"null");
	$sql.= ")";

	$resql=$db->query($sql);
	if (! $resql) dol_print_error($db);
}

// Disable a document generator module
if ($action== 'del')
{
	$type='company';
	$sql = "DELETE FROM ".MAIN_DB_PREFIX."document_model";
	$sql.= " WHERE nom='".$db->escape($value)."' AND type='".$type."' AND entity=".$conf->entity;
	$resql=$db->query($sql);
	if (! $resql) dol_print_error($db);
}

// Define default generator
if ($action == 'setdoc')
{
	$label = GETPOST('label','alpha');
	$scandir = GETPOST('scandir','alpha');

	$db->begin();

	dolibarr_set_const($db, "COMPANY_ADDON_PDF",$value,'chaine',0,'',$conf->entity);

	// On active le modele
	$type='company';
	$sql_del = "DELETE FROM ".MAIN_DB_PREFIX."document_model";
	$sql_del.= " WHERE nom = '".$db->escape(GETPOST('value','alpha'))."'";
	$sql_del.= " AND type = '".$type."'";
	$sql_del.= " AND entity = ".$conf->entity;
    dol_syslog("societe.php ".$sql);
	$result1=$db->query($sql_del);

	$sql = "INSERT INTO ".MAIN_DB_PREFIX."document_model (nom, type, entity, libelle, description)";
	$sql.= " VALUES ('".$db->escape($value)."', '".$type."', ".$conf->entity.", ";
	$sql.= ($label?"'".$db->escape($label)."'":'null').", ";
	$sql.= (! empty($scandir)?"'".$db->escape($scandir)."'":"null");
	$sql.= ")";
    dol_syslog("societe.php", LOG_DEBUG);
	$result2=$db->query($sql);
	if ($result1 && $result2)
	{
		$db->commit();
	}
	else
	{
	    $db->rollback();
	}
}

//Activate ProfId
if ($action == 'setprofid')
{
	$status = GETPOST('status','alpha');

	$idprof="SOCIETE_IDPROF".$value."_UNIQUE";
	if (dolibarr_set_const($db, $idprof,$status,'chaine',0,'',$conf->entity) > 0)
	{
		header("Location: ".$_SERVER["PHP_SELF"]);
		exit;
	}
	else
	{
		dol_print_error($db);
	}
}

//Activate Set ref in list
if ($action=="setaddrefinlist") {
	$setaddrefinlist = GETPOST('value','int');
	$res = dolibarr_set_const($db, "SOCIETE_ADD_REF_IN_LIST", $setaddrefinlist,'yesno',0,'',$conf->entity);
	if (! $res > 0) $error++;
	if (! $error)
	{
		setEventMessage($langs->trans("SetupSaved"));
	}
	else
	{
		setEventMessage($langs->trans("Error"), 'errors');
	}
}


//Activate ProfId mandatory
if ($action == 'setprofidmandatory')
{
	$status = GETPOST('status','alpha');

	$idprof="SOCIETE_IDPROF".$value."_MANDATORY";
	if (dolibarr_set_const($db, $idprof,$status,'chaine',0,'',$conf->entity) > 0)
	{
		header("Location: ".$_SERVER["PHP_SELF"]);
		exit;
	}
	else
	{
		dol_print_error($db);
	}
}

//Activate ProfId invoice mandatory
if ($action == 'setprofidinvoicemandatory')
{
	$status = GETPOST('status','alpha');

	$idprof="SOCIETE_IDPROF".$value."_INVOICE_MANDATORY";
	if (dolibarr_set_const($db, $idprof,$status,'chaine',0,'',$conf->entity) > 0)
	{
		header("Location: ".$_SERVER["PHP_SELF"]);
		exit;
	}
	else
	{
		dol_print_error($db);
	}
}

//Set hide closed customer into combox or select
if ($action == 'sethideinactivethirdparty')
{
	$status = GETPOST('status','alpha');

	if (dolibarr_set_const($db, "COMPANY_HIDE_INACTIVE_IN_COMBOBOX",$status,'chaine',0,'',$conf->entity) > 0)
	{
		header("Location: ".$_SERVER["PHP_SELF"]);
		exit;
	}
	else
	{
		dol_print_error($db);
	}
}

/*
 * 	View
 */

clearstatcache();

$form=new Form($db);

$help_url='EN:Module Third Parties setup|FR:Paramétrage_du_module_Tiers|ES:Configuración_del_módulo_terceros';
llxHeader('',$langs->trans("CompanySetup"),$help_url);

$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';
print_fiche_titre($langs->trans("CompanySetup"),$linkback,'setup');


$head = societe_admin_prepare_head(null);

dol_fiche_head($head, 'general', $langs->trans("ThirdParties"), 0, 'company');

$dirsociete=array_merge(array('/core/modules/societe/'),$conf->modules_parts['societe']);

// Module to manage customer/supplier code

print_titre($langs->trans("CompanyCodeChecker"));

print '<table class="noborder" width="100%">'."\n";
print '<tr class="liste_titre">'."\n";
print '  <td>'.$langs->trans("Name").'</td>';
print '  <td>'.$langs->trans("Description").'</td>';
print '  <td>'.$langs->trans("Example").'</td>';
print '  <td align="center" width="80">'.$langs->trans("Status").'</td>';
print '  <td align="center" width="60">'.$langs->trans("ShortInfo").'</td>';
print "</tr>\n";

$var = true;
foreach ($dirsociete as $dirroot)
{
	$dir = dol_buildpath($dirroot,0);

    $handle = @opendir($dir);
    if (is_resource($handle))
    {
    	// Loop on each module find in opened directory
    	while (($file = readdir($handle))!==false)
    	{
    		if (substr($file, 0, 15) == 'mod_codeclient_' && substr($file, -3) == 'php')
    		{
    			$file = substr($file, 0, dol_strlen($file)-4);

    			try {
        			dol_include_once($dirroot.$file.'.php');
    			}
    			catch(Exception $e)
    			{
    			    dol_syslog($e->getMessage(), LOG_ERR);
    			}

    			$modCodeTiers = new $file;

    			// Show modules according to features level
    			if ($modCodeTiers->version == 'development'  && $conf->global->MAIN_FEATURES_LEVEL < 2) continue;
    			if ($modCodeTiers->version == 'experimental' && $conf->global->MAIN_FEATURES_LEVEL < 1) continue;

    			$var = !$var;
    			print '<tr '.$bc[$var].'>'."\n";
    			print '<td width="140">'.$modCodeTiers->name.'</td>'."\n";
    			print '<td>'.$modCodeTiers->info($langs).'</td>'."\n";
    			print '<td class="nowrap">'.$modCodeTiers->getExample($langs).'</td>'."\n";

    			if ($conf->global->SOCIETE_CODECLIENT_ADDON == "$file")
    			{
    				print '<td align="center">'."\n";
    				print img_picto($langs->trans("Activated"),'switch_on');
    				print "</td>\n";
    			}
    			else
    			{
    				$disabled = false;
    				if (! empty($conf->multicompany->enabled) && (is_object($mc) && ! empty($mc->sharings['referent']) && $mc->sharings['referent'] == $conf->entity) ? false : true);
    				print '<td align="center">';
    				if (! $disabled) print '<a href="'.$_SERVER['PHP_SELF'].'?action=setcodeclient&value='.$file.'">';
    				print img_picto($langs->trans("Disabled"),'switch_off');
    				if (! $disabled) print '</a>';
    				print '</td>';
    			}

    			print '<td align="center">';
    			$s=$modCodeTiers->getToolTip($langs,null,-1);
    			print $form->textwithpicto('',$s,1);
    			print '</td>';

    			print '</tr>';
    		}
    	}
    	closedir($handle);
    }
}
print '</table>';


print "<br>";


// Select accountancy code numbering module

print_titre($langs->trans("AccountCodeManager"));

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td width="140">'.$langs->trans("Name").'</td>';
print '<td>'.$langs->trans("Description").'</td>';
print '<td>'.$langs->trans("Example").'</td>';
print '<td align="center" width="80">'.$langs->trans("Status").'</td>';
print '<td align="center" width="60">'.$langs->trans("ShortInfo").'</td>';
print "</tr>\n";

$var=true;
foreach ($dirsociete as $dirroot)
{
	$dir = dol_buildpath($dirroot,0);

	$handle = @opendir($dir);
    if (is_resource($handle))
    {
    	while (($file = readdir($handle))!==false)
    	{
    		if (substr($file, 0, 15) == 'mod_codecompta_' && substr($file, -3) == 'php')
    		{
    			$file = substr($file, 0, dol_strlen($file)-4);

    		    try {
        			dol_include_once($dirroot.$file.'.php');
    			}
    			catch(Exception $e)
    			{
    			    dol_syslog($e->getMessage(), LOG_ERR);
    			}

    			$modCodeCompta = new $file;
    			$var = !$var;

    			print '<tr '.$bc[$var].'>';
    			print '<td>'.$modCodeCompta->name."</td><td>\n";
    			print $modCodeCompta->info($langs);
    			print '</td>';
    			print '<td class="nowrap">'.$modCodeCompta->getExample($langs)."</td>\n";

    			if ($conf->global->SOCIETE_CODECOMPTA_ADDON == "$file")
    			{
    				print '<td align="center">';
    				print img_picto($langs->trans("Activated"),'switch_on');
    				print '</td>';
    			}
    			else
    			{
    				print '<td align="center"><a href="'.$_SERVER['PHP_SELF'].'?action=setcodecompta&value='.$file.'">';
    				print img_picto($langs->trans("Disabled"),'switch_off');
    				print '</a></td>';
    			}
    			print '<td align="center">';
    			$s=$modCodeCompta->getToolTip($langs,null,-1);
    			print $form->textwithpicto('',$s,1);
    			print '</td>';
    			print "</tr>\n";
    		}
    	}
    	closedir($handle);
    }
}
print "</table>\n";


/*
 *  Document templates generators
 */
print '<br>';
print_titre($langs->trans("ModelModules"));

// Load array def with activated templates
$def = array();
$sql = "SELECT nom";
$sql.= " FROM ".MAIN_DB_PREFIX."document_model";
$sql.= " WHERE type = 'company'";
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

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td width="140">'.$langs->trans("Name").'</td>';
print '<td>'.$langs->trans("Description").'</td>';
print '<td align="center" width="80">'.$langs->trans("Status").'</td>';
print '<td align="center" width="60">'.$langs->trans("ShortInfo").'</td>';
print '<td align="center" width="60">'.$langs->trans("Preview").'</td>';
print "</tr>\n";

$var=true;
foreach ($dirsociete as $dirroot)
{
	$dir = dol_buildpath($dirroot.'doc/',0);

	$handle=@opendir($dir);
	if (is_resource($handle))
	{
		while (($file = readdir($handle))!==false)
		{
			if (preg_match('/\.modules\.php$/i',$file))
			{
				$name = substr($file, 4, dol_strlen($file) -16);
				$classname = substr($file, 0, dol_strlen($file) -12);

			    try {
        			dol_include_once($dirroot.'doc/'.$file);
    			}
    			catch(Exception $e)
    			{
    			    dol_syslog($e->getMessage(), LOG_ERR);
    			}

    			$module = new $classname($db);

				$modulequalified=1;
				if (! empty($module->version)) {
					if ($module->version == 'development'  && $conf->global->MAIN_FEATURES_LEVEL < 2) $modulequalified=0;
					else if ($module->version == 'experimental' && $conf->global->MAIN_FEATURES_LEVEL < 1) $modulequalified=0;
				}

				if ($modulequalified)
				{
					$var = !$var;
					print '<tr '.$bc[$var].'><td width="100">';
					print $module->name;
					print "</td><td>\n";
					if (method_exists($module,'info')) print $module->info($langs);
					else print $module->description;
					print '</td>';

					// Activate / Disable
					if (in_array($name, $def))
					{
						print "<td align=\"center\">\n";
						//if ($conf->global->COMPANY_ADDON_PDF != "$name")
						//{
							print '<a href="'.$_SERVER["PHP_SELF"].'?action=del&value='.$name.'&scandir='.$module->scandir.'&label='.urlencode($module->name).'">';
							print img_picto($langs->trans("Enabled"),'switch_on');
							print '</a>';
						//}
						//else
						//{
						//	print img_picto($langs->trans("Enabled"),'on');
						//}
						print "</td>";
					}
					else
					{
						if (versioncompare($module->phpmin,versionphparray()) > 0)
						{
							print "<td align=\"center\">\n";
							print img_picto(dol_escape_htmltag($langs->trans("ErrorModuleRequirePHPVersion",join('.',$module->phpmin))),'switch_off');
							print "</td>";
						}
						else
						{
							print "<td align=\"center\">\n";
							print '<a href="'.$_SERVER["PHP_SELF"].'?action=set&value='.$name.'&scandir='.$module->scandir.'&label='.urlencode($module->name).'">'.img_picto($langs->trans("Disabled"),'switch_off').'</a>';
							print "</td>";
						}
					}

					// Info
					$htmltooltip =    ''.$langs->trans("Name").': '.$module->name;
					$htmltooltip.='<br>'.$langs->trans("Type").': '.($module->type?$module->type:$langs->trans("Unknown"));
					if ($module->type == 'pdf')
					{
						$htmltooltip.='<br>'.$langs->trans("Height").'/'.$langs->trans("Width").': '.$module->page_hauteur.'/'.$module->page_largeur;
					}
					$htmltooltip.='<br><br><u>'.$langs->trans("FeaturesSupported").':</u>';
					$htmltooltip.='<br>'.$langs->trans("WatermarkOnDraft").': '.yn((! empty($module->option_draft_watermark)?$module->option_draft_watermark:''), 1, 1);

					print '<td align="center" class="nowrap">';
					print $form->textwithpicto('',$htmltooltip,1,0);
					print '</td>';

					// Preview
					print '<td align="center" class="nowrap">';
					if ($module->type == 'pdf')
					{
						$linkspec='<a href="'.$_SERVER["PHP_SELF"].'?action=specimen&module='.$name.'">'.img_object($langs->trans("Preview"),'bill').'</a>';
					}
					else
					{
						$linkspec=img_object($langs->trans("PreviewNotAvailable"),'generic');
					}
					print $linkspec;
					print '</td>';

					print "</tr>\n";
				}
			}
		}
		closedir($handle);
	}
}
print '</table>';

print '<br>';

//IDProf
print_titre($langs->trans("CompanyIdProfChecker"));

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Name").'</td>';
print '<td>'.$langs->trans("Description").'</td>';
print '<td align="center">'.$langs->trans("MustBeUnique").'</td>';
print '<td align="center">'.$langs->trans("MustBeMandatory").'</td>';
print '<td align="center">'.$langs->trans("MustBeInvoiceMandatory").'</td>';
print "</tr>\n";

$profid[0][0]=$langs->trans("ProfId1");
$profid[0][1]=$langs->transcountry('ProfId1', $mysoc->country_code);
$profid[1][0]=$langs->trans("ProfId2");
$profid[1][1]=$langs->transcountry('ProfId2', $mysoc->country_code);
$profid[2][0]=$langs->trans("ProfId3");
$profid[2][1]=$langs->transcountry('ProfId3', $mysoc->country_code);
$profid[3][0]=$langs->trans("ProfId4");
$profid[3][1]=$langs->transcountry('ProfId4', $mysoc->country_code);
$profid[4][0]=$langs->trans("ProfId5");
$profid[4][1]=$langs->transcountry('ProfId5', $mysoc->country_code);
$profid[5][0]=$langs->trans("ProfId6");
$profid[5][1]=$langs->transcountry('ProfId6', $mysoc->country_code);

$var = true;
$i=0;

$nbofloop=count($profid);
while ($i < $nbofloop)
{
	if ($profid[$i][1]!='-')
	{
		$var = !$var;

		print '<tr '.$bc[$var].'>';
		print '<td>'.$profid[$i][0]."</td><td>\n";
		print $profid[$i][1];
		print '</td>';

		$idprof_unique ='SOCIETE_IDPROF'.($i+1).'_UNIQUE';
		$idprof_mandatory ='SOCIETE_IDPROF'.($i+1).'_MANDATORY';
		$idprof_invoice_mandatory ='SOCIETE_IDPROF'.($i+1).'_INVOICE_MANDATORY';
		$verif=(empty($conf->global->$idprof_unique)?false:true);
		$mandatory=(empty($conf->global->$idprof_mandatory)?false:true);
		$invoice_mandatory=(empty($conf->global->$idprof_invoice_mandatory)?false:true);

		if ($verif)
		{
			print '<td align="center"><a href="'.$_SERVER['PHP_SELF'].'?action=setprofid&value='.($i+1).'&status=0">';
			print img_picto($langs->trans("Activated"),'switch_on');
			print '</a></td>';
		}
		else
		{
			print '<td align="center"><a href="'.$_SERVER['PHP_SELF'].'?action=setprofid&value='.($i+1).'&status=1">';
			print img_picto($langs->trans("Disabled"),'switch_off');
			print '</a></td>';
		}

		if ($mandatory)
		{
			print '<td align="center"><a href="'.$_SERVER['PHP_SELF'].'?action=setprofidmandatory&value='.($i+1).'&status=0">';
			print img_picto($langs->trans("Activated"),'switch_on');
			print '</a></td>';
		}
		else
		{
			print '<td align="center"><a href="'.$_SERVER['PHP_SELF'].'?action=setprofidmandatory&value='.($i+1).'&status=1">';
			print img_picto($langs->trans("Disabled"),'switch_off');
			print '</a></td>';
		}

		if ($invoice_mandatory)
		{
			print '<td align="center"><a href="'.$_SERVER['PHP_SELF'].'?action=setprofidinvoicemandatory&value='.($i+1).'&status=0">';
			print img_picto($langs->trans("Activated"),'switch_on');
			print '</a></td>';
		}
		else
		{
			print '<td align="center"><a href="'.$_SERVER['PHP_SELF'].'?action=setprofidinvoicemandatory&value='.($i+1).'&status=1">';
			print img_picto($langs->trans("Disabled"),'switch_off');
			print '</a></td>';
		}

		print "</tr>\n";
	}
	$i++;
}

print "</table><br>\n";


print_titre($langs->trans("Other"));

// Autres options
$form=new Form($db);
$var=true;

print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="updateoptions">';

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print "<td>".$langs->trans("Parameters")."</td>\n";
print '<td align="right" width="60">'.$langs->trans("Value").'</td>'."\n";
print '<td width="80">&nbsp;</td></tr>'."\n";

// Utilisation formulaire Ajax sur choix societe
$var=!$var;
print "<tr ".$bc[$var].">";
print '<td width="80%">'.$form->textwithpicto($langs->trans("DelaiedFullListToSelectCompany"),$langs->trans('UseSearchToSelectCompanyTooltip'),1).' </td>';
if (! $conf->use_javascript_ajax)
{
	print '<td class="nowrap" align="right" colspan="2">';
	print $langs->trans("NotAvailableWhenAjaxDisabled");
	print "</td>";
}
else
{
	print '<td width="60" align="right">';
	$arrval=array('0'=>$langs->trans("No"),
	'1'=>$langs->trans("Yes").' ('.$langs->trans("NumberOfKeyToSearch",1).')',
    '2'=>$langs->trans("Yes").' ('.$langs->trans("NumberOfKeyToSearch",2).')',
    '3'=>$langs->trans("Yes").' ('.$langs->trans("NumberOfKeyToSearch",3).')',
	);
	print $form->selectarray("activate_COMPANY_USE_SEARCH_TO_SELECT", $arrval, $conf->global->COMPANY_USE_SEARCH_TO_SELECT);
	print '</td><td align="right">';
	print '<input type="submit" class="button" name="COMPANY_USE_SEARCH_TO_SELECT" value="'.$langs->trans("Modify").'">';
	print "</td>";
}
print '</tr>';

$var=!$var;
print "<tr ".$bc[$var].">";
print '<td width="80%">'.$form->textwithpicto($langs->trans("DelaiedFullListToSelectContact"),$langs->trans('UseSearchToSelectContactTooltip'),1).'</td>';
if (! $conf->use_javascript_ajax)
{
	print '<td class="nowrap" align="right" colspan="2">';
	print $langs->trans("NotAvailableWhenAjaxDisabled");
	print "</td>";
}
else
{
	print '<td width="60" align="right">';
	$arrval=array('0'=>$langs->trans("No"),
	'1'=>$langs->trans("Yes").' ('.$langs->trans("NumberOfKeyToSearch",1).')',
	'2'=>$langs->trans("Yes").' ('.$langs->trans("NumberOfKeyToSearch",2).')',
	'3'=>$langs->trans("Yes").' ('.$langs->trans("NumberOfKeyToSearch",3).')',
	);
	print $form->selectarray("activate_CONTACT_USE_SEARCH_TO_SELECT", $arrval, $conf->global->CONTACT_USE_SEARCH_TO_SELECT);
	print '</td><td align="right">';
	print '<input type="submit" class="button" name="CONTACT_USE_SEARCH_TO_SELECT" value="'.$langs->trans("Modify").'">';
	print "</td>";
}
print '</tr>';


$var=!$var;
print "<tr ".$bc[$var].">";
print '<td width="80%">'.$langs->trans("AddRefInList").'</td>';
print '<td>&nbsp</td>';
print '<td align="center">';
if (!empty($conf->global->SOCIETE_ADD_REF_IN_LIST))
{
	print '<a href="'.$_SERVER['PHP_SELF'].'?action=setaddrefinlist&value=0">';
	print img_picto($langs->trans("Activated"),'switch_on');

}
else
{
	print '<a href="'.$_SERVER['PHP_SELF'].'?action=setaddrefinlist&value=1">';
	print img_picto($langs->trans("Disabled"),'switch_off');
}
print '</a></td>';
print '</tr>';


/*
// COMPANY_USE_SEARCH_TO_SELECT
$var=!$var;
print "<tr ".$bc[$var].">";
print '<td width="80%">'.$langs->trans("HideClosedThirdpartyComboBox").'</td>';
if (! empty($conf->global->COMPANY_HIDE_INACTIVE_IN_COMBOBOX))
{
	print '<td align="center"><a href="'.$_SERVER['PHP_SELF'].'?action=sethideinactivethirdparty&status=0">';
	print img_picto($langs->trans("Activated"),'switch_on');
	print '</a></td>';
}
else
{
	print '<td align="center"><a href="'.$_SERVER['PHP_SELF'].'?action=sethideinactivethirdparty&status=1">';
	print img_picto($langs->trans("Disabled"),'switch_off');
	print '</a></td>';
}
print '</tr>';
*/

print '</table>';

print '</form>';


dol_fiche_end();

$db->close();

llxFooter();
