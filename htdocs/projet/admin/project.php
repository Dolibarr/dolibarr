<?php
/* Copyright (C) 2010-2014	Regis Houssin		<regis.houssin@inodbox.com>
 * Copyright (C) 2011-2016	Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2011-2015	Juanjo Menent		<jmenent@2byte.es>
 * Copyright (C) 2011-2018	Philippe Grand		<philippe.grand@atoo-net.com>
 * Copyright (C) 2013		Florian Henry		<florian.henry@open-concept.pro>
 * Copyright (C) 2015       Marcos García       <marcosgdf@gmail.com>
 * Copyright (C) 2018		Ferran Marcet		<fmarcet@2byte.es>
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
 *  \file       htdocs/admin/project.php
 *  \ingroup    project
 *  \brief      Page to setup project module
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/project.lib.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/task.class.php';

// Load translation files required by the page
$langs->loadLangs(array('admin', 'errors', 'other', 'projects'));

if (!$user->admin) accessforbidden();

$value = GETPOST('value','alpha');
$action = GETPOST('action','alpha');
$label = GETPOST('label','alpha');
$scandir = GETPOST('scan_dir','alpha');
$type='project';


/*
 * Actions
 */

include DOL_DOCUMENT_ROOT.'/core/actions_setmoduleoptions.inc.php';

if ($action == 'setmainoptions')
{
	if (GETPOST('PROJECT_USE_OPPORTUNITIES')) dolibarr_set_const($db, "PROJECT_USE_OPPORTUNITIES",GETPOST('PROJECT_USE_OPPORTUNITIES'),'chaine',0,'',$conf->entity);
	else dolibarr_del_const($db, "PROJECT_USE_OPPORTUNITIES", $conf->entity);

	// Warning, the constant saved and used in code is PROJECT_HIDE_TASKS
	if (GETPOST('PROJECT_USE_TASKS')) dolibarr_del_const($db, "PROJECT_HIDE_TASKS", $conf->entity);
	else dolibarr_set_const($db, "PROJECT_HIDE_TASKS",1,'chaine',0,'',$conf->entity);
}

