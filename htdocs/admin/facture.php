<?php
/* Copyright (C) 2003-2004	Rodolphe Quiedeville		<rodolphe@quiedeville.org>
 * Copyright (C) 2004-2011	Laurent Destailleur			<eldy@users.sourceforge.net>
 * Copyright (C) 2005		Eric Seigne					<eric.seigne@ryxeo.com>
 * Copyright (C) 2005-2011	Regis Houssin				<regis@dolibarr.fr>
 * Copyright (C) 2008		Raphael Bertrand (Resultic)	<raphael.bertrand@resultic.fr>
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
 *      \file       htdocs/admin/facture.php
 *		\ingroup    facture
 *		\brief      Page to setup invoice module
 */

require("../main.inc.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/admin.lib.php");
require_once(DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php');

$langs->load("admin");
$langs->load("errors");

if (! $user->admin) accessforbidden();

$action = GETPOST("action");
$value = GETPOST("value");


/*
 * Actions
 */

if ($action == 'updateMask')
{
    $maskconstinvoice=GETPOST("maskconstinvoice");
    $maskconstcredit=GETPOST("maskconstcredit");
    $maskinvoice=GETPOST("maskinvoice");
    $maskcredit=GETPOST("maskcredit");
    if ($maskconstinvoice) $res = dolibarr_set_const($db,$maskconstinvoice,$maskinvoice,'chaine',0,'',$conf->entity);
    if ($maskconstcredit)  $res = dolibarr_set_const($db,$maskconstcredit,$maskcredit,'chaine',0,'',$conf->entity);

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
    $modele=GETPOST("module");

    $facture = new Facture($db);
    $facture->initAsSpecimen();

    // Load template
    $dir = DOL_DOCUMENT_ROOT . "/core/modules/facture/doc/";
    $file = "pdf_".$modele.".modules.php";
    if (file_exists($dir.$file))
    {
        $classname = "pdf_".$modele;
        require_once($dir.$file);

        $obj = new $classname($db);

        if ($obj->write_file($facture,$langs) > 0)
        {
            header("Location: ".DOL_URL_ROOT."/document.php?modulepart=facture&file=SPECIMEN.pdf");
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

// define constants for models generator that need parameters
if ($action == 'setModuleOptions')
{
    $post_size=count($_POST);
    for($i=0;$i < $post_size;$i++)
    {
        if (array_key_exists('param'.$i,$_POST))
        {
            $param=$_POST["param".$i];
            $value=$_POST["value".$i];
            if ($param) $res = dolibarr_set_const($db,$param,$value,'chaine',0,'',$conf->entity);
        }
    }
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

if ($action == 'set')
{
	$label = GETPOST("label");
	$scandir = GETPOST("scandir");

    $type='invoice';
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
    $type='invoice';
    $sql = "DELETE FROM ".MAIN_DB_PREFIX."document_model";
    $sql.= " WHERE nom = '".$db->escape($value)."'";
    $sql.= " AND type = '".$type."'";
    $sql.= " AND entity = ".$conf->entity;

    if ($db->query($sql))
    {
        if ($conf->global->FACTURE_ADDON_PDF == "$value") dolibarr_del_const($db, 'FACTURE_ADDON_PDF',$conf->entity);
    }
}

if ($action == 'setdoc')
{
	$label = GETPOST("label");
	$scandir = GETPOST("scandir");

    $db->begin();

    if (dolibarr_set_const($db, "FACTURE_ADDON_PDF",$value,'chaine',0,'',$conf->entity))
    {
        $conf->global->FACTURE_ADDON_PDF = $value;
    }

    // On active le modele
    $type='invoice';

    $sql_del = "DELETE FROM ".MAIN_DB_PREFIX."document_model";
    $sql_del.= " WHERE nom = '".$db->escape($value)."'";
    $sql_del.= " AND type = '".$type."'";
    $sql_del.= " AND entity = ".$conf->entity;
    dol_syslog("facture.php ".$sql_del);
    $result1=$db->query($sql_del);

    $sql = "INSERT INTO ".MAIN_DB_PREFIX."document_model (nom, type, entity, libelle, description)";
    $sql.= " VALUES ('".$value."', '".$type."', ".$conf->entity.", ";
    $sql.= ($label?"'".$db->escape($label)."'":'null').", ";
    $sql.= (! empty($scandir)?"'".$scandir."'":"null");
    $sql.= ")";
    dol_syslog("facture.php ".$sql);
    $result2=$db->query($sql);
    if ($result1 && $result2)
    {
        $db->commit();
    }
    else
    {
        dol_syslog("facture.php ".$db->lasterror(), LOG_ERR);
        $db->rollback();
    }
}

if ($action == 'setmod')
{
    // TODO Verifier si module numerotation choisi peut etre active
    // par appel methode canBeActivated

    dolibarr_set_const($db, "FACTURE_ADDON",$value,'chaine',0,'',$conf->entity);
}

if ($action == 'setribchq')
{
	$rib = GETPOST("rib");
	$chq = GETPOST("chq");

	$res = dolibarr_set_const($db, "FACTURE_RIB_NUMBER",$rib,'chaine',0,'',$conf->entity);
    $res = dolibarr_set_const($db, "FACTURE_CHQ_NUMBER",$chq,'chaine',0,'',$conf->entity);

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

if ($action == 'set_FACTURE_DRAFT_WATERMARK')
{
	$draft = GETPOST("FACTURE_DRAFT_WATERMARK");

    $res = dolibarr_set_const($db, "FACTURE_DRAFT_WATERMARK",trim($draft),'chaine',0,'',$conf->entity);

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

if ($action == 'set_FACTURE_FREE_TEXT')
{
	$free = GETPOST("FACTURE_FREE_TEXT");

    $res = dolibarr_set_const($db, "FACTURE_FREE_TEXT",$free,'chaine',0,'',$conf->entity);

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

if ($action == 'setforcedate')
{
	$forcedate = GETPOST("forcedate");

    $res = dolibarr_set_const($db, "FAC_FORCE_DATE_VALIDATION",$forcedate,'chaine',0,'',$conf->entity);

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


/*
 * View
 */

llxHeader("",$langs->trans("BillsSetup"),'EN:Invoice_Configuration|FR:Configuration_module_facture|ES:ConfiguracionFactura');

$form=new Form($db);


$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';
print_fiche_titre($langs->trans("BillsSetup"),$linkback,'setup');
print '<br>';


/*
 *  Numbering module
 */

print_titre($langs->trans("BillsNumberingModule"));

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Name").'</td>';
print '<td>'.$langs->trans("Description").'</td>';
print '<td nowrap>'.$langs->trans("Example").'</td>';
print '<td align="center" width="60">'.$langs->trans("Status").'</td>';
print '<td align="center" width="16">'.$langs->trans("Infos").'</td>';
print '</tr>'."\n";

clearstatcache();

$var=true;
foreach ($conf->file->dol_document_root as $dirroot)
{
    $dir = $dirroot . "/core/modules/facture/";

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
                    if (! class_exists($classname) && is_readable($dir.$filebis) && (preg_match('/mod_/',$filebis) || preg_match('/mod_/',$classname)) && substr($filebis, dol_strlen($filebis)-3, 3) == 'php')
                    {
                        // Chargement de la classe de numerotation
                        require_once($dir.$filebis);

                        $module = new $classname($db);

                        // Show modules according to features level
                        if ($module->version == 'development'  && $conf->global->MAIN_FEATURES_LEVEL < 2) continue;
                        if ($module->version == 'experimental' && $conf->global->MAIN_FEATURES_LEVEL < 1) continue;

                        if ($module->isEnabled())
                        {
                            $var = !$var;
                            print '<tr '.$bc[$var].'><td width="100">';
                            echo preg_replace('/mod_facture_/','',preg_replace('/\.php$/','',$file));
                            print "</td><td>\n";

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
                            //print "> ".$conf->global->FACTURE_ADDON." - ".$file;
                            if ($conf->global->FACTURE_ADDON == $file || $conf->global->FACTURE_ADDON.'.php' == $file)
                            {
                                print img_picto($langs->trans("Activated"),'switch_on');
                            }
                            else
                            {
                                print '<a href="'.$_SERVER["PHP_SELF"].'?action=setmod&amp;value='.preg_replace('/\.php$/','',$file).'&amp;scandir='.$module->scandir.'&amp;label='.urlencode($module->name).'" alt="'.$langs->trans("Default").'">'.img_picto($langs->trans("Disabled"),'switch_off').'</a>';
                            }
                            print '</td>';

                            $facture=new Facture($db);
                            $facture->initAsSpecimen();

                            // Example for standard invoice
                            $htmltooltip='';
                            $htmltooltip.=''.$langs->trans("Version").': <b>'.$module->getVersion().'</b><br>';
                            $facture->type=0;
                            $nextval=$module->getNextValue($mysoc,$facture);
                            if ("$nextval" != $langs->trans("NotAvailable"))	// Keep " on nextval
                            {
                                $htmltooltip.=$langs->trans("NextValueForInvoices").': ';
                                if ($nextval)
                                {
                                    $htmltooltip.=$nextval.'<br>';
                                }
                                else
                                {
                                    $htmltooltip.=$langs->trans($module->error).'<br>';
                                }
                            }
                            // Example for credit invoice
                            $facture->type=2;
                            $nextval=$module->getNextValue($mysoc,$facture);
                            if ("$nextval" != $langs->trans("NotAvailable"))	// Keep " on nextval
                            {
                                $htmltooltip.=$langs->trans("NextValueForCreditNotes").': ';
                                if ($nextval)
                                {
                                    $htmltooltip.=$nextval;
                                }
                                else
                                {
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
print_titre($langs->trans("BillsPDFModules"));

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
print '<td align="center" width="32" colspan="2">'.$langs->trans("Infos").'</td>';
print "</tr>\n";

clearstatcache();

$var=true;
foreach ($conf->file->dol_document_root as $dirroot)
{
    foreach (array('','/doc') as $valdir)
    {
        $dir = $dirroot . "/core/modules/facture".$valdir;

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

	                        require_once($dir.'/'.$file);
	                        $module = new $classname($db);

	                        $modulequalified=1;
	                        if ($module->version == 'development'  && $conf->global->MAIN_FEATURES_LEVEL < 2) $modulequalified=0;
	                        if ($module->version == 'experimental' && $conf->global->MAIN_FEATURES_LEVEL < 1) $modulequalified=0;

	                        if ($modulequalified)
	                        {
	                            $var = !$var;
	                            print '<tr '.$bc[$var].'><td width="100">';
	                            print (empty($module->name)?$name:$module->name);
	                            print "</td><td>\n";
	                            if (method_exists($module,'info')) print $module->info($langs);
	                            else print $module->description;
	                            print '</td>';

	                            // Active
	                            if (in_array($name, $def))
	                            {
	                            	print '<td align="center">'."\n";
	                            	print '<a href="'.$_SERVER["PHP_SELF"].'?action=del&amp;value='.$name.'">';
	                            	print img_picto($langs->trans("Enabled"),'switch_on');
	                            	print '</a>';
	                            	print '</td>';
	                            }
	                            else
	                            {
	                                print "<td align=\"center\">\n";
	                                print '<a href="'.$_SERVER["PHP_SELF"].'?action=set&amp;value='.$name.'&amp;scandir='.$module->scandir.'&amp;label='.urlencode($module->name).'">'.img_picto($langs->trans("Disabled"),'switch_off').'</a>';
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
	                                print '<a href="'.$_SERVER["PHP_SELF"].'?action=setdoc&amp;value='.$name.'&amp;scandir='.$module->scandir.'&amp;label='.urlencode($module->name).'" alt="'.$langs->trans("Default").'">'.img_picto($langs->trans("Disabled"),'off').'</a>';
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
	                            $htmltooltip.='<br>'.$langs->trans("Escompte").': '.yn($module->option_escompte,1,1);
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


/*
 *  Modes de reglement
 *
 */
print '<br>';
print_titre($langs->trans("SuggestedPaymentModesIfNotDefinedInInvoice"));

print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'" />';

print '<table class="noborder" width="100%">';
$var=True;

print '<tr class="liste_titre">';
print '<td>';
print '<input type="hidden" name="action" value="setribchq">';
print $langs->trans("PaymentMode").'</td>';
print '<td align="right"><input type="submit" class="button" value="'.$langs->trans("Modify").'"></td>';
print "</tr>\n";
$var=!$var;
print '<tr '.$bc[$var].'>';
print "<td>".$langs->trans("SuggestPaymentByRIBOnAccount")."</td>";
print "<td>";
if ($conf->banque->enabled)
{
    $sql = "SELECT rowid, label";
    $sql.= " FROM ".MAIN_DB_PREFIX."bank_account";
    $sql.= " WHERE clos = 0";
    $sql.= " AND courant = 1";
    $sql.= " AND entity = ".$conf->entity;
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
                print $conf->global->FACTURE_RIB_NUMBER == $row[0] ? ' selected="selected"':'';
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
$var=!$var;
print '<tr '.$bc[$var].'>';
print "<td>".$langs->trans("SuggestPaymentByChequeToAddress")."</td>";
print "<td>";
print '<select class="flat" name="chq" id="chq">';
print '<option value="0">'.$langs->trans("DoNotSuggestPaymentMode").'</option>';
print '<option value="-1"'.($conf->global->FACTURE_CHQ_NUMBER?' selected="selected"':'').'>'.$langs->trans("MenuCompanySetup").' ('.($mysoc->name?$mysoc->name:$langs->trans("NotDefined")).')</option>';

$sql = "SELECT rowid, label";
$sql.= " FROM ".MAIN_DB_PREFIX."bank_account";
$sql.= " WHERE clos = 0";
$sql.= " AND courant = 1";
$sql.= " AND entity = ".$conf->entity;
$var=True;
$resql=$db->query($sql);
if ($resql)
{
    $num = $db->num_rows($resql);
    $i = 0;
    while ($i < $num)
    {
        $var=!$var;
        $row = $db->fetch_row($resql);

        print '<option value="'.$row[0].'"';
        print $conf->global->FACTURE_CHQ_NUMBER == $row[0] ? ' selected="selected"':'';
        print '>'.$langs->trans("OwnerOfBankAccount",$row[1]).'</option>';

        $i++;
    }
}
print "</select>";
print "</td></tr>";
print "</table>";
print "</form>";


print "<br>";
print_titre($langs->trans("OtherOptions"));

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Parameter").'</td>';
print '<td align="center" width="60">'.$langs->trans("Value").'</td>';
print '<td width="80">&nbsp;</td>';
print "</tr>\n";
$var=true;

// Force date validation
$var=! $var;
print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'" />';
print '<input type="hidden" name="action" value="setforcedate" />';
print '<tr '.$bc[$var].'><td>';
print $langs->trans("ForceInvoiceDate");
print '</td><td width="60" align="center">';
print $form->selectyesno("forcedate",$conf->global->FAC_FORCE_DATE_VALIDATION,1);
print '</td><td align="right">';
print '<input type="submit" class="button" value="'.$langs->trans("Modify").'" />';
print "</td></tr>\n";
print '</form>';

$var=! $var;
print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'" />';
print '<input type="hidden" name="action" value="set_FACTURE_FREE_TEXT" />';
print '<tr '.$bc[$var].'><td colspan="2">';
print $langs->trans("FreeLegalTextOnInvoices").' ('.$langs->trans("AddCRIfTooLong").')<br>';
print '<textarea name="FACTURE_FREE_TEXT" class="flat" cols="120">'.$conf->global->FACTURE_FREE_TEXT.'</textarea>';
print '</td><td align="right">';
print '<input type="submit" class="button" value="'.$langs->trans("Modify").'" />';
print "</td></tr>\n";
print '</form>';

$var=!$var;
print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'" />';
print '<input type="hidden" name="action" value="set_FACTURE_DRAFT_WATERMARK" />';
print '<tr '.$bc[$var].'><td colspan="2">';
print $langs->trans("WatermarkOnDraftBill").'<br>';
print '<input size="50" class="flat" type="text" name="FACTURE_DRAFT_WATERMARK" value="'.$conf->global->FACTURE_DRAFT_WATERMARK.'" />';
print '</td><td align="right">';
print '<input type="submit" class="button" value="'.$langs->trans("Modify").'" />';
print "</td></tr>\n";
print '</form>';

print '</table>';


/*
 *  Repertoire
 */
print '<br>';
print_titre($langs->trans("PathToDocuments"));

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


//dol_fiche_end();

dol_htmloutput_mesg($mesg);

$db->close();

llxFooter();
?>
