<?php
/* Copyright (C) 2006-2015 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2013 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2010-2013 Juanjo Menent        <jmenent@2byte.es>
 * Copyright (C) 2012      Christophe Battarel  <christophe.battarel@altairis.fr>
 * Copyright (C) 2010-2015 Juanjo Menent        <jmenent@2byte.es>
 * Copyright (C) 2012-2013 Christophe Battarel  <christophe.battarel@altairis.fr>
 * Copyright (C) 2011-2014 Philippe Grand	    <philippe.grand@atoo-net.com>
 * Copyright (C) 2012-2015 Marcos García        <marcosgdf@gmail.com>
 * Copyright (C) 2012-2015 Raphaël Doursenaud   <rdoursenaud@gpcsolutions.fr>
 * Copyright (C) 2012      Cedric Salvador      <csalvador@gpcsolutions.fr>
 * Copyright (C) 2015      Alexandre Spangaro   <aspangaro.dolibarr@gmail.com>
 * Copyright (C) 2016      Bahfir abbes         <dolipar@dolipar.org>
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
 *	\file       htdocs/core/class/commonobject.class.php
 *	\ingroup    core
 *	\brief      File of parent class of all other business classes (invoices, contracts, proposals, orders, ...)
 */


/**
 *	Parent class of all other business classes (invoices, contracts, proposals, orders, ...)
 */
abstract class CommonObject
{
    /**
     * @var DoliDb		Database handler (result of a new DoliDB)
     */
	public $db;

	/**
	 * @var int The object identifier
	 */
	public $id;

    /**
     * @var string 		Error string
     * @deprecated		Use instead the array of error strings
     * @see             errors
     */
    public $error;

	/**
     * @var string[]	Array of error strings
     */
    public $errors=array();

    /**
     * @var string		Key value used to track if data is coming from import wizard
     */
    public $import_key;

    /**
     * @var mixed		Contains data to manage extrafields
     */
    public $array_options=array();

    /**
     * @var int[]		Array of linked objects ids. Loaded by ->fetchObjectLinked
     */
    public $linkedObjectsIds;

    /**
     * @var mixed		Array of linked objects. Loaded by ->fetchObjectLinked
     */
    public $linkedObjects;

    /**
     * @var string		Column name of the ref field.
     */
    protected $table_ref_field = '';



    // Following vars are used by some objects only. We keep this property here in CommonObject to be able to provide common method using them.

    /**
     * @var string[]	Can be used to pass information when only object is provided to method
     */
    public $context=array();

    /**
     * @var string		Contains canvas name if record is an alternative canvas record
     */
    public $canvas;

	/**
	 * @var Project The related project
	 * @see fetch_projet()
	 */
	public $project;
	/**
	 * @var int The related project ID
	 * @see setProject(), project
	 */
	public $fk_project;
	/**
	 * @deprecated
	 * @see project
	 */
	public $projet;

	/**
	 * @var Contact a related contact
	 * @see fetch_contact()
	 */
	public $contact;
	/**
	 * @var int The related contact ID
	 * @see fetch_contact()
	 */
	public $contact_id;

	/**
	 * @var Societe A related thirdparty
	 * @see fetch_thirdparty()
	 */
	public $thirdparty;

	/**
	 * @var User A related user
	 * @see fetch_user()
	 */
	public $user;

	/**
	 * @var CommonObject An originating object?
	 * @see fetch_origin()
	 */
	public $origin;
	/**
	 * @var int The originating object?
	 * @see fetch_origin(), origin
	 */
	public $origin_id;

	/**
	 * @var string The object's reference
	 */
	public $ref;
	/**
	 * @var string The object's previous reference
	 */
	public $ref_previous;
	/**
	 * @var string The object's next reference
	 */
	public $ref_next;
	/**
	 * @var string An external reference for the object
	 */
	public $ref_ext;

	/**
	 * @var string
	 */
	public $element;
	/**
	 * @var string
	 */
	public $table_element;
	/**
	 * @var
	 */
	public $table_element_line;

	/**
	 * @var int The object's status
	 * @see setStatut()
	 */
	public $statut;

	/**
	 * @var string
	 * @see getFullAddress()
	 */
	public $country;
	/**
	 * @var int
	 * @see getFullAddress(), country
	 */
	public $country_id;
	/**
	 * @var string
	 * @see getFullAddress(), isInEEC(), country
	 */
	public $country_code;

	/**
	 * @var int
	 * @see fetch_barcode()
	 */
	public $barcode_type;
	/**
	 * @var string
	 * @see fetch_barcode(), barcode_type
	 */
	public $barcode_type_code;
	/**
	 * @var string
	 * @see fetch_barcode(), barcode_type
	 */
	public $barcode_type_label;
	/**
	 * @var string
	 * @see fetch_barcode(), barcode_type
	 */
	public $barcode_type_coder;

	/**
	 * @var int Payment method ID (cheque, cash, ...)
	 * @see setPaymentMethods()
	 */
	public $mode_reglement_id;

	/**
	 * @var int Payment terms ID
	 * @see setPaymentTerms()
	 */
	public $cond_reglement_id;
	/**
	 * @var int Payment terms ID
	 * @deprecated Kept for compatibility
	 * @see cond_reglement_id;
	 */
	public $cond_reglement;

	/**
	 * @var int Delivery address ID
	 * @see setDeliveryAddress()
	 */
	public $fk_delivery_address;

	/**
	 * @var int Shipping method ID
	 * @see setShippingMethod()
	 */
	public $shipping_method_id;

	/**
	 * @var string
	 * @see SetDocModel()
	 */
	public $modelpdf;

	/**
	 * @var int Bank account ID
	 * @see SetBankAccount()
	 */
	public $fk_account;

	/**
	 * @var string Public note
	 * @see update_note()
	 */
	public $note_public;
	/**
	 * @var string Private note
	 * @see update_note()
	 */
	public $note_private;
	/**
	 * @deprecated
	 * @see note_public
	 */
	public $note;

	/**
	 * @var float Total amount before taxes
	 * @see update_price()
	 */
	public $total_ht;
	/**
	 * @var float Total VAT amount
	 * @see update_price()
	 */
	public $total_tva;
	/**
	 * @var float Total local tax 1 amount
	 * @see update_price()
	 */
	public $total_localtax1;
	/**
	 * @var float Total local tax 2 amount
	 * @see update_price()
	 */
	public $total_localtax2;
	/**
	 * @var float Total amount with taxes
	 * @see update_price()
	 */
	public $total_ttc;

	/**
	 * @var CommonObjectLine[]
	 */
	public $lines;

	/**
	 * @var int
	 * @see setIncoterms()
	 */
	public $fk_incoterms;
	/**
	 * @var string
	 * @see SetIncoterms()
	 */
	public $libelle_incoterms;
	/**
	 * @var string
	 * @see display_incoterms()
	 */
	public $location_incoterms;

    public $name;
    public $lastname;
    public $firstname;
    public $civility_id;

    // No constructor as it is an abstract class

    /**
     * Check an object id/ref exists
     * If you don't need/want to instantiate object and just need to know if object exists, use this method instead of fetch
     *
	 *  @param	string	$element   	String of element ('product', 'facture', ...)
	 *  @param	int		$id      	Id of object
	 *  @param  string	$ref     	Ref of object to check
	 *  @param	string	$ref_ext	Ref ext of object to check
	 *  @return int     			<0 if KO, 0 if OK but not found, >0 if OK and exists
     */
    static function isExistingObject($element, $id, $ref='', $ref_ext='')
    {
    	global $db,$conf;

		$sql = "SELECT rowid, ref, ref_ext";
		$sql.= " FROM ".MAIN_DB_PREFIX.$element;
		$sql.= " WHERE entity IN (".getEntity($element, true).")" ;

		if ($id > 0) $sql.= " AND rowid = ".$db->escape($id);
		else if ($ref) $sql.= " AND ref = '".$db->escape($ref)."'";
		else if ($ref_ext) $sql.= " AND ref_ext = '".$db->escape($ref_ext)."'";
		else {
			$error='ErrorWrongParameters';
			dol_print_error(get_class()."::isExistingObject ".$error, LOG_ERR);
			return -1;
		}
		if ($ref || $ref_ext) $sql.= " AND entity = ".$conf->entity;

		dol_syslog(get_class()."::isExistingObject", LOG_DEBUG);
		$resql = $db->query($sql);
		if ($resql)
		{
			$num=$db->num_rows($resql);
			if ($num > 0) return 1;
			else return 0;
		}
		return -1;
    }

    /**
     * Method to output saved errors
     *
     * @return	string		String with errors
     */
    function errorsToString()
    {
    	return $this->error.(is_array($this->errors)?(($this->error!=''?', ':'').join(', ',$this->errors)):'');
    }

    /**
     *	Return full name (civility+' '+name+' '+lastname)
     *
     *	@param	Translate	$langs			Language object for translation of civility
     *	@param	int			$option			0=No option, 1=Add civility
     * 	@param	int			$nameorder		-1=Auto, 0=Lastname+Firstname, 1=Firstname+Lastname, 2=Firstname
     * 	@param	int			$maxlen			Maximum length
     * 	@return	string						String with full name
     */
    function getFullName($langs,$option=0,$nameorder=-1,$maxlen=0)
    {
        //print "lastname=".$this->lastname." name=".$this->name." nom=".$this->nom."<br>\n";
        $lastname=$this->lastname;
        $firstname=$this->firstname;
        if (empty($lastname))  $lastname=(isset($this->lastname)?$this->lastname:(isset($this->name)?$this->name:(isset($this->nom)?$this->nom:'')));

        $ret='';
        if ($option && $this->civility_id)
        {
            if ($langs->transnoentitiesnoconv("Civility".$this->civility_id)!="Civility".$this->civility_id) $ret.=$langs->transnoentitiesnoconv("Civility".$this->civility_id).' ';
            else $ret.=$this->civility_id.' ';
        }

        $ret.=dolGetFirstLastname($firstname, $lastname, $nameorder);

        return dol_trunc($ret,$maxlen);
    }

    /**
     * 	Return full address of contact
     *
     * 	@param		int			$withcountry		1=Add country into address string
     *  @param		string		$sep				Separator to use to build string
     *	@return		string							Full address string
     */
    function getFullAddress($withcountry=0,$sep="\n")
    {
    	if ($withcountry && $this->country_id && (empty($this->country_code) || empty($this->country)))
    	{
    		require_once DOL_DOCUMENT_ROOT .'/core/lib/company.lib.php';
    		$tmparray=getCountry($this->country_id,'all');
    		$this->country_code=$tmparray['code'];
    		$this->country     =$tmparray['label'];
    	}

    	return dol_format_address($this, $withcountry, $sep);
    }


    /**
     * 	Return full address for banner
     *
     * 	@param		string		$htmlkey            HTML id to make banner content unique
     *  @param      Object      $object				Object (thirdparty, thirdparty of contact for contact, null for a member)
     *	@return		string							Full address string
     */
    function getBannerAddress($htmlkey, $object)
    {
    	global $conf, $langs;

    	$countriesusingstate=array('AU','US','IN','GB','ES','UK','TR');    // See also option MAIN_FORCE_STATE_INTO_ADDRESS

    	$contactid=0;
    	$thirdpartyid=0;
    	if ($this->element == 'societe')
    	{
    		$thirdpartyid=$this->id;
    	}
    	if ($this->element == 'contact')
    	{
    		$contactid=$this->id;
			$thirdpartyid=$object->fk_soc;
    	}
        if ($this->element == 'user')
    	{
    		$contactid=$this->contact_id;
			$thirdpartyid=$object->fk_soc;
    	}

		$out='<!-- BEGIN part to show address block -->';

		$outdone=0;
		$coords = $this->getFullAddress(1,', ');
		if ($coords)
		{
			if (! empty($conf->use_javascript_ajax))
			{
				$namecoords = $this->getFullName($langs,1).'<br>'.$coords;
				// hideonsmatphone because copyToClipboard call jquery dialog that does not work with jmobile
				$out.='<a href="#" class="hideonsmartphone" onclick="return copyToClipboard(\''.dol_escape_js($namecoords).'\',\''.dol_escape_js($langs->trans("HelpCopyToClipboard")).'\');">';
				$out.=img_picto($langs->trans("Address"), 'object_address.png');
				$out.='</a> ';
			}
			$out.=dol_print_address($coords, 'address_'.$htmlkey.'_'.$this->id, $this->element, $this->id, 1, ', '); $outdone++;
			$outdone++;
		}

		if (! in_array($this->country_code,$countriesusingstate) && empty($conf->global->MAIN_FORCE_STATE_INTO_ADDRESS)   // If MAIN_FORCE_STATE_INTO_ADDRESS is on, state is already returned previously with getFullAddress
				&& empty($conf->global->SOCIETE_DISABLE_STATE) && $this->state)
		{
			$out.=($outdone?' - ':'').$this->state;
			$outdone++;
		}

		if (! empty($this->phone) || ! empty($this->phone_pro) || ! empty($this->phone_mobile) || ! empty($this->phone_perso) || ! empty($this->fax) || ! empty($this->office_phone) || ! empty($this->user_mobile) || ! empty($this->office_fax)) $out.=($outdone?'<br>':'');
    	if (! empty($this->phone) && empty($this->phone_pro)) {		// For objects that store pro phone into ->phone
			$out.=dol_print_phone($this->phone,$this->country_code,$contactid,$thirdpartyid,'AC_TEL','&nbsp;','phone',$langs->trans("PhonePro")); $outdone++;
		}
		if (! empty($this->phone_pro)) {
			$out.=dol_print_phone($this->phone_pro,$this->country_code,$contactid,$thirdpartyid,'AC_TEL','&nbsp;','phone',$langs->trans("PhonePro")); $outdone++;
		}
		if (! empty($this->phone_mobile)) {
			$out.=dol_print_phone($this->phone_mobile,$this->country_code,$contactid,$thirdpartyid,'AC_TEL','&nbsp;','phone',$langs->trans("PhoneMobile")); $outdone++;
		}
		if (! empty($this->phone_perso)) {
			$out.=dol_print_phone($this->phone_perso,$this->country_code,$contactid,$thirdpartyid,'AC_TEL','&nbsp;','phone',$langs->trans("PhonePerso")); $outdone++;
		}
		if (! empty($this->fax)) {
			$out.=dol_print_phone($this->fax,$this->country_code,$contactid,$thirdpartyid,'AC_FAX','&nbsp;','fax',$langs->trans("Fax")); $outdone++;
		}
    	if (! empty($this->office_phone)) {
			$out.=dol_print_phone($this->office_phone,$this->country_code,$contactid,$thirdpartyid,'AC_TEL','&nbsp;','phone',$langs->trans("PhonePro")); $outdone++;
		}
		if (! empty($this->user_mobile)) {
			$out.=dol_print_phone($this->user_mobile,$this->country_code,$contactid,$thirdpartyid,'AC_TEL','&nbsp;','phone',$langs->trans("PhoneMobile")); $outdone++;
		}
		if (! empty($this->office_fax)) {
			$out.=dol_print_phone($this->fax,$this->country_code,$contactid,$thirdpartyid,'AC_FAX','&nbsp;','fax',$langs->trans("Fax")); $outdone++;
		}

		$out.='<div style="clear: both;"></div>';
		$outdone=0;
		if (! empty($this->email))
		{
			$out.=dol_print_email($this->email,$this->id,$object->id,'AC_EMAIL',0,0,1);
			$outdone++;
		}
    	if (! empty($this->url))
		{
			$out.=dol_print_url($this->url,'_goout',0,1);
			$outdone++;
		}
		if (! empty($conf->skype->enabled))
		{
			$out.='<div style="clear: both;"></div>';
			if ($this->skype) $out.=dol_print_skype($this->skype,$this->id,$object->id,'AC_SKYPE');
			$outdone++;
		}

		$out.='<!-- END Part to show address block -->';

		return $out;
    }

    /**
     *  Add a link between element $this->element and a contact
     *
     *  @param	int		$fk_socpeople       Id of thirdparty contact (if source = 'external') or id of user (if souce = 'internal') to link
     *  @param 	int		$type_contact 		Type of contact (code or id). Must be id or code found into table llx_c_type_contact. For example: SALESREPFOLL
     *  @param  string	$source             external=Contact extern (llx_socpeople), internal=Contact intern (llx_user)
     *  @param  int		$notrigger			Disable all triggers
     *  @return int                 		<0 if KO, >0 if OK
     */
    function add_contact($fk_socpeople, $type_contact, $source='external',$notrigger=0)
    {
        global $user,$langs;


        dol_syslog(get_class($this)."::add_contact $fk_socpeople, $type_contact, $source, $notrigger");

        // Check parameters
        if ($fk_socpeople <= 0)
        {
            $langs->load("errors");
            $this->error=$langs->trans("ErrorWrongValueForParameterX","1");
            dol_syslog(get_class($this)."::add_contact ".$this->error,LOG_ERR);
            return -1;
        }
        if (! $type_contact)
        {
            $langs->load("errors");
            $this->error=$langs->trans("ErrorWrongValueForParameterX","2");
            dol_syslog(get_class($this)."::add_contact ".$this->error,LOG_ERR);
            return -2;
        }

        $id_type_contact=0;
        if (is_numeric($type_contact))
        {
            $id_type_contact=$type_contact;
        }
        else
        {
            // On recherche id type_contact
            $sql = "SELECT tc.rowid";
            $sql.= " FROM ".MAIN_DB_PREFIX."c_type_contact as tc";
            $sql.= " WHERE tc.element='".$this->element."'";
            $sql.= " AND tc.source='".$source."'";
            $sql.= " AND tc.code='".$type_contact."' AND tc.active=1";
			//print $sql;
            $resql=$this->db->query($sql);
            if ($resql)
            {
                $obj = $this->db->fetch_object($resql);
                if ($obj) $id_type_contact=$obj->rowid;
            }
        }

        if ($id_type_contact == 0)
        {
            $this->error='CODE_NOT_VALID_FOR_THIS_ELEMENT';
            dol_syslog("CODE_NOT_VALID_FOR_THIS_ELEMENT");
            return -3;
        }
            
        $datecreate = dol_now();

        $this->db->begin();
        
        // Insertion dans la base
        $sql = "INSERT INTO ".MAIN_DB_PREFIX."element_contact";
        $sql.= " (element_id, fk_socpeople, datecreate, statut, fk_c_type_contact) ";
        $sql.= " VALUES (".$this->id.", ".$fk_socpeople." , " ;
        $sql.= "'".$this->db->idate($datecreate)."'";
        $sql.= ", 4, ". $id_type_contact;
        $sql.= ")";

        $resql=$this->db->query($sql);
        if ($resql)
        {
            if (! $notrigger)
            {
            	$result=$this->call_trigger(strtoupper($this->element).'_ADD_CONTACT', $user);
	            if ($result < 0)
	            {
	                $this->db->rollback();
	                return -1;
	            }
            }

            $this->db->commit();
            return 1;
        }
        else
        {
            if ($this->db->errno() == 'DB_ERROR_RECORD_ALREADY_EXISTS')
            {
                $this->error=$this->db->errno();
            	$this->db->rollback();
                return -2;
            }
            else
            {
                $this->error=$this->db->error();
                $this->db->rollback();
                return -1;
            }
        }
    }

    /**
     *    Copy contact from one element to current
     *
     *    @param    CommonObject    $objFrom    Source element
     *    @param    string          $source     Nature of contact ('internal' or 'external')
     *    @return   int                         >0 if OK, <0 if KO
     */
    function copy_linked_contact($objFrom, $source='internal')
    {
        $contacts = $objFrom->liste_contact(-1, $source);
        foreach($contacts as $contact)
        {
            if ($this->add_contact($contact['id'], $contact['fk_c_type_contact'], $contact['source']) < 0)
            {
                $this->error=$this->db->lasterror();
                return -1;
            }
        }
        return 1;
    }

    /**
     *      Update a link to contact line
     *
     *      @param	int		$rowid              Id of line contact-element
     * 		@param	int		$statut	            New status of link
     *      @param  int		$type_contact_id    Id of contact type (not modified if 0)
     *      @param  int		$fk_socpeople	    Id of soc_people to update (not modified if 0)
     *      @return int                 		<0 if KO, >= 0 if OK
     */
    function update_contact($rowid, $statut, $type_contact_id=0, $fk_socpeople=0)
    {
        // Insertion dans la base
        $sql = "UPDATE ".MAIN_DB_PREFIX."element_contact set";
        $sql.= " statut = ".$statut;
        if ($type_contact_id) $sql.= ", fk_c_type_contact = '".$type_contact_id ."'";
        if ($fk_socpeople) $sql.= ", fk_socpeople = '".$fk_socpeople ."'";
        $sql.= " where rowid = ".$rowid;
        $resql=$this->db->query($sql);
        if ($resql)
        {
            return 0;
        }
        else
        {
            $this->error=$this->db->lasterror();
            return -1;
        }
    }

