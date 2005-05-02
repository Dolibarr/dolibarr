<?php
/* Copyright (C) 2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2005 Laurent Destailleur  <eldy@users.sourceforge.net>
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


require("./pre.inc.php");

if (!$user->admin)
    access_forbidden();


llxHeader();


print_titre("Vérifications et réparation des données de la base");


/*
 * Mise a jour des paiements (lien n-n paiements factures)
 */
print '<br>';
print "<b>Mise a jour des paiments (lien n-n paiements-factures)</b><br>\n";

$sql = "SELECT p.rowid, p.fk_facture, p.amount";
$sql .= " FROM ".MAIN_DB_PREFIX."paiement as p";
$sql .= " WHERE p.fk_facture > 0";
$resql = $db->query($sql);

if ($resql) 
{
  $i = 0;
  $row = array();
  $num = $db->num_rows($resql);
  
  while ($i < $num)
    {
      $obj = $db->fetch_object($resql);
      $row[$i][0] = $obj->rowid ;
      $row[$i][1] = $obj->fk_facture;
      $row[$i][2] = $obj->amount;
      $i++;
    }
}
else {
    dolibarr_print_error($db);   
}

if ($num)
{
    print "$num paiement(s) à mettre à jour<br>\n";
    if ($db->begin())
    {
      $res = 0;
      for ($i = 0 ; $i < sizeof($row) ; $i++)
        {
          $sql = "INSERT INTO ".MAIN_DB_PREFIX."paiement_facture (fk_facture, fk_paiement, amount)";
          $sql .= " VALUES (".$row[$i][1].",".$row[$i][0].",".$row[$i][2].")";
          
          $res += $db->query($sql);
          
          $sql = "UPDATE ".MAIN_DB_PREFIX."paiement SET fk_facture = 0 WHERE rowid = ".$row[$i][0];
          
          $res += $db->query($sql);
    
          print "Mise a jour paiement(s) ".$row[$i]."<br>\n";
        } 
    }
    
    if ($res == (2 * sizeof($row)))
    {
      $db->commit();
      print "Mise à jour réussie<br>";
    }
    else
    {
      $db->rollback();
      print "La mise à jour à échouée<br>";
    }
}
else
{
    print "Pas ou plus de paiements orhpelins à corriger.<br>\n";
}  


/*
 * Mise a jour des date de contrats non renseignées
 */
print '<br>';
print "<b>Mise a jour des dates de contrats non renseignées</b><br>\n";

$sql="update llx_contrat set date_contrat=tms where date_contrat is null";
$resql = $db->query($sql);
if (! $resql) dolibarr_print_error($db);

$sql="update llx_contrat set datec=tms where datec is null";
$resql = $db->query($sql);
if (! $resql) dolibarr_print_error($db);
print "Ok<br>\n";

/*
 * Mise a jour des contrats (gestion du contrat + detail de contrat)
 */
$nberr=0;

print '<br>';
print "<b>Mise a jour des contrats sans details (gestion du contrat + detail de contrat)</b><br>\n";

$sql = "SELECT c.rowid as cref, c.date_contrat, c.statut, c.mise_en_service, c.fin_validite, c.date_cloture, c.fk_product, c.fk_facture, c.fk_user_author,";
$sql.= " p.ref, p.label, p.description, p.price, p.tva_tx, p.duration, cd.rowid";
$sql.= " FROM ".MAIN_DB_PREFIX."contrat as c";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."product as p";
$sql.= " ON c.fk_product = p.rowid";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."contratdet as cd";
$sql.= " ON c.rowid=cd.fk_contrat";
$sql.= " WHERE cd.rowid IS NULL AND p.rowid IS NOT NULL";
$resql = $db->query($sql);

if ($resql) 
{
    $i = 0;
    $row = array();
    $num = $db->num_rows($resql);

    if ($num)
    {
        print "$num contrat(s) à mettre à jour<br>\n";
        $db->begin();
        
        while ($i < $num)
        {
            $obj = $db->fetch_object($resql);
            
            $sql = "INSERT INTO ".MAIN_DB_PREFIX."contratdet (";
            $sql.= "fk_contrat, fk_product, statut, label, description,";
            $sql.= "date_ouverture_prevue, date_ouverture, date_fin_validite, tva_tx, qty,";
            $sql.= "subprice, price_ht, fk_user_author, fk_user_ouverture)";
            $sql.= " VALUES (";
            $sql.= $obj->cref.",".($obj->fk_product?$obj->fk_product:0).",";
            $sql.= ($obj->mise_en_service?"4":"0").",";
            $sql.= "'".addslashes($obj->label)."', null,";
            $sql.= ($obj->mise_en_service?"'".$obj->mise_en_service."'":($obj->date_contrat?"'".$obj->date_contrat."'":"null")).",";
            $sql.= ($obj->mise_en_service?"'".$obj->mise_en_service."'":"null").",";
            $sql.= ($obj->fin_validite?"'".$obj->fin_validite."'":"null").",";
            $sql.= "'".$obj->tva_tx."', 1,";
            $sql.= "'".$obj->price."', '".$obj->price."',".$obj->fk_user_author.",";
            $sql.= ($obj->mise_en_service?$obj->fk_user_author:"null");
            $sql.= ")";

            if ($db->query($sql)) 
            {
                print "Création ligne contrat pour contrat ref ".$obj->cref."<br>\n";
            }
            else 
            {            
                dolibarr_print_error($db);
                $nberr++;
            }

            $i++;
        }

        if (! $nberr)
        {
        //      $db->rollback();
              $db->commit();
              print "Mise à jour réussie<br>";
        }
        else
        {
              $db->rollback();
              print "La mise à jour à échouée<br>";
        }
    }
    else {
        print "Pas ou plus de contrats (liés à un produit) sans lignes de details à corriger.<br>\n";
    }
}
else
{
    dolibarr_print_error($db);   
}    

print "<br>";

$db->close();

llxFooter();
?>
