<?php
/* Copyright (C) 2003-2008	Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2004-2011	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2004		Sebastien Di Cintio		<sdicintio@ressource-toi.org>
 * Copyright (C) 2004		Benoit Mortier			<benoit.mortier@opensides.be>
 * Copyright (C) 2004		Eric Seigne				<eric.seigne@ryxeo.com>
 * Copyright (C) 2005-2012	Regis Houssin			<regis.houssin@capnetworks.com>
 * Copyright (C) 2011-2012	Juanjo Menent			<jmenent@2byte.es>
 * Copyright (C) 2011-2013	Philippe Grand			<philippe.grand@atoo-net.com>
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
 *	\file       htdocs/admin/expedition.php
 *	\ingroup    expedition
 *	\brief      Page d'administration/configuration du module Expedition
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/expedition/class/expedition.class.php';

$langs->load("admin");
$langs->load("sendings");
$langs->load("deliveries");
$langs->load('other');

if (! $user->admin)
	accessforbidden();

$action=GETPOST('action','alpha');
$value=GETPOST('value','alpha');
$label = GETPOST('label','alpha');
$scandir = GETPOST('scandir','alpha');
$type='shipping';

if (empty($conf->global->EXPEDITION_ADDON_NUMBER))
{
    $conf->global->EXPEDITION_ADDON_NUMBER='mod_expedition_safor';
}


/*
 * Actions
 */
if ($action == 'updateMask')
{
	$maskconst=GETPOST('maskconstexpedition','alpha');
	$maskvalue=GETPOST('maskexpedition','alpha');
	if (! empty($maskconst))
		$res = dolibarr_set_const($db,$maskconst,$maskvalue,'chaine',0,'',$conf->entity);

	if (isset($res))
	{
		if ($res > 0)
			setEventMessage($langs->trans("SetupSaved"));
		else
			setEventMessage($langs->trans("Error"), 'errors');
	}
}

else if ($action == 'set_SHIPPING_FREE_TEXT')
{
	$freetext=GETPOST('SHIPPING_FREE_TEXT','alpha');
	$res = dolibarr_set_const($db, "SHIPPING_FREE_TEXT",$freetext,'chaine',0,'',$conf->entity);

	if ($res < 0)
		setEventMessage($langs->trans("SetupSaved"));
	else
		setEventMessage($langs->trans("Error"), 'errors');
}

else if ($action == 'set_SHIPPING_DRAFT_WATERMARK')
{
	$draft=GETPOST('SHIPPING_DRAFT_WATERMARK','alpha');
	$res = dolibarr_set_const($db, "SHIPPING_DRAFT_WATERMARK",trim($draft),'chaine',0,'',$conf->entity);

	if ($res < 0)
		setEventMessage($langs->trans("SetupSaved"));
	else
		setEventMessage($langs->trans("Error"), 'errors');
}

else if ($action == 'specimen')
{
	$modele=GETPOST('module','alpha');

	$exp = new Expedition($db);
	$exp->initAsSpecimen();

	// Search template files
	$file=''; $classname=''; $filefound=0;
	$dirmodels=array_merge(array('/'),(array) $conf->modules_parts['models']);
	foreach($dirmodels as $reldir)
	{
	    $file=dol_buildpath($reldir."core/modules/expedition/doc/pdf_expedition_".$modele.".modules.php",0);
		if (file_exists($file))
		{
			$filefound=1;
			$classname = "pdf_expedition_".$modele;
			break;
		}
	}

	if ($filefound)
	{
		require_once $file;

		$module = new $classname($db);

		if ($module->write_file($exp,$langs) > 0)
		{
			header("Location: ".DOL_URL_ROOT."/document.php?modulepart=expedition&file=SPECIMEN.pdf");
			return;
		}
		else
		{
			setEventMessage($module->error, 'errors');
			dol_syslog($module->error, LOG_ERR);
		}
	}
	else
	{
		setEventMessage($langs->trans("ErrorModuleNotFound"), 'errors');
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
        if ($conf->global->EXPEDITION_ADDON_PDF == "$value") dolibarr_del_const($db, 'EXPEDITION_ADDON_PDF',$conf->entity);
	}
}

