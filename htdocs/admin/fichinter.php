<?php
/* Copyright (C) 2003-2004 Rodolphe Quiedeville         <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2011 Laurent Destailleur          <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Sebastien Di Cintio          <sdicintio@ressource-toi.org>
 * Copyright (C) 2004      Benoit Mortier               <benoit.mortier@opensides.be>
 * Copyright (C) 2005-2011 Regis Houssin                <regis@dolibarr.fr>
 * Copyright (C) 2008 	   Raphael Bertrand (Resultic)  <raphael.bertrand@resultic.fr>
 * Copyright (C) 2011 	   Juanjo Menent			    <jmenent@2byte.es>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
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
 *	\file       htdocs/admin/fichinter.php
 *	\ingroup    fichinter
 *	\brief      Setup page of module Interventions
 */

require("../main.inc.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/admin.lib.php");
require_once(DOL_DOCUMENT_ROOT.'/fichinter/class/fichinter.class.php');

$langs->load("admin");
$langs->load("errors");
$langs->load("interventions");

if (!$user->admin)
accessforbidden();

$action = GETPOST("action");
$value = GETPOST("value");


/*
 * Actions
 */
if ($action == 'updateMask')
{
	$maskconst=GETPOST("maskconst");
	$maskvalue=GETPOST("maskvalue");
	if ($maskconst) $res = dolibarr_set_const($db,$maskconst,$maskvalue,'chaine',0,'',$conf->entity);

	if (! $res > 0) $error++;

 	if (! $error)
    {
        $mesg = "<font class=\"ok\">".$langs->trans("SetupSaved")."</font>";
    }
    else
    {
        $mesg = "<font class=\"error\">".$langs->trans("Error")."</font>";
    }
}

if ($action == 'set_FICHINTER_FREE_TEXT')
{
	$freetext= GETPOST("FICHINTER_FREE_TEXT");
	$res = dolibarr_set_const($db, "FICHINTER_FREE_TEXT",$freetext,'chaine',0,'',$conf->entity);

	if (! $res > 0) $error++;

 	if (! $error)
    {
        $mesg = "<font class=\"ok\">".$langs->trans("SetupSaved")."</font>";
    }
    else
    {
        $mesg = "<font class=\"error\">".$langs->trans("Error")."</font>";
    }
}

if ($action == 'set_FICHINTER_DRAFT_WATERMARK')
{
	$draft= GETPOST("FICHINTER_DRAFT_WATERMARK");

	$res = dolibarr_set_const($db, "FICHINTER_DRAFT_WATERMARK",trim($draft),'chaine',0,'',$conf->entity);

	if (! $res > 0) $error++;

 	if (! $error)
    {
        $mesg = "<font class=\"ok\">".$langs->trans("SetupSaved")."</font>";
    }
    else
    {
        $mesg = "<font class=\"error\">".$langs->trans("Error")."</font>";
    }
}

if ($action == 'specimen')
{
	$modele=$_GET["module"];

	$inter = new Fichinter($db);
	$inter->initAsSpecimen();

	// Charge le modele
	$dir = DOL_DOCUMENT_ROOT . "/core/modules/fichinter/";
	$file = "pdf_".$modele.".modules.php";
	if (file_exists($dir.$file))
	{
		$classname = "pdf_".$modele;
		require_once($dir.$file);

		$obj = new $classname($db);

		if ($obj->write_file($inter,$langs) > 0)
		{
			header("Location: ".DOL_URL_ROOT."/document.php?modulepart=ficheinter&file=SPECIMEN.pdf");
			return;
		}
		else
		{
			$mesg='<font class="error">'.$obj->error.'</font>';
			dol_syslog($obj->error, LOG_ERR);
		}
	}
	else
	{
		$mesg='<font class="error">'.$langs->trans("ErrorModuleNotFound").'</font>';
		dol_syslog($langs->trans("ErrorModuleNotFound"), LOG_ERR);
	}
}

if ($action == 'set')
{
	$label = GETPOST("label");
	$scandir = GETPOST("scandir");

	$type='ficheinter';
    $sql = "INSERT INTO ".MAIN_DB_PREFIX."document_model (nom, type, entity, libelle, description)";
    $sql.= " VALUES ('".$db->escape($value)."','".$type."',".$conf->entity.", ";
    $sql.= ($label?"'".$db->escape($label)."'":'null').", ";
    $sql.= (! empty($scandir)?"'".$db->escape($scandir)."'":"null");
    $sql.= ")";
	if ($db->query($sql))
	{

	}
}

