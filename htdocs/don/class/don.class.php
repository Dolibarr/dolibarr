<?php
/* Copyright (C) 2002      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2009      Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2014      Florian Henry        <florian.henry@open-concept.pro>
 * Copyright (C) 2015      Alexandre Spangaro   <aspangaro.dolibarr@gmail.com>
 * Copyright (C) 2016      Juanjo Menent        <jmenent@2byte.es>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *   	\file       htdocs/don/class/don.class.php
 *		\ingroup    Donation
 *		\brief      File of class to manage donations
 */

require_once DOL_DOCUMENT_ROOT .'/core/class/commonobject.class.php';


/**
 *		Class to manage donations
 */
class Don extends CommonObject
{
    public $element='don'; 					// Id that identify managed objects
    public $table_element='don';			// Name of table without prefix where object is stored
	public $fk_element = 'fk_donation';
	protected $ismultientitymanaged = 1;  	// 0=No test on entity, 1=Test with field entity, 2=Test with link by societe
    var $picto = 'generic';
    
    var $date;
    var $amount;
    var $societe;
    var $address;
    var $zip;
    var $town;
    var $email;
    var $public;
    var $fk_project;
    var $fk_typepayment;
	var $num_payment;
	var $date_valid;

	/**
	 * @deprecated
	 * @see note_private, note_public
	 */
	var $commentaire;

    /**
     *  Constructor
     *
     *  @param	DoliDB	$db 	Database handler
     */
    function __construct($db)
    {
        global $langs;

        $this->db = $db;
        $this->modepaiementid = 0;

        $langs->load("donations");
        $this->labelstatut[-1]=$langs->trans("Canceled");
        $this->labelstatut[0]=$langs->trans("DonationStatusPromiseNotValidated");
        $this->labelstatut[1]=$langs->trans("DonationStatusPromiseValidated");
        $this->labelstatut[2]=$langs->trans("DonationStatusPaid");
        $this->labelstatutshort[-1]=$langs->trans("Canceled");
        $this->labelstatutshort[0]=$langs->trans("DonationStatusPromiseNotValidatedShort");
        $this->labelstatutshort[1]=$langs->trans("DonationStatusPromiseValidatedShort");
        $this->labelstatutshort[2]=$langs->trans("DonationStatusPaidShort");
    }


    /**
     * 	Retourne le libelle du statut d'un don (brouillon, validee, abandonnee, payee)
     *
     *  @param	int		$mode       0=libelle long, 1=libelle court, 2=Picto + Libelle court, 3=Picto, 4=Picto + Libelle long
     *  @return string        		Libelle
     */
    function getLibStatut($mode=0)
    {
        return $this->LibStatut($this->statut,$mode);
    }

