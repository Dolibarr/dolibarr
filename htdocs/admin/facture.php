<?php
/* Copyright (C) 2003-2004	Rodolphe Quiedeville		<rodolphe@quiedeville.org>
 * Copyright (C) 2004-2011	Laurent Destailleur			<eldy@users.sourceforge.net>
 * Copyright (C) 2005		Eric Seigne					<eric.seigne@ryxeo.com>
 * Copyright (C) 2005-2012	Regis Houssin				<regis.houssin@inodbox.com>
 * Copyright (C) 2008		Raphael Bertrand (Resultic)	<raphael.bertrand@resultic.fr>
 * Copyright (C) 2012-2013  Juanjo Menent				<jmenent@2byte.es>
 * Copyright (C) 2014		Teddy Andreotti				<125155@supinfo.com>
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
 *      \file       htdocs/admin/facture.php
 *		\ingroup    facture
 *		\brief      Page to setup invoice module
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/invoice.lib.php';
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';

// Load translation files required by the page
$langs->loadLangs(array('admin', 'errors', 'other', 'bills'));

if (! $user->admin) accessforbidden();

$action = GETPOST('action','alpha');
$value = GETPOST('value','alpha');
$label = GETPOST('label','alpha');
$scandir = GETPOST('scan_dir','alpha');
$type='invoice';


/*
 * Actions
 */

include DOL_DOCUMENT_ROOT.'/core/actions_setmoduleoptions.inc.php';

