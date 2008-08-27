<?PHP
/* Copyright (C) 2004-2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2006 Laurent Destailleur  <eldy@users.sourceforge.net>
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
   \file       htdocs/compta/export/modules/compta.export.poivre.class.php
   \ingroup    compta
   \brief      Modele d'export compta poivre, export au format tableur
   \remarks    Ce fichier doit etre utilise comme un exemple, il est specifique a une utilisation particuliere
   \version    $Revision$
*/

require_once(PHP_WRITEEXCEL_PATH."/class.writeexcel_workbook.inc.php");
require_once(PHP_WRITEEXCEL_PATH."/class.writeexcel_worksheet.inc.php");

/**
   \class      ComptaExportPoivre
   \brief      Classe permettant les exports comptables au format tableur
*/

class ComptaExportPoivre extends ComptaExport
{
    var $db;
    var $user;

  /**
     \brief      Constructeur de la class
     \param      DB          Object de base de données
     \param      USER        Object utilisateur
  */  
  function ComptaExportPoivre ($DB, $USER)
  {
    $this->db = $DB;
    $this->user = $USER;
  }
  
  /**
   * Agrégation des lignes de facture
   */
  function Agregate($line_in)
  {
    dolibarr_syslog("ComptaExportPoivre::Agregate");
    dolibarr_syslog("ComptaExportPoivre::Agregate " . sizeof($line_in) . " lignes en entrées");
    $i = 0;
    $j = 0;
    $n = sizeof($line_in);
    
    // On commence par la ligne 0
    
    $this->line_out[$j] = $line_in[$i];
    
    //print "$j ".$this->line_out[$j][8] . "<br>";
    
    for ( $i = 1 ; $i < $n ; $i++)
      {
	// On agrège les lignes avec le même code comptable
	
	if ( ($line_in[$i][1] == $line_in[$i-1][1]) && ($line_in[$i][4] == $line_in[$i-1][4]) )
	  {
	    $this->line_out[$j][8] = ($this->line_out[$j][8] + $line_in[$i][8]);
	  }
	else
	  {
	    $j++;
	    $this->line_out[$j] = $line_in[$i];
	  }	
        }
    
    dolibarr_syslog("ComptaExportPoivre::Agregate " . sizeof($this->line_out) . " lignes en sorties");
    
    return 0;
  }
  
