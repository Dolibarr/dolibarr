<?php
/* Copyright (C) 2003-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Sebastien Di Cintio  <sdicintio@ressource-toi.org>
 * Copyright (C) 2004      Benoit Mortier       <benoit.mortier@opensides.be>
 * Copyright (C) 2004      Eric Seigne          <eric.seigne@ryxeo.com>
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
 */

/**
	    \file       htdocs/admin/propale.php
		\ingroup    propale
		\brief      Page d'administration/configuration du module Propale
		\version    $Id$
*/

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/lib/admin.lib.php");
require_once(DOL_DOCUMENT_ROOT."/propal.class.php");

$langs->load("admin");
$langs->load("bills");
$langs->load("propal");
$langs->load("other");

if (!$user->admin)
  accessforbidden();


/*
 * Actions
 */

if ($_POST["action"] == 'updateMask')
{
	$maskconstpropal=$_POST['maskconstpropal'];
	$maskpropal=$_POST['maskpropal'];
	if ($maskconstpropal)  dolibarr_set_const($db,$maskconstpropal,$maskpropal);
}

if ($_GET["action"] == 'specimen')
{
	$modele=$_GET["module"];

	$propal = new Propal($db);
	$propal->initAsSpecimen();

	// Charge le modele
	$dir = DOL_DOCUMENT_ROOT . "/includes/modules/propale/";
	$file = "pdf_propale_".$modele.".modules.php";
	if (file_exists($dir.$file))
	{
		$classname = "pdf_propale_".$modele;
		require_once($dir.$file);

		$module = new $classname($db);

		if ($module->write_file($propal) > 0)
		{
			header("Location: ".DOL_URL_ROOT."/document.php?modulepart=propal&file=SPECIMEN.pdf");
			return;
		}
	}
}

if ($_POST["action"] == 'setnbprod')
{
    dolibarr_set_const($db, "PROPALE_NEW_FORM_NB_PRODUCT",$_POST["value"]);
    Header("Location: propale.php");
    exit;
}

if ($_POST["action"] == 'setdefaultduration')
{
    dolibarr_set_const($db, "PROPALE_VALIDITY_DURATION",$_POST["value"]);
    Header("Location: propale.php");
    exit;
}

if ($_POST["action"] == 'setaddshippingdate')
{
    dolibarr_set_const($db, "PROPALE_ADD_SHIPPING_DATE",$_POST["value"]);
    Header("Location: propale.php");
    exit;
}

if ($_POST["action"] == 'setadddeliveryaddress')
{
    dolibarr_set_const($db, "PROPALE_ADD_DELIVERY_ADDRESS",$_POST["value"]);
    Header("Location: propale.php");
    exit;
}

if ($_POST["action"] == 'setuseoptionline')
{
    dolibarr_set_const($db, "PROPALE_USE_OPTION_LINE",$_POST["value"]);
    Header("Location: propale.php");
    exit;
}

if ($_POST["action"] == 'setclassifiedinvoiced')
{
    dolibarr_set_const($db, "PROPALE_CLASSIFIED_INVOICED_WITH_ORDER",$_POST["value"]);
    Header("Location: propale.php");
    exit;
}

if ($_POST["action"] == 'setusecustomercontactasrecipient')
{
    dolibarr_set_const($db, "PROPALE_USE_CUSTOMER_CONTACT_AS_RECIPIENT",$_POST["value"]);
    Header("Location: propale.php");
    exit;
}




if ($_GET["action"] == 'set')
{
	$type='propal';
    $sql = "INSERT INTO ".MAIN_DB_PREFIX."document_model (nom, type) VALUES ('".$_GET["value"]."','".$type."')";
    if ($db->query($sql))
    {

    }
}
if ($_GET["action"] == 'del')
{
    $type='propal';
    $sql = "DELETE FROM ".MAIN_DB_PREFIX."document_model";
    $sql .= "  WHERE nom = '".$_GET["value"]."' AND type = '".$type."'";
    if ($db->query($sql))
    {

    }
}

if ($_GET["action"] == 'setdoc')
{
	$db->begin();

    if (dolibarr_set_const($db, "PROPALE_ADDON_PDF",$_GET["value"]))
    {
        $conf->global->PROPALE_ADDON_PDF = $_GET["value"];
    }

    // On active le modele
    $type='propal';
    $sql_del = "DELETE FROM ".MAIN_DB_PREFIX."document_model";
    $sql_del .= "  WHERE nom = '".$_GET["value"]."' AND type = '".$type."'";
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
    // \todo Verifier si module numerotation choisi peut etre activ�
    // par appel methode canBeActivated

	dolibarr_set_const($db, "PROPALE_ADDON",$_GET["value"]);
}

