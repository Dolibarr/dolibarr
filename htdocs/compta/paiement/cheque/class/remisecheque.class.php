<?php
/* Copyright (C) 2006      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2007-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009 Regis Houssin        <regis@dolibarr.fr>
 * Copyright (C) 2011      Juanjo Menent        <jmenent@2byte.es>
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
 *	\file       htdocs/compta/paiement/cheque/class/remisecheque.class.php
 *	\ingroup    compta
 *	\brief      Fichier de la classe des bordereau de remise de cheque
 */
require_once(DOL_DOCUMENT_ROOT ."/core/class/commonobject.class.php");


/**
 *	\class RemiseCheque
 *	\brief Classe permettant la gestion des remises de cheque
 */
class RemiseCheque extends CommonObject
{
	var $db;
	var $error;
	var $element='chequereceipt';
	var $table_element='bordereau_cheque';

	var $id;
	var $num;
	var $intitule;
	//! Numero d'erreur Plage 1024-1279
	var $errno;

	/**
	 *	Constructor
	 *
	 *  @param		DoliDB		$DB      Database handler
	 */
	function RemiseCheque($DB)
	{
		$this->db = $DB;
		$this->next_id = 0;
		$this->previous_id = 0;
	}

	/**
	 *	Load record
	 *
	 *	@param 		id 			Id record
	 *	@param 		ref		 	Ref record
	 * 	@return		int			<0 if KO, > 0 if OK
	 */
	function fetch($id,$ref='')
	{
		global $conf;

		$sql = "SELECT bc.rowid, bc.datec, bc.fk_user_author, bc.fk_bank_account, bc.amount, bc.number, bc.statut, bc.nbcheque";
		$sql.= ", bc.date_bordereau as date_bordereau";
		$sql.= ", ba.label as account_label";
		$sql.= " FROM ".MAIN_DB_PREFIX."bordereau_cheque as bc";
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."bank_account as ba ON bc.fk_bank_account = ba.rowid";
		$sql.= " WHERE bc.entity = ".$conf->entity;
		if ($id)  $sql.= " AND bc.rowid = ".$id;
		if ($ref) $sql.= " AND bc.number = '".$this->db->escape($ref)."'";

		dol_syslog("RemiseCheque::fetch sql=".$sql, LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql)
		{
			if ($obj = $this->db->fetch_object($resql) )
			{
				$this->id             = $obj->rowid;
				$this->amount         = $obj->amount;
				$this->date_bordereau = $this->db->jdate($obj->date_bordereau);
				$this->account_id     = $obj->fk_bank_account;
				$this->account_label  = $obj->account_label;
				$this->author_id      = $obj->fk_user_author;
				$this->nbcheque       = $obj->nbcheque;
				$this->statut         = $obj->statut;

				if ($this->statut == 0)
				{
					$this->number         = "(PROV".$this->id.")";
				}
				else
				{
					$this->number         = $obj->number;
				}
				$this->ref            = $this->number;

			}
			$this->db->free($resql);

			return 1;
		}
		else
		{
		    $this->error=$this->db->lasterror();
			return -1;
		}
	}

