<?php
/* Copyright (C) 2003-2004 Rodolphe Quiedeville         <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2015 Laurent Destailleur          <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Sebastien Di Cintio          <sdicintio@ressource-toi.org>
 * Copyright (C) 2004      Benoit Mortier               <benoit.mortier@opensides.be>
 * Copyright (C) 2005-2014 Regis Houssin                <regis.houssin@capnetworks.com>
 * Copyright (C) 2008      Raphael Bertrand (Resultic)  <raphael.bertrand@resultic.fr>
 * Copyright (C) 2011-2013 Juanjo Menent			    <jmenent@2byte.es>
 * Copyright (C) 2011-2015 Philippe Grand			    <philippe.grand@atoo-net.com>
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
 *	\file       htdocs/admin/expensereport.php
 *	\ingroup    expensereport
 *	\brief      Setup page of module ExpenseReport
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/expensereport.lib.php';
require_once DOL_DOCUMENT_ROOT.'/expensereport/class/expensereport.class.php';

$langs->load("admin");
$langs->load("errors");
$langs->load("trips");
$langs->load('other');

if (! $user->admin) accessforbidden();

$action = GETPOST('action','alpha');
$value = GETPOST('value','alpha');
$label = GETPOST('label','alpha');
$scandir = GETPOST('scan_dir','alpha');
$type='expensereport';


/*
 * Actions
 */

include DOL_DOCUMENT_ROOT.'/core/actions_setmoduleoptions.inc.php';

