<?php
/* Copyright (C) 2002      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2009      Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2014      Florian Henry        <florian.henry@open-concept.pro>
 * Copyright (C) 2015-2017 Alexandre Spangaro   <aspangaro@zendsi.com>
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
    /**
	 * @var string ID to identify managed object
	 */
	public $element='don';

    /**
	 * @var string Name of table without prefix where object is stored
	 */
	public $table_element='don';

	/**
	 * @var int Field with ID of parent key if this field has a parent
	 */
	public $fk_element = 'fk_donation';

	/**
	 * 0=No test on entity, 1=Test with field entity, 2=Test with link by societe
	 * @var int
	 */
	public $ismultientitymanaged = 1;

    /**
	 * @var string String with name of icon for myobject. Must be the part after the 'object_' into object_myobject.png
	 */
	public $picto = 'generic';

    public $date;
    public $amount;
    public $societe;

    /**
	 * @var string Address
	 */
	public $address;

    public $zip;
    public $town;
    public $email;
    public $public;

    /**
     * @var int ID
     */
    public $fk_project;

    /**
     * @var int ID
     */
    public $fk_typepayment;

	public $num_payment;
	public $date_valid;
	public $modepaymentid = 0;

	public $labelstatut;
	public $labelstatutshort;

	/**
	 * Draft
	 */
	const STATUS_DRAFT = 0;


    /**
     *  Constructor
     *
     *  @param	DoliDB	$db 	Database handler
     */
    function __construct($db)
    {
         $this->db = $db;
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

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.NotCamelCaps
    /**
     *  Renvoi le libelle d'un statut donne
     *
     *  @param	int		$statut        	Id statut
     *  @param  int		$mode          	0=libelle long, 1=libelle court, 2=Picto + Libelle court, 3=Picto, 4=Picto + Libelle long, 5=Libelle court + Picto
     *  @return string 			       	Libelle du statut
     */
    function LibStatut($statut,$mode=0)
    {
        // phpcs:enable
    	if (empty($this->labelstatut) || empty($this->labelstatutshort))
    	{
	    	global $langs;
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

        if ($mode == 0)
        {
            return $this->labelstatut[$statut];
        }
        elseif ($mode == 1)
        {
            return $this->labelstatutshort[$statut];
        }
        elseif ($mode == 2)
        {
            if ($statut == -1) return img_picto($this->labelstatut[$statut],'statut5').' '.$this->labelstatutshort[$statut];
            elseif ($statut == 0)  return img_picto($this->labelstatut[$statut],'statut0').' '.$this->labelstatutshort[$statut];
            elseif ($statut == 1)  return img_picto($this->labelstatut[$statut],'statut1').' '.$this->labelstatutshort[$statut];
            elseif ($statut == 2)  return img_picto($this->labelstatut[$statut],'statut6').' '.$this->labelstatutshort[$statut];
        }
        elseif ($mode == 3)
        {
            if ($statut == -1) return img_picto($this->labelstatut[$statut],'statut5');
            elseif ($statut == 0)  return img_picto($this->labelstatut[$statut],'statut0');
            elseif ($statut == 1)  return img_picto($this->labelstatut[$statut],'statut1');
            elseif ($statut == 2)  return img_picto($this->labelstatut[$statut],'statut6');
        }
        elseif ($mode == 4)
        {
            if ($statut == -1) return img_picto($this->labelstatut[$statut],'statut5').' '.$this->labelstatut[$statut];
            elseif ($statut == 0)  return img_picto($this->labelstatut[$statut],'statut0').' '.$this->labelstatut[$statut];
            elseif ($statut == 1)  return img_picto($this->labelstatut[$statut],'statut1').' '.$this->labelstatut[$statut];
            elseif ($statut == 2)  return img_picto($this->labelstatut[$statut],'statut6').' '.$this->labelstatut[$statut];
        }
        elseif ($mode == 5)
        {
            if ($statut == -1) return $this->labelstatutshort[$statut].' '.img_picto($this->labelstatut[$statut],'statut5');
            elseif ($statut == 0)  return $this->labelstatutshort[$statut].' '.img_picto($this->labelstatut[$statut],'statut0');
            elseif ($statut == 1)  return $this->labelstatutshort[$statut].' '.img_picto($this->labelstatut[$statut],'statut1');
            elseif ($statut == 2)  return $this->labelstatutshort[$statut].' '.img_picto($this->labelstatut[$statut],'statut6');
        }
        elseif ($mode == 6)
        {
            if ($statut == -1) return $this->labelstatut[$statut].' '.img_picto($this->labelstatut[$statut],'statut5');
            elseif ($statut == 0)  return $this->labelstatut[$statut].' '.img_picto($this->labelstatut[$statut],'statut0');
            elseif ($statut == 1)  return $this->labelstatut[$statut].' '.img_picto($this->labelstatut[$statut],'statut1');
            elseif ($statut == 2)  return $this->labelstatut[$statut].' '.img_picto($this->labelstatut[$statut],'statut6');
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
        $sql.= ", ".($this->modepaymentid?$this->modepaymentid:"null");
        $sql.= ", '".$this->db->escape($this->firstname)."'";
        $sql.= ", '".$this->db->escape($this->lastname)."'";
        $sql.= ", '".$this->db->escape($this->societe)."'";
        $sql.= ", '".$this->db->escape($this->address)."'";
        $sql.= ", '".$this->db->escape($this->zip)."'";
        $sql.= ", '".$this->db->escape($this->town)."'";
        $sql.= ", ".($this->country_id > 0 ? $this->country_id : '0');
        $sql.= ", ".((int) $this->public);
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
        if (! $error) {
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
            //$res = $this->setValid($user);
            //if ($res < 0) $error++;
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
        $sql .= ",email='".$this->db->escape($this->email)."'";
        $sql .= ",phone='".$this->db->escape($this->phone)."'";
        $sql .= ",phone_mobile='".$this->db->escape($this->phone_mobile)."'";
        $sql .= ",fk_statut=".$this->statut;
        $sql .= " WHERE rowid = ".$this->id;

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
            if (! $error)
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
        $sql.= " d.fk_country, d.country as country_olddata, d.public, d.amount, d.fk_payment, d.paid, d.note_private, d.note_public, d.email, d.phone, ";
        $sql.= " d.phone_mobile, d.fk_projet as fk_project, d.model_pdf,";
        $sql.= " p.ref as project_ref,";
        $sql.= " cp.libelle as payment_label, cp.code as payment_code,";
        $sql.= " c.code as country_code, c.label as country";
        $sql.= " FROM ".MAIN_DB_PREFIX."don as d";
        $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."projet as p ON p.rowid = d.fk_projet";
        $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."c_paiement as cp ON cp.id = d.fk_payment";
        $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."c_country as c ON d.fk_country = c.rowid";
        $sql.= " WHERE d.entity IN (".getEntity('donation').")";
        if (! empty($id))
        {
        	$sql.= " AND d.rowid=".$id;
        }
        else if (! empty($ref))
        {
        	$sql.= " AND d.ref='".$this->db->escape($ref)."'";
        }

        dol_syslog(get_class($this)."::fetch", LOG_DEBUG);
        $resql=$this->db->query($sql);
        if ($resql)
        {
            if ($this->db->num_rows($resql))
            {
                $obj = $this->db->fetch_object($resql);

                $this->id             	= $obj->rowid;
                $this->ref            	= $obj->rowid;
                $this->datec          	= $this->db->jdate($obj->datec);
		$this->date_creation  	= $this->db->jdate($obj->datec);
                $this->date_valid     	= $this->db->jdate($obj->date_valid);
                $this->date_validation	= $this->db->jdate($obj->date_valid);
                $this->datem		= $this->db->jdate($obj->datem);
                $this->date_modification= $this->db->jdate($obj->datem);
                $this->date           	= $this->db->jdate($obj->datedon);
                $this->firstname      	= $obj->firstname;
                $this->lastname       	= $obj->lastname;
                $this->societe        	= $obj->societe;
                $this->statut         	= $obj->fk_statut;
                $this->address        	= $obj->address;
                $this->town           	= $obj->town;
                $this->zip            	= $obj->zip;
                $this->town           	= $obj->town;
                $this->country_id     	= $obj->fk_country;
                $this->country_code   	= $obj->country_code;
                $this->country        	= $obj->country;
                $this->country_olddata	= $obj->country_olddata;	// deprecated
		$this->email          	= $obj->email;
                $this->phone          	= $obj->phone;
                $this->phone_mobile   	= $obj->phone_mobile;
                $this->project        	= $obj->project_ref;
                $this->fk_projet      	= $obj->fk_project;   // deprecated
                $this->fk_project     	= $obj->fk_project;
                $this->public         	= $obj->public;
                $this->modepaymentid  	= $obj->fk_payment;
                $this->modepaymentcode 	= $obj->payment_code;
                $this->modepayment    	= $obj->payment_label;
		$this->paid		= $obj->paid;
                $this->amount         	= $obj->amount;
                $this->note_private	= $obj->note_private;
                $this->note_public	= $obj->note_public;
                $this->modelpdf       	= $obj->model_pdf;

                // Retreive all extrafield
                // fetch optionals attributes and labels
                $this->fetch_optionals();
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
	 *	Validate a intervention
     *
     *	@param		User		$user		User that validate
     *  @param		int			$notrigger	1=Does not execute triggers, 0= execute triggers
     *	@return		int						<0 if KO, >0 if OK
     */
	function setValid($user, $notrigger=0)
	{
		return $this->valid_promesse($this->id, $user->id, $notrigger);
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.NotCamelCaps
	/**
     *    Validate a promise of donation
     *
     *    @param	int		$id   		id of donation
     *    @param  	int		$userid  	User who validate the donation/promise
     *    @param	int		$notrigger	Disable triggers
     *    @return   int     			<0 if KO, >0 if OK
     */
	function valid_promesse($id, $userid, $notrigger=0)
	{
		// phpcs:enable
		global $langs, $user;

		$error=0;

		$this->db->begin();

        $sql = "UPDATE ".MAIN_DB_PREFIX."don SET fk_statut = 1, fk_user_valid = ".$userid." WHERE rowid = ".$id." AND fk_statut = 0";

        $resql=$this->db->query($sql);
        if ($resql)
        {
            if ($this->db->affected_rows($resql))
            {
            	if (!$notrigger)
            	{
            		// Call trigger
            		$result=$this->call_trigger('DON_VALIDATE',$user);
            		if ($result < 0) { $error++; }
            		// End call triggers
            	}
            }
        }
        else
        {
            $error++;
            $this->error = $this->db->lasterror();
        }

        if (!$error)
        {
        	$this->db->commit();
        	return 1;
        }
        else
        {
        	$this->db->rollback();
        	return -1;
        }
    }

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.NotCamelCaps
    /**
     *    Classify the donation as paid, the donation was received
     *
     *    @param	int		$id           	    id of donation
     *    @param    int		$modepayment   	    mode of payment
     *    @return   int      					<0 if KO, >0 if OK
     */
    function set_paid($id, $modepayment=0)
    {
        // phpcs:enable
        $sql = "UPDATE ".MAIN_DB_PREFIX."don SET fk_statut = 2";
        if ($modepayment)
        {
            $sql .= ", fk_payment=".$modepayment;
        }
        $sql .=  " WHERE rowid = ".$id." AND fk_statut = 1";

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

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.NotCamelCaps
    /**
     *    Set donation to status cancelled
     *
     *    @param	int		$id   	    id of donation
     *    @return   int     			<0 if KO, >0 if OK
     */
    function set_cancel($id)
    {
        // phpcs:enable
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

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.NotCamelCaps
    /**
     *  Sum of donations
     *
     *	@param	string	$param	1=promesses de dons validees , 2=xxx, 3=encaisses
     *	@return	int				Summ of donations
     */
    function sum_donations($param)
    {
        // phpcs:enable
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

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.NotCamelCaps
    /**
     *	Charge indicateurs this->nb pour le tableau de bord
     *
     *	@return     int         <0 if KO, >0 if OK
     */
    function load_state_board()
    {
        // phpcs:enable
        global $conf;

        $this->nb=array();

        $sql = "SELECT count(d.rowid) as nb";
        $sql.= " FROM ".MAIN_DB_PREFIX."don as d";
        $sql.= " WHERE d.fk_statut > 0";
        $sql.= " AND d.entity IN (".getEntity('donation').")";

        $resql=$this->db->query($sql);
        if ($resql)
        {
            while ($obj=$this->db->fetch_object($resql))
            {
                $this->nb["donations"]=$obj->nb;
            }
            $this->db->free($resql);
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
     *	Return clicable name (with picto eventually)
     *
     *	@param	int		$withpicto		0=No picto, 1=Include picto into link, 2=Only picto
     *	@param	int  	$notooltip		1=Disable tooltip
     *	@return	string					Chaine avec URL
     */
    function getNomUrl($withpicto=0, $notooltip=0)
    {
        global $langs;

        $result='';
        $label=$langs->trans("ShowDonation").': '.$this->id;

        $linkstart = '<a href="'.DOL_URL_ROOT.'/don/card.php?id='.$this->id.'" title="'.dol_escape_htmltag($label, 1).'" class="classfortooltip">';
        $linkend='</a>';

        $result .= $linkstart;
        if ($withpicto) $result.=img_object(($notooltip?'':$label), ($this->picto?$this->picto:'generic'), ($notooltip?(($withpicto != 2) ? 'class="paddingright"' : ''):'class="'.(($withpicto != 2) ? 'paddingright ' : '').'classfortooltip"'), 0, 0, $notooltip?0:1);
        if ($withpicto != 2) $result.= $this->ref;
        $result .= $linkend;

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


	/**
	 *  Create a document onto disk according to template module.
	 *
	 *  @param	    string		$modele			Force template to use ('' to not force)
	 *  @param		Translate	$outputlangs	objet lang a utiliser pour traduction
	 *  @param      int			$hidedetails    Hide details of lines
	 *  @param      int			$hidedesc       Hide description
	 *  @param      int			$hideref        Hide ref
	 *  @return     int         				0 if KO, 1 if OK
	 */
	public function generateDocument($modele, $outputlangs, $hidedetails=0, $hidedesc=0, $hideref=0)
	{
		global $conf,$langs;

		$langs->load("bills");

		if (! dol_strlen($modele)) {

			$modele = 'html_cerfafr';

			if ($this->modelpdf) {
				$modele = $this->modelpdf;
			} elseif (! empty($conf->global->DON_ADDON_MODEL)) {
				$modele = $conf->global->DON_ADDON_MODEL;
			}
		}

		$modelpath = "core/modules/dons/";

		// TODO Restore use of commonGenerateDocument instead of dedicated code here
		//return $this->commonGenerateDocument($modelpath, $modele, $outputlangs, $hidedetails, $hidedesc, $hideref);

		// Increase limit for PDF build
		$err=error_reporting();
		error_reporting(0);
		@set_time_limit(120);
		error_reporting($err);

		$srctemplatepath='';

		// If selected modele is a filename template (then $modele="modelname:filename")
		$tmp=explode(':',$modele,2);
		if (! empty($tmp[1]))
		{
			$modele=$tmp[0];
			$srctemplatepath=$tmp[1];
		}

		// Search template files
		$file=''; $classname=''; $filefound=0;
		$dirmodels=array('/');
		if (is_array($conf->modules_parts['models'])) $dirmodels=array_merge($dirmodels,$conf->modules_parts['models']);
		foreach($dirmodels as $reldir)
		{
			foreach(array('html','doc','pdf') as $prefix)
			{
				$file = $prefix."_".preg_replace('/^html_/','',$modele).".modules.php";

				// On verifie l'emplacement du modele
				$file=dol_buildpath($reldir."core/modules/dons/".$file,0);
				if (file_exists($file))
				{
					$filefound=1;
					$classname=$prefix.'_'.$modele;
					break;
				}
			}
			if ($filefound) break;
		}

		// Charge le modele
		if ($filefound)
		{
			require_once $file;

			$object=$this;

			$classname = $modele;
			$obj = new $classname($this->db);

			// We save charset_output to restore it because write_file can change it if needed for
			// output format that does not support UTF8.
			$sav_charset_output=$outputlangs->charset_output;
			if ($obj->write_file($object,$outputlangs, $srctemplatepath, $hidedetails, $hidedesc, $hideref) > 0)
			{
				$outputlangs->charset_output=$sav_charset_output;

				// we delete preview files
				require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
				dol_delete_preview($object);
				return 1;
			}
			else
			{
				$outputlangs->charset_output=$sav_charset_output;
				dol_syslog("Erreur dans don_create");
				dol_print_error($this->db,$obj->error);
				return 0;
			}
		}
		else
		{
			print $langs->trans("Error")." ".$langs->trans("ErrorFileDoesNotExists",$file);
			return 0;
		}
	}
}
