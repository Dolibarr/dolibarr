<?php
/* Copyright (C) 2011-2013      Juanjo Menent	    <jmenent@2byte.es>
 * Copyright (C) 2011-2013      Philippe Grand	    <philippe.grand@atoo-net.com>
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
 *	\file       htdocs/admin/contract.php
 *	\ingroup    contract
 *	\brief      Setup page of module Contracts
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/contrat/class/contrat.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/contract.lib.php';

$langs->load("admin");
$langs->load("errors");
$langs->load("contracts");

if (!$user->admin) accessforbidden();

$action = GETPOST('action','alpha');
$value = GETPOST('value','alpha');

if (empty($conf->global->CONTRACT_ADDON))
{
    $conf->global->CONTRACT_ADDON='mod_contract_serpis';
}


/*
 * Actions
 */

if ($action == 'updateMask')
{
    $maskconst = GETPOST('maskconstcontract','alpha');
    $maskvalue =  GETPOST('maskcontract','alpha');
    if ($maskconst) $res = dolibarr_set_const($db,$maskconst,$maskvalue,'chaine',0,'',$conf->entity);

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

if ($action == 'setmod')
{
    dolibarr_set_const($db, "CONTRACT_ADDON",$value,'chaine',0,'',$conf->entity);
}

else if ($action == 'specimen') // For contract
{
	$modele= GETPOST('module','alpha');

	$contract = new Contrat($db);
	$contract->initAsSpecimen();

	// Search template files
	$file=''; $classname=''; $filefound=0;
	$dirmodels=array_merge(array('/'),(array) $conf->modules_parts['models']);
	foreach($dirmodels as $reldir)
	{
	    $file=dol_buildpath($reldir."core/modules/contract/doc/pdf_".$modele.".modules.php",0);
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

		$module = new $classname($db);

		if ($module->write_file($contract,$langs) > 0)
		{
			header("Location: ".DOL_URL_ROOT."/document.php?modulepart=contract&file=SPECIMEN.pdf");
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
        if ($conf->global->CONTRACT_ADDON_PDF == "$value") dolibarr_del_const($db, 'CONTRACT_ADDON_PDF',$conf->entity);
	}
}

// Set default model
else if ($action == 'setdoc')
{
	if (dolibarr_set_const($db, "CONTRACT_ADDON_PDF",$value,'chaine',0,'',$conf->entity))
	{
		// La constante qui a ete lue en avant du nouveau set
		// on passe donc par une variable pour avoir un affichage coherent
		$conf->global->CONTRACT_ADDON_PDF = $value;
	}

	// On active le modele
	$ret = delDocumentModel($value, $type);
	if ($ret > 0)
	{
		$ret = addDocumentModel($value, $type, $label, $scandir);
	}
}

else if ($action == 'setmod')
{
	// TODO Verifier si module numerotation choisi peut etre active
	// par appel methode canBeActivated

	dolibarr_set_const($db, "CONTRACT_ADDON",$value,'chaine',0,'',$conf->entity);
}

else if ($action == 'set_CONTRAT_FREE_TEXT')
{
	$freetext= GETPOST('CONTRAT_FREE_TEXT','alpha');
	$res = dolibarr_set_const($db, "CONTRAT_FREE_TEXT",$freetext,'chaine',0,'',$conf->entity);

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

else if ($action == 'set_CONTRAT_DRAFT_WATERMARK')
{
	$draft= GETPOST('CONTRAT_DRAFT_WATERMARK','alpha');

	$res = dolibarr_set_const($db, "CONTRAT_DRAFT_WATERMARK",trim($draft),'chaine',0,'',$conf->entity);

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

elseif ($action == 'set_CONTRAT_PRINT_PRODUCTS')
{
	$val = GETPOST('CONTRAT_PRINT_PRODUCTS','alpha');
	$res = dolibarr_set_const($db, "CONTRAT_PRINT_PRODUCTS",($val == 'on'),'bool',0,'',$conf->entity);

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

/*
 * View
 */

$dirmodels=array_merge(array('/'),(array) $conf->modules_parts['models']);
 
llxHeader();

$dir=DOL_DOCUMENT_ROOT."/core/modules/contract/";
$form=new Form($db);

$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';
print_fiche_titre($langs->trans("ContractsSetup"),$linkback,'setup');

print "<br>";

$head=contract_admin_prepare_head();

dol_fiche_head($head, 'contract', $langs->trans("ModuleSetup"));

/*
 * Contracts Numbering model
 */

print_titre($langs->trans("ContractsNumberingModules"));

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td width="100">'.$langs->trans("Name").'</td>';
print '<td>'.$langs->trans("Description").'</td>';
print '<td>'.$langs->trans("Example").'</td>';
print '<td align="center" width="60">'.$langs->trans("Status").'</td>';
print '<td align="center" width="16">'.$langs->trans("ShortInfo").'</td>';
print "</tr>\n";

clearstatcache();

$dir = "../core/modules/contract/";
$handle = opendir($dir);
if (is_resource($handle))
{
    $var=true;

    while (($file = readdir($handle))!==false)
    {
        if (substr($file, 0, 13) == 'mod_contract_' && substr($file, dol_strlen($file)-3, 3) == 'php')
        {
            $file = substr($file, 0, dol_strlen($file)-4);

            require_once DOL_DOCUMENT_ROOT ."/core/modules/contract/".$file.'.php';

            $module = new $file;

            // Show modules according to features level
            if ($module->version == 'development'  && $conf->global->MAIN_FEATURES_LEVEL < 2) continue;
            if ($module->version == 'experimental' && $conf->global->MAIN_FEATURES_LEVEL < 1) continue;

            if ($module->isEnabled())
            {
                $var=!$var;
                print '<tr '.$bc[$var].'><td>'.$module->nom."</td>\n";
                print '<td>';
                print $module->info();
                print '</td>';

                // Show example of numbering model
                print '<td class="nowrap">';
                $tmp=$module->getExample();
                if (preg_match('/^Error/',$tmp)) { $langs->load("errors"); print '<div class="error">'.$langs->trans($tmp).'</div>'; }
                elseif ($tmp=='NotConfigured') print $langs->trans($tmp);
                else print $tmp;
                print '</td>'."\n";

                print '<td align="center">';
                if ($conf->global->CONTRACT_ADDON == "$file")
                {
                    print img_picto($langs->trans("Activated"),'switch_on');
                }
                else
                {
                    print '<a href="'.$_SERVER["PHP_SELF"].'?action=setmod&amp;value='.$file.'&amp;scandir='.$module->scandir.'&amp;label='.urlencode($module->name).'">';
                    print img_picto($langs->trans("Disabled"),'switch_off');
                    print '</a>';
                }
                print '</td>';

                $contract=new Contrat($db);
                $contract->initAsSpecimen();

                // Info
                $htmltooltip='';
                $htmltooltip.=''.$langs->trans("Version").': <b>'.$module->getVersion().'</b><br>';
                $nextval=$module->getNextValue($mysoc,$contract);
                if ("$nextval" != $langs->trans("NotAvailable"))	// Keep " on nextval
                {
                    $htmltooltip.=''.$langs->trans("NextValue").': ';
                    if ($nextval)
                    {
                        $htmltooltip.=$nextval.'<br>';
                    }
                    else
                    {
                        $htmltooltip.=$langs->trans($module->error).'<br>';
                    }
                }

                print '<td align="center">';
                print $form->textwithpicto('',$htmltooltip,1,0);
                print '</td>';

                print '</tr>';
            }
        }
    }
    closedir($handle);
}

print '</table><br>';

/*
 *  Documents models for Contracts
 */

print_titre($langs->trans("TemplatePDFInterventions"));

// Defini tableau def des modeles
$type='contrat';
$def = array();
$sql = "SELECT nom";
$sql.= " FROM ".MAIN_DB_PREFIX."document_model";
$sql.= " WHERE type = '".$type."'";
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
print '<td>'.$langs->trans("Name").'</td>';
print '<td>'.$langs->trans("Description").'</td>';
print '<td align="center" width="60">'.$langs->trans("Status")."</td>\n";
print '<td align="center" width="60">'.$langs->trans("Default")."</td>\n";
print '<td align="center" width="80">'.$langs->trans("ShortInfo").'</td>';
print '<td align="center" width="80">'.$langs->trans("Preview").'</td>';
print "</tr>\n";

clearstatcache();

$var=true;
foreach ($dirmodels as $reldir)
{
	$dir = dol_buildpath($reldir."core/modules/contract/doc/");

	if (is_dir($dir))
	{
		$handle=opendir($dir);
		if (is_resource($handle))
		{
		    while (($file = readdir($handle))!==false)
		    {
		    	if (substr($file, dol_strlen($file) -12) == '.modules.php' && substr($file,0,4) == 'pdf_')
		    	{
		    		$name = substr($file, 4, dol_strlen($file) -16);
		    		$classname = substr($file, 0, dol_strlen($file) -12);

		    		$var=!$var;

		    		print '<tr '.$bc[$var].'><td>';
		    		echo "$name";
		    		print "</td><td>\n";
		    		require_once $dir.$file;
		    		$module = new $classname($db);
		    		print $module->description;
		    		print '</td>';

		    		// Active
		    		if (in_array($name, $def))
		    		{
		    			print "<td align=\"center\">\n";
		    			print '<a href="'.$_SERVER["PHP_SELF"].'?action=del&amp;value='.$name.'&amp;scandir='.$module->scandir.'&amp;label='.urlencode($module->name).'">';
		    			print img_picto($langs->trans("Enabled"),'switch_on');
		    			print '</a>';
		    			print "</td>";
		    		}
		    		else
		    		{
		    			print "<td align=\"center\">\n";
		    			print '<a href="'.$_SERVER["PHP_SELF"].'?action=set&amp;value='.$name.'&amp;scandir='.$module->scandir.'&amp;label='.urlencode($module->name).'">'.img_picto($langs->trans("Disabled"),'switch_off').'</a>';
		    			print "</td>";
		    		}

		    		// Default
		    		print "<td align=\"center\">";
		    		if ($conf->global->CONTRACT_ADDON_PDF == "$name")
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
		    		$htmltooltip.='<br>'.$langs->trans("Width").'/'.$langs->trans("Height").': '.$module->page_largeur.'/'.$module->page_hauteur;
		    		$htmltooltip.='<br><br><u>'.$langs->trans("FeaturesSupported").':</u>';
		    		$htmltooltip.='<br>'.$langs->trans("Logo").': '.yn($module->option_logo,1,1);
		    		$htmltooltip.='<br>'.$langs->trans("PaymentMode").': '.yn($module->option_modereg,1,1);
		    		$htmltooltip.='<br>'.$langs->trans("PaymentConditions").': '.yn($module->option_condreg,1,1);
		    		$htmltooltip.='<br>'.$langs->trans("MultiLanguage").': '.yn($module->option_multilang,1,1);
		    		$htmltooltip.='<br>'.$langs->trans("WatermarkOnDraftOrders").': '.yn($module->option_draft_watermark,1,1);
		    		print '<td align="center">';
		    		print $form->textwithpicto('',$htmltooltip,-1,0);
		    		print '</td>';
		    		
		    		// Preview
		    		$link='<a href="'.$_SERVER["PHP_SELF"].'?action=specimen&module='.$name.'">'.img_object($langs->trans("Preview"),'contrat').'</a>';
		    		print '<td align="center">';
		    		print $link;
		    		print '</td>';

		    		print '</tr>';
		    	}
		    }
		    closedir($handle);
		}
	}
}

print '</table>';
print "<br>";

/*
 * Other options
 *
 */

print_titre($langs->trans("OtherOptions"));
print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Parameter").'</td>';
print '<td align="center" width="60">'.$langs->trans("Value").'</td>';
print "<td>&nbsp;</td>\n";
print "</tr>\n";
$var=true;

$var=! $var;
print '<form action="'.$_SERVER["PHP_SELF"].'" method="post">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="set_CONTRAT_FREE_TEXT">';
print '<tr '.$bc[$var].'><td colspan="2">';
print $langs->trans("FreeLegalTextOnContracts").' ('.$langs->trans("AddCRIfTooLong").')<br>';
print '<textarea name="CONTRAT_FREE_TEXT" class="flat" cols="120">'.$conf->global->CONTRAT_FREE_TEXT.'</textarea>';
print '</td><td align="right">';
print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
print "</td></tr>\n";
print '</form>';

//Use draft Watermark
$var=!$var;
print "<form method=\"post\" action=\"".$_SERVER["PHP_SELF"]."\">";
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print "<input type=\"hidden\" name=\"action\" value=\"set_CONTRAT_DRAFT_WATERMARK\">";
print '<tr '.$bc[$var].'><td colspan="2">';
print $langs->trans("WatermarkOnDraftContractCards").'<br>';
print '<input size="50" class="flat" type="text" name="CONTRAT_DRAFT_WATERMARK" value="'.$conf->global->CONTRAT_DRAFT_WATERMARK.'">';
print '</td><td align="right">';
print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
print "</td></tr>\n";

// print products on fichinter
$var=! $var;
print '<form action="'.$_SERVER["PHP_SELF"].'" method="post">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="set_CONTRAT_PRINT_PRODUCTS">';
print '<tr '.$bc[$var].'><td>';
print $langs->trans("PrintProductsOnContract").' ('.$langs->trans("PrintProductsOnContractDetails").')</td>';
print '<td align="center"><input type="checkbox" name="CONTRAT_PRINT_PRODUCTS" ';
if ($conf->global->CONTRAT_PRINT_PRODUCTS)
	print 'checked="checked" ';
print '/>';
print '</td><td align="right">';
print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
print "</td></tr>\n";

print '</form>';


print '</table>';

print '<br>';

$db->close();

llxFooter();
?>
