<?php
/* Copyright (C) 2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004 Destailleur Laurent  <eldy@users.sourceforge.net>
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
	    \file       htdocs/contrat/contrat.class.php
        \ingroup    contrat
		\brief      Fichier de la classe des contrats
		\version    $Revision$
*/


/*!     \class      Contrat
		\brief      Classe permettant la gestion des contrats
*/

class Contrat
{
  var $id;
  var $db;

  /**
   *    \brief      Constructeur de la classe
   *    \param      DB          handler accès base de données
   */
  function Contrat($DB)
  {
    $this->db = $DB ;
    $this->product = new Product($DB);
    $this->societe = new Societe($DB);
    $this->user_service = new User($DB);
    $this->user_cloture = new User($DB);
  }

  /*
   *    \brief      Modifie date de mise en service d'un contrat
   *                Si la duree est renseignée, date_start=date_start et date_end=date_start+duree
   *                sinon date_start=date_start et date_end=date_end
   */
  function mise_en_service($user, $date_start, $duree=0, $date_end)
  {
    if ($duree) {
        // Si duree renseignee
        $duree_value = substr($duree,0,strlen($duree)-1);
        $duree_unit = substr($duree,-1);

        $month = date("m",$date_start);
        $day = date("d",$date_start);
        $year = date("Y",$date_start);

        switch($duree_unit) 
          {
          case "d":
    	$day = $day + $duree_value;
    	break;
          case "w":
    	$day = $day + ($duree_value * 7);
    	break;
          case "m":
    	$month = $month + $duree_value;
    	break;
          case "y":
    	$year = $year + $duree_value;
    	break;
          }
        $date_end = mktime(date("H",$date_start), date("i",$date_start), 0, $month, $day, $year);
    }

    $sql = "UPDATE ".MAIN_DB_PREFIX."contrat SET enservice = 1";
    $sql .= " , mise_en_service = ".$this->db->idate($date_start).", fk_user_mise_en_service = ".$user->id;
    $sql .= " , fin_validite = ". $this->db->idate($date_end);
    $sql .= " WHERE rowid = ".$this->id . " AND enservice = 0";

    $result = $this->db->query($sql) ;
    if (!$result)
      {
      dolibarr_print_error($this->db);
      }
  }

  /*
   *    \brief      Cloture un contrat
   *    \param      user    objet User qui cloture
   *
   */
  function cloture($user)
  {
    $sql = "UPDATE ".MAIN_DB_PREFIX."contrat SET enservice = 2";
    $sql .= " , date_cloture = now(), fk_user_cloture = ".$user->id;
    $sql .= " WHERE rowid = ".$this->id . " AND enservice = 1";

    $result = $this->db->query($sql) ;
  }

  /*
   *    \brief      Annule un contrat
   *    \param      user    objet User qui annule
   *
   */
  function annule($user)
  {
    $sql = "UPDATE ".MAIN_DB_PREFIX."contrat SET enservice = 0";
    $sql .= " , date_cloture = now(), fk_user_cloture = ".$user->id;
    $sql .= " WHERE rowid = ".$this->id . " AND enservice = 1";

    $result = $this->db->query($sql) ;
  }

  /*
   *    \brief      Charge de la base les données du contrat
   *    \param      id      id du contrat à charger
   */ 
  function fetch ($id)
  {    
      $sql = "SELECT rowid, enservice, fk_soc, fk_product, ".$this->db->pdate("mise_en_service")." as datemise";
      $sql .= ", fk_user_mise_en_service, ".$this->db->pdate("date_cloture")." as datecloture";
      $sql .= ", ".$this->db->pdate("fin_validite")." as datefin";
      $sql .= ", fk_user_cloture, fk_facture, fk_facturedet";
      $sql .= " FROM ".MAIN_DB_PREFIX."contrat WHERE rowid = $id";

      $result = $this->db->query($sql) ;

      if ( $result )
	{
	  $result = $this->db->fetch_array();

	  $this->id                = $result["rowid"];
	  $this->enservice         = $result["enservice"];
	  $this->factureid         = $result["fk_facture"];
	  $this->facturedetid      = $result["fk_facturedet"];
	  $this->mise_en_service   = $result["datemise"];
	  $this->date_fin_validite = $result["datefin"];
	  $this->date_cloture      = $result["datecloture"];

	  $this->user_service->id = $result["fk_user_mise_en_service"];
	  $this->user_cloture->id = $result["fk_user_cloture"];

	  $this->product->fetch($result["fk_product"]);

	  $this->societe->fetch($result["fk_soc"]);

	  $this->db->free();
	}
      else
	{
      dolibarr_print_error($this->db);
	}

      return $result;
  }

  /*
   *    \brief      Crée autant de contrats que de lignes de facture, pour une facture donnée
   *
   */
  function create_from_facture($factureid, $user, $socid)
    {
      $sql = "SELECT p.rowid as rowid, fd.rowid as fdrowid FROM ".MAIN_DB_PREFIX."product as p, ".MAIN_DB_PREFIX."facturedet as fd";
      $sql .= " WHERE p.rowid = fd.fk_product AND p.fk_product_type = 1 AND fd.fk_facture = ".$factureid;

      if ($this->db->query($sql))
	{
	  $num = $this->db->num_rows();

	  if ($num > 0)
	    {
	      $i = 0;
	  
	      while ($i < $num)
		{
		  $objp = $this->db->fetch_object();
		  $prowid[$i] = $objp->rowid;
		  $fdrowid[$i] = $objp->fdrowid;
		  $i++;
		}
	      
	      $this->db->free();
	      
	      while (list($i, $value) = each ($prowid))
		{
		  $sql = "INSERT INTO ".MAIN_DB_PREFIX."contrat (fk_product, fk_facture, fk_facturedet, fk_soc, fk_user_author)";
		  $sql .= " VALUES (".$prowid[$i].", $factureid, ".$fdrowid[$i].", $socid, $user->id)";
		  if (! $this->db->query($sql))
		    {
		      dolibarr_syslog("Contrat::create_from_facture - 10");
		      dolibarr_print_error($this->db,"Contrat::create_from_facture - 10");
		    }
		}
	    }
	  else
	    {
	      $this->db->free();
	    }
	}
      else
	{
	  dolibarr_syslog("Contrat::create_from_facture - 20");
      dolibarr_print_error($this->db,"Contrat::create_from_facture - 20");
	}
      
      return $result;
    }


  /**
   *    \brief      Retourne le libellé du statut du contrat
   *    \return     string      Libellé
   */
  function get_libstatut()
    {
		return $this->LibStatut($this->statut);
    }

  /**
   *    \brief      Renvoi le libellé d'un statut donné
   *    \param      statut      id statut
   *    \return     string      Libellé
   */
    function LibStatut($statut)
    {
        global $langs;
        $langs->load("contracts");

        if ($statut == 0) { return $langs->trans("ContractStatusNotRunning"); }
        if ($statut == 1) { return $langs->trans("ContractStatusRunning"); }
        if ($statut == 2) { return $langs->trans("ContractStatusClosed"); }
    }

}
?>
