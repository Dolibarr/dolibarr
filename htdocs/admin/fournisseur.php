<?php
/* Copyright (C) 2003-2007 Rodolphe Quiedeville    <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2007 Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2007 Regis Houssin           <regis@dolibarr.fr>
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
 *
 * $Id$
 */

/**
   \file       htdocs/admin/fournisseur.php
   \ingroup    fournisseur
   \brief      Page d'administration-configuration du module Fournisseur
   \version    $Revision$
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
if ($_GET["action"] == 'specimen')
{
  $modele=$_GET["module"];
  
  $commande = new CommandeFournisseur($db);
  $commande->initAsSpecimen();
  
  // Charge le modele
  $dir = DOL_DOCUMENT_ROOT . "/fourn/commande/modules/pdf/";
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
    }
  else
    {
      $mesg='<div class="error">'.$langs->trans("ErrorModuleNotFound").'</div>';
    }
}

if ($_GET["action"] == 'set')
{
  $type='supplier_order';
  $sql = "INSERT INTO ".MAIN_DB_PREFIX."document_model (nom, type) VALUES ('".$_GET["value"]."','".$type."')";
  if ($db->query($sql))
    {
      
    }
}

if ($_GET["action"] == 'del')
{
  $type='supplier_order';
  $sql = "DELETE FROM ".MAIN_DB_PREFIX."document_model";
  $sql .= "  WHERE nom = '".$_GET["value"]."' AND type = '".$type."'";
  if ($db->query($sql))
    {
      
    }
}

if ($_GET["action"] == 'setdoc')
{
	$db->begin();
	
    if (dolibarr_set_const($db, "COMMANDE_SUPPLIER_ADDON_PDF",$_GET["value"]))
    {
        $conf->global->COMMANDE_SUPPLIER_ADDON_PDF = $_GET["value"];
    }

    // On active le modele
    $type='supplier_order';
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

	dolibarr_set_const($db, "COMMANDE_SUPPLIER_ADDON",$_GET["value"]);
}

if ($_POST["action"] == 'addcat')
{
  $fourn = new Fournisseur($db);
  $fourn->CreateCategory($user,$_POST["cat"]);
}

// d�fini les constantes du mod�le orchidee
if ($_POST["action"] == 'updateMatrice') dolibarr_set_const($db, "COMMANDE_FOURNISSEUR_NUM_MATRICE",$_POST["matrice"]);
if ($_POST["action"] == 'updatePrefixCommande') dolibarr_set_const($db, "COMMANDE_FOURNISSEUR_NUM_PREFIX",$_POST["prefixcommande"]);
if ($_POST["action"] == 'setOffset') dolibarr_set_const($db, "COMMANDE_FOURNISSEUR_NUM_DELTA",$_POST["offset"]);
if ($_POST["action"] == 'setFiscalMonth') dolibarr_set_const($db, "SOCIETE_FISCAL_MONTH_START",$_POST["fiscalmonth"]);
if ($_POST["action"] == 'setNumRestart') dolibarr_set_const($db, "COMMANDE_FOURNISSEUR_NUM_RESTART_BEGIN_YEAR",$_POST["numrestart"]);

/*
 * Affichage page
 */
 
llxHeader();

$dir = "../fourn/commande/modules/pdf/";
$html=new Form($db);

$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';
print_fiche_titre($langs->trans("SuppliersSetup"),$linkback,'setup');

print "<br>";

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

$dir = "../fourn/commande/modules/";
$handle = opendir($dir);
if ($handle)
{
    $var=true;
    
    while (($file = readdir($handle))!==false)
    {
        if (substr($file, 0, 25) == 'mod_commande_fournisseur_' && substr($file, strlen($file)-3, 3) == 'php')
        {
            $file = substr($file, 0, strlen($file)-4);

            require_once(DOL_DOCUMENT_ROOT ."/fourn/commande/modules/".$file.".php");

            $module = new $file;

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
	    
	    // Info
	    $htmltooltip='';
	    $nextval=$module->getNextValue($mysoc,$commande);
	    if ($nextval != $langs->trans("NotAvailable"))
	    {
	    	$htmltooltip='<b>'.$langs->trans("NextValue").'</b>: '.$nextval;
	    }
	    print '<td align="center">';
	    print $html->textwithhelp('',$htmltooltip,1,0);
	    print '</td>';
	    
            print '</tr>';
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
$type='supplier_order';
$def = array();
$sql = "SELECT nom";
$sql.= " FROM ".MAIN_DB_PREFIX."document_model";
$sql.= " WHERE type = '".$type."'";
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

$dir = "../fourn/commande/modules/pdf/";

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
      
      // Activ�
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
      $htmltooltip =    '<b>'.$langs->trans("Type").'</b>: '.($module->type?$module->type:$langs->trans("Unknown"));
      $htmltooltip.='<br><b>'.$langs->trans("Width").'</b>: '.$module->page_largeur;
      $htmltooltip.='<br><b>'.$langs->trans("Height").'</b>: '.$module->page_hauteur;
      $htmltooltip.='<br><br>'.$langs->trans("FeaturesSupported").':';
      $htmltooltip.='<br><b>'.$langs->trans("Logo").'</b>: '.yn($module->option_logo);
      $htmltooltip.='<br><b>'.$langs->trans("PaymentMode").'</b>: '.yn($module->option_modereg);
      $htmltooltip.='<br><b>'.$langs->trans("PaymentConditions").'</b>: '.yn($module->option_condreg);
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

/* Obsolete. Les categories de fournisseurs sont gerees dans la table llx_categories
sur le meme principe que les categories clients et produits

print_titre($langs->trans("Categories"));

$sql = "SELECT rowid, label";
$sql.= " FROM ".MAIN_DB_PREFIX."fournisseur_categorie";
$sql.= " ORDER BY label ASC";

$resql = $db->query($sql);
if ($resql)
{
  $num = $db->num_rows($resql);
  $i = 0;
  
  print '<form action="fournisseur.php" method="POST"><table class="liste" width="100%">';
  print '<input type="hidden" name="action" value="addcat">';
  print '<tr class="liste_titre"><td>';
  print $langs->trans("Num").'</td><td>'.$langs->trans("Name");
  print "</td></tr>\n";
  $var=True;

  print "<tr $bc[$var]><td>&nbsp;</td>";
  print '<td><input type="text" name="cat">&nbsp;';
  print '<input type="submit" value="'.$langs->trans("Add").'">';
  print "</td></tr>\n";

  while ($obj = $db->fetch_object($resql))
    {
      $var=!$var;
      print "<tr $bc[$var]>\n";
      print '<td width="10%">'.$obj->rowid.'</td>';
      print '<td width="90%"><a href="liste.php?cat='.$obj->rowid.'">'.stripslashes($obj->label).'</a></td>';
      print "</tr>\n";
    }
  print "</table></form>\n";

  $db->free($resql);
}
else 
{
  dolibarr_print_error($db);
}
*/

llxFooter('$Date$ - $Revision$');
?>
