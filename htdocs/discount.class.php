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
 */

/**
 *		\file       htdocs/discount.class.php
 * 		\ingroup    propal facture commande
 *		\brief      Fichier de la classe de gestion des remises
 *		\version    $Id$
 */


/**
 *		\class      DiscountAbsolute
 *		\brief      Classe permettant la gestion des remises fixes
 */
class DiscountAbsolute
{
	var $db;
	var $error;

	var $id;					// Id remise
	var $amount_ht;				//
	var $amount_tva;			//
	var $amount_ttc;			//
	var $tva_tx;				//
	var $fk_user;				// Id utilisateur qui accorde la remise
	var $description;			// Description libre
	var $datec;					// Date creation
	var $fk_facture_line;  		// Id invoice line when a discount linked to invoice line
	var $fk_facture;			// Id invoice when a discoutn linked to invoice
	var $fk_facture_source;		// Id facture avoir � l'origine de la remise
	var $ref_facture_source;	// Ref facture avoir � l'origine de la remise

	/**
	 *    \brief  Constructeur de la classe
	 *    \param  DB          handler acc�s base de donn�es
	 */
	function DiscountAbsolute($DB)
	{
		$this->db = $DB;
	}


	/**
	 *    	\brief      Charge objet remise depuis la base
	 *    	\param      rowid       		id du projet � charger
	 *    	\param      fk_facture_source	fk_facture_source
	 *		\return		int					<0 si ko, =0 si non trouv�, >0 si ok
	 */
	function fetch($rowid,$fk_facture_source=0)
	{
		// Check parameters
		if (! $rowid && ! $fk_facture_source)
		{
			$this->error='ErrorBadParameters';
			return -1;
		}

		$sql = "SELECT sr.rowid, sr.fk_soc,";
		$sql.= " sr.fk_user,";
		$sql.= " sr.amount_ht, sr.amount_tva, sr.amount_ttc, sr.tva_tx,";
		$sql.= " sr.fk_facture_line, sr.fk_facture, sr.fk_facture_source, sr.description,";
		$sql.= " ".$this->db->pdate("sr.datec")." as datec,";
		$sql.= " f.facnumber as ref_facture_source";
		$sql.= " FROM ".MAIN_DB_PREFIX."societe_remise_except as sr";
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."facture as f ON sr.fk_facture_source = f.rowid";
		$sql.= " WHERE";
		if ($rowid) $sql.= " sr.rowid=".$rowid;
		if ($fk_facture_source) $sql.= " sr.fk_facture_source=".$fk_facture_source;

		dol_syslog("DiscountAbsolute::fetch sql=".$sql);
 		$resql = $this->db->query($sql);
		if ($resql)
		{
			if ($this->db->num_rows($resql))
			{
				$obj = $this->db->fetch_object($resql);

				$this->id = $obj->rowid;
				$this->fk_soc = $obj->fk_soc;
				$this->amount_ht = $obj->amount_ht;
				$this->amount_tva = $obj->amount_tva;
				$this->amount_ttc = $obj->amount_ttc;
				$this->tva_tx = $obj->tva_tx;
				$this->fk_user = $obj->fk_user;
				$this->fk_facture_line = $obj->fk_facture_line;
				$this->fk_facture = $obj->fk_facture;
				$this->fk_facture_source = $obj->fk_facture_source;		// Id avoir source
				$this->ref_facture_source = $obj->ref_facture_source;	// Ref avoir source
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

		// Nettoyage parametres
		$this->amount_ht=price2num($this->amount_ht);
		$this->amount_tva=price2num($this->amount_tva);
		$this->amount_ttc=price2num($this->amount_ttc);
		$this->tva_tx=price2num($this->tva_tx);

        // Insert request
		$sql = "INSERT INTO ".MAIN_DB_PREFIX."societe_remise_except";
		$sql.= " (datec, fk_soc, fk_user, description,";
		$sql.= " amount_ht, amount_tva, amount_ttc, tva_tx,";
		$sql.= " fk_facture_source";
		$sql.= ")";
		$sql.= " VALUES (".$this->db->idate(mktime()).", ".$this->fk_soc.", ".$user->id.", '".addslashes($this->desc)."',";
		$sql.= " ".$this->amount_ht.", ".$this->amount_tva.", ".$this->amount_ttc.", ".$this->tva_tx.",";
		$sql.= " ".($this->fk_facture_source?"'".$this->fk_facture_source."'":"null");
		$sql.= ")";

	   	dol_syslog("DiscountAbsolute::create sql=".$sql);
		$resql=$this->db->query($sql);
		if ($resql)
		{
			$this->id=$this->db->last_insert_id(MAIN_DB_PREFIX."societe_remise_except");
			return $this->id;
		}
		else
		{
            $this->error=$this->db->lasterror().' - sql='.$sql;
            dol_syslog("DiscountAbsolute::create ".$this->error);
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

		$this->db->begin();

		$sql = "DELETE FROM ".MAIN_DB_PREFIX."societe_remise_except ";
		$sql.= " WHERE rowid = ".$this->id." AND (fk_facture_line IS NULL or fk_facture IS NULL)";

	   	dol_syslog("DiscountAbsolute::delete Delete discount sql=".$sql);
		$result=$this->db->query($sql);
		if ($result)
		{
			// If source of discount was a credit not, we change credit note statut.
			if ($this->fk_facture_source)
			{
				$sql = "UPDATE ".MAIN_DB_PREFIX."facture";
				$sql.=" set paye=0, fk_statut=1";
				$sql.=" WHERE (type = 2 or type = 3) AND rowid=".$this->fk_facture_source;

			   	dol_syslog("DiscountAbsolute::delete Update credit note or deposit invoice statut sql=".$sql);
				$result=$this->db->query($sql);
				if ($result)
				{
					$this->db->commit();
					return 1;
				}
				else
				{
					$this->error=$this->db->lasterror();
					$this->db->rollback();
					return -1;
				}
			}
			else
			{
				$this->db->commit();
				return 1;
			}
		}
		else
		{
			$this->error=$this->db->lasterror();
			$this->db->rollback();
			return -1;
		}
	}



	/**
	*		\brief		Link the discount to a particular invoice line or a particular invoice
	*		\param		rowidline		Invoice line id
	*		\param		rowidinvoice	Invoice id
	*		\return		int				<0 ko, >0 ok
	*/
	function link_to_invoice($rowidline,$rowidinvoice)
	{
		// Check parameters
		if (! $rowidline && ! $rowidinvoice)
		{
			$this->error='ErrorBadParameters';
			return -1;
		}
		if ($rowidline && $rowidinvoice)
		{
			$this->error='ErrorBadParameters';
			return -2;
		}

		$sql ="UPDATE ".MAIN_DB_PREFIX."societe_remise_except";
		if ($rowidline)    $sql.=" SET fk_facture_line = ".$rowidline;
		if ($rowidinvoice) $sql.=" SET fk_facture = ".$rowidinvoice;
		$sql.=" WHERE rowid = ".$this->id;

		dol_syslog("DiscountAbsolute::link_to_invoice sql=".$sql,LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql)
		{
			return 1;
		}
		else
		{
			$this->error=$this->db->error();
			dol_syslog("DiscountAbsolute::link_to_invoice ".$this->error,LOG_ERR);
			return -3;
		}
	}


	/**
	*		\brief		Link the discount to a particular invoice line or a particular invoice
	*		\remarks	Do not call this if discount is linked to a reconcialiated invoice
	*		\param		rowidline			Invoice line id
	*		\param		rowidinvoice		Invoice id
	*		\return		int					<0 if KO, >0 if OK
	*/
	function unlink_invoice()
	{
		$sql ="UPDATE ".MAIN_DB_PREFIX."societe_remise_except";
		$sql.=" SET fk_facture_line = NULL, fk_facture = NULL";
		$sql.=" WHERE rowid = ".$this->id;

		dol_syslog("DiscountAbsolute::unlink_invoice sql=".$sql,LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql)
		{
			return 1;
		}
		else
		{
			$this->error=$this->db->error();
			dol_syslog("DiscountAbsolute::link_to_invoice ".$this->error,LOG_ERR);
			return -3;
		}
	}


	/**
	 *    	\brief      Renvoie montant TTC des reductions/avoirs en cours disponibles
	 *		\param		company		Object third party for filter
	 *		\param		user		Filtre sur un user auteur des remises
	 * 		\param		filter		Filtre autre
	 * 		\param		maxvalue	Filter on max value for discount
	 * 		\return		int			<0 si ko, montant avoir sinon
	 */
	function getAvailableDiscounts($company='', $user='',$filter='', $maxvalue=0)
	{
        $sql  = "SELECT SUM(rc.amount_ttc) as amount";
//        $sql  = "SELECT rc.amount_ttc as amount";
		$sql.= " FROM ".MAIN_DB_PREFIX."societe_remise_except as rc";
        $sql.= " WHERE (rc.fk_facture IS NULL AND rc.fk_facture_line IS NULL)";	// Available
		if (is_object($company)) $sql.= " AND rc.fk_soc = ".$company->id;
        if (is_object($user))    $sql.= " AND rc.fk_user = ".$user->id;
        if ($filter)   $sql.=' AND '.$filter;
        if ($maxvalue) $sql.=' AND rc.amount_ttc <= '.price2num($maxvalue);

        dol_syslog("DiscountAbsolute::getAvailableDiscounts sql=".$sql,LOG_DEBUG);
        $resql=$this->db->query($sql);
        if ($resql)
        {
            $obj = $this->db->fetch_object($resql);
        	//while ($obj)
            //{
            	//print 'zz'.$obj->amount;
            	//$obj = $this->db->fetch_object($resql);
            //}
            return $obj->amount;
        }
		return -1;
	}


	/**
	 *    	\brief      Return amount (with tax) of all credit notes and deposits invoices used by invoice
	 *		\return		int			<0 if KO, Sum of credit notes and deposits amount otherwise
	 */
	function getSumCreditNotesUsed($invoice)
	{
		$sql = 'SELECT sum(rc.amount_ttc) as amount';
		$sql.= ' FROM '.MAIN_DB_PREFIX.'societe_remise_except as rc, '.MAIN_DB_PREFIX.'facture as f';
		$sql.= ' WHERE rc.fk_facture_source=f.rowid AND rc.fk_facture = '.$invoice->id;
		$sql.= ' AND f.type = 2';

        dol_syslog("DiscountAbsolute::getSumCreditNotesUsed sql=".$sql,LOG_DEBUG);
		$resql=$this->db->query($sql);
		if ($resql)
		{
			$obj = $this->db->fetch_object($resql);
			return $obj->amount;
		}
		else
		{
			return -1;
		}
	}

	/**
	 *    	\brief      Return amount (with tax) of all deposits invoices used by invoice
	 *		\return		int			<0 if KO, Sum of credit notes and deposits amount otherwise
	 */
	function getSumDepositsUsed($invoice)
	{
		$sql = 'SELECT sum(rc.amount_ttc) as amount';
		$sql.= ' FROM '.MAIN_DB_PREFIX.'societe_remise_except as rc, '.MAIN_DB_PREFIX.'facture as f';
		$sql.= ' WHERE rc.fk_facture_source=f.rowid AND rc.fk_facture = '.$invoice->id;
		$sql.= ' AND f.type = 3';

        dol_syslog("DiscountAbsolute::getSumDepositsUsed sql=".$sql,LOG_DEBUG);
		$resql=$this->db->query($sql);
		if ($resql)
		{
			$obj = $this->db->fetch_object($resql);
			return $obj->amount;
		}
		else
		{
			return -1;
		}
	}

	/**
	 *	\brief      Return clicable ref of object (with picto or not)
	 *	\param		withpicto		0=Pas de picto, 1=Inclut le picto dans le lien, 2=Picto seul
	 *	\param		option			Sur quoi pointe le lien
	 *	\return		string			Chaine avec URL
	 */
	function getNomUrl($withpicto,$option='invoice')
	{
		global $langs;

		$result='';

		if ($option == 'invoice')
		{
			$lien = '<a href="'.DOL_URL_ROOT.'/compta/facture.php?facid='.$this->fk_facture_source.'">';
			$lienfin='</a>';
			$label=$langs->trans("ShowDiscount").': '.$this->ref_facture_source;
			$ref=$this->ref_facture_source;
			$picto='bill';
		}
		if ($option == 'discount')
		{
			$lien = '<a href="'.DOL_URL_ROOT.'/comm/remx.php?id='.$this->fk_soc.'">';
			$lienfin='</a>';
			$label=$langs->trans("Discount");
			$ref=$langs->trans("Discount");
			$picto='generic';
		}


		if ($withpicto) $result.=($lien.img_object($label,$picto).$lienfin);
		if ($withpicto && $withpicto != 2) $result.=' ';
		$result.=$lien.$ref.$lienfin;
		return $result;
	}

}
?>