	/**
	 *	Create a receipt to send cheques
	 *
	 *	@param  	user 			User making creation
	 *	@param  	account_id 		Bank account for cheque receipt
	 *  @param      limit           Limit number of cheque to this
	 *  @param		toRemise		array with cheques to remise
	 *	@return		int				<0 if KO, >0 if OK
	 */
	function create($user, $account_id, $limit=40,$toRemise)
	{
		global $conf;

		$this->errno = 0;
		$this->id = 0;

		$now=dol_now();

		$this->db->begin();

		$sql = "INSERT INTO ".MAIN_DB_PREFIX."bordereau_cheque (";
		$sql.= "datec";
		$sql.= ", date_bordereau";
		$sql.= ", fk_user_author";
		$sql.= ", fk_bank_account";
		$sql.= ", statut";
		$sql.= ", amount";
		$sql.= ", number";
		$sql.= ", entity";
		$sql.= ", nbcheque";
		$sql.= ") VALUES (";
		$sql.= $this->db->idate($now);
		$sql.= ", ".$this->db->idate($now);
		$sql.= ", ".$user->id;
		$sql.= ", ".$account_id;
		$sql.= ", 0";
		$sql.= ", 0";
		$sql.= ", 0";
		$sql.= ", ".$conf->entity;
		$sql.= ", 0";
		$sql.= ")";

		dol_syslog("RemiseCheque::Create sql=".$sql, LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ( $resql )
		{
			$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX."bordereau_cheque");
			if ($this->id == 0)
			{
				$this->errno = -1024;
				dol_syslog("Remisecheque::Create Error read id ".$this->errno, LOG_ERR);
			}

			if ($this->id > 0 && $this->errno == 0)
			{
				$sql = "UPDATE ".MAIN_DB_PREFIX."bordereau_cheque";
				$sql.= " SET number='(PROV".$this->id.")'";
				$sql.= " WHERE rowid='".$this->id."';";

				dol_syslog("RemiseCheque::Create sql=".$sql, LOG_DEBUG);
				$resql = $this->db->query($sql);
				if (! $resql)
				{
					$this->errno = -1025;
					dol_syslog("RemiseCheque::Create Error update ".$this->errno, LOG_ERR);
				}
			}

			if ($this->id > 0 && $this->errno == 0)
			{
				$lines = array();
				$sql = "SELECT b.rowid";
				$sql.= " FROM ".MAIN_DB_PREFIX."bank as b";
				$sql.= " WHERE b.fk_type = 'CHQ'";
				$sql.= " AND b.amount > 0";
				$sql.= " AND b.fk_bordereau = 0";
				$sql.= " AND b.fk_account='".$account_id."'";
				if ($limit) $sql.= $this->db->plimit($limit);

				dol_syslog("RemiseCheque::Create sql=".$sql, LOG_DEBUG);
				$resql = $this->db->query($sql);
				if ($resql)
				{
					while ($row = $this->db->fetch_row($resql) )
					{
						array_push($lines, $row[0]);
					}
					$this->db->free($resql);
				}
				else
				{
					$this->errno = -1026;
					dol_syslog("RemiseCheque::Create Error ".$this->errno, LOG_ERR);
				}
			}

			if ($this->id > 0 && $this->errno == 0)
			{
				foreach ($lines as $lineid)
				{
					$checkremise=false;
					foreach ($toRemise as $linetoremise)
					{
						if($linetoremise==$lineid) $checkremise=true;
					}

					if($checkremise==true)
					{
						$sql = "UPDATE ".MAIN_DB_PREFIX."bank";
						$sql.= " SET fk_bordereau = ".$this->id;
						$sql.= " WHERE rowid = ".$lineid;

						dol_syslog("RemiseCheque::Create sql=".$sql, LOG_DEBUG);
						$resql = $this->db->query($sql);
						if (!$resql)
						{
							$this->errno = -18;
							dol_syslog("RemiseCheque::Create Error update bank ".$this->errno, LOG_ERR);
						}
					}
				}
			}

			if ($this->id > 0 && $this->errno == 0)
			{
				if ($this->updateAmount() <> 0)
				{
					$this->errno = -1027;
					dol_syslog("RemiseCheque::Create Error update amount ".$this->errno, LOG_ERR);
				}
			}
		}
		else
		{
			$this->errno = -1;
			$this->error=$this->db->lasterror();
			$this->errno=$this->db->lasterrno();
			dol_syslog("RemiseCheque::Create Error ".$this->error, LOG_ERR);
		}

	    if (! $this->errno && ! empty($conf->global->MAIN_DISABLEDRAFTSTATUS))
        {
            $res=$this->validate($user);
            //if ($res < 0) $error++;
        }

        if (! $this->errno)
        {
            $this->db->commit();
            return $this->id;
        }
        else
        {
            $this->db->rollback();
            return $this->errno;
        }
	}

