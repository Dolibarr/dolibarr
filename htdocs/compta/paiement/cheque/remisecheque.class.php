<?php
/* Copyright (C) 2006      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2007-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009 Regis Houssin        <regis@dolibarr.fr>
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
 *	\file       htdocs/compta/paiement/cheque/remisecheque.class.php
 *	\ingroup    compta
 *	\brief      Fichier de la classe des bordereau de remise de cheque
 *	\version    $Id$
 */

require_once(DOL_DOCUMENT_ROOT ."/commonobject.class.php");


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
	 *    \brief  Constructeur de la classe
	 *    \param  DB          handler acces base de donnees
	 *    \param  id          id compte (0 par defaut)
	 */
	function RemiseCheque($DB)
	{
		$this->db = $DB;
		$this->next_id = 0;
		$this->previous_id = 0;
	}

	/**
	 *	\brief 		Load record
	 *	\param 		id 			Id record
	 *	\param 		ref		 	Ref record
	 * 	\return		int			<0 if KO, >= 0 if OK
	 */
	function Fetch($id,$ref='')
	{
		global $conf;
		
		$sql = "SELECT bc.rowid, bc.datec, bc.fk_user_author, bc.fk_bank_account, bc.amount, bc.number, bc.statut, bc.nbcheque";
		$sql.= ", ".$this->db->pdate("bc.date_bordereau"). " as date_bordereau";
		$sql.= ", ba.label as account_label";
		$sql.= " FROM ".MAIN_DB_PREFIX."bordereau_cheque as bc";
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."bank_account as ba ON bc.fk_bank_account = ba.rowid";
		$sql.= " WHERE bc.entity = ".$conf->entity;
		if ($id)  $sql.= " AND bc.rowid = ".$id;
		if ($ref) $sql.= " AND bc.number = '".addslashes($ref)."'";

		dol_syslog("RemiseCheque::fetch sql=".$sql, LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql)
		{
			if ($obj = $this->db->fetch_object($resql) )
			{
				$this->id             = $obj->rowid;
				$this->amount         = $obj->amount;
				$this->date_bordereau = $obj->date_bordereau;
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

			return 0;
		}
		else
		{
			return -1;
		}
	}

	/**
	 *	\brief  	Create a receipt to send cheques
	 *	\param  	user 			Utilisateur qui effectue l'operation
	 *	\param  	account_id 		Compte bancaire concerne
	 *	\return		int				<0 if KO, >0 if OK
	 */
	function Create($user, $account_id)
	{
		global $conf;
		
		$this->errno = 0;
		$this->id = 0;

		$this->db->begin();

		$sql = "INSERT INTO ".MAIN_DB_PREFIX."bordereau_cheque (";
		$sql.= "datec";
		$sql.= ", date_bordereau";
		$sql.= ", fk_user_author";
		$sql.= ", fk_bank_account";
		$sql.= ", amount";
		$sql.= ", number";
		$sql.= ", entity";
		$sql.= ", nbcheque";
		$sql.= ") VALUES (";
		$sql.= $this->db->idate(mktime());
		$sql.= ", ".$this->db->idate(mktime());
		$sql.= ", ".$user->id;
		$sql.= ", ".$account_id;
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
				dol_syslog("Remisecheque::Create Erreur Lecture ID ($this->errno)", LOG_ERR);
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
					dol_syslog("RemiseCheque::Create ERREUR UPDATE ($this->errno)", LOG_ERR);
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
				$sql.= " LIMIT 40"; // On limite a 40 pour ne generer des PDF que d'une page

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
					dol_syslog("RemiseCheque::Create Error ($this->errno)", LOG_ERR);
				}
			}

			if ($this->id > 0 && $this->errno == 0)
			{
				foreach ($lines as $lineid)
				{
					$sql = "UPDATE ".MAIN_DB_PREFIX."bank";
					$sql.= " SET fk_bordereau = ".$this->id;
					$sql.= " WHERE rowid = ".$lineid;

					dol_syslog("RemiseCheque::Create sql=".$sql, LOG_DEBUG);
					$resql = $this->db->query($sql);
					if (!$resql)
					{
						$this->errno = -18;
						dol_syslog("RemiseCheque::Create Error update bank ($this->errno)", LOG_ERR);
					}
				}
			}

			if ($this->id > 0 && $this->errno == 0)
			{
				if ($this->UpdateAmount() <> 0)
				{
					$this->errno = -1027;
					dol_syslog("RemiseCheque::Create ERREUR ($this->errno)");
				}
			}
		}
		else
		{
			$result = -1;
			$this->error=$this->db->lasterror();
			$this->errno=$this->db->lasterrno();
			dol_syslog("RemiseCheque::Create Erreur $result INSERT Mysql");
		}


		if ($this->errno == 0)
		{
			$this->db->commit();
			return $this->id;
		}
		else
		{
			$this->db->rollback();
			dol_syslog("RemiseCheque::Create ROLLBACK ($this->errno)");
			return $this->errno;
		}

	}

	/**
	 \brief  Supprime la remise en base
	 \param  user utilisateur qui effectue l'operation
	 */
	function Delete($user='')
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
	 *  \brief  Validate receipt
	 *  \param  user 	User
	 */
	function Validate($user)
	{
		global $langs,$conf;

		$this->errno = 0;

		$this->db->begin();

		$num=$this->getNextNumber();

		if ($this->errno == 0 && $num)
		{
			$sql = "UPDATE ".MAIN_DB_PREFIX."bordereau_cheque";
			$sql.= " SET statut = 1";
			$sql.= ", number = '".$num."'";
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
		}
		else
		{
			$this->db->rollback();
			dol_syslog("RemiseCheque::Validate ".$this->errno, LOG_ERR);
		}

		return $this->errno;
	}


	/**
	 * Old module for cheque receipt numbering
	 *
	 * @return string
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
	 *      \brief      Charge indicateurs this->nbtodo et this->nbtodolate de tableau de bord
	 *      \param      user        Objet user
	 *      \return     int         <0 si ko, >0 si ok
	 */
	function load_board($user)
	{
		global $conf;

		if ($user->societe_id) return -1;   // protection pour eviter appel par utilisateur externe

		$now=gmmktime();

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
	 *	\brief  	Build document
	 *	\param 		model 			Model name
	 *	\return  	int        		<0 if KO, >0 if OK
	 */
	function GeneratePdf($model='blochet', $outputlangs)
	{
		global $langs,$conf;

		if (empty($model)) $model='blochet';

		dol_syslog("RemiseCheque::GeneratePdf model=".$model, LOG_DEBUG);

		$dir=DOL_DOCUMENT_ROOT ."/includes/modules/cheque/pdf/";

		// Charge le modele
		$file = "pdf_".$model.".class.php";
		if (file_exists($dir.$file))
		{
			require_once(DOL_DOCUMENT_ROOT ."/compta/bank/account.class.php");
			require_once($dir.$file);

			$classname='BordereauCheque'.ucfirst($model);
			$pdf = new $classname($db);

			$sql = "SELECT b.banque, b.emetteur, b.amount, b.num_chq";
			$sql.= " FROM ".MAIN_DB_PREFIX."bank as b";
			$sql.= ", ".MAIN_DB_PREFIX."bank_account as ba";
			$sql.= ", ".MAIN_DB_PREFIX."bordereau_cheque as bc";
			$sql.= " WHERE b.fk_account = ba.rowid";
			$sql.= " AND b.fk_bordereau = bc.rowid";
			$sql.= " AND bc.rowid = ".$this->id;
			$sql.= " AND bc.entity = ".$conf->entity;
			$sql.= " ORDER BY b.emetteur ASC, b.rowid ASC;";

			dol_syslog("RemiseCheque::GeneratePdf sql=".$sql, LOG_DEBUG);
			$result = $this->db->query($sql);
			if ($result)
			{
				$i = 0;
				while ( $objp = $this->db->fetch_object($result) )
				{
					$pdf->lines[$i]->bank_chq = $objp->banque;
					$pdf->lines[$i]->emetteur_chq = $objp->emetteur;
					$pdf->lines[$i]->amount_chq = $objp->amount;
					$pdf->lines[$i]->num_chq = $objp->num_chq;
					$i++;
				}
			}
			$pdf->nbcheque = $this->nbcheque;
			$pdf->number = $this->number;
			$pdf->amount = $this->amount;
			$pdf->date   = $this->date_bordereau;

			$account = new Account($this->db);
			$account->fetch($this->account_id);

			$pdf->account = &$account;

			// We save charset_output to restore it because write_file can change it if needed for
			// output format that does not support UTF8.
			$sav_charset_output=$outputlangs->charset_output;
			$result=$pdf->write_file($conf->comptabilite->dir_output.'/bordereau', $this->number, $outputlangs);
			if ($result > 0)
			{
				$outputlangs->charset_output=$sav_charset_output;
				return 1;
			}
			else
			{
				$outputlangs->charset_output=$sav_charset_output;
				dol_syslog("Error");
				dol_print_error($db,$pdf->pdferror());
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
	 *	\brief  Mets a jour le montant total
	 *	\return int, 0 en cas de succes
	 */
	function UpdateAmount()
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
	  	dol_syslog("RemiseCheque::UpdateAmount ERREUR UPDATE ($this->errno)");
	  }
		}
		else
		{
			$this->errno = -1031;
			dol_syslog("RemiseCheque::UpdateAmount ERREUR SELECT ($this->errno)");
		}

		if ($this->errno === 0)
		{
			$this->db->commit();
		}
		else
		{
			$this->db->rollback();
			dol_syslog("RemiseCheque::UpdateAmount ROLLBACK ($this->errno)");
		}

		return $this->errno;
	}

	/**
	 \brief  Insere la remise en base
	 \param  user utilisateur qui effectue l'operation
	 \param  account_id Compte bancaire concerne
	 */
	function RemoveCheck($account_id)
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
	  	$this->UpdateAmount();
	  }
	  else
	  {
	  	$this->errno = -1032;
	  	dol_syslog("RemiseCheque::RemoveCheck ERREUR UPDATE ($this->errno)");
	  }
		}
		return 0;
	}
	/**
	 \brief      Charge les proprietes ref_previous et ref_next
	 \return     int   <0 si ko, 0 si ok
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
	 *    	\brief      Renvoie nom clicable (avec eventuellement le picto)
	 *		\param		withpicto		Inclut le picto dans le lien
	 *		\param		option			Sur quoi pointe le lien
	 *		\return		string			Chaine avec URL
	 */
	function getNomUrl($withpicto=0,$option='')
	{
		global $langs;	// TODO Renvoyer le libelle anglais et faire traduction a affichage

		$result='';

		$number=$this->number;
		if ($this->statut == 0) $number='(PROV'.$this->rowid.')';

		$lien = '<a href="'.DOL_URL_ROOT.'/compta/paiement/cheque/fiche.php?id='.$this->rowid.'">';
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
	 *    	\brief      Renvoi le libelle d'un statut donne
	 *    	\param      status      Statut
	 *		\param      mode        0=libelle long, 1=libelle court, 2=Picto + Libelle court, 3=Picto, 4=Picto + Libelle long, 5=Libelle court + Picto
	 *    	\return     string      Libelle du statut
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
			if ($status == 0) return img_picto($langs->trans('ToValidate'),'statut1').' '.$langs->trans('ToValidate');
			if ($status == 1) return img_picto($langs->trans('Validated'),'statut4').' '.$langs->trans('Validated');
		}
		if ($mode == 3)
		{
			if ($status == 0) return img_picto($langs->trans('ToValidate'),'statut1');
			if ($status == 1) return img_picto($langs->trans('Validated'),'statut4');
		}
		if ($mode == 4)
		{
			if ($status == 0) return img_picto($langs->trans('ToValidate'),'statut1').' '.$langs->trans('ToValidate');
			if ($status == 1) return img_picto($langs->trans('Validated'),'statut4').' '.$langs->trans('Validated');
		}
		if ($mode == 5)
		{
			if ($status == 0) return $langs->trans('ToValidate').' '.img_picto($langs->trans('ToValidate'),'statut1');
			if ($status == 1) return $langs->trans('Validated').' '.img_picto($langs->trans('Validated'),'statut4');
		}
		return $langs->trans('Unknown');
	}

}
?>
