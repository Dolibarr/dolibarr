<?php
/* Copyright (C) 2005      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2007 Laurent Destailleur  <eldy@users.sourceforge.net>
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
		\file       htdocs/discount.class.php
		\ingroup    propal,facture,commande
		\brief      Fichier de la classe de gestion des remises
		\version    $Revision$
*/


/**
		\class      DiscountAbsolute
		\brief      Classe permettant la gestion des remises fixes
*/

class DiscountAbsolute
{
	var $id;
	var $db;


	/**
	 *    \brief  Constructeur de la classe
	 *    \param  DB          handler accès base de données
	 */
	function DiscountAbsolute($DB)
	{
		$this->db = $DB;
	}

	
	/**
	 *    	\brief      Charge objet remise depuis la base
	 *    	\param      rowid       id du projet à charger
	 *		\return		int			<0 si ko, =0 si non trouvé, >0 si ok
	 */
	function fetch($rowid)
	{
		$sql = "SELECT fk_soc, amount_ht, fk_user, fk_facture, description,";
		$sql.= " ".$this->db->pdate("datec")." as datec";
		$sql.= " FROM ".MAIN_DB_PREFIX."societe_remise_except";
		$sql.= " WHERE rowid=".$rowid;
	
		dolibarr_syslog("DiscountAbsolute::fetch sql=".$sql);
 		$resql = $this->db->query($sql);
		if ($resql)
		{
			if ($this->db->num_rows($resql))
			{
				$obj = $this->db->fetch_object($resql);
	
				$this->id = $rowid;
				$this->fk_soc = $obj->fk_soc;
				$this->amount_ht = $obj->amount_ht;
				$this->fk_user = $obj->fk_user;
				$this->fk_facture = $obj->fk_facture;
				$this->description = $obj->description;
				$this->datec = $obj->datec;
	
				$this->db->free($resql);
				return 1;
			}
			else
			{
				$this->db->free($resql);
				return 0;
			}
		}
		else
		{
			$this->error=$this->db->error();
			return -1;
		}
	}
	

    /**
     *      \brief      Create in database
     *      \param      user        User that create
     *      \return     int         <0 si ko, >0 si ok
     */
    function create($user)
    {
    	global $conf, $langs;
    	
        // Insert request
		$sql  = "INSERT INTO ".MAIN_DB_PREFIX."societe_remise_except ";
		$sql .= " (datec, fk_soc, amount_ht, fk_user, description)";
		$sql .= " VALUES (now(),".$this->fk_soc.",'".$this->amount_ht."',".$user->id.",'".addslashes($this->desc)."')";

	   	dolibarr_syslog("DiscountAbsolute::create sql=".$sql);
		$resql=$this->db->query($sql);
		if ($resql)
		{
			$rowid=$this->db->last_insert_id(MAIN_DB_PREFIX."societe_remise_except");
			return $rowid;
		}
		else
		{
            $this->error=$this->db->lasterror();
            dolibarr_syslog("Skeleton_class::create ".$this->error);
            return -1;
		}
    }


				 	/*
	*   \brief      Delete object in database
	*	\return		int			<0 if KO, >0 if OK
	*/
	function delete()
	{
		global $conf, $langs;
	
		$sql = "DELETE FROM ".MAIN_DB_PREFIX."societe_remise_except ";
		$sql.= " WHERE rowid = ".$this->id." AND fk_facture IS NULL";

	   	dolibarr_syslog("DiscountAbsolute::delete sql=".$sql);
		if (! $this->db->query($sql))
		{
			$this->error=$this->db->lasterror().' sql='.$sql;
			return -1;
		}
		else
		{
			return 1;
		}
	}
	

	
	/**
	*		\brief		Link the discount to a particular invoice
	*		\param		rowid		Invoice id
	*		\return		int			<0 ko, >0 ok
	*/
	function link_to_invoice($rowid)
	{
		dolibarr_syslog("Discount.class::link_to_invoice link discount ".$this->id." to invoice rowid=".$rowid);

		$sql ="UPDATE ".MAIN_DB_PREFIX."societe_remise_except";
		$sql.=" SET fk_facture = ".$rowid;
		$sql.=" WHERE rowid = ".$this->id;
		$resql = $this->db->query($sql);
		if ($resql)
		{
			return 1;
		}
		else
		{
			$this->error=$this->db->error();
			dolibarr_syslog("Discount.class::link_to_invoice ".$this->error." sql=".$sql);
			return -1;
		}
	}
	
}
?>