	/**
	 *	Supprime la remise en base
	 *
	 *	@param  user utilisateur qui effectue l'operation
	 */
	function delete($user='')
	{
		global $conf;

		$this->errno = 0;
		$this->db->begin();

		$sql = "DELETE FROM ".MAIN_DB_PREFIX."bordereau_cheque";
		$sql.= " WHERE rowid = ".$this->id;
		$sql.= " AND entity = ".$conf->entity;

		$resql = $this->db->query($sql);
		if ( $resql )
		{
			$num = $this->db->affected_rows($resql);

			if ($num <> 1)
	  {
	  	$this->errno = -2;
	  	dol_syslog("Remisecheque::Delete Erreur Lecture ID ($this->errno)");
	  }

	  if ( $this->errno === 0)
	  {
	  	$sql = "UPDATE ".MAIN_DB_PREFIX."bank";
	  	$sql.= " SET fk_bordereau = 0";
	  	$sql.= " WHERE fk_bordereau = '".$this->id."'";

	  	$resql = $this->db->query($sql);
	  	if (!$resql)
	  	{
	  		$this->errno = -1028;
	  		dol_syslog("RemiseCheque::Delete ERREUR UPDATE ($this->errno)");
	  	}
	  }
		}

		if ($this->errno === 0)
		{
			$this->db->commit();
		}
		else
		{
			$this->db->rollback();
			dol_syslog("RemiseCheque::Delete ROLLBACK ($this->errno)");
		}

		return $this->errno;
	}

	/**
	 *  Validate a receipt
	 *
	 *  @param     user 	User
	 *  @return    int      <0 if KO, >0 if OK
	 */
	function validate($user)
	{
		global $langs,$conf;

		$this->errno = 0;

		$this->db->begin();

		$numref=$this->getNextNumber();

		if ($this->errno == 0 && $numref)
		{
			$sql = "UPDATE ".MAIN_DB_PREFIX."bordereau_cheque";
			$sql.= " SET statut = 1, number = '".$numref."'";
			$sql.= " WHERE rowid = ".$this->id;
			$sql.= " AND entity = ".$conf->entity;
			$sql.= " AND statut = 0";

			dol_syslog("RemiseCheque::Validate sql=".$sql, LOG_DEBUG);
			$resql = $this->db->query($sql);
			if ( $resql )
			{
				$num = $this->db->affected_rows($resql);

				if ($num == 1)
				{
				    $this->number = $numref;
					$this->statut = 1;
				}
				else
				{
					$this->errno = -1029;
					dol_syslog("Remisecheque::Validate Error ".$this->errno, LOG_ERR);
				}
			}
			else
			{
				$this->errno = -1033;
				dol_syslog("Remisecheque::Validate Error ".$this->errno, LOG_ERR);
			}
		}

		// Commit/Rollback
		if ($this->errno == 0)
		{
			$this->db->commit();
			return 1;
		}
		else
		{
			$this->db->rollback();
			dol_syslog("RemiseCheque::Validate ".$this->errno, LOG_ERR);
            return $this->errno;
		}
	}


	/**
	 * Old module for cheque receipt numbering
	 *
	 * @return 	int		Next number of cheque
	 */
	function getNextNumber()
	{
		global $conf;

		$num=0;

		// We use +0 to convert varchar to number
		$sql = "SELECT MAX(number+0)";
		$sql.= " FROM ".MAIN_DB_PREFIX."bordereau_cheque";
		$sql.= " WHERE entity = ".$conf->entity;

		dol_syslog("Remisecheque::getNextNumber sql=".$sql);
		$resql = $this->db->query($sql);
		if ($resql)
		{
			$row = $this->db->fetch_row($resql);
			$num = $row[0];
			$this->db->free($resql);
		}
		else
		{
			$this->errno = -1034;
			dol_syslog("Remisecheque::Validate Erreur SELECT ($this->errno)", LOG_ERR);
		}

		$num++;

		return $num;
	}

