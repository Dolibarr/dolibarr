<?php
/* Copyright (C) 2015       Alexandre Spangaro	  	<aspangaro@open-dsi.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 *  \file       htdocs/don/class/paymentdonation.class.php
 *  \ingroup    Donation
 *  \brief      File of class to manage payment of donations
 */

require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';


/**
 *	Class to manage payments of donations
 */
class PaymentDonation extends CommonObject
{
	/**
	 * @var string ID to identify managed object
	 */
	public $element='payment_donation';

	/**
	 * @var string Name of table without prefix where object is stored
	 */
	public $table_element='payment_donation';

    /**
	 * @var string String with name of icon for myobject. Must be the part after the 'object_' into object_myobject.png
	 */
	public $picto = 'payment';

	/**
	 * @var int ID
	 */
	public $rowid;

	/**
     * @var int ID
     */
	public $fk_donation;

	public $datec='';
	public $tms='';
	public $datep='';
    public $amount;            // Total amount of payment
    public $amounts=array();   // Array of amounts
	public $typepayment;
	public $num_payment;

	/**
     * @var int ID
     */
	public $fk_bank;

	/**
     * @var int ID
     */
	public $fk_user_creat;

	/**
     * @var int ID
     */
	public $fk_user_modif;

	/**
	 * @deprecated
	 * @see $amount, $amounts
	 */
	public $total;

	public $type_code;
	public $type_label;


	/**
	 *	Constructor
	 *
	 *  @param		DoliDB		$db      Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;
	}

	/**
	 *  Create payment of donation into database.
     *  Use this->amounts to have list of lines for the payment
     *
	 *  @param      User		$user			User making payment
	 *  @param      bool 		$notrigger 		false=launch triggers after, true=disable triggers
	 *  @return     int     					<0 if KO, id of payment if OK
	 */
	public function create($user, $notrigger = false)
	{
		global $conf, $langs;

		$error=0;

        $now=dol_now();

        // Validate parameters
		if (! $this->datepaid)
		{
			$this->error='ErrorBadValueForParameterCreatePaymentDonation';
			return -1;
		}

		// Clean parameters
		if (isset($this->fk_donation)) 		$this->fk_donation = (int) $this->fk_donation;
		if (isset($this->amount))			$this->amount=trim($this->amount);
		if (isset($this->fk_typepayment))   $this->fk_typepayment=trim($this->fk_typepayment);
		if (isset($this->num_payment))      $this->num_payment=trim($this->num_payment);
		if (isset($this->note_public))		$this->note_public=trim($this->note_public);
		if (isset($this->fk_bank))			$this->fk_bank = (int) $this->fk_bank;
		if (isset($this->fk_user_creat))	$this->fk_user_creat = (int) $this->fk_user_creat;
		if (isset($this->fk_user_modif))	$this->fk_user_modif = (int) $this->fk_user_modif;

        $totalamount = 0;
        foreach ($this->amounts as $key => $value)  // How payment is dispatch
        {
            $newvalue = price2num($value, 'MT');
            $this->amounts[$key] = $newvalue;
            $totalamount += $newvalue;
        }
        $totalamount = price2num($totalamount);

        // Check parameters
        if ($totalamount == 0) return -1; // On accepte les montants negatifs pour les rejets de prelevement mais pas null


		$this->db->begin();

		if ($totalamount != 0)
		{
			$sql = "INSERT INTO ".MAIN_DB_PREFIX."payment_donation (fk_donation, datec, datep, amount,";
			$sql.= " fk_typepayment, num_payment, note, fk_user_creat, fk_bank)";
			$sql.= " VALUES ($this->chid, '".$this->db->idate($now)."',";
			$sql.= " '".$this->db->idate($this->datepaid)."',";
			$sql.= " ".$totalamount.",";
			$sql.= " ".$this->paymenttype.", '".$this->db->escape($this->num_payment)."', '".$this->db->escape($this->note_public)."', ".$user->id.",";
			$sql.= " 0)";

			dol_syslog(get_class($this)."::create", LOG_DEBUG);
			$resql=$this->db->query($sql);
			if ($resql)
			{
				$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX."payment_donation");
				$this->ref = $this->id;
			}
			else
			{
				$error++;
			}
		}

		if (! $error && ! $notrigger)
		{
			// Call triggers
			$result=$this->call_trigger('DONATION_PAYMENT_CREATE', $user);
			if ($result < 0) { $error++; }
			// End call triggers
		}

