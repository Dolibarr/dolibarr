<?php
/* Copyright (C) 2003-2007 Rodolphe Quiedeville    <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2011 Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2011 Regis Houssin           <regis@dolibarr.fr>
 * Copyright (C) 2004      Sebastien Di Cintio     <sdicintio@ressource-toi.org>
 * Copyright (C) 2004      Benoit Mortier          <benoit.mortier@opensides.be>
 * Copyright (C) 2010-2011 Juanjo Menent           <jmenent@2byte.es>
 * Copyright (C) 2011      Philippe Grand          <philippe.grand@atoo-net.com>
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
 *  \file       htdocs/admin/fournisseur.php
 *  \ingroup    fournisseur
 *  \brief      Page d'administration-configuration du module Fournisseur
 */

require("../main.inc.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/admin.lib.php");
require_once(DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.class.php');
require_once(DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.commande.class.php');
require_once(DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.facture.class.php');

$langs->load("admin");

if (!$user->admin)
accessforbidden();

$type=GETPOST('type');
$value=GETPOST('value');
$action=GETPOST('action');

$specimenthirdparty=new Societe($db);
$specimenthirdparty->initAsSpecimen();


/*
 * Actions
 */

if ($action == 'updateMask')
{
    $maskconstorder=$_POST['maskconstorder'];
    $maskorder=$_POST['maskorder'];
    if ($maskconstorder)  $res = dolibarr_set_const($db,$maskconstorder,$maskorder,'chaine',0,'',$conf->entity);

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

if ($action == 'specimen')  // For orders
{
    $modele=GETPOST("module");

    $commande = new CommandeFournisseur($db);
    $commande->initAsSpecimen();
    $commande->thirdparty=$specimenthirdparty;
    
    // Search template files
    $file=''; $classname=''; $filefound=0;
    $dirmodels=array_merge(array('/'),(array)$conf->modules_parts['models']);
    foreach($dirmodels as $reldir)
    {
    	$file=dol_buildpath($reldir."core/modules/supplier_order/pdf/pdf_".$modele.".modules.php",0);
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
    
    	if ($module->write_file($commande,$langs) > 0)
    	{
    		header("Location: ".DOL_URL_ROOT."/document.php?modulepart=commande_fournisseur&file=SPECIMEN.pdf");
    		return;
    	}
    	else
    	{
    		$mesg='<font class="error">'.$module->error.'</font>';
    		dol_syslog($module->error, LOG_ERR);
    	}
    }
    else
    {
    	$mesg='<font class="error">'.$langs->trans("ErrorModuleNotFound").'</font>';
    	dol_syslog($langs->trans("ErrorModuleNotFound"), LOG_ERR);
    }
}

if ($action == 'specimenfacture')   // For invoices
{
    $modele=GETPOST("module");

    $facture = new FactureFournisseur($db);
    $facture->initAsSpecimen();
    $facture->thirdparty=$specimenthirdparty;    // Define who should has build the invoice (so the supplier)
    
	// Search template files
    $file=''; $classname=''; $filefound=0;
    $dirmodels=array_merge(array('/'),(array)$conf->modules_parts['models']);
    foreach($dirmodels as $reldir)
    {
    	$file=dol_buildpath($reldir."core/modules/supplier_invoice/pdf/pdf_".$modele.".modules.php",0);
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
    
    	if ($module->write_file($facture,$langs) > 0)
    	{
    		header("Location: ".DOL_URL_ROOT."/document.php?modulepart=facture_fournisseur&file=SPECIMEN.pdf");
    		return;
    	}
    	else
    	{
    		$mesg='<font class="error">'.$module->error.'</font>';
    		dol_syslog($module->error, LOG_ERR);
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
    $sql = "INSERT INTO ".MAIN_DB_PREFIX."document_model (nom, type, entity, libelle, description)";
    $sql.= " VALUES ('".$db->escape($value)."','".$type."',".$conf->entity.", ";
    $sql.= ($_GET["label"]?"'".$db->escape($_GET["label"])."'":'null').", ";
    $sql.= (! empty($_GET["scandir"])?"'".$db->escape($_GET["scandir"])."'":"null");
    $sql.= ")";
    $res=$db->query($sql);
    if ($res)
    {

    }
    //	else dol_print_error($db);
}

if ($action == 'del')
{
    $sql = "DELETE FROM ".MAIN_DB_PREFIX."document_model";
    $sql.= " WHERE nom = '".$value."'";
    $sql.= " AND type = '".$type."'";
    $sql.= " AND entity = ".$conf->entity;
    $db->query($sql);
    if ($res)
    {

    }
    //    else dol_print_error($db);
}

if ($action == 'setdoc')
{
    $db->begin();

    if ($_GET["type"] == 'order_supplier' && dolibarr_set_const($db, "COMMANDE_SUPPLIER_ADDON_PDF",$value,'chaine',0,'',$conf->entity))
    {
        $conf->global->COMMANDE_SUPPLIER_ADDON_PDF = $value;
    }

    if ($_GET["type"] == 'invoice_supplier' && dolibarr_set_const($db, "INVOICE_SUPPLIER_ADDON_PDF",$value,'chaine',0,'',$conf->entity))
    {
        $conf->global->INVOICE_SUPPLIER_ADDON_PDF = $value;
    }

    // On active le modele
    $sql_del = "DELETE FROM ".MAIN_DB_PREFIX."document_model";
    $sql_del.= " WHERE nom = '".$db->escape($value)."'";
    $sql_del.= " AND type = '".$type."'";
    $sql_del.= " AND entity = ".$conf->entity;
    $result1=$db->query($sql_del);

    $sql = "INSERT INTO ".MAIN_DB_PREFIX."document_model (nom, type, entity, libelle, description)";
    $sql.= " VALUES ('".$db->escape($value)."', '".$type."', ".$conf->entity.", ";
    $sql.= ($_GET["label"]?"'".$db->escape($_GET["label"])."'":'null').", ";
    $sql.= (! empty($_GET["scandir"])?"'".$db->escape($_GET["scandir"])."'":"null");
    $sql.= ")";
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

    dolibarr_set_const($db, "COMMANDE_SUPPLIER_ADDON",$value,'chaine',0,'',$conf->entity);
}

if ($action == 'addcat')
{
    $fourn = new Fournisseur($db);
    $fourn->CreateCategory($user,$_POST["cat"]);
}

if ($action == 'set_SUPPLIER_INVOICE_FREE_TEXT')
{
    $free = GETPOST("SUPPLIER_INVOICE_FREE_TEXT");
    $res = dolibarr_set_const($db, "SUPPLIER_INVOICE_FREE_TEXT",$free,'chaine',0,'',$conf->entity);

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

$dirmodels=array_merge(array('/'),(array)$conf->modules_parts['models']);

llxHeader();

$form=new Form($db);

$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';
print_fiche_titre($langs->trans("SuppliersSetup"),$linkback,'setup');

print "<br>";


// Supplier order numbering module

print_titre($langs->trans("OrdersNumberingModules"));

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td width="100">'.$langs->trans("Name").'</td>';
print '<td>'.$langs->trans("Description").'</td>';
print '<td>'.$langs->trans("Example").'</td>';
print '<td align="center" width="60">'.$langs->trans("Status").'</td>';
print '<td align="center" width="16">'.$langs->trans("Info").'</td>';
print "</tr>\n";

clearstatcache();

foreach ($dirmodels as $reldir)
{
	$dir = dol_buildpath($reldir."core/modules/supplier_order/");

    if (is_dir($dir))
    {
        $handle = opendir($dir);
        if (is_resource($handle))
        {
            $var=true;

            while (($file = readdir($handle))!==false)
            {
                if (substr($file, 0, 25) == 'mod_commande_fournisseur_' && substr($file, dol_strlen($file)-3, 3) == 'php')
                {
                    $file = substr($file, 0, dol_strlen($file)-4);

                    require_once($dir.$file.".php");

                    $module = new $file;

                    if ($module->isEnabled())
                    {
                        // Show modules according to features level
                        if ($module->version == 'development'  && $conf->global->MAIN_FEATURES_LEVEL < 2) continue;
                        if ($module->version == 'experimental' && $conf->global->MAIN_FEATURES_LEVEL < 1) continue;

                        $var=!$var;
                        print '<tr '.$bc[$var].'><td>'.$module->nom."</td><td>\n";
                        print $module->info();
                        print '</td>';

                        // Show example of numbering module
                        print '<td nowrap="nowrap">';
                        $tmp=$module->getExample();
                        if (preg_match('/^Error/',$tmp)) {
                            $langs->load("errors"); print '<div class="error">'.$langs->trans($tmp).'</div>';
                        }
                        elseif ($tmp=='NotConfigured') print $langs->trans($tmp);
                        else print $tmp;
                        print '</td>'."\n";

                        print '<td align="center">';
                        if ($conf->global->COMMANDE_SUPPLIER_ADDON == "$file")
                        {
                            print img_picto($langs->trans("Activated"),'switch_on');
                        }
                        else
                        {
                            print '<a href="'.$_SERVER["PHP_SELF"].'?action=setmod&amp;value='.$file.'" alt="'.$langs->trans("Default").'">'.img_picto($langs->trans("Disabled"),'switch_off').'</a>';
                        }
                        print '</td>';

                        $commande=new CommandeFournisseur($db);
                        $commande->initAsSpecimen();

                        // Info
                        $htmltooltip='';
                        $htmltooltip.=''.$langs->trans("Version").': <b>'.$module->getVersion().'</b><br>';
                        $facture->type=0;
                        $nextval=$module->getNextValue($mysoc,$commande);
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
 * Modeles documents for supplier orders
 */

print_titre($langs->trans("OrdersModelModule"));

// Defini tableau def de modele
$def = array();

$sql = "SELECT nom";
$sql.= " FROM ".MAIN_DB_PREFIX."document_model";
$sql.= " WHERE type = 'order_supplier'";
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

print '<table class="noborder" width="100%">'."\n";
print '<tr class="liste_titre">'."\n";
print '<td width="100">'.$langs->trans("Name").'</td>'."\n";
print '<td>'.$langs->trans("Description").'</td>'."\n";
print '<td align="center" width="60">'.$langs->trans("Status").'</td>'."\n";
print '<td align="center" width="60">'.$langs->trans("Default").'</td>'."\n";
print '<td align="center" width="40" colspan="2">'.$langs->trans("Info").'</td>';
print '</tr>'."\n";

clearstatcache();

foreach ($dirmodels as $reldir)
{
	$dir = dol_buildpath($reldir."core/modules/supplier_order/pdf/");

    if (is_dir($dir))
    {
        $var=true;

        $handle=opendir($dir);


        if (is_resource($handle))
        {
            while (($file = readdir($handle))!==false)
            {
                if (preg_match('/\.modules\.php$/i',$file) && substr($file,0,4) == 'pdf_')
                {
                    $name = substr($file, 4, dol_strlen($file) -16);
                    $classname = substr($file, 0, dol_strlen($file) -12);

                    $var=!$var;
                    print "<tr ".$bc[$var].">\n";
                    print "<td>".$name."</td>\n";
                    print "<td>\n";
                    require_once($dir.$file);
                    $module = new $classname($db,$specimenthirdparty);
                    print $module->description;
                    print "</td>\n";

                    // Active
                    if (in_array($name, $def))
                    {
                        print '<td align="center">'."\n";
                        if ($conf->global->COMMANDE_SUPPLIER_ADDON_PDF != "$name")
                        {
                            print '<a href="'.$_SERVER["PHP_SELF"].'?action=del&amp;value='.$name.'&amp;scandir='.$module->scandir.'&amp;label='.urlencode($module->name).'&amp;type=order_supplier">';
                            print img_picto($langs->trans("Enabled"),'switch_on');
                            print '</a>';
                        }
                        else
                        {
                            print img_picto($langs->trans("Enabled"),'switch_on');
                        }
                        print "</td>";
                    }
                    else
                    {
                        print '<td align="center">'."\n";
                        print '<a href="'.$_SERVER["PHP_SELF"].'?action=set&amp;value='.$name.'&amp;scandir='.$module->scandir.'&amp;label='.urlencode($module->name).'&amp;type=order_supplier">'.img_picto($langs->trans("Disabled"),'switch_off').'</a>';
                        print "</td>";
                    }

                    // Defaut
                    print '<td align="center">';
                    if ($conf->global->COMMANDE_SUPPLIER_ADDON_PDF == "$name")
                    {
                        print img_picto($langs->trans("Default"),'on');
                    }
                    else
                    {
                        print '<a href="'.$_SERVER["PHP_SELF"].'?action=setdoc&amp;value='.$name.'&amp;scandir='.$module->scandir.'&amp;label='.urlencode($module->name).'&amp;type=order_supplier"" alt="'.$langs->trans("Default").'">'.img_picto($langs->trans("Disabled"),'off').'</a>';
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
                    print '<td align="center">';
                    print $form->textwithpicto('',$htmltooltip,1,0);
                    print '</td>';
                    print '<td align="center">';
                    print '<a href="'.$_SERVER["PHP_SELF"].'?action=specimen&amp;module='.$name.'">'.img_object($langs->trans("Preview"),'order').'</a>';
                    print '</td>';

                    print "</tr>\n";
                }
            }

            closedir($handle);
        }
    }
}

print '</table><br/>';

/*
 * Modeles documents for supplier invoices
 */

print_titre($langs->trans("BillsPDFModules"));

// Defini tableau def de modele
$def = array();

$sql = "SELECT nom";
$sql.= " FROM ".MAIN_DB_PREFIX."document_model";
$sql.= " WHERE type = 'invoice_supplier'";
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


print '<table class="noborder" width="100%">'."\n";
print '<tr class="liste_titre">'."\n";
print '<td width="100">'.$langs->trans("Name").'</td>'."\n";
print '<td>'.$langs->trans("Description").'</td>'."\n";
print '<td align="center" width="60">'.$langs->trans("Status").'</td>'."\n";
print '<td align="center" width="60">'.$langs->trans("Default").'</td>'."\n";
print '<td align="center" width="40" colspan="2">'.$langs->trans("Info").'</td>';
print '</tr>'."\n";

clearstatcache();

foreach ($dirmodels as $reldir)
{
	$dir = dol_buildpath($reldir."core/modules/supplier_invoice/pdf/");

    if (is_dir($dir))
    {
        $var=true;

        $handle=opendir($dir);

        if (is_resource($handle))
        {
            while (($file = readdir($handle)) !== false)
            {
                if (preg_match('/\.modules\.php$/i',$file) && substr($file,0,4) == 'pdf_')
                {
                    $name = substr($file, 4, dol_strlen($file) -16);
                    $classname = substr($file, 0, dol_strlen($file) -12);

                    $var=!$var;
                    print "<tr ".$bc[$var].">\n";
                    print "<td>".$name."</td>\n";
                    print "<td>";
                    require_once($dir.$file);
                    $module = new $classname($db,$specimenthirdparty);
                    print $module->description;
                    print "</td>\n";

                    // Active
                    if (in_array($name, $def))
                    {
                        print "<td align=\"center\">\n";
                        if ($conf->global->INVOICE_SUPPLIER_ADDON_PDF != "$name")
                        {
                            print '<a href="'.$_SERVER["PHP_SELF"].'?action=del&amp;value='.$name.'&amp;scandir='.$module->scandir.'&amp;label='.urlencode($module->name).'&amp;type=invoice_supplier">';
                            print img_picto($langs->trans("Enabled"),'switch_on');
                            print '</a>';
                        }
                        else
                        {
                            print img_picto($langs->trans("Enabled"),'switch_on');
                        }
                        print "</td>";
                    }
                    else
                    {
                        print "<td align=\"center\">\n";
                        print '<a href="'.$_SERVER["PHP_SELF"].'?action=set&amp;value='.$name.'&amp;scandir='.$module->scandir.'&amp;label='.urlencode($module->name).'&amp;type=invoice_supplier">'.img_picto($langs->trans("Disabled"),'switch_off').'</a>';
                        print "</td>";
                    }

                    // Defaut
                    print "<td align=\"center\">";
                    if ($conf->global->INVOICE_SUPPLIER_ADDON_PDF == "$name")
                    {
                        print img_picto($langs->trans("Default"),'on');
                    }
                    else
                    {
                        print '<a href="'.$_SERVER["PHP_SELF"].'?action=setdoc&amp;value='.$name.'&amp;scandir='.$module->scandir.'&amp;label='.urlencode($module->name).'&amp;type=invoice_supplier" alt="'.$langs->trans("Default").'">'.img_picto($langs->trans("Disabled"),'off').'</a>';
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
                    print '<td align="center">';
                    print $form->textwithpicto('',$htmltooltip,1,0);
                    print '</td>';
                    print '<td align="center">';
                    print '<a href="'.$_SERVER["PHP_SELF"].'?action=specimenfacture&amp;module='.$name.'">'.img_object($langs->trans("Preview"),'bill').'</a>';
                    print '</td>';

                    print "</tr>\n";
                }
            }
            closedir($handle);
        }
    }
}

print '</table><br/>';

print_titre($langs->trans("OtherOptions"));
print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Parameter").'</td>';
print '<td align="center" width="60">'.$langs->trans("Value").'</td>';
print '<td width="80">&nbsp;</td>';
print "</tr>\n";

print '<form action="'.$_SERVER["PHP_SELF"].'" method="post">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="set_SUPPLIER_INVOICE_FREE_TEXT">';
print '<tr '.$bc[$var].'><td colspan="2">';
print $langs->trans("FreeLegalTextOnInvoices").' ('.$langs->trans("AddCRIfTooLong").')<br>';
print '<textarea name="SUPPLIER_INVOICE_FREE_TEXT" class="flat" cols="120">'.$conf->global->SUPPLIER_INVOICE_FREE_TEXT.'</textarea>';
print '</td><td align="right">';
print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
print "</td></tr>\n";
print '</form>';

dol_htmloutput_mesg($mesg);

$db->close();
llxFooter();
?>