	/**
     *      Load indicators for dashboard (this->nbtodo and this->nbtodolate)
     *
     *      @param      User	$user       Objet user
     *      @return     int                 <0 if KO, >0 if OK
	 */
	function load_board($user)
	{
		global $conf;

		if ($user->societe_id) return -1;   // protection pour eviter appel par utilisateur externe

		$now=dol_now();

		$this->nbtodo=$this->nbtodolate=0;

		$sql = "SELECT b.rowid, b.datev as datefin";
		$sql.= " FROM ".MAIN_DB_PREFIX."bank as b";
		$sql.= ", ".MAIN_DB_PREFIX."bank_account as ba";
		$sql.= " WHERE b.fk_account = ba.rowid";
		$sql.= " AND ba.entity = ".$conf->entity;
		$sql.= " AND b.fk_type = 'CHQ'";
		$sql.= " AND b.fk_bordereau = 0";
		$sql.= " AND b.amount > 0";

		$resql=$this->db->query($sql);
		if ($resql)
		{
			while ($obj=$this->db->fetch_object($resql))
			{
				$this->nbtodo++;
				if ($this->db->jdate($obj->datefin) < ($now - $conf->bank->cheque->warning_delay)) $this->nbtodolate++;
			}
			return 1;
		}
		else
		{
			dol_print_error($this->db);
			$this->error=$this->db->error();
			return -1;
		}
	}


	/**
	 *	Build document
	 *	@param 		model 			Model name
	 *	@param 		outputlangs		Object langs
	 * 	@return  	int        		<0 if KO, >0 if OK
	 */
	function generatePdf($model='blochet', $outputlangs)
	{
		global $langs,$conf;

		if (empty($model)) $model='blochet';

		dol_syslog("RemiseCheque::generatePdf model=".$model." id=".$this->id, LOG_DEBUG);

		$dir=DOL_DOCUMENT_ROOT ."/includes/modules/cheque/pdf/";

		// Charge le modele
		$file = "pdf_".$model.".class.php";
		if (file_exists($dir.$file))
		{
			require_once(DOL_DOCUMENT_ROOT ."/compta/bank/class/account.class.php");
			require_once($dir.$file);

			$classname='BordereauCheque'.ucfirst($model);
			$docmodel = new $classname($db);

			$sql = "SELECT b.banque, b.emetteur, b.amount, b.num_chq";
			$sql.= " FROM ".MAIN_DB_PREFIX."bank as b";
			$sql.= ", ".MAIN_DB_PREFIX."bank_account as ba";
			$sql.= ", ".MAIN_DB_PREFIX."bordereau_cheque as bc";
			$sql.= " WHERE b.fk_account = ba.rowid";
			$sql.= " AND b.fk_bordereau = bc.rowid";
			$sql.= " AND bc.rowid = ".$this->id;
			$sql.= " AND bc.entity = ".$conf->entity;
			$sql.= " ORDER BY b.emetteur ASC, b.rowid ASC;";

			dol_syslog("RemiseCheque::generatePdf sql=".$sql, LOG_DEBUG);
			$result = $this->db->query($sql);
			if ($result)
			{
				$i = 0;
				while ( $objp = $this->db->fetch_object($result) )
				{
					$docmodel->lines[$i]->bank_chq = $objp->banque;
					$docmodel->lines[$i]->emetteur_chq = $objp->emetteur;
					$docmodel->lines[$i]->amount_chq = $objp->amount;
					$docmodel->lines[$i]->num_chq = $objp->num_chq;
					$i++;
				}
			}
			$docmodel->nbcheque = $this->nbcheque;
			$docmodel->number = $this->number;
			$docmodel->amount = $this->amount;
			$docmodel->date   = $this->date_bordereau;

			$account = new Account($this->db);
			$account->fetch($this->account_id);

			$docmodel->account = &$account;

			// We save charset_output to restore it because write_file can change it if needed for
			// output format that does not support UTF8.
			$sav_charset_output=$outputlangs->charset_output;
			$result=$docmodel->write_file($conf->banque->dir_output.'/bordereau', $this->number, $outputlangs);
			if ($result > 0)
			{
				$outputlangs->charset_output=$sav_charset_output;
				return 1;
			}
			else
			{
				$outputlangs->charset_output=$sav_charset_output;
				dol_syslog("Error");
				dol_print_error($db,$docmodel->error);
				return 0;
			}
		}
		else
		{
			$this->error=$langs->trans("ErrorFileDoesNotExists",$dir.$file);
			return -1;
		}
	}

