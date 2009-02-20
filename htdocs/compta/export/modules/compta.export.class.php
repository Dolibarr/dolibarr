<?PHP
/* Copyright (C) 2004-2006 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2006      Laurent Destailleur  <eldy@users.sourceforge.net>
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
   \file       htdocs/compta/export/modules/compta.export.class.php
   \ingroup    compta
   \brief      Fichier de la classe d'export compta
   \version    $Revision$
*/


/**
   \class      ComptaExport
   \brief      Classe permettant les exports comptables
*/

class ComptaExport
{
  /**
     \brief      Constructeur de la class
     \param      DB          Object de base de données
     \param      USER        Object utilisateur
     \param      classe      Nom de la classe utilisée pour formater les rapports
  */
  function ComptaExport ($DB, $USER, $classe)
  {
    $this->db = $DB;
    $this->user = $USER;
    $this->classe_export = $classe;
    $this->error_message = '';
  }
  
  
  /**
     \brief      Lecture des factures dans la base
     \param      id      Id ligne
  */
  function ReadLines($id=0)
  {
    global $langs;
    
    dol_syslog("ComptaExport::ReadLines id=".$id);
    
    $error = 0;
    
    $sql = "SELECT f.rowid as facid, f.facnumber, ".$this->db->pdate("f.datef")." as datef";
    $sql .= " , f.total_ttc, f.tva ";
    $sql .= " ,s.nom, s.rowid as socid, s.code_compta";
    $sql .= " , l.price, l.tva_taux";
    $sql .= " , c.numero, f.increment";
    $sql .= " , l.rowid as lrowid";
    
    $sql .= " FROM ".MAIN_DB_PREFIX."facturedet as l";
    $sql .= " , ".MAIN_DB_PREFIX."facture as f";
    $sql .= " , ".MAIN_DB_PREFIX."societe as s";
    $sql .= " , ".MAIN_DB_PREFIX."compta_compte_generaux as c";
    
    $sql .= " WHERE f.rowid = l.fk_facture ";
    $sql .= " AND s.rowid = f.fk_soc";
    $sql .= " AND f.fk_statut = 1 ";
    
    $sql .= " AND l.fk_code_ventilation <> 0 ";
    
    $sql .= " AND l.fk_export_compta = ".$id;
    
    $sql .= " AND c.rowid = l.fk_code_ventilation";
    
    $sql .= " ORDER BY f.rowid ASC, l.fk_code_ventilation ASC";
    
    
    $resql = $this->db->query($sql);
    
    if ($resql)
      {
	$num = $this->db->num_rows($resql);
	$i = 0;
	$this->linec = array();
	
	while ($i < $num)
	  {
	    $obj = $this->db->fetch_object($resql);
	    
	    $this->linec[$i][0] = $obj->datef;
	    $this->linec[$i][1] = $obj->facid;
	    $this->linec[$i][2] = $obj->code_compta;
	    $this->linec[$i][3] = $obj->nom;
	    $this->linec[$i][4] = $obj->numero;
	    $this->linec[$i][5] = $obj->facnumber;
	    $this->linec[$i][6] = $obj->tva;
	    $this->linec[$i][7] = $obj->total_ttc;
	    $this->linec[$i][8] = $obj->price;
	    $this->linec[$i][9] = $obj->increment;
	    $this->linec[$i][10] = $obj->lrowid;
	    
	    if ($obj->code_compta == '')
	      {
		$societe=new Societe($this->db);
		$societe->fetch($obj->socid);
		$this->error_message.= $langs->transnoentities("ErrorWrongAccountancyCodeForCompany",$societe->getNomUrl(1))."<br>";
		$error++;
	      }
	    
	    $i++;
	  }
	$this->db->free($resql);
      }
    
    return $error;
  }
  
  /**
     \brief      Lecture des paiements dans la base
     \param      id      Id ligne
  */
  
  function ReadLinesPayment($id=0)
  {
    dol_syslog("ComptaExport::ReadLinesPayment id=".$id);
    $error = 0;
    
    $sql = "SELECT p.rowid as paymentid, f.facnumber";
    $sql .= " ,".$this->db->pdate("p.datep")." as datep";
    $sql .= " , pf.amount";
    $sql .= " , s.nom, s.code_compta";
    $sql .= " , cp.libelle, f.increment";

    $sql .= " FROM ".MAIN_DB_PREFIX."paiement as p";
    $sql .= " , ".MAIN_DB_PREFIX."paiement_facture as pf";
    $sql .= " , ".MAIN_DB_PREFIX."c_paiement as cp";
    $sql .= " , ".MAIN_DB_PREFIX."facture as f";
    $sql .= " , ".MAIN_DB_PREFIX."societe as s";
    
    $sql .= " WHERE p.fk_export_compta = ".$id;
    $sql .= " AND p.rowid = pf.fk_paiement";
    $sql .= " AND cp.id = p.fk_paiement";
    $sql .= " AND f.rowid = pf.fk_facture";
    $sql .= " AND f.fk_soc = s.rowid";
    $sql .= " AND p.statut = 1 ";

    $sql .= " ORDER BY f.rowid ASC, p.rowid ASC";

    $resql = $this->db->query($sql);

    if ($resql)
      {
	$num = $this->db->num_rows($resql);
	$i = 0;
	$this->linep = array();

	while ($i < $num)
	  {
	    $obj = $this->db->fetch_object($resql);

	    $this->linep[$i][0] = $obj->datep;
	    $this->linep[$i][1] = $obj->paymentid;
	    $this->linep[$i][2] = $obj->code_compta;
	    $this->linep[$i][3] = $obj->nom;
	    $this->linep[$i][4] = $obj->facnumber;
	    $this->linep[$i][5] = $obj->amount;
	    $this->linep[$i][6] = $obj->libelle;

	    if (strlen(trim( $obj->increment)) > 0)
	      {
		$this->linep[$i][7] = $obj->increment;
	      }
	    else
	      {
		$this->linep[$i][7] = $obj->facnumber;
	      }

	    $i++;
	  }

	$this->db->free($resql);

      }
    else
      {
	$error++;
      }
        
    return $error;
  }

  /**
    \brief      Créé le fichier d'export
  */

  function Export($id=0, $dir)
  {
    $error = 0;

    dol_syslog("ComptaExport::Export");

    $error += $this->ReadLines($id);
    $error += $this->ReadLinesPayment($id);

    dol_syslog("ComptaExport::Export Lignes de factures  : ".sizeof($this->linec));
    dol_syslog("ComptaExport::Export Lignes de paiements : ".sizeof($this->linep));

    if (!$error && (sizeof($this->linec) > 0 || sizeof($this->linep) > 0))
      {
	include_once DOL_DOCUMENT_ROOT.'/compta/export/modules/compta.export.'.strtolower($this->classe_export).'.class.php';  

	$objexport_name = "ComptaExport".$this->classe_export;

	$objexport = new $objexport_name($this->db, $this->user);

	$objexport->Export($dir, $this->linec, $this->linep, $id);

	$this->id = $objexport->id;
	$this->ref = $objexport->ref;
      }
  }

}
