<?php
/* Copyright (C) 2003-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2007 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005      Eric Seigne          <eric.seigne@ryxeo.com>
 * Copyright (C) 2005-2007 Regis Houssin        <regis@dolibarr.fr>
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
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 *
 * $Id$
 */

/**
        \file       htdocs/admin/facture.php
		\ingroup    facture
		\brief      Page d'administration/configuration du module Facture
		\version    $Revision$
*/

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT.'/facture.class.php');

$langs->load("admin");
$langs->load("companies");
$langs->load("bills");
$langs->load("other");

if (!$user->admin)
  accessforbidden();

$typeconst=array('yesno','texte','chaine');
$dir = DOL_DOCUMENT_ROOT."/includes/modules/facture/";


/*
 * Actions
 */
if ($_GET["action"] == 'specimen')
{
	$modele=$_GET["module"];

	$facture = new Facture($db);
	$facture->initAsSpecimen();

	// Charge le modele
	$dir = DOL_DOCUMENT_ROOT . "/includes/modules/facture/";
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
	}
	else
	{
		$mesg='<div class="error">'.$langs->trans("ErrorModuleNotFound").'</div>';
	}
}

if ($_GET["action"] == 'set')
{
	$type='invoice';
    $sql = "INSERT INTO ".MAIN_DB_PREFIX."document_model (nom, type) VALUES ('".$_GET["value"]."','".$type."')";
    if ($db->query($sql))
    {

    }
}

if ($_GET["action"] == 'del')
{
    $type='invoice';
    $sql = "DELETE FROM ".MAIN_DB_PREFIX."document_model";
    $sql .= "  WHERE nom = '".$_GET["value"]."' AND type = '".$type."'";
    if ($db->query($sql))
    {

    }
}

