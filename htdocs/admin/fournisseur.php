<?php
/* Copyright (C) 2003-2007 Rodolphe Quiedeville    <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2008 Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009 Regis Houssin           <regis@dolibarr.fr>
 * Copyright (C) 2004      Sebastien Di Cintio     <sdicintio@ressource-toi.org>
 * Copyright (C) 2004      Benoit Mortier          <benoit.mortier@opensides.be>
 * Copyright (C) 2010      Juanjo Menent           <jmenent@2byte.es>
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
 */

/**
 *  \file       htdocs/admin/fournisseur.php
 *  \ingroup    fournisseur
 *  \brief      Page d'administration-configuration du module Fournisseur
 *  \version    $Id$
 */

require("../main.inc.php");
require_once(DOL_DOCUMENT_ROOT."/lib/admin.lib.php");
require_once(DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.class.php');
require_once(DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.commande.class.php');
require_once(DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.facture.class.php');

$langs->load("admin");
$langs->load("bills");
$langs->load("other");
$langs->load("orders");

if (!$user->admin)
accessforbidden();

$specimenthirdparty=new Societe($db);
$specimenthirdparty->initAsSpecimen();


/*
 * Actions
 */

if ($_POST["action"] == 'updateMask')
{
	$maskconstorder=$_POST['maskconstorder'];
	$maskorder=$_POST['maskorder'];
	if ($maskconstorder)  dolibarr_set_const($db,$maskconstorder,$maskorder,'chaine',0,'',$conf->entity);
}

if ($_GET["action"] == 'specimen')  // For orders
{
	$modele=$_GET["module"];

	$commande = new CommandeFournisseur($db);
	$commande->initAsSpecimen();
    $commande->thirdparty=$specimenthirdparty;

	// Charge le modele
	$dir = DOL_DOCUMENT_ROOT . "/includes/modules/supplier_order/pdf/";
	$file = "pdf_".$modele.".modules.php";
	if (file_exists($dir.$file))
	{
		$classname = "pdf_".$modele;
		require_once($dir.$file);

		$obj = new $classname($db,$commande);

		if ($obj->write_file($commande,$langs) > 0)
		{
			header("Location: ".DOL_URL_ROOT."/document.php?modulepart=commande_fournisseur&file=SPECIMEN.pdf");
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

if ($_GET["action"] == 'specimenfacture')   // For invoices
{
	$modele=$_GET["module"];

	$facture = new FactureFournisseur($db);
	$facture->initAsSpecimen();
    $facture->thirdparty=$specimenthirdparty;

	// Charge le modele
	$dir = DOL_DOCUMENT_ROOT . "/includes/modules/supplier_invoice/pdf/";
	$file = "pdf_".$modele.".modules.php";
	if (file_exists($dir.$file))
	{
		$classname = "pdf_".$modele;
		require_once($dir.$file);

		$obj = new $classname($db,$facture);

		if ($obj->write_file($facture,$langs) > 0)
		{
			header("Location: ".DOL_URL_ROOT."/document.php?modulepart=facture_fournisseur&file=SPECIMEN.pdf");
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

if ($_GET["action"] == 'set')
{
	$sql = "INSERT INTO ".MAIN_DB_PREFIX."document_model (nom, type, entity) VALUES ('".$_GET["value"]."','".$_GET["type"]."',".$conf->entity.")";
	if ($db->query($sql))
	{

	}
}

if ($_GET["action"] == 'del')
{
	$sql = "DELETE FROM ".MAIN_DB_PREFIX."document_model";
	$sql.= " WHERE nom = '".$_GET["value"]."'";
	$sql.= " AND type = '".$_GET["type"]."'";
	$sql.= " AND entity = ".$conf->entity;
	if ($db->query($sql))
	{

	}
}

if ($_GET["action"] == 'setdoc')
{
	$db->begin();

	if ($_GET["type"] == 'order_supplier' && dolibarr_set_const($db, "COMMANDE_SUPPLIER_ADDON_PDF",$_GET["value"],'chaine',0,'',$conf->entity))
	{
		$conf->global->COMMANDE_SUPPLIER_ADDON_PDF = $_GET["value"];
	}

	if ($_GET["type"] == 'invoice_supplier' && dolibarr_set_const($db, "INVOICE_SUPPLIER_ADDON_PDF",$_GET["value"],'chaine',0,'',$conf->entity))
	{
		$conf->global->INVOICE_SUPPLIER_ADDON_PDF = $_GET["value"];
	}

	// On active le modele
	$sql_del = "DELETE FROM ".MAIN_DB_PREFIX."document_model";
	$sql_del.= " WHERE nom = '".$_GET["value"]."'";
	$sql_del.= " AND type = '".$_GET["type"]."'";
	$sql_del.= " AND entity = ".$conf->entity;
	$result1=$db->query($sql_del);
	$sql = "INSERT INTO ".MAIN_DB_PREFIX."document_model (nom,type,entity) VALUES ('".$_GET["value"]."','".$_GET["type"]."',".$conf->entity.")";
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
	// TODO Verifier si module numerotation choisi peut etre active
	// par appel methode canBeActivated

	dolibarr_set_const($db, "COMMANDE_SUPPLIER_ADDON",$_GET["value"],'chaine',0,'',$conf->entity);
}

if ($_POST["action"] == 'addcat')
{
	$fourn = new Fournisseur($db);
	$fourn->CreateCategory($user,$_POST["cat"]);
}

if ($_POST["action"] == 'set_SUPPLIER_INVOICE_FREE_TEXT')
{
	dolibarr_set_const($db, "SUPPLIER_INVOICE_FREE_TEXT",$_POST["SUPPLIER_INVOICE_FREE_TEXT"],'chaine',0,'',$conf->entity);
}
/*
 * View
 */

$html=new Form($db);

llxHeader();

$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';
print_fiche_titre($langs->trans("SuppliersSetup"),$linkback,'setup');

print "<br>";


// Supplier order numbering module

$dir = DOL_DOCUMENT_ROOT."/includes/modules/supplier_order/";

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

$handle = opendir($dir);
if ($handle)
{
	$var=true;

	while (($file = readdir($handle))!==false)
	{
		if (substr($file, 0, 25) == 'mod_commande_fournisseur_' && substr($file, dol_strlen($file)-3, 3) == 'php')
		{
			$file = substr($file, 0, dol_strlen($file)-4);

			require_once(DOL_DOCUMENT_ROOT ."/includes/modules/supplier_order/".$file.".php");

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

				// Examples
				print '<td nowrap="nowrap">'.$module->getExample()."</td>\n";

				print '<td align="center">';
				if ($conf->global->COMMANDE_SUPPLIER_ADDON == "$file")
				{
					print img_picto($langs->trans("Activated"),'on');
				}
				else
				{
					print '<a href="'.$_SERVER["PHP_SELF"].'?action=setmod&amp;value='.$file.'" alt="'.$langs->trans("Default").'">'.img_picto($langs->trans("Disabled"),'off').'</a>';
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
				print $html->textwithpicto('',$htmltooltip,1,0);
				print '</td>';

				print '</tr>';
			}
		}
	}
	closedir($handle);
}

print '</table><br>';




/*
 * Modeles documents for supplier orders
 */

$dir = DOL_DOCUMENT_ROOT.'/includes/modules/supplier_order/pdf/';

print_titre($langs->trans("OrdersModelModule"));

// Defini tableau def de modele
$type='order_supplier';
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

print '<table class="noborder" width="100%">'."\n";
print '<tr class="liste_titre">'."\n";
print '<td width="100">'.$langs->trans("Name").'</td>'."\n";
print '<td>'.$langs->trans("Description").'</td>'."\n";
print '<td align="center" width="60">'.$langs->trans("Status").'</td>'."\n";
print '<td align="center" width="60">'.$langs->trans("Default").'</td>'."\n";
print '<td align="center" width="32" colspan="2">'.$langs->trans("Info").'</td>';
print '</tr>'."\n";

clearstatcache();

$handle=opendir($dir);

$var=true;
while (($file = readdir($handle))!==false)
{
	if (preg_match('/\.modules\.php$/i',$file) && substr($file,0,4) == 'pdf_')
	{
		$name = substr($file, 4, dol_strlen($file) -16);
		$classname = substr($file, 0, dol_strlen($file) -12);

		$var=!$var;
		print "<tr ".$bc[$var].">\n  <td>$name";
		print "</td>\n  <td>\n";
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
				print '<a href="'.$_SERVER["PHP_SELF"].'?action=del&amp;value='.$name.'&amp;type=supplier_order">';
				print img_picto($langs->trans("Enabled"),'on');
				print '</a>';
			}
			else
			{
				print img_picto($langs->trans("Enabled"),'on');
			}
			print "</td>";
		}
		else
		{
			print '<td align="center">'."\n";
			print '<a href="'.$_SERVER["PHP_SELF"].'?action=set&amp;value='.$name.'&amp;type=supplier_order">'.img_picto($langs->trans("Disabled"),'off').'</a>';
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
	  		print '<a href="'.$_SERVER["PHP_SELF"].'?action=setdoc&amp;value='.$name.'&amp;type=supplier_order"" alt="'.$langs->trans("Default").'">'.img_picto($langs->trans("Disabled"),'on').'</a>';
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
		print $html->textwithpicto('',$htmltooltip,1,0);
		print '</td>';
		print '<td align="center">';
		print '<a href="'.$_SERVER["PHP_SELF"].'?action=specimen&amp;module='.$name.'">'.img_object($langs->trans("Preview"),'order').'</a>';
		print '</td>';

		print "</tr>\n";
	}
}

closedir($handle);

print '</table><br/>';

/*
 * Modeles documents for supplier invoices
 */

$dir = DOL_DOCUMENT_ROOT.'/includes/modules/supplier_invoice/pdf/';

print_titre($langs->trans("BillsPDFModules"));

// Defini tableau def de modele
$type='invoice_supplier';
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

print '<table class="noborder" width="100%">'."\n";
print '<tr class="liste_titre">'."\n";
print '<td width="100">'.$langs->trans("Name").'</td>'."\n";
print '<td>'.$langs->trans("Description").'</td>'."\n";
print '<td align="center" width="60">'.$langs->trans("Status").'</td>'."\n";
print '<td align="center" width="60">'.$langs->trans("Default").'</td>'."\n";
print '<td align="center" width="32" colspan="2">'.$langs->trans("Info").'</td>';
print '</tr>'."\n";

clearstatcache();

$handle=opendir($dir);
$var=true;
while (($file = readdir($handle)) !== false)
{
	if (preg_match('/\.modules\.php$/i',$file) && substr($file,0,4) == 'pdf_')
	{
		$name = substr($file, 4, dol_strlen($file) -16);
		$classname = substr($file, 0, dol_strlen($file) -12);

		$var=!$var;
		print "<tr ".$bc[$var].">\n  <td>$name";
		print "</td>\n  <td>\n";
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
				print '<a href="'.$_SERVER["PHP_SELF"].'?action=del&amp;value='.$name.'&amp;type=supplier_invoice">';
				print img_picto($langs->trans("Enabled"),'on');
				print '</a>';
			}
			else
			{
				print img_picto($langs->trans("Enabled"),'on');
			}
			print "</td>";
		}
		else
		{
			print "<td align=\"center\">\n";
			print '<a href="'.$_SERVER["PHP_SELF"].'?action=set&amp;value='.$name.'&amp;type=supplier_invoice">'.img_picto($langs->trans("Disabled"),'off').'</a>';
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
	  		print '<a href="'.$_SERVER["PHP_SELF"].'?action=setdoc&amp;value='.$name.'&amp;type=supplier_invoice" alt="'.$langs->trans("Default").'">'.img_picto($langs->trans("Disabled"),'on').'</a>';
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
		print $html->textwithpicto('',$htmltooltip,1,0);
		print '</td>';
		print '<td align="center">';
		print '<a href="'.$_SERVER["PHP_SELF"].'?action=specimenfacture&amp;module='.$name.'">'.img_object($langs->trans("Preview"),'bill').'</a>';
		print '</td>';

		print "</tr>\n";
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

closedir($handle);

llxFooter('$Date$ - $Revision$');
?>
