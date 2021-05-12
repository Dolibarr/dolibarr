<?php
/* Copyright (C) 2003-2004 Rodolphe Quiedeville         <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2011 Laurent Destailleur          <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Sebastien Di Cintio          <sdicintio@ressource-toi.org>
 * Copyright (C) 2004      Benoit Mortier               <benoit.mortier@opensides.be>
<<<<<<< HEAD
 * Copyright (C) 2005-2014 Regis Houssin                <regis.houssin@capnetworks.com>
 * Copyright (C) 2008      Raphael Bertrand (Resultic)  <raphael.bertrand@resultic.fr>
 * Copyright (C) 2011-2013 Juanjo Menent			    <jmenent@2byte.es>
 * Copyright (C) 2011-2017 Philippe Grand			    <philippe.grand@atoo-net.com>
=======
 * Copyright (C) 2005-2014 Regis Houssin                <regis.houssin@inodbox.com>
 * Copyright (C) 2008      Raphael Bertrand (Resultic)  <raphael.bertrand@resultic.fr>
 * Copyright (C) 2011-2013 Juanjo Menent			    <jmenent@2byte.es>
 * Copyright (C) 2011-2018 Philippe Grand			    <philippe.grand@atoo-net.com>
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
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
 *	\file       htdocs/admin/fichinter.php
 *	\ingroup    fichinter
 *	\brief      Setup page of module Interventions
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/fichinter.lib.php';
require_once DOL_DOCUMENT_ROOT.'/fichinter/class/fichinter.class.php';

// Load translation files required by the page
$langs->loadLangs(array('admin', 'errors', 'interventions', 'other'));

if (! $user->admin) accessforbidden();

<<<<<<< HEAD
$action = GETPOST('action','alpha');
$value = GETPOST('value','alpha');
$label = GETPOST('label','alpha');
$scandir = GETPOST('scan_dir','alpha');
=======
$action = GETPOST('action', 'alpha');
$value = GETPOST('value', 'alpha');
$label = GETPOST('label', 'alpha');
$scandir = GETPOST('scan_dir', 'alpha');
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
$type='ficheinter';


/*
 * Actions
 */

include DOL_DOCUMENT_ROOT.'/core/actions_setmoduleoptions.inc.php';