if ($_GET["action"] == 'setdoc')
{
	$db->begin();

    if (dolibarr_set_const($db, "FACTURE_ADDON_PDF",$_GET["value"]))
    {
        $conf->global->FACTURE_ADDON_PDF = $_GET["value"];
    }

    // On active le modele
    $type='invoice';
    $sql_del = "DELETE FROM ".MAIN_DB_PREFIX."document_model";
    $sql_del.= " WHERE nom = '".$_GET["value"]."' AND type = '".$type."'";
    $result1=$db->query($sql_del);
    $sql = "INSERT INTO ".MAIN_DB_PREFIX."document_model (nom,type) VALUES ('".$_GET["value"]."','".$type."')";
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

if ($_GET["action"] == 'setmod')
{
    // \todo Verifier si module numerotation choisi peut etre activé
    // par appel methode canBeActivated

	dolibarr_set_const($db, "FACTURE_ADDON",$_GET["value"]);
}

if ($_POST["action"] == 'setribchq')
{
    dolibarr_set_const($db, "FACTURE_RIB_NUMBER",$_POST["rib"]);
    dolibarr_set_const($db, "FACTURE_CHQ_NUMBER",$_POST["chq"]);
}

if ($_POST["action"] == 'setforcedate')
{
    dolibarr_set_const($db, "FAC_FORCE_DATE_VALIDATION",$_POST["forcedate"]);
}

if ($_POST["action"] == 'set_disable_repeatable')
{
    dolibarr_set_const($db, "FACTURE_DISABLE_RECUR",$_POST["disable_repeatable"]);
}

if ($_POST["action"] == 'set_enable_editdelete')
{
    dolibarr_set_const($db, "FACTURE_ENABLE_EDITDELETE",$_POST["enable_editdelete"]);
}

if ($_POST["action"] == 'set_use_bill_contact_as_recipient')
{
    dolibarr_set_const($db, "FACTURE_USE_BILL_CONTACT_AS_RECIPIENT",$_POST["use_bill_contact_as_recipient"]);
}

if ($_POST["action"] == 'update' || $_POST["action"] == 'add')
{
	if (! dolibarr_set_const($db, $_POST["constname"],$_POST["constvalue"],$typeconst[$_POST["consttype"]],0,isset($_POST["constnote"])?$_POST["constnote"]:''));
	{
	   dolibarr_print_error($db);
	}
}

if ($_GET["action"] == 'delete')
{
  if (! dolibarr_del_const($db, $_GET["rowid"]));
  {
    dolibarr_print_error($db);
  }
}

// défini les constantes du modèle pluton
if ($_POST["action"] == 'updateMatrice') dolibarr_set_const($db, "FACTURE_NUM_MATRICE",$_POST["matrice"]);
if ($_POST["action"] == 'updatePrefixFacture') dolibarr_set_const($db, "FACTURE_NUM_PREFIX",$_POST["prefixfacture"]);
if ($_POST["action"] == 'updatePrefixAvoir') dolibarr_set_const($db, "AVOIR_NUM_PREFIX",$_POST["prefixavoir"]);
if ($_POST["action"] == 'setOffsetInvoice') dolibarr_set_const($db, "FACTURE_NUM_DELTA",$_POST["offsetinvoice"]);
if ($_POST["action"] == 'setOffsetCreditNote') dolibarr_set_const($db, "AVOIR_NUM_DELTA",$_POST["offsetcreditnote"]);
if ($_POST["action"] == 'setFiscalMonth') dolibarr_set_const($db, "SOCIETE_FISCAL_MONTH_START",$_POST["fiscalmonth"]);
if ($_POST["action"] == 'setNumRestart') dolibarr_set_const($db, "FACTURE_NUM_RESTART_BEGIN_YEAR",$_POST["numrestart"]);
if ($_POST["action"] == 'setNumWithInvoice') dolibarr_set_const($db, "AVOIR_NUM_WITH_INVOICE",$_POST["numwithinvoice"]);


/*
 * Affiche page
 */

llxHeader("","");

$html=new Form($db);


print_fiche_titre($langs->trans("BillsNumberingModule"),'','setup');
print '<br>';

$h = 0;

$head[$h][0] = DOL_URL_ROOT."/admin/facture.php";
$head[$h][1] = $langs->trans("Invoices");
$hselected=$h;
$h++;

dolibarr_fiche_head($head, $hselected, $langs->trans("ModuleSetup"));

/*
 *  Module numérotation
 */
print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Name").'</td>';
print '<td>'.$langs->trans("Description").'</td>';
print '<td nowrap>'.$langs->trans("Example").'</td>';
print '<td align="center" width="60">'.$langs->trans("Default").'</td>';
print '<td align="center" width="16">'.$langs->trans("Infos").'</td>';
print '</tr>'."\n";

clearstatcache();

$handle=opendir($dir);

$var=true;
while (($file = readdir($handle))!==false)
{
    if (is_dir($dir.$file) && substr($file, 0, 1) <> '.' && substr($file, 0, 3) <> 'CVS')
    {
        $filebis = $file."/".$file.".modules.php";
		if (is_readable($dir.$filebis))
		{
	        // Chargement de la classe de numérotation
	        require_once($dir.$filebis);
	        $classname = "mod_facture_".$file;
	        $module = new $classname($db);

			// Show modules according to features level
		    if ($module->version == 'development'  && $conf->global->MAIN_FEATURES_LEVEL < 2) continue;
		    if ($module->version == 'experimental' && $conf->global->MAIN_FEATURES_LEVEL < 1) continue;
			
	        $var = !$var;
	        print '<tr '.$bc[$var].'><td width="100">';
	        echo "$file";
	        print "</td><td>\n";

	        print $module->info();

	        print '</td>';

	        // Affiche example
	        print '<td nowrap="nowrap">'.$module->getExample().'</td>';

	        print '<td align="center">';
	        if ($conf->global->FACTURE_ADDON == "$file")
	        {
	            print img_tick($langs->trans("Activated"));
	        }
	        else
	        {
	            print '<a href="'.$_SERVER["PHP_SELF"].'?action=setmod&amp;value='.$file.'" alt="'.$langs->trans("Default").'">'.$langs->trans("Default").'</a>';
	        }
	        print '</td>';

			$facture=new Facture($db);

			// Info
			$htmltooltip='';
			$htmltooltip.='<b>'.$langs->trans("Version").'</b>: '.$module->getVersion().'<br>';
			$facture->type=0;
	        $nextval=$module->getNextValue($mysoc,$facture);
	        if ($nextval != $langs->trans("NotAvailable"))
	        {
	            $htmltooltip.='<b>'.$langs->trans("NextValueForInvoices").'</b>: '.$nextval.'<br>';
	        }
			$facture->type=2;
	        $nextval=$module->getNextValue($mysoc,$facture);
	        if ($nextval != $langs->trans("NotAvailable"))
	        {
	            $htmltooltip.='<b>'.$langs->trans("NextValueForCreditNotes").'</b>: '.$nextval;
	        }
	    	print '<td align="center">';
	    	print $html->textwithhelp('',$htmltooltip,1,0);
	    	print '</td>';

	        print "</tr>\n";
		}
    }
}
closedir($handle);

print '</table>';


/*
 *  Modeles de documents
 */
print '<br>';
print_titre($langs->trans("BillsPDFModules"));

// Defini tableau def de modele invoice
$def = array();
$sql = "SELECT nom";
$sql.= " FROM ".MAIN_DB_PREFIX."document_model";
$sql.= " WHERE type = 'invoice'";
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
	dolibarr_print_error($db);
}

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Name").'</td>';
print '<td>'.$langs->trans("Description").'</td>';
print '<td align="center" width="60">'.$langs->trans("Activated").'</td>';
print '<td align="center" width="60">'.$langs->trans("Default").'</td>';
print '<td align="center" width="32" colspan="2">'.$langs->trans("Infos").'</td>';
print "</tr>\n";