if ($action == 'del')
{
	$type='ficheinter';
	$sql = "DELETE FROM ".MAIN_DB_PREFIX."document_model";
	$sql.= " WHERE nom = '".$db->escape($value)."'";
	$sql.= " AND type = '".$type."'";
	$sql.= " AND entity = ".$conf->entity;

	if ($db->query($sql))
	{
        if ($conf->global->FICHEINTER_ADDON_PDF == "$value") dolibarr_del_const($db, 'FICHEINTER_ADDON_PDF',$conf->entity);
	}
}

if ($action == 'setdoc')
{
	$label = GETPOST("label");
	$scandir = GETPOST("scandir");

	$db->begin();

	if (dolibarr_set_const($db, "FICHEINTER_ADDON_PDF",$value,'chaine',0,'',$conf->entity))
	{
		// La constante qui a ete lue en avant du nouveau set
		// on passe donc par une variable pour avoir un affichage coherent
		$conf->global->FICHEINTER_ADDON_PDF = $value;
	}

	// On active le modele
	$type='ficheinter';
	$sql_del = "DELETE FROM ".MAIN_DB_PREFIX."document_model";
	$sql_del.= " WHERE nom = '".$db->escape($value)."'";
	$sql_del.= " AND type = '".$type."'";
	$sql_del.= " AND entity = ".$conf->entity;
	dol_syslog("fichinter: sql_del=".$sql_del);
	$result1=$db->query($sql_del);

    $sql = "INSERT INTO ".MAIN_DB_PREFIX."document_model (nom, type, entity, libelle, description)";
    $sql.= " VALUES ('".$db->escape($value)."', '".$type."', ".$conf->entity.", ";
    $sql.= ($label?"'".$db->escape($label)."'":'null').", ";
    $sql.= (! empty($scandir)?"'".$db->escape($scandir)."'":"null");
    $sql.= ")";
	dol_syslog("fichinter: sql_del=".$sql_del);
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

if ($action == 'setmod')
{
	// TODO Verifier si module numerotation choisi peut etre active
	// par appel methode canBeActivated

	dolibarr_set_const($db, "FICHEINTER_ADDON",$value,'chaine',0,'',$conf->entity);
}


/*
 * View
 */

llxHeader();

$form=new Form($db);

$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';
print_fiche_titre($langs->trans("InterventionsSetup"),$linkback,'setup');

print "<br>";


print_titre($langs->trans("FicheinterNumberingModules"));

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td width="100">'.$langs->trans("Name").'</td>';
print '<td>'.$langs->trans("Description").'</td>';
print '<td>'.$langs->trans("Example").'</td>';
print '<td align="center" width="60">'.$langs->trans("Status").'</td>';
print '<td align="center" width="80">'.$langs->trans("Infos").'</td>';
print "</tr>\n";

clearstatcache();

foreach ($conf->file->dol_document_root as $dirroot)
{
	$dir = $dirroot . "/core/modules/fichinter/";

	if (is_dir($dir))
	{
		$handle = opendir($dir);
		if (is_resource($handle))
		{
			$var=true;

			while (($file = readdir($handle))!==false)
			{
				if (preg_match('/^(mod_.*)\.php$/i',$file,$reg))
				{
					$file = $reg[1];
					$classname = substr($file,4);

					require_once($dir.$file.".php");

					$module = new $file;

					// Show modules according to features level
					if ($module->version == 'development'  && $conf->global->MAIN_FEATURES_LEVEL < 2) continue;
					if ($module->version == 'experimental' && $conf->global->MAIN_FEATURES_LEVEL < 1) continue;

					if ($module->isEnabled())
					{
						$var=!$var;
						print '<tr '.$bc[$var].'><td>'.$module->nom."</td><td>\n";
						print $module->info();
						print '</td>';

                        // Show example of numbering module
                        print '<td nowrap="nowrap">';
                        $tmp=$module->getExample();
                        if (preg_match('/^Error/',$tmp)) { $langs->load("errors"); print '<div class="error">'.$langs->trans($tmp).'</div>'; }
                        elseif ($tmp=='NotConfigured') print $langs->trans($tmp);
                        else print $tmp;
                        print '</td>'."\n";

						print '<td align="center">';
						if ($conf->global->FICHEINTER_ADDON == $classname)
						{
							print img_picto($langs->trans("Activated"),'switch_on');
						}
						else
						{
							print '<a href="'.$_SERVER["PHP_SELF"].'?action=setmod&amp;value='.$classname.'" alt="'.$langs->trans("Default").'">'.img_picto($langs->trans("Disabled"),'switch_off').'</a>';
						}
						print '</td>';

						$ficheinter=new Fichinter($db);
						$ficheinter->initAsSpecimen();

						// Info
						$htmltooltip='';
						$htmltooltip.=''.$langs->trans("Version").': <b>'.$module->getVersion().'</b><br>';
						$nextval=$module->getNextValue($mysoc,$ficheinter);
						if ($nextval != $langs->trans("NotAvailable"))
						{
							$htmltooltip.=''.$langs->trans("NextValue").': '.$nextval;
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
	}
}

print '</table><br>';



print_titre($langs->trans("TemplatePDFInterventions"));

// Defini tableau def des modeles
$type='ficheinter';
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
print '<td align="center" width="80">'.$langs->trans("Infos").'</td>';
print "</tr>\n";

clearstatcache();

$var=true;
foreach ($conf->file->dol_document_root as $dirroot)
{
	$dir = $dirroot . "/core/modules/fichinter/";

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
		    		require_once($dir.$file);
		    		$module = new $classname();
		    		print $module->description;
		    		print '</td>';

		    		// Active
		    		if (in_array($name, $def))
		    		{
		    			print "<td align=\"center\">\n";
		    			//if ($conf->global->FICHEINTER_ADDON_PDF != "$name")
		    			//{
		    				print '<a href="'.$_SERVER["PHP_SELF"].'?action=del&amp;value='.$name.'&amp;scandir='.$module->scandir.'&amp;label='.urlencode($module->name).'">';
		    				print img_picto($langs->trans("Enabled"),'switch_on');
		    				print '</a>';
		    			//}
		    			//else
		    			//{
		    			//	print img_picto($langs->trans("Enabled"),'switch_on');
		    			//}
		    			print "</td>";
		    		}
		    		else
		    		{
		    			print "<td align=\"center\">\n";
		    			print '<a href="'.$_SERVER["PHP_SELF"].'?action=set&amp;value='.$name.'&amp;scandir='.$module->scandir.'&amp;label='.urlencode($module->name).'">'.img_picto($langs->trans("Disabled"),'switch_off').'</a>';
		    			print "</td>";
		    		}

		    		// Defaut
		    		print "<td align=\"center\">";
		    		if ($conf->global->FICHEINTER_ADDON_PDF == "$name")
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
		    		$link='<a href="'.$_SERVER["PHP_SELF"].'?action=specimen&module='.$name.'">'.img_object($langs->trans("Preview"),'intervention').'</a>';
		    		print $form->textwithpicto(' &nbsp; &nbsp; '.$link,$htmltooltip,-1,0);
		    		print '</td>';

		    		print '</tr>';
		    	}
		    }
		    closedir($handle);
		}
	}
}

print '</table>';

//Autres Options
print "<br>";
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
print '<input type="hidden" name="action" value="set_FICHINTER_FREE_TEXT">';
print '<tr '.$bc[$var].'><td colspan="2">';
print $langs->trans("FreeLegalTextOnInterventions").' ('.$langs->trans("AddCRIfTooLong").')<br>';
print '<textarea name="FICHINTER_FREE_TEXT" class="flat" cols="120">'.$conf->global->FICHINTER_FREE_TEXT.'</textarea>';
print '</td><td align="right">';
print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
print "</td></tr>\n";
print '</form>';

//Use draft Watermark
$var=!$var;
print "<form method=\"post\" action=\"".$_SERVER["PHP_SELF"]."\">";
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print "<input type=\"hidden\" name=\"action\" value=\"set_FICHINTER_DRAFT_WATERMARK\">";
print '<tr '.$bc[$var].'><td colspan="2">';
print $langs->trans("WatermarkOnDraftInterventionCards").'<br>';
print '<input size="50" class="flat" type="text" name="FICHINTER_DRAFT_WATERMARK" value="'.$conf->global->FICHINTER_DRAFT_WATERMARK.'">';
print '</td><td align="right">';
print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
print "</td></tr>\n";
print '</form>';


print '</table>';

print '<br>';

dol_htmloutput_mesg($mesg);

$db->close();

llxFooter();
?>
