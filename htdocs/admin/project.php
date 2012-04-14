<?php
/* Copyright (C) 2010 		Regis Houssin		<regis@dolibarr.fr>
 * Copyright (C) 2011 		Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2011-2012 	Juanjo Menent		<jmenent@2byte.es>
 * Copyright (C) 2011-2012  Philippe Grand	    <philippe.grand@atoo-net.com>
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
 *  \file       htdocs/admin/project.php
 *  \ingroup    project
 *  \brief      Page to setup project module
 */

require("../main.inc.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/admin.lib.php");
require_once(DOL_DOCUMENT_ROOT.'/projet/class/project.class.php');
require_once(DOL_DOCUMENT_ROOT.'/projet/class/task.class.php');

$langs->load("admin");
$langs->load("errors");
$langs->load("other");
$langs->load("projects");

if (!$user->admin) accessforbidden();

$value = GETPOST('value','alpha');
$action = GETPOST('action','alpha');
$label = GETPOST('label','alpha');
$scandir = GETPOST('scandir','alpha');
$type='project';


/*
 * Actions
 */

if ($action == 'updateMask')
{
	$maskconstproject=GETPOST('maskconstproject','alpha');
	$maskproject=GETPOST('maskproject','alpha');
	
	if ($maskconstproject)  $res = dolibarr_set_const($db,$maskconstproject,$maskproject,'chaine',0,'',$conf->entity);

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
	$modele=GETPOST('module','alpha');

	$project = new Project($db);
	$project->initAsSpecimen();

	// Search template files
	$file=''; $classname=''; $filefound=0;
	$dirmodels=array_merge(array('/'),(array) $conf->modules_parts['models']);
	foreach($dirmodels as $reldir)
	{
	    $file=dol_buildpath($reldir."core/modules/project/pdf/pdf_".$modele.".modules.php",0);
		if (file_exists($file))
		{
			$filefound=1;
			$classname = "pdf_".$modele;
			break;
		}
	}
	
	if ($filefound)
	{
		require_once($file);

		$module = new $classname($db);

		if ($module->write_file($project,$langs) > 0)
		{
	 	 	header("Location: ".DOL_URL_ROOT."/document.php?modulepart=project&file=SPECIMEN.pdf");
	  		return;
		}
		else
		{
			$mesg='<div class="error">'.$obj->error.'</div>';
			dol_syslog($obj->error, LOG_ERR);
		}
	}
	else
	{
		$mesg='<div class="error">'.$langs->trans("ErrorModuleNotFound").'</div>';
		dol_syslog($langs->trans("ErrorModuleNotFound"), LOG_ERR);
	}
}

if ($action == 'set')
{
	$ret = addDocumentModel($value, $type, $label, $scandir);
}

if ($action == 'del')
{
	$ret = delDocumentModel($value, $type);
	if ($ret > 0)
	{
        if ($conf->global->PROJECT_ADDON_PDF == "$value") dolibarr_del_const($db, 'PROJECT_ADDON_PDF',$conf->entity);
	}
}

if ($action == 'setdoc')
{
	if (dolibarr_set_const($db, "PROJECT_ADDON_PDF",$value,'chaine',0,'',$conf->entity))
	{
		// La constante qui a ete lue en avant du nouveau set
		// on passe donc par une variable pour avoir un affichage coherent
		$conf->global->PROJECT_ADDON_PDF = $value;
	}

	// On active le modele
	$ret = delDocumentModel($value, $type);
	if ($ret > 0)
	{
		$ret = addDocumentModel($value, $type, $label, $scandir);
	}
}

if ($action == 'setmod')
{
	// TODO Verifier si module numerotation choisi peut etre active
	// par appel methode canBeActivated

	dolibarr_set_const($db, "PROJECT_ADDON",$value,'chaine',0,'',$conf->entity);
}

/*
 * View
 */

$dirmodels=array_merge(array('/'),(array) $conf->modules_parts['models']);

llxHeader();

$form=new Form($db);

$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';
print_fiche_titre($langs->trans("ProjectsSetup"),$linkback,'setup');

print "<br>";


// Project numbering module
print_titre($langs->trans("ProjectsNumberingModules"));

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td width="100">'.$langs->trans("Name").'</td>';
print '<td>'.$langs->trans("Description").'</td>';
print '<td>'.$langs->trans("Example").'</td>';
print '<td align="center" width="60">'.$langs->trans("Activated").'</td>';
print '<td align="center" width="80">'.$langs->trans("Infos").'</td>';
print "</tr>\n";

clearstatcache();

foreach ($dirmodels as $reldir)
{
	$dir = dol_buildpath($reldir."core/modules/project/");

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

					require_once(DOL_DOCUMENT_ROOT ."/core/modules/project/".$file.".php");

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
						if ($conf->global->PROJECT_ADDON == 'mod_'.$classname)
						{
							print img_picto($langs->trans("Activated"),'switch_on');
						}
						else
						{
							print '<a href="'.$_SERVER["PHP_SELF"].'?action=setmod&amp;value=mod_'.$classname.'" alt="'.$langs->trans("Default").'">'.img_picto($langs->trans("Disabled"),'switch_off').'</a>';
						}
						print '</td>';

						$project=new Project($db);
						$project->initAsSpecimen();

						// Info
						$htmltooltip='';
						$htmltooltip.=''.$langs->trans("Version").': <b>'.$module->getVersion().'</b><br>';
						$nextval=$module->getNextValue($mysoc,$project);
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
 * Modeles documents for projects
 */

print_titre($langs->trans("ProjectsModelModule"));

// Defini tableau def de modele
$type='project';
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

print "<table class=\"noborder\" width=\"100%\">\n";
print "<tr class=\"liste_titre\">\n";
print '  <td width="100">'.$langs->trans("Name")."</td>\n";
print "  <td>".$langs->trans("Description")."</td>\n";
print '<td align="center" width="60">'.$langs->trans("Activated")."</td>\n";
print '<td align="center" width="60">'.$langs->trans("Default")."</td>\n";
print '<td align="center" width="80">'.$langs->trans("Infos").'</td>';
print "</tr>\n";

clearstatcache();

$var=true;
foreach ($dirmodels as $reldir)
{
	$dir = dol_buildpath($reldir."core/modules/project/pdf/");

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
					print "<tr ".$bc[$var].">\n  <td>$name";
					print "</td>\n  <td>\n";
					require_once($dir.$file);
					$module = new $classname($db);
					print $module->description;
					print "</td>\n";

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

					// Defaut
					print "<td align=\"center\">";
					if ($conf->global->PROJECT_ADDON_PDF == "$name")
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
					$link='<a href="'.$_SERVER["PHP_SELF"].'?action=specimen&amp;module='.$name.'">'.img_object($langs->trans("Preview"),'order').'</a>';
					print $form->textwithpicto(' &nbsp; &nbsp; '.$link,$htmltooltip,-1,0);
					print '</td>';

					print "</tr>\n";
				}
			}
			closedir($handle);
		}
	}
}

print '</table><br/>';

dol_htmloutput_mesg($mesg);

$db->close();

llxFooter();
?>