if ($action == 'updateMask')
{
    $maskconstinvoice=GETPOST('maskconstinvoice','alpha');
    $maskconstreplacement=GETPOST('maskconstreplacement','alpha');
    $maskconstcredit=GETPOST('maskconstcredit','alpha');
	$maskconstdeposit=GETPOST('maskconstdeposit','alpha');
    $maskinvoice=GETPOST('maskinvoice','alpha');
    $maskreplacement=GETPOST('maskreplacement','alpha');
    $maskcredit=GETPOST('maskcredit','alpha');
	$maskdeposit=GETPOST('maskdeposit','alpha');
    if ($maskconstinvoice) $res = dolibarr_set_const($db,$maskconstinvoice,$maskinvoice,'chaine',0,'',$conf->entity);
    if ($maskconstreplacement) $res = dolibarr_set_const($db,$maskconstreplacement,$maskreplacement,'chaine',0,'',$conf->entity);
    if ($maskconstcredit)  $res = dolibarr_set_const($db,$maskconstcredit,$maskcredit,'chaine',0,'',$conf->entity);
	if ($maskconstdeposit)  $res = dolibarr_set_const($db,$maskconstdeposit,$maskdeposit,'chaine',0,'',$conf->entity);

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

    $facture = new Facture($db);
    $facture->initAsSpecimen();

	// Search template files
	$file=''; $classname=''; $filefound=0;
	$dirmodels=array_merge(array('/'),(array) $conf->modules_parts['models']);
	foreach($dirmodels as $reldir)
	{
	    $file=dol_buildpath($reldir."core/modules/facture/doc/pdf_".$modele.".modules.php",0);
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

    	if ($module->write_file($facture,$langs) > 0)
    	{
    		header("Location: ".DOL_URL_ROOT."/document.php?modulepart=facture&file=SPECIMEN.pdf");
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
else if ($action == 'set')
{
	$ret = addDocumentModel($value, $type, $label, $scandir);
}

else if ($action == 'del')
{
	$ret = delDocumentModel($value, $type);
	if ($ret > 0)
	{
        if ($conf->global->FACTURE_ADDON_PDF == "$value") dolibarr_del_const($db, 'FACTURE_ADDON_PDF',$conf->entity);
	}
}

// Set default model
else if ($action == 'setdoc')
{
	if (dolibarr_set_const($db, "FACTURE_ADDON_PDF",$value,'chaine',0,'',$conf->entity))
	{
		// La constante qui a ete lue en avant du nouveau set
		// on passe donc par une variable pour avoir un affichage coherent
		$conf->global->FACTURE_ADDON_PDF = $value;
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

    dolibarr_set_const($db, "FACTURE_ADDON",$value,'chaine',0,'',$conf->entity);
}

else if ($action == 'setribchq')
{
	$rib = GETPOST('rib','alpha');
	$chq = GETPOST('chq','alpha');

	$res = dolibarr_set_const($db, "FACTURE_RIB_NUMBER",$rib,'chaine',0,'',$conf->entity);
    $res = dolibarr_set_const($db, "FACTURE_CHQ_NUMBER",$chq,'chaine',0,'',$conf->entity);

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

else if ($action == 'set_FACTURE_DRAFT_WATERMARK')
{
	$draft = GETPOST('FACTURE_DRAFT_WATERMARK','alpha');

    $res = dolibarr_set_const($db, "FACTURE_DRAFT_WATERMARK",trim($draft),'chaine',0,'',$conf->entity);

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

else if ($action == 'set_INVOICE_FREE_TEXT')
{
	$freetext = GETPOST('INVOICE_FREE_TEXT','none');	// No alpha here, we want exact string

    $res = dolibarr_set_const($db, "INVOICE_FREE_TEXT",$freetext,'chaine',0,'',$conf->entity);

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

else if ($action == 'setforcedate')
{
	$forcedate = GETPOST('forcedate','alpha');

    $res = dolibarr_set_const($db, "FAC_FORCE_DATE_VALIDATION",$forcedate,'chaine',0,'',$conf->entity);

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

else if ($action == 'setDefaultPDFModulesByType')
{
    $invoicetypemodels =  GETPOST('invoicetypemodels');

    if(!empty($invoicetypemodels) && is_array($invoicetypemodels))
    {
        $error = 0;

        foreach ($invoicetypemodels as $type => $value)
        {
            $res = dolibarr_set_const($db, 'FACTURE_ADDON_PDF_'.intval($type),$value,'chaine',0,'',$conf->entity);
            if (! $res > 0) $error++;
        }

        if (! $error)
        {
            setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
        }
        else
        {
            setEventMessages($langs->trans("Error"), null, 'errors');
        }
    }
}


/*
 * View
 */

$dirmodels=array_merge(array('/'),(array) $conf->modules_parts['models']);

llxHeader("",$langs->trans("BillsSetup"),'EN:Invoice_Configuration|FR:Configuration_module_facture|ES:ConfiguracionFactura');

$form=new Form($db);


$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1">'.$langs->trans("BackToModuleList").'</a>';
print load_fiche_titre($langs->trans("BillsSetup"),$linkback,'title_setup');

$head = invoice_admin_prepare_head();
dol_fiche_head($head, 'general', $langs->trans("Invoices"), -1, 'invoice');

/*
 *  Numbering module
 */

print load_fiche_titre($langs->trans("BillsNumberingModule"),'','');

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
	$dir = dol_buildpath($reldir."core/modules/facture/");
    if (is_dir($dir))
    {
        $handle = opendir($dir);
        if (is_resource($handle))
        {
            while (($file = readdir($handle))!==false)
            {
                if (! is_dir($dir.$file) || (substr($file, 0, 1) <> '.' && substr($file, 0, 3) <> 'CVS'))
                {
                    $filebis = $file;
                    $classname = preg_replace('/\.php$/','',$file);
                    // For compatibility
                    if (! is_file($dir.$filebis))
                    {
                        $filebis = $file."/".$file.".modules.php";
                        $classname = "mod_facture_".$file;
                    }
                    // Check if there is a filter on country
                    preg_match('/\-(.*)_(.*)$/',$classname,$reg);
                    if (! empty($reg[2]) && $reg[2] != strtoupper($mysoc->country_code)) continue;

                    $classname = preg_replace('/\-.*$/','',$classname);
                    if (! class_exists($classname) && is_readable($dir.$filebis) && (preg_match('/mod_/',$filebis) || preg_match('/mod_/',$classname)) && substr($filebis, dol_strlen($filebis)-3, 3) == 'php')
                    {
                        // Charging the numbering class
                        require_once $dir.$filebis;

                        $module = new $classname($db);

                        // Show modules according to features level
                        if ($module->version == 'development'  && $conf->global->MAIN_FEATURES_LEVEL < 2) continue;
                        if ($module->version == 'experimental' && $conf->global->MAIN_FEATURES_LEVEL < 1) continue;

                        if ($module->isEnabled())
                        {
                            print '<tr class="oddeven"><td width="100">';
                            echo preg_replace('/\-.*$/','',preg_replace('/mod_facture_/','',preg_replace('/\.php$/','',$file)));
                            print "</td><td>\n";

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
                            //print "> ".$conf->global->FACTURE_ADDON." - ".$file;
                            if ($conf->global->FACTURE_ADDON == $file || $conf->global->FACTURE_ADDON.'.php' == $file)
                            {
                                print img_picto($langs->trans("Activated"),'switch_on');
                            }
                            else
                            {
                                print '<a href="'.$_SERVER["PHP_SELF"].'?action=setmod&value='.preg_replace('/\.php$/','',$file).'&scan_dir='.$module->scandir.'&label='.urlencode($module->name).'" alt="'.$langs->trans("Default").'">'.img_picto($langs->trans("Disabled"),'switch_off').'</a>';
                            }
                            print '</td>';

                            $facture=new Facture($db);
                            $facture->initAsSpecimen();

                            // Example for standard invoice
                            $htmltooltip='';
                            $htmltooltip.=''.$langs->trans("Version").': <b>'.$module->getVersion().'</b><br>';
                            $facture->type=0;
                            $nextval=$module->getNextValue($mysoc,$facture);
                            if ("$nextval" != $langs->trans("NotAvailable")) {  // Keep " on nextval
                                $htmltooltip.=$langs->trans("NextValueForInvoices").': ';
                                if ($nextval) {
                                    if (preg_match('/^Error/',$nextval) || $nextval=='NotConfigured')
                                        $nextval = $langs->trans($nextval);
                                    $htmltooltip.=$nextval.'<br>';
                                } else {
                                    $htmltooltip.=$langs->trans($module->error).'<br>';
                                }
                            }
                            // Example for remplacement
                            $facture->type=1;
                            $nextval=$module->getNextValue($mysoc,$facture);
                            if ("$nextval" != $langs->trans("NotAvailable")) {  // Keep " on nextval
                                $htmltooltip.=$langs->trans("NextValueForReplacements").': ';
                                if ($nextval) {
                                    if (preg_match('/^Error/',$nextval) || $nextval=='NotConfigured')
                                        $nextval = $langs->trans($nextval);
                                    $htmltooltip.=$nextval.'<br>';
                                } else {
                                    $htmltooltip.=$langs->trans($module->error).'<br>';
                                }
                            }

                            // Example for credit invoice
                            $facture->type=2;
                            $nextval=$module->getNextValue($mysoc,$facture);
                            if ("$nextval" != $langs->trans("NotAvailable")) {  // Keep " on nextval
                                $htmltooltip.=$langs->trans("NextValueForCreditNotes").': ';
                                if ($nextval) {
                                    if (preg_match('/^Error/',$nextval) || $nextval=='NotConfigured')
                                        $nextval = $langs->trans($nextval);
                                    $htmltooltip.=$nextval.'<br>';
                                } else {
                                    $htmltooltip.=$langs->trans($module->error).'<br>';
                                }
                            }
                            // Example for deposit invoice
                            $facture->type=3;
                            $nextval=$module->getNextValue($mysoc,$facture);
                            if ("$nextval" != $langs->trans("NotAvailable")) {  // Keep " on nextval
                                $htmltooltip.=$langs->trans("NextValueForDeposit").': ';
                                if ($nextval) {
                                    if (preg_match('/^Error/',$nextval) || $nextval=='NotConfigured')
                                        $nextval = $langs->trans($nextval);
                                    $htmltooltip.=$nextval;
                                } else {
                                    $htmltooltip.=$langs->trans($module->error);
                                }
                            }

                            print '<td align="center">';
                            print $form->textwithpicto('',$htmltooltip,1,0);

                            if ($conf->global->FACTURE_ADDON.'.php' == $file)  // If module is the one used, we show existing errors
                            {
                                if (! empty($module->error)) dol_htmloutput_mesg($module->error,'','error',1);
                            }

                            print '</td>';

                            print "</tr>\n";
                        }
                    }
                }
            }
            closedir($handle);
        }
    }
}

print '</table>';


/*
 *  Document templates generators
 */
print '<br>';
print load_fiche_titre($langs->trans("BillsPDFModules"),'','');

// Load array def with activated templates
$type='invoice';
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
print '<td align="center" width="60">'.$langs->trans("Status").'</td>';
print '<td align="center" width="60">'.$langs->trans("Default").'</td>';
print '<td align="center" width="32">'.$langs->trans("ShortInfo").'</td>';
print '<td align="center" width="32">'.$langs->trans("Preview").'</td>';
print "</tr>\n";

clearstatcache();

$activatedModels = array();

foreach ($dirmodels as $reldir)
{
    foreach (array('','/doc') as $valdir)
    {
    	$dir = dol_buildpath($reldir."core/modules/facture".$valdir);

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
	                            	print '<td align="center">'."\n";
	                            	print '<a href="'.$_SERVER["PHP_SELF"].'?action=del&value='.$name.'">';
	                            	print img_picto($langs->trans("Enabled"),'switch_on');
	                            	print '</a>';
	                            	print '</td>';
	                            }
	                            else
	                            {
	                                print "<td align=\"center\">\n";
	                                print '<a href="'.$_SERVER["PHP_SELF"].'?action=set&value='.$name.'&scan_dir='.$module->scandir.'&label='.urlencode($module->name).'">'.img_picto($langs->trans("SetAsDefault"),'switch_off').'</a>';
	                                print "</td>";
	                            }

	                            // Defaut
	                            print "<td align=\"center\">";
	                            if ($conf->global->FACTURE_ADDON_PDF == "$name")
	                            {
	                                print img_picto($langs->trans("Default"),'on');
	                            }
	                            else
	                            {
	                                print '<a href="'.$_SERVER["PHP_SELF"].'?action=setdoc&value='.$name.'&scan_dir='.$module->scandir.'&label='.urlencode($module->name).'" alt="'.$langs->trans("Default").'">'.img_picto($langs->trans("SetAsDefault"),'off').'</a>';
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
	                            $htmltooltip.='<br>'.$langs->trans("PaymentMode").': '.yn($module->option_modereg,1,1);
	                            $htmltooltip.='<br>'.$langs->trans("PaymentConditions").': '.yn($module->option_condreg,1,1);
	                            $htmltooltip.='<br>'.$langs->trans("Discounts").': '.yn($module->option_escompte,1,1);
	                            $htmltooltip.='<br>'.$langs->trans("CreditNote").': '.yn($module->option_credit_note,1,1);
	                            $htmltooltip.='<br>'.$langs->trans("MultiLanguage").': '.yn($module->option_multilang,1,1);
	                            $htmltooltip.='<br>'.$langs->trans("WatermarkOnDraftInvoices").': '.yn($module->option_draft_watermark,1,1);


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
print '</table>';

if(!empty($conf->global->INVOICE_USE_DEFAULT_DOCUMENT)) // Hidden conf
{
    /*
     *  Document templates generators
     */
    print '<br>';
    print load_fiche_titre($langs->trans("BillsPDFModulesAccordindToInvoiceType"),'','');
    print '<form action="'.$_SERVER["PHP_SELF"].'#default-pdf-modules-by-type-table" method="POST">';
    print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'" />';
    print '<input type="hidden" name="action" value="setDefaultPDFModulesByType" >';
    print '<table id="default-pdf-modules-by-type-table" class="noborder" width="100%">';
    print '<tr class="liste_titre">';
    print '<td>'.$langs->trans("Type").'</td>';
    print '<td>'.$langs->trans("Name").'</td>';
    print '<td align="right"><input type="submit" class="button" value="'.$langs->trans("Modify").'"></td>';
    print "</tr>\n";

    $listtype=array(
        Facture::TYPE_STANDARD=>$langs->trans("InvoiceStandard"),
        Facture::TYPE_REPLACEMENT=>$langs->trans("InvoiceReplacement"),
        Facture::TYPE_CREDIT_NOTE=>$langs->trans("InvoiceAvoir"),
        Facture::TYPE_DEPOSIT=>$langs->trans("InvoiceDeposit"),
    );
    if (! empty($conf->global->INVOICE_USE_SITUATION))
    {
        $listtype[Facture::TYPE_SITUATION] = $langs->trans("InvoiceSituation");
    }

    foreach ($listtype as $type => $trans)
    {
        $thisTypeConfName = 'FACTURE_ADDON_PDF_'.$type;
        $current = !empty($conf->global->{$thisTypeConfName})?$conf->global->{$thisTypeConfName}:$conf->global->FACTURE_ADDON_PDF;
        print '<tr >';
        print '<td>'.$trans.'</td>';
        print '<td colspan="2" >'.$form->selectarray('invoicetypemodels['.$type.']', ModelePDFFactures::liste_modeles($db), $current,0,0, 0).'</td>';
        print "</tr>\n";
    }

    print '</table>';
    print "</form>";
}

/*
 *  Modes de reglement
 */
print '<br>';
print load_fiche_titre($langs->trans("SuggestedPaymentModesIfNotDefinedInInvoice"),'','');

print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'" />';

print '<table class="noborder" width="100%">';

print '<tr class="liste_titre">';
print '<td>';
print '<input type="hidden" name="action" value="setribchq">';
print $langs->trans("PaymentMode").'</td>';
print '<td align="right"><input type="submit" class="button" value="'.$langs->trans("Modify").'"></td>';
print "</tr>\n";

print '<tr class="oddeven">';
print "<td>".$langs->trans("SuggestPaymentByRIBOnAccount")."</td>";
print "<td>";
if (! empty($conf->banque->enabled))
{
    $sql = "SELECT rowid, label";
    $sql.= " FROM ".MAIN_DB_PREFIX."bank_account";
    $sql.= " WHERE clos = 0";
    $sql.= " AND courant = 1";
    $sql.= " AND entity IN (".getEntity('bank_account').")";
    $resql=$db->query($sql);
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
                print $conf->global->FACTURE_RIB_NUMBER == $row[0] ? ' selected':'';
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
    print $langs->trans("BankModuleNotActive");
}
print "</td></tr>";

print '<tr class="oddeven">';
print "<td>".$langs->trans("SuggestPaymentByChequeToAddress")."</td>";
print "<td>";
print '<select class="flat" name="chq" id="chq">';
print '<option value="0">'.$langs->trans("DoNotSuggestPaymentMode").'</option>';
print '<option value="-1"'.($conf->global->FACTURE_CHQ_NUMBER?' selected':'').'>'.$langs->trans("MenuCompanySetup").' ('.($mysoc->name?$mysoc->name:$langs->trans("NotDefined")).')</option>';

$sql = "SELECT rowid, label";
$sql.= " FROM ".MAIN_DB_PREFIX."bank_account";
$sql.= " WHERE clos = 0";
$sql.= " AND courant = 1";
$sql.= " AND entity IN (".getEntity('bank_account').")";

$resql=$db->query($sql);
if ($resql)
{
    $num = $db->num_rows($resql);
    $i = 0;
    while ($i < $num)
    {

        $row = $db->fetch_row($resql);

        print '<option value="'.$row[0].'"';
        print $conf->global->FACTURE_CHQ_NUMBER == $row[0] ? ' selected':'';
        print '>'.$langs->trans("OwnerOfBankAccount",$row[1]).'</option>';

        $i++;
    }
}
print "</select>";
print "</td></tr>";
print "</table>";
print "</form>";


print "<br>";
print load_fiche_titre($langs->trans("OtherOptions"),'','');

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Parameter").'</td>';
print '<td align="center" width="60">'.$langs->trans("Value").'</td>';
print '<td width="80">&nbsp;</td>';
print "</tr>\n";

// Force date validation
print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'" />';
print '<input type="hidden" name="action" value="setforcedate" />';
print '<tr class="oddeven"><td>';
print $langs->trans("ForceInvoiceDate");
print '</td><td width="60" align="center">';
print $form->selectyesno("forcedate",$conf->global->FAC_FORCE_DATE_VALIDATION,1);
print '</td><td align="right">';
print '<input type="submit" class="button" value="'.$langs->trans("Modify").'" />';
print "</td></tr>\n";
print '</form>';

$substitutionarray=pdf_getSubstitutionArray($langs, null, null, 2);
$substitutionarray['__(AnyTranslationKey)__']=$langs->trans("Translation");
$htmltext = '<i>'.$langs->trans("AvailableVariables").':<br>';
foreach($substitutionarray as $key => $val)	$htmltext.=$key.'<br>';
$htmltext.='</i>';

print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'" />';
print '<input type="hidden" name="action" value="set_INVOICE_FREE_TEXT" />';
print '<tr class="oddeven"><td colspan="2">';
print $form->textwithpicto($langs->trans("FreeLegalTextOnInvoices"), $langs->trans("AddCRIfTooLong").'<br><br>'.$htmltext, 1, 'help', '', 0, 2, 'freetexttooltip').'<br>';
$variablename='INVOICE_FREE_TEXT';
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
print '</td><td align="right">';
print '<input type="submit" class="button" value="'.$langs->trans("Modify").'" />';
print "</td></tr>\n";
print '</form>';


print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'" />';
print '<input type="hidden" name="action" value="set_FACTURE_DRAFT_WATERMARK" />';
print '<tr class="oddeven"><td>';
print $form->textwithpicto($langs->trans("WatermarkOnDraftBill"), $htmltext, 1, 'help', '', 0, 2, 'watermarktooltip').'<br>';
print '</td>';
print '<td><input size="50" class="flat" type="text" name="FACTURE_DRAFT_WATERMARK" value="'.$conf->global->FACTURE_DRAFT_WATERMARK.'" />';
print '</td><td align="right">';
print '<input type="submit" class="button" value="'.$langs->trans("Modify").'" />';
print "</td></tr>\n";
print '</form>';

print '</table>';


/*
 *  Repertoire
 */
print '<br>';
print load_fiche_titre($langs->trans("PathToDocuments"),'','');

print '<table class="noborder" width="100%">'."\n";
print '<tr class="liste_titre">'."\n";
print '<td>'.$langs->trans("Name").'</td>'."\n";
print '<td>'.$langs->trans("Value").'</td>'."\n";
print "</tr>\n";
print '<tr '.$bc[false].'>'."\n";
print '<td width="140">'.$langs->trans("PathDirectory").'</td>'."\n";
print '<td>'.$conf->facture->dir_output.'</td>'."\n";
print '</tr>'."\n";
print "</table>\n";


/*
 * Notifications
 */
print '<br>';
print load_fiche_titre($langs->trans("Notifications"),'','');
print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Parameter").'</td>';
print '<td align="center" width="60"></td>';
print '<td width="80">&nbsp;</td>';
print "</tr>\n";

print '<tr class="oddeven"><td colspan="2">';
print $langs->trans("YouMayFindNotificationsFeaturesIntoModuleNotification").'<br>';
print '</td><td align="right">';
print "</td></tr>\n";

print '</table>';

dol_fiche_end();

// End of page
llxFooter();
$db->close();
