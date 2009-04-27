<?php
/* Copyright (C) 2003-2007 Rodolphe Quiedeville    <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2008 Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009 Regis Houssin           <regis@dolibarr.fr>
 * Copyright (C) 2004      Sebastien Di Cintio     <sdicintio@ressource-toi.org>
 * Copyright (C) 2004      Benoit Mortier          <benoit.mortier@opensides.be>
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
 \file       htdocs/admin/fournisseur.php
 \ingroup    fournisseur
 \brief      Page d'administration-configuration du module Fournisseur
 \version    $Id$
 */

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/lib/admin.lib.php");
require_once(DOL_DOCUMENT_ROOT.'/fourn/fournisseur.class.php');
require_once(DOL_DOCUMENT_ROOT.'/fourn/fournisseur.commande.class.php');

$langs->load("admin");
$langs->load("bills");
$langs->load("other");
$langs->load("orders");

if (!$user->admin)
accessforbidden();


/*
 * Actions
 */

if ($_POST["action"] == 'updateMask')
{
	$maskconstorder=$_POST['maskconstorder'];
	$maskorder=$_POST['maskorder'];
	if ($maskconstorder)  dolibarr_set_const($db,$maskconstorder,$maskorder,'chaine',0,'',$conf->entity);
}

if ($_GET["action"] == 'specimen')
{
	$modele=$_GET["module"];

	$commande = new CommandeFournisseur($db);
	$commande->initAsSpecimen();

	// Charge le modele
	$dir = DOL_DOCUMENT_ROOT . "/includes/modules/supplier_order/pdf/";
	$file = "pdf_".$modele.".modules.php";
	if (file_exists($dir.$file))
	{
		$classname = "pdf_".$modele;
		require_once($dir.$file);

		$obj = new $classname($db);

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

if ($_GET["action"] == 'set')
{
	$type='supplier_order';
	$sql = "INSERT INTO ".MAIN_DB_PREFIX."document_model (nom, type, entity) VALUES ('".$_GET["value"]."','".$type."',".$conf->entity.")";
	if ($db->query($sql))
	{

	}
}

if ($_GET["action"] == 'del')
{
	$type='supplier_order';
	$sql = "DELETE FROM ".MAIN_DB_PREFIX."document_model";
	$sql.= " WHERE nom = '".$_GET["value"];
	$sql.= " AND type = '".$type."'";
	$sql.= " AND entity = ".$conf->entity;
	if ($db->query($sql))
	{

	}
}

if ($_GET["action"] == 'setdoc')
{
	$db->begin();

	if (dolibarr_set_const($db, "COMMANDE_SUPPLIER_ADDON_PDF",$_GET["value"],'chaine',0,'',$conf->entity))
	{
		$conf->global->COMMANDE_SUPPLIER_ADDON_PDF = $_GET["value"];
	}

	// On active le modele
	$type='supplier_order';
	$sql_del = "DELETE FROM ".MAIN_DB_PREFIX."document_model";
	$sql_del.= " WHERE nom = '".$_GET["value"];
	$sql_del.= " AND type = '".$type."'";
	$sql_del.= " AND entity = ".$conf->entity;
	$result1=$db->query($sql_del);
	$sql = "INSERT INTO ".MAIN_DB_PREFIX."document_model (nom,type,entity) VALUES ('".$_GET["value"]."','".$type."',".$conf->entity.")";
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
	// \todo Verifier si module numerotation choisi peut etre active
	// par appel methode canBeActivated

	dolibarr_set_const($db, "COMMANDE_SUPPLIER_ADDON",$_GET["value"],'chaine',0,'',$conf->entity);
}

if ($_POST["action"] == 'addcat')
{
	$fourn = new Fournisseur($db);
	$fourn->CreateCategory($user,$_POST["cat"]);
}

// defini les constantes du modele orchidee
if ($_POST["action"] == 'updateMatrice') dolibarr_set_const($db, "COMMANDE_FOURNISSEUR_NUM_MATRICE",$_POST["matrice"],'chaine',0,'',$conf->entity);
if ($_POST["action"] == 'updatePrefixCommande') dolibarr_set_const($db, "COMMANDE_FOURNISSEUR_NUM_PREFIX",$_POST["prefixcommande"],'chaine',0,'',$conf->entity);
if ($_POST["action"] == 'setOffset') dolibarr_set_const($db, "COMMANDE_FOURNISSEUR_NUM_DELTA",$_POST["offset"],'chaine',0,'',$conf->entity);
if ($_POST["action"] == 'setNumRestart') dolibarr_set_const($db, "COMMANDE_FOURNISSEUR_NUM_RESTART_BEGIN_YEAR",$_POST["numrestart"],'chaine',0,'',$conf->entity);


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
print '<td align="center" width="60">'.$langs->trans("Activated").'</td>';
print '<td align="center" width="16">'.$langs->trans("Info").'</td>';
print "</tr>\n";

clearstatcache();

$handle = opendir($dir);
if ($handle)
{
	$var=true;

	while (($file = readdir($handle))!==false)
	{
		if (substr($file, 0, 25) == 'mod_commande_fournisseur_' && substr($file, strlen($file)-3, 3) == 'php')
		{
			$file = substr($file, 0, strlen($file)-4);

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
					print img_tick($langs->trans("Activated"));
				}
				else
				{
					print '<a href="'.$_SERVER["PHP_SELF"].'?action=setmod&amp;value='.$file.'" alt="'.$langs->trans("Default").'">'.$langs->trans("Activate").'</a>';
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
				print $html->textwithhelp('',$htmltooltip,1,0);
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
$type='supplier_order';
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
print '<td align="center" width="32" colspan="2">'.$langs->trans("Info").'</td>';
print "</tr>\n";

clearstatcache();

$handle=opendir($dir);

$var=true;
while (($file = readdir($handle))!==false)
{
	if (eregi('\.modules\.php$',$file) && substr($file,0,4) == 'pdf_')
	{
		$name = substr($file, 4, strlen($file) -16);
		$classname = substr($file, 0, strlen($file) -12);

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
	  if ($conf->global->COMMANDE_SUPPLIER_ADDON_PDF != "$name")
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
		if ($conf->global->COMMANDE_SUPPLIER_ADDON_PDF == "$name")
		{
	  print img_tick($langs->trans("Default"));
		}
		else
		{
	  print '<a href="'.$_SERVER["PHP_SELF"].'?action=setdoc&amp;value='.$name.'" alt="'.$langs->trans("Default").'">'.$langs->trans("Default").'</a>';
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
		print $html->textwithhelp('',$htmltooltip,1,0);
		print '</td>';
		print '<td align="center">';
		print '<a href="'.$_SERVER["PHP_SELF"].'?action=specimen&amp;module='.$name.'">'.img_object($langs->trans("Preview"),'order').'</a>';
		print '</td>';

		print "</tr>\n";
	}
}
closedir($handle);

print '</table><br/>';

llxFooter('$Date$ - $Revision$');
?>
