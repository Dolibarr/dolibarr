<?php
/* Copyright (C) 2002      Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
 *      \file       htdocs/compta/sociales/class/chargesociales.class.php
 *		\ingroup    facture
 *		\brief      Fichier de la classe des charges sociales
 *		\version    $Id$
 */

require_once(DOL_DOCUMENT_ROOT."/core/class/commonobject.class.php");


/**     \class      ChargeSociales
 *		\brief      Classe permettant la gestion des paiements des charges
 *                  La tva collectee n'est calculee que sur les factures payees.
 */
class ChargeSociales extends CommonObject
{
	var $db;
	var $error;
	var $element='rowid';
	var $table_element='chargesociales';

	var $id;
	var $date_ech;
	var $lib;
	var $type;
	var $type_libelle;
	var $amount;
	var $paye;
	var $periode;


	function ChargeSociales($DB)
	{
		$this->db = $DB;

		return 1;
	}

	/**
	 *   \brief      Retrouve et charge une charge sociale
	 *   \return     int     1 si trouve, 0 sinon
	 */
	function fetch($id)
	{
		$sql = "SELECT cs.rowid, cs.date_ech,";
		$sql.= " cs.libelle as lib, cs.fk_type, cs.amount, cs.paye, cs.periode,";
		$sql.= " c.libelle";
		$sql.= " FROM ".MAIN_DB_PREFIX."chargesociales as cs, ".MAIN_DB_PREFIX."c_chargesociales as c";
		$sql.= " WHERE cs.fk_type = c.id";
		$sql.= " AND cs.rowid = ".$id;

		dol_syslog("ChargesSociales::fetch sql=".$sql);
		$resql=$this->db->query($sql);
		if ($resql)
		{
			if ($this->db->num_rows($resql))
			{
				$obj = $this->db->fetch_object($resql);

				$this->id             = $obj->rowid;
				$this->ref            = $obj->rowid;
				$this->date_ech       = $this->db->jdate($obj->date_ech);
				$this->lib            = $obj->lib;
				$this->type           = $obj->fk_type;
				$this->type_libelle   = $obj->libelle;
				$this->amount         = $obj->amount;
				$this->paye           = $obj->paye;
				$this->periode        = $this->db->jdate($obj->periode);

				return 1;
			}
			else
			{
				return 0;
			}
			$this->db->free($resql);
		}
		else
		{
			$this->error=$this->db->error();
			return -1;
		}
	}


	/**
	 *      \brief      Create a social contribution in database
	 *      \param      user    User making creation
	 *      \return     int     <0 if KO, id if OK
	 */
	function create($user)
	{
		// Nettoyage parametres
		$newamount=price2num($this->amount,'MT');

		// Validation parametres
		if (! $newamount > 0)
		{
			$this->error="ErrorBadParameter";
			return -2;
		}

		$this->db->begin();

		$sql = "INSERT INTO ".MAIN_DB_PREFIX."chargesociales (fk_type, libelle, date_ech, periode, amount)";
		$sql.= " VALUES (".$this->type.",'".addslashes($this->lib)."',";
		$sql.= " '".$this->db->idate($this->date_ech)."','".$this->db->idate($this->periode)."',";
		$sql.= " ".price2num($newamount);
		$sql.= ")";

		dol_syslog("ChargesSociales::create sql=".$sql);
		$resql=$this->db->query($sql);
		if ($resql)
		{
			$this->id=$this->db->last_insert_id(MAIN_DB_PREFIX."chargesociales");

			//dol_syslog("ChargesSociales::create this->id=".$this->id);
			$this->db->commit();
			return $this->id;
		}
		else
		{
			$this->error=$this->db->error();
			$this->db->rollback();
			return -1;
		}
	}


	/**
	 *      \brief      Efface un charge sociale
	 *      \param      user    Utilisateur qui cree le paiement
	 *      \return     int     <0 si erreur, >0 si ok
	 */
	function delete($user)
	{
		$sql = "DELETE FROM ".MAIN_DB_PREFIX."chargesociales where rowid='".$this->id."'";

		dol_syslog("ChargesSociales::delete sql=".$sql);
		$resql=$this->db->query($sql);
		if ($resql)
		{
			return 1;
		}
		else
		{
			$this->error=$this->db->error();
			return -1;
		}
	}