// Set default model
else if ($action == 'setdoc')
{
	if (dolibarr_set_const($db, "EXPEDITION_ADDON_PDF",$value,'chaine',0,'',$conf->entity))
	{
		// La constante qui a ete lue en avant du nouveau set
		// on passe donc par une variable pour avoir un affichage coherent
		$conf->global->EXPEDITION_ADDON_PDF = $value;
	}

	// On active le modele
	$ret = delDocumentModel($value, $type);
	if ($ret > 0)
	{
		$ret = addDocumentModel($value, $type, $label, $scandir);
	}
}

else if ($action == 'setmodel')
{
	dolibarr_set_const($db, "EXPEDITION_ADDON_NUMBER",$value,'chaine',0,'',$conf->entity);
}


/*
 * View
 */

$dirmodels=array_merge(array('/'),(array) $conf->modules_parts['models']);

$form=new Form($db);

llxHeader("","");

$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';
print_fiche_titre($langs->trans("SendingsSetup"),$linkback,'setup');
print '<br>';


//if ($mesg) print $mesg.'<br>';


$h = 0;

$head[$h][0] = DOL_URL_ROOT."/admin/confexped.php";
$head[$h][1] = $langs->trans("Setup");
$h++;

$head[$h][0] = DOL_URL_ROOT."/admin/expedition.php";
$head[$h][1] = $langs->trans("Sending");
$hselected=$h;
$h++;

if (! empty($conf->global->MAIN_SUBMODULE_LIVRAISON))
{
    $head[$h][0] = DOL_URL_ROOT."/admin/livraison.php";
    $head[$h][1] = $langs->trans("Receivings");
    $h++;
}

dol_fiche_head($head, $hselected, $langs->trans("ModuleSetup"));

/*
 * Expedition numbering model
 */ 

print_titre($langs->trans("SendingsNumberingModules"));

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td width="100">'.$langs->trans("Name").'</td>';
print '<td>'.$langs->trans("Description").'</td>';
print '<td>'.$langs->trans("Example").'</td>';
print '<td align="center" width="60">'.$langs->trans("Status").'</td>';
print '<td align="center" width="80">'.$langs->trans("Infos").'</td>';
print "</tr>\n";

clearstatcache();

