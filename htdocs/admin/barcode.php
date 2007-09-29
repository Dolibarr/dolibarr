<?php
/* Copyright (C) 2003-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2005 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2007 Regis Houssin        <regis.houssin@cap-networks.com>
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
 * $Source$
 */

/**	
        \file       htdocs/admin/barcode.php
		\ingroup    barcode
		\brief      Page d'administration/configuration du module Code barre
		\version    $Revision$
*/

require("./pre.inc.php");

$langs->load("admin");

if (!$user->admin)
  accessforbidden();

if ($_POST["action"] == 'setcoder')
{
	$sqlp = "UPDATE ".MAIN_DB_PREFIX."c_barcode_type";
  $sqlp.= " SET coder = " . $_POST["coder"];
  $sqlp.= " WHERE rowid = ". $_POST["code_id"];
  $resql=$db->query($sqlp);
}
else if ($_POST["action"] == 'setgenbarcodelocation')
{
	dolibarr_set_const($db, "GENBARCODE_LOCATION",$_POST["genbarcodelocation"]);
  Header("Location: barcode.php");
  exit;
}
else if ($_POST["action"] == 'setproductusebarcode')
{
  dolibarr_set_const($db, "PRODUIT_USE_BARCODE",$_POST["value"]);
  Header("Location: barcode.php");
  exit;
}

$html = new Form($db);

llxHeader('',$langs->trans("BarcodeSetup"),'BarcodeConfiguration');

print_fiche_titre($langs->trans("BarcodeSetup"),'','setup');

/*
 *  CHOIX ENCODAGE
 */

print '<br>';
print_titre($langs->trans("BarcodeEncodeModule"));

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Name").'</td>';
print '<td>'.$langs->trans("Description").'</td>';
print '<td width="200">'.$langs->trans("Example").'</td>';
print '<td align="center" width="60">'.$langs->trans("Default").'</td>';
print "</tr>\n";

$sql = "SELECT rowid, code, libelle, coder, example";
$sql .= " FROM ".MAIN_DB_PREFIX."c_barcode_type";
$resql=$db->query($sql);
if ($resql)
{
	$num = $db->num_rows($resql);
	$i = 0;
	$var=true;
	
	while ($i <	$num)
	{
		$obj = $db->fetch_object($resql);

    print '<tr '.$bc[$var].'><td width="100">';
    print $obj->libelle;
    print "</td><td>\n";
    print $langs->trans('BarcodeDesc'.$obj->code);  
    //print "L'EAN se compose de 8 caractères, 7 chiffres plus une clé de contrôle.<br>";
    //print "L'utilisation des symbologies EAN8 impose la souscription et l'abonnement auprès d'organisme tel que GENCOD.<br>";
    //print "Codes numériques utilisés exclusivement à l'identification des produits susceptibles d'être vendus au grand public.";
    print '</td>';

    // Affiche exemple
    print '<td align="center"><img src="'.dol_genbarcode($obj->example,$obj->code,$obj->coder).'"></td>';
    
    print '<td align="center">';
    print $html->setBarcodeEncoder($obj->coder,$obj->rowid,'form'.$i);
	  print "</td></tr>\n";
	  $var=!$var;
	  $i++;
	}
}
print "</table>\n";

print "<br>";

/*
 * Autres options
 *
 */
print_titre($langs->trans("OtherOptions"));

$var=true;
print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Parameter").'</td>';
print '<td width="60" align="center">'.$langs->trans("Value").'</td>';
print '<td>&nbsp;</td>';
print '</tr>';

// Chemin du binaire genbarcode sous linux
if (!isset($_ENV['windir']) && !file_exists($_ENV['windir']))
{
	$var=!$var;
	print '<form method="post" action="'.$_SERVER["PHP_SELF"].'">';
	print '<input type="hidden" name="action" value="setgenbarcodelocation">';
	print '<tr '.$bc[$var].'>';
	print '<td>'.$langs->trans("GenbarcodeLocation").'</td>';
	print '<td width="60" align="center">';
	print '<input type="text" size="40" name="genbarcodelocation" value="'.$conf->global->GENBARCODE_LOCATION.'">';
	print '</td>';
	print '<td width="60" align="center"><input type="submit" class="button" value="'.$langs->trans("Modify").'"></td>';
	print '</tr>';
	print '</form>';
}

// Module produits
if ($conf->produit->enabled)
{
	$var=!$var;
	print '<form method="post" action="'.$_SERVER["PHP_SELF"].'">';
	print '<input type="hidden" name="action" value="setproductusebarcode">';
	print '<tr '.$bc[$var].'>';
	print '<td>'.$langs->trans("UseBarcodeInProductModule").'</td>';
	print '<td width="60" align="right">';
	print $html->selectyesno('value',$conf->global->PRODUIT_USE_BARCODE,1);
	print '</td>';
	print '<td width="60" align="center"><input type="submit" class="button" value="'.$langs->trans("Modify").'"></td>';
	print '</tr>';
	print '</form>';
}

