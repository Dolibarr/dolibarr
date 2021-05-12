<?php
/* Copyright (C) 2003-2004 Rodolphe Quiedeville        <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2011 Laurent Destailleur         <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Sebastien Di Cintio         <sdicintio@ressource-toi.org>
 * Copyright (C) 2004      Benoit Mortier              <benoit.mortier@opensides.be>
 * Copyright (C) 2004      Eric Seigne                 <eric.seigne@ryxeo.com>
<<<<<<< HEAD
 * Copyright (C) 2005-2012 Regis Houssin               <regis.houssin@capnetworks.com>
=======
 * Copyright (C) 2005-2012 Regis Houssin               <regis.houssin@inodbox.com>
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
 * Copyright (C) 2008      Raphael Bertrand (Resultic) <raphael.bertrand@resultic.fr>
 * Copyright (C) 2011-2013 Juanjo Menent               <jmenent@2byte.es>
 * Copyright (C) 2015      Jean-François Ferry		   <jfefe@aternatik.fr>
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

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php';
require_once DOL_DOCUMENT_ROOT.'/supplier_proposal/class/supplier_proposal.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/supplier_proposal.lib.php';

// Load translation files required by the page
$langs->loadLangs(array("admin", "errors", "other", "supplier_proposal"));

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
$type='supplier_proposal';

$error=0;


/*
 * Actions
 */

include DOL_DOCUMENT_ROOT.'/core/actions_setmoduleoptions.inc.php';