    /**
     *  Renvoi le libelle d'un statut donne
     *
     *  @param	int		$statut        	Id statut
     *  @param  int		$mode          	0=libelle long, 1=libelle court, 2=Picto + Libelle court, 3=Picto, 4=Picto + Libelle long, 5=Libelle court + Picto
     *  @return string 			       	Libelle du statut
     */
    function LibStatut($statut,$mode=0)
    {
        global $langs;

        if ($mode == 0)
        {
            return $this->labelstatut[$statut];
        }
        if ($mode == 1)
        {
            return $this->labelstatutshort[$statut];
        }
        if ($mode == 2)
        {
            if ($statut == -1) return img_picto($this->labelstatut[$statut],'statut5').' '.$this->labelstatutshort[$statut];
            if ($statut == 0)  return img_picto($this->labelstatut[$statut],'statut0').' '.$this->labelstatutshort[$statut];
            if ($statut == 1)  return img_picto($this->labelstatut[$statut],'statut1').' '.$this->labelstatutshort[$statut];
            if ($statut == 2)  return img_picto($this->labelstatut[$statut],'statut6').' '.$this->labelstatutshort[$statut];
        }
        if ($mode == 3)
        {
            if ($statut == -1) return img_picto($this->labelstatut[$statut],'statut5');
            if ($statut == 0)  return img_picto($this->labelstatut[$statut],'statut0');
            if ($statut == 1)  return img_picto($this->labelstatut[$statut],'statut1');
            if ($statut == 2)  return img_picto($this->labelstatut[$statut],'statut6');
        }
        if ($mode == 4)
        {
            if ($statut == -1) return img_picto($this->labelstatut[$statut],'statut5').' '.$this->labelstatut[$statut];
            if ($statut == 0)  return img_picto($this->labelstatut[$statut],'statut0').' '.$this->labelstatut[$statut];
            if ($statut == 1)  return img_picto($this->labelstatut[$statut],'statut1').' '.$this->labelstatut[$statut];
            if ($statut == 2)  return img_picto($this->labelstatut[$statut],'statut6').' '.$this->labelstatut[$statut];
        }
            if ($mode == 5)
        {
            if ($statut == -1) return $this->labelstatutshort[$statut].' '.img_picto($this->labelstatut[$statut],'statut5');
            if ($statut == 0)  return $this->labelstatutshort[$statut].' '.img_picto($this->labelstatut[$statut],'statut0');
            if ($statut == 1)  return $this->labelstatutshort[$statut].' '.img_picto($this->labelstatut[$statut],'statut1');
            if ($statut == 2)  return $this->labelstatutshort[$statut].' '.img_picto($this->labelstatut[$statut],'statut6');
        }
        if ($mode == 6)
        {
            if ($statut == -1) return $this->labelstatut[$statut].' '.img_picto($this->labelstatut[$statut],'statut5');
            if ($statut == 0)  return $this->labelstatut[$statut].' '.img_picto($this->labelstatut[$statut],'statut0');
            if ($statut == 1)  return $this->labelstatut[$statut].' '.img_picto($this->labelstatut[$statut],'statut1');
            if ($statut == 2)  return $this->labelstatut[$statut].' '.img_picto($this->labelstatut[$statut],'statut6');
        }
    }


    /**
     *  Initialise an instance with random values.
     *  Used to build previews or test instances.
     *	id must be 0 if object instance is a specimen.
     *
     *  @return	void
     */
    function initAsSpecimen()
    {
        global $conf, $user,$langs;

        $now = dol_now();
        
        // Charge tableau des id de societe socids
        $socids = array();

        $sql = "SELECT rowid";
        $sql.= " FROM ".MAIN_DB_PREFIX."societe";
        $sql.= " WHERE client IN (1, 3)";
        $sql.= " AND entity = ".$conf->entity;
        $sql.= " LIMIT 10";

        $resql = $this->db->query($sql);
        if ($resql)
        {
            $num_socs = $this->db->num_rows($resql);
            $i = 0;
            while ($i < $num_socs)
            {
                $i++;

                $row = $this->db->fetch_row($resql);
                $socids[$i] = $row[0];
            }
        }

        // Initialise parametres
        $this->id=0;
        $this->ref = 'SPECIMEN';
        $this->specimen=1;
        $this->lastname = 'Doe';
        $this->firstname = 'John';
        $this->socid = 1;
        $this->date = $now;
        $this->date_valid = $now;
        $this->amount = 100;
        $this->public = 1;
        $this->societe = 'The Company';
        $this->address = 'Twist road';
        $this->zip = '99999';
        $this->town = 'Town';
        $this->note_private='Private note';
        $this->note_public='Public note';
        $this->email='email@email.com';
        $this->note='';
        $this->statut=1;
    }