		if ($totalamount != 0 && ! $error)
		{
		    $this->amount=$totalamount;
            $this->total=$totalamount;    // deprecated
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
	 *  Load object in memory from database
	 *
	 *  @param	int		$id         Id object
	 *  @return int         		<0 if KO, >0 if OK
	 */
	public function fetch($id)
	{
		global $langs;
		$sql = "SELECT";
		$sql.= " t.rowid,";
		$sql.= " t.fk_donation,";
		$sql.= " t.datec,";
		$sql.= " t.tms,";
		$sql.= " t.datep,";
		$sql.= " t.amount,";
		$sql.= " t.fk_typepayment,";
		$sql.= " t.num_payment,";
		$sql.= " t.note as note_public,";
		$sql.= " t.fk_bank,";
		$sql.= " t.fk_user_creat,";
		$sql.= " t.fk_user_modif,";
		$sql.= " pt.code as type_code, pt.libelle as type_label,";
		$sql.= ' b.fk_account';
		$sql.= " FROM ".MAIN_DB_PREFIX."payment_donation as t";
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."c_paiement as pt ON t.fk_typepayment = pt.id";
		$sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'bank as b ON t.fk_bank = b.rowid';
		$sql.= " WHERE t.rowid = ".$id;

		dol_syslog(get_class($this)."::fetch", LOG_DEBUG);
		$resql=$this->db->query($sql);
		if ($resql)
		{
			if ($this->db->num_rows($resql))
			{
				$obj = $this->db->fetch_object($resql);

				$this->id    = $obj->rowid;
				$this->ref   = $obj->rowid;

				$this->fk_donation		= $obj->fk_donation;
				$this->datec			= $this->db->jdate($obj->datec);
				$this->tms				= $this->db->jdate($obj->tms);
				$this->datep			= $this->db->jdate($obj->datep);
				$this->amount			= $obj->amount;
				$this->fk_typepayment	= $obj->fk_typepayment;
				$this->num_payment		= $obj->num_payment;
				$this->note_public      = $obj->note_public;
				$this->fk_bank			= $obj->fk_bank;
				$this->fk_user_creat	= $obj->fk_user_creat;
				$this->fk_user_modif	= $obj->fk_user_modif;

				$this->type_code		= $obj->type_code;
				$this->type_label		= $obj->type_label;

				$this->bank_account		= $obj->fk_account;
				$this->bank_line		= $obj->fk_bank;
			}
			$this->db->free($resql);

			return 1;
		}
		else
		{
			$this->error="Error ".$this->db->lasterror();
			return -1;
		}
	}


	/**
	 *  Update database
	 *
	 *  @param	User	$user        	User that modify
	 *  @param  int		$notrigger	    0=launch triggers after, 1=disable triggers
	 *  @return int         			<0 if KO, >0 if OK
	 */
	public function update($user, $notrigger = 0)
	{
		global $conf, $langs;
		$error=0;

		// Clean parameters

		if (isset($this->fk_donation))		$this->fk_donation = (int) $this->fk_donation;
		if (isset($this->amount))			$this->amount=trim($this->amount);
		if (isset($this->fk_typepayment))	$this->fk_typepayment=trim($this->fk_typepayment);
		if (isset($this->num_payment))		$this->num_payment=trim($this->num_payment);
		if (isset($this->note_public))		$this->note_public=trim($this->note_public);
		if (isset($this->fk_bank))			$this->fk_bank = (int) $this->fk_bank;
		if (isset($this->fk_user_creat))	$this->fk_user_creat = (int) $this->fk_user_creat;
		if (isset($this->fk_user_modif))	$this->fk_user_modif = (int) $this->fk_user_modif;

		// Check parameters
		// Put here code to add control on parameters values

		// Update request
		$sql = "UPDATE ".MAIN_DB_PREFIX."payment_donation SET";
		$sql.= " fk_donation=".(isset($this->fk_donation)?$this->fk_donation:"null").",";
		$sql.= " datec=".(dol_strlen($this->datec)!=0 ? "'".$this->db->idate($this->datec)."'" : 'null').",";
		$sql.= " tms=".(dol_strlen($this->tms)!=0 ? "'".$this->db->idate($this->tms)."'" : 'null').",";
		$sql.= " datep=".(dol_strlen($this->datep)!=0 ? "'".$this->db->idate($this->datep)."'" : 'null').",";
		$sql.= " amount=".(isset($this->amount)?$this->amount:"null").",";
		$sql.= " fk_typepayment=".(isset($this->fk_typepayment)?$this->fk_typepayment:"null").",";
		$sql.= " num_payment=".(isset($this->num_payment)?"'".$this->db->escape($this->num_payment)."'":"null").",";
		$sql.= " note=".(isset($this->note_public)?"'".$this->db->escape($this->note_public)."'":"null").",";
		$sql.= " fk_bank=".(isset($this->fk_bank)?$this->fk_bank:"null").",";
		$sql.= " fk_user_creat=".(isset($this->fk_user_creat)?$this->fk_user_creat:"null").",";
		$sql.= " fk_user_modif=".(isset($this->fk_user_modif)?$this->fk_user_modif:"null")."";
		$sql.= " WHERE rowid=".(int) $this->id;

		$this->db->begin();

		dol_syslog(get_class($this)."::update", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (! $resql) {
            $error++;
            $this->errors[]="Error ".$this->db->lasterror();
        }

		if (! $error)
		{
			if (! $notrigger)
			{
				if (! $error && ! $notrigger)
				{
					// Call triggers
					$result=$this->call_trigger('DONATION_PAYMENT_MODIFY', $user);
					if ($result < 0) { $error++; }
					// End call triggers
				}
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
	 *  Delete object in database
	 *
	 *  @param	User	$user        	User that delete
	 *  @param  int		$notrigger		0=launch triggers after, 1=disable triggers
	 *  @return int						<0 if KO, >0 if OK
	 */
	public function delete($user, $notrigger = 0)
	{
		global $conf, $langs;
		$error=0;

		$this->db->begin();

	    if (! $error)
        {
            $sql = "DELETE FROM ".MAIN_DB_PREFIX."bank_url";
            $sql.= " WHERE type='payment_donation' AND url_id=".(int) $this->id;

            dol_syslog(get_class($this)."::delete", LOG_DEBUG);
            $resql = $this->db->query($sql);
            if (! $resql) { $error++; $this->errors[]="Error ".$this->db->lasterror(); }
        }

		if (! $error)
		{
			$sql = "DELETE FROM ".MAIN_DB_PREFIX."payment_donation";
			$sql.= " WHERE rowid=".$this->id;

			dol_syslog(get_class($this)."::delete", LOG_DEBUG);
			$resql = $this->db->query($sql);
			if (! $resql) {
				$error++;
				$this->errors[]="Error ".$this->db->lasterror();
			}
		}

		if (! $error)
		{
			if (! $notrigger)
			{
				if (! $error && ! $notrigger)
				{
					// Call triggers
					$result=$this->call_trigger('DONATION_PAYMENT_DELETE', $user);
					if ($result < 0) { $error++; }
					// End call triggers
				}
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
	 *	Load an object from its id and create a new one in database
	 *
	 *  @param	User	$user		    User making the clone
	 *	@param	int		$fromid     	Id of object to clone
	 * 	@return	int						New id of clone
	 */
	public function createFromClone(User $user, $fromid)
	{
		$error=0;

		$object=new PaymentDonation($this->db);

		$this->db->begin();

		// Load source object
		$object->fetch($fromid);
		$object->id=0;
		$object->statut=0;

		// Clear fields
		// ...

		// Create clone
		$object->context['createfromclone'] = 'createfromclone';
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

		unset($object->context['createfromclone']);

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
	 * 	Retourne le libelle du statut d'un don (brouillon, validee, abandonnee, payee)
	 *
	 *  @param	int		$mode       0=libelle long, 1=libelle court, 2=Picto + Libelle court, 3=Picto, 4=Picto + Libelle long
	 *  @return string        		Libelle
	 */
	public function getLibStatut($mode = 0)
	{
	    return '';
	}

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Renvoi le libelle d'un statut donne
	 *
	 *  @param	int		$status        	Id status
	 *  @param  int		$mode          	0=libelle long, 1=libelle court, 2=Picto + Libelle court, 3=Picto, 4=Picto + Libelle long, 5=Libelle court + Picto
	 *  @return string 			       	Libelle du statut
	 */
    public function LibStatut($status, $mode = 0)
    {
        // phpcs:enable
        global $langs;

        return '';
    }


	/**
     *  Initialise an instance with random values.
     *  Used to build previews or test instances.
     *	id must be 0 if object instance is a specimen.
     *
     *  @return	void
	 */
	public function initAsSpecimen()
	{
		$this->id=0;

		$this->fk_donation='';
		$this->datec='';
		$this->tms='';
		$this->datep='';
		$this->amount='';
		$this->fk_typepayment='';
		$this->num_payment='';
		$this->note_public='';
		$this->fk_bank='';
		$this->fk_user_creat='';
		$this->fk_user_modif='';
	}


    /**
     *      Add record into bank for payment with links between this bank record and invoices of payment.
     *      All payment properties must have been set first like after a call to create().
     *
     *      @param	User	$user               Object of user making payment
     *      @param  string	$mode               'payment_donation'
     *      @param  string	$label              Label to use in bank record
     *      @param  int		$accountid          Id of bank account to do link with
     *      @param  string	$emetteur_nom       Name of transmitter
     *      @param  string	$emetteur_banque    Name of bank
     *      @return int                 		<0 if KO, >0 if OK
     */
    public function addPaymentToBank($user, $mode, $label, $accountid, $emetteur_nom, $emetteur_banque)
    {
        global $conf;

        $error=0;

        if (! empty($conf->banque->enabled))
        {
            require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';

            $acc = new Account($this->db);
            $acc->fetch($accountid);

            $total=$this->total;
            if ($mode == 'payment_donation') $amount=$total;

            // Insert payment into llx_bank
            $bank_line_id = $acc->addline(
                $this->datepaid,
                $this->paymenttype,  // Payment mode id or code ("CHQ or VIR for example")
                $label,
                $amount,
                $this->num_payment,
                '',
                $user,
                $emetteur_nom,
                $emetteur_banque
            );

            // Update fk_bank in llx_paiement.
            // On connait ainsi le paiement qui a genere l'ecriture bancaire
            if ($bank_line_id > 0)
            {
                $result=$this->update_fk_bank($bank_line_id);
                if ($result <= 0)
                {
                    $error++;
                    dol_print_error($this->db);
                }

                // Add link 'payment', 'payment_supplier', 'payment_donation' in bank_url between payment and bank transaction
                $url='';
                if ($mode == 'payment_donation') $url=DOL_URL_ROOT.'/don/payment/card.php?rowid=';
                if ($url)
                {
                    $result=$acc->add_url_line($bank_line_id, $this->id, $url, '(paiement)', $mode);
                    if ($result <= 0)
                    {
                        $error++;
                        dol_print_error($this->db);
                    }
                }
            }
            else
            {
                $this->error=$acc->error;
                $error++;
            }
        }

        if (! $error)
        {
            return 1;
        }
        else
        {
            return -1;
        }
    }


    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
    /**
	 *  Update link between the donation payment and the generated line in llx_bank
	 *
	 *  @param	int		$id_bank         Id if bank
	 *  @return	int			             >0 if OK, <=0 if KO
	 */
	public function update_fk_bank($id_bank)
	{
        // phpcs:enable
		$sql = "UPDATE ".MAIN_DB_PREFIX."payment_donation SET fk_bank = ".(int) $id_bank." WHERE rowid = ".(int) $this->id;

		dol_syslog(get_class($this)."::update_fk_bank", LOG_DEBUG);
		$result = $this->db->query($sql);
		if ($result)
		{
			return 1;
		}
		else
		{
			$this->error=$this->db->error();
			return 0;
		}
	}

	/**
	 *  Return clicable name (with picto eventually)
	 *
	 *	@param	int		$withpicto		0=No picto, 1=Include picto into link, 2=Only picto
	 * 	@param	int		$maxlen			Longueur max libelle
	 *	@return	string					Chaine avec URL
	 */
	public function getNomUrl($withpicto = 0, $maxlen = 0)
	{
		global $langs;

		$result='';

        $label = $langs->trans("ShowPayment").': '.$this->ref;

		if (!empty($this->id))
		{
			$link = '<a href="'.DOL_URL_ROOT.'/don/payment/card.php?id='.$this->id.'" title="'.dol_escape_htmltag($label, 1).'" class="classfortooltip">';
			$linkend='</a>';

            if ($withpicto) $result.=($link.img_object($label, 'payment', 'class="classfortooltip"').$linkend.' ');
			if ($withpicto && $withpicto != 2) $result.=' ';
			if ($withpicto != 2) $result.=$link.($maxlen?dol_trunc($this->ref, $maxlen):$this->ref).$linkend;
		}

		return $result;
	}
}