else if ($action == 'updateMask')
{
	$maskconstproject=GETPOST('maskconstproject','alpha');
	$maskproject=GETPOST('maskproject','alpha');

	if ($maskconstproject)  $res = dolibarr_set_const($db,$maskconstproject,$maskproject,'chaine',0,'',$conf->entity);

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

if ($action == 'updateMaskTask')
{
	$maskconstmasktask=GETPOST('maskconsttask','alpha');
	$masktaskt=GETPOST('masktask','alpha');

	if ($maskconstmasktask)  $res = dolibarr_set_const($db,$maskconstmasktask,$masktaskt,'chaine',0,'',$conf->entity);

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

else if ($action == 'specimen')
{
	$modele=GETPOST('module','alpha');

	$project = new Project($db);
	$project->initAsSpecimen();

	// Search template files
	$file=''; $classname=''; $filefound=0;
	$dirmodels=array_merge(array('/'),(array) $conf->modules_parts['models']);
	foreach($dirmodels as $reldir)
	{
		$file=dol_buildpath($reldir."core/modules/project/doc/pdf_".$modele.".modules.php",0);
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

		if ($module->write_file($project,$langs) > 0)
		{
			header("Location: ".DOL_URL_ROOT."/document.php?modulepart=project&file=SPECIMEN.pdf");
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

else if ($action == 'specimentask')
{
	$modele=GETPOST('module','alpha');

	$project = new Project($db);
	$project->initAsSpecimen();

	// Search template files
	$file=''; $classname=''; $filefound=0;
	$dirmodels=array_merge(array('/'),(array) $conf->modules_parts['models']);
	foreach($dirmodels as $reldir)
	{
		$file=dol_buildpath($reldir."core/modules/project/task/doc/pdf_".$modele.".modules.php",0);
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

		if ($module->write_file($project,$langs) > 0)
		{
			header("Location: ".DOL_URL_ROOT."/document.php?modulepart=project_task&file=SPECIMEN.pdf");
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

// Activate a model
else if ($action == 'set')
{
	$ret = addDocumentModel($value, $type, $label, $scandir);
}
// Activate a model for task
else if ($action == 'settask')
{
	$ret = addDocumentModel($value,'project_task', $label, $scandir);
}

else if ($action == 'del')
{
	$ret = delDocumentModel($value, $type);
	if ($ret > 0)
	{
		if ($conf->global->PROJECT_ADDON_PDF == "$value") dolibarr_del_const($db, 'PROJECT_ADDON_PDF',$conf->entity);
	}
}
if ($action == 'deltask')
{
	$ret = delDocumentModel($value, 'project_task');
	if ($ret > 0)
	{
		if ($conf->global->PROJECT_TASK_ADDON_PDF == "$value") dolibarr_del_const($db, 'PROJECT_TASK_ADDON_PDF',$conf->entity);
	}
}

// Set default model
else if ($action == 'setdoc')
{
	dolibarr_set_const($db, "PROJECT_ADDON_PDF",$value,'chaine',0,'',$conf->entity);

	// On active le modele
	$ret = delDocumentModel($value, $type);
	if ($ret > 0)
	{
		$ret = addDocumentModel($value, $type, $label, $scandir);
	}
}

else if ($action == 'setdoctask')
{
	if (dolibarr_set_const($db, "PROJECT_TASK_ADDON_PDF",$value,'chaine',0,'',$conf->entity))
	{
		// La constante qui a ete lue en avant du nouveau set
		// on passe donc par une variable pour avoir un affichage coherent
		$conf->global->PROJECT_TASK_ADDON_PDF = $value;
	}

	// On active le modele
	$ret = delDocumentModel($value, 'project_task');
	if ($ret > 0)
	{
		$ret = addDocumentModel($value, 'project_task', $label, $scandir);
	}
}

else if ($action == 'setmod')
{
	// TODO Verifier si module numerotation choisi peut etre active
	// par appel methode canBeActivated

	dolibarr_set_const($db, "PROJECT_ADDON",$value,'chaine',0,'',$conf->entity);
}

else if ($action == 'setmodtask')
{
	// TODO Verifier si module numerotation choisi peut etre active
	// par appel methode canBeActivated

	dolibarr_set_const($db, "PROJECT_TASK_ADDON",$value,'chaine',0,'',$conf->entity);
}
elseif ($action == 'updateoptions')
{
	if (GETPOST('PROJECT_USE_SEARCH_TO_SELECT'))
	{
		$companysearch = GETPOST('activate_PROJECT_USE_SEARCH_TO_SELECT', 'alpha');
		if (dolibarr_set_const($db, "PROJECT_USE_SEARCH_TO_SELECT", $companysearch, 'chaine', 0, '', $conf->entity))
		{
			$conf->global->PROJECT_USE_SEARCH_TO_SELECT = $companysearch;
		}
	}
	if (GETPOST('PROJECT_ALLOW_TO_LINK_FROM_OTHER_COMPANY'))
	{
		$projectToSelect = GETPOST('projectToSelect','alpha');
		dolibarr_set_const($db, 'PROJECT_ALLOW_TO_LINK_FROM_OTHER_COMPANY', $projectToSelect, 'chaine', 0, '', $conf->entity);	//Allow to disable this configuration if empty value
	}
}


/*
 * View
 */

$dirmodels=array_merge(array('/'),(array) $conf->modules_parts['models']);

llxHeader("",$langs->trans("ProjectsSetup"));

$form=new Form($db);

$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1">'.$langs->trans("BackToModuleList").'</a>';
print load_fiche_titre($langs->trans("ProjectsSetup"),$linkback,'title_setup');

$head=project_admin_prepare_head();

dol_fiche_head($head, 'project', $langs->trans("Projects"), -1, 'project');



// Main options
$form=new Form($db);

print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="setmainoptions">';

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print "<td>".$langs->trans("Parameters")."</td>\n";
print '<td align="right" width="60">'.$langs->trans("Value").'</td>'."\n";
print '<td width="80">&nbsp;</td></tr>'."\n";

print '<tr class="oddeven">';
print '<td width="80%">'.$langs->trans("ManageOpportunitiesStatus").'</td>';
print '<td width="60" align="right">';
$arrval=array('0'=>$langs->trans("No"),
	'1'=>$langs->trans("Yes"),
);
print $form->selectyesno('PROJECT_USE_OPPORTUNITIES', $conf->global->PROJECT_USE_OPPORTUNITIES, 1);
print '</td><td align="right">';
print '<input type="submit" class="button" name="modifyPROJECT_USE_OPPORTUNITIES" value="'.$langs->trans("Modify").'">';
print "</td>";
print '</tr>';


print '<tr class="oddeven">';
print '<td width="80%">'.$langs->trans("ManageTasks").'</td>';
print '<td width="60" align="right">';
$arrval=array('0'=>$langs->trans("No"),
	'1'=>$langs->trans("Yes"),
);
print $form->selectyesno('PROJECT_USE_TASKS', empty($conf->global->PROJECT_HIDE_TASKS)?1:0, 1);
print '</td><td align="right">';
print '<input type="submit" class="button" name="modifyPROJECT_USE_TASKS" value="'.$langs->trans("Modify").'">';
print "</td>";
print '</tr>';

print '</table></form>';

print '<br>';



/*
 * Projects Numbering model
 */

print load_fiche_titre($langs->trans("ProjectsNumberingModules"), '', '');

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td width="100">'.$langs->trans("Name").'</td>';
print '<td>'.$langs->trans("Description").'</td>';
print '<td>'.$langs->trans("Example").'</td>';
print '<td align="center" width="60">'.$langs->trans("Activated").'</td>';
print '<td align="center" width="80">'.$langs->trans("ShortInfo").'</td>';
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
			while (($file = readdir($handle))!==false)
			{
				if (preg_match('/^(mod_.*)\.php$/i',$file,$reg))
				{
					$file = $reg[1];
					$classname = substr($file,4);

					require_once $dir.$file.'.php';

					$module = new $file;

					// Show modules according to features level
					if ($module->version == 'development'  && $conf->global->MAIN_FEATURES_LEVEL < 2) continue;
					if ($module->version == 'experimental' && $conf->global->MAIN_FEATURES_LEVEL < 1) continue;

					if ($module->isEnabled())
					{
						print '<tr class="oddeven"><td>'.$module->name."</td><td>\n";
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


if (empty($conf->global->PROJECT_HIDE_TASKS))
{
	// Task numbering module
	print load_fiche_titre($langs->trans("TasksNumberingModules"), '', '');

	print '<table class="noborder" width="100%">';
	print '<tr class="liste_titre">';
	print '<td width="100">'.$langs->trans("Name").'</td>';
	print '<td>'.$langs->trans("Description").'</td>';
	print '<td>'.$langs->trans("Example").'</td>';
	print '<td align="center" width="60">'.$langs->trans("Activated").'</td>';
	print '<td align="center" width="80">'.$langs->trans("ShortInfo").'</td>';
	print "</tr>\n";

	clearstatcache();

	foreach ($dirmodels as $reldir)
	{
		$dir = dol_buildpath($reldir."core/modules/project/task/");

		if (is_dir($dir))
		{
			$handle = opendir($dir);
			if (is_resource($handle))
			{
				while (($file = readdir($handle))!==false)
				{
					if (preg_match('/^(mod_.*)\.php$/i',$file,$reg))
					{
						$file = $reg[1];
						$classname = substr($file,4);

						require_once $dir.$file.'.php';

						$module = new $file;

						// Show modules according to features level
						if ($module->version == 'development'  && $conf->global->MAIN_FEATURES_LEVEL < 2) continue;
						if ($module->version == 'experimental' && $conf->global->MAIN_FEATURES_LEVEL < 1) continue;

						if ($module->isEnabled())
						{
							print '<tr class="oddeven"><td>'.$module->name."</td><td>\n";
							print $module->info();
							print '</td>';

							// Show example of numbering module
							print '<td class="nowrap">';
							$tmp=$module->getExample();
							if (preg_match('/^Error/',$tmp)) print '<div class="error">'.$langs->trans($tmp).'</div>';
							elseif ($tmp=='NotConfigured') print $langs->trans($tmp);
							else print $tmp;
							print '</td>'."\n";

							print '<td align="center">';
							if ($conf->global->PROJECT_TASK_ADDON == 'mod_'.$classname)
							{
								print img_picto($langs->trans("Activated"),'switch_on');
							}
							else
							{
								print '<a href="'.$_SERVER["PHP_SELF"].'?action=setmodtask&amp;value=mod_'.$classname.'" alt="'.$langs->trans("Default").'">'.img_picto($langs->trans("Disabled"),'switch_off').'</a>';
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
}


/*
 * Document templates generators
 */

print load_fiche_titre($langs->trans("ProjectsModelModule"), '', '');

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
print '<td align="center" width="80">'.$langs->trans("ShortInfo").'</td>';
print '<td align="center" width="80">'.$langs->trans("Preview").'</td>';
print "</tr>\n";

clearstatcache();

foreach ($dirmodels as $reldir)
{
	foreach (array('','/doc') as $valdir)
	{
		$dir = dol_buildpath($reldir."core/modules/project/".$valdir);

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
								print "</td>\n";

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
								if ($conf->global->PROJECT_ADDON_PDF == "$name")
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
								if ($module->type == 'pdf')
								{
									$htmltooltip.='<br>'.$langs->trans("Width").'/'.$langs->trans("Height").': '.$module->page_largeur.'/'.$module->page_hauteur;
								}
								$htmltooltip.='<br><br><u>'.$langs->trans("FeaturesSupported").':</u>';
								$htmltooltip.='<br>'.$langs->trans("Logo").': '.yn($module->option_logo,1,1);

								print '<td align="center">';
								print $form->textwithpicto('',$htmltooltip,1,0);
								print '</td>';

								// Preview
								print '<td align="center">';
								if ($module->type == 'pdf')
								{
									print '<a href="'.$_SERVER["PHP_SELF"].'?action=specimen&module='.$name.'">'.img_object($langs->trans("Preview"),'bill').'</a>';
								}
								else
								{
									print img_object($langs->trans("PreviewNotAvailable"),'generic');
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

print '</table><br/>';



if (empty($conf->global->PROJECT_HIDE_TASKS))
{
	/*
	 * Modeles documents for Task
	 */

	print load_fiche_titre($langs->trans("TaskModelModule"), '', '');

	// Defini tableau def de modele
	$type='project_task';
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
	print '<td align="center" width="80">'.$langs->trans("ShortInfo").'</td>';
	print '<td align="center" width="80">'.$langs->trans("Preview").'</td>';
	print "</tr>\n";

	clearstatcache();

	foreach ($dirmodels as $reldir)
	{
		foreach (array('','/doc') as $valdir)
		{
			$dir = dol_buildpath($reldir."core/modules/project/task/".$valdir);

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
									print "</td>\n";

									// Active
									if (in_array($name, $def))
									{
										print "<td align=\"center\">\n";
										print '<a href="'.$_SERVER["PHP_SELF"].'?action=deltask&amp;value='.$name.'&amp;scan_dir='.$module->scandir.'&amp;label='.urlencode($module->name).'">';
										print img_picto($langs->trans("Enabled"),'switch_on');
										print '</a>';
										print "</td>";
									}
									else
									{
										print "<td align=\"center\">\n";
										print '<a href="'.$_SERVER["PHP_SELF"].'?action=settask&amp;value='.$name.'&amp;scan_dir='.$module->scandir.'&amp;label='.urlencode($module->name).'">'.img_picto($langs->trans("Disabled"),'switch_off').'</a>';
										print "</td>";
									}

									// Defaut
									print "<td align=\"center\">";
									if ($conf->global->PROJECT_TASK_ADDON_PDF == "$name")
									{
										print img_picto($langs->trans("Default"),'on');
									}
									else
									{
										print '<a href="'.$_SERVER["PHP_SELF"].'?action=setdoctask&amp;value='.$name.'&amp;scan_dir='.$module->scandir.'&amp;label='.urlencode($module->name).'" alt="'.$langs->trans("Default").'">'.img_picto($langs->trans("Disabled"),'off').'</a>';
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

									print '<td align="center">';
									print $form->textwithpicto('',$htmltooltip,1,0);
									print '</td>';

									// Preview
									print '<td align="center">';
									if ($module->type == 'pdf')
									{
										print '<a href="'.$_SERVER["PHP_SELF"].'?action=specimentask&module='.$name.'">'.img_object($langs->trans("Preview"),'bill').'</a>';
									}
									else
									{
										print img_object($langs->trans("PreviewNotAvailable"),'generic');
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

	print '</table><br/>';
}


print load_fiche_titre($langs->trans("Other"), '', '');

// Other options
$form=new Form($db);

print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="updateoptions">';

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print "<td>".$langs->trans("Parameters")."</td>\n";
print '<td align="right" width="60">'.$langs->trans("Value").'</td>'."\n";
print '<td width="80">&nbsp;</td></tr>'."\n";

print '<tr class="oddeven">';
print '<td width="80%">'.$langs->trans("UseSearchToSelectProject").'</td>';
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
	print $form->selectarray("activate_PROJECT_USE_SEARCH_TO_SELECT",$arrval,$conf->global->PROJECT_USE_SEARCH_TO_SELECT);
	print '</td><td align="right">';
	print '<input type="submit" class="button" name="PROJECT_USE_SEARCH_TO_SELECT" value="'.$langs->trans("Modify").'">';
	print "</td>";
}
print '</tr>';

print '<tr class="oddeven">';
print '<td>'.$langs->trans("AllowToSelectProjectFromOtherCompany").'</td>';

print '<td align="right" width="60" colspan="2">';
print '<input type="text" id="projectToSelect" name="projectToSelect" value="'.$conf->global->PROJECT_ALLOW_TO_LINK_FROM_OTHER_COMPANY.'"/>&nbsp;';
print $form->textwithpicto('', $langs->trans('AllowToLinkFromOtherCompany'));
print '<input type="submit" class="button" name="PROJECT_ALLOW_TO_LINK_FROM_OTHER_COMPANY" value="'.$langs->trans("Modify").'">';
print '</td>';

print '</table>';


print '</form>';

// End of page
llxFooter();
$db->close();