	/**
	 *      \brief      Met a jour une charge sociale
	 *      \param      user    Utilisateur qui modifie
	 *      \return     int     <0 si erreur, >0 si ok
	 */
	function update($user)
	{
		$this->db->begin();

		$sql = "UPDATE ".MAIN_DB_PREFIX."chargesociales";
		$sql.= " SET libelle='".addslashes($this->lib)."',";
		$sql.= " date_ech='".$this->db->idate($this->date_ech)."',";
		$sql.= " periode='".$this->db->idate($this->periode)."'";
		$sql.= " WHERE rowid=".$this->id;

		dol_syslog("ChargesSociales::update sql=".$sql);
		$resql=$this->db->query($sql);
		if ($resql)
		{
			$this->db->commit();
			return 1;
		}
		else
		{
			$this->error=$this->db->error();
			$this->db->rollback();
			return -1;
		}
	}


	function solde($year = 0)
	{
		$sql = "SELECT sum(f.amount) as amount";
		$sql .= " FROM ".MAIN_DB_PREFIX."chargesociales as f WHERE paye = 0";

		if ($year) {
			$sql .= " AND f.datev >= '$y-01-01' AND f.datev <= '$y-12-31' ";
		}

		$result = $this->db->query($sql);
		if ($result) {
			if ($this->db->num_rows($result)) {
				$obj = $this->db->fetch_object($result);
				return $obj->amount;
			} else {
				return 0;
			}

			$this->db->free();

		} else {
			print $this->db->error();
			return -1;
		}
	}

	/**
	 *    \brief      Tag la charge comme payee completement
	 *    \param      rowid       id de la ligne a modifier
	 */
	function set_paid($rowid)
	{
		$sql = "UPDATE ".MAIN_DB_PREFIX."chargesociales set paye=1 WHERE rowid = ".$rowid;
		$return = $this->db->query( $sql);
	}

	/**
	 *    \brief      Retourne le libelle du statut d'une charge (impaye, payee)
	 *    \param      mode          0=libelle long, 1=libelle court, 2=Picto + Libelle court, 3=Picto, 4=Picto + Libelle long
	 *    \return     string        Libelle
	 */
	function getLibStatut($mode=0)
	{
		return $this->LibStatut($this->paye,$mode);
	}

	/**
	 *    	\brief      Renvoi le libelle d'un statut donne
	 *    	\param      statut        	Id statut
	 *    	\param      mode          	0=libelle long, 1=libelle court, 2=Picto + Libelle court, 3=Picto, 4=Picto + Libelle long, 5=Libelle court + Picto
	 *    	\return     string        	Libelle du statut
	 */
	function LibStatut($statut,$mode=0)
	{
		global $langs;
		$langs->load('customers');

		if ($mode == 0)
		{
			if ($statut ==  0) return $langs->trans("Unpaid");
			if ($statut ==  1) return $langs->trans("Paid");
		}
		if ($mode == 1)
		{
			if ($statut ==  0) return $langs->trans("Unpaid");
			if ($statut ==  1) return $langs->trans("Paid");
		}
		if ($mode == 2)
		{
			if ($statut ==  0) return img_picto($langs->trans("Unpaid"), 'statut1').' '.$langs->trans("Unpaid");
			if ($statut ==  1) return img_picto($langs->trans("Paid"), 'statut6').' '.$langs->trans("Paid");
		}
		if ($mode == 3)
		{
			if ($statut ==  0) return img_picto($langs->trans("Unpaid"), 'statut1');
			if ($statut ==  1) return img_picto($langs->trans("Paid"), 'statut6');
		}
		if ($mode == 4)
		{
			if ($statut ==  0) return img_picto($langs->trans("Unpaid"), 'statut1').' '.$langs->trans("Unpaid");
			if ($statut ==  1) return img_picto($langs->trans("Paid"), 'statut6').' '.$langs->trans("Paid");
		}
		if ($mode == 5)
		{
			if ($statut ==  0) return $langs->trans("Unpaid").' '.img_picto($langs->trans("Unpaid"), 'statut1');
			if ($statut ==  1) return $langs->trans("Paid").' '.img_picto($langs->trans("Paid"), 'statut6');
		}

		return "Error, mode/status not found";
	}


