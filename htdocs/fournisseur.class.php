<?php
/* Copyright (C) 2004-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
 *
 */

/*!
  \file       htdocs/fournisseur.class.php
  \ingroup    societe
  \brief      Fichier de la classe des fournisseurs
  \version    $Revision$
*/

/*!
  \class Fournisseur
  \brief Classe permettant la gestion des fournisseur
*/

include_once DOL_DOCUMENT_ROOT."/societe.class.php";
include_once DOL_DOCUMENT_ROOT."/fournisseur.commande.class.php";

class Fournisseur extends Societe {
  var $db;

  /**
   *    \brief  Constructeur de la classe
   *    \param  DB     handler accès base de données
   *    \param  id     id societe (0 par defaut)
   */
	 
  function Fournisseur($DB, $id=0)
  {
    global $config;

    $this->db = $DB;
    $this->id = $id;
    $this->client = 0;
    $this->fournisseur = 0;
    $this->effectif_id  = 0;
    $this->forme_juridique_code  = 0;

    return 0;
  }


  function nb_open_commande()
  {
    $sql = "SELECT rowid";
    $sql .= " FROM ".MAIN_DB_PREFIX."commande_fournisseur as cf";
    $sql .= " WHERE cf.fk_soc = ".$this->id;
    
    $result = $this->db->query($sql) ;
    
    if ( $result )
      {
	$num = $this->db->num_rows();
	
	if ($num == 1)
	  {
	    $row = $this->db->fetch_row();

	    $this->single_open_commande = $row[0];
	  }
      }
    return $num;
  }

  function NbProduct()
  {
    $sql = "SELECT count(*)";
    $sql .= " FROM ".MAIN_DB_PREFIX."product_fournisseur";
    $sql .= " WHERE fk_soc = ".$this->id;
    
    $resql = $this->db->query($sql) ;
    
    if ( $resql )
      {
	$row = $this->db->fetch_row($resql);	    
	return $row[0];
      }
    else
      {
	return -1;
      }

  }

  function create_commande($user)
  {
    $result = -1;
    dolibarr_syslog("Fournisseur::Create_Commande");
    $comm = new CommandeFournisseur($this->db);
    $comm->soc_id = $this->id;

    if ( $comm->create($user) == 0 )
      {
	dolibarr_syslog("Fournisseur::Create_Commande : Success");

	$this->single_open_commande = $comm->id;

	$result = 0;
      }
    else
      {
	dolibarr_syslog("Fournisseur::Create_Commande : Failed");
	$result = -2;
      }

    return $result;
  }

  function ProductCommande($user, $product_id)
  {
    include_once DOL_DOCUMENT_ROOT."/fournisseur.commande.class.php";
    include_once DOL_DOCUMENT_ROOT."/product.class.php";

    $commf = new CommandeFournisseur($this->db);
    
    $nbc = $this->nb_open_commande();
    
    dolibarr_syslog("Fournisseur::ProductCommande : nbc = ".$nbc);
    
    if ($nbc == 0)
      {
	if ( $this->create_commande($user) == 0 )
	  {
	    $idc = $this->single_open_commande;
	  }
      }
    elseif ($nbc == 1)
      {
	
	$idc = $this->single_open_commande;
      }
    
    if ($idc > 0)
      {
	$prod = new Product($this->db);
	$prod->fetch($product_id);
	$prod->fetch_fourn_data($this->id);

	$commf->fetch($idc);
	$commf->addline("Toto",120,1,$prod->tva, $prod->id, 0, $prod->ref_fourn);
      }
  }
}

?>