print '</table>';
/*
//EAN13
      $var=!$var;
      print '<tr '.$bc[$var].'><td width="100">';
      print "EAN13";
      print "</td><td>\n";
      
      print "L'EAN se compose de 13 caractères, 12 chiffres plus une clé de contrôle. Il fonctionne de la même manière que l'UPC, avec lequel il est compatible.<br>";
      print "L'utilisation des symbologies EAN13 impose la souscription et l'abonnement auprès d'organisme tel que GENCOD.<br>";
      print "Codes numériques utilisés exclusivement à l'identification des produits susceptibles d'être vendus au grand public.";
      print '</td>';

      // Affiche exemple
      print '<td align="center"><img src="'.dol_genbarcode('123456789012','EAN',1).'"></td>';
      
      print '<td align="center">';
      print $html->setBarcodeEncoder('EAN13','form'.$i);
	    print "</td></tr>\n";
	    $i++;

//UPC
      $var=!$var;
      print '<tr '.$bc[$var].'><td width="100">';
      print "UPC";
      print "</td><td>\n";
      print "L'UPC est l'équivalent de l'EAN8/13 pour des pays codificateurs autre que l'Europe.<br>";
      print "Il ne comporte que 11 chiffres plus la clé.<br>";
      print "C'est en réalité un code EAN13 dont le premier chiffre serait zéro et dont la présentation serait légérement différente.<br>";
      print "Codes numériques utilisés exclusivement à l'identification des produits susceptibles d'être vendus au grand public.";
      print '</td>';

      // Affiche exemple
      print '<td align="center"><img src="'.dol_genbarcode('123456789012','UPC',1).'"></td>';
      
      print '<td align="center">';
      print $html->setBarcodeEncoder('UPC','form'.$i);
	    print "</td></tr>\n";
	    $i++;
	    
//ISBN
      $var=!$var;
      print '<tr '.$bc[$var].'><td width="100">';
      print "ISBN";
      print "</td><td>\n";
      print "Le code ISBN est un code dédié au milieu de la presse écrite.";
      print '</td>';

      // Affiche exemple
      print '<td align="center"><img src="'.dol_genbarcode('123456789','ISBN',1).'"></td>';
      
      print '<td align="center">';
      print $html->setBarcodeEncoder('ISBN','form'.$i);
	    print "</td></tr>\n";
	    $i++;
	    
//code 39
      $var=!$var;
      print '<tr '.$bc[$var].'><td width="100">';
      print "Code 39";
      print "</td><td>\n";
      print "Premier code alpha numérique utilisé massivement dans l'Industrie pour sa capacité d'encodage (chiffres et lettres)<br>";
      print "ainsi que par son degré de sécurité à l'encodage (clef de contrôle).<br>";
      print "Il met a disposition les 10 chiffres, les 26 lettres de l'alphabet et sept symboles.<br>";
			print "l'astérisque (*) sert de caractère de bornage. La lecture est bidirectionnelle.<br>";
			print "La longueur est variable mais en général ne dépasse pas 32 caractères.";
      print '</td>';

      // Affiche exemple
      print '<td align="center"><img src="'.dol_genbarcode('1234567890','39',1).'"></td>';
      
      print '<td align="center">';
      print $html->setBarcodeEncoder('C39','form'.$i);
	    print "</td></tr>\n";
	    $i++;
	    
	    
//code 128
      $var=!$var;
      print '<tr '.$bc[$var].'><td width="100">';
      print "Code 128";
      print "</td><td>\n";
      print "Ce code \"dernière génération\" alpha numérique est susceptible d'encoder les 128 caractères de la table ASCII ( chiffres + lettres + symboles ).<br>";
			print "Le code 128 possède des algorithmes de cryptage sécurisés assez avancés.<br>";
      print "C'est le plus complet des codes à barres, il propose 3 jeux de 128 caractères.<br>";
			print "La lecture est bidirectionnelle.<br>";
			print "La longueur est variable mais en général ne dépasse pas 20 caractères.";
      print '</td>';

      // Affiche exemple
      print '<td align="center"><img src="'.dol_genbarcode('ABCD1234567890','128',1).'"></td>';
      
      print '<td align="center">';
      print $html->setBarcodeEncoder('C128','form'.$i);
	    print "</td></tr>\n";
	    $i++;
	    
//I25
      $var=!$var;
      print '<tr '.$bc[$var].'><td width="100">';
      print "I25";
      print "</td><td>\n";
      print "information";
      print '</td>';

      // Affiche exemple
      print '<td align="center"><img src="'.dol_genbarcode('1234567890','I25',1).'"></td>';
      
      print '<td align="center">';
      print $html->setBarcodeEncoder('I25','form'.$i);
	    print "</td></tr>\n";
	    $i++;
*/

print "<br>";

$db->close();

llxFooter('$Date$ - $Revision$');
?>