if ($action == 'updateMask')
{
<<<<<<< HEAD
	$maskconstsupplier_proposal=GETPOST('maskconstsupplier_proposal','alpha');
	$masksupplier_proposal=GETPOST('masksupplier_proposal','alpha');
	if ($maskconstsupplier_proposal) $res = dolibarr_set_const($db,$maskconstsupplier_proposal,$masksupplier_proposal,'chaine',0,'',$conf->entity);
=======
	$maskconstsupplier_proposal=GETPOST('maskconstsupplier_proposal', 'alpha');
	$masksupplier_proposal=GETPOST('masksupplier_proposal', 'alpha');
	if ($maskconstsupplier_proposal) $res = dolibarr_set_const($db, $maskconstsupplier_proposal, $masksupplier_proposal, 'chaine', 0, '', $conf->entity);
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

if ($action == 'specimen')
{
<<<<<<< HEAD
	$modele=GETPOST('module','alpha');
=======
	$modele=GETPOST('module', 'alpha');
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9

	$supplier_proposal = new SupplierProposal($db);
	$supplier_proposal->initAsSpecimen();

	// Search template files
	$file=''; $classname=''; $filefound=0;
<<<<<<< HEAD
	$dirmodels=array_merge(array('/'),(array) $conf->modules_parts['models']);
=======
	$dirmodels=array_merge(array('/'), (array) $conf->modules_parts['models']);
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	foreach($dirmodels as $reldir)
	{
	    $file=dol_buildpath($reldir."core/modules/supplier_proposal/doc/pdf_".$modele.".modules.php");
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
		if ($module->write_file($supplier_proposal,$langs) > 0)
=======
		if ($module->write_file($supplier_proposal, $langs) > 0)
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
		{
			header("Location: ".DOL_URL_ROOT."/document.php?modulepart=supplier_proposal&file=SPECIMEN.pdf");
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

if ($action == 'set_SUPPLIER_PROPOSAL_DRAFT_WATERMARK')
{
<<<<<<< HEAD
	$draft = GETPOST('SUPPLIER_PROPOSAL_DRAFT_WATERMARK','alpha');

	$res = dolibarr_set_const($db, "SUPPLIER_PROPOSAL_DRAFT_WATERMARK",trim($draft),'chaine',0,'',$conf->entity);
=======
	$draft = GETPOST('SUPPLIER_PROPOSAL_DRAFT_WATERMARK', 'alpha');

	$res = dolibarr_set_const($db, "SUPPLIER_PROPOSAL_DRAFT_WATERMARK", trim($draft), 'chaine', 0, '', $conf->entity);
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

if ($action == 'set_SUPPLIER_PROPOSAL_FREE_TEXT')
{
<<<<<<< HEAD
	$freetext = GETPOST('SUPPLIER_PROPOSAL_FREE_TEXT','none');	// No alpha here, we want exact string

	$res = dolibarr_set_const($db, "SUPPLIER_PROPOSAL_FREE_TEXT",$freetext,'chaine',0,'',$conf->entity);
=======
	$freetext = GETPOST('SUPPLIER_PROPOSAL_FREE_TEXT', 'none');	// No alpha here, we want exact string

	$res = dolibarr_set_const($db, "SUPPLIER_PROPOSAL_FREE_TEXT", $freetext, 'chaine', 0, '', $conf->entity);
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

if ($action == 'set_BANK_ASK_PAYMENT_BANK_DURING_SUPPLIER_PROPOSAL')
{
<<<<<<< HEAD
    $res = dolibarr_set_const($db, "BANK_ASK_PAYMENT_BANK_DURING_SUPPLIER_PROPOSAL",$value,'chaine',0,'',$conf->entity);
=======
    $res = dolibarr_set_const($db, "BANK_ASK_PAYMENT_BANK_DURING_SUPPLIER_PROPOSAL", $value, 'chaine', 0, '', $conf->entity);
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

// Activate a model
if ($action == 'set')
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
        if ($conf->global->SUPPLIER_PROPOSAL_ADDON_PDF == "$value") dolibarr_del_const($db, 'SUPPLIER_PROPOSAL_ADDON_PDF',$conf->entity);
	}
}

else if ($action == 'setdoc')
{
    if (dolibarr_set_const($db, "SUPPLIER_PROPOSAL_ADDON_PDF",$value,'chaine',0,'',$conf->entity))
=======
        if ($conf->global->SUPPLIER_PROPOSAL_ADDON_PDF == "$value") dolibarr_del_const($db, 'SUPPLIER_PROPOSAL_ADDON_PDF', $conf->entity);
	}
}

elseif ($action == 'setdoc')
{
    if (dolibarr_set_const($db, "SUPPLIER_PROPOSAL_ADDON_PDF", $value, 'chaine', 0, '', $conf->entity))
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	{
		$conf->global->SUPPLIER_PROPOSAL_ADDON_PDF = $value;
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
	dolibarr_set_const($db, "SUPPLIER_PROPOSAL_ADDON",$value,'chaine',0,'',$conf->entity);
=======
	dolibarr_set_const($db, "SUPPLIER_PROPOSAL_ADDON", $value, 'chaine', 0, '', $conf->entity);
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
}


/*
 * Affiche page
 */

<<<<<<< HEAD
$dirmodels=array_merge(array('/'),(array) $conf->modules_parts['models']);


llxHeader('',$langs->trans("SupplierProposalSetup"));
=======
$dirmodels=array_merge(array('/'), (array) $conf->modules_parts['models']);


llxHeader('', $langs->trans("SupplierProposalSetup"));
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9

$form=new Form($db);

//if ($mesg) print $mesg;

$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1">'.$langs->trans("BackToModuleList").'</a>';
<<<<<<< HEAD
print load_fiche_titre($langs->trans("SupplierProposalSetup"),$linkback,'title_setup');

$head = supplier_proposal_admin_prepare_head();

dol_fiche_head($head, 'general', $langs->trans("CommRequests"), 0, 'supplier_proposal');
=======
print load_fiche_titre($langs->trans("SupplierProposalSetup"), $linkback, 'title_setup');

$head = supplier_proposal_admin_prepare_head();

dol_fiche_head($head, 'general', $langs->trans("CommRequests"), -1, 'supplier_proposal');
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9

/*
 *  Module numerotation
 */
<<<<<<< HEAD
print load_fiche_titre($langs->trans("SupplierProposalNumberingModules"),'','');
=======
print load_fiche_titre($langs->trans("SupplierProposalNumberingModules"), '', '');
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Name")."</td>\n";
print '<td>'.$langs->trans("Description")."</td>\n";
print '<td class="nowrap">'.$langs->trans("Example")."</td>\n";
print '<td align="center" width="60">'.$langs->trans("Status").'</td>';
print '<td align="center" width="16">'.$langs->trans("ShortInfo").'</td>';
print '</tr>'."\n";

clearstatcache();
foreach ($dirmodels as $reldir)
{
	$dir = dol_buildpath($reldir."core/modules/supplier_proposal/");

	if (is_dir($dir))
	{
		$handle = opendir($dir);
		if (is_resource($handle))
		{

			while (($file = readdir($handle))!==false)
			{
				if (substr($file, 0, 22) == 'mod_supplier_proposal_' && substr($file, dol_strlen($file)-3, 3) == 'php')
				{
					$file = substr($file, 0, dol_strlen($file)-4);

					require_once $dir.$file.'.php';

					$module = new $file;

					// Show modules according to features level
					if ($module->version == 'development'  && $conf->global->MAIN_FEATURES_LEVEL < 2) continue;
					if ($module->version == 'experimental' && $conf->global->MAIN_FEATURES_LEVEL < 1) continue;

					if ($module->isEnabled())
					{

						print '<tr class="oddeven"><td>'.$module->nom."</td><td>\n";
						print $module->info();
						print '</td>';

                        // Show example of numbering module
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
						if ($conf->global->SUPPLIER_PROPOSAL_ADDON == "$file")
						{
<<<<<<< HEAD
							print img_picto($langs->trans("Activated"),'switch_on');
						}
						else
						{
							print '<a href="'.$_SERVER["PHP_SELF"].'?action=setmod&amp;value='.$file.'">';
							print img_picto($langs->trans("Disabled"),'switch_off');
=======
							print img_picto($langs->trans("Activated"), 'switch_on');
						}
						else
						{
							print '<a class="reposition" href="'.$_SERVER["PHP_SELF"].'?action=setmod&amp;value='.$file.'">';
							print img_picto($langs->trans("Disabled"), 'switch_off');
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
							print '</a>';
						}
						print '</td>';

						$supplier_proposal=new SupplierProposal($db);
						$supplier_proposal->initAsSpecimen();

						// Info
						$htmltooltip='';
						$htmltooltip.=''.$langs->trans("Version").': <b>'.$module->getVersion().'</b><br>';
<<<<<<< HEAD
						$nextval=$module->getNextValue($mysoc,$supplier_proposal);
                        if ("$nextval" != $langs->trans("NotAvailable")) {  // Keep " on nextval
                            $htmltooltip.=''.$langs->trans("NextValue").': ';
                            if ($nextval) {
                                if (preg_match('/^Error/',$nextval) || $nextval=='NotConfigured')
=======
						$nextval=$module->getNextValue($mysoc, $supplier_proposal);
                        if ("$nextval" != $langs->trans("NotAvailable")) {  // Keep " on nextval
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

<<<<<<< HEAD
print load_fiche_titre($langs->trans("SupplierProposalPDFModules"),'','');
=======
print load_fiche_titre($langs->trans("SupplierProposalPDFModules"), '', '');
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9

// Load array def with activated templates
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
print "  <td>".$langs->trans("Name")."</td>\n";
print "  <td>".$langs->trans("Description")."</td>\n";
print '<td align="center" width="40">'.$langs->trans("Status")."</td>\n";
print '<td align="center" width="40">'.$langs->trans("Default")."</td>\n";
print '<td align="center" width="40">'.$langs->trans("ShortInfo").'</td>';
print '<td align="center" width="40">'.$langs->trans("Preview").'</td>';
print "</tr>\n";

clearstatcache();

foreach ($dirmodels as $reldir)
{
    foreach (array('','/doc') as $valdir)
    {
    	$dir = dol_buildpath($reldir."core/modules/supplier_proposal".$valdir);

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
	                            	print '<td align="center">'."\n";
	                            	print '<a href="'.$_SERVER["PHP_SELF"].'?action=del&amp;value='.$name.'">';
<<<<<<< HEAD
	                            	print img_picto($langs->trans("Enabled"),'switch_on');
=======
	                            	print img_picto($langs->trans("Enabled"), 'switch_on');
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	                            	print '</a>';
	                            	print '</td>';
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

	                            // Defaut
	                            print "<td align=\"center\">";
	                            if ($conf->global->SUPPLIER_PROPOSAL_ADDON_PDF == "$name")
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
	                            if ($module->type == 'pdf')
	                            {
	                                $htmltooltip.='<br>'.$langs->trans("Width").'/'.$langs->trans("Height").': '.$module->page_largeur.'/'.$module->page_hauteur;
	                            }
								$htmltooltip.='<br><br><u>'.$langs->trans("FeaturesSupported").':</u>';
<<<<<<< HEAD
								$htmltooltip.='<br>'.$langs->trans("Logo").': '.yn($module->option_logo,1,1);
								$htmltooltip.='<br>'.$langs->trans("PaymentMode").': '.yn($module->option_modereg,1,1);
								$htmltooltip.='<br>'.$langs->trans("PaymentConditions").': '.yn($module->option_condreg,1,1);
								$htmltooltip.='<br>'.$langs->trans("MultiLanguage").': '.yn($module->option_multilang,1,1);
								//$htmltooltip.='<br>'.$langs->trans("Discounts").': '.yn($module->option_escompte,1,1);
								//$htmltooltip.='<br>'.$langs->trans("CreditNote").': '.yn($module->option_credit_note,1,1);
								$htmltooltip.='<br>'.$langs->trans("WatermarkOnDraftProposal").': '.yn($module->option_draft_watermark,1,1);


	                            print '<td align="center">';
	                            print $form->textwithpicto('',$htmltooltip,1,0);
=======
								$htmltooltip.='<br>'.$langs->trans("Logo").': '.yn($module->option_logo, 1, 1);
								$htmltooltip.='<br>'.$langs->trans("PaymentMode").': '.yn($module->option_modereg, 1, 1);
								$htmltooltip.='<br>'.$langs->trans("PaymentConditions").': '.yn($module->option_condreg, 1, 1);
								$htmltooltip.='<br>'.$langs->trans("MultiLanguage").': '.yn($module->option_multilang, 1, 1);
								//$htmltooltip.='<br>'.$langs->trans("Discounts").': '.yn($module->option_escompte,1,1);
								//$htmltooltip.='<br>'.$langs->trans("CreditNote").': '.yn($module->option_credit_note,1,1);
								$htmltooltip.='<br>'.$langs->trans("WatermarkOnDraftProposal").': '.yn($module->option_draft_watermark, 1, 1);


	                            print '<td align="center">';
	                            print $form->textwithpicto('', $htmltooltip, 1, 0);
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	                            print '</td>';

	                            // Preview
	                            print '<td align="center">';
	                            if ($module->type == 'pdf')
	                            {
<<<<<<< HEAD
	                                print '<a href="'.$_SERVER["PHP_SELF"].'?action=specimen&module='.$name.'">'.img_object($langs->trans("Preview"),'bill').'</a>';
	                            }
	                            else
	                            {
	                                print img_object($langs->trans("PreviewNotAvailable"),'generic');
=======
	                                print '<a href="'.$_SERVER["PHP_SELF"].'?action=specimen&module='.$name.'">'.img_object($langs->trans("Preview"), 'bill').'</a>';
	                            }
	                            else
	                            {
	                                print img_object($langs->trans("PreviewNotAvailable"), 'generic');
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
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
print '<br>';


/*
 * Other options
 *
 */
<<<<<<< HEAD
print load_fiche_titre($langs->trans("OtherOptions"),'','');
=======
print load_fiche_titre($langs->trans("OtherOptions"), '', '');
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9

print "<table class=\"noborder\" width=\"100%\">";
print "<tr class=\"liste_titre\">";
print "<td>".$langs->trans("Parameter")."</td>\n";
print '<td width="60" align="center">'.$langs->trans("Value")."</td>\n";
print "<td>&nbsp;</td>\n";
print "</tr>";

$substitutionarray=pdf_getSubstitutionArray($langs, null, null, 2);
$substitutionarray['__(AnyTranslationKey)__']=$langs->trans("Translation");
$htmltext = '<i>'.$langs->trans("AvailableVariables").':<br>';
foreach($substitutionarray as $key => $val)	$htmltext.=$key.'<br>';
$htmltext.='</i>';

print '<form action="'.$_SERVER["PHP_SELF"].'" method="post">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="set_SUPPLIER_PROPOSAL_FREE_TEXT">';
print '<tr class="oddeven"><td colspan="2">';
print $form->textwithpicto($langs->trans("FreeLegalTextOnSupplierProposal"), $langs->trans("AddCRIfTooLong").'<br><br>'.$htmltext, 1, 'help', '', 0, 2, 'freetexttooltip').'<br>';
$variablename='SUPPLIER_PROPOSAL_FREE_TEXT';
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


print "<form method=\"post\" action=\"".$_SERVER["PHP_SELF"]."\">";
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print "<input type=\"hidden\" name=\"action\" value=\"set_SUPPLIER_PROPOSAL_DRAFT_WATERMARK\">";
print '<tr class="oddeven"><td>';
print $form->textwithpicto($langs->trans("WatermarkOnDraftProposal"), $htmltext, 1, 'help', '', 0, 2, 'watermarktooltip').'<br>';
print '</td><td>';
print '<input size="50" class="flat" type="text" name="SUPPLIER_PROPOSAL_DRAFT_WATERMARK" value="'.$conf->global->SUPPLIER_PROPOSAL_DRAFT_WATERMARK.'">';
<<<<<<< HEAD
print '</td><td align="right">';
=======
print '</td><td class="right">';
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
print "</td></tr>\n";
print '</form>';

if ($conf->banque->enabled)
{

    print '<tr class="oddeven"><td>';
<<<<<<< HEAD
    print $langs->trans("BANK_ASK_PAYMENT_BANK_DURING_SUPPLIER_PROPOSAL").'</td><td>&nbsp</td><td align="right">';
=======
    print $langs->trans("BANK_ASK_PAYMENT_BANK_DURING_SUPPLIER_PROPOSAL").'</td><td>&nbsp</td><td class="right">';
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
    if (! empty($conf->use_javascript_ajax))
    {
        print ajax_constantonoff('BANK_ASK_PAYMENT_BANK_DURING_SUPPLIER_PROPOSAL');
    }
    else
    {
        if (empty($conf->global->BANK_ASK_PAYMENT_BANK_DURING_SUPPLIER_PROPOSAL))
        {
<<<<<<< HEAD
            print '<a href="'.$_SERVER['PHP_SELF'].'?action=set_BANK_ASK_PAYMENT_BANK_DURING_SUPPLIER_PROPOSAL&amp;value=1">'.img_picto($langs->trans("Disabled"),'switch_off').'</a>';
        }
        else
        {
            print '<a href="'.$_SERVER['PHP_SELF'].'?action=set_BANK_ASK_PAYMENT_BANK_DURING_SUPPLIER_PROPOSAL&amp;value=0">'.img_picto($langs->trans("Enabled"),'switch_on').'</a>';
=======
            print '<a href="'.$_SERVER['PHP_SELF'].'?action=set_BANK_ASK_PAYMENT_BANK_DURING_SUPPLIER_PROPOSAL&amp;value=1">'.img_picto($langs->trans("Disabled"), 'switch_off').'</a>';
        }
        else
        {
            print '<a href="'.$_SERVER['PHP_SELF'].'?action=set_BANK_ASK_PAYMENT_BANK_DURING_SUPPLIER_PROPOSAL&amp;value=0">'.img_picto($langs->trans("Enabled"), 'switch_on').'</a>';
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
        }
    }
    print '</td></tr>';
}
else
{

    print '<tr class="oddeven"><td>';
    print $langs->trans("BANK_ASK_PAYMENT_BANK_DURING_SUPPLIER_PROPOSAL").'</td><td>&nbsp;</td><td align="center">'.$langs->trans('NotAvailable').'</td></tr>';
}

print '</table>';



/*
 *  Directory
 */
print '<br>';
<<<<<<< HEAD
print load_fiche_titre($langs->trans("PathToDocuments"),'','');
=======
print load_fiche_titre($langs->trans("PathToDocuments"), '', '');
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9

print "<table class=\"noborder\" width=\"100%\">\n";
print "<tr class=\"liste_titre\">\n";
print "  <td>".$langs->trans("Name")."</td>\n";
print "  <td>".$langs->trans("Value")."</td>\n";
print "</tr>\n";
print "<tr class=\"oddeven\">\n  <td width=\"140\">".$langs->trans("PathDirectory")."</td>\n  <td>".$conf->supplier_proposal->dir_output."</td>\n</tr>\n";
print "</table>\n<br>";

<<<<<<< HEAD
llxFooter();
$db->close();

=======
// End of page
llxFooter();
$db->close();
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