// d�fini les constantes du mod�le saphir
if ($_POST["action"] == 'updateMatrice') dolibarr_set_const($db, "PROPALE_NUM_MATRICE",$_POST["matrice"]);
if ($_POST["action"] == 'updatePrefix') dolibarr_set_const($db, "PROPALE_NUM_PREFIX",$_POST["prefix"]);
if ($_POST["action"] == 'setOffset') dolibarr_set_const($db, "PROPALE_NUM_DELTA",$_POST["offset"]);
if ($_POST["action"] == 'setFiscalMonth') dolibarr_set_const($db, "SOCIETE_FISCAL_MONTH_START",$_POST["fiscalmonth"]);
if ($_POST["action"] == 'setNumRestart') dolibarr_set_const($db, "PROPALE_NUM_RESTART_BEGIN_YEAR",$_POST["numrestart"]);


/*
 * Affiche page
 */

llxHeader('',$langs->trans("PropalSetup"));

$dir = "../includes/modules/propale/";
$html=new Form($db);


$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';
print_fiche_titre($langs->trans("PropalSetup"),$linkback,'setup');

/*
 *  Module num�rotation
 */
print "<br>";
print_titre($langs->trans("ProposalsNumberingModules"));

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Name")."</td>\n";
print '<td>'.$langs->trans("Description")."</td>\n";
print '<td nowrap>'.$langs->trans("Example")."</td>\n";
print '<td align="center" width="60">'.$langs->trans("Activated").'</td>';
print '<td align="center" width="16">'.$langs->trans("Infos").'</td>';
print '</tr>'."\n";

clearstatcache();