clearstatcache();

$handle=opendir($dir);

$var=True;
while (($file = readdir($handle))!==false)
{
    if (eregi('\.modules\.php$',$file) && substr($file,0,4) == 'pdf_')
    {
        $var = !$var;
        $name = substr($file, 4, strlen($file) -16);
        $classname = substr($file, 0, strlen($file) -12);

        print '<tr '.$bc[$var].'><td width="100">';
        echo "$name";
        print "</td><td>\n";

        require_once($dir.$file);
        $module = new $classname($db);
        print $module->description;
        print '</td>';

		// Activé
		if (in_array($name, $def))
		{
			print "<td align=\"center\">\n";
			if ($conf->global->FACTURE_ADDON_PDF != "$name")
			{
				print '<a href="'.$_SERVER["PHP_SELF"].'?action=del&amp;value='.$name.'">';
				print img_tick($langs->trans("Disable"));
				print '</a>';
			}
			else
			{
				print img_tick($langs->trans("Enabled"));
			}
			print "</td>";
		}
		else
		{
			print "<td align=\"center\">\n";
			print '<a href="'.$_SERVER["PHP_SELF"].'?action=set&amp;value='.$name.'">'.$langs->trans("Activate").'</a>';
			print "</td>";
		}

		// Defaut
		print "<td align=\"center\">";
		if ($conf->global->FACTURE_ADDON_PDF == "$name")
		{
			print img_tick($langs->trans("Default"));
		}
		else
		{
			print '<a href="'.$_SERVER["PHP_SELF"].'?action=setdoc&amp;value='.$name.'" alt="'.$langs->trans("Default").'">'.$langs->trans("Default").'</a>';
		}
		print '</td>';

		// Info
    	$htmltooltip =    '<b>'.$langs->trans("Name").'</b>: '.$module->name;
    	$htmltooltip.='<br><b>'.$langs->trans("Type").'</b>: '.($module->type?$module->type:$langs->trans("Unknown"));
    	$htmltooltip.='<br><b>'.$langs->trans("Height").'/'.$langs->trans("Width").'</b>: '.$module->page_hauteur.'/'.$module->page_largeur;
    	$htmltooltip.='<br><br>'.$langs->trans("FeaturesSupported").':';
    	$htmltooltip.='<br><b>'.$langs->trans("Logo").'</b>: '.yn($module->option_logo);
    	$htmltooltip.='<br><b>'.$langs->trans("PaymentMode").'</b>: '.yn($module->option_modereg);
    	$htmltooltip.='<br><b>'.$langs->trans("PaymentConditions").'</b>: '.yn($module->option_condreg);
    	$htmltooltip.='<br><b>'.$langs->trans("MultiLanguage").'</b>: '.yn($module->option_multilang);
    	print '<td align="center">';
    	print $html->textwithhelp('',$htmltooltip,1,0);
    	print '</td>';
    	print '<td align="center">';
    	print '<a href="'.$_SERVER["PHP_SELF"].'?action=specimen&module='.$name.'">'.img_object($langs->trans("Preview"),'bill').'</a>';
    	print '</td>';

        print "</tr>\n";
    }
}
closedir($handle);

print '</table>';


/*
 *  Modes de règlement
 *
 */
print '<br>';
print_titre($langs->trans("SuggestedPaymentModesIfNotDefinedInInvoice"));

print '<form action="'.$_SERVER["PHP_SELF"].'" method="post">';

print '<table class="noborder" width="100%">';
$var=True;

