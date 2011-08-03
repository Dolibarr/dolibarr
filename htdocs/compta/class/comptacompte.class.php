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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *	\file       htdocs/compta/class/comptacompte.class.php
 * 	\ingroup    compta
 * 	\brief      Fichier de la classe des comptes comptable
 * 	\version    $Id: comptacompte.class.php,v 1.3 2011/08/03 00:46:33 eldy Exp $
 */


/** 	\class ComptaCompte
 *    	\brief Classe permettant la gestion des comptes generaux de compta
 */

class ComptaCompte
{
  var $db ;

  var $id ;
  var $num;
  var $intitule;

  /**
   *    \brief  Constructeur de la classe
   *    \param  DB          handler acces base de donnees
   *    \param  id          id compte (0 par defaut)
   */

  function ComptaCompte($DB, $id=0)
    {
      $this->db = $DB;
      $this->id   = $id ;
    }

  /**
   *    \brief  Insere le produit en base
   *    \param  user utilisateur qui effectue l'insertion
   */

  function create($user)
    {
      if (dol_strlen(trim($this->numero)) && dol_strlen(trim($this->intitule)))
	{
	  $sql = "SELECT count(*)";
	  $sql .= " FROM ".MAIN_DB_PREFIX."compta_compte_generaux ";
	  $sql .= " WHERE numero = '" .trim($this->numero)."'";

	  $resql = $this->db->query($sql) ;

	  if ( $resql )
	    {
	      $row = $this->db->fetch_array($resql);
	      if ($row[0] == 0)
		{
		  $sql = "INSERT INTO ".MAIN_DB_PREFIX."compta_compte_generaux (date_creation, fk_user_author, numero,intitule)";
		  $sql .= " VALUES (".$this->db->idate(mktime()).",".$user->id.",'".$this->numero."','".$this->intitule."')";

		  $resql = $this->db->query($sql);
		  if ( $resql )
		    {
		      $id = $this->db->last_insert_id(MAIN_DB_PREFIX."compta_compte_generaux");

		      if ($id > 0)
			{
			  $this->id = $id;
			  $result = 0;
			}
		      else
			{
			  $result = -2;
			  dol_syslog("ComptaCompte::Create Erreur $result lecture ID");
			}
		    }
		  else
		    {
		      $result = -1;
		      dol_syslog("ComptaCompte::Create Erreur $result INSERT Mysql");
		    }
		}
	      else
		{
		  $result = -3;
		  dol_syslog("ComptaCompte::Create Erreur $result SELECT Mysql");
		}
	    }
	  else
	    {
	      $result = -5;
	      dol_syslog("ComptaCompte::Create Erreur $result SELECT Mysql");
	    }
	}
      else
	{
	  $result = -4;
	  dol_syslog("ComptaCompte::Create Erreur  $result Valeur Manquante");
	}

      return $result;
    }
}
?>
