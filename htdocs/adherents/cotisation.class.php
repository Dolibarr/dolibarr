<?php
/* Copyright (C) 2002-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2006-2007 Laurent Destailleur  <eldy@users.sourceforge.net>
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
		\file 		htdocs/adherents/cotisation.class.php
        \ingroup    adherent
		\brief      Fichier de la classe permettant de gèrer les cotisations
		\version    $Revision$
*/


/**
	\class 		Cotisation
	\brief      Classe permettant de gèrer les cotisations
*/
class Cotisation
{
	var $id;
	var $db;
	var $error;
	var $errors;

	var $datec;
	var $datem;
	var $dateh;
	var $fk_adherent;
	var $amount;
	var $note;
	var $fk_bank;

	
	/**
			\brief Cotisation
			\param DB				Handler base de données
	*/
	function Cotisation($DB)
	{
		$this->db = $DB;
	}


	/**
		\brief 		Fonction qui permet de créer le don
		\param 		userid		userid de celui qui insere
		\return		int			<0 si KO, Id cotisation créé si OK
	*/
	function create($userid)
	{
        $sql = "INSERT INTO ".MAIN_DB_PREFIX."cotisation (fk_adherent, datec, dateadh, cotisation, note)";
        $sql .= " VALUES (".$this->fk_adherent.", now(), ".$this->db->idate($this->dateh).", ".$this->amount.",'".$this->note."')";

		dolibarr_syslog("Cotisation::create sql=".$sql);
		$result = $this->db->query($sql);
		if ($result)
		{
			return $this->db->last_insert_id(MAIN_DB_PREFIX."cotisation");
		}
		else
		{
			$this->error=$this->db->error().' sql='.$sql;
			return -1;
		}
	}

	/*!
	\TODO A ecrire
	\brief fonction qui permet de mettre à jour le don
	\param userid			userid de l'adhérent
	*/
	function update($userid)
	{

		$this->date = $this->db->idate($this->date);

		$sql = "UPDATE ".MAIN_DB_PREFIX."don SET ";
		$sql .= "amount = " . $this->amount;
		$sql .= ",fk_paiement = ".$this->modepaiementid;
		$sql .= ",prenom = '".$this->prenom ."'";
		$sql .= ",nom='".$this->nom."'";
		$sql .= ",societe='".$this->societe."'";
		$sql .= ",adresse='".$this->adresse."'";
		$sql .= ",cp='".$this->cp."'";
		$sql .= ",ville='".$this->ville."'";
		$sql .= ",pays='".$this->pays."'";
		$sql .= ",public=".$this->public;
		$sql .= ",fk_don_projet=".$this->projetid;
		$sql .= ",note='".$this->commentaire."'";
		$sql .= ",datedon='".$this->date."'";
		$sql .= ",email='".$this->email."'";
		$sql .= ",fk_statut=".$this->statut;

		$sql .= " WHERE rowid = $this->id";

		$result = $this->db->query($sql);

		if ($result)
		{
			return 1;
		}
		else
		{
			dolibarr_print_error($this->db);
			return 0;
		}
	}

	/**
			\brief		Fonction qui permet de supprimer la cotisation
			\param 		rowid	Id cotisation
			\return		int		<0 si KO, 0 si OK mais non trouve, >0 si OK
	*/
	function delete($rowid)
	{
		$sql = "DELETE FROM ".MAIN_DB_PREFIX."cotisation WHERE rowid = ".$rowid;

		dolibarr_syslog("Cotisation::delete sql=".$sql);
		$resql=$this->db->query($sql);
		if ($resql)
		{
			if ( $this->db->affected_rows($resql))
			{
				return 1;
			}
			else
			{
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
		\brief 		Fonction qui permet de récupèrer une cotisation
		\param 		rowid		Id cotisation
		\return		int			<0 si KO, =0 si OK mais non trouve, >0 si OK
	*/
	function fetch($rowid)
	{
        $sql="SELECT rowid, fk_adherent, datec, tms, dateadh, cotisation, note, fk_bank";
		$sql.=" FROM ".MAIN_DB_PREFIX."cotisation";
		$sql.="	WHERE rowid=".$rowid;

		dolibarr_syslog("Cotisation::fetch sql=".$sql);
		$resql=$this->db->query($sql);
		if ($resql)
		{
			if ($this->db->num_rows($resql))
			{
				$obj = $this->db->fetch_object($resql);

				$this->id             = $obj->rowid;
				$this->ref            = $obj->rowid;

				$this->fk_adherent    = $obj->fk_adherent;
				$this->datec          = $obj->datec;
				$this->datem          = $obj->tms;
				$this->dateh          = $obj->dateadh;
				$this->amount         = $obj->cotisation;
				$this->note           = $obj->note;
				$this->fk_bank        = $obj->fk_bank;
				return 1;
			}
			else
			{
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
	\TODO a ecrire
	\brief fonction qui permet de mettre un commentaire sur le don
	\param	rowid
	\param	commentaire
	*/
	function set_commentaire($rowid, $commentaire='')
	{
		$sql = "UPDATE ".MAIN_DB_PREFIX."don SET note = '$commentaire'";

		$sql .=  " WHERE rowid = $rowid ;";

		if ( $this->db->query( $sql) )
		{
			if ( $this->db->affected_rows() )
			{
				return 1;
			}
			else
			{
				return 0;
			}
		}
		else
		{
			dolibarr_print_error($this->db);
			return 0;
		}
	}


	/**
	 *    	\brief      Renvoie nom clicable (avec eventuellement le picto)
	 *		\param		withpicto		0=Pas de picto, 1=Inclut le picto dans le lien, 2=Picto seul
	 *		\return		string			Chaine avec URL
	 */
	function getNomUrl($withpicto=0)
	{
		global $langs;
		
		$result='';

		$lien = '<a href="'.DOL_URL_ROOT.'/adherents/fiche_subscription.php?rowid='.$this->id.'">';
		$lienfin='</a>';
		
		$picto='payment';
		$label=$langs->trans("ShowSubscription");
		
		if ($withpicto) $result.=($lien.img_object($label,$picto).$lienfin);
		if ($withpicto && $withpicto != 2) $result.=' ';
		$result.=$lien.$this->ref.$lienfin;
		return $result;
	}
}
?>