print '<input type="hidden" name="action" value="setribchq">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("PaymentMode").'</td>';
print '<td align="right"><input type="submit" class="button" value="'.$langs->trans("Modify").'"></td>';
print "</tr>\n";
$var=!$var;
print '<tr '.$bc[$var].'>';
print "<td>Proposer paiement par RIB sur le compte</td>";
print "<td>";
if ($conf->banque->enabled)
{
    $sql = "SELECT rowid, label";
    $sql.= " FROM ".MAIN_DB_PREFIX."bank_account";
    $sql.= " where clos = 0";
    $sql.= " and courant = 1";
    $resql=$db->query($sql);
    if ($resql)
    {
      $num = $db->num_rows($resql);
      $i = 0;
      if ($num > 0) {
        print "<select name=\"rib\">";
        print '<option value="0">'.$langs->trans("DoNotSuggestPaymentMode").'</option>';
        while ($i < $num)
          {
    	$var=!$var;
    	$row = $db->fetch_row($resql);

        print '<option value="'.$row[0].'"';
        print $conf->global->FACTURE_RIB_NUMBER == $row[0] ? ' selected="true"':'';
        print '>'.$row[1].'</option>';

        $i++;
          }
        print "</select>";
        } else {
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
print "<td>Proposer paiement par chèque à l'ordre et adresse de</td>";
print "<td>";
print '<select name="chq">';
print '<option value="0">'.$langs->trans("DoNotSuggestPaymentMode").'</option>';
print '<option value="-1"'.($conf->global->FACTURE_CHQ_NUMBER?' selected="true"':'').'>'.$langs->trans("MenuCompanySetup").' ('.($mysoc->nom?$mysoc->nom:$langs->trans("NotDefined")).')</option>';

$sql = "SELECT rowid, label";
$sql.= " FROM ".MAIN_DB_PREFIX."bank_account";
$sql.= " where clos = 0";
$sql.= " and courant = 1";
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
    print $conf->global->FACTURE_CHQ_NUMBER == $row[0] ? ' selected="true"':'';
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
print '<form action="'.$_SERVER["PHP_SELF"].'" method="post">';
print '<input type="hidden" name="action" value="setforcedate">';
print '<tr '.$bc[$var].'><td>';
print $langs->trans("ForceInvoiceDate");
print '</td><td width="60" align="center">';
print $html->selectyesno("forcedate",$conf->global->FAC_FORCE_DATE_VALIDATION,1);
print '</td><td align="right">';
print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
print "</td></tr>\n";
print '</form>';

// Active facture récurrentes
$var=! $var;
print '<form action="'.$_SERVER["PHP_SELF"].'" method="post">';
print '<input type="hidden" name="action" value="set_disable_repeatable">';
print '<tr '.$bc[$var].'><td>';
print $langs->trans("DisableRepeatable");
print '</td><td width="60" align="center">';
print $html->selectyesno("disable_repeatable",$conf->global->FACTURE_DISABLE_RECUR,1);
print '</td><td align="right">';
print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
print "</td></tr>\n";
print '</form>';

// Active la possibilité d'éditer/supprimer une facture validée sans paiement
$var=! $var;
print '<form action="'.$_SERVER["PHP_SELF"].'" method="post">';
print '<input type="hidden" name="action" value="set_enable_editdelete">';
print '<tr '.$bc[$var].'><td>';
print $langs->trans("EnableEditDeleteValidInvoice");
print '</td><td width="60" align="center">';
print $html->selectyesno("enable_editdelete",$conf->global->FACTURE_ENABLE_EDITDELETE,1);
print '</td><td align="right">';
print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
print "</td></tr>\n";
print '</form>';

$var=! $var;
print '<form action="'.$_SERVER["PHP_SELF"].'" method="post">';
print '<input type="hidden" name="action" value="set_use_bill_contact_as_recipient">';
print '<tr '.$bc[$var].'><td>';
print $langs->trans("UsBillingContactAsIncoiveRecipientIfExist");
print '</td><td width="60" align="center">';
print $html->selectyesno("use_bill_contact_as_recipient",$conf->global->FACTURE_USE_BILL_CONTACT_AS_RECIPIENT,1);
print '</td><td align="right">';
print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
print "</td></tr>\n";
print '</form>';

print '</table>';


/*
 *  Repertoire
 */
print '<br>';
print_titre($langs->trans("PathToDocuments"));

print "<table class=\"noborder\" width=\"100%\">\n";
print "<tr class=\"liste_titre\">\n";
print "  <td>".$langs->trans("Name")."</td>\n";
print "  <td>".$langs->trans("Value")."</td>\n";
print "</tr>\n";
print "<tr ".$bc[false].">\n  <td width=\"140\">".$langs->trans("PathDirectory")."</td>\n  <td>".$conf->facture->dir_output."</td>\n</tr>\n";
print "</table>\n";




$db->close();

llxFooter('$Date$ - $Revision$');
?>