if ($action == 'updateMask')
{
	$maskconst=GETPOST('maskconst','alpha');
	$maskvalue=GETPOST('maskvalue','alpha');
	if ($maskconst) $res = dolibarr_set_const($db,$maskconst,$maskvalue,'chaine',0,'',$conf->entity);

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

else if ($action == 'specimen') // For fiche inter
{
	$modele= GETPOST('module','alpha');

	$inter = new ExpenseReport($db);
	$inter->initAsSpecimen();
	$inter->status = 0;     // Force statut draft to show watermark
	$inter->fk_statut = 0;     // Force statut draft to show watermark

	// Search template files
	$file=''; $classname=''; $filefound=0;
	$dirmodels=array_merge(array('/'),(array) $conf->modules_parts['models']);
	foreach($dirmodels as $reldir)
	{
	    $file=dol_buildpath($reldir."core/modules/expensereport/doc/pdf_".$modele.".modules.php",0);
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

		if ($module->write_file($inter,$langs) > 0)
		{
			header("Location: ".DOL_URL_ROOT."/document.php?modulepart=expensereport&file=SPECIMEN.pdf");
			return;
		}
		else
		{
			setEventMessages($module->error, $module->errors,'errors');
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
else if ($action == 'set')
{
	$ret = addDocumentModel($value, $type, $label, $scandir);
	if ($ret > 0 && empty($conf->global->EXPENSEREPORT_ADDON_PDF))
	{
		dolibarr_set_const($db, 'EXPENSEREPORT_ADDON_PDF', $value,'chaine',0,'',$conf->entity);
	}
}

else if ($action == 'del')
{
	$ret = delDocumentModel($value, $type);
	if ($ret > 0)
	{
        if ($conf->global->EXPENSEREPORT_ADDON_PDF == "$value") dolibarr_del_const($db, 'EXPENSEREPORT_ADDON_PDF',$conf->entity);
	}
}

// Set default model
else if ($action == 'setdoc')
{
	if (dolibarr_set_const($db, "EXPENSEREPORT_ADDON_PDF",$value,'chaine',0,'',$conf->entity))
	{
		// La constante qui a ete lue en avant du nouveau set
		// on passe donc par une variable pour avoir un affichage coherent
		$conf->global->EXPENSEREPORT_ADDON_PDF = $value;
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

	dolibarr_set_const($db, "EXPENSEREPORT_ADDON",$value,'chaine',0,'',$conf->entity);
}

else if ($action == 'setoptions')
{
    $db->begin();

	$freetext= GETPOST('EXPENSEREPORT_FREE_TEXT','none');	// No alpha here, we want exact string
	$res1 = dolibarr_set_const($db, "EXPENSEREPORT_FREE_TEXT",$freetext,'chaine',0,'',$conf->entity);

	$draft= GETPOST('EXPENSEREPORT_DRAFT_WATERMARK','alpha');
	$res2 = dolibarr_set_const($db, "EXPENSEREPORT_DRAFT_WATERMARK",trim($draft),'chaine',0,'',$conf->entity);

	if (! $res1 > 0 || ! $res2 > 0) $error++;

 	if (! $error)
    {
        $db->commit();
        setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
    }
    else
    {
        $db->rollback();
        setEventMessages($langs->trans("Error"), null, 'errors');
    }
}


/*
 * View
 */

$dirmodels=array_merge(array('/'),(array) $conf->modules_parts['models']);

llxHeader('',$langs->trans("ExpenseReportsSetup"));

$form=new Form($db);

$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1">'.$langs->trans("BackToModuleList").'</a>';
print load_fiche_titre($langs->trans("ExpenseReportsSetup"),$linkback,'title_setup');


$head=expensereport_admin_prepare_head();

dol_fiche_head($head, 'expensereport', $langs->trans("ExpenseReports"), -1, 'trip');

/*
 * Expense report numbering model
 */

print load_fiche_titre($langs->trans("ExpenseReportNumberingModules"),'','');

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Name").'</td>';
print '<td>'.$langs->trans("Description").'</td>';
print '<td class="nowrap">'.$langs->trans("Example").'</td>';
print '<td align="center" width="60">'.$langs->trans("Status").'</td>';
print '<td align="center" width="16">'.$langs->trans("ShortInfo").'</td>';
print '</tr>'."\n";

clearstatcache();

foreach ($dirmodels as $reldir)
{
	$dir = dol_buildpath($reldir."core/modules/expensereport/");

	if (is_dir($dir))
	{
		$handle = opendir($dir);
		if (is_resource($handle))
		{
			$var=true;

			while (($file = readdir($handle))!==false)
			{
				if (substr($file, 0, 18) == 'mod_expensereport_' && substr($file, dol_strlen($file)-3, 3) == 'php')
				{
					$file = substr($file, 0, dol_strlen($file)-4);

					require_once $dir.$file.'.php';

					$module = new $file($db);

					// Show modules according to features level
					if ($module->version == 'development'  && $conf->global->MAIN_FEATURES_LEVEL < 2) continue;
					if ($module->version == 'experimental' && $conf->global->MAIN_FEATURES_LEVEL < 1) continue;

					if ($module->isEnabled())
					{

						print '<tr class="oddeven"><td>'.$module->nom."</td><td>\n";
						print $module->info();
						print '</td>';

                        // Show example of numbering model
                        print '<td class="nowrap">';
                        $tmp=$module->getExample();
                        if (preg_match('/^Error/',$tmp)) print '<div class="error">'.$langs->trans($tmp).'</div>';
                        elseif ($tmp=='NotConfigured') print $langs->trans($tmp);
                        else print $tmp;
                        print '</td>'."\n";

						print '<td align="center">';
						if ($conf->global->EXPENSEREPORT_ADDON == $file)
						{
							print img_picto($langs->trans("Activated"),'switch_on');
						}
						else
						{
							print '<a href="'.$_SERVER["PHP_SELF"].'?action=setmod&amp;value='.$file.'">';
							print img_picto($langs->trans("Disabled"),'switch_off');
							print '</a>';
						}
						print '</td>';

						$exp=new ExpenseReport($db);
						$exp->initAsSpecimen();

						// Info
						$htmltooltip='';
						$htmltooltip.=''.$langs->trans("Version").': <b>'.$module->getVersion().'</b><br>';
						$nextval=$module->getNextValue($exp);
                        if ("$nextval" != $langs->trans("NotAvailable")) {  // Keep " on nextval
                            $htmltooltip.=''.$langs->trans("NextValue").': ';
                            if ($nextval) {
                                if (preg_match('/^Error/',$nextval) || $nextval=='NotConfigured')
                                    $nextval = $langs->trans($nextval);
                                $htmltooltip.=$nextval.'<br>';
                            } else {
                                $htmltooltip.=$langs->trans($module->error).'<br>';
                            }
                        }

						print '<td align="center">';
						print $form->textwithpicto('',$htmltooltip,1,0);
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
 *  Documents models for Interventions
 */

print load_fiche_titre($langs->trans("TemplatePDFExpenseReports"), '', '');

// Defini tableau def des modeles
$type='expensereport';
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
	$dir = dol_buildpath($reldir."core/modules/expensereport/doc");

	if (is_dir($dir))
	{
		$handle=opendir($dir);
		if (is_resource($handle))
		{
			while (($file = readdir($handle))!==false)
			{
				$filelist[]=$file;
			}
			closedir($handle);
			arsort($filelist);

			foreach($filelist as $file)
			{
				if (preg_match('/\.modules\.php$/i',$file) && preg_match('/^(pdf_|doc_)/',$file))
		    	{

		    		if (file_exists($dir.'/'.$file))
		    		{


		    			$name = substr($file, 4, dol_strlen($file) -16);
		    			$classname = substr($file, 0, dol_strlen($file) -12);

		    			require_once $dir.'/'.$file;
		    			$module = new $classname($db);

		    			$modulequalified=1;
		    			if ($module->version == 'development'  && $conf->global->MAIN_FEATURES_LEVEL < 2) $modulequalified=0;
		    			if ($module->version == 'experimental' && $conf->global->MAIN_FEATURES_LEVEL < 1) $modulequalified=0;

		    			if ($modulequalified)
		    			{
		    				print '<tr class="oddeven"><td width="100">';
		    				print (empty($module->name)?$name:$module->name);
		    				print "</td><td>\n";
		    				if (method_exists($module,'info')) print $module->info($langs);
		    				else print $module->description;
		    				print '</td>';

		    				// Active
		    				if (in_array($name, $def))
		    				{
		    					print "<td align=\"center\">\n";
		    					print '<a href="'.$_SERVER["PHP_SELF"].'?action=del&amp;value='.$name.'&amp;scan_dir='.$module->scandir.'&amp;label='.urlencode($module->name).'">';
		    					print img_picto($langs->trans("Enabled"),'switch_on');
		    					print '</a>';
		    					print "</td>";
		    				}
		    				else
		    				{
		    					print "<td align=\"center\">\n";
		    					print '<a href="'.$_SERVER["PHP_SELF"].'?action=set&amp;value='.$name.'&amp;scan_dir='.$module->scandir.'&amp;label='.urlencode($module->name).'">'.img_picto($langs->trans("Disabled"),'switch_off').'</a>';
		    					print "</td>";
		    				}

		    				// Default
		    				print "<td align=\"center\">";
		    				if ($conf->global->EXPENSEREPORT_ADDON_PDF == "$name")
		    				{
		    					print img_picto($langs->trans("Default"),'on');
		    				}
		    				else
		    				{
		    					print '<a href="'.$_SERVER["PHP_SELF"].'?action=setdoc&amp;value='.$name.'&amp;scan_dir='.$module->scandir.'&amp;label='.urlencode($module->name).'" alt="'.$langs->trans("Default").'">'.img_picto($langs->trans("Disabled"),'off').'</a>';
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
		    				print '<td align="center">';
		    				if ($module->type == 'pdf')
		    				{
		    					print '<a href="'.$_SERVER["PHP_SELF"].'?action=specimen&module='.$name.'">'.img_object($langs->trans("Preview"),'intervention').'</a>';
		    				}
		    				else
		    				{
		    					print img_object($langs->trans("PreviewNotAvailable"),'generic');
		    				}
		    				print '</td>';

		    				print '</tr>';
		    			}
		    		}
		    	}
		    }
		}
	}
}

print '</table>';


print '<br>';



/*
 * Other options
 */

print '<form action="'.$_SERVER["PHP_SELF"].'" method="post">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="setoptions">';

print load_fiche_titre($langs->trans("OtherOptions"), '', '');
print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Parameter").'</td>';
print '<td align="center" width="60"></td>';
print "</tr>\n";
$var=true;

$substitutionarray=pdf_getSubstitutionArray($langs, null, null, 2);
$substitutionarray['__(AnyTranslationKey)__']=$langs->trans("Translation");
$htmltext = '<i>'.$langs->trans("AvailableVariables").':<br>';
foreach($substitutionarray as $key => $val)	$htmltext.=$key.'<br>';
$htmltext.='</i>';

$var=! $var;
print '<tr class="oddeven"><td colspan="2">';
print $form->textwithpicto($langs->trans("FreeLegalTextOnExpenseReports"), $langs->trans("AddCRIfTooLong").'<br><br>'.$htmltext, 1, 'help', '', 0, 2, 'freetexttooltip').'<br>';
$variablename='EXPENSEREPORT_FREE_TEXT';
if (empty($conf->global->PDF_ALLOW_HTML_FOR_FREE_TEXT))
{
    print '<textarea name="'.$variablename.'" class="flat" cols="120">'.$conf->global->$variablename.'</textarea>';
}
else
{
    include_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
    $doleditor=new DolEditor($variablename, $conf->global->$variablename,'',80,'dolibarr_notes');
    print $doleditor->Create();
}
print '</td></tr>'."\n";

//Use draft Watermark

print '<tr class="oddeven"><td colspan="2">';
print $form->textwithpicto($langs->trans("WatermarkOnDraftExpenseReports"), $htmltext, 1, 'help', '', 0, 2, 'watermarktooltip').'<br>';
print '<input size="50" class="flat" type="text" name="EXPENSEREPORT_DRAFT_WATERMARK" value="'.$conf->global->EXPENSEREPORT_DRAFT_WATERMARK.'">';
print '</td></tr>'."\n";

print '</table>';

print '<div class="center">';
print '<input type="submit" class="button" value="'.$langs->trans("Save").'">';
print '</div>';

print '</form>';

dol_fiche_end();


llxFooter();

$db->close();
