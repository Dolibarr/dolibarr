<?php
/* Copyright (C) 2003-2006 Rodolphe Quiedeville	        <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2009 Laurent Destailleur          <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Sebastien Di Cintio          <sdicintio@ressource-toi.org>
 * Copyright (C) 2004      Benoit Mortier               <benoit.mortier@opensides.be>
 * Copyright (C) 2004      Andre Cianfarani             <acianfa@free.fr>
 * Copyright (C) 2005-2009 Regis Houssin                <regis.houssin@dolibarr.fr>
 * Copyright (C) 2008 	   Raphael Bertrand (Resultic)  <raphael.bertrand@resultic.fr>
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
 \file       htdocs/admin/commande.php
 \ingroup    commande
 \brief      Page d'administration-configuration du module Commande
 \version    $Id$
 */

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/lib/admin.lib.php");
require_once(DOL_DOCUMENT_ROOT.'/commande/commande.class.php');

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

	$commande = new Commande($db);
	$commande->initAsSpecimen();

	// Charge le modele
	$dir = DOL_DOCUMENT_ROOT . "/includes/modules/commande/";
	$file = "pdf_".$modele.".modules.php";
	if (file_exists($dir.$file))
	{
		$classname = "pdf_".$modele;
		require_once($dir.$file);

		$obj = new $classname($db);

		if ($obj->write_file($commande,$langs) > 0)
		{
			header("Location: ".DOL_URL_ROOT."/document.php?modulepart=commande&file=SPECIMEN.pdf");
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
	$type='order';
	$sql = "INSERT INTO ".MAIN_DB_PREFIX."document_model (nom, type, entity) VALUES ('".$_GET["value"]."','".$type."',".$conf->entity.")";
	if ($db->query($sql))
	{

	}
}

if ($_GET["action"] == 'del')
{
	$type='order';
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

	if (dolibarr_set_const($db, "COMMANDE_ADDON_PDF",$_GET["value"],'chaine',0,'',$conf->entity))
	{
		$conf->global->COMMANDE_ADDON_PDF = $_GET["value"];
	}

	// On active le modele
	$type='order';
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

	dolibarr_set_const($db, "COMMANDE_ADDON",$_GET["value"],'chaine',0,'',$conf->entity);
}

if ($_POST["action"] == 'set_COMMANDE_DRAFT_WATERMARK')
{
	dolibarr_set_const($db, "COMMANDE_DRAFT_WATERMARK",trim($_POST["COMMANDE_DRAFT_WATERMARK"]),'chaine',0,'',$conf->entity);
}

if ($_POST["action"] == 'set_COMMANDE_FREE_TEXT')
{
	dolibarr_set_const($db, "COMMANDE_FREE_TEXT",trim($_POST["COMMANDE_FREE_TEXT"]),'chaine',0,'',$conf->entity);
}

if ($_POST["action"] == 'setvalidorder')
{
	dolibarr_set_const($db, "COMMANDE_VALID_AFTER_CLOSE_PROPAL",$_POST["validorder"],'chaine',0,'',$conf->entity);
}

if ($_POST["action"] == 'deliverycostline')
{
	dolibarr_set_const($db, "COMMANDE_ADD_DELIVERY_COST_LINE",$_POST["addline"],'chaine',0,'',$conf->entity);
}

if ($_POST["action"] == 'set_use_customer_contact_as_recipient')
{
	dolibarr_set_const($db, "COMMANDE_USE_CUSTOMER_CONTACT_AS_RECIPIENT",$_POST["use_customer_contact_as_recipient"],'chaine',0,'',$conf->entity);
}


/*
 * View
 */

llxHeader();

$dir = "../includes/modules/commande/";
$html=new Form($db);

$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';
print_fiche_titre($langs->trans("OrdersSetup"),$linkback,'setup');

print "<br>";



/*
 * Numbering module
 */

print_titre($langs->trans("OrdersNumberingModules"));

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td width="100">'.$langs->trans("Name").'</td>';
print '<td>'.$langs->trans("Description").'</td>';
print '<td>'.$langs->trans("Example").'</td>';
print '<td align="center" width="60">'.$langs->trans("Activated").'</td>';
print '<td align="center" width="16">'.$langs->trans("Infos").'</td>';
print "</tr>\n";

clearstatcache();

$dir = "../includes/modules/commande/";
$handle = opendir($dir);
if ($handle)
{
	$var=true;

	while (($file = readdir($handle))!==false)
	{
		if (substr($file, 0, 13) == 'mod_commande_' && substr($file, strlen($file)-3, 3) == 'php')
		{
			$file = substr($file, 0, strlen($file)-4);

			require_once(DOL_DOCUMENT_ROOT ."/includes/modules/commande/".$file.".php");

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

				// Examples
				print '<td nowrap="nowrap">'.$module->getExample()."</td>\n";

				print '<td align="center">';
				if ($conf->global->COMMANDE_ADDON == "$file")
				{
					print img_tick($langs->trans("Activated"));
				}
				else
				{
					print '<a href="'.$_SERVER["PHP_SELF"].'?action=setmod&amp;value='.$file.'" alt="'.$langs->trans("Default").'">'.$langs->trans("Activate").'</a>';
				}
				print '</td>';

				$commande=new Commande($db);
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
 * Modeles de documents
 */
print_titre($langs->trans("OrdersModelModule"));

// Defini tableau def de modele
$type='order';
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

$dir = "../includes/modules/commande/";

print "<table class=\"noborder\" width=\"100%\">\n";
print "<tr class=\"liste_titre\">\n";
print '  <td width="100">'.$langs->trans("Name")."</td>\n";
print "  <td>".$langs->trans("Description")."</td>\n";
print '<td align="center" width="60">'.$langs->trans("Activated")."</td>\n";
print '<td align="center" width="60">'.$langs->trans("Default")."</td>\n";
print '<td align="center" width="32" colspan="2">'.$langs->trans("Infos").'</td>';
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
		print "<tr ".$bc[$var].">\n  <td>";
		print "$name";
		print "</td>\n  <td>\n";
		require_once($dir.$file);
		$module = new $classname($db);
		print $module->description;
		print "</td>\n";

		// Activated
		if (in_array($name, $def))
		{
			print "<td align=\"center\">\n";
			if ($conf->global->COMMANDE_ADDON_PDF != "$name")
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
		if ($conf->global->COMMANDE_ADDON_PDF == "$name")
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
		$htmltooltip.='<br>'.$langs->trans("Height").'/'.$langs->trans("Width").': '.$module->page_hauteur.'/'.$module->page_largeur;
		$htmltooltip.='<br><br><u>'.$langs->trans("FeaturesSupported").':</u>';
		$htmltooltip.='<br>'.$langs->trans("Logo").': '.yn($module->option_logo,1,1);
		$htmltooltip.='<br>'.$langs->trans("PaymentMode").': '.yn($module->option_modereg,1,1);
		$htmltooltip.='<br>'.$langs->trans("PaymentConditions").': '.yn($module->option_condreg,1,1);
		$htmltooltip.='<br>'.$langs->trans("MultiLanguage").': '.yn($module->option_multilang,1,1);
		//$htmltooltip.='<br>'.$langs->trans("Escompte").': '.yn($module->option_escompte,1,1);
		//$htmltooltip.='<br>'.$langs->trans("CreditNote").': '.yn($module->option_credit_note,1,1);
		$htmltooltip.='<br>'.$langs->trans("WatermarkOnDraftOrders").': '.yn($module->option_draft_watermark,1,1);

		print '<td align="center">';
		print $html->textwithpicto('',$htmltooltip,1,0);
		print '</td>';
		print '<td align="center">';
		print '<a href="'.$_SERVER["PHP_SELF"].'?action=specimen&module='.$name.'">'.img_object($langs->trans("Preview"),'order').'</a>';
		print '</td>';

		print "</tr>\n";
	}
}
closedir($handle);

print '</table>';

//Autres Options
print "<br>";
print_titre($langs->trans("OtherOptions"));

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Parameter").'</td>';
print '<td align="center" width="60">'.$langs->trans("Value").'</td>';
print "<td>&nbsp;</td>\n";
print "</tr>\n";
$var=true;

// Valider la commande apres cloture de la propale
// permet de na pas passer par l'option commande provisoire
/*
$var=! $var;
print '<form action="'.$_SERVER["PHP_SELF"].'" method="post">';
print '<input type="hidden" name="action" value="setvalidorder">';
print '<tr '.$bc[$var].'>';
print '<td>'.$langs->trans("ValidOrderAfterPropalClosed").'</td>';
print '<td width="60" align="center">'.$html->selectyesno("validorder",$conf->global->COMMANDE_VALID_AFTER_CLOSE_PROPAL,1).'</td>';
print '<td align="right"><input type="submit" class="button" value="'.$langs->trans("Modify").'"></td>';
print '</tr>';
print '</form>';
*/

// Ajouter une ligne de frais port indiquant le poids de la commande
/*
$var=! $var;
print '<form action="'.$_SERVER["PHP_SELF"].'" method="post">';
print '<input type="hidden" name="action" value="deliverycostline">';
print '<tr '.$bc[$var].'>';
print '<td>'.$langs->trans("AddDeliveryCostLine").'</td>';
print '<td width="60" align="center">'.$html->selectyesno("addline",$conf->global->COMMANDE_ADD_DELIVERY_COST_LINE,1).'</td>';
print '<td align="right"><input type="submit" class="button" value="'.$langs->trans("Modify").'"></td>';
print '</tr>';
print '</form>';
*/

// Utiliser le contact de la commande dans le document
$var=! $var;
print '<form action="'.$_SERVER["PHP_SELF"].'" method="post">';
print '<input type="hidden" name="action" value="set_use_customer_contact_as_recipient">';
print '<tr '.$bc[$var].'>';
print '<td>'.$langs->trans("UseCustomerContactAsOrderRecipientIfExist").'</td>';
print '<td width="60" align="center">'.$html->selectyesno("use_customer_contact_as_recipient",$conf->global->COMMANDE_USE_CUSTOMER_CONTACT_AS_RECIPIENT,1).'</td>';
print '<td align="right"><input type="submit" class="button" value="'.$langs->trans("Modify").'"></td>';
print '</tr>';
print '</form>';

$var=! $var;
print '<form action="'.$_SERVER["PHP_SELF"].'" method="post">';
print '<input type="hidden" name="action" value="set_COMMANDE_FREE_TEXT">';
print '<tr '.$bc[$var].'><td colspan="2">';
print $langs->trans("FreeLegalTextOnOrders").'<br>';
print '<textarea name="COMMANDE_FREE_TEXT" class="flat" cols="100">'.$conf->global->COMMANDE_FREE_TEXT.'</textarea>';
print '</td><td align="right">';
print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
print "</td></tr>\n";
print '</form>';

//Use draft Watermark
$var=!$var;
print "<form method=\"post\" action=\"".$_SERVER["PHP_SELF"]."\">";
print "<input type=\"hidden\" name=\"action\" value=\"set_COMMANDE_DRAFT_WATERMARK\">";
print '<tr '.$bc[$var].'><td colspan="2">';
print $langs->trans("WatermarkOnDraftOrders").'<br>';
print '<input size="50" class="flat" type="text" name="COMMANDE_DRAFT_WATERMARK" value="'.$conf->global->COMMANDE_DRAFT_WATERMARK.'">';
print '</td><td align="right">';
print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
print "</td></tr>\n";
print '</form>';

print '</table>';

print '<br>';

llxFooter('$Date$ - $Revision$');
?>