    /**
     *	Check params and init ->errors array.
     *  TODO This function seems to not be used by core code.
     *
     *	@param	int	$minimum	Minimum
     *	@return	int				0 if KO, >0 if OK
     */
    function check($minimum=0)
    {
    	global $langs;
    	$langs->load('main');
    	$langs->load('companies');

    	$error_string = array();
        $err = 0;

        if (dol_strlen(trim($this->societe)) == 0)
        {
            if ((dol_strlen(trim($this->lastname)) + dol_strlen(trim($this->firstname))) == 0)
            {
                $error_string[] = $langs->trans('ErrorFieldRequired',$langs->trans('Company').'/'.$langs->trans('Firstname').'-'.$langs->trans('Lastname'));
                $err++;
            }
        }

        if (dol_strlen(trim($this->address)) == 0)
        {
            $error_string[] = $langs->trans('ErrorFieldRequired',$langs->trans('Address'));
            $err++;
        }

        if (dol_strlen(trim($this->zip)) == 0)
        {
            $error_string[] = $langs->trans('ErrorFieldRequired',$langs->trans('Zip'));
            $err++;
        }

        if (dol_strlen(trim($this->town)) == 0)
        {
            $error_string[] = $langs->trans('ErrorFieldRequired',$langs->trans('Town'));
            $err++;
        }

        if (dol_strlen(trim($this->email)) == 0)
        {
            $error_string[] = $langs->trans('ErrorFieldRequired',$langs->trans('EMail'));
            $err++;
        }

        $this->amount = trim($this->amount);

        $map = range(0,9);
        $len=dol_strlen($this->amount);
        for ($i = 0; $i < $len; $i++)
        {
            if (!isset($map[substr($this->amount, $i, 1)] ))
            {
                $error_string[] = $langs->trans('ErrorFieldRequired',$langs->trans('Amount'));
                $err++;
                $amount_invalid = 1;
                break;
            }
        }

        if (! $amount_invalid)
        {
            if ($this->amount == 0)
            {
                $error_string[] = $langs->trans('ErrorFieldRequired',$langs->trans('Amount'));
                $err++;
            }
            else
            {
                if ($this->amount < $minimum && $minimum > 0)
                {
                    $error_string[] = $langs->trans('MinimumAmount',$langs->trans('$minimum'));
                    $err++;
                }
            }
        }

        if ($err)
        {
            $this->errors = $error_string;
            return 0;
        }
        else
		{
            return 1;
        }
    }

    /**
     * Create donation record into database
     *
     * @param	User	$user		User who created the donation
     * @param	int		$notrigger	Disable triggers
     * @return  int  		        <0 if KO, id of created donation if OK
     * TODO    add numbering module for Ref
     */
    function create($user, $notrigger=0)
    {
        global $conf, $langs;

		$error = 0;
		$ret = 0;
        $now=dol_now();

        // Clean parameters
        $this->address=($this->address>0?$this->address:$this->address);
        $this->zip=($this->zip>0?$this->zip:$this->zip);
        $this->town=($this->town>0?$this->town:$this->town);
        $this->country_id=($this->country_id>0?$this->country_id:$this->country_id);
        $this->country=($this->country?$this->country:$this->country);

        $this->db->begin();

        $sql = "INSERT INTO ".MAIN_DB_PREFIX."don (";
        $sql.= "datec";
        $sql.= ", entity";
        $sql.= ", amount";
        $sql.= ", fk_payment";
        $sql.= ", firstname";
        $sql.= ", lastname";
        $sql.= ", societe";
        $sql.= ", address";
        $sql.= ", zip";
        $sql.= ", town";
        // $sql.= ", country"; -- Deprecated
        $sql.= ", fk_country";
        $sql.= ", public";
        $sql.= ", fk_projet";
        $sql.= ", note_private";
        $sql.= ", note_public";
        $sql.= ", fk_user_author";
        $sql.= ", fk_user_valid";
        $sql.= ", datedon";
        $sql.= ", email";
        $sql.= ", phone";
        $sql.= ", phone_mobile";
        $sql.= ") VALUES (";
        $sql.= " '".$this->db->idate($now)."'";
        $sql.= ", ".$conf->entity;
        $sql.= ", ".price2num($this->amount);
        $sql.= ", ".($this->modepaiementid?$this->modepaiementid:"null");
        $sql.= ", '".$this->db->escape($this->firstname)."'";
        $sql.= ", '".$this->db->escape($this->lastname)."'";
        $sql.= ", '".$this->db->escape($this->societe)."'";
        $sql.= ", '".$this->db->escape($this->address)."'";
        $sql.= ", '".$this->db->escape($this->zip)."'";
        $sql.= ", '".$this->db->escape($this->town)."'";
		$sql.= ", ".$this->country_id;
        $sql.= ", ".$this->public;
        $sql.= ", ".($this->fk_project > 0?$this->fk_project:"null");
       	$sql.= ", ".(!empty($this->note_private)?("'".$this->db->escape($this->note_private)."'"):"NULL");
		$sql.= ", ".(!empty($this->note_public)?("'".$this->db->escape($this->note_public)."'"):"NULL");
        $sql.= ", ".$user->id;
        $sql.= ", null";
        $sql.= ", '".$this->db->idate($this->date)."'";
        $sql.= ", '".$this->db->escape($this->email)."'";
        $sql.= ", '".$this->db->escape($this->phone)."'";
        $sql.= ", '".$this->db->escape($this->phone_mobile)."'";
        $sql.= ")";

        dol_syslog(get_class($this)."::create", LOG_DEBUG);
        $resql = $this->db->query($sql);
        if ($resql)
        {
			$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX."don");
			$ret = $this->id;

            if (!$notrigger)
            {
                // Call trigger
                $result=$this->call_trigger('DON_CREATE',$user);
                if ($result < 0) { $error++; }
                // End call triggers
            }
        }
		else
        {
            $this->error = $this->db->lasterror();
            $this->errno = $this->db->lasterrno();
            $error++;
        }