foreach ($dirmodels as $reldir)
{
	$dir = dol_buildpath($reldir."core/modules/expedition/");

	if (is_dir($dir))
	{
		$handle = opendir($dir);
		if (is_resource($handle))
		{
			$var=true;

			while (($file = readdir($handle))!==false)
			{
				if (substr($file, 0, 15) == 'mod_expedition_' && substr($file, dol_strlen($file)-3, 3) == 'php')
				{
					$file = substr($file, 0, dol_strlen($file)-4);

					require_once $dir.$file.'.php';

					$module = new $file;
					
					if ($module->isEnabled())
					{
						// Show modules according to features level
						if ($module->version == 'development'  && $conf->global->MAIN_FEATURES_LEVEL < 2) continue;
						if ($module->version == 'experimental' && $conf->global->MAIN_FEATURES_LEVEL < 1) continue;
					
						$var=!$var;
						print '<tr '.$bc[$var].'><td>'.$module->nom."</td>\n";
						print '<td>';
						print $module->info();
						print '</td>';

                        // Show example of numbering module
                        print '<td class="nowrap">';
                        $tmp=$module->getExample();
                        if (preg_match('/^Error/',$tmp)) { 
							$langs->load("errors"); print '<div class="error">'.$langs->trans($tmp).'</div>'; 
						}
                        elseif ($tmp=='NotConfigured') print $langs->trans($tmp);
                        else print $tmp;
                        print '</td>'."\n";

						print '<td align="center">';
						if ($conf->global->EXPEDITION_ADDON_NUMBER == "$file")
						{
							print img_picto($langs->trans("Activated"),'switch_on');
						}
						else
						{
							print '<a href="'.$_SERVER["PHP_SELF"].'?action=setmodel&amp;value='.$file.'&amp;scandir='.$module->scandir.'&amp;label='.urlencode($module->name).'">';
							print img_picto($langs->trans("Disabled"),'switch_off');
							print '</a>';
						}
						print '</td>';

						$expedition=new Expedition($db);
						$expedition->initAsSpecimen();

						// Info
						$htmltooltip='';
						$htmltooltip.=''.$langs->trans("Version").': <b>'.$module->getVersion().'</b><br>';
						$nextval=$module->getNextValue($mysoc,$expedition);
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
	}
}

print '</table><br>';


/*
 *  Documents models for Sendings Receipt
 */
print_titre($langs->trans("SendingsReceiptModel"));

// Defini tableau def de modele invoice
$type="shipping";
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
print '<td width="140">'.$langs->trans("Name").'</td>';
print '<td>'.$langs->trans("Description").'</td>';
print '<td align="center" width="60">'.$langs->trans("Status").'</td>';
print '<td align="center" width="60">'.$langs->trans("Default").'</td>';
print '<td align="center" width="80" class="nowrap">'.$langs->trans("Infos").'</td>';
print "</tr>\n";

clearstatcache();

$var=true;
foreach ($dirmodels as $reldir)
{
	$dir = dol_buildpath($reldir."core/modules/expedition/doc/");

	if (is_dir($dir))
	{
		$handle=opendir($dir);
	    if (is_resource($handle))
	    {
	    	while (($file = readdir($handle))!==false)
	    	{
	    		if (substr($file, dol_strlen($file) -12) == '.modules.php' && substr($file,0,15) == 'pdf_expedition_')
	    		{
	    			$name = substr($file, 15, dol_strlen($file) - 27);
	    			$classname = substr($file, 0, dol_strlen($file) - 12);

	    			$var=!$var;
	    			print '<tr '.$bc[$var].'><td>';
	    			print $name;
	    			print "</td><td>\n";
	    			require_once $dir.$file;
	    			$module = new $classname();

	    			print $module->description;
	    			print '</td>';

	    			// Active
	    			if (in_array($name, $def))
	    			{
	    				print "<td align=\"center\">\n";
	    				print '<a href="'.$_SERVER["PHP_SELF"].'?action=del&amp;value='.$name.'">';
	    				print img_picto($langs->trans("Activated"),'switch_on');
	    				print '</a>';
	    				print "</td>";
	    			}
	    			else
	    			{
	    				print "<td align=\"center\">\n";
	    				print '<a href="'.$_SERVER["PHP_SELF"].'?action=set&amp;value='.$name.'">'.img_picto($langs->trans("Disabled"),'switch_off').'</a>';
	    				print "</td>";
	    			}

	    			// Default
	    			print "<td align=\"center\">";
	    			if ($conf->global->EXPEDITION_ADDON_PDF == $name)
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
	    			print '<td align="center">';
	    			$link='<a href="'.$_SERVER["PHP_SELF"].'?action=specimen&module='.$name.'&amp;scandir='.$module->scandir.'&amp;label='.urlencode($module->name).'">'.img_object($langs->trans("Preview"),'sending').'</a>';
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
print '<br>';


/*
 * Other options
 *
 */
print_titre($langs->trans("OtherOptions"));

$var=true;
print "<table class=\"noborder\" width=\"100%\">";
print "<tr class=\"liste_titre\">";
print "<td>".$langs->trans("Parameter")."</td>\n";
print '<td width="60" align="center">'.$langs->trans("Value")."</td>\n";
print "<td>&nbsp;</td>\n";
print "</tr>";

$var=! $var;
print '<form action="'.$_SERVER["PHP_SELF"].'" method="post">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="set_SHIPPING_FREE_TEXT">';
print '<tr '.$bc[$var].'><td colspan="2">';
print $langs->trans("FreeLegalTextOnShippings").' ('.$langs->trans("AddCRIfTooLong").')<br>';
print '<textarea name="SHIPPING_FREE_TEXT" class="flat" cols="120">'.$conf->global->SHIPPING_FREE_TEXT.'</textarea>';
print '</td><td align="right">';
print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
print "</td></tr>\n";
print '</form>';

$var=!$var;
print '<form action="'.$_SERVER["PHP_SELF"].'" method="post">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="set_SHIPPING_DRAFT_WATERMARK">';
print '<tr '.$bc[$var].'><td colspan="2">';
print $langs->trans("WatermarkOnDraft").'<br>';
print '<input size="50" class="flat" type="text" name="SHIPPING_DRAFT_WATERMARK" value="'.$conf->global->SHIPPING_DRAFT_WATERMARK.'">';
print '</td><td align="right">';
print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
print "</td></tr>\n";
print '</form>';

print '</table>';


llxFooter();
$db->close();
?>