	/**
	 *    	\brief      Renvoie nom clicable (avec eventuellement le picto)
	 *		\param		withpicto		0=Pas de picto, 1=Inclut le picto dans le lien, 2=Picto seul
	 * 		\param		maxlen			Longueur max libelle
	 *		\return		string			Chaine avec URL
	 */
	function getNomUrl($withpicto=0,$maxlen=0)
	{
		global $langs;

		$result='';

		if (empty($this->ref)) $this->ref=$this->lib;

		$lien = '<a href="'.DOL_URL_ROOT.'/compta/sociales/charges.php?id='.$this->id.'">';
		$lienfin='</a>';

		if ($withpicto) $result.=($lien.img_object($langs->trans("ShowSocialContribution").': '.$this->lib,'bill').$lienfin.' ');
		if ($withpicto && $withpicto != 2) $result.=' ';
		if ($withpicto != 2) $result.=$lien.($maxlen?dol_trunc($this->ref,$maxlen):$this->ref).$lienfin;
		return $result;
	}

	/**
	 * 	\brief     	Return amount aof payments already done
	 *	\return		int		Amount of payment already done, <0 if KO
	 */
	function getSommePaiement()
	{
		$table='paiementcharge';
		$field='fk_charge';

		$sql = 'SELECT sum(amount) as amount';
		$sql.= ' FROM '.MAIN_DB_PREFIX.$table;
		$sql.= ' WHERE '.$field.' = '.$this->id;

		dol_syslog("ChargeSociales::getSommePaiement sql=".$sql, LOG_DEBUG);
		$resql=$this->db->query($sql);
		if ($resql)
		{
			$obj = $this->db->fetch_object($resql);
			$this->db->free($resql);
			return $obj->amount;
		}
		else
		{
			return -1;
		}
	}
}


/**     \class      PaiementCharge
 *		\brief      Classe permettant la gestion des paiements des charges
 */
class PaiementCharge extends CommonObject
{
	var $db;							//!< To store db handler
	var $error;							//!< To return error code (or message)
	var $errors=array();				//!< To return several error codes (or messages)
	var $element='paiementcharge';			//!< Id that identify managed objects
	var $table_element='paiementcharge';	//!< Name of table without prefix where object is stored

	var $id;
	var $ref;

	var $fk_charge;
	var $datec='';
	var $tms='';
	var $datep='';
	var $amount;
	var $fk_typepaiement;
	var $num_paiement;
	var $note;
	var $fk_bank;
	var $fk_user_creat;
	var $fk_user_modif;

	/**
	 *      \brief      Constructor
	 *      \param      DB      Database handler
	 */
	function Paiementcharge($DB)
	{
		$this->db = $DB;
		return 1;
	}

	/**
	 *      \brief      Creation d'un paiement de charge sociale dans la base
	 *      \param      user    Utilisateur qui cree le paiement
	 *      \return     int     <0 si KO, id du paiement cree si OK
	 */
	function create($user)
	{
		global $conf, $langs;
		$error=0;

		// Validation parametres
		if (! $this->datepaye)
		{
			$this->error='ErrorBadValueForParameters';
			return -1;
		}

		$now=dol_now();

		// Clean parameters
		if (isset($this->fk_charge)) $this->fk_charge=trim($this->fk_charge);
		if (isset($this->amount)) $this->amount=trim($this->amount);
		if (isset($this->fk_typepaiement)) $this->fk_typepaiement=trim($this->fk_typepaiement);
		if (isset($this->num_paiement)) $this->num_paiement=trim($this->num_paiement);
		if (isset($this->note)) $this->note=trim($this->note);
		if (isset($this->fk_bank)) $this->fk_bank=trim($this->fk_bank);
		if (isset($this->fk_user_creat)) $this->fk_user_creat=trim($this->fk_user_creat);
		if (isset($this->fk_user_modif)) $this->fk_user_modif=trim($this->fk_user_modif);

		$this->db->begin();

		$total=0;
		foreach ($this->amounts as $key => $value)
		{
			$facid = $key;
			$amount = price2num(trim($value), 'MT');
			$total += $amount;
		}

		if ($total != 0)
		{
			$sql = "INSERT INTO ".MAIN_DB_PREFIX."paiementcharge (fk_charge, datec, datep, amount,";
			$sql.= " fk_typepaiement, num_paiement, note, fk_user_creat, fk_bank)";
			$sql.= " VALUES ($this->chid, '".$this->db->idate($now)."', ";
			$sql.= " '".$this->db->idate($this->datepaye)."', ";
			$sql.= price2num($total);
			$sql.= ", ".$this->paiementtype.", '".addslashes($this->num_paiement)."', '".addslashes($this->note)."', ".$user->id.",";
			$sql.= "0)";

			dol_syslog("PaiementCharge::create sql=".$sql);
			$resql=$this->db->query($sql);
			if ($resql)
			{
				$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX."paiementcharge");
			}
			else
			{
				$error++;
			}

		}