  /*
   *
   */
  function Export($dir, $linec, $linep, $id=0)
  {
    $error = 0;
    
    dolibarr_syslog("ComptaExportPoivre::Export");
    dolibarr_syslog("ComptaExportPoivre::Export " . sizeof($linec) . " lignes en entrées");
    
    $this->Agregate($linec);
    
    $this->db->begin();
    
    if ($id == 0)
      {
	$dt = strftime('EC%y%m', time());
	
	$sql = "SELECT count(ref) FROM ".MAIN_DB_PREFIX."export_compta";
	$sql .= " WHERE ref like '$dt%'";
	
	if ($this->db->query($sql))
	  {
	    $row = $this->db->fetch_row();
	    $cc = $row[0];
	  }
	else
	  {
	    $error++;
	    dolibarr_syslog("ComptaExportPoivre::Export Erreur Select");
	  }
	
	
	if (!$error)
	  {
	    $this->ref = $dt . substr("000".$cc, -2);
	    
	    $sql = "INSERT INTO ".MAIN_DB_PREFIX."export_compta (ref, date_export, fk_user)";
	    $sql .= " VALUES ('".$this->ref."', ".$this->db->idate(mktime()).",".$this->user->id.")";
	    
	    if ($this->db->query($sql))
	      {
		$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX."export_compta");
	      }
	    else
	      {
		$error++;
		dolibarr_syslog("ComptaExportPoivre::Export Erreur INSERT");
	      }
	  }
      }
    else
      {
	$this->id = $id;
	
	$sql = "SELECT ref FROM ".MAIN_DB_PREFIX."export_compta";
	$sql .= " WHERE rowid = ".$this->id;
	
	$resql = $this->db->query($sql);
	
	if ($resql)
	  {
	    $row = $this->db->fetch_row($resql);
	    $this->ref = $row[0];
	  }
	else
	  {
	    $error++;
	    dolibarr_syslog("ComptaExportPoivre::Export Erreur Select");
	  }
      }
    
    
    if (!$error)
      {
	dolibarr_syslog("ComptaExportPoivre::Export ref : ".$this->ref);
	
	$fxname = $dir . "/".$this->ref.".xls";
	
	$workbook = &new writeexcel_workbook($fxname);
	
	$page = &$workbook->addworksheet('Export');
	
	$page->set_column(0,0,8); // A
	$page->set_column(1,1,6); // B
	$page->set_column(2,2,9); // C
	$page->set_column(3,3,14); // D
	$page->set_column(4,4,44); // E
	$page->set_column(5,5,9); // F Numéro de pièce
	$page->set_column(6,6,8); // G

	
	// Pour les factures
	
	// A 0 Date Opération 040604 pour 4 juin 2004
	// B 1 VE -> ventilation
	// C 2 code Compte général
	// D 3 code client
	// E 4 Intitul
	// F 5 Numéro de pièce
	// G 7 Montant
	// H 8 Type opération D pour Débit ou C pour Crédit
	// I Date d'échéance, = à la date d'opération si pas d'échéance
	// J EUR pour Monnaie en Euros
	
	// Pour les paiements
	
	$i = 0;
	$j = 0;
	$n = sizeof($this->line_out);
	
	$oldfacture = 0;
	
	for ( $i = 0 ; $i < $n ; $i++)
	  {
	    if ( $oldfacture <> $this->line_out[$i][1])
	      {
		// Ligne client
		$page->write_string($j, 0, strftime("%d%m%y",$this->line_out[$i][0]));
		$page->write_string($j, 1,  "VI");
		$page->write_string($j, 2,  "41100000");
		$page->write_string($j, 3, stripslashes($this->line_out[$i][2]));
		$page->write_string($j, 4, stripslashes($this->line_out[$i][3])." Facture");
		$page->write_string($j, 5, $this->line_out[$i][5]); // Numéro de factur
		$page->write($j, 6, price2num($this->line_out[$i][7]));
		$page->write_string($j, 7, 'D' ); // D pour débit
		$page->write_string($j, 8, strftime("%d%m%y",$this->line_out[$i][0]));
		
		$j++;
		
		// Ligne TVA
		$page->write_string($j, 0, strftime("%d%m%y",$this->line_out[$i][0]));
		$page->write_string($j, 1, "VI");
		$page->write_string($j, 2, '4457119');
		
		$page->write_string($j, 4, stripslashes($this->line_out[$i][3])." Facture");
		$page->write_string($j, 5, $this->line_out[$i][5]); // Numéro de facture
		$page->write($j, 6, price2num($this->line_out[$i][6])); // Montant de TVA
		$page->write_string($j, 7, 'C'); // C pour crédit
		$page->write_string($j, 8, strftime("%d%m%y",$this->line_out[$i][0]));
				
		$oldfacture = $this->line_out[$i][1];
		$j++;
	      }

	    $page->write_string($j, 0, strftime("%d%m%y",$this->line_out[$i][0]));
	    $page->write_string($j, 1, 'VI');
	    $page->write_string($j, 2, $this->line_out[$i][4]); // Code Comptable
	    $page->write_string($j, 4, $this->line_out[$i][3]." Facture");
	    $page->write_string($j, 5, $this->line_out[$i][5]);
	    $page->write($j, 6, price2num(round($this->line_out[$i][8], 2)));
	    $page->write_string($j, 7, 'C');                     // C pour crédit
	    $page->write_string($j, 8, strftime("%d%m%y",$this->line_out[$i][0]));
	    
	    $j++;
	  }
	
	// Tag des lignes de factures
	$n = sizeof($linec);
	for ( $i = 0 ; $i < $n ; $i++)
	  {
	    $sql = "UPDATE ".MAIN_DB_PREFIX."facturedet";
	    $sql .= " SET fk_export_compta=".$this->id;
	    $sql .= " WHERE rowid = ".$linec[$i][10];
	    
	    if (!$this->db->query($sql))
	      {
		$error++;
	      }
	  }
	
	// Pour les paiements
	
	// A Date Opération 040604 pour 4 juin 2004
	// B CE -> caisse d'epargne
	// C code Compte général
	// D code client
	// E Intitul
	// F Numéro de pièce
	// G Montant
	// H Type opération D pour Débit ou C pour Crédit
	// I Date d'échéance, = à la date d'opération si pas d'échéance
	// J EUR pour Monnaie en Euros
	
	$i = 0;
	//$j = 0;
	$n = sizeof($linep);
	
	$oldfacture = 0;
	
	for ( $i = 0 ; $i < $n ; $i++)
	  {
	    /*
	     * En cas de rejet ou paiement en négatif on inverse debit et credit
	     *
	     *
	     */
	    if ($linep[$i][5] >= 0)
	      {
		$debit = "D";
		$credit = "C";
	      }
	    else
	      {
		$debit = "C";
		$credit = "D";
		
		if ($linep[$i][6] == 'Prélèvement')
		  {
		    $linep[$i][6] = 'Rejet Prelevement';
		  }		
	      }
	    
	    $page->write_string($j,0, strftime("%d%m%y",$linep[$i][0]));
	    $page->write_string($j,1, 'CE');
	    
	    $page->write_string($j,2, '5122000');

	    if ($linep[$i][6] == 'Prélèvement')
	      {
		$linep[$i][6] = 'Prelevement';
	      }
	    
	    $page->write_string($j,4, stripslashes($linep[$i][3])." ".stripslashes($linep[$i][6])); //
	    $page->write_string($j,5, $linep[$i][7]);                  // Numéro de facture
	    
	    $page->write($j,6, price2num(round(abs($linep[$i][5]), 2)));  // Montant de la ligne
	    $page->write_string($j,7,$debit);
	    $page->write_string($j,8, strftime("%d%m%y",$linep[$i][0]));
	    
	    
	    $j++;
	    
	    $page->write_string($j,0, strftime("%d%m%y",$linep[$i][0]));
	    $page->write_string($j,1, 'CE');
	    
	    $page->write_string($j,2, '41100000');
	    $page->write_string($j,3, $linep[$i][2]);
	    $page->write_string($j,4, stripslashes($linep[$i][3])." ".stripslashes($linep[$i][6])); //
	    $page->write_string($j,5, $linep[$i][7]);     // Numéro de facture
	    $page->write($j,6, price2num(round(abs($linep[$i][5]), 2)));  // Montant de la ligne
	    $page->write_string($j,7, $credit);
	    $page->write_string($j,8, strftime("%d%m%y",$linep[$i][0]));
	    
	    $j++;
	    
	  }
	$workbook->close();
	
	// Tag des lignes de factures
	$n = sizeof($linep);
	for ( $i = 0 ; $i < $n ; $i++)
	  
	  {
	    $sql = "UPDATE ".MAIN_DB_PREFIX."paiement";
	    $sql .= " SET fk_export_compta=".$this->id;
	    $sql .= " WHERE rowid = ".$linep[$i][1];
	    
	    if (!$this->db->query($sql))
	      {
		$error++;
	      }
	  }
	
      }
    
    if (!$error)
      {
	$this->db->commit();
	dolibarr_syslog("ComptaExportPoivre::Export COMMIT");
      }
    else
      {
	$this->db->rollback();
	dolibarr_syslog("ComptaExportPoivre::Export ROLLBACK");
      }
    
    return 0;
  }
}