    /**
     *    Delete a link to contact line
     *
     *    @param	int		$rowid			Id of contact link line to delete
     *    @param	int		$notrigger		Disable all triggers
     *    @return   int						>0 if OK, <0 if KO
     */
    function delete_contact($rowid, $notrigger=0)
    {
        global $user;


        $this->db->begin();

        $sql = "DELETE FROM ".MAIN_DB_PREFIX."element_contact";
        $sql.= " WHERE rowid =".$rowid;

        dol_syslog(get_class($this)."::delete_contact", LOG_DEBUG);
        if ($this->db->query($sql))
        {
            if (! $notrigger)
            {
            	$result=$this->call_trigger(strtoupper($this->element).'_DELETE_CONTACT', $user);
	            if ($result < 0) { $this->db->rollback(); return -1; }
            }

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

    /**
     *    Delete all links between an object $this and all its contacts
     *
     *	  @param	string	$source		'' or 'internal' or 'external'
     *	  @param	string	$code		Type of contact (code or id)
     *    @return   int					>0 if OK, <0 if KO
     */
    function delete_linked_contact($source='',$code='')
    {
        $temp = array();
        $typeContact = $this->liste_type_contact($source,'',0,0,$code);

        foreach($typeContact as $key => $value)
        {
            array_push($temp,$key);
        }
        $listId = implode(",", $temp);

        $sql = "DELETE FROM ".MAIN_DB_PREFIX."element_contact";
        $sql.= " WHERE element_id = ".$this->id;
        if ($listId)
        	$sql.= " AND fk_c_type_contact IN (".$listId.")";

        dol_syslog(get_class($this)."::delete_linked_contact", LOG_DEBUG);
        if ($this->db->query($sql))
        {
            return 1;
        }
        else
		{
            $this->error=$this->db->lasterror();
            return -1;
        }
    }

    /**
     *    Get array of all contacts for an object
     *
     *    @param	int			$statut		Status of links to get (-1=all)
     *    @param	string		$source		Source of contact: external or thirdparty (llx_socpeople) or internal (llx_user)
     *    @param	int         $list       0:Return array contains all properties, 1:Return array contains just id
     *    @param    string      $code       Filter on this code of contact type ('SHIPPING', 'BILLING', ...)
     *    @return	array		            Array of contacts
     */
    function liste_contact($statut=-1,$source='external',$list=0,$code='')
    {
        global $langs;

        $tab=array();

        $sql = "SELECT ec.rowid, ec.statut as statuslink, ec.fk_socpeople as id, ec.fk_c_type_contact";    // This field contains id of llx_socpeople or id of llx_user
        if ($source == 'internal') $sql.=", '-1' as socid, t.statut as statuscontact";
        if ($source == 'external' || $source == 'thirdparty') $sql.=", t.fk_soc as socid, t.statut as statuscontact";
        $sql.= ", t.civility as civility, t.lastname as lastname, t.firstname, t.email";
        $sql.= ", tc.source, tc.element, tc.code, tc.libelle";
        $sql.= " FROM ".MAIN_DB_PREFIX."c_type_contact tc";
        $sql.= ", ".MAIN_DB_PREFIX."element_contact ec";
        if ($source == 'internal') $sql.=" LEFT JOIN ".MAIN_DB_PREFIX."user t on ec.fk_socpeople = t.rowid";
        if ($source == 'external'|| $source == 'thirdparty') $sql.=" LEFT JOIN ".MAIN_DB_PREFIX."socpeople t on ec.fk_socpeople = t.rowid";
        $sql.= " WHERE ec.element_id =".$this->id;
        $sql.= " AND ec.fk_c_type_contact=tc.rowid";
        $sql.= " AND tc.element='".$this->element."'";
        if ($code) $sql.= " AND tc.code = '".$this->db->escape($code)."'";
        if ($source == 'internal') $sql.= " AND tc.source = 'internal'";
        if ($source == 'external' || $source == 'thirdparty') $sql.= " AND tc.source = 'external'";
        $sql.= " AND tc.active=1";
        if ($statut >= 0) $sql.= " AND ec.statut = '".$statut."'";
        $sql.=" ORDER BY t.lastname ASC";

        dol_syslog(get_class($this)."::liste_contact", LOG_DEBUG);
        $resql=$this->db->query($sql);
        if ($resql)
        {
            $num=$this->db->num_rows($resql);
            $i=0;
            while ($i < $num)
            {
                $obj = $this->db->fetch_object($resql);

                if (! $list)
                {
                    $transkey="TypeContact_".$obj->element."_".$obj->source."_".$obj->code;
                    $libelle_type=($langs->trans($transkey)!=$transkey ? $langs->trans($transkey) : $obj->libelle);
                    $tab[$i]=array('source'=>$obj->source,'socid'=>$obj->socid,'id'=>$obj->id,
					               'nom'=>$obj->lastname,      // For backward compatibility
					               'civility'=>$obj->civility, 'lastname'=>$obj->lastname, 'firstname'=>$obj->firstname, 'email'=>$obj->email, 'statuscontact'=>$obj->statuscontact,
					               'rowid'=>$obj->rowid, 'code'=>$obj->code, 'libelle'=>$libelle_type, 'status'=>$obj->statuslink, 'fk_c_type_contact'=>$obj->fk_c_type_contact);
                }
                else
                {
                    $tab[$i]=$obj->id;
                }

                $i++;
            }

            return $tab;
        }
        else
        {
            $this->error=$this->db->lasterror();
            dol_print_error($this->db);
            return -1;
        }
    }


    /**
     * 		Update status of a contact linked to object
     *
     * 		@param	int		$rowid		Id of link between object and contact
     * 		@return	int					<0 if KO, >=0 if OK
     */
    function swapContactStatus($rowid)
    {
        $sql = "SELECT ec.datecreate, ec.statut, ec.fk_socpeople, ec.fk_c_type_contact,";
        $sql.= " tc.code, tc.libelle";
        //$sql.= ", s.fk_soc";
        $sql.= " FROM (".MAIN_DB_PREFIX."element_contact as ec, ".MAIN_DB_PREFIX."c_type_contact as tc)";
        //$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."socpeople as s ON ec.fk_socpeople=s.rowid";	// Si contact de type external, alors il est lie a une societe
        $sql.= " WHERE ec.rowid =".$rowid;
        $sql.= " AND ec.fk_c_type_contact=tc.rowid";
        $sql.= " AND tc.element = '".$this->element."'";

        dol_syslog(get_class($this)."::swapContactStatus", LOG_DEBUG);
        $resql=$this->db->query($sql);
        if ($resql)
        {
            $obj = $this->db->fetch_object($resql);
            $newstatut = ($obj->statut == 4) ? 5 : 4;
            $result = $this->update_contact($rowid, $newstatut);
            $this->db->free($resql);
            return $result;
        }
        else
        {
            $this->error=$this->db->error();
            dol_print_error($this->db);
            return -1;
        }

    }

    /**
     *      Return array with list of possible values for type of contacts
     *
     *      @param	string	$source     'internal', 'external' or 'all'
     *      @param	string	$order		Sort order by : 'position', 'code', 'rowid'...
     *      @param  int		$option     0=Return array id->label, 1=Return array code->label
     *      @param  int		$activeonly 0=all status of contact, 1=only the active
     *		@param	string	$code		Type of contact (Example: 'CUSTOMER', 'SERVICE')
     *      @return array       		Array list of type of contacts (id->label if option=0, code->label if option=1)
     */
    function liste_type_contact($source='internal', $order='position', $option=0, $activeonly=0, $code='')
    {
        global $langs;

        if (empty($order)) $order='position';
        if ($order == 'position') $order.=',code';

        $tab = array();
        $sql = "SELECT DISTINCT tc.rowid, tc.code, tc.libelle, tc.position";
        $sql.= " FROM ".MAIN_DB_PREFIX."c_type_contact as tc";
        $sql.= " WHERE tc.element='".$this->element."'";
        if ($activeonly == 1) $sql.= " AND tc.active=1"; // only the active types
        if (! empty($source) && $source != 'all') $sql.= " AND tc.source='".$source."'";
        if (! empty($code)) $sql.= " AND tc.code='".$code."'";
        $sql.= $this->db->order($order,'ASC');

        //print "sql=".$sql;
        $resql=$this->db->query($sql);
        if ($resql)
        {
            $num=$this->db->num_rows($resql);
            $i=0;
            while ($i < $num)
            {
                $obj = $this->db->fetch_object($resql);

                $transkey="TypeContact_".$this->element."_".$source."_".$obj->code;
                $libelle_type=($langs->trans($transkey)!=$transkey ? $langs->trans($transkey) : $obj->libelle);
                if (empty($option)) $tab[$obj->rowid]=$libelle_type;
                else $tab[$obj->code]=$libelle_type;
                $i++;
            }
            return $tab;
        }
        else
        {
            $this->error=$this->db->lasterror();
            //dol_print_error($this->db);
            return null;
        }
    }

    /**
     *      Return id of contacts for a source and a contact code.
     *      Example: contact client de facturation ('external', 'BILLING')
     *      Example: contact client de livraison ('external', 'SHIPPING')
     *      Example: contact interne suivi paiement ('internal', 'SALESREPFOLL')
     *
     *		@param	string	$source		'external' or 'internal'
     *		@param	string	$code		'BILLING', 'SHIPPING', 'SALESREPFOLL', ...
     *		@param	int		$status		limited to a certain status
     *      @return array       		List of id for such contacts
     */
    function getIdContact($source,$code,$status=0)
    {
        global $conf;

        $result=array();
        $i=0;

        $sql = "SELECT ec.fk_socpeople";
        $sql.= " FROM ".MAIN_DB_PREFIX."element_contact as ec,";
        if ($source == 'internal') $sql.= " ".MAIN_DB_PREFIX."user as c,";
        if ($source == 'external') $sql.= " ".MAIN_DB_PREFIX."socpeople as c,";
        $sql.= " ".MAIN_DB_PREFIX."c_type_contact as tc";
        $sql.= " WHERE ec.element_id = ".$this->id;
        $sql.= " AND ec.fk_socpeople = c.rowid";
        if ($source == 'internal') $sql.= " AND c.entity IN (0,".$conf->entity.")";
        if ($source == 'external') $sql.= " AND c.entity IN (".getEntity('societe', 1).")";
        $sql.= " AND ec.fk_c_type_contact = tc.rowid";
        $sql.= " AND tc.element = '".$this->element."'";
        $sql.= " AND tc.source = '".$source."'";
        $sql.= " AND tc.code = '".$code."'";
        $sql.= " AND tc.active = 1";
        if ($status) $sql.= " AND ec.statut = ".$status;

        dol_syslog(get_class($this)."::getIdContact", LOG_DEBUG);
        $resql=$this->db->query($sql);
        if ($resql)
        {
            while ($obj = $this->db->fetch_object($resql))
            {
                $result[$i]=$obj->fk_socpeople;
                $i++;
            }
        }
        else
        {
            $this->error=$this->db->error();
            return null;
        }

        return $result;
    }

    /**
     *		Load object contact with id=$this->contactid into $this->contact
     *
     *		@param	int		$contactid      Id du contact. Use this->contactid if empty.
     *		@return	int						<0 if KO, >0 if OK
     */
    function fetch_contact($contactid=null)
    {
    	if (empty($contactid)) $contactid=$this->contactid;

    	if (empty($contactid)) return 0;

        require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
        $contact = new Contact($this->db);
        $result=$contact->fetch($contactid);
        $this->contact = $contact;
        return $result;
    }

    /**
     *    	Load the third party of object, from id $this->socid or $this->fk_soc, into this->thirdparty
     *
     *		@param		int		$force_thirdparty_id	Force thirdparty id
     *		@return		int								<0 if KO, >0 if OK
     */
    function fetch_thirdparty($force_thirdparty_id=0)
    {
        global $conf;

        if (empty($this->socid) && empty($this->fk_soc) && empty($this->fk_thirdparty) && empty($force_thirdparty_id))
            return 0;

        require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';

        $idtofetch = isset($this->socid) ? $this->socid : (isset($this->fk_soc) ? $this->fk_soc : $this->fk_thirdparty);
        if ($force_thirdparty_id)
            $idtofetch = $force_thirdparty_id;

        if ($idtofetch) {
            $thirdparty = new Societe($this->db);
            $result = $thirdparty->fetch($idtofetch);
            $this->thirdparty = $thirdparty;

            // Use first price level if level not defined for third party
            if (!empty($conf->global->PRODUIT_MULTIPRICES) && empty($this->thirdparty->price_level)) {
                $this->thirdparty->price_level = 1;
            }

            return $result;
        } else
            return -1;
    }


    /**
     * Looks for an object with ref matching the wildcard provided
     * It does only work when $this->table_ref_field is set
     *
     * @param string $ref Wildcard
     * @return int >1 = OK, 0 = Not found or table_ref_field not defined, <0 = KO
     */
    public function fetchOneLike($ref)
    {
        if (!$this->table_ref_field) {
            return 0;
        }

        $sql = 'SELECT rowid FROM '.MAIN_DB_PREFIX.$this->table_element.' WHERE '.$this->table_ref_field.' LIKE "'.$this->db->escape($ref).'" LIMIT 1';

        $query = $this->db->query($sql);

        if (!$this->db->num_rows($query)) {
            return 0;
        }

        $result = $this->db->fetch_object($query);

        return $this->fetch($result->rowid);
    }

    /**
     *	Load data for barcode into properties ->barcode_type*
     *	Properties ->barcode_type that is id of barcode. Type is used to find other properties, but
     *  if it is not defined, ->element must be defined to know default barcode type.
     *
     *	@return		int			<0 if KO, 0 if can't guess type of barcode (ISBN, EAN13...), >0 if OK (all barcode properties loaded)
     */
    function fetch_barcode()
    {
        global $conf;

        dol_syslog(get_class($this).'::fetch_barcode this->element='.$this->element.' this->barcode_type='.$this->barcode_type);

        $idtype=$this->barcode_type;
        if (empty($idtype) && $idtype != '0')	// If type of barcode no set, we try to guess. If set to '0' it means we forced to have type remain not defined
        {
            if ($this->element == 'product')      $idtype = $conf->global->PRODUIT_DEFAULT_BARCODE_TYPE;
            else if ($this->element == 'societe') $idtype = $conf->global->GENBARCODE_BARCODETYPE_THIRDPARTY;
            else dol_syslog('Call fetch_barcode with barcode_type not defined and cant be guessed', LOG_WARNING);
        }

        if ($idtype > 0)
        {
            if (empty($this->barcode_type) || empty($this->barcode_type_code) || empty($this->barcode_type_label) || empty($this->barcode_type_coder))    // If data not already loaded
            {
                $sql = "SELECT rowid, code, libelle as label, coder";
                $sql.= " FROM ".MAIN_DB_PREFIX."c_barcode_type";
                $sql.= " WHERE rowid = ".$idtype;
                dol_syslog(get_class($this).'::fetch_barcode', LOG_DEBUG);
                $resql = $this->db->query($sql);
            	if ($resql)
                {
                    $obj = $this->db->fetch_object($resql);
                    $this->barcode_type       = $obj->rowid;
                    $this->barcode_type_code  = $obj->code;
                    $this->barcode_type_label = $obj->label;
                    $this->barcode_type_coder = $obj->coder;
                    return 1;
                }
                else
                {
                    dol_print_error($this->db);
                    return -1;
                }
            }
        }
        return 0;
    }

    /**
     *		Charge le projet d'id $this->fk_project dans this->projet
     *
     *		@return		int			<0 if KO, >=0 if OK
     */
    function fetch_projet()
    {
    	include_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';

    	if (empty($this->fk_project) && ! empty($this->fk_projet)) $this->fk_project = $this->fk_projet;	// For backward compatibility
        if (empty($this->fk_project)) return 0;

        $project = new Project($this->db);
        $result = $project->fetch($this->fk_project);

        $this->projet = $project;	// deprecated
        $this->project = $project;
        return $result;
    }

    /**
     *		Charge le user d'id userid dans this->user
     *
     *		@param	int		$userid 		Id du contact
     *		@return	int						<0 if KO, >0 if OK
     */
    function fetch_user($userid)
    {
        $user = new User($this->db);
        $result=$user->fetch($userid);
        $this->user = $user;
        return $result;
    }

    /**
     *	Read linked origin object
     *
     *	@return		void
     */
    function fetch_origin()
    {
        if ($this->origin == 'shipping') $this->origin = 'expedition';
        if ($this->origin == 'delivery') $this->origin = 'livraison';

        $origin = $this->origin;

        $classname = ucfirst($origin);
        $this->$origin = new $classname($this->db);
        $this->$origin->fetch($this->origin_id);
    }

    /**
     *    	Load object from specific field
     *
     *    	@param	string	$table		Table element or element line
     *    	@param	string	$field		Field selected
     *    	@param	string	$key		Import key
     *		@return	int					<0 if KO, >0 if OK
     */
    function fetchObjectFrom($table,$field,$key)
    {
        global $conf;

        $result=false;

        $sql = "SELECT rowid FROM ".MAIN_DB_PREFIX.$table;
        $sql.= " WHERE ".$field." = '".$key."'";
        $sql.= " AND entity = ".$conf->entity;

        dol_syslog(get_class($this).'::fetchObjectFrom', LOG_DEBUG);
        $resql = $this->db->query($sql);
        if ($resql)
        {
            $row = $this->db->fetch_row($resql);
            $result = $this->fetch($row[0]);
        }

        return $result;
    }

    /**
     *	Getter generic. Load value from a specific field
     *
     *	@param	string	$table		Table of element or element line
     *	@param	int		$id			Element id
     *	@param	string	$field		Field selected
     *	@return	int					<0 if KO, >0 if OK
     */
    function getValueFrom($table, $id, $field)
    {
        $result=false;
		if (!empty($id) && !empty($field) && !empty($table)) {
	        $sql = "SELECT ".$field." FROM ".MAIN_DB_PREFIX.$table;
	        $sql.= " WHERE rowid = ".$id;

	        dol_syslog(get_class($this).'::getValueFrom', LOG_DEBUG);
	        $resql = $this->db->query($sql);
	        if ($resql)
	        {
	            $row = $this->db->fetch_row($resql);
	            $result = $row[0];
	        }
		}
        return $result;
    }

    /**
     *	Setter generic. Update a specific field into database.
     *  Warning: Trigger is run only if param trigkey is provided.
     *
     *	@param	string		$field		Field to update
     *	@param	mixed		$value		New value
     *	@param	string		$table		To force other table element or element line (should not be used)
     *	@param	int			$id			To force other object id (should not be used)
     *	@param	string		$format		Data format ('text', 'date'). 'text' is used if not defined
     *	@param	string		$id_field	To force rowid field name. 'rowid' is used if not defined
     *	@param	User|string	$fuser		Update the user of last update field with this user. If not provided, current user is used except if value is 'none'
     *  @param  string      $trigkey    Trigger key to run (in most cases something like 'XXX_MODIFY')
     *	@return	int						<0 if KO, >0 if OK
     */
    function setValueFrom($field, $value, $table='', $id=null, $format='', $id_field='', $fuser=null, $trigkey='')
    {
        global $user,$langs,$conf;
        
        if (empty($table)) 	  $table=$this->table_element;
        if (empty($id))    	  $id=$this->id;
		if (empty($format))   $format='text';
		if (empty($id_field)) $id_field='rowid';

		$error=0;
		
        $this->db->begin();

        // Special case
        if ($table == 'product' && $field == 'note_private') $field='note';
        
        $sql = "UPDATE ".MAIN_DB_PREFIX.$table." SET ";
        if ($format == 'text') $sql.= $field." = '".$this->db->escape($value)."'";
        else if ($format == 'int') $sql.= $field." = ".$this->db->escape($value);
        else if ($format == 'date') $sql.= $field." = ".($value ? "'".$this->db->idate($value)."'" : "null");
        if (! empty($fuser) && is_object($fuser)) $sql.=", fk_user_modif = ".$fuser->id;
        elseif (empty($fuser) || $fuser != 'none') $sql.=", fk_user_modif = ".$user->id;
        $sql.= " WHERE ".$id_field." = ".$id;

        dol_syslog(get_class($this)."::".__FUNCTION__."", LOG_DEBUG);
        $resql = $this->db->query($sql);
        if ($resql)
        {
            if ($trigkey)
            {
                $result=$this->call_trigger($trigkey, (! empty($fuser) && is_object($fuser)) ? $fuser : $user);   // This may set this->errors
                if ($result < 0) $error++;
            }

            if (! $error)
            {
                if (property_exists($this, $field)) $this->$field = $value;
                $this->db->commit();
                return 1;
            }
            else
            {
                $this->db->rollback();
                return -2;
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
     *      Load properties id_previous and id_next
     *
     *      @param	string	$filter		Optional filter
     *	 	@param  int		$fieldid   	Name of field to use for the select MAX and MIN
     *		@param	int		$nodbprefix	Do not include DB prefix to forge table name
     *      @return int         		<0 if KO, >0 if OK
     */
    function load_previous_next_ref($filter, $fieldid, $nodbprefix=0)
    {
        global $user;

        if (! $this->table_element)
        {
            dol_print_error('',get_class($this)."::load_previous_next_ref was called on objet with property table_element not defined");
            return -1;
        }

        // this->ismultientitymanaged contains
        // 0=No test on entity, 1=Test with field entity, 2=Test with link by societe
        $alias = 's';
        if ($this->element == 'societe') $alias = 'te';

        $sql = "SELECT MAX(te.".$fieldid.")";
        $sql.= " FROM ".(empty($nodbprefix)?MAIN_DB_PREFIX:'').$this->table_element." as te";
        if (isset($this->ismultientitymanaged) && $this->ismultientitymanaged == 2 || ($this->element != 'societe' && empty($this->isnolinkedbythird) && empty($user->rights->societe->client->voir))) $sql.= ", ".MAIN_DB_PREFIX."societe as s";	// If we need to link to societe to limit select to entity
        if (empty($this->isnolinkedbythird) && !$user->rights->societe->client->voir) $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe_commerciaux as sc ON ".$alias.".rowid = sc.fk_soc";
        $sql.= " WHERE te.".$fieldid." < '".$this->db->escape($this->ref)."'";  // ->ref must always be defined (set to id if field does not exists)
        if (empty($this->isnolinkedbythird) && !$user->rights->societe->client->voir) $sql.= " AND sc.fk_user = " .$user->id;
        if (! empty($filter)) $sql.=" AND ".$filter;
        if (isset($this->ismultientitymanaged) && $this->ismultientitymanaged == 2 || ($this->element != 'societe' && empty($this->isnolinkedbythird) && !$user->rights->societe->client->voir)) $sql.= ' AND te.fk_soc = s.rowid';			// If we need to link to societe to limit select to entity
        if (isset($this->ismultientitymanaged) && $this->ismultientitymanaged == 1) $sql.= ' AND te.entity IN ('.getEntity($this->element, 1).')';

        //print $sql."<br>";
        $result = $this->db->query($sql);
        if (! $result)
        {
            $this->error=$this->db->lasterror();
            return -1;
        }
        $row = $this->db->fetch_row($result);
        $this->ref_previous = $row[0];


        $sql = "SELECT MIN(te.".$fieldid.")";
        $sql.= " FROM ".(empty($nodbprefix)?MAIN_DB_PREFIX:'').$this->table_element." as te";
        if (isset($this->ismultientitymanaged) && $this->ismultientitymanaged == 2 || ($this->element != 'societe' && empty($this->isnolinkedbythird) && !$user->rights->societe->client->voir)) $sql.= ", ".MAIN_DB_PREFIX."societe as s";	// If we need to link to societe to limit select to entity
        if (empty($this->isnolinkedbythird) && !$user->rights->societe->client->voir) $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe_commerciaux as sc ON ".$alias.".rowid = sc.fk_soc";
        $sql.= " WHERE te.".$fieldid." > '".$this->db->escape($this->ref)."'";  // ->ref must always be defined (set to id if field does not exists)
        if (empty($this->isnolinkedbythird) && !$user->rights->societe->client->voir) $sql.= " AND sc.fk_user = " .$user->id;
        if (! empty($filter)) $sql.=" AND ".$filter;
        if (isset($this->ismultientitymanaged) && $this->ismultientitymanaged == 2 || ($this->element != 'societe' && empty($this->isnolinkedbythird) && !$user->rights->societe->client->voir)) $sql.= ' AND te.fk_soc = s.rowid';			// If we need to link to societe to limit select to entity
        if (isset($this->ismultientitymanaged) && $this->ismultientitymanaged == 1) $sql.= ' AND te.entity IN ('.getEntity($this->element, 1).')';
        // Rem: Bug in some mysql version: SELECT MIN(rowid) FROM llx_socpeople WHERE rowid > 1 when one row in database with rowid=1, returns 1 instead of null

        //print $sql."<br>";
        $result = $this->db->query($sql);
        if (! $result)
        {
            $this->error=$this->db->lasterror();
            return -2;
        }
        $row = $this->db->fetch_row($result);
        $this->ref_next = $row[0];

        return 1;
    }


    /**
     *      Return list of id of contacts of project
     *
     *      @param	string	$source     Source of contact: external (llx_socpeople) or internal (llx_user) or thirdparty (llx_societe)
     *      @return array				Array of id of contacts (if source=external or internal)
     * 									Array of id of third parties with at least one contact on project (if source=thirdparty)
     */
    function getListContactId($source='external')
    {
        $contactAlreadySelected = array();
        $tab = $this->liste_contact(-1,$source);
        $num=count($tab);
        $i = 0;
        while ($i < $num)
        {
            if ($source == 'thirdparty') $contactAlreadySelected[$i] = $tab[$i]['socid'];
            else  $contactAlreadySelected[$i] = $tab[$i]['id'];
            $i++;
        }
        return $contactAlreadySelected;
    }


    /**
     *	Link element with a project
     *
     *	@param     	int		$projectid		Project id to link element to
     *	@return		int						<0 if KO, >0 if OK
     */
    function setProject($projectid)
    {
        if (! $this->table_element)
        {
            dol_syslog(get_class($this)."::setProject was called on objet with property table_element not defined",LOG_ERR);
            return -1;
        }

        $sql = 'UPDATE '.MAIN_DB_PREFIX.$this->table_element;
        if ($projectid) $sql.= ' SET fk_projet = '.$projectid;
        else $sql.= ' SET fk_projet = NULL';
        $sql.= ' WHERE rowid = '.$this->id;

        dol_syslog(get_class($this)."::setProject", LOG_DEBUG);
        if ($this->db->query($sql))
        {
            $this->fk_project = $projectid;
            return 1;
        }
        else
        {
            dol_print_error($this->db);
            return -1;
        }
    }

    /**
     *  Change the payments methods
     *
     *  @param		int		$id		Id of new payment method
     *  @return		int				>0 if OK, <0 if KO
     */
    function setPaymentMethods($id)
    {
    	dol_syslog(get_class($this).'::setPaymentMethods('.$id.')');
    	if ($this->statut >= 0 || $this->element == 'societe')
    	{
    		// TODO uniformize field name
    		$fieldname = 'fk_mode_reglement';
    		if ($this->element == 'societe') $fieldname = 'mode_reglement';
    		if (get_class($this) == 'Fournisseur') $fieldname = 'mode_reglement_supplier';

    		$sql = 'UPDATE '.MAIN_DB_PREFIX.$this->table_element;
    		$sql .= ' SET '.$fieldname.' = '.$id;
    		$sql .= ' WHERE rowid='.$this->id;

    		if ($this->db->query($sql))
    		{
    			$this->mode_reglement_id = $id;
    			// for supplier
    			if (get_class($this) == 'Fournisseur') $this->mode_reglement_supplier_id = $id;
    			return 1;
    		}
    		else
    		{
    			dol_syslog(get_class($this).'::setPaymentMethods Erreur '.$sql.' - '.$this->db->error());
    			$this->error=$this->db->error();
    			return -1;
    		}
    	}
    	else
    	{
    		dol_syslog(get_class($this).'::setPaymentMethods, status of the object is incompatible');
    		$this->error='Status of the object is incompatible '.$this->statut;
    		return -2;
    	}
    }

	/**
     *  Change the multicurrency code
     *
     *  @param		string	$code	multicurrency code
     *  @return		int				>0 if OK, <0 if KO
     */
    function setMulticurrencyCode($code)
    {
    	dol_syslog(get_class($this).'::setMulticurrencyCode('.$id.')');
    	if ($this->statut >= 0 || $this->element == 'societe')
    	{
    		$fieldname = 'multicurrency_code';

    		$sql = 'UPDATE '.MAIN_DB_PREFIX.$this->table_element;
    		$sql .= ' SET '.$fieldname.' = "'.$this->db->escape($code).'"';
    		$sql .= ' WHERE rowid='.$this->id;

    		if ($this->db->query($sql))
    		{
    			$this->multicurrency_code = $code;

				list($fk_multicurrency, $rate) = MultiCurrency::getIdAndTxFromCode($this->db, $code);
				if ($rate) $this->setMulticurrencyRate($rate);

    			return 1;
    		}
    		else
    		{
    			dol_syslog(get_class($this).'::setMulticurrencyCode Erreur '.$sql.' - '.$this->db->error());
    			$this->error=$this->db->error();
    			return -1;
    		}
    	}
    	else
    	{
    		dol_syslog(get_class($this).'::setMulticurrencyCode, status of the object is incompatible');
    		$this->error='Status of the object is incompatible '.$this->statut;
    		return -2;
    	}
    }

	/**
     *  Change the multicurrency rate
     *
     *  @param		double	$rate	multicurrency rate
	 *  @param		int		$mode	mode 1 : amounts in company currency will be recalculated, mode 2 : amounts in foreign currency
     *  @return		int				>0 if OK, <0 if KO
     */
    function setMulticurrencyRate($rate, $mode=1)
    {
    	dol_syslog(get_class($this).'::setMulticurrencyRate('.$id.')');
    	if ($this->statut >= 0 || $this->element == 'societe')
    	{
    		$fieldname = 'multicurrency_tx';

    		$sql = 'UPDATE '.MAIN_DB_PREFIX.$this->table_element;
    		$sql .= ' SET '.$fieldname.' = '.$rate;
    		$sql .= ' WHERE rowid='.$this->id;

    		if ($this->db->query($sql))
    		{
    			$this->multicurrency_tx = $rate;

				// Update line price
				if (!empty($this->lines))
				{
					foreach ($this->lines as &$line)
					{
						if($mode == 1) {
							$line->subprice = 0;
						}
						
						switch ($this->element) {
							case 'propal':
								$this->updateline($line->id, $line->subprice, $line->qty, $line->remise_percent, $line->tva_tx, $line->localtax1_tx, $line->localtax2_tx, $line->desc, 'HT', $line->info_bits, $line->special_code, $line->fk_parent_line, $line->skip_update_total, $line->fk_fournprice, $line->pa_ht, $line->label, $line->product_type, $line->date_start, $line->date_end, $line->array_options, $line->fk_unit, $line->multicurrency_subprice);
								break;
							case 'commande':
								$this->updateline($line->id, $line->desc, $line->subprice, $line->qty, $line->remise_percent, $line->tva_tx, $line->localtax1_tx, $line->localtax2_tx, 'HT', $line->info_bits, $line->date_start, $line->date_end, $line->product_type, $line->fk_parent_line, $line->skip_update_total, $line->fk_fournprice, $line->pa_ht, $line->label, $line->special_code, $line->array_options, $line->fk_unit, $line->multicurrency_subprice);
								break;
							case 'facture':
								$this->updateline($line->id, $line->desc, $line->subprice, $line->qty, $line->remise_percent, $line->date_start, $line->date_end, $line->tva_tx, $line->localtax1_tx, $line->localtax2_tx, 'HT', $line->info_bits, $line->product_type, $line->fk_parent_line, $line->skip_update_total, $line->fk_fournprice, $line->pa_ht, $line->label, $line->special_code, $line->array_options, $line->situation_percent, $line->fk_unit, $line->multicurrency_subprice);
								break;
							case 'supplier_proposal':
								$this->updateline($line->id, $line->subprice, $line->qty, $line->remise_percent, $line->tva_tx, $line->localtax1_tx, $line->localtax2_tx, $line->desc, 'HT', $line->info_bits, $line->special_code, $line->fk_parent_line, $line->skip_update_total, $line->fk_fournprice, $line->pa_ht, $line->label, $line->product_type, $line->array_options, $line->ref_fourn, $line->multicurrency_subprice);
								break;
							case 'order_supplier':
								$this->updateline($line->id, $line->desc, $line->subprice, $line->qty, $line->remise_percent, $line->tva_tx, $line->localtax1_tx, $line->localtax2_tx, 'HT', $line->info_bits,  $line->product_type, false, $line->date_start, $line->date_end, $line->array_options, $line->fk_unit, $line->multicurrency_subprice);
								break;
							case 'invoice_supplier':
								$this->updateline($line->id, $line->desc, $line->subprice, $line->tva_tx, $line->localtax1_tx, $line->localtax2_tx, $line->qty, 0, 'HT', $line->info_bits, $line->product_type, $line->remise_percent, false, $line->date_start, $line->date_end, $line->array_options, $line->fk_unit, $line->multicurrency_subprice);
								break;
							default:
								dol_syslog(get_class($this).'::setMulticurrencyRate no updateline defined', LOG_DEBUG);
								break;
						}

					}
				}

    			return 1;
    		}
    		else
    		{
    			dol_syslog(get_class($this).'::setMulticurrencyRate Erreur '.$sql.' - '.$this->db->error());
    			$this->error=$this->db->error();
    			return -1;
    		}
    	}
    	else
    	{
    		dol_syslog(get_class($this).'::setMulticurrencyRate, status of the object is incompatible');
    		$this->error='Status of the object is incompatible '.$this->statut;
    		return -2;
    	}
    }

    /**
     *  Change the payments terms
     *
     *  @param		int		$id		Id of new payment terms
     *  @return		int				>0 if OK, <0 if KO
     */
    function setPaymentTerms($id)
    {
    	dol_syslog(get_class($this).'::setPaymentTerms('.$id.')');
    	if ($this->statut >= 0 || $this->element == 'societe')
    	{
    		// TODO uniformize field name
    		$fieldname = 'fk_cond_reglement';
    		if ($this->element == 'societe') $fieldname = 'cond_reglement';
    		if (get_class($this) == 'Fournisseur') $fieldname = 'cond_reglement_supplier';

    		$sql = 'UPDATE '.MAIN_DB_PREFIX.$this->table_element;
    		$sql .= ' SET '.$fieldname.' = '.$id;
    		$sql .= ' WHERE rowid='.$this->id;

    		if ($this->db->query($sql))
    		{
    			$this->cond_reglement_id = $id;
    			// for supplier
    			if (get_class($this) == 'Fournisseur') $this->cond_reglement_supplier_id = $id;
    			$this->cond_reglement = $id;	// for compatibility
    			return 1;
    		}
    		else
    		{
    			dol_syslog(get_class($this).'::setPaymentTerms Erreur '.$sql.' - '.$this->db->error());
    			$this->error=$this->db->error();
    			return -1;
    		}
    	}
    	else
    	{
    		dol_syslog(get_class($this).'::setPaymentTerms, status of the object is incompatible');
    		$this->error='Status of the object is incompatible '.$this->statut;
    		return -2;
    	}
    }

    /**
     *	Define delivery address
     *
     *	@param      int		$id		Address id
     *	@return     int				<0 si ko, >0 si ok
     */
    function setDeliveryAddress($id)
    {
    	$fieldname = 'fk_delivery_address';
    	if ($this->element == 'delivery' || $this->element == 'shipping') $fieldname = 'fk_address';

    	$sql = "UPDATE ".MAIN_DB_PREFIX.$this->table_element." SET ".$fieldname." = ".$id;
    	$sql.= " WHERE rowid = ".$this->id." AND fk_statut = 0";

    	if ($this->db->query($sql))
    	{
    		$this->fk_delivery_address = $id;
    		return 1;
    	}
    	else
    	{
    		$this->error=$this->db->error();
    		dol_syslog(get_class($this).'::setDeliveryAddress Erreur '.$sql.' - '.$this->error);
    		return -1;
    	}
    }


    /**
     *  Change the shipping method
     *
     *  @param      int     $shipping_method_id     Id of shipping method
     *  @return     int              1 if OK, 0 if KO
     */
    function setShippingMethod($shipping_method_id)
    {
        if (! $this->table_element) {
            dol_syslog(get_class($this)."::setShippingMethod was called on objet with property table_element not defined",LOG_ERR);
            return -1;
        }
        if ($shipping_method_id<0) $shipping_method_id='NULL';
        dol_syslog(get_class($this).'::setShippingMethod('.$shipping_method_id.')');

        $sql = "UPDATE ".MAIN_DB_PREFIX.$this->table_element;
        $sql.= " SET fk_shipping_method = ".$shipping_method_id;
        $sql.= " WHERE rowid=".$this->id;

        if ($this->db->query($sql)) {
            $this->shipping_method_id = ($shipping_method_id=='NULL')?null:$shipping_method_id;
            return 1;
        } else {
            dol_syslog(get_class($this).'::setShippingMethod Error ', LOG_DEBUG);
            $this->error=$this->db->error();
            return 0;
        }
    }


    /**
     *  Change the warehouse
     *
     *  @param      int     $warehouse_id     Id of warehouse
     *  @return     int              1 if OK, 0 if KO
     */
    function setWarehouse($warehouse_id)
    {
        if (! $this->table_element) {
            dol_syslog(get_class($this)."::setWarehouse was called on objet with property table_element not defined",LOG_ERR);
            return -1;
        }
        if ($warehouse_id<0) $warehouse_id='NULL';
        dol_syslog(get_class($this).'::setWarehouse('.$warehouse_id.')');

        $sql = "UPDATE ".MAIN_DB_PREFIX.$this->table_element;
        $sql.= " SET fk_warehouse = ".$warehouse_id;
        $sql.= " WHERE rowid=".$this->id;

        if ($this->db->query($sql)) {
            $this->warehouse_id = ($warehouse_id=='NULL')?null:$warehouse_id;
            return 1;
        } else {
            dol_syslog(get_class($this).'::setWarehouse Error ', LOG_DEBUG);
            $this->error=$this->db->error();
            return 0;
        }
    }


    /**
     *		Set last model used by doc generator
     *
     *		@param		User	$user		User object that make change
     *		@param		string	$modelpdf	Modele name
     *		@return		int					<0 if KO, >0 if OK
     */
    function setDocModel($user, $modelpdf)
    {
        if (! $this->table_element)
        {
            dol_syslog(get_class($this)."::setDocModel was called on objet with property table_element not defined",LOG_ERR);
            return -1;
        }

        $newmodelpdf=dol_trunc($modelpdf,255);

        $sql = "UPDATE ".MAIN_DB_PREFIX.$this->table_element;
        $sql.= " SET model_pdf = '".$this->db->escape($newmodelpdf)."'";
        $sql.= " WHERE rowid = ".$this->id;
        // if ($this->element == 'facture') $sql.= " AND fk_statut < 2";
        // if ($this->element == 'propal')  $sql.= " AND fk_statut = 0";

        dol_syslog(get_class($this)."::setDocModel", LOG_DEBUG);
        $resql=$this->db->query($sql);
        if ($resql)
        {
            $this->modelpdf=$modelpdf;
            return 1;
        }
        else
        {
            dol_print_error($this->db);
            return 0;
        }
    }


    /**
     *  Change the bank account
     *
     *  @param		int		$fk_account		Id of bank account
     *  @return		int				1 if OK, 0 if KO
     */
    function setBankAccount($fk_account)
    {
        if (! $this->table_element) {
            dol_syslog(get_class($this)."::setBankAccount was called on objet with property table_element not defined",LOG_ERR);
            return -1;
        }
        if ($fk_account<0) $fk_account='NULL';
        dol_syslog(get_class($this).'::setBankAccount('.$fk_account.')');

        $sql = "UPDATE ".MAIN_DB_PREFIX.$this->table_element;
        $sql.= " SET fk_account = ".$fk_account;
        $sql.= " WHERE rowid=".$this->id;

        if ($this->db->query($sql)) {
            $this->fk_account = ($fk_account=='NULL')?null:$fk_account;
            return 1;
        } else {
            dol_syslog(get_class($this).'::setBankAccount Error '.$sql.' - '.$this->db->error());
            $this->error=$this->db->error();
            return 0;
        }
    }

	// TODO: Move line related operations to CommonObjectLine?

    /**
     *  Save a new position (field rang) for details lines.
     *  You can choose to set position for lines with already a position or lines without any position defined.
     *
     * 	@param		boolean		$renum			   True to renum all already ordered lines, false to renum only not already ordered lines.
     * 	@param		string		$rowidorder		   ASC or DESC
     * 	@param		boolean		$fk_parent_line    Table with fk_parent_line field or not
     * 	@return		int                            <0 if KO, >0 if OK
     */
    function line_order($renum=false, $rowidorder='ASC', $fk_parent_line=true)
    {
        if (! $this->table_element_line)
        {
            dol_syslog(get_class($this)."::line_order was called on objet with property table_element_line not defined",LOG_ERR);
            return -1;
        }
        if (! $this->fk_element)
        {
            dol_syslog(get_class($this)."::line_order was called on objet with property fk_element not defined",LOG_ERR);
            return -1;
        }

        // Count number of lines to reorder (according to choice $renum)
    	$nl=0;
        $sql = 'SELECT count(rowid) FROM '.MAIN_DB_PREFIX.$this->table_element_line;
		$sql.= ' WHERE '.$this->fk_element.'='.$this->id;
		if (! $renum) $sql.= ' AND rang = 0';
		if ($renum) $sql.= ' AND rang <> 0';

		dol_syslog(get_class($this)."::line_order", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql)
		{
			$row = $this->db->fetch_row($resql);
			$nl = $row[0];
		}
		else dol_print_error($this->db);
		if ($nl > 0)
		{
			// The goal of this part is to reorder all lines, with all children lines sharing the same
			// counter that parents.
			$rows=array();

			// We first search all lines that are parent lines (for multilevel details lines)
			$sql = 'SELECT rowid FROM '.MAIN_DB_PREFIX.$this->table_element_line;
			$sql.= ' WHERE '.$this->fk_element.' = '.$this->id;
			if ($fk_parent_line) $sql.= ' AND fk_parent_line IS NULL';
			$sql.= ' ORDER BY rang ASC, rowid '.$rowidorder;

			dol_syslog(get_class($this)."::line_order search all parent lines", LOG_DEBUG);
			$resql = $this->db->query($sql);
			if ($resql)
			{
				$i=0;
				$num = $this->db->num_rows($resql);
				while ($i < $num)
				{
					$row = $this->db->fetch_row($resql);
					$rows[] = $row[0];	// Add parent line into array rows
					$childrens = $this->getChildrenOfLine($row[0]);
					if (! empty($childrens))
					{
						foreach($childrens as $child)
						{
							array_push($rows, $child);
						}
					}
					$i++;
				}

				// Now we set a new number for each lines (parent and children with children included into parent tree)
				if (! empty($rows))
				{
					foreach($rows as $key => $row)
					{
						$this->updateRangOfLine($row, ($key+1));
					}
				}
			}
			else
			{
				dol_print_error($this->db);
			}
		}
		return 1;
	}

	/**
	 * 	Get children of line
	 *
	 * 	@param	int		$id		Id of parent line
	 * 	@return	array			Array with list of children lines id
	 */
	function getChildrenOfLine($id)
	{
		$rows=array();

		$sql = 'SELECT rowid FROM '.MAIN_DB_PREFIX.$this->table_element_line;
		$sql.= ' WHERE '.$this->fk_element.' = '.$this->id;
		$sql.= ' AND fk_parent_line = '.$id;
		$sql.= ' ORDER BY rang ASC';

		dol_syslog(get_class($this)."::getChildrenOfLine search children lines for line ".$id."", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql)
		{
			$i=0;
			$num = $this->db->num_rows($resql);
			while ($i < $num)
			{
				$row = $this->db->fetch_row($resql);
				$rows[$i] = $row[0];
				$i++;
			}
		}

		return $rows;
	}

    /**
     * 	Update a line to have a lower rank
     *
     * 	@param 	int			$rowid				Id of line
     * 	@param	boolean		$fk_parent_line		Table with fk_parent_line field or not
     * 	@return	void
     */
    function line_up($rowid, $fk_parent_line=true)
    {
        $this->line_order(false, 'ASC', $fk_parent_line);

        // Get rang of line
        $rang = $this->getRangOfLine($rowid);

        // Update position of line
        $this->updateLineUp($rowid, $rang);
    }

    /**
     * 	Update a line to have a higher rank
     *
     * 	@param	int			$rowid				Id of line
     * 	@param	boolean		$fk_parent_line		Table with fk_parent_line field or not
     * 	@return	void
     */
    function line_down($rowid, $fk_parent_line=true)
    {
        $this->line_order(false, 'ASC', $fk_parent_line);

        // Get rang of line
        $rang = $this->getRangOfLine($rowid);

        // Get max value for rang
        $max = $this->line_max();

        // Update position of line
        $this->updateLineDown($rowid, $rang, $max);
    }

	/**
	 * 	Update position of line (rang)
	 *
	 * 	@param	int		$rowid		Id of line
	 * 	@param	int		$rang		Position
	 * 	@return	void
	 */
	function updateRangOfLine($rowid,$rang)
	{
		$sql = 'UPDATE '.MAIN_DB_PREFIX.$this->table_element_line.' SET rang  = '.$rang;
		$sql.= ' WHERE rowid = '.$rowid;

		dol_syslog(get_class($this)."::updateRangOfLine", LOG_DEBUG);
		if (! $this->db->query($sql))
		{
			dol_print_error($this->db);
		}
	}

    /**
     * 	Update position of line with ajax (rang)
     *
     * 	@param	array	$rows	Array of rows
     * 	@return	void
     */
    function line_ajaxorder($rows)
    {
        $num = count($rows);
        for ($i = 0 ; $i < $num ; $i++)
        {
            $this->updateRangOfLine($rows[$i], ($i+1));
        }
    }

    /**
     * 	Update position of line up (rang)
     *
     * 	@param	int		$rowid		Id of line
     * 	@param	int		$rang		Position
     * 	@return	void
     */
    function updateLineUp($rowid,$rang)
    {
        if ($rang > 1 )
        {
            $sql = 'UPDATE '.MAIN_DB_PREFIX.$this->table_element_line.' SET rang = '.$rang ;
            $sql.= ' WHERE '.$this->fk_element.' = '.$this->id;
            $sql.= ' AND rang = '.($rang - 1);
            if ($this->db->query($sql) )
            {
                $sql = 'UPDATE '.MAIN_DB_PREFIX.$this->table_element_line.' SET rang  = '.($rang - 1);
                $sql.= ' WHERE rowid = '.$rowid;
                if (! $this->db->query($sql) )
                {
                    dol_print_error($this->db);
                }
            }
            else
            {
                dol_print_error($this->db);
            }
        }
    }

    /**
     * 	Update position of line down (rang)
     *
     * 	@param	int		$rowid		Id of line
     * 	@param	int		$rang		Position
     * 	@param	int		$max		Max
     * 	@return	void
     */
    function updateLineDown($rowid,$rang,$max)
    {
        if ($rang < $max)
        {
            $sql = 'UPDATE '.MAIN_DB_PREFIX.$this->table_element_line.' SET rang = '.$rang;
            $sql.= ' WHERE '.$this->fk_element.' = '.$this->id;
            $sql.= ' AND rang = '.($rang+1);
            if ($this->db->query($sql) )
            {
                $sql = 'UPDATE '.MAIN_DB_PREFIX.$this->table_element_line.' SET rang = '.($rang+1);
                $sql.= ' WHERE rowid = '.$rowid;
                if (! $this->db->query($sql) )
                {
                    dol_print_error($this->db);
                }
            }
            else
            {
                dol_print_error($this->db);
            }
        }
    }

    /**
     * 	Get position of line (rang)
     *
     * 	@param		int		$rowid		Id of line
     *  @return		int     			Value of rang in table of lines
     */
    function getRangOfLine($rowid)
    {
        $sql = 'SELECT rang FROM '.MAIN_DB_PREFIX.$this->table_element_line;
        $sql.= ' WHERE rowid ='.$rowid;

        dol_syslog(get_class($this)."::getRangOfLine", LOG_DEBUG);
        $resql = $this->db->query($sql);
        if ($resql)
        {
            $row = $this->db->fetch_row($resql);
            return $row[0];
        }
    }

    /**
     * 	Get rowid of the line relative to its position
     *
     * 	@param		int		$rang		Rang value
     *  @return     int     			Rowid of the line
     */
    function getIdOfLine($rang)
    {
        $sql = 'SELECT rowid FROM '.MAIN_DB_PREFIX.$this->table_element_line;
        $sql.= ' WHERE '.$this->fk_element.' = '.$this->id;
        $sql.= ' AND rang = '.$rang;
        $resql = $this->db->query($sql);
        if ($resql)
        {
            $row = $this->db->fetch_row($resql);
            return $row[0];
        }
    }

    /**
     * 	Get max value used for position of line (rang)
     *
     * 	@param		int		$fk_parent_line		Parent line id
     *  @return     int  			   			Max value of rang in table of lines
     */
    function line_max($fk_parent_line=0)
    {
        // Search the last rang with fk_parent_line
        if ($fk_parent_line)
        {
            $sql = 'SELECT max(rang) FROM '.MAIN_DB_PREFIX.$this->table_element_line;
            $sql.= ' WHERE '.$this->fk_element.' = '.$this->id;
            $sql.= ' AND fk_parent_line = '.$fk_parent_line;

            dol_syslog(get_class($this)."::line_max", LOG_DEBUG);
            $resql = $this->db->query($sql);
            if ($resql)
            {
                $row = $this->db->fetch_row($resql);
                if (! empty($row[0]))
                {
                    return $row[0];
                }
                else
                {
                    return $this->getRangOfLine($fk_parent_line);
                }
            }
        }
        // If not, search the last rang of element
        else
        {
            $sql = 'SELECT max(rang) FROM '.MAIN_DB_PREFIX.$this->table_element_line;
            $sql.= ' WHERE '.$this->fk_element.' = '.$this->id;

            dol_syslog(get_class($this)."::line_max", LOG_DEBUG);
            $resql = $this->db->query($sql);
            if ($resql)
            {
                $row = $this->db->fetch_row($resql);
                return $row[0];
            }
        }
    }

    /**
     *  Update external ref of element
     *
     *  @param      string		$ref_ext	Update field ref_ext
     *  @return     int      		   		<0 if KO, >0 if OK
     */
    function update_ref_ext($ref_ext)
    {
        if (! $this->table_element)
        {
            dol_syslog(get_class($this)."::update_ref_ext was called on objet with property table_element not defined", LOG_ERR);
            return -1;
        }

        $sql = 'UPDATE '.MAIN_DB_PREFIX.$this->table_element;
        $sql.= " SET ref_ext = '".$this->db->escape($ref_ext)."'";
        $sql.= " WHERE ".(isset($this->table_rowid)?$this->table_rowid:'rowid')." = ". $this->id;

        dol_syslog(get_class($this)."::update_ref_ext", LOG_DEBUG);
        if ($this->db->query($sql))
        {
            $this->ref_ext = $ref_ext;
            return 1;
        }
        else
        {
            $this->error=$this->db->error();
            return -1;
        }
    }

    /**
     *  Update note of element
     *
     *  @param      string		$note		New value for note
     *  @param		string		$suffix		'', '_public' or '_private'
     *  @return     int      		   		<0 if KO, >0 if OK
     */
    function update_note($note,$suffix='')
    {
    	if (! $this->table_element)
    	{
    		dol_syslog(get_class($this)."::update_note was called on objet with property table_element not defined", LOG_ERR);
    		return -1;
    	}
		if (! in_array($suffix,array('','_public','_private')))
		{
    		dol_syslog(get_class($this)."::update_note Parameter suffix must be empty, '_private' or '_public'", LOG_ERR);
			return -2;
		}
        // Special cas
        //var_dump($this->table_element);exit;
		if ($this->table_element == 'product') $suffix='';
            
    	$sql = 'UPDATE '.MAIN_DB_PREFIX.$this->table_element;
    	$sql.= " SET note".$suffix." = ".(!empty($note)?("'".$this->db->escape($note)."'"):"NULL");
    	$sql.= " WHERE rowid =". $this->id;

    	dol_syslog(get_class($this)."::update_note", LOG_DEBUG);
    	if ($this->db->query($sql))
    	{
    		if ($suffix == '_public') $this->note_public = $note;
    		else if ($suffix == '_private') $this->note_private = $note;
    		else 
    		{
    		    $this->note = $note;      // deprecated
    		    $this->note_private = $note;
    		}
    		return 1;
    	}
    	else
    	{
    		$this->error=$this->db->lasterror();
    		return -1;
    	}
    }

    /**
     * 	Update public note (kept for backward compatibility)
     *
     * @param      string		$note		New value for note
     * @return     int      		   		<0 if KO, >0 if OK
     * @deprecated
     * @see update_note()
     */
    function update_note_public($note)
    {
    	return $this->update_note($note,'_public');
    }

    /**
     *	Update total_ht, total_ttc, total_vat, total_localtax1, total_localtax2 for an object (sum of lines).
     *  Must be called at end of methods addline or updateline.
     *
     *	@param	int		$exclspec          	>0 = Exclude special product (product_type=9)
     *  @param  string	$roundingadjust    	'none'=Do nothing, 'auto'=Use default method (MAIN_ROUNDOFTOTAL_NOT_TOTALOFROUND if defined, or '0'), '0'=Force mode total of rounding, '1'=Force mode rounding of total
     *  @param	int		$nodatabaseupdate	1=Do not update database. Update only properties of object.
     *  @param	Societe	$seller				If roundingadjust is '0' or '1' or maybe 'auto', it means we recalculate total for lines before calculating total for object and for this, we need seller object.
     *	@return	int    			           	<0 if KO, >0 if OK
     */
    function update_price($exclspec=0,$roundingadjust='none',$nodatabaseupdate=0,$seller=null)
    {
    	global $conf;

        include_once DOL_DOCUMENT_ROOT.'/core/lib/price.lib.php';

        if ($roundingadjust == '-1') $roundingadjust='auto';	// For backward compatibility

        $forcedroundingmode=$roundingadjust;
        if ($forcedroundingmode == 'auto' && isset($conf->global->MAIN_ROUNDOFTOTAL_NOT_TOTALOFROUND)) $forcedroundingmode=$conf->global->MAIN_ROUNDOFTOTAL_NOT_TOTALOFROUND;
        elseif ($forcedroundingmode == 'auto') $forcedroundingmode='0';

        $error=0;

        // Define constants to find lines to sum
        $fieldtva='total_tva';
        $fieldlocaltax1='total_localtax1';
        $fieldlocaltax2='total_localtax2';
        $fieldup='subprice';
        if ($this->element == 'facture_fourn' || $this->element == 'invoice_supplier')
        {
        	$fieldtva='tva';
        	$fieldup='pu_ht';
        }
        if ($this->element == 'expensereport')
        {
        	$fieldup='value_unit';
        }

        $sql = 'SELECT rowid, qty, '.$fieldup.' as up, remise_percent, total_ht, '.$fieldtva.' as total_tva, total_ttc, '.$fieldlocaltax1.' as total_localtax1, '.$fieldlocaltax2.' as total_localtax2,';
        $sql.= ' tva_tx as vatrate, localtax1_tx, localtax2_tx, localtax1_type, localtax2_type, info_bits, product_type';
		if ($this->table_element_line == 'facturedet') $sql.= ', situation_percent';
		$sql.= ', multicurrency_total_ht, multicurrency_total_tva, multicurrency_total_ttc';
        $sql.= ' FROM '.MAIN_DB_PREFIX.$this->table_element_line;
        $sql.= ' WHERE '.$this->fk_element.' = '.$this->id;
        if ($exclspec)
        {
            $product_field='product_type';
            if ($this->table_element_line == 'contratdet') $product_field='';    // contratdet table has no product_type field
            if ($product_field) $sql.= ' AND '.$product_field.' <> 9';
        }
        $sql.= ' ORDER by rowid';	// We want to be sure to always use same order of line to not change lines differently when option MAIN_ROUNDOFTOTAL_NOT_TOTALOFROUND is used

        dol_syslog(get_class($this)."::update_price", LOG_DEBUG);
        $resql = $this->db->query($sql);
        if ($resql)
        {
            $this->total_ht  = 0;
            $this->total_tva = 0;
            $this->total_localtax1 = 0;
            $this->total_localtax2 = 0;
            $this->total_ttc = 0;
            $total_ht_by_vats  = array();
            $total_tva_by_vats = array();
            $total_ttc_by_vats = array();
			$this->multicurrency_total_ht	= 0;
            $this->multicurrency_total_tva	= 0;
           	$this->multicurrency_total_ttc	= 0;

            $num = $this->db->num_rows($resql);
            $i = 0;
            while ($i < $num)
            {
                $obj = $this->db->fetch_object($resql);

                // Note: There is no check on detail line and no check on total, if $forcedroundingmode = 'none'
				$multicurrency_tx = !empty($this->multicurrency_tx) ? $this->multicurrency_tx : 1;
                if ($forcedroundingmode == '0')	// Check if data on line are consistent. This may solve lines that were not consistent because set with $forcedroundingmode='auto'
                {
                	$localtax_array=array($obj->localtax1_type,$obj->localtax1_tx,$obj->localtax2_type,$obj->localtax2_tx);
                	$tmpcal=calcul_price_total($obj->qty, $obj->up, $obj->remise_percent, $obj->vatrate, $obj->localtax1_tx, $obj->localtax2_tx, 0, 'HT', $obj->info_bits, $obj->product_type, $seller, $localtax_array, (isset($obj->situation_percent) ? $obj->situation_percent : 100), $multicurrency_tx);
                	$diff=price2num($tmpcal[1] - $obj->total_tva, 'MT', 1);
                	if ($diff)
                	{
                		$sqlfix="UPDATE ".MAIN_DB_PREFIX.$this->table_element_line." SET ".$fieldtva." = ".$tmpcal[1].", total_ttc = ".$tmpcal[2]." WHERE rowid = ".$obj->rowid;
                		dol_syslog('We found unconsistent data into detailed line (difference of '.$diff.') for line rowid = '.$obj->rowid." (total vat of line calculated=".$tmpcal[1].", database=".$obj->total_tva."). We fix the total_vat and total_ttc of line by running sqlfix = ".$sqlfix);
						$resqlfix=$this->db->query($sqlfix);
						if (! $resqlfix) dol_print_error($this->db,'Failed to update line');
						$obj->total_tva = $tmpcal[1];
						$obj->total_ttc = $tmpcal[2];
                		//
                	}
                }

                $this->total_ht        += $obj->total_ht;		// The only field visible at end of line detail
                $this->total_tva       += $obj->total_tva;
                $this->total_localtax1 += $obj->total_localtax1;
                $this->total_localtax2 += $obj->total_localtax2;
                $this->total_ttc       += $obj->total_ttc;

                if (! isset($total_ht_by_vats[$obj->vatrate]))  $total_ht_by_vats[$obj->vatrate]=0;
                if (! isset($total_tva_by_vats[$obj->vatrate])) $total_tva_by_vats[$obj->vatrate]=0;
                if (! isset($total_ttc_by_vats[$obj->vatrate])) $total_ttc_by_vats[$obj->vatrate]=0;
                $total_ht_by_vats[$obj->vatrate]  += $obj->total_ht;
                $total_tva_by_vats[$obj->vatrate] += $obj->total_tva;
                $total_ttc_by_vats[$obj->vatrate] += $obj->total_ttc;

                if ($forcedroundingmode == '1')	// Check if we need adjustement onto line for vat
                {
                	$tmpvat=price2num($total_ht_by_vats[$obj->vatrate] * $obj->vatrate / 100, 'MT', 1);
                	$diff=price2num($total_tva_by_vats[$obj->vatrate]-$tmpvat, 'MT', 1);
                	//print 'Line '.$i.' rowid='.$obj->rowid.' vat_rate='.$obj->vatrate.' total_ht='.$obj->total_ht.' total_tva='.$obj->total_tva.' total_ttc='.$obj->total_ttc.' total_ht_by_vats='.$total_ht_by_vats[$obj->vatrate].' total_tva_by_vats='.$total_tva_by_vats[$obj->vatrate].' (new calculation = '.$tmpvat.') total_ttc_by_vats='.$total_ttc_by_vats[$obj->vatrate].($diff?" => DIFF":"")."<br>\n";
                	if ($diff)
                	{
                		if (abs($diff) > 0.1) { dol_syslog('A rounding difference was detected into TOTAL but is too high to be corrected', LOG_WARNING); exit; }
                		$sqlfix="UPDATE ".MAIN_DB_PREFIX.$this->table_element_line." SET ".$fieldtva." = ".($obj->total_tva - $diff).", total_ttc = ".($obj->total_ttc - $diff)." WHERE rowid = ".$obj->rowid;
                		dol_syslog('We found a difference of '.$diff.' for line rowid = '.$obj->rowid.". We fix the total_vat and total_ttc of line by running sqlfix = ".$sqlfix);
						$resqlfix=$this->db->query($sqlfix);
						if (! $resqlfix) dol_print_error($this->db,'Failed to update line');
						$this->total_tva -= $diff;
						$this->total_ttc -= $diff;
						$total_tva_by_vats[$obj->vatrate] -= $diff;
						$total_ttc_by_vats[$obj->vatrate] -= $diff;

                	}
                }

                $i++;
            }

            // Add revenue stamp to total
            $this->total_ttc       += isset($this->revenuestamp)?$this->revenuestamp:0;

			// Situations totals
			if ($this->situation_cycle_ref && $this->situation_counter > 1) {
				$prev_sits = $this->get_prev_sits();

				foreach ($prev_sits as $sit) {
					$this->total_ht -= $sit->total_ht;
					$this->total_tva -= $sit->total_tva;
					$this->total_localtax1 -= $sit->total_localtax1;
					$this->total_localtax2 -= $sit->total_localtax2;
					$this->total_ttc -= $sit->total_ttc;
				}
			}

			// Multicurrency
			$this->multicurrency_total_ht	+= $this->total_ht * $multicurrency_tx;
            $this->multicurrency_total_tva	+= $this->total_tva * $multicurrency_tx;
            $this->multicurrency_total_ttc	+= $this->total_ttc * $multicurrency_tx;

            $this->db->free($resql);

            // Now update global field total_ht, total_ttc and tva
            $fieldht='total_ht';
            $fieldtva='tva';
            $fieldlocaltax1='localtax1';
            $fieldlocaltax2='localtax2';
            $fieldttc='total_ttc';
            // Specific code for backward compatibility with old field names
            if ($this->element == 'facture' || $this->element == 'facturerec')             $fieldht='total';
            if ($this->element == 'facture_fourn' || $this->element == 'invoice_supplier') $fieldtva='total_tva';
            if ($this->element == 'propal')                                                $fieldttc='total';
            if ($this->element == 'expensereport')                                         $fieldtva='total_tva';
            if ($this->element == 'supplier_proposal')                                      $fieldttc='total';

            if (empty($nodatabaseupdate))
            {
                $sql = 'UPDATE '.MAIN_DB_PREFIX.$this->table_element.' SET';
                $sql .= " ".$fieldht."='".price2num($this->total_ht)."',";
                $sql .= " ".$fieldtva."='".price2num($this->total_tva)."',";
                $sql .= " ".$fieldlocaltax1."='".price2num($this->total_localtax1)."',";
                $sql .= " ".$fieldlocaltax2."='".price2num($this->total_localtax2)."',";
                $sql .= " ".$fieldttc."='".price2num($this->total_ttc)."'";
				$sql .= ", multicurrency_total_ht='".price2num($this->multicurrency_total_ht, 'MT', 1)."'";
				$sql .= ", multicurrency_total_tva='".price2num($this->multicurrency_total_tva, 'MT', 1)."'";
				$sql .= ", multicurrency_total_ttc='".price2num($this->multicurrency_total_ttc, 'MT', 1)."'";
                $sql .= ' WHERE rowid = '.$this->id;

                //print "xx".$sql;
                dol_syslog(get_class($this)."::update_price", LOG_DEBUG);
                $resql=$this->db->query($sql);
                if (! $resql)
                {
                    $error++;
                    $this->error=$this->db->lasterror();
                    $this->errors[]=$this->db->lasterror();
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
        else
        {
            dol_print_error($this->db,'Bad request in update_price');
            return -1;
        }
    }

    /**
     *	Add objects linked in llx_element_element.
     *
     *	@param		string	$origin		Linked element type
     *	@param		int		$origin_id	Linked element id
     *	@return		int					<=0 if KO, >0 if OK
     *	@see		fetchObjectLinked, updateObjectLinked, deleteObjectLinked
     */
    function add_object_linked($origin=null, $origin_id=null)
    {
    	$origin = (! empty($origin) ? $origin : $this->origin);
    	$origin_id = (! empty($origin_id) ? $origin_id : $this->origin_id);

    	// Special case
    	if ($origin == 'order') $origin='commande';
    	if ($origin == 'invoice') $origin='facture';
    	
        $this->db->begin();

        $sql = "INSERT INTO ".MAIN_DB_PREFIX."element_element (";
        $sql.= "fk_source";
        $sql.= ", sourcetype";
        $sql.= ", fk_target";
        $sql.= ", targettype";
        $sql.= ") VALUES (";
        $sql.= $origin_id;
        $sql.= ", '".$origin."'";
        $sql.= ", ".$this->id;
        $sql.= ", '".$this->element."'";
        $sql.= ")";

        dol_syslog(get_class($this)."::add_object_linked", LOG_DEBUG);
		if ($this->db->query($sql))
	  	{
	  		$this->db->commit();
	  		return 1;
	  	}
	  	else
	  	{
	  		$this->error=$this->db->lasterror();
	  		$this->db->rollback();
	  		return 0;
	  	}
	}

    /**
     *	Fetch array of objects linked to current object. Links are loaded into this->linkedObjects array and this->linkedObjectsIds
     *  Possible usage for parameters:
     *  - all parameters empty -> we look all link to current object (current object can be source or target)
     *  - source id+type -> will get target list linked to source 
     *  - target id+type -> will get source list linked to target 
     *  - source id+type + target type -> will get target list of the type 
     *  - target id+type + target source -> will get source list of the type 
     *
     *	@param	int		$sourceid		Object source id (if not defined, id of object)
     *	@param  string	$sourcetype		Object source type (if not defined, element name of object)
     *	@param  int		$targetid		Object target id (if not defined, id of object)
     *	@param  string	$targettype		Object target type (if not defined, elemennt name of object)
     *	@param  string	$clause			'OR' or 'AND' clause used when both source id and target id are provided
     *  @param	int		$alsosametype	0=Return only links to object that differs from source. 1=Include also link to objects of same type.
     *	@return	void
     *  @see	add_object_linked, updateObjectLinked, deleteObjectLinked
     */
	function fetchObjectLinked($sourceid=null,$sourcetype='',$targetid=null,$targettype='',$clause='OR',$alsosametype=1)
    {
        global $conf;

        $this->linkedObjectsIds=array();
        $this->linkedObjects=array();

        $justsource=false;
        $justtarget=false;
        $withtargettype=false;
        $withsourcetype=false;

        if (! empty($sourceid) && ! empty($sourcetype) && empty($targetid))
        {
        	$justsource=true;  // the source (id and type) is a search criteria
        	if (! empty($targettype)) $withtargettype=true;
        }
        if (! empty($targetid) && ! empty($targettype) && empty($sourceid))
        {
        	$justtarget=true;  // the target (id and type) is a search criteria
        	if (! empty($sourcetype)) $withsourcetype=true;
        }

        $sourceid = (! empty($sourceid) ? $sourceid : $this->id);
        $targetid = (! empty($targetid) ? $targetid : $this->id);
        $sourcetype = (! empty($sourcetype) ? $sourcetype : $this->element);
        $targettype = (! empty($targettype) ? $targettype : $this->element);

        /*if (empty($sourceid) && empty($targetid))
        {
        	dol_syslog('Bad usage of function. No source nor target id defined (nor as parameter nor as object id)', LOG_ERR);
        	return -1;
        }*/

        // Links between objects are stored in table element_element
        $sql = 'SELECT rowid, fk_source, sourcetype, fk_target, targettype';
        $sql.= ' FROM '.MAIN_DB_PREFIX.'element_element';
        $sql.= " WHERE ";
        if ($justsource || $justtarget)
        {
            if ($justsource)
            {
            	$sql.= "fk_source = ".$sourceid." AND sourcetype = '".$sourcetype."'";
            	if ($withtargettype) $sql.= " AND targettype = '".$targettype."'";
            }
            else if ($justtarget)
            {
            	$sql.= "fk_target = ".$targetid." AND targettype = '".$targettype."'";
            	if ($withsourcetype) $sql.= " AND sourcetype = '".$sourcetype."'";
            }
        }
        else
		{
            $sql.= "(fk_source = ".$sourceid." AND sourcetype = '".$sourcetype."')";
            $sql.= " ".$clause." (fk_target = ".$targetid." AND targettype = '".$targettype."')";
        }
        $sql .= ' ORDER BY sourcetype';
        //print $sql;

        dol_syslog(get_class($this)."::fetchObjectLink", LOG_DEBUG);
        $resql = $this->db->query($sql);
        if ($resql)
        {
            $num = $this->db->num_rows($resql);
            $i = 0;
            while ($i < $num)
            {
                $obj = $this->db->fetch_object($resql);
                if ($justsource || $justtarget)
                {
                    if ($justsource)
                    {
                        $this->linkedObjectsIds[$obj->targettype][$obj->rowid]=$obj->fk_target;
                    }
                    else if ($justtarget)
                    {
                        $this->linkedObjectsIds[$obj->sourcetype][$obj->rowid]=$obj->fk_source;
                    }
                }
                else
                {
                    if ($obj->fk_source == $sourceid && $obj->sourcetype == $sourcetype)
                    {
                        $this->linkedObjectsIds[$obj->targettype][$obj->rowid]=$obj->fk_target;
                    }
                    if ($obj->fk_target == $targetid && $obj->targettype == $targettype)
                    {
                        $this->linkedObjectsIds[$obj->sourcetype][$obj->rowid]=$obj->fk_source;
                    }
                }
                $i++;
            }

            if (! empty($this->linkedObjectsIds))
            {
                foreach($this->linkedObjectsIds as $objecttype => $objectids)       // $objecttype is a module name ('facture', 'mymodule', ...) or a module name with a suffix ('project_task', 'mymodule_myobj', ...)
                {
                    // Parse element/subelement (ex: project_task, cabinetmed_consultation, ...)
                    $module = $element = $subelement = $objecttype;
                    if ($objecttype != 'supplier_proposal' && $objecttype != 'order_supplier' && $objecttype != 'invoice_supplier'
                        && preg_match('/^([^_]+)_([^_]+)/i',$objecttype,$regs))
                    {
                        $module = $element = $regs[1];
                        $subelement = $regs[2];
                    }

                    $classpath = $element.'/class';
                    // To work with non standard classpath or module name
                    if ($objecttype == 'facture')			{
                        $classpath = 'compta/facture/class';
                    }
                    else if ($objecttype == 'facturerec')			{
                        $classpath = 'compta/facture/class'; $module = 'facture';
                    }
                    else if ($objecttype == 'propal')			{
                        $classpath = 'comm/propal/class';
                    }
                    else if ($objecttype == 'supplier_proposal')			{
                        $classpath = 'supplier_proposal/class';
                    }
                    else if ($objecttype == 'shipping')			{
                        $classpath = 'expedition/class'; $subelement = 'expedition'; $module = 'expedition_bon';
                    }
                    else if ($objecttype == 'delivery')			{
                        $classpath = 'livraison/class'; $subelement = 'livraison'; $module = 'livraison_bon';
                    }
                    else if ($objecttype == 'invoice_supplier' || $objecttype == 'order_supplier')	{
                        $classpath = 'fourn/class'; $module = 'fournisseur';
                    }
                    else if ($objecttype == 'fichinter')			{
                        $classpath = 'fichinter/class'; $subelement = 'fichinter'; $module = 'ficheinter';
                    }
                    else if ($objecttype == 'subscription')			{
                        $classpath = 'adherents/class'; $module = 'adherent';
                    }

                    // Set classfile
                    $classfile = strtolower($subelement); $classname = ucfirst($subelement);

                    if ($objecttype == 'order') {
                        $classfile = 'commande'; $classname = 'Commande';
                    }
                    else if ($objecttype == 'invoice_supplier') {
                        $classfile = 'fournisseur.facture'; $classname = 'FactureFournisseur';
                    }
                    else if ($objecttype == 'order_supplier')   {
                        $classfile = 'fournisseur.commande'; $classname = 'CommandeFournisseur';
                    }
                    else if ($objecttype == 'supplier_proposal')   {
                        $classfile = 'supplier_proposal'; $classname = 'SupplierProposal';
                    }
                    else if ($objecttype == 'facturerec')   {
                        $classfile = 'facture-rec'; $classname = 'FactureRec';
                    }
                    else if ($objecttype == 'subscription')   {
                        $classfile = 'subscription'; $classname = 'Subscription';
                    }

                    // Here $module, $classfile and $classname are set
                    if ($conf->$module->enabled && (($element != $this->element) || $alsosametype))
                    {
                        dol_include_once('/'.$classpath.'/'.$classfile.'.class.php');
                        //print '/'.$classpath.'/'.$classfile.'.class.php '.class_exists($classname);
                        if (class_exists($classname))
                        {
	                        foreach($objectids as $i => $objectid)	// $i is rowid into llx_element_element
	                        {
	                            $object = new $classname($this->db);
	                            $ret = $object->fetch($objectid);
	                            if ($ret >= 0)
	                            {
	                                $this->linkedObjects[$objecttype][$i] = $object;
	                            }
	                        }
                        }
                    }
                }
            }
        }
        else
        {
            dol_print_error($this->db);
        }
    }

    /**
     *	Update object linked of a current object
     *
     *	@param	int		$sourceid		Object source id
     *	@param  string	$sourcetype		Object source type
     *	@param  int		$targetid		Object target id
     *	@param  string	$targettype		Object target type
     *	@return							int	>0 if OK, <0 if KO
     *	@see	add_object_linked, fetObjectLinked, deleteObjectLinked
     */
    function updateObjectLinked($sourceid=null, $sourcetype='', $targetid=null, $targettype='')
    {
    	$updatesource=false;
    	$updatetarget=false;

    	if (! empty($sourceid) && ! empty($sourcetype) && empty($targetid) && empty($targettype)) $updatesource=true;
    	else if (empty($sourceid) && empty($sourcetype) && ! empty($targetid) && ! empty($targettype)) $updatetarget=true;

    	$sql = "UPDATE ".MAIN_DB_PREFIX."element_element SET ";
    	if ($updatesource)
    	{
    		$sql.= "fk_source = ".$sourceid;
    		$sql.= ", sourcetype = '".$sourcetype."'";
    		$sql.= " WHERE fk_target = ".$this->id;
    		$sql.= " AND targettype = '".$this->element."'";
    	}
    	else if ($updatetarget)
    	{
    		$sql.= "fk_target = ".$targetid;
    		$sql.= ", targettype = '".$targettype."'";
    		$sql.= " WHERE fk_source = ".$this->id;
    		$sql.= " AND sourcetype = '".$this->element."'";
    	}

    	dol_syslog(get_class($this)."::updateObjectLinked", LOG_DEBUG);
    	if ($this->db->query($sql))
    	{
    		return 1;
    	}
    	else
    	{
    		$this->error=$this->db->lasterror();
    		return -1;
    	}
    }

	/**
	 *	Delete all links between an object $this
	 *
	 *	@param	int		$sourceid		Object source id
     *	@param  string	$sourcetype		Object source type
     *	@param  int		$targetid		Object target id
     *	@param  string	$targettype		Object target type
     *  @param	int		$rowid			Row id of line to delete. If defined, other parameters are not used.
	 *	@return     					int	>0 if OK, <0 if KO
	 *	@see	add_object_linked, updateObjectLinked, fetchObjectLinked
	 */
	function deleteObjectLinked($sourceid=null, $sourcetype='', $targetid=null, $targettype='', $rowid='')
	{
		$deletesource=false;
		$deletetarget=false;

		if (! empty($sourceid) && ! empty($sourcetype) && empty($targetid) && empty($targettype)) $deletesource=true;
		else if (empty($sourceid) && empty($sourcetype) && ! empty($targetid) && ! empty($targettype)) $deletetarget=true;

		$sourceid = (! empty($sourceid) ? $sourceid : $this->id);
		$sourcetype = (! empty($sourcetype) ? $sourcetype : $this->element);
		$targetid = (! empty($targetid) ? $targetid : $this->id);
		$targettype = (! empty($targettype) ? $targettype : $this->element);

		$sql = "DELETE FROM ".MAIN_DB_PREFIX."element_element";
		$sql.= " WHERE";
		if ($rowid > 0)
		{
			$sql.=" rowid = ".$rowid;
		}
		else
		{
			if ($deletesource)
			{
				$sql.= " fk_source = ".$sourceid." AND sourcetype = '".$sourcetype."'";
				$sql.= " AND fk_target = ".$this->id." AND targettype = '".$this->element."'";
			}
			else if ($deletetarget)
			{
				$sql.= " fk_target = ".$targetid." AND targettype = '".$targettype."'";
				$sql.= " AND fk_source = ".$this->id." AND sourcetype = '".$this->element."'";
			}
			else
			{
				$sql.= " (fk_source = ".$this->id." AND sourcetype = '".$this->element."')";
				$sql.= " OR";
				$sql.= " (fk_target = ".$this->id." AND targettype = '".$this->element."')";
			}
		}

		dol_syslog(get_class($this)."::deleteObjectLinked", LOG_DEBUG);
		if ($this->db->query($sql))
		{
			return 1;
		}
		else
		{
			$this->error=$this->db->lasterror();
			$this->errors[]=$this->error;
			return -1;
		}
	}

    /**
     *      Set status of an object
     *
     *      @param	int		$status			Status to set
     *      @param	int		$elementId		Id of element to force (use this->id by default)
     *      @param	string	$elementType	Type of element to force (use this->table_element by default)
     *      @return int						<0 if KO, >0 if OK
     */
    function setStatut($status,$elementId=null,$elementType='')
    {
    	global $user,$langs,$conf;

        $elementId = (!empty($elementId)?$elementId:$this->id);
        $elementTable = (!empty($elementType)?$elementType:$this->table_element);

        $this->db->begin();

        $fieldstatus="fk_statut";
        if ($elementTable == 'mailing') $fieldstatus="statut";
        if ($elementTable == 'user') $fieldstatus="statut";
        if ($elementTable == 'expensereport') $fieldstatus="fk_statut";
		if ($elementTable == 'commande_fournisseur_dispatch') $fieldstatus="status";

        $sql = "UPDATE ".MAIN_DB_PREFIX.$elementTable;
        $sql.= " SET ".$fieldstatus." = ".$status;
        // If status = 1 = validated, update also fk_user_valid
        if ($status == 1 && $elementTable == 'expensereport') $sql.=", fk_user_valid = ".$user->id;
        $sql.= " WHERE rowid=".$elementId;

        dol_syslog(get_class($this)."::setStatut", LOG_DEBUG);
        if ($this->db->query($sql))
        {
            $error = 0;

            $trigkey='';
            if ($this->element == 'supplier_proposal' && $status == 2) $trigkey='SUPPLIER_PROPOSAL_CLOSE';
            if ($this->element == 'fichinter' && $status == 3) $trigkey='FICHINTER_CLASSIFY_DONE';
            if ($this->element == 'fichinter' && $status == 2) $trigkey='FICHINTER_CLASSIFY_BILLED';
            if ($this->element == 'fichinter' && $status == 1) $trigkey='FICHINTER_CLASSIFY_UNBILLED';

            if ($trigkey)
            {
                // Appel des triggers
                include_once DOL_DOCUMENT_ROOT . '/core/class/interfaces.class.php';
                $interface=new Interfaces($this->db);
                $result=$interface->run_triggers($trigkey,$this,$user,$langs,$conf);
                if ($result < 0) {
                    $error++; $this->errors=$interface->errors;
                }
                // Fin appel triggers
            }

			if (! $error)
			{
				$this->db->commit();
        		if (empty($elementId))    // If the element we update was $this (so $elementId is null)
        		{
        		    $this->statut = $status;
        		    $this->status = $status;
        		}
				return 1;
			}
			else
			{
				$this->db->rollback();
				dol_syslog(get_class($this)."::setStatus ".$this->error,LOG_ERR);
				return -1;
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
     *  Load type of canvas of an object if it exists
     *
     *  @param      int		$id     Record id
     *  @param      string	$ref    Record ref
     *  @return		int				<0 if KO, 0 if nothing done, >0 if OK
     */
    function getCanvas($id=0,$ref='')
    {
        global $conf;

        if (empty($id) && empty($ref)) return 0;
        if (! empty($conf->global->MAIN_DISABLE_CANVAS)) return 0;    // To increase speed. Not enabled by default.

        // Clean parameters
        $ref = trim($ref);

        $sql = "SELECT rowid, canvas";
        $sql.= " FROM ".MAIN_DB_PREFIX.$this->table_element;
        $sql.= " WHERE entity IN (".getEntity($this->element, 1).")";
        if (! empty($id))  $sql.= " AND rowid = ".$id;
        if (! empty($ref)) $sql.= " AND ref = '".$this->db->escape($ref)."'";

        $resql = $this->db->query($sql);
        if ($resql)
        {
            $obj = $this->db->fetch_object($resql);
            if ($obj)
            {
                $this->canvas   = $obj->canvas;
                return 1;
            }
            else return 0;
        }
        else
        {
            dol_print_error($this->db);
            return -1;
        }
    }


    /**
     * 	Get special code of a line
     *
     * 	@param	int		$lineid		Id of line
     * 	@return	int					Special code
     */
    function getSpecialCode($lineid)
    {
        $sql = 'SELECT special_code FROM '.MAIN_DB_PREFIX.$this->table_element_line;
        $sql.= ' WHERE rowid = '.$lineid;
        $resql = $this->db->query($sql);
        if ($resql)
        {
            $row = $this->db->fetch_row($resql);
            return $row[0];
        }
    }

    /**
     *  Function to check if an object is used by others.
     *  Check is done into this->childtables. There is no check into llx_element_element.
     *
     *  @param	int		$id			Force id of object
     *  @return	int					<0 if KO, 0 if not used, >0 if already used
     */
    function isObjectUsed($id=0)
    {
        if (empty($id)) $id=$this->id;
        
        // Check parameters
        if (! isset($this->childtables) || ! is_array($this->childtables) || count($this->childtables) == 0)
        {
            dol_print_error('Called isObjectUsed on a class with property this->childtables not defined');
            return -1;
        }

        // Test if child exists
        $haschild=0;
        foreach($this->childtables as $table)
        {
            // Check if third party can be deleted
            $sql = "SELECT COUNT(*) as nb from ".MAIN_DB_PREFIX.$table;
            $sql.= " WHERE ".$this->fk_element." = ".$id;
            $resql=$this->db->query($sql);
            if ($resql)
            {
                $obj=$this->db->fetch_object($resql);
                $haschild+=$obj->nb;
                //print 'Found into table '.$table;
                if ($haschild) break;    // We found at least on, we stop here
            }
            else
            {
                $this->error=$this->db->lasterror();
                return -1;
            }
        }
        if ($haschild > 0)
        {
            $this->error="ErrorRecordHasChildren";
            return $haschild;
        }
        else return 0;
    }

    /**
     *  Function to say how many lines object contains
     *
     *	@param	int		$predefined		-1=All, 0=Count free product/service only, 1=Count predefined product/service only, 2=Count predefined product, 3=Count predefined service
     *  @return	int						<0 if KO, 0 if no predefined products, nb of lines with predefined products if found
     */
    function hasProductsOrServices($predefined=-1)
    {
        $nb=0;

        foreach($this->lines as $key => $val)
        {
            $qualified=0;
            if ($predefined == -1) $qualified=1;
            if ($predefined == 1 && $val->fk_product > 0) $qualified=1;
            if ($predefined == 0 && $val->fk_product <= 0) $qualified=1;
            if ($predefined == 2 && $val->fk_product > 0 && $val->product_type==0) $qualified=1;
            if ($predefined == 3 && $val->fk_product > 0 && $val->product_type==1) $qualified=1;
            if ($qualified) $nb++;
        }
        dol_syslog(get_class($this).'::hasProductsOrServices we found '.$nb.' qualified lines of products/servcies');
        return $nb;
    }

    /**
     * Function that returns the total amount HT of discounts applied for all lines.
     *
     * @return 	float
     */
    function getTotalDiscount()
    {
    	$total_discount=0.00;

        $sql = "SELECT subprice as pu_ht, qty, remise_percent, total_ht";
        $sql.= " FROM ".MAIN_DB_PREFIX.$this->table_element."det";
        $sql.= " WHERE ".$this->fk_element." = ".$this->id;

        dol_syslog(get_class($this).'::getTotalDiscount', LOG_DEBUG);
        $resql = $this->db->query($sql);
        if ($resql)
        {
        	$num=$this->db->num_rows($resql);
        	$i=0;
        	while ($i < $num)
        	{
            	$obj = $this->db->fetch_object($resql);

            	$pu_ht = $obj->pu_ht;
            	$qty= $obj->qty;
            	$total_ht = $obj->total_ht;

        		$total_discount_line = floatval(price2num(($pu_ht * $qty) - $total_ht, 'MT'));
        		$total_discount += $total_discount_line;

        		$i++;
        	}
        }

        //print $total_discount; exit;
        return price2num($total_discount);
    }


    /**
     * Return into unit=0, the calculated total of weight and volume of all lines * qty
     * Calculate by adding weight and volume of each product line, so properties ->volume/volume_units/weight/weight_units must be loaded on line.
     *
     * @return  array                           array('weight'=>...,'volume'=>...)
     */
    function getTotalWeightVolume()
    {
        $totalWeight = 0;
        $totalVolume = 0;
	    // defined for shipment only
        $totalOrdered = '';
	    // defined for shipment only
        $totalToShip = '';

        foreach ($this->lines as $line)
        {
            if (isset($line->qty_asked))   
            {
                if (empty($totalOrdered)) $totalOrdered=0;  // Avoid warning because $totalOrdered is ''
                $totalOrdered+=$line->qty_asked;    // defined for shipment only
            }
            if (isset($line->qty_shipped)) 
            {
                if (empty($totalToShip)) $totalToShip=0;    // Avoid warning because $totalToShip is ''
                $totalToShip+=$line->qty_shipped;   // defined for shipment only
            }

	        // Define qty, weight, volume, weight_units, volume_units
	        if ($this->element == 'shipping') {
		        // for shipments
		        $qty = $line->qty_shipped ? $line->qty_shipped : 0;
	        }
	        else {
		        $qty = $line->qty ? $line->qty : 0;
	        }

            $weight = $line->weight ? $line->weight : 0;
            $volume = $line->volume ? $line->volume : 0;

            $weight_units=$line->weight_units;
            $volume_units=$line->volume_units;

            $weightUnit=0;
            $volumeUnit=0;
            if (! empty($weight_units)) $weightUnit = $weight_units;
            if (! empty($volume_units)) $volumeUnit = $volume_units;

            if (empty($totalWeight)) $totalWeight=0;  // Avoid warning because $totalWeight is ''
            if (empty($totalVolume)) $totalVolume=0;  // Avoid warning because $totalVolume is ''
            
            //var_dump($line->volume_units);
            if ($weight_units < 50)   // >50 means a standard unit (power of 10 of official unit), > 50 means an exotic unit (like inch)
            {
                $trueWeightUnit=pow(10, $weightUnit);
                $totalWeight += $weight * $qty * $trueWeightUnit;
            }
            else
            {
                $totalWeight += $weight * $qty;   // This may be wrong if we mix different units
            }
            if ($volume_units < 50)   // >50 means a standard unit (power of 10 of official unit), > 50 means an exotic unit (like inch)
            {
                //print $line->volume."x".$line->volume_units."x".($line->volume_units < 50)."x".$volumeUnit;
                $trueVolumeUnit=pow(10, $volumeUnit);
                //print $line->volume;
                $totalVolume += $volume * $qty * $trueVolumeUnit;
            }
            else
            {
                $totalVolume += $volume * $qty;   // This may be wrong if we mix different units
            }
        }

        return array('weight'=>$totalWeight, 'volume'=>$totalVolume, 'ordered'=>$totalOrdered, 'toship'=>$totalToShip);
    }


    /**
     *	Set extra parameters
     *
     *	@return	int      <0 if KO, >0 if OK
     */
    function setExtraParameters()
    {
    	$this->db->begin();

    	$extraparams = (! empty($this->extraparams) ? json_encode($this->extraparams) : null);

    	$sql = "UPDATE ".MAIN_DB_PREFIX.$this->table_element;
    	$sql.= " SET extraparams = ".(! empty($extraparams) ? "'".$this->db->escape($extraparams)."'" : "null");
    	$sql.= " WHERE rowid = ".$this->id;

    	dol_syslog(get_class($this)."::setExtraParameters", LOG_DEBUG);
    	$resql = $this->db->query($sql);
    	if (! $resql)
    	{
    		$this->error=$this->db->lasterror();
    		$this->db->rollback();
    		return -1;
    	}
    	else
    	{
    		$this->db->commit();
    		return 1;
    	}
    }


	/**
     *    Return incoterms informations
     *    TODO Use a cache for label get
     *
     *    @return	string	incoterms info
     */
    function display_incoterms()
    {
        $out = '';
		$this->libelle_incoterms = '';
		if (!empty($this->fk_incoterms))
		{
			$sql = 'SELECT code FROM '.MAIN_DB_PREFIX.'c_incoterms WHERE rowid = '.(int) $this->fk_incoterms;
			$result = $this->db->query($sql);
			if ($result)
			{
				$res = $this->db->fetch_object($result);
				$out .= $res->code;
			}
		}

		$out .= (($res->code && $this->location_incoterms)?' - ':'').$this->location_incoterms;

		return $out;
    }

	/**
     *    Return incoterms informations for pdf display
     *
     *    @return	string		incoterms info
     */
	function getIncotermsForPDF()
	{
		$sql = 'SELECT code FROM '.MAIN_DB_PREFIX.'c_incoterms WHERE rowid = '.(int) $this->fk_incoterms;
		$resql = $this->db->query($sql);
		if ($resql)
		{
			$num = $this->db->num_rows($resql);
			if ($num > 0)
			{
				$res = $this->db->fetch_object($resql);
				return 'Incoterm : '.$res->code.' - '.$this->location_incoterms;
			}
			else
			{
				return '';
			}
		}
		else
		{
            $this->errors[] = $this->db->lasterror();
			return false;
		}
	}

	/**
     *    Define incoterms values of current object
     *
     *    @param	int		$id_incoterm     Id of incoterm to set or '' to remove
	 * 	  @param 	string  $location		 location of incoterm
     *    @return	int     		<0 if KO, >0 if OK
     */
    function setIncoterms($id_incoterm, $location)
    {
        if ($this->id && $this->table_element)
        {
            $sql = "UPDATE ".MAIN_DB_PREFIX.$this->table_element;
            $sql.= " SET fk_incoterms = ".($id_incoterm > 0 ? $id_incoterm : "null");
			$sql.= ", location_incoterms = ".($id_incoterm > 0 ? "'".$this->db->escape($location)."'" : "null");
            $sql.= " WHERE rowid = " . $this->id;
			dol_syslog(get_class($this).'::setIncoterms', LOG_DEBUG);
            $resql=$this->db->query($sql);
            if ($resql)
            {
            	$this->fk_incoterms = $id_incoterm;
				$this->location_incoterms = $location;

				$sql = 'SELECT libelle FROM '.MAIN_DB_PREFIX.'c_incoterms WHERE rowid = '.(int) $this->fk_incoterms;
				$res = $this->db->query($sql);
				if ($res)
				{
					$obj = $this->db->fetch_object($res);
					$this->libelle_incoterms = $obj->libelle;
				}
                return 1;
            }
            else
			{
                $this->errors[] = $this->db->lasterror();
                return -1;
            }
        }
        else return -1;
    }


    /**
     *  Return if a country is inside the EEC (European Economic Community)
     *  TODO Add a field into dictionary
     *
     *  @return     boolean		true = country inside EEC, false = country outside EEC
     */
    function isInEEC()
    {
        // List of all country codes that are in europe for european vat rules
        // List found on http://ec.europa.eu/taxation_customs/common/faq/faq_1179_en.htm#9
        $country_code_in_EEC=array(
    			'AT',	// Austria
    			'BE',	// Belgium
    			'BG',	// Bulgaria
    			'CY',	// Cyprus
    			'CZ',	// Czech republic
    			'DE',	// Germany
    			'DK',	// Danemark
    			'EE',	// Estonia
    			'ES',	// Spain
    			'FI',	// Finland
    			'FR',	// France
    			'GB',	// United Kingdom
    			'GR',	// Greece
    			'HR',   // Croatia
                'NL',	// Holland
    			'HU',	// Hungary
    			'IE',	// Ireland
    			'IM',	// Isle of Man - Included in UK
    			'IT',	// Italy
    			'LT',	// Lithuania
    			'LU',	// Luxembourg
    			'LV',	// Latvia
    			'MC',	// Monaco - Included in France
    			'MT',	// Malta
                //'NO',	// Norway
    			'PL',	// Poland
    			'PT',	// Portugal
    			'RO',	// Romania
    			'SE',	// Sweden
    			'SK',	// Slovakia
    			'SI',	// Slovenia
    			'UK',	// United Kingdom
        //'CH',	// Switzerland - No. Swizerland in not in EEC
        );
        //print "dd".$this->country_code;
        return in_array($this->country_code,$country_code_in_EEC);
    }


    // --------------------
    // TODO: All functions here must be redesigned and moved as they are not business functions but output functions
    // --------------------

    /* This is to show add lines */

    /**
     *	Show add free and predefined products/services form
     *
     *  @param	int		        $dateSelector       1=Show also date range input fields
     *  @param	Societe			$seller				Object thirdparty who sell
     *  @param	Societe			$buyer				Object thirdparty who buy
     *	@return	void
     */
	function formAddObjectLine($dateSelector,$seller,$buyer)
	{
		global $conf,$user,$langs,$object,$hookmanager;
		global $form,$bcnd,$var;

		//Line extrafield
		require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
		$extrafieldsline = new ExtraFields($this->db);
		$extralabelslines=$extrafieldsline->fetch_name_optionals_label($this->table_element_line);

		// Output template part (modules that overwrite templates must declare this into descriptor)
        // Use global variables + $dateSelector + $seller and $buyer
		$dirtpls=array_merge($conf->modules_parts['tpl'],array('/core/tpl'));
		foreach($dirtpls as $reldir)
		{
			$tpl = dol_buildpath($reldir.'/objectline_create.tpl.php');
			if (empty($conf->file->strict_mode)) {
				$res=@include $tpl;
			} else {
				$res=include $tpl; // for debug
			}
		    if ($res) break;
		}
    }



    /* This is to show array of line of details */


	/**
	 *	Return HTML table for object lines
	 *	TODO Move this into an output class file (htmlline.class.php)
	 *	If lines are into a template, title must also be into a template
	 *	But for the moment we don't know if it'st possible as we keep a method available on overloaded objects.
	 *
	 *	@param	string		$action				Action code
	 *	@param  string		$seller            	Object of seller third party
	 *	@param  string  	$buyer             	Object of buyer third party
	 *	@param	int			$selected		   	Object line selected
	 *	@param  int	    	$dateSelector      	1=Show also date range input fields
	 *	@return	void
	 */
	function printObjectLines($action, $seller, $buyer, $selected=0, $dateSelector=0)
	{
		global $conf, $hookmanager, $langs, $user;
		// TODO We should not use global var for this !
		global $inputalsopricewithtax, $usemargins, $disableedit, $disablemove, $disableremove, $outputalsopricetotalwithtax;

		// Define usemargins
		$usemargins=0;
		if (! empty($conf->margin->enabled) && ! empty($this->element) && in_array($this->element,array('facture','propal','commande'))) $usemargins=1;

		print '<tr class="liste_titre nodrag nodrop">';

		if (! empty($conf->global->MAIN_VIEW_LINE_NUMBER)) print '<td class="linecolnum" align="center" width="5">&nbsp;</td>';

		// Description
		print '<td class="linecoldescription">'.$langs->trans('Description').'</td>';

		if ($this->element == 'supplier_proposal')
		{
			print '<td class="linerefsupplier" align="right"><span id="title_fourn_ref">'.$langs->trans("SupplierProposalRefFourn").'</span></td>';
		}

		// VAT
		print '<td class="linecolvat" align="right" width="80">'.$langs->trans('VAT').'</td>';

		// Price HT
		print '<td class="linecoluht" align="right" width="80">'.$langs->trans('PriceUHT').'</td>';

		// Multicurrency
		if (!empty($conf->multicurrency->enabled)) print '<td class="linecoluht_currency" align="right" width="80">'.$langs->trans('PriceUHTCurrency', $this->multicurrency_code).'</td>';

		if ($inputalsopricewithtax) print '<td align="right" width="80">'.$langs->trans('PriceUTTC').'</td>';

		// Qty
		print '<td class="linecolqty" align="right">'.$langs->trans('Qty').'</td>';

		if($conf->global->PRODUCT_USE_UNITS)
		{
			print '<td class="linecoluseunit" align="left">'.$langs->trans('Unit').'</td>';
		}

		// Reduction short
		print '<td class="linecoldiscount" align="right">'.$langs->trans('ReductionShort').'</td>';

		if ($this->situation_cycle_ref) {
			print '<td class="linecolcycleref" align="right">' . $langs->trans('Progress') . '</td>';
		}

		if ($usemargins && ! empty($conf->margin->enabled) && empty($user->societe_id))
		{
			if (!empty($user->rights->margins->creer))
			{
				if ($conf->global->MARGIN_TYPE == "1")
					print '<td class="linecolmargin1 margininfos" align="right" width="80">'.$langs->trans('BuyingPrice').'</td>';
				else
					print '<td class="linecolmargin1 margininfos" align="right" width="80">'.$langs->trans('CostPrice').'</td>';	
			}
			
			if (! empty($conf->global->DISPLAY_MARGIN_RATES) && $user->rights->margins->liretous)
				print '<td class="linecolmargin2 margininfos" align="right" width="50">'.$langs->trans('MarginRate').'</td>';
			if (! empty($conf->global->DISPLAY_MARK_RATES) && $user->rights->margins->liretous)
				print '<td class="linecolmargin2 margininfos" align="right" width="50">'.$langs->trans('MarkRate').'</td>';
		}

		// Total HT
		print '<td class="linecolht" align="right">'.$langs->trans('TotalHTShort').'</td>';

		// Multicurrency
		if (!empty($conf->multicurrency->enabled)) print '<td class="linecoltotalht_currency" align="right">'.$langs->trans('TotalHTShortCurrency', $this->multicurrency_code).'</td>';

        if ($outputalsopricetotalwithtax) print '<td align="right" width="80">'.$langs->trans('TotalTTCShort').'</td>';

		print '<td class="linecoledit"></td>';  // No width to allow autodim

		print '<td class="linecoldelete" width="10"></td>';

		print '<td class="linecolmove" width="10"></td>';

		print "</tr>\n";

		$num = count($this->lines);
		$var = true;
		$i	 = 0;

		//Line extrafield
		require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
		$extrafieldsline = new ExtraFields($this->db);
		$extralabelslines=$extrafieldsline->fetch_name_optionals_label($this->table_element_line);

		foreach ($this->lines as $line)
		{
			//Line extrafield
			$line->fetch_optionals($line->id,$extralabelslines);

			$var=!$var;

			//if (is_object($hookmanager) && (($line->product_type == 9 && ! empty($line->special_code)) || ! empty($line->fk_parent_line)))
            if (is_object($hookmanager))   // Old code is commented on preceding line.
			{
				if (empty($line->fk_parent_line))
				{
					$parameters = array('line'=>$line,'var'=>$var,'num'=>$num,'i'=>$i,'dateSelector'=>$dateSelector,'seller'=>$seller,'buyer'=>$buyer,'selected'=>$selected, 'extrafieldsline'=>$extrafieldsline);
                    $reshook = $hookmanager->executeHooks('printObjectLine', $parameters, $this, $action);    // Note that $action and $object may have been modified by some hooks
				}
				else
				{
					$parameters = array('line'=>$line,'var'=>$var,'num'=>$num,'i'=>$i,'dateSelector'=>$dateSelector,'seller'=>$seller,'buyer'=>$buyer,'selected'=>$selected, 'extrafieldsline'=>$extrafieldsline);
                    $reshook = $hookmanager->executeHooks('printObjectSubLine', $parameters, $this, $action);    // Note that $action and $object may have been modified by some hooks
				}
			}
            if (empty($reshook))
			{
				$this->printObjectLine($action,$line,$var,$num,$i,$dateSelector,$seller,$buyer,$selected,$extrafieldsline);
			}

			$i++;
		}
	}

	/**
	 *	Return HTML content of a detail line
	 *	TODO Move this into an output class file (htmlline.class.php)
	 *
	 *	@param	string		$action				GET/POST action
	 *	@param CommonObjectLine $line		       	Selected object line to output
	 *	@param  string	    $var               	Is it a an odd line (true)
	 *	@param  int		    $num               	Number of line (0)
	 *	@param  int		    $i					I
	 *	@param  int		    $dateSelector      	1=Show also date range input fields
	 *	@param  string	    $seller            	Object of seller third party
	 *	@param  string	    $buyer             	Object of buyer third party
	 *	@param	int			$selected		   	Object line selected
	 *  @param  int			$extrafieldsline	Object of extrafield line attribute
	 *	@return	void
	 */
	function printObjectLine($action,$line,$var,$num,$i,$dateSelector,$seller,$buyer,$selected=0,$extrafieldsline=0)
	{
		global $conf,$langs,$user,$object,$hookmanager;
		global $form,$bc,$bcdd;
		global $object_rights, $disableedit, $disablemove;   // TODO We should not use global var for this !

		$object_rights = $this->getRights();

		$element=$this->element;

		$text=''; $description=''; $type=0;

		// Show product and description
		$type=(! empty($line->product_type)?$line->product_type:$line->fk_product_type);
		// Try to enhance type detection using date_start and date_end for free lines where type was not saved.
		if (! empty($line->date_start)) $type=1; // deprecated
		if (! empty($line->date_end)) $type=1; // deprecated

		// Ligne en mode visu
		if ($action != 'editline' || $selected != $line->id)
		{
			// Product
			if ($line->fk_product > 0)
			{
				$product_static = new Product($this->db);
				$product_static->fetch($line->fk_product);

                $product_static->ref = $line->ref; //can change ref in hook
                $product_static->label = $line->label; //can change label in hook
				$text=$product_static->getNomUrl(1);

				// Define output language and label
				if (! empty($conf->global->MAIN_MULTILANGS))
				{
					if (! is_object($this->thirdparty))
					{
						dol_print_error('','Error: Method printObjectLine was called on an object and object->fetch_thirdparty was not done before');
						return;
					}

					$prod = new Product($this->db);
					$prod->fetch($line->fk_product);

					$outputlangs = $langs;
					$newlang='';
					if (empty($newlang) && GETPOST('lang_id')) $newlang=GETPOST('lang_id');
					if (! empty($conf->global->PRODUIT_TEXTS_IN_THIRDPARTY_LANGUAGE) && empty($newlang)) $newlang=$this->thirdparty->default_lang;		// For language to language of customer
					if (! empty($newlang))
					{
						$outputlangs = new Translate("",$conf);
						$outputlangs->setDefaultLang($newlang);
					}

					$label = (! empty($prod->multilangs[$outputlangs->defaultlang]["label"])) ? $prod->multilangs[$outputlangs->defaultlang]["label"] : $line->product_label;
				}
				else
				{
					$label = $line->product_label;
				}

				$text.= ' - '.(! empty($line->label)?$line->label:$label);
				$description.=(! empty($conf->global->PRODUIT_DESC_IN_FORM)?'':dol_htmlentitiesbr($line->description));	// Description is what to show on popup. We shown nothing if already into desc.
			}

			$line->pu_ttc = price2num($line->subprice * (1 + ($line->tva_tx/100)), 'MU');

			// Output template part (modules that overwrite templates must declare this into descriptor)
			// Use global variables + $dateSelector + $seller and $buyer
			$dirtpls=array_merge($conf->modules_parts['tpl'],array('/core/tpl'));
			foreach($dirtpls as $reldir)
			{
				$tpl = dol_buildpath($reldir.'/objectline_view.tpl.php');
				if (empty($conf->file->strict_mode)) {
					$res=@include $tpl;
				} else {
					$res=include $tpl; // for debug
				}
				if ($res) break;
			}
		}

		// Ligne en mode update
		if ($this->statut == 0 && $action == 'editline' && $selected == $line->id)
		{
			$label = (! empty($line->label) ? $line->label : (($line->fk_product > 0) ? $line->product_label : ''));
			if (! empty($conf->global->MAIN_HTML5_PLACEHOLDER)) $placeholder=' placeholder="'.$langs->trans("Label").'"';
			else $placeholder=' title="'.$langs->trans("Label").'"';

			$line->pu_ttc = price2num($line->subprice * (1 + ($line->tva_tx/100)), 'MU');

			// Output template part (modules that overwrite templates must declare this into descriptor)
			// Use global variables + $dateSelector + $seller and $buyer
			$dirtpls=array_merge($conf->modules_parts['tpl'],array('/core/tpl'));
			foreach($dirtpls as $reldir)
			{
				$tpl = dol_buildpath($reldir.'/objectline_edit.tpl.php');
				if (empty($conf->file->strict_mode)) {
					$res=@include $tpl;
				} else {
					$res=include $tpl; // for debug
				}
				if ($res) break;
			}
		}
	}


    /* This is to show array of line of details of source object */


    /**
     * 	Return HTML table table of source object lines
     *  TODO Move this and previous function into output html class file (htmlline.class.php).
     *  If lines are into a template, title must also be into a template
     *  But for the moment we don't know if it's possible as we keep a method available on overloaded objects.
     *
     *  @return	void
     */
    function printOriginLinesList()
    {
        global $langs, $hookmanager, $conf;

        print '<tr class="liste_titre">';
        print '<td>'.$langs->trans('Ref').'</td>';
        print '<td>'.$langs->trans('Description').'</td>';
        print '<td align="right">'.$langs->trans('VATRate').'</td>';
        print '<td align="right">'.$langs->trans('PriceUHT').'</td>';
		if (!empty($conf->multicurrency->enabled)) print '<td align="right">'.$langs->trans('PriceUHTCurrency').'</td>';
        print '<td align="right">'.$langs->trans('Qty').'</td>';
	    if($conf->global->PRODUCT_USE_UNITS)
	    {
		    print '<td align="left">'.$langs->trans('Unit').'</td>';
	    }
        print '<td align="right">'.$langs->trans('ReductionShort').'</td></tr>';

        $var = true;
        $i	 = 0;

        foreach ($this->lines as $line)
        {
            $var=!$var;

            if (is_object($hookmanager) && (($line->product_type == 9 && ! empty($line->special_code)) || ! empty($line->fk_parent_line)))
            {
                if (empty($line->fk_parent_line))
                {
                    $parameters=array('line'=>$line,'var'=>$var,'i'=>$i);
                    $action='';
                    $hookmanager->executeHooks('printOriginObjectLine',$parameters,$this,$action);    // Note that $action and $object may have been modified by some hooks
                }
            }
            else
            {
                $this->printOriginLine($line,$var);
            }

            $i++;
        }
    }

    /**
     * 	Return HTML with a line of table array of source object lines
     *  TODO Move this and previous function into output html class file (htmlline.class.php).
     *  If lines are into a template, title must also be into a template
     *  But for the moment we don't know if it's possible as we keep a method available on overloaded objects.
     *
     * 	@param	CommonObjectLine	$line		Line
     * 	@param	string				$var		Var
     * 	@return	void
     */
    function printOriginLine($line,$var)
    {
        global $langs, $conf;

        //var_dump($line);
		if (!empty($line->date_start))
		{
			$date_start=$line->date_start;
		}
		else
		{
			$date_start=$line->date_debut_prevue;
			if ($line->date_debut_reel) $date_start=$line->date_debut_reel;
		}
		if (!empty($line->date_end))
		{
			$date_end=$line->date_end;
		}
		else
		{
			$date_end=$line->date_fin_prevue;
			if ($line->date_fin_reel) $date_end=$line->date_fin_reel;
		}

        $this->tpl['label'] = '';
        if (! empty($line->fk_parent_line)) $this->tpl['label'].= img_picto('', 'rightarrow');

        if (($line->info_bits & 2) == 2)  // TODO Not sure this is used for source object
        {
            $discount=new DiscountAbsolute($this->db);
            $discount->fk_soc = $this->socid;
            $this->tpl['label'].= $discount->getNomUrl(0,'discount');
        }
        else if (! empty($line->fk_product))
        {
            $productstatic = new Product($this->db);
            $productstatic->id = $line->fk_product;
            $productstatic->ref = $line->ref;
            $productstatic->type = $line->fk_product_type;
            $this->tpl['label'].= $productstatic->getNomUrl(1);
            $this->tpl['label'].= ' - '.(! empty($line->label)?$line->label:$line->product_label);
            // Dates
            if ($line->product_type == 1 && ($date_start || $date_end))
            {
                $this->tpl['label'].= get_date_range($date_start,$date_end);
            }
        }
        else
        {
            $this->tpl['label'].= ($line->product_type == -1 ? '&nbsp;' : ($line->product_type == 1 ? img_object($langs->trans(''),'service') : img_object($langs->trans(''),'product')));
            if (!empty($line->desc)) {
            	$this->tpl['label'].=$line->desc;
            }else {
            	$this->tpl['label'].= ($line->label ? '&nbsp;'.$line->label : '');
            }
            // Dates
            if ($line->product_type == 1 && ($date_start || $date_end))
            {
                $this->tpl['label'].= get_date_range($date_start,$date_end);
            }
        }

        if (! empty($line->desc))
        {
            if ($line->desc == '(CREDIT_NOTE)')  // TODO Not sure this is used for source object
            {
                $discount=new DiscountAbsolute($this->db);
                $discount->fetch($line->fk_remise_except);
                $this->tpl['description'] = $langs->transnoentities("DiscountFromCreditNote",$discount->getNomUrl(0));
            }
            elseif ($line->desc == '(DEPOSIT)')  // TODO Not sure this is used for source object
            {
                $discount=new DiscountAbsolute($this->db);
                $discount->fetch($line->fk_remise_except);
                $this->tpl['description'] = $langs->transnoentities("DiscountFromDeposit",$discount->getNomUrl(0));
            }
            else
            {
                $this->tpl['description'] = dol_trunc($line->desc,60);
            }
        }
        else
        {
            $this->tpl['description'] = '&nbsp;';
        }

        // VAT Rate
        $this->tpl['vat_rate'] = vatrate($line->tva_tx, true);
        if (! empty($line->vat_src_code) && ! preg_match('/\(/', $this->tpl['vat_rate'])) $this->tpl['vat_rate'].=' ('.$line->vat_src_code.')';

        $this->tpl['price'] = price($line->subprice);
		$this->tpl['multicurrency_price'] = price($line->multicurrency_subprice);
        $this->tpl['qty'] = (($line->info_bits & 2) != 2) ? $line->qty : '&nbsp;';
	    if($conf->global->PRODUCT_USE_UNITS) $this->tpl['unit'] = $line->getLabelOfUnit('long');
        $this->tpl['remise_percent'] = (($line->info_bits & 2) != 2) ? vatrate($line->remise_percent, true) : '&nbsp;';

        // Output template part (modules that overwrite templates must declare this into descriptor)
        // Use global variables + $dateSelector + $seller and $buyer
        $dirtpls=array_merge($conf->modules_parts['tpl'],array('/core/tpl'));
        foreach($dirtpls as $reldir)
        {
            $tpl = dol_buildpath($reldir.'/originproductline.tpl.php');
            if (empty($conf->file->strict_mode)) {
            	$res=@include $tpl;
            } else {
            	$res=include $tpl; // for debug
            }
            if ($res) break;
        }
    }


	/**
	 * Show the array with all margin infos
	 *
	 * @param 		bool	$force_price	Force price
	 * @return		void
	 * @deprecated	3.8 Load FormMargin class and make a direct call to displayMarginInfos
	 */
	function displayMarginInfos($force_price=false)
	{
		include_once DOL_DOCUMENT_ROOT.'/core/class/html.formmargin.class.php';
		$formmargin=new FormMargin($this->db);
		$formmargin->displayMarginInfos($this, $force_price);
	}


	/**
	 *	Add resources to the current object : add entry into llx_element_resources
	 *	Need $this->element & $this->id
	 *
	 *	@param		int		$resource_id		Resource id
	 *	@param		string	$resource_type		'resource'
	 *	@param		int		$busy				Busy or not
	 *	@param		int		$mandatory			Mandatory or not
	 *	@return		int							<=0 if KO, >0 if OK
	 */
	function add_element_resource($resource_id, $resource_type, $busy=0, $mandatory=0)
	{
		$this->db->begin();

		$sql = "INSERT INTO ".MAIN_DB_PREFIX."element_resources (";
		$sql.= "resource_id";
		$sql.= ", resource_type";
		$sql.= ", element_id";
		$sql.= ", element_type";
		$sql.= ", busy";
		$sql.= ", mandatory";
		$sql.= ") VALUES (";
		$sql.= $resource_id;
		$sql.= ", '".$resource_type."'";
		$sql.= ", '".$this->id."'";
		$sql.= ", '".$this->element."'";
		$sql.= ", '".$busy."'";
		$sql.= ", '".$mandatory."'";
		$sql.= ")";

		dol_syslog(get_class($this)."::add_element_resource", LOG_DEBUG);
		if ($this->db->query($sql))
		{
			$this->db->commit();
			return 1;
		}
		else
		{
			$this->error=$this->db->lasterror();
			$this->db->rollback();
			return  0;
		}
	}

	/**
	 *    Delete a link to resource line
	 *
	 *    @param	int		$rowid			Id of resource line to delete
	 *    @param	int		$element		element name (for trigger) TODO: use $this->element into commonobject class
	 *    @param	int		$notrigger		Disable all triggers
	 *    @return   int						>0 if OK, <0 if KO
	 */
	function delete_resource($rowid, $element, $notrigger=0)
	{
	    global $user;

	    $this->db->begin();

	    $sql = "DELETE FROM ".MAIN_DB_PREFIX."element_resources";
	    $sql.= " WHERE rowid=".$rowid;

	    dol_syslog(get_class($this)."::delete_resource", LOG_DEBUG);

	    $resql=$this->db->query($sql);
        if (! $resql)
        {
            $this->error=$this->db->lasterror();
            $this->db->rollback();
            return -1;
        }
        else
        {
            if (! $notrigger)
            {
                $result=$this->call_trigger(strtoupper($element).'_DELETE_RESOURCE', $user);
                if ($result < 0) { $this->db->rollback(); return -1; }
            }
            $this->db->commit();
            return 1;
        }
	}


	/**
	 * Overwrite magic function to solve problem of cloning object that are kept as references
	 *
	 * @return void
	 */
	function __clone()
    {
        // Force a copy of this->lines, otherwise it will point to same object.
        if (isset($this->lines) && is_array($this->lines))
        {
        	$nboflines=count($this->lines);
        	for($i=0; $i < $nboflines; $i++)
        	{
            	$this->lines[$i] = clone $this->lines[$i];
        	}
        }
    }

	/**
	 * Common function for all objects extending CommonObject for generating documents
	 *
	 * @param 	string 		$modelspath 	Relative folder where generators are placed
	 * @param 	string 		$modele 		Generator to use. Caller must set it to obj->modelpdf or GETPOST('modelpdf') for example.
	 * @param 	Translate 	$outputlangs 	Language to use
	 * @param 	int 		$hidedetails 	1 to hide details. 0 by default
	 * @param 	int 		$hidedesc 		1 to hide product description. 0 by default
	 * @param 	int 		$hideref 		1 to hide product reference. 0 by default
	 * @param   null|array  $moreparams     Array to provide more information
	 * @return 	int 						>0 if OK, <0 if KO
	 */
	protected function commonGenerateDocument($modelspath, $modele, $outputlangs, $hidedetails, $hidedesc, $hideref, $moreparams=null)
	{
		global $conf, $langs;

		$srctemplatepath='';

		// Increase limit for PDF build
		$err=error_reporting();
		error_reporting(0);
		@set_time_limit(120);
		error_reporting($err);

		// If selected model is a filename template (then $modele="modelname" or "modelname:filename")
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
			foreach(array('doc','pdf') as $prefix)
			{
			    if (in_array(get_class($this), array('Adherent'))) $file = $prefix."_".$modele.".class.php";     // Member module use prefix_module.class.php
				else $file = $prefix."_".$modele.".modules.php";

				// On verifie l'emplacement du modele
				$file=dol_buildpath($reldir.$modelspath.$file,0);
				if (file_exists($file))
				{
					$filefound=1;
					$classname=$prefix.'_'.$modele;
					break;
				}
			}
			if ($filefound) break;
		}

		// If generator was found
		if ($filefound)
		{
			global $db;  // Required to solve a conception default in commonstickergenerator.class.php making an include of code using $db
		    
			require_once $file;

			$obj = new $classname($this->db);

			// If generator is ODT, we must have srctemplatepath defined, if not we set it.
			if ($obj->type == 'odt' && empty($srctemplatepath))
			{
				$varfortemplatedir=$obj->scandir;
				if ($varfortemplatedir && ! empty($conf->global->$varfortemplatedir))
				{
					$dirtoscan=$conf->global->$varfortemplatedir;

					$listoffiles=array();

					// Now we add first model found in directories scanned
	                $listofdir=explode(',',$dirtoscan);
	                foreach($listofdir as $key=>$tmpdir)
	                {
	                    $tmpdir=trim($tmpdir);
	                    $tmpdir=preg_replace('/DOL_DATA_ROOT/',DOL_DATA_ROOT,$tmpdir);
	                    if (! $tmpdir) { unset($listofdir[$key]); continue; }
	                    if (is_dir($tmpdir))
	                    {
	                        $tmpfiles=dol_dir_list($tmpdir,'files',0,'\.od(s|t)$','','name',SORT_ASC,0);
	                        if (count($tmpfiles)) $listoffiles=array_merge($listoffiles,$tmpfiles);
	                    }
	                }

	                if (count($listoffiles))
	                {
	                	foreach($listoffiles as $record)
	                    {
	                    	$srctemplatepath=$record['fullname'];
	                    	break;
	                    }
	                }
				}

				if (empty($srctemplatepath))
				{
					$this->error='ErrorGenerationAskedForOdtTemplateWithSrcFileNotDefined';
					return -1;
				}
			}

            if ($obj->type == 'odt' && ! empty($srctemplatepath))
            {
                if (! dol_is_file($srctemplatepath))
                {
                    $this->error='ErrorGenerationAskedForOdtTemplateWithSrcFileNotFound';
                    return -1;
                }
            }

			// We save charset_output to restore it because write_file can change it if needed for
			// output format that does not support UTF8.
			$sav_charset_output=$outputlangs->charset_output;

			if (in_array(get_class($this), array('Adherent'))) 
			{
			    $arrayofrecords = array();   // The write_file of templates of adherent class need this
			    $resultwritefile = $obj->write_file($this, $outputlangs, $srctemplatepath, 'member', 1, $moreparams);
			}
			else $resultwritefile = $obj->write_file($this, $outputlangs, $srctemplatepath, $hidedetails, $hidedesc, $hideref, $moreparams);

			if ($resultwritefile > 0)
			{
				$outputlangs->charset_output=$sav_charset_output;

				// We delete old preview
				require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
				dol_delete_preview($this);

				// Success in building document. We build meta file.
				dol_meta_create($this);

				return 1;
			}
			else
			{
				$outputlangs->charset_output=$sav_charset_output;
				dol_print_error($this->db, "Error generating document for ".__CLASS__.". Error: ".$obj->error, $obj->errors);
				return -1;
			}

		}
		else
		{
			$this->error=$langs->trans("Error")." ".$langs->trans("ErrorFileDoesNotExists",$file);
			dol_print_error('',$this->error);
			return -1;
		}
	}

	/**
	 *  Build thumb
	 *
	 *  @param      string	$file           Path file in UTF8 to original file to create thumbs from.
	 *	@return		void
	 */
	function addThumbs($file)
	{
		global $maxwidthsmall, $maxheightsmall, $maxwidthmini, $maxheightmini, $quality;

		require_once DOL_DOCUMENT_ROOT .'/core/lib/images.lib.php';		// This define also $maxwidthsmall, $quality, ...

		$file_osencoded=dol_osencode($file);
		if (file_exists($file_osencoded))
		{
			// Create small thumbs for company (Ratio is near 16/9)
	        // Used on logon for example
	        vignette($file_osencoded, $maxwidthsmall, $maxheightsmall, '_small', $quality);

	        // Create mini thumbs for company (Ratio is near 16/9)
	        // Used on menu or for setup page for example
	        vignette($file_osencoded, $maxwidthmini, $maxheightmini, '_mini', $quality);
		}
	}


	/* Functions common to commonobject and commonobjectline */

    /* For default values */

    /**
     * Return the default value to use for a field when showing the create form of object.
     * Return values in this order:
     * 1) If parameter is available into POST, we return it first.
     * 2) If not but an alternate value was provided as parameter of function, we return it.
     * 3) If not but a constant $conf->global->OBJECTELEMENT_FIELDNAME is set, we return it (It is better to use the dedicated table).
     * 4) Return value found into database (TODO No yet implemented)
     *
     * @param   string              $fieldname          Name of field
     * @param   string              $alternatevalue     Alternate value to use
     * @return  string|string[]                         Default value (can be an array if the GETPOST return an array)
     **/
	function getDefaultCreateValueFor($fieldname, $alternatevalue=null)
    {
        global $conf, $_POST;

        // If param here has been posted, we use this value first.
        if (isset($_POST[$fieldname])) return GETPOST($fieldname, 2);

        if (isset($alternatevalue)) return $alternatevalue;

        $newelement=$this->element;
        if ($newelement == 'facture') $newelement='invoice';
        if ($newelement == 'commande') $newelement='order';
        if (empty($newelement))
        {
            dol_syslog("Ask a default value using common method getDefaultCreateValueForField on an object with no property ->element defined. Return empty string.", LOG_WARNING);
            return '';
        }

        $keyforfieldname=strtoupper($newelement.'_DEFAULT_'.$fieldname);
        //var_dump($keyforfieldname);
        if (isset($conf->global->$keyforfieldname)) return $conf->global->$keyforfieldname;

        // TODO Ad here a scan into table llx_overwrite_default with a filter on $this->element and $fieldname

    }


	/* For triggers */


    /**
     * Call trigger based on this instance.
     * Some context information may also be provided into array property this->context.
     * NB: Error from trigger are stacked in interface->errors
     * NB2: If return code of triggers are < 0, action calling trigger should cancel all transaction.
     *
     * @param   string    $trigger_name   trigger's name to execute
     * @param   User      $user           Object user
     * @return  int                       Result of run_triggers
     */
    function call_trigger($trigger_name, $user)
    {
    	global $langs,$conf;

    	include_once DOL_DOCUMENT_ROOT . '/core/class/interfaces.class.php';
    	$interface=new Interfaces($this->db);
    	$result=$interface->run_triggers($trigger_name,$this,$user,$langs,$conf);

    	if ($result < 0)
    	{
    		if (!empty($this->errors))
    		{
    			$this->errors=array_unique(array_merge($this->errors,$interface->errors));   // We use array_unique because when a trigger call another trigger on same object, this->errors is added twice.
    		}
    		else
    		{
    			$this->errors=$interface->errors;
    		}
    	}
    	return $result;
    }


    /* Functions for extrafields */


    /**
     *  Function to get extra fields of a member into $this->array_options
     *  This method is in most cases called by method fetch of objects but you can call it separately.
     *
     *  @param	int		$rowid			Id of line
     *  @param  array	$optionsArray   Array resulting of call of extrafields->fetch_name_optionals_label()
     *  @return	int						<0 if error, 0 if no optionals to find nor found, 1 if a line is found and optional loaded
     */
    function fetch_optionals($rowid=null,$optionsArray=null)
    {
    	if (empty($rowid)) $rowid=$this->id;

        //To avoid SQL errors. Probably not the better solution though
        if (!$this->table_element) {
            return 0;
        }

        if (! is_array($optionsArray))
        {
            // optionsArray not already loaded, so we load it
            require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
            $extrafields = new ExtraFields($this->db);
            $optionsArray = $extrafields->fetch_name_optionals_label($this->table_element);
        }

        // Request to get complementary values
        if (count($optionsArray) > 0)
        {
            $sql = "SELECT rowid";
            foreach ($optionsArray as $name => $label)
            {
                $sql.= ", ".$name;
            }
            $sql.= " FROM ".MAIN_DB_PREFIX.$this->table_element."_extrafields";
            $sql.= " WHERE fk_object = ".$rowid;

            dol_syslog(get_class($this)."::fetch_optionals", LOG_DEBUG);
            $resql=$this->db->query($sql);
            if ($resql)
            {
            	$numrows=$this->db->num_rows($resql);
                if ($numrows)
                {
                    $tab = $this->db->fetch_array($resql);

                    foreach ($tab as $key => $value)
                    {
                    	// Test fetch_array ! is_int($key) because fetch_array result is a mix table with Key as alpha and Key as int (depend db engine)
                        if ($key != 'rowid' && $key != 'tms' && $key != 'fk_member' && ! is_int($key))
                        {
                            // we can add this attribute to object
                            $this->array_options["options_".$key]=$value;
                        }
                    }
                }

                $this->db->free($resql);

                if ($numrows) return $numrows;
                else return 0;
            }
            else
            {
                dol_print_error($this->db);
                return -1;
            }
        }
        return 0;
    }

    /**
     *	Delete all extra fields values for the current object.
     *
     *  @return	int		<0 if KO, >0 if OK
     */
	function deleteExtraFields()
	{
		$this->db->begin();

		$sql_del = "DELETE FROM ".MAIN_DB_PREFIX.$this->table_element."_extrafields WHERE fk_object = ".$this->id;
		dol_syslog(get_class($this)."::deleteExtraFields delete", LOG_DEBUG);
		$resql=$this->db->query($sql_del);
		if (! $resql)
		{
			$this->error=$this->db->lasterror();
			$this->db->rollback();
			return -1;
		}
		else
		{
			$this->db->commit();
			return 1;
		}
	}

    /**
     *	Add/Update all extra fields values for the current object.
     *  Data to describe values to insert/update are stored into $this->array_options=array('options_codeforfield1'=>'valueforfield1', 'options_codeforfield2'=>'valueforfield2', ...)
     *  This function delete record with all extrafields and insert them again from the array $this->array_options.
     *
     *  @return int -1=error, O=did nothing, 1=OK
     */
    function insertExtraFields()
    {
        global $conf,$langs;

		$error=0;

		if (! empty($conf->global->MAIN_EXTRAFIELDS_DISABLED)) return 0;	// For avoid conflicts if trigger used

        if (! empty($this->array_options))
        {
            // Check parameters
            $langs->load('admin');
            require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
            $extrafields = new ExtraFields($this->db);
            $target_extrafields=$extrafields->fetch_name_optionals_label($this->table_element);
            
            //Eliminate copied source object extra_fields that do not exist in target object
            $new_array_options=array();
            foreach ($this->array_options as $key => $value) {
                if (in_array(substr($key,8), array_keys($target_extrafields)))
                    $new_array_options[$key] = $value;
            }

            foreach($new_array_options as $key => $value)
            {
               	$attributeKey = substr($key,8);   // Remove 'options_' prefix
               	$attributeType  = $extrafields->attribute_type[$attributeKey];
               	$attributeLabel = $extrafields->attribute_label[$attributeKey];
               	$attributeParam = $extrafields->attribute_param[$attributeKey];
               	switch ($attributeType)
               	{
               		case 'int':
              			if (!is_numeric($value) && $value!='')
               			{
               				$this->errors[]=$langs->trans("ExtraFieldHasWrongValue",$attributeLabel);
               				return -1;
              			}
               			elseif ($value=='')
               			{
               				$this->array_options[$key] = null;
               			}
             			break;
            		case 'price':
            			$this->array_options[$key] = price2num($this->array_options[$key]);
            			break;
            		case 'date':
            			$this->array_options[$key]=$this->db->idate($this->array_options[$key]);
            			break;
            		case 'datetime':
            			$this->array_options[$key]=$this->db->idate($this->array_options[$key]);
            			break;
           			case 'link':
						$param_list=array_keys($attributeParam ['options']);
						// 0 : ObjectName
						// 1 : classPath
						$InfoFieldList = explode(":", $param_list[0]);
						dol_include_once($InfoFieldList[1]);
            			if ($InfoFieldList[0] && class_exists($InfoFieldList[0]))
            			{
    						$object = new $InfoFieldList[0]($this->db);
    						if ($value)
    						{
    							if (is_numeric($value)) $res=$object->fetch($value);
								else $res=$object->fetch('',$value);
								
    							if ($res > 0) $this->array_options[$key]=$object->id;
    							else
    							{
    							    $this->error="Ref '".$value."' for object '".$object->element."' not found";
                                    $this->db->rollback();
                                    return -1;
    							}
    						}
            			}
            			else
            			{
            			    dol_syslog('Error bad setup of extrafield', LOG_WARNING);
            			}
						break;
               	}
            }
            $this->db->begin();

            $sql_del = "DELETE FROM ".MAIN_DB_PREFIX.$this->table_element."_extrafields WHERE fk_object = ".$this->id;
            dol_syslog(get_class($this)."::insertExtraFields delete", LOG_DEBUG);
            $this->db->query($sql_del);

            $sql = "INSERT INTO ".MAIN_DB_PREFIX.$this->table_element."_extrafields (fk_object";
            foreach($new_array_options as $key => $value)
            {
            	$attributeKey = substr($key,8);   // Remove 'options_' prefix
                // Add field of attribut
            	if ($extrafields->attribute_type[$attributeKey] != 'separate') // Only for other type of separate
                	$sql.=",".$attributeKey;
            }
            $sql .= ") VALUES (".$this->id;
            foreach($new_array_options as $key => $value)
            {
            	$attributeKey = substr($key,8);   // Remove 'options_' prefix
                // Add field o fattribut
            	if($extrafields->attribute_type[$attributeKey] != 'separate') // Only for other type of separate)
            	{
	                if ($this->array_options[$key] != '')
	                {
	                    $sql.=",'".$this->db->escape($this->array_options[$key])."'";
	                }
	                else
	                {
	                    $sql.=",null";
	                }
            	}
            }
            $sql.=")";

            dol_syslog(get_class($this)."::insertExtraFields insert", LOG_DEBUG);
            $resql = $this->db->query($sql);
            if (! $resql)
            {
                $this->error=$this->db->lasterror();
                $this->db->rollback();
                return -1;
            }
            else
            {
                $this->db->commit();
                return 1;
            }
        }
        else return 0;
    }

    /**
     *	Update an exta field value for the current object.
     *  Data to describe values to insert/update are stored into $this->array_options=array('options_codeforfield1'=>'valueforfield1', 'options_codeforfield2'=>'valueforfield2', ...)
     *  This function delte record with all extrafields and insert them again from the array $this->array_options.
     *
     *  @param  string      $key    Key of the extrafield
     *  @return int                 -1=error, O=did nothing, 1=OK
     */
    function updateExtraField($key)
    {
        global $conf,$langs;

		$error=0;

		if (! empty($conf->global->MAIN_EXTRAFIELDS_DISABLED)) return 0;	// For avoid conflicts if trigger used

        if (! empty($this->array_options) && isset($this->array_options["options_".$key]))
        {
            // Check parameters
            $langs->load('admin');
            require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
            $extrafields = new ExtraFields($this->db);
            $target_extrafields=$extrafields->fetch_name_optionals_label($this->table_element);

            $value=$this->array_options["options_".$key];
            $attributeType  = $extrafields->attribute_type[$key];
            $attributeLabel = $extrafields->attribute_label[$key];
            $attributeParam = $extrafields->attribute_param[$key];
            switch ($attributeType)
            {
                case 'int':
                    if (!is_numeric($value) && $value!='')
                    {
                        $this->errors[]=$langs->trans("ExtraFieldHasWrongValue",$attributeLabel);
                        return -1;
                    }
                    elseif ($value=='')
                    {
                        $this->array_options["options_".$key] = null;
                    }
                    break;
                case 'price':
                    $this->array_options["options_".$key] = price2num($this->array_options["options_".$key]);
                    break;
                case 'date':
                    $this->array_options["options_".$key]=$this->db->idate($this->array_options["options_".$key]);
                    break;
                case 'datetime':
                    $this->array_options["options_".$key]=$this->db->idate($this->array_options["options_".$key]);
                    break;
                case 'link':
                    $param_list=array_keys($attributeParam ['options']);
                    // 0 : ObjectName
                    // 1 : classPath
                    $InfoFieldList = explode(":", $param_list[0]);
                    dol_include_once($InfoFieldList[1]);
                    $object = new $InfoFieldList[0]($this->db);
                    if ($value)
                    {
                        $object->fetch(0,$value);
                        $this->array_options["options_".$key]=$object->id;
                    }
                    break;
            }

            $this->db->begin();
            $sql = "UPDATE ".MAIN_DB_PREFIX.$this->table_element."_extrafields SET ".$key."='".$this->db->escape($this->array_options["options_".$key])."'";
            $sql .= " WHERE fk_object = ".$this->id;
            $resql = $this->db->query($sql);
            if (! $resql)
            {
                $this->error=$this->db->lasterror();
                $this->db->rollback();
                return -1;
            }
            else
            {
                $this->db->commit();
                return 1;
            }
        }
        else return 0;
    }

   /**
     * Function to show lines of extrafields with output datas
     *
	 * @param Extrafields   $extrafields    Extrafield Object
	 * @param string        $mode           Show output (view) or input (edit) for extrafield
	 * @param array         $params         Optional parameters
	 * @param string        $keyprefix      Prefix string to add into name and id of field (can be used to avoid duplicate names)
     *
     * @return string
     */
    function showOptionals($extrafields, $mode='view', $params=null, $keyprefix='')
    {
		global $_POST, $conf, $langs;

		$out = '';

		if (count($extrafields->attribute_label) > 0)
		{
			$out .= "\n";
			$out .= '<!-- showOptionalsInput --> ';
			$out .= "\n";

			$e = 0;
			foreach($extrafields->attribute_label as $key=>$label)
			{
				if (is_array($params) && count($params)>0) {
					if (array_key_exists('colspan',$params)) {
						$colspan=$params['colspan'];
					}
				}else {
					$colspan='3';
				}
				switch($mode) {
					case "view":
						$value=$this->array_options["options_".$key];
						break;
					case "edit":
						if (isset($_POST["options_" . $key])) {
							if (is_array($_POST["options_" . $key])) {
								// $_POST["options"] is an array but following code expects a comma separated string
								$value = implode(",", $_POST["options_" . $key]);
							} else {
								$value = $_POST["options_" . $key];
							}
						} else {
							$value = $this->array_options["options_" . $key];
						}
						break;
				}
				if ($extrafields->attribute_type[$key] == 'separate')
				{
					$out .= $extrafields->showSeparator($key);
				}
				else
				{
					$csstyle='';
					$class=(!empty($extrafields->attribute_hidden[$key]) ? 'class="hideobject" ' : '');
					if (is_array($params) && count($params)>0) {
						if (array_key_exists('style',$params)) {
							$csstyle=$params['style'];
						}
					}
					if ( !empty($conf->global->MAIN_EXTRAFIELDS_USE_TWO_COLUMS) && ($e % 2) == 0)
					{
						$out .= '<tr '.$class.$csstyle.' class="'.$this->element.'_extras_'.$key.'">';
						$colspan='0';
					}
					else
					{
						$out .= '<tr '.$class.$csstyle.' class="'.$this->element.'_extras_'.$key.'">';
					}
					// Convert date into timestamp format
					if (in_array($extrafields->attribute_type[$key],array('date','datetime')))
					{
						$value = isset($_POST["options_".$key])?dol_mktime($_POST["options_".$key."hour"], $_POST["options_".$key."min"], 0, $_POST["options_".$key."month"], $_POST["options_".$key."day"], $_POST["options_".$key."year"]):$this->db->jdate($this->array_options['options_'.$key]);
					}

					if($extrafields->attribute_required[$key])
						$label = '<span'.($mode != 'view' ? ' class="fieldrequired"':'').'>'.$label.'</span>';

					$out .= '<td>'.$langs->trans($label).'</td>';
					$html_id = !empty($this->id) ? $this->element.'_extras_'.$key.'_'.$this->id : '';
					$out .='<td id="'.$html_id.'" class="'.$this->element.'_extras_'.$key.'" '.($colspan?' colspan="'.$colspan.'"':'').'>';

					switch($mode) {
    					case "view":
    						$out .= $extrafields->showOutputField($key, $value);
    						break;
    					case "edit":
    						$out .= $extrafields->showInputField($key, $value, '', $keyprefix, '', 0, $this->id);
    						break;
					}

					$out .= '</td>';

					if (! empty($conf->global->MAIN_EXTRAFIELDS_USE_TWO_COLUMS) && (($e % 2) == 1)) $out .= '</tr>';
					else $out .= '</tr>';
					$e++;
				}
			}
			$out .= "\n";
			// Add code to manage list depending on others
			if (! empty($conf->use_javascript_ajax))
			$out .= '
				<script type="text/javascript">
				    jQuery(document).ready(function() {
				    	function showOptions(child_list, parent_list)
				    	{
				    		var val = $("select[name=\"options_"+parent_list+"\"]").val();
				    		var parentVal = parent_list + ":" + val;
							if(val > 0) {
					    		$("select[name=\""+child_list+"\"] option[parent]").hide();
					    		$("select[name=\""+child_list+"\"] option[parent=\""+parentVal+"\"]").show();
							} else {
								$("select[name=\""+child_list+"\"] option").show();
							}
				    	}
						function setListDependencies() {
					    	jQuery("select option[parent]").parent().each(function() {
					    		var child_list = $(this).attr("name");
								var parent = $(this).find("option[parent]:first").attr("parent");
								var infos = parent.split(":");
								var parent_list = infos[0];
								$("select[name=\"options_"+parent_list+"\"]").change(function() {
									showOptions(child_list, parent_list);
								});
					    	});
						}

						setListDependencies();
				    });
				</script>'."\n";
			$out .= '<!-- /showOptionalsInput --> '."\n";
		}
		return $out;
	}

	/**
	 * Returns the rights used for this class
	 * @return stdClass
	 */
	public function getRights()
	{
		global $user;

		$element = $this->element;
		if ($element == 'facturerec') $element='facture';

		return $user->rights->{$element};
	}

	/**
	 * Function used to replace a thirdparty id with another one.
	 * This function is meant to be called from replaceThirdparty with the appropiate tables
	 * Column name fk_soc MUST be used to identify thirdparties
	 *
	 * @param  DoliDB 	   $db 			  Database handler
	 * @param  int 		   $origin_id     Old thirdparty id (the thirdparty to delete)
	 * @param  int 		   $dest_id       New thirdparty id (the thirdparty that will received element of the other)
	 * @param  string[]    $tables        Tables that need to be changed
	 * @param  int         $ignoreerrors  Ignore errors. Return true even if errors. We need this when replacement can fails like for categories (categorie of old thirdparty may already exists on new one)
	 * @return bool
	 */
	public static function commonReplaceThirdparty(DoliDB $db, $origin_id, $dest_id, array $tables, $ignoreerrors=0)
	{
		foreach ($tables as $table)
		{
			$sql = 'UPDATE '.MAIN_DB_PREFIX.$table.' SET fk_soc = '.$dest_id.' WHERE fk_soc = '.$origin_id;

			if (! $db->query($sql))
			{
			    if ($ignoreerrors) return true;		// TODO Not enough. If there is A-B on kept thirdarty and B-C on old one, we must get A-B-C after merge. Not A-B.
				//$this->errors = $db->lasterror();
			    return false;
			}
		}

		return true;
	}

	/**
	 * Get buy price to use for margin calculation. This function is called when buy price is unknown.
	 *	set buy price = sell price if ForceBuyingPriceIfNull configured,
	 *   else if calculation MARGIN_TYPE = 'costprice' and costprice is defined, use costprice as buyprice
	 *	 else if calculation MARGIN_TYPE = 'pmp' and pmp is calculated, use pmp as buyprice
	 *	 else set min buy price as buy price
	 *
	 * @param float		$unitPrice		 product unit price
	 * @param float		$discountPercent line discount percent
	 * @param int		$fk_product		 product id
	 *
	 * @return	float <0 if ko, buyprice if ok
	 */
	public function defineBuyPrice($unitPrice = 0, $discountPercent = 0, $fk_product = 0)
	{
		global $conf;

		$buyPrice = 0;

		if (($unitPrice > 0) && (isset($conf->global->ForceBuyingPriceIfNull) && $conf->global->ForceBuyingPriceIfNull == 1)) // In most cases, test here is false
		{
			$buyPrice = $unitPrice * (1 - $discountPercent / 100);
		}
		else
		{
			// Get cost price for margin calculation
			if (! empty($fk_product))
			{
				if (isset($conf->global->MARGIN_TYPE) && $conf->global->MARGIN_TYPE == 'costprice')
				{
					require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
					$product = new Product($this->db);
					$result = $product->fetch($fk_product);
					if ($result <= 0)
					{
						$this->errors[] = 'ErrorProductIdDoesNotExists';
						return -1;
					}
					if ($product->cost_price > 0)
					{
						$buyPrice = $product->cost_price;
					}
					else if ($product->pmp > 0)
					{
						$buyPrice = $product->pmp;
					}
				}
				else if (isset($conf->global->MARGIN_TYPE) && $conf->global->MARGIN_TYPE == 'pmp')
				{
					require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
					$product = new Product($this->db);
					$result = $product->fetch($fk_product);
					if ($result <= 0)
					{
						$this->errors[] = 'ErrorProductIdDoesNotExists';
						return -1;
					}
					if ($product->pmp > 0)
					{
						$buyPrice = $product->pmp;
					}
				}

				if (empty($buyPrice) && isset($conf->global->MARGIN_TYPE) && in_array($conf->global->MARGIN_TYPE, array('1','pmp','costprice')))
				{
					require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.product.class.php';
					$productFournisseur = new ProductFournisseur($this->db);
					if (($result = $productFournisseur->find_min_price_product_fournisseur($fk_product)) > 0)
					{
						$buyPrice = $productFournisseur->fourn_unitprice;
					}
					else if ($result < 0)
					{
						$this->errors[] = $productFournisseur->error;
						return -2;
					}
				}
			}
		}
		return $buyPrice;
	}
}