		if ($total != 0 && ! $error)
		{
			$this->db->commit();
			return $this->id;
		}
		else
		{
			$this->error=$this->db->error();
			dol_syslog("PaiementCharges::create ".$this->error, LOG_ERR);
			$this->db->rollback();
			return -1;
		}
	}

	/**
	 *    \brief      Load object in memory from database
	 *    \param      id          id object
	 *    \return     int         <0 if KO, >0 if OK
	 */
	function fetch($id)
	{
		global $langs;
		$sql = "SELECT";
		$sql.= " t.rowid,";
		$sql.= " t.fk_charge,";
		$sql.= " t.datec,";
		$sql.= " t.tms,";
		$sql.= " t.datep,";
		$sql.= " t.amount,";
		$sql.= " t.fk_typepaiement,";
		$sql.= " t.num_paiement,";
		$sql.= " t.note,";
		$sql.= " t.fk_bank,";
		$sql.= " t.fk_user_creat,";
		$sql.= " t.fk_user_modif,";
		$sql.= " pt.code as type_code, pt.libelle as type_libelle,";
		$sql.= ' b.fk_account';
		$sql.= " FROM (".MAIN_DB_PREFIX."paiementcharge as t, ".MAIN_DB_PREFIX."c_paiement as pt)";
		$sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'bank as b ON t.fk_bank = b.rowid';
		$sql.= " WHERE t.rowid = ".$id." AND t.fk_typepaiement = pt.id";

		dol_syslog(get_class($this)."::fetch sql=".$sql, LOG_DEBUG);
		$resql=$this->db->query($sql);
		if ($resql)
		{
			if ($this->db->num_rows($resql))
			{
				$obj = $this->db->fetch_object($resql);

				$this->id    = $obj->rowid;
				$this->ref   = $obj->rowid;

				$this->fk_charge = $obj->fk_charge;
				$this->datec = $this->db->jdate($obj->datec);
				$this->tms = $this->db->jdate($obj->tms);
				$this->datep = $this->db->jdate($obj->datep);
				$this->amount = $obj->amount;
				$this->fk_typepaiement = $obj->fk_typepaiement;
				$this->num_paiement = $obj->num_paiement;
				$this->note = $obj->note;
				$this->fk_bank = $obj->fk_bank;
				$this->fk_user_creat = $obj->fk_user_creat;
				$this->fk_user_modif = $obj->fk_user_modif;

				$this->type_code = $obj->type_code;
				$this->type_libelle = $obj->type_libelle;

				$this->bank_account   = $obj->fk_account;
				$this->bank_line      = $obj->fk_bank;
			}
			$this->db->free($resql);

			return 1;
		}
		else
		{
			$this->error="Error ".$this->db->lasterror();
			dol_syslog(get_class($this)."::fetch ".$this->error, LOG_ERR);
			return -1;
		}
	}


	/**
	 *      \brief      Update database
	 *      \param      user        	User that modify
	 *      \param      notrigger	    0=launch triggers after, 1=disable triggers
	 *      \return     int         	<0 if KO, >0 if OK
	 */
	function update($user=0, $notrigger=0)
	{
		global $conf, $langs;
		$error=0;

		// Clean parameters

		if (isset($this->fk_charge)) $this->fk_charge=trim($this->fk_charge);
		if (isset($this->amount)) $this->amount=trim($this->amount);
		if (isset($this->fk_typepaiement)) $this->fk_typepaiement=trim($this->fk_typepaiement);
		if (isset($this->num_paiement)) $this->num_paiement=trim($this->num_paiement);
		if (isset($this->note)) $this->note=trim($this->note);
		if (isset($this->fk_bank)) $this->fk_bank=trim($this->fk_bank);
		if (isset($this->fk_user_creat)) $this->fk_user_creat=trim($this->fk_user_creat);
		if (isset($this->fk_user_modif)) $this->fk_user_modif=trim($this->fk_user_modif);



		// Check parameters
		// Put here code to add control on parameters values

		// Update request
		$sql = "UPDATE ".MAIN_DB_PREFIX."paiementcharge SET";

		$sql.= " fk_charge=".(isset($this->fk_charge)?$this->fk_charge:"null").",";
		$sql.= " datec=".(strlen($this->datec)!=0 ? "'".$this->db->idate($this->datec)."'" : 'null').",";
		$sql.= " tms=".(strlen($this->tms)!=0 ? "'".$this->db->idate($this->tms)."'" : 'null').",";
		$sql.= " datep=".(strlen($this->datep)!=0 ? "'".$this->db->idate($this->datep)."'" : 'null').",";
		$sql.= " amount=".(isset($this->amount)?$this->amount:"null").",";
		$sql.= " fk_typepaiement=".(isset($this->fk_typepaiement)?$this->fk_typepaiement:"null").",";
		$sql.= " num_paiement=".(isset($this->num_paiement)?"'".addslashes($this->num_paiement)."'":"null").",";
		$sql.= " note=".(isset($this->note)?"'".addslashes($this->note)."'":"null").",";
		$sql.= " fk_bank=".(isset($this->fk_bank)?$this->fk_bank:"null").",";
		$sql.= " fk_user_creat=".(isset($this->fk_user_creat)?$this->fk_user_creat:"null").",";
		$sql.= " fk_user_modif=".(isset($this->fk_user_modif)?$this->fk_user_modif:"null")."";


		$sql.= " WHERE rowid=".$this->id;

		$this->db->begin();

		dol_syslog(get_class($this)."::update sql=".$sql, LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (! $resql) { $error++; $this->errors[]="Error ".$this->db->lasterror(); }

		if (! $error)
		{
			if (! $notrigger)
			{
				// Uncomment this and change MYOBJECT to your own tag if you
				// want this action call a trigger.

				//// Call triggers
				//include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
				//$interface=new Interfaces($this->db);
				//$result=$interface->run_triggers('MYOBJECT_MODIFY',$this,$user,$langs,$conf);
				//if ($result < 0) { $error++; $this->errors=$interface->errors; }
				//// End call triggers
			}
		}

		// Commit or rollback
		if ($error)
		{
			foreach($this->errors as $errmsg)
			{
				dol_syslog(get_class($this)."::update ".$errmsg, LOG_ERR);
				$this->error.=($this->error?', '.$errmsg:$errmsg);
			}
			$this->db->rollback();
			return -1*$error;
		}
		else
		{
			$this->db->commit();
			return 1;
		}
	}


	/**
	 *     \brief      Delete object in database
	 *     \param      user        	User that delete
	 *     \param      notrigger	0=launch triggers after, 1=disable triggers
	 *     \return     int			<0 if KO, >0 if OK
	 */
	function delete($user, $notrigger=0)
	{
		global $conf, $langs;
		$error=0;

		$this->db->begin();

	    if (! $error)
        {
            $sql = "DELETE FROM ".MAIN_DB_PREFIX."bank_url";
            $sql.= " WHERE type='payment_sc' AND url_id=".$this->id;

            dol_syslog(get_class($this)."::delete sql=".$sql);
            $resql = $this->db->query($sql);
            if (! $resql) { $error++; $this->errors[]="Error ".$this->db->lasterror(); }
        }

		if (! $error)
		{
			$sql = "DELETE FROM ".MAIN_DB_PREFIX."paiementcharge";
			$sql.= " WHERE rowid=".$this->id;

			dol_syslog(get_class($this)."::delete sql=".$sql);
			$resql = $this->db->query($sql);
			if (! $resql) { $error++; $this->errors[]="Error ".$this->db->lasterror(); }
		}

		if (! $error)
		{
			if (! $notrigger)
			{
				// Uncomment this and change MYOBJECT to your own tag if you
				// want this action call a trigger.

				//// Call triggers
				//include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
				//$interface=new Interfaces($this->db);
				//$result=$interface->run_triggers('MYOBJECT_DELETE',$this,$user,$langs,$conf);
				//if ($result < 0) { $error++; $this->errors=$interface->errors; }
				//// End call triggers
			}
		}

		// Commit or rollback
		if ($error)
		{
			foreach($this->errors as $errmsg)
			{
				dol_syslog(get_class($this)."::delete ".$errmsg, LOG_ERR);
				$this->error.=($this->error?', '.$errmsg:$errmsg);
			}
			$this->db->rollback();
			return -1*$error;
		}
		else
		{
			$this->db->commit();
			return 1;
		}
	}



	/**
	 *		\brief      Load an object from its id and create a new one in database
	 *		\param      fromid     		Id of object to clone
	 * 	 	\return		int				New id of clone
	 */
	function createFromClone($fromid)
	{
		global $user,$langs;

		$error=0;

		$object=new Paiementcharge($this->db);

		$this->db->begin();

		// Load source object
		$object->fetch($fromid);
		$object->id=0;
		$object->statut=0;

		// Clear fields
		// ...

		// Create clone
		$result=$object->create($user);

		// Other options
		if ($result < 0)
		{
			$this->error=$object->error;
			$error++;
		}

		if (! $error)
		{



		}

		// End
		if (! $error)
		{
			$this->db->commit();
			return $object->id;
		}
		else
		{
			$this->db->rollback();
			return -1;
		}
	}


	/**
	 *		\brief		Initialise object with example values
	 *		\remarks	id must be 0 if object instance is a specimen.
	 */
	function initAsSpecimen()
	{
		$this->id=0;

		$this->fk_charge='';
		$this->datec='';
		$this->tms='';
		$this->datep='';
		$this->amount='';
		$this->fk_typepaiement='';
		$this->num_paiement='';
		$this->note='';
		$this->fk_bank='';
		$this->fk_user_creat='';
		$this->fk_user_modif='';


	}


	/**
	 *      \brief      Mise a jour du lien entre le paiement de  charge et la ligne dans llx_bank generee
	 *      \param      id_bank         Id de la banque
	 *      \return     int             >0 si OK, <=0 si KO
	 */
	function update_fk_bank($id_bank)
	{
		$sql = "UPDATE llx_paiementcharge set fk_bank = ".$id_bank." where rowid = ".$this->id;

		dol_syslog("PaiementCharge::update_fk_bank sql=".$sql);
		$result = $this->db->query($sql);
		if ($result)
		{
			return 1;
		}
		else
		{
			$this->error=$this->db->error();
			dol_syslog("PaiementCharges::update_fk_bank ".$this->error, LOG_ERR);
			return 0;
		}
	}

	/**
	 *    	\brief      Renvoie nom clicable (avec eventuellement le picto)
	 *		\param		withpicto		0=Pas de picto, 1=Inclut le picto dans le lien, 2=Picto seul
	 * 		\param		maxlen			Longueur max libelle
	 *		\return		string			Chaine avec URL
	 */
	function getNomUrl($withpicto=0,$maxlen=0)
	{
		global $langs;

		$result='';

		if (empty($this->ref)) $this->ref=$this->lib;

		$lien = '<a href="'.DOL_URL_ROOT.'/compta/payment_sc/fiche.php?id='.$this->id.'">';
		$lienfin='</a>';

		if ($withpicto) $result.=($lien.img_object($langs->trans("ShowPayment").': '.$this->ref,'payment').$lienfin.' ');
		if ($withpicto && $withpicto != 2) $result.=' ';
		if ($withpicto != 2) $result.=$lien.($maxlen?dol_trunc($this->ref,$maxlen):$this->ref).$lienfin;
		return $result;
	}
}


?>