if ($action == 'updateMask')
{
<<<<<<< HEAD
	$maskconst=GETPOST('maskconst','alpha');
	$maskvalue=GETPOST('maskvalue','alpha');
	if ($maskconst) $res = dolibarr_set_const($db,$maskconst,$maskvalue,'chaine',0,'',$conf->entity);
=======
	$maskconst=GETPOST('maskconst', 'alpha');
	$maskvalue=GETPOST('maskvalue', 'alpha');
	if ($maskconst) $res = dolibarr_set_const($db, $maskconst, $maskvalue, 'chaine', 0, '', $conf->entity);
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9

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

<<<<<<< HEAD
else if ($action == 'specimen') // For fiche inter
{
	$modele= GETPOST('module','alpha');
=======
elseif ($action == 'specimen') // For fiche inter
{
	$modele= GETPOST('module', 'alpha');
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9

	$inter = new Fichinter($db);
	$inter->initAsSpecimen();

	// Search template files
	$file=''; $classname=''; $filefound=0;
<<<<<<< HEAD
	$dirmodels=array_merge(array('/'),(array) $conf->modules_parts['models']);
	foreach($dirmodels as $reldir)
	{
	    $file=dol_buildpath($reldir."core/modules/fichinter/doc/pdf_".$modele.".modules.php",0);
=======
	$dirmodels=array_merge(array('/'), (array) $conf->modules_parts['models']);
	foreach($dirmodels as $reldir)
	{
	    $file=dol_buildpath($reldir."core/modules/fichinter/doc/pdf_".$modele.".modules.php", 0);
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
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

<<<<<<< HEAD
		if ($module->write_file($inter,$langs) > 0)
=======
		if ($module->write_file($inter, $langs) > 0)
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
		{
			header("Location: ".DOL_URL_ROOT."/document.php?modulepart=ficheinter&file=SPECIMEN.pdf");
			return;
		}
		else
		{
			setEventMessages($module->error, $module->errors, 'errors');
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
<<<<<<< HEAD
else if ($action == 'set')
=======
elseif ($action == 'set')
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
{
	$ret = addDocumentModel($value, $type, $label, $scandir);
}

<<<<<<< HEAD
else if ($action == 'del')
=======
elseif ($action == 'del')
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
{
	$ret = delDocumentModel($value, $type);
	if ($ret > 0)
	{
<<<<<<< HEAD
        if ($conf->global->FICHEINTER_ADDON_PDF == "$value") dolibarr_del_const($db, 'FICHEINTER_ADDON_PDF',$conf->entity);
=======
        if ($conf->global->FICHEINTER_ADDON_PDF == "$value") dolibarr_del_const($db, 'FICHEINTER_ADDON_PDF', $conf->entity);
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	}
}

// Set default model
<<<<<<< HEAD
else if ($action == 'setdoc')
{
	if (dolibarr_set_const($db, "FICHEINTER_ADDON_PDF",$value,'chaine',0,'',$conf->entity))
=======
elseif ($action == 'setdoc')
{
	if (dolibarr_set_const($db, "FICHEINTER_ADDON_PDF", $value, 'chaine', 0, '', $conf->entity))
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	{
		// La constante qui a ete lue en avant du nouveau set
		// on passe donc par une variable pour avoir un affichage coherent
		$conf->global->FICHEINTER_ADDON_PDF = $value;
	}

	// On active le modele
	$ret = delDocumentModel($value, $type);
	if ($ret > 0)
	{
		$ret = addDocumentModel($value, $type, $label, $scandir);
	}
}

<<<<<<< HEAD
else if ($action == 'setmod')
=======
elseif ($action == 'setmod')
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
{
	// TODO Verifier si module numerotation choisi peut etre active
	// par appel methode canBeActivated

<<<<<<< HEAD
	dolibarr_set_const($db, "FICHEINTER_ADDON",$value,'chaine',0,'',$conf->entity);
}

else if ($action == 'set_FICHINTER_FREE_TEXT')
{
	$freetext= GETPOST('FICHINTER_FREE_TEXT','none');	// No alpha here, we want exact string
	$res = dolibarr_set_const($db, "FICHINTER_FREE_TEXT",$freetext,'chaine',0,'',$conf->entity);
=======
	dolibarr_set_const($db, "FICHEINTER_ADDON", $value, 'chaine', 0, '', $conf->entity);
}

elseif ($action == 'set_FICHINTER_FREE_TEXT')
{
	$freetext= GETPOST('FICHINTER_FREE_TEXT', 'none');	// No alpha here, we want exact string
	$res = dolibarr_set_const($db, "FICHINTER_FREE_TEXT", $freetext, 'chaine', 0, '', $conf->entity);
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9

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

<<<<<<< HEAD
else if ($action == 'set_FICHINTER_DRAFT_WATERMARK')
{
	$draft= GETPOST('FICHINTER_DRAFT_WATERMARK','alpha');
	$res = dolibarr_set_const($db, "FICHINTER_DRAFT_WATERMARK",trim($draft),'chaine',0,'',$conf->entity);
=======
elseif ($action == 'set_FICHINTER_DRAFT_WATERMARK')
{
	$draft= GETPOST('FICHINTER_DRAFT_WATERMARK', 'alpha');
	$res = dolibarr_set_const($db, "FICHINTER_DRAFT_WATERMARK", trim($draft), 'chaine', 0, '', $conf->entity);
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9

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

elseif ($action == 'set_FICHINTER_PRINT_PRODUCTS')
{
<<<<<<< HEAD
	$val = GETPOST('FICHINTER_PRINT_PRODUCTS','alpha');
	$res = dolibarr_set_const($db, "FICHINTER_PRINT_PRODUCTS",($val == 'on' ? 1 : 0),'bool',0,'',$conf->entity);
=======
	$val = GETPOST('FICHINTER_PRINT_PRODUCTS', 'alpha');
	$res = dolibarr_set_const($db, "FICHINTER_PRINT_PRODUCTS", ($val == 'on' ? 1 : 0), 'bool', 0, '', $conf->entity);
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9

	if (! $res > 0) $error++;

 	if (! $error)
    {
        setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
    }
    else
    {
        setEventMessages($langs->trans("Error"), null, 'errors');
    }
} elseif ($action == 'set_FICHINTER_USE_SERVICE_DURATION') {
	$val = GETPOST('FICHINTER_USE_SERVICE_DURATION', 'alpha');
<<<<<<< HEAD
	$res = dolibarr_set_const($db, "FICHINTER_USE_SERVICE_DURATION", ($val == 'on' ? 1 : 0), 'bool', 0, '',
=======
$res = dolibarr_set_const($db, "FICHINTER_USE_SERVICE_DURATION", ($val == 'on' ? 1 : 0), 'bool', 0, '',
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
		$conf->entity);

	if (!$res > 0) {
		$error++;
	}

	if (!$error) {
		setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
	} else {
		setEventMessages($langs->trans("Error"), null, 'errors');
	}
} elseif ($action == 'set_FICHINTER_WITHOUT_DURATION') {
        $val = GETPOST('FICHINTER_WITHOUT_DURATION', 'alpha');
        $res = dolibarr_set_const($db, "FICHINTER_WITHOUT_DURATION", ($val == 'on' ? 1 : 0), 'bool', 0, '',
                $conf->entity);

        if (!$res > 0) {
                $error++;
        }

        if (!$error) {
                setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
        } else {
                setEventMessages($langs->trans("Error"), null, 'errors');
        }
} elseif ($action == 'set_FICHINTER_DATE_WITHOUT_HOUR') {
        $val = GETPOST('FICHINTER_DATE_WITHOUT_HOUR', 'alpha');
        $res = dolibarr_set_const($db, "FICHINTER_DATE_WITHOUT_HOUR", ($val == 'on' ? 1 : 0), 'bool', 0, '',
                $conf->entity);

        if (!$res > 0) {
                $error++;
        }

        if (!$error) {
                setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
        } else {
                setEventMessages($langs->trans("Error"), null, 'errors');
        }
}



/*
 * View
 */

<<<<<<< HEAD
$dirmodels=array_merge(array('/'),(array) $conf->modules_parts['models']);
=======
$dirmodels=array_merge(array('/'), (array) $conf->modules_parts['models']);
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9

llxHeader();

$form=new Form($db);

$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1">'.$langs->trans("BackToModuleList").'</a>';
<<<<<<< HEAD
print load_fiche_titre($langs->trans("InterventionsSetup"),$linkback,'title_setup');
=======
print load_fiche_titre($langs->trans("InterventionsSetup"), $linkback, 'title_setup');
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9


$head=fichinter_admin_prepare_head();

dol_fiche_head($head, 'ficheinter', $langs->trans("Interventions"), -1, 'intervention');

// Interventions numbering model

<<<<<<< HEAD
print load_fiche_titre($langs->trans("FicheinterNumberingModules"),'','');
=======
print load_fiche_titre($langs->trans("FicheinterNumberingModules"), '', '');
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td width="100">'.$langs->trans("Name").'</td>';
print '<td>'.$langs->trans("Description").'</td>';
print '<td>'.$langs->trans("Example").'</td>';
print '<td align="center" width="60">'.$langs->trans("Status").'</td>';
print '<td align="center" width="80">'.$langs->trans("ShortInfo").'</td>';
print "</tr>\n";

clearstatcache();

foreach ($dirmodels as $reldir)
{
	$dir = dol_buildpath($reldir."core/modules/fichinter/");

	if (is_dir($dir))
	{
		$handle = opendir($dir);
		if (is_resource($handle))
		{

			while (($file = readdir($handle))!==false)
			{
<<<<<<< HEAD
				if (preg_match('/^(mod_.*)\.php$/i',$file,$reg))
				{
					$file = $reg[1];
					$classname = substr($file,4);
=======
				if (preg_match('/^(mod_.*)\.php$/i', $file, $reg))
				{
					$file = $reg[1];
					$classname = substr($file, 4);
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9

					require_once $dir.$file.'.php';

					$module = new $file;

					if ($module->isEnabled())
					{
						// Show modules according to features level
						if ($module->version == 'development'  && $conf->global->MAIN_FEATURES_LEVEL < 2) continue;
						if ($module->version == 'experimental' && $conf->global->MAIN_FEATURES_LEVEL < 1) continue;


						print '<tr class="oddeven"><td>'.$module->nom."</td><td>\n";
						print $module->info();
						print '</td>';

                        // Show example of numbering model
                        print '<td class="nowrap">';
                        $tmp=$module->getExample();
<<<<<<< HEAD
                        if (preg_match('/^Error/',$tmp)) print '<div class="error">'.$langs->trans($tmp).'</div>';
=======
                        if (preg_match('/^Error/', $tmp)) print '<div class="error">'.$langs->trans($tmp).'</div>';
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
                        elseif ($tmp=='NotConfigured') print $langs->trans($tmp);
                        else print $tmp;
                        print '</td>'."\n";

						print '<td align="center">';
						if ($conf->global->FICHEINTER_ADDON == $classname)
						{
<<<<<<< HEAD
							print img_picto($langs->trans("Activated"),'switch_on');
						}
						else
						{
							print '<a href="'.$_SERVER["PHP_SELF"].'?action=setmod&amp;value='.$classname.'" alt="'.$langs->trans("Default").'">'.img_picto($langs->trans("Disabled"),'switch_off').'</a>';
=======
							print img_picto($langs->trans("Activated"), 'switch_on');
						}
						else
						{
							print '<a class="reposition" href="'.$_SERVER["PHP_SELF"].'?action=setmod&amp;value='.$classname.'" alt="'.$langs->trans("Default").'">'.img_picto($langs->trans("Disabled"), 'switch_off').'</a>';
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
						}
						print '</td>';

						$ficheinter=new Fichinter($db);
						$ficheinter->initAsSpecimen();

						// Info
						$htmltooltip='';
						$htmltooltip.=''.$langs->trans("Version").': <b>'.$module->getVersion().'</b><br>';
<<<<<<< HEAD
						$nextval=$module->getNextValue($mysoc,$ficheinter);
                        if ("$nextval" != $langs->trans("NotAvailable")) {   // Keep " on nextval
                            $htmltooltip.=''.$langs->trans("NextValue").': ';
                            if ($nextval) {
                                if (preg_match('/^Error/',$nextval) || $nextval=='NotConfigured')
=======
						$nextval=$module->getNextValue($mysoc, $ficheinter);
                        if ("$nextval" != $langs->trans("NotAvailable")) {   // Keep " on nextval
                            $htmltooltip.=''.$langs->trans("NextValue").': ';
                            if ($nextval) {
                                if (preg_match('/^Error/', $nextval) || $nextval=='NotConfigured')
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
                                    $nextval = $langs->trans($nextval);
                                $htmltooltip.=$nextval.'<br>';
                            } else {
                                $htmltooltip.=$langs->trans($module->error).'<br>';
                            }
                        }
						print '<td align="center">';
<<<<<<< HEAD
						print $form->textwithpicto('',$htmltooltip,1,0);
=======
						print $form->textwithpicto('', $htmltooltip, 1, 0);
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
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
 *  Documents models for Interventions
 */

<<<<<<< HEAD
print load_fiche_titre($langs->trans("TemplatePDFInterventions"),'','');
=======
print load_fiche_titre($langs->trans("TemplatePDFInterventions"), '', '');
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9

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
print '<td align="center" width="80">'.$langs->trans("ShortInfo").'</td>';
print '<td align="center" width="80">'.$langs->trans("Preview").'</td>';
print "</tr>\n";

clearstatcache();

foreach ($dirmodels as $reldir)
{
	$dir = dol_buildpath($reldir."core/modules/fichinter/doc/");

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
<<<<<<< HEAD
				if (preg_match('/\.modules\.php$/i',$file) && preg_match('/^(pdf_|doc_)/',$file))
=======
				if (preg_match('/\.modules\.php$/i', $file) && preg_match('/^(pdf_|doc_)/', $file))
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
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
<<<<<<< HEAD
		    				if (method_exists($module,'info')) print $module->info($langs);
=======
		    				if (method_exists($module, 'info')) print $module->info($langs);
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
		    				else print $module->description;
		    				print '</td>';

		    				// Active
		    				if (in_array($name, $def))
		    				{
		    					print "<td align=\"center\">\n";
		    					print '<a href="'.$_SERVER["PHP_SELF"].'?action=del&amp;value='.$name.'&amp;scan_dir='.$module->scandir.'&amp;label='.urlencode($module->name).'">';
<<<<<<< HEAD
		    					print img_picto($langs->trans("Enabled"),'switch_on');
=======
		    					print img_picto($langs->trans("Enabled"), 'switch_on');
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
		    					print '</a>';
		    					print "</td>";
		    				}
		    				else
		    				{
		    					print "<td align=\"center\">\n";
<<<<<<< HEAD
		    					print '<a href="'.$_SERVER["PHP_SELF"].'?action=set&amp;value='.$name.'&amp;scan_dir='.$module->scandir.'&amp;label='.urlencode($module->name).'">'.img_picto($langs->trans("Disabled"),'switch_off').'</a>';
=======
		    					print '<a href="'.$_SERVER["PHP_SELF"].'?action=set&amp;value='.$name.'&amp;scan_dir='.$module->scandir.'&amp;label='.urlencode($module->name).'">'.img_picto($langs->trans("Disabled"), 'switch_off').'</a>';
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
		    					print "</td>";
		    				}

		    				// Default
		    				print "<td align=\"center\">";
		    				if ($conf->global->FICHEINTER_ADDON_PDF == "$name")
		    				{
<<<<<<< HEAD
		    					print img_picto($langs->trans("Default"),'on');
		    				}
		    				else
		    				{
		    					print '<a href="'.$_SERVER["PHP_SELF"].'?action=setdoc&amp;value='.$name.'&amp;scan_dir='.$module->scandir.'&amp;label='.urlencode($module->name).'" alt="'.$langs->trans("Default").'">'.img_picto($langs->trans("Disabled"),'off').'</a>';
=======
		    					print img_picto($langs->trans("Default"), 'on');
		    				}
		    				else
		    				{
		    					print '<a href="'.$_SERVER["PHP_SELF"].'?action=setdoc&amp;value='.$name.'&amp;scan_dir='.$module->scandir.'&amp;label='.urlencode($module->name).'" alt="'.$langs->trans("Default").'">'.img_picto($langs->trans("Disabled"), 'off').'</a>';
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
		    				}
		    				print '</td>';

		    				// Info
		    				$htmltooltip =    ''.$langs->trans("Name").': '.$module->name;
		    				$htmltooltip.='<br>'.$langs->trans("Type").': '.($module->type?$module->type:$langs->trans("Unknown"));
		    				$htmltooltip.='<br>'.$langs->trans("Width").'/'.$langs->trans("Height").': '.$module->page_largeur.'/'.$module->page_hauteur;
		    				$htmltooltip.='<br><br><u>'.$langs->trans("FeaturesSupported").':</u>';
<<<<<<< HEAD
		    				$htmltooltip.='<br>'.$langs->trans("Logo").': '.yn($module->option_logo,1,1);
		    				$htmltooltip.='<br>'.$langs->trans("PaymentMode").': '.yn($module->option_modereg,1,1);
		    				$htmltooltip.='<br>'.$langs->trans("PaymentConditions").': '.yn($module->option_condreg,1,1);
		    				$htmltooltip.='<br>'.$langs->trans("MultiLanguage").': '.yn($module->option_multilang,1,1);
		    				$htmltooltip.='<br>'.$langs->trans("WatermarkOnDraftOrders").': '.yn($module->option_draft_watermark,1,1);
		    				print '<td align="center">';
		    				print $form->textwithpicto('',$htmltooltip,-1,0);
=======
		    				$htmltooltip.='<br>'.$langs->trans("Logo").': '.yn($module->option_logo, 1, 1);
		    				$htmltooltip.='<br>'.$langs->trans("PaymentMode").': '.yn($module->option_modereg, 1, 1);
		    				$htmltooltip.='<br>'.$langs->trans("PaymentConditions").': '.yn($module->option_condreg, 1, 1);
		    				$htmltooltip.='<br>'.$langs->trans("MultiLanguage").': '.yn($module->option_multilang, 1, 1);
		    				$htmltooltip.='<br>'.$langs->trans("WatermarkOnDraftOrders").': '.yn($module->option_draft_watermark, 1, 1);
		    				print '<td align="center">';
		    				print $form->textwithpicto('', $htmltooltip, -1, 0);
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
		    				print '</td>';

		    				// Preview
		    				print '<td align="center">';
		    				if ($module->type == 'pdf')
		    				{
<<<<<<< HEAD
		    					print '<a href="'.$_SERVER["PHP_SELF"].'?action=specimen&module='.$name.'">'.img_object($langs->trans("Preview"),'intervention').'</a>';
		    				}
		    				else
		    				{
		    					print img_object($langs->trans("PreviewNotAvailable"),'generic');
=======
		    					print '<a href="'.$_SERVER["PHP_SELF"].'?action=specimen&module='.$name.'">'.img_object($langs->trans("Preview"), 'intervention').'</a>';
		    				}
		    				else
		    				{
		    					print img_object($langs->trans("PreviewNotAvailable"), 'generic');
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
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
print "<br>";

/*
 * Other options
 */

<<<<<<< HEAD
print load_fiche_titre($langs->trans("OtherOptions"),'','');
=======
print load_fiche_titre($langs->trans("OtherOptions"), '', '');
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Parameter").'</td>';
print '<td align="center" width="60">'.$langs->trans("Value").'</td>';
print "<td>&nbsp;</td>\n";
print "</tr>\n";

$substitutionarray=pdf_getSubstitutionArray($langs, null, null, 2);
$substitutionarray['__(AnyTranslationKey)__']=$langs->trans("Translation");
$htmltext = '<i>'.$langs->trans("AvailableVariables").':<br>';
foreach($substitutionarray as $key => $val)	$htmltext.=$key.'<br>';
$htmltext.='</i>';

print '<form action="'.$_SERVER["PHP_SELF"].'" method="post">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="set_FICHINTER_FREE_TEXT">';
print '<tr class="oddeven"><td colspan="2">';
print $form->textwithpicto($langs->trans("FreeLegalTextOnInterventions"), $langs->trans("AddCRIfTooLong").'<br><br>'.$htmltext, 1, 'help', '', 0, 2, 'freetexttooltip').'<br>';
$variablename='FICHINTER_FREE_TEXT';
if (empty($conf->global->PDF_ALLOW_HTML_FOR_FREE_TEXT))
{
    print '<textarea name="'.$variablename.'" class="flat" cols="120">'.$conf->global->$variablename.'</textarea>';
}
else
{
    include_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
<<<<<<< HEAD
    $doleditor=new DolEditor($variablename, $conf->global->$variablename,'',80,'dolibarr_notes');
    print $doleditor->Create();
}
print '</td><td align="right">';
=======
    $doleditor=new DolEditor($variablename, $conf->global->$variablename, '', 80, 'dolibarr_notes');
    print $doleditor->Create();
}
print '</td><td class="right">';
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
print "</td></tr>\n";
print '</form>';

//Use draft Watermark
<<<<<<< HEAD

=======
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
print "<form method=\"post\" action=\"".$_SERVER["PHP_SELF"]."\">";
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print "<input type=\"hidden\" name=\"action\" value=\"set_FICHINTER_DRAFT_WATERMARK\">";
print '<tr class="oddeven"><td>';
print $form->textwithpicto($langs->trans("WatermarkOnDraftInterventionCards"), $htmltext, 1, 'help', '', 0, 2, 'watermarktooltip').'<br>';
print '</td><td>';
print '<input size="50" class="flat" type="text" name="FICHINTER_DRAFT_WATERMARK" value="'.$conf->global->FICHINTER_DRAFT_WATERMARK.'">';
<<<<<<< HEAD
print '</td><td align="right">';
=======
print '</td><td class="right">';
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
print "</td></tr>\n";
print '</form>';
// print products on fichinter
print '<form action="'.$_SERVER["PHP_SELF"].'" method="post">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="set_FICHINTER_PRINT_PRODUCTS">';
print '<tr class="oddeven"><td>';
print $langs->trans("PrintProductsOnFichinter").' ('.$langs->trans("PrintProductsOnFichinterDetails").')</td>';
print '<td align="center"><input type="checkbox" name="FICHINTER_PRINT_PRODUCTS" ';
if ($conf->global->FICHINTER_PRINT_PRODUCTS)
	print 'checked ';
print '/>';
<<<<<<< HEAD
print '</td><td align="right">';
=======
print '</td><td class="right">';
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
print "</td></tr>\n";
print '</form>';
// Use services duration
print '<form action="' . $_SERVER["PHP_SELF"] . '" method="post">';
print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
print '<input type="hidden" name="action" value="set_FICHINTER_USE_SERVICE_DURATION">';
print '<tr class="oddeven">';
print '<td>';
print $langs->trans("UseServicesDurationOnFichinter");
print '</td>';
print '<td align="center">';
print '<input type="checkbox" name="FICHINTER_USE_SERVICE_DURATION"' . ($conf->global->FICHINTER_USE_SERVICE_DURATION?' checked':'') . '>';
print '</td>';
<<<<<<< HEAD
print '<td align="right">';
=======
print '<td class="right">';
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
print '<input type="submit" class="button" value="' . $langs->trans("Modify") . '">';
print '</td>';
print '</tr>';
print '</form>';
// Use duration
print '<form action="' . $_SERVER["PHP_SELF"] . '" method="post">';
print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
print '<input type="hidden" name="action" value="set_FICHINTER_WITHOUT_DURATION">';
print '<tr class="oddeven">';
print '<td>';
print $langs->trans("UseDurationOnFichinter");
print '</td>';
print '<td align="center">';
print '<input type="checkbox" name="FICHINTER_WITHOUT_DURATION"' . ($conf->global->FICHINTER_WITHOUT_DURATION?' checked':'') . '>';
print '</td>';
<<<<<<< HEAD
print '<td align="right">';
=======
print '<td class="right">';
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
print '<input type="submit" class="button" value="' . $langs->trans("Modify") . '">';
print '</td>';
print '</tr>';
print '</form>';
// use date without hour
print '<form action="' . $_SERVER["PHP_SELF"] . '" method="post">';
print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
print '<input type="hidden" name="action" value="set_FICHINTER_DATE_WITHOUT_HOUR">';
print '<tr class="oddeven">';
print '<td>';
print $langs->trans("UseDateWithoutHourOnFichinter");
print '</td>';
print '<td align="center">';
print '<input type="checkbox" name="FICHINTER_DATE_WITHOUT_HOUR"' . ($conf->global->FICHINTER_DATE_WITHOUT_HOUR?' checked':'') . '>';
print '</td>';
<<<<<<< HEAD
print '<td align="right">';
=======
print '<td class="right">';
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
print '<input type="submit" class="button" value="' . $langs->trans("Modify") . '">';
print '</td>';
print '</tr>';
print '</form>';

<<<<<<< HEAD



=======
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
print '</table>';

print '<br>';

<<<<<<< HEAD
=======
// End of page
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
llxFooter();
$db->close();