$handle = opendir($dir);
if ($handle)
{
    $var=true;
    while (($file = readdir($handle))!==false)
    {
        if (substr($file, 0, 12) == 'mod_propale_' && substr($file, strlen($file)-3, 3) == 'php')
        {
            $file = substr($file, 0, strlen($file)-4);

            require_once(DOL_DOCUMENT_ROOT ."/includes/modules/propale/".$file.".php");

            $module = new $file;

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
            if ($conf->global->PROPALE_ADDON == "$file")
            {
                print img_tick($langs->trans("Activated"));
            }
            else
            {
                print '<a href="'.$_SERVER["PHP_SELF"].'?action=setmod&amp;value='.$file.'" alt="'.$langs->trans("Default").'">'.$langs->trans("Activate").'</a>';
            }
            print '</td>';
            
            $propale=new Propal($db);
			     
			// Info
			$htmltooltip='';
			$htmltooltip.='<b>'.$langs->trans("Version").'</b>: '.$module->getVersion().'<br>';
			$facture->type=0;
	        $nextval=$module->getNextValue($mysoc,$propale);
			if ("$nextval" != $langs->trans("NotAvailable"))	// Keep " on nextval
			{
				$htmltooltip.='<b>'.$langs->trans("NextValue").'</b>: ';
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

            print "</tr>\n";
        }
    }
    closedir($handle);
}
print "</table><br>\n";


/*
 * Modeles de documents
 */

print_titre($langs->trans("ProposalsPDFModules"));

// Defini tableau def de modele propal
$def = array();
$sql = "SELECT nom";
$sql.= " FROM ".MAIN_DB_PREFIX."document_model";
$sql.= " WHERE type = 'propal'";
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

$dir = "../includes/modules/propale/";

print "<table class=\"noborder\" width=\"100%\">\n";
print "<tr class=\"liste_titre\">\n";
print "  <td width=\"140\">".$langs->trans("Name")."</td>\n";
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
	if (substr($file, strlen($file) -12) == '.modules.php' && substr($file,0,12) == 'pdf_propale_')
	{
		$name = substr($file, 12, strlen($file) - 24);
		$classname = substr($file, 0, strlen($file) -12);

		$var=!$var;
		print "<tr ".$bc[$var].">\n  <td>";
		print "$name";
		print "</td>\n  <td>\n";
		require_once($dir.$file);
		$module = new $classname($db);
		print $module->description;
		print '</td>';

		// Activ�
		if (in_array($name, $def))
		{
			print "<td align=\"center\">\n";
			if ($conf->global->PROPALE_ADDON_PDF != "$name")
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
		if ($conf->global->PROPALE_ADDON_PDF == "$name")
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
    	print '<a href="'.$_SERVER["PHP_SELF"].'?action=specimen&module='.$name.'">'.img_object($langs->trans("Preview"),'propal').'</a>';
    	print '</td>';

        print "</tr>\n";
	}
}
closedir($handle);

print '</table>';
print '<br>';


/*
 * Autres options
 *
 */
print_titre($langs->trans("OtherOptions"));

$var=true;
print "<table class=\"noborder\" width=\"100%\">";
print "<tr class=\"liste_titre\">";
print "<td>".$langs->trans("Parameter")."</td>\n";
print '<td width="60" align="center">'.$langs->trans("Value")."</td>\n";
print "<td>&nbsp;</td>\n";
print "</tr>";

$var=!$var;
print "<form method=\"post\" action=\"".$_SERVER["PHP_SELF"]."\">";
print "<input type=\"hidden\" name=\"action\" value=\"setdefaultduration\">";
print "<tr ".$bc[$var].">";
print '<td>'.$langs->trans("DefaultProposalDurationValidity").'</td>';
print '<td width="60" align="center">'."<input size=\"3\" class=\"flat\" type=\"text\" name=\"value\" value=\"".$conf->global->PROPALE_VALIDITY_DURATION."\"></td>";
print '<td align="right"><input type="submit" class="button" value="'.$langs->trans("Modify").'"></td>';
print '</tr>';
print '</form>';

$var=!$var;
print "<form method=\"post\" action=\"".$_SERVER["PHP_SELF"]."\">";
print "<input type=\"hidden\" name=\"action\" value=\"setaddshippingdate\">";
print "<tr ".$bc[$var].">";
print '<td>'.$langs->trans("AddShippingDateAbility").'</td>';
print '<td width="60" align="center">'.$html->selectyesno('value',$conf->global->PROPALE_ADD_SHIPPING_DATE,1).'</td>';
print '<td align="right"><input type="submit" class="button" value="'.$langs->trans("Modify").'"></td>';
print '</tr>';
print '</form>';

$var=!$var;
print "<form method=\"post\" action=\"".$_SERVER["PHP_SELF"]."\">";
print "<input type=\"hidden\" name=\"action\" value=\"setadddeliveryaddress\">";
print "<tr ".$bc[$var].">";
print '<td>'.$langs->trans("AddDeliveryAddressAbility").'</td>';
print '<td width="60" align="center">'.$html->selectyesno('value',$conf->global->PROPALE_ADD_DELIVERY_ADDRESS,1).'</td>';
print '<td align="right"><input type="submit" class="button" value="'.$langs->trans("Modify").'"></td>';
print '</tr>';
print '</form>';

$var=! $var;
print '<form action="'.$_SERVER["PHP_SELF"].'" method="post">';
print '<input type="hidden" name="action" value="setusecustomercontactasrecipient">';
print '<tr '.$bc[$var].'><td>';
print $langs->trans("UseCustomerContactAsPropalRecipientIfExist");
print '</td><td width="60" align="center">';
print $html->selectyesno("value",$conf->global->PROPALE_USE_CUSTOMER_CONTACT_AS_RECIPIENT,1);
print '</td><td align="right">';
print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
print "</td></tr>\n";
print '</form>';

$var=! $var;
print '<form action="'.$_SERVER["PHP_SELF"].'" method="post">';
print '<input type="hidden" name="action" value="setuseoptionline">';
print '<tr '.$bc[$var].'><td>';
print $langs->trans("UseOptionLineIfNoQuantity");
print '</td><td width="60" align="center">';
print $html->selectyesno("value",$conf->global->PROPALE_USE_OPTION_LINE,1);
print '</td><td align="right">';
print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
print "</td></tr>\n";
print '</form>';

if ($conf->commande->enabled)
{
	$var=!$var;
	print "<form method=\"post\" action=\"".$_SERVER["PHP_SELF"]."\">";
	print "<input type=\"hidden\" name=\"action\" value=\"setclassifiedinvoiced\">";
	print "<tr ".$bc[$var].">";
	print '<td>'.$langs->trans("ClassifiedInvoicedWithOrder").'</td>';
	print '<td width="60" align="center">';
	print $html->selectyesno('value',$conf->global->PROPALE_CLASSIFIED_INVOICED_WITH_ORDER,1);
	print "</td>";
	print '<td align="right"><input type="submit" class="button" value="'.$langs->trans("Modify").'"></td>';
	print '</tr>';
	print '</form>';
}

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
print "<tr ".$bc[false].">\n  <td width=\"140\">".$langs->trans("PathDirectory")."</td>\n  <td>".$conf->propal->dir_output."</td>\n</tr>\n";
print "</table>\n<br>";



$db->close();

llxFooter('$Date$ - $Revision$');
?>