		// Update extrafield
        if (!$error) {
        	if (empty($conf->global->MAIN_EXTRAFIELDS_DISABLED)) // For avoid conflicts if trigger used
        	{
        		$result=$this->insertExtraFields();
        		if ($result < 0)
        		{
        			$error++;
        		}
        	}
        }

		if (!$error && !empty($conf->global->MAIN_DISABLEDRAFTSTATUS))
        {
            $res = $this->setValid($user);
            if ($res < 0) $error++;
        }

        if (!$error)
        {
            $this->db->commit();
            return $ret;
        }
        else
        {
            $this->db->rollback();
            return -1;
        }
    }

    /**
     *  Update a donation record
     *
     *  @param 		User	$user   Objet utilisateur qui met a jour le don
     *  @param      int		$notrigger	Disable triggers
     *  @return     int      		>0 if OK, <0 if KO
     */
    function update($user, $notrigger=0)
    {
        global $langs, $conf;

		$error=0;

        // Clean parameters
        $this->address=($this->address>0?$this->address:$this->address);
        $this->zip=($this->zip>0?$this->zip:$this->zip);
        $this->town=($this->town>0?$this->town:$this->town);
        $this->country_id=($this->country_id>0?$this->country_id:$this->country_id);
        $this->country=($this->country?$this->country:$this->country);

        $this->db->begin();

        $sql = "UPDATE ".MAIN_DB_PREFIX."don SET ";
        $sql .= "amount = " . price2num($this->amount);
        $sql .= ",fk_payment = ".($this->modepaymentid?$this->modepaymentid:"null");
        $sql .= ",firstname = '".$this->db->escape($this->firstname)."'";
        $sql .= ",lastname='".$this->db->escape($this->lastname)."'";
        $sql .= ",societe='".$this->db->escape($this->societe)."'";
        $sql .= ",address='".$this->db->escape($this->address)."'";
        $sql .= ",zip='".$this->db->escape($this->zip)."'";
        $sql .= ",town='".$this->db->escape($this->town)."'";
        $sql .= ",fk_country = ".$this->country_id;
        $sql .= ",public=".$this->public;
        $sql .= ",fk_projet=".($this->fk_project>0?$this->fk_project:'null');
        $sql .= ",note_private=".(!empty($this->note_private)?("'".$this->db->escape($this->note_private)."'"):"NULL");
        $sql .= ",note_public=".(!empty($this->note_public)?("'".$this->db->escape($this->note_public)."'"):"NULL");
        $sql .= ",datedon='".$this->db->idate($this->date)."'";
        $sql .= ",date_valid=".($this->date_valid?"'".$this->db->idate($this->date)."'":"null");
        $sql .= ",email='".$this->email."'";
        $sql .= ",phone='".$this->phone."'";
        $sql .= ",phone_mobile='".$this->phone_mobile."'";
        $sql .= ",fk_statut=".$this->statut;
        $sql .= " WHERE rowid = '".$this->id."'";

        dol_syslog(get_class($this)."::Update", LOG_DEBUG);
        $resql=$this->db->query($sql);
        if ($resql)
        {
            if (!$notrigger)
            {
				// Call trigger
                $result=$this->call_trigger('DON_MODIFY',$user);
                if ($result < 0) { $error++; }
                // End call triggers
            }

            // Update extrafield
            if (!$error)
			{
              	if (empty($conf->global->MAIN_EXTRAFIELDS_DISABLED)) // For avoid conflicts if trigger used
               	{
               		$result=$this->insertExtraFields();
               		if ($result < 0)
               		{
               			$error++;
               		}
               	}
            }

			if (! $error )
            {
                $this->db->commit();
                $result = 1;
            }
            else
            {
                $this->db->rollback();
                $result = -1;
            }
        }
        else
		{
            $this->error = $this->db->lasterror();
            $this->errors[] = $this->error;
            $this->db->rollback();
            dol_syslog(get_class($this)."::Update error -2 " . $this->error, LOG_ERR);
            $result = -2;
        }
		return $result;
	}

    /**
     *    Delete a donation from database
     *
     *    @param       User		$user            User
     *    @param       int		$notrigger       Disable triggers
     *    @return      int       			      <0 if KO, 0 if not possible, >0 if OK
     */
    function delete($user, $notrigger=0)
    {
		global $user, $conf, $langs;
		require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';

		$error = 0;

		$this->db->begin();

   		if (! $error)
        {
            if (!$notrigger)
            {
                // Call trigger
                $result=$this->call_trigger('DON_DELETE',$user);

                if ($result < 0) {
                    $error++;
                }
                // End call triggers
            }
        }

        // Delete donation
        if (! $error)
        {
	        $sql = "DELETE FROM " . MAIN_DB_PREFIX . "don_extrafields";
	        $sql.= " WHERE fk_object=" . $this->id;

	        $resql = $this->db->query($sql);
	        if (! $resql)
	        {
	        	$this->errors[] = $this->db->lasterror();
	        	$error++;
	        }
        }

		if (! $error)
        {
	        $sql = "DELETE FROM " . MAIN_DB_PREFIX . "don";
	        $sql.= " WHERE rowid=" . $this->id;

	        $resql = $this->db->query($sql);
	        if (!$resql)
	        {
	        	$this->errors[] = $this->db->lasterror();
	        	$error++;
	        }
        }

    	if (! $error)
    	{
            $this->db->commit();
            return 1;
        }
        else
       {
        	foreach($this->errors as $errmsg)
        	{
				dol_syslog(get_class($this) . "::delete " . $errmsg, LOG_ERR);
				$this->error .= ($this->error ? ', ' . $errmsg : $errmsg);
			}
            dol_syslog(get_class($this) . "::delete " . $this->error, LOG_ERR);
            $this->db->rollback();
            return -1;
        }
    }

    /**
     *      Load donation from database
     *
     *      @param      int		$id      Id of donation to load
     *      @param      string	$ref        Ref of donation to load
     *      @return     int      			<0 if KO, >0 if OK
     */
    function fetch($id, $ref='')
    {
        global $conf;

        $sql = "SELECT d.rowid, d.datec, d.date_valid, d.tms as datem, d.datedon,";
        $sql.= " d.firstname, d.lastname, d.societe, d.amount, d.fk_statut, d.address, d.zip, d.town, ";
        $sql.= " d.fk_country, d.country as country_olddata, d.public, d.amount, d.fk_payment, d.paid, d.note_private, d.note_public, cp.libelle, d.email, d.phone, ";
        $sql.= " d.phone_mobile, d.fk_projet as fk_project, d.model_pdf,";
        $sql.= " p.ref as project_ref,";
        $sql.= " c.code as country_code, c.label as country";
        $sql.= " FROM ".MAIN_DB_PREFIX."don as d";
        $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."projet as p ON p.rowid = d.fk_projet";
        $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."c_paiement as cp ON cp.id = d.fk_payment";
        $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."c_country as c ON d.fk_country = c.rowid";
		if (! empty($id))
        {
        	$sql.= " WHERE d.rowid=".$id;
        }
        else if (! empty($ref))
        {
        	$sql.= " WHERE ref='".$this->db->escape($ref)."'";
        }
        $sql.= " AND d.entity = ".$conf->entity;

        dol_syslog(get_class($this)."::fetch", LOG_DEBUG);
        $resql=$this->db->query($sql);
        if ($resql)
        {
            if ($this->db->num_rows($resql))
            {
                $obj = $this->db->fetch_object($resql);

                $this->id             = $obj->rowid;
                $this->ref            = $obj->rowid;
                $this->datec          = $this->db->jdate($obj->datec);
                $this->date_valid     = $this->db->jdate($obj->date_valid);
                $this->datem          = $this->db->jdate($obj->datem);
                $this->date           = $this->db->jdate($obj->datedon);
                $this->firstname      = $obj->firstname;
                $this->lastname       = $obj->lastname;
                $this->societe        = $obj->societe;
                $this->statut         = $obj->fk_statut;
                $this->address        = $obj->address;
                $this->town           = $obj->town;
                $this->zip            = $obj->zip;
                $this->town           = $obj->town;
                $this->country_id     = $obj->fk_country;
                $this->country_code   = $obj->country_code;
                $this->country        = $obj->country;
                $this->country_olddata= $obj->country_olddata;	// deprecated
				$this->email          = $obj->email;
                $this->phone          = $obj->phone;
                $this->phone_mobile   = $obj->phone_mobile;
                $this->project        = $obj->project_ref;
                $this->fk_projet      = $obj->fk_project;   // deprecated
                $this->fk_project     = $obj->fk_project;
                $this->public         = $obj->public;
                $this->modepaymentid  = $obj->fk_payment;
                $this->modepayment    = $obj->libelle;
				$this->paid			  = $obj->paid;
                $this->amount         = $obj->amount;
                $this->note_private	  = $obj->note_private;
                $this->note_public	  = $obj->note_public;
                $this->modelpdf       = $obj->model_pdf;
                $this->commentaire    = $obj->note;	// deprecated

				// Retrieve all extrafield for thirdparty
                // fetch optionals attributes and labels
                require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
                $extrafields=new ExtraFields($this->db);
                $extralabels=$extrafields->fetch_name_optionals_label($this->table_element,true);
                $this->fetch_optionals($this->id,$extralabels);
            }
            return 1;
        }
        else
        {
            dol_print_error($this->db);
            return -1;
        }

    }

    /**
     *    Validate a promise of donation
     *
     *    @param	int		$id   		id of donation
     *    @param  	int		$userid  	User who validate the donation/promise
     *    @return   int     			<0 if KO, >0 if OK
     */
    function valid_promesse($id, $userid)
    {

        $sql = "UPDATE ".MAIN_DB_PREFIX."don SET fk_statut = 1, fk_user_valid = ".$userid." WHERE rowid = ".$id." AND fk_statut = 0";

        $resql=$this->db->query($sql);
        if ($resql)
        {
            if ( $this->db->affected_rows($resql) )
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
            dol_print_error($this->db);
            return -1;
        }
    }

    /**
     *    Classify the donation as paid, the donation was received
     *
     *    @param	int		$id           	    id of donation
     *    @param    int		$modepayment   	    mode of payment
     *    @return   int      					<0 if KO, >0 if OK
     */
    function set_paid($id, $modepayment='')
    {
        $sql = "UPDATE ".MAIN_DB_PREFIX."don SET fk_statut = 2";
        if ($modepayment)
        {
            $sql .= ", fk_payment=$modepayment";
        }
        $sql .=  " WHERE rowid = $id AND fk_statut = 1";

        $resql=$this->db->query($sql);
        if ($resql)
        {
            if ($this->db->affected_rows($resql))
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
            dol_print_error($this->db);
            return -1;
        }
    }

    /**
     *    Set donation to status cancelled
     *
     *    @param	int		$id   	    id of donation
     *    @return   int     			<0 if KO, >0 if OK
     */
    function set_cancel($id)
    {
        $sql = "UPDATE ".MAIN_DB_PREFIX."don SET fk_statut = -1 WHERE rowid = ".$id;

        $resql=$this->db->query($sql);
        if ($resql)
        {
            if ( $this->db->affected_rows($resql) )
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
            dol_print_error($this->db);
            return -1;
        }
    }

    /**
     *  Sum of donations
     *
     *	@param	string	$param	1=promesses de dons validees , 2=xxx, 3=encaisses
     *	@return	int				Summ of donations
     */
    function sum_donations($param)
    {
        global $conf;

        $result=0;

        $sql = "SELECT sum(amount) as total";
        $sql.= " FROM ".MAIN_DB_PREFIX."don";
        $sql.= " WHERE fk_statut = ".$param;
        $sql.= " AND entity = ".$conf->entity;

        $resql=$this->db->query($sql);
        if ($resql)
        {
            $obj = $this->db->fetch_object($resql);
            $result=$obj->total;
        }

        return $result;
    }


    /**
     *	Return clicable name (with picto eventually)
     *
     *	@param	int		$withpicto		0=No picto, 1=Include picto into link, 2=Only picto
     *	@return	string					Chaine avec URL
     */
    function getNomUrl($withpicto=0)
    {
        global $langs;

        $result='';
        $label=$langs->trans("ShowDonation").': '.$this->id;

        $link = '<a href="'.DOL_URL_ROOT.'/don/card.php?id='.$this->id.'" title="'.dol_escape_htmltag($label, 1).'" class="classfortooltip">';
        $linkend='</a>';

        $picto='generic';


        if ($withpicto) $result.=($link.img_object($label, $picto, 'class="classfortooltip"').$linkend);
        if ($withpicto && $withpicto != 2) $result.=' ';
        if ($withpicto != 2) $result.=$link.$this->id.$linkend;
        return $result;
    }

	/**
	 * Information on record
	 *
	 * @param	int		$id      Id of record
	 * @return	void
	 */
	function info($id)
	{
		$sql = 'SELECT d.rowid, d.datec, d.fk_user_author, d.fk_user_valid,';
		$sql.= ' d.tms';
		$sql.= ' FROM '.MAIN_DB_PREFIX.'don as d';
		$sql.= ' WHERE d.rowid = '.$id;

		dol_syslog(get_class($this).'::info', LOG_DEBUG);
		$result = $this->db->query($sql);

		if ($result)
		{
			if ($this->db->num_rows($result))
			{
				$obj = $this->db->fetch_object($result);
				$this->id = $obj->rowid;
				if ($obj->fk_user_author)
				{
					$cuser = new User($this->db);
					$cuser->fetch($obj->fk_user_author);
					$this->user_creation = $cuser;
				}
				if ($obj->fk_user_valid)
				{
					$vuser = new User($this->db);
					$vuser->fetch($obj->fk_user_valid);
					$this->user_modification = $vuser;
				}
				$this->date_creation     = $this->db->jdate($obj->datec);
				$this->date_modification = $this->db->jdate($obj->tms);
			}
			$this->db->free($result);
		}
		else
		{
			dol_print_error($this->db);
		}
	}

}