	/**
	 *	\brief  	Mets a jour le montant total
	 *	\return 	int		0 en cas de succes
	 */
	function updateAmount()
	{
		global $conf;

		$this->errno = 0;
		$this->db->begin();
		$total = 0;
		$nb = 0;
		$sql = "SELECT amount ";
		$sql.= " FROM ".MAIN_DB_PREFIX."bank";
		$sql.= " WHERE fk_bordereau = ".$this->id;

		$resql = $this->db->query($sql);
		if ( $resql )
		{
			while ( $row = $this->db->fetch_row($resql) )
	  {
	  	$total += $row[0];
	  	$nb++;
	  }

	  $this->db->free($resql);

	  $sql = "UPDATE ".MAIN_DB_PREFIX."bordereau_cheque";
	  $sql.= " SET amount = '".price2num($total)."'";
	  $sql.= ", nbcheque = ".$nb;
	  $sql.= " WHERE rowid = ".$this->id;
	  $sql.= " AND entity = ".$conf->entity;

	  $resql = $this->db->query($sql);
	  if (!$resql)
	  {
	  	$this->errno = -1030;
	  	dol_syslog("RemiseCheque::updateAmount ERREUR UPDATE ($this->errno)");
	  }
		}
		else
		{
			$this->errno = -1031;
			dol_syslog("RemiseCheque::updateAmount ERREUR SELECT ($this->errno)");
		}

		if ($this->errno === 0)
		{
			$this->db->commit();
		}
		else
		{
			$this->db->rollback();
			dol_syslog("RemiseCheque::updateAmount ROLLBACK ($this->errno)");
		}

		return $this->errno;
	}

	/**
	 *	\brief  	Insere la remise en base
	 *	\param  	account_id 		Compte bancaire concerne
	 * 	\return		int
	 */
	function removeCheck($account_id)
	{
		$this->errno = 0;

		if ($this->id > 0)
		{
			$sql = "UPDATE ".MAIN_DB_PREFIX."bank";
			$sql.= " SET fk_bordereau = 0";
			$sql.= " WHERE rowid = '".$account_id."'";
			$sql.= " AND fk_bordereau = ".$this->id;

			$resql = $this->db->query($sql);
			if ($resql)
	  {
	  	$this->updateAmount();
	  }
	  else
	  {
	  	$this->errno = -1032;
	  	dol_syslog("RemiseCheque::removeCheck ERREUR UPDATE ($this->errno)");
	  }
		}
		return 0;
	}
	/**
	 *	\brief      Charge les proprietes ref_previous et ref_next
	 *	\return     int   <0 si ko, 0 si ok
	 */
	function load_previous_next_id()
	{
		global $conf;

		$this->errno = 0;

		$sql = "SELECT MAX(rowid)";
		$sql.= " FROM ".MAIN_DB_PREFIX."bordereau_cheque";
		$sql.= " WHERE rowid < ".$this->id;
		$sql.= " AND entity = ".$conf->entity;

		$result = $this->db->query($sql) ;
		if (! $result)
		{
			$this->errno = -1035;
		}
		$row = $this->db->fetch_row($result);
		$this->previous_id = $row[0];

		$sql = "SELECT MIN(rowid)";
		$sql.= " FROM ".MAIN_DB_PREFIX."bordereau_cheque";
		$sql.= " WHERE rowid > ".$this->id;
		$sql.= " AND entity = ".$conf->entity;

		$result = $this->db->query($sql) ;
		if (! $result)
		{
			$this->errno = -1035;
		}
		$row = $this->db->fetch_row($result);
		$this->next_id = $row[0];

		return $this->errno;
	}


    /**
     *      Set the creation date
     *      @param      user                Object user
     *      @param      date                Date creation
     *      @return     int                 <0 if KO, >0 if OK
     */
    function set_date($user, $date)
    {
        if ($user->rights->banque->cheque)
        {
            $sql = "UPDATE ".MAIN_DB_PREFIX."bordereau_cheque";
            $sql.= " SET date_bordereau = ".($date ? $this->db->idate($date) : 'null');
            $sql.= " WHERE rowid = ".$this->id;

            dol_syslog("RemiseCheque::set_date sql=$sql",LOG_DEBUG);
            $resql=$this->db->query($sql);
            if ($resql)
            {
                $this->date_bordereau = $date;
                return 1;
            }
            else
            {
                $this->error=$this->db->error();
                dol_syslog("RemiseCheque::set_date ".$this->error,LOG_ERR);
                return -1;
            }
        }
        else
        {
            return -2;
        }
    }


	/**
	 *    	Renvoie nom clicable (avec eventuellement le picto)
	 *		@param		withpicto		Inclut le picto dans le lien
	 *		@param		option			Sur quoi pointe le lien
	 *		@return		string			Chaine avec URL
	 */
	function getNomUrl($withpicto=0,$option='')
	{
		global $langs;

		$result='';

		$number=$this->ref;
		if ($this->statut == 0) $number='(PROV'.$this->id.')';

		$lien = '<a href="'.DOL_URL_ROOT.'/compta/paiement/cheque/fiche.php?id='.$this->id.'">';
		$lienfin='</a>';

		if ($withpicto) $result.=($lien.img_object($langs->trans("ShowCheckReceipt"),'payment').$lienfin.' ');
		$result.=$lien.$number.$lienfin;
		return $result;
	}

	/**
	 *    	\brief      Retourne le libelle du statut d'une facture (brouillon, validee, abandonnee, payee)
	 *    	\param      mode        0=libelle long, 1=libelle court, 2=Picto + Libelle court, 3=Picto, 4=Picto + Libelle long, 5=Libelle court + Picto
	 *    	\return     string		Libelle
	 */
	function getLibStatut($mode=0)
	{
		return $this->LibStatut($this->statut,$mode);
	}

	/**
	 *    	Return label of a status
	 *    	@param      status      Statut
	 *		@param      mode        0=libelle long, 1=libelle court, 2=Picto + Libelle court, 3=Picto, 4=Picto + Libelle long, 5=Libelle court + Picto
	 *    	@return     string      Libelle du statut
	 */
	function LibStatut($status,$mode=0)
	{
		global $langs;	// TODO Renvoyer le libelle anglais et faire traduction a affichage
		$langs->load('compta');
		if ($mode == 0)
		{
			if ($status == 0) return $langs->trans('ToValidate');
			if ($status == 1) return $langs->trans('Validated');
		}
		if ($mode == 1)
		{
			if ($status == 0) return $langs->trans('ToValidate');
			if ($status == 1) return $langs->trans('Validated');
		}
		if ($mode == 2)
		{
			if ($status == 0) return img_picto($langs->trans('ToValidate'),'statut0').' '.$langs->trans('ToValidate');
			if ($status == 1) return img_picto($langs->trans('Validated'),'statut4').' '.$langs->trans('Validated');
		}
		if ($mode == 3)
		{
			if ($status == 0) return img_picto($langs->trans('ToValidate'),'statut0');
			if ($status == 1) return img_picto($langs->trans('Validated'),'statut4');
		}
		if ($mode == 4)
		{
			if ($status == 0) return img_picto($langs->trans('ToValidate'),'statut0').' '.$langs->trans('ToValidate');
			if ($status == 1) return img_picto($langs->trans('Validated'),'statut4').' '.$langs->trans('Validated');
		}
		if ($mode == 5)
		{
			if ($status == 0) return $langs->trans('ToValidate').' '.img_picto($langs->trans('ToValidate'),'statut0');
			if ($status == 1) return $langs->trans('Validated').' '.img_picto($langs->trans('Validated'),'statut4');
		}
		return $langs->trans('Unknown');
	}

}
?>
