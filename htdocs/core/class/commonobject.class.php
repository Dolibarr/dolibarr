<?php
/* Copyright (C) 2006-2013 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2013 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2010-2013 Juanjo Menent        <jmenent@2byte.es>
 * Copyright (C) 2012      Christophe Battarel  <christophe.battarel@altairis.fr>
 * Copyright (C) 2010-2014 Juanjo Menent        <jmenent@2byte.es>
 * Copyright (C) 2012-2013 Christophe Battarel  <christophe.battarel@altairis.fr>
 * Copyright (C) 2011-2014 Philippe Grand	    <philippe.grand@atoo-net.com>
 * Copyright (C) 2012-2014 Marcos García        <marcosgdf@gmail.com>
 * Copyright (C) 2012-2014 Raphaël Doursenaud   <rdoursenaud@gpcsolutions.fr>
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
     * @var string 		Error string
     * @deprecated		Use instead the array of error strings
     */
    public $error;

    /**
     * @var string[]	Array of error strings
     */
    public $errors;

    /**
     * @var string		Can be used to pass information when only object is provied to method
     */
    public $context=array();

    /**
     * @var string		Contains canvas name if record is an alternative canvas record
     */
    public $canvas;

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



    // Following var are used by some objects only. We keep this property here in CommonObject to be able to provide common method using them.

    public $name;
    public $lastname;
    public $firstname;
    public $civility_id;
    public $thirdparty;

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
    	global $db;

		$sql = "SELECT rowid, ref, ref_ext";
		$sql.= " FROM ".MAIN_DB_PREFIX.$element;
		if ($id > 0) $sql.= " WHERE rowid = ".$db->escape($id);
		else if ($ref) $sql.= " WHERE ref = '".$db->escape($ref)."'";
		else if ($ref_ext) $sql.= " WHERE ref_ext = '".$db->escape($ref_ext)."'";
		else {
			$error='ErrorWrongParameters';
			dol_print_error(get_class()."::isExistingObject ".$error, LOG_ERR);
			return -1;
		}

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
    	return $this->error.(is_array($this->errors)?(($this->error!=''?' ':'').join(',',$this->errors)):'');
    }

    /**
     *	Return full name (civility+' '+name+' '+lastname)
     *
     *	@param	Translate	$langs			Language object for translation of civility
     *	@param	int			$option			0=No option, 1=Add civility
     * 	@param	int			$nameorder		-1=Auto, 0=Lastname+Firstname, 1=Firstname+Lastname
     * 	@param	int			$maxlen			Maximum length
     * 	@return	string						String with full name
     */
    function getFullName($langs,$option=0,$nameorder=-1,$maxlen=0)
    {
        global $conf;

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
     *  Add a link between element $this->element and a contact
     *
     *  @param	int		$fk_socpeople       Id of thirdparty contact (if source = 'external') or id of user (if souce = 'internal') to link
     *  @param 	int		$type_contact 		Type of contact (code or id). Must be if or code found into table llx_c_type_contact. For example: SALESREPFOLL
     *  @param  int		$source             external=Contact extern (llx_socpeople), internal=Contact intern (llx_user)
     *  @param  int		$notrigger			Disable all triggers
     *  @return int                 		<0 if KO, >0 if OK
     */
    function add_contact($fk_socpeople, $type_contact, $source='external',$notrigger=0)
    {
        global $user,$conf,$langs;


        dol_syslog(get_class($this)."::add_contact $fk_socpeople, $type_contact, $source");

        // Check parameters
        if ($fk_socpeople <= 0)
        {
            $this->error=$langs->trans("ErrorWrongValueForParameter","1");
            dol_syslog(get_class($this)."::add_contact ".$this->error,LOG_ERR);
            return -1;
        }
        if (! $type_contact)
        {
            $this->error=$langs->trans("ErrorWrongValueForParameter","2");
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
                $id_type_contact=$obj->rowid;
            }
        }

        $datecreate = dol_now();

        $this->db->begin();

        // Insertion dans la base
        $sql = "INSERT INTO ".MAIN_DB_PREFIX."element_contact";
        $sql.= " (element_id, fk_socpeople, datecreate, statut, fk_c_type_contact) ";
        $sql.= " VALUES (".$this->id.", ".$fk_socpeople." , " ;
        $sql.= "'".$this->db->idate($datecreate)."'";
        $sql.= ", 4, '". $id_type_contact . "' ";
        $sql.= ")";
        dol_syslog(get_class($this)."::add_contact", LOG_DEBUG);

        $resql=$this->db->query($sql);
        if ($resql)
        {
            if (! $notrigger)
            {
            	$result=$this->call_trigger(strtoupper($this->element).'_ADD_CONTACT', $user);
	            if ($result < 0) { $this->db->rollback(); return -1; }
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
        global $user,$langs,$conf;

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
        global $user,$langs,$conf;


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
     *    @param	int			$statut		Status of lines to get (-1=all)
     *    @param	string		$source		Source of contact: external or thirdparty (llx_socpeople) or internal (llx_user)
     *    @param	int         $list       0:Return array contains all properties, 1:Return array contains just id
     *    @return	array		            Array of contacts
     */
    function liste_contact($statut=-1,$source='external',$list=0)
    {
        global $langs;

        $tab=array();

        $sql = "SELECT ec.rowid, ec.statut, ec.fk_socpeople as id, ec.fk_c_type_contact";    // This field contains id of llx_socpeople or id of llx_user
        if ($source == 'internal') $sql.=", '-1' as socid";
        if ($source == 'external' || $source == 'thirdparty') $sql.=", t.fk_soc as socid";
        $sql.= ", t.civility as civility, t.lastname as lastname, t.firstname, t.email";
        $sql.= ", tc.source, tc.element, tc.code, tc.libelle";
        $sql.= " FROM ".MAIN_DB_PREFIX."c_type_contact tc";
        $sql.= ", ".MAIN_DB_PREFIX."element_contact ec";
        if ($source == 'internal') $sql.=" LEFT JOIN ".MAIN_DB_PREFIX."user t on ec.fk_socpeople = t.rowid";
        if ($source == 'external'|| $source == 'thirdparty') $sql.=" LEFT JOIN ".MAIN_DB_PREFIX."socpeople t on ec.fk_socpeople = t.rowid";
        $sql.= " WHERE ec.element_id =".$this->id;
        $sql.= " AND ec.fk_c_type_contact=tc.rowid";
        $sql.= " AND tc.element='".$this->element."'";
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
					               'civility'=>$obj->civility, 'lastname'=>$obj->lastname, 'firstname'=>$obj->firstname, 'email'=>$obj->email,
					               'rowid'=>$obj->rowid,'code'=>$obj->code,'libelle'=>$libelle_type,'status'=>$obj->statut, 'fk_c_type_contact' => $obj->fk_c_type_contact);
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
            $this->error=$this->db->error();
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
     *      @param	string	$order		Sort order by : 'code' or 'rowid'
     *      @param  string	$option     0=Return array id->label, 1=Return array code->label
     *      @param  string	$activeonly 0=all status of contact, 1=only the active
     *		@param	string	$code		Type of contact (Example: 'CUSTOMER', 'SERVICE')
     *      @return array       		Array list of type of contacts (id->label if option=0, code->label if option=1)
     */
    function liste_type_contact($source='internal', $order='', $option=0, $activeonly=0, $code='')
    {
        global $langs;

        if (empty($order)) $order='code';

        $tab = array();
        $sql = "SELECT DISTINCT tc.rowid, tc.code, tc.libelle";
        $sql.= " FROM ".MAIN_DB_PREFIX."c_type_contact as tc";
        $sql.= " WHERE tc.element='".$this->element."'";
        if ($activeonly == 1) $sql.= " AND tc.active=1"; // only the active type
        if (! empty($source) && $source != 'all') $sql.= " AND tc.source='".$source."'";
        if (! empty($code)) $sql.= " AND tc.code='".$code."'";
        $sql.= " ORDER by tc.".$order;

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
     *		Charge le contact d'id $id dans this->contact
     *
     *		@param	int		$contactid      Id du contact. Use this->contactid if empty.
     *		@return	int						<0 if KO, >0 if OK
     */
    function fetch_contact($contactid='')
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

        if (empty($this->socid) && empty($this->fk_soc) && empty($this->fk_thirdparty) && empty($force_thirdparty_id)) return 0;

	    require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';

	    $idtofetch=isset($this->socid)?$this->socid:(isset($this->fk_soc)?$this->fk_soc:$this->fk_thirdparty);
		if ($force_thirdparty_id) $idtofetch=$force_thirdparty_id;

        $thirdparty = new Societe($this->db);
        $result=$thirdparty->fetch($idtofetch);
        $this->client = $thirdparty;  // deprecated
        $this->thirdparty = $thirdparty;

        // Use first price level if level not defined for third party
        if (! empty($conf->global->PRODUIT_MULTIPRICES) && empty($this->thirdparty->price_level))
        {
            $this->client->price_level=1; // deprecated
            $this->thirdparty->price_level=1;
        }

        return $result;
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
        else return 0;
    }

    /**
     *		Charge le projet d'id $this->fk_project dans this->projet
     *
     *		@return		int			<0 if KO, >=0 if OK
     */
    function fetch_projet()
    {
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
        // TODO uniformise code
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
     *	Load value from specific field
     *
     *	@param	string	$table		Table of element or element line
     *	@param	int		$id			Element id
     *	@param	string	$field		Field selected
     *	@return	int					<0 if KO, >0 if OK
     */
    function getValueFrom($table, $id, $field)
    {
        $result=false;

        $sql = "SELECT ".$field." FROM ".MAIN_DB_PREFIX.$table;
        $sql.= " WHERE rowid = ".$id;

        dol_syslog(get_class($this).'::getValueFrom', LOG_DEBUG);
        $resql = $this->db->query($sql);
        if ($resql)
        {
            $row = $this->db->fetch_row($resql);
            $result = $row[0];
        }

        return $result;
    }

    /**
     *	Update a specific field into database
     *
     *	@param	string	$field		Field to update
     *	@param	mixed	$value		New value
     *	@param	string	$table		To force other table element or element line (should not be used)
     *	@param	int		$id			To force other object id (should not be used)
     *	@param	string	$format		Data format ('text', 'date'). 'text' is used if not defined
     *	@param	string	$id_field	To force rowid field name. 'rowid' is used it not defined
     *	@param	string	$user		Update last update fields also if user object provided
     *	@return	int					<0 if KO, >0 if OK
     */
    function setValueFrom($field, $value, $table='', $id='', $format='', $id_field='', $user='')
    {
        global $conf;

        if (empty($table)) 	$table=$this->table_element;
        if (empty($id))    	$id=$this->id;
		if (empty($format)) 	$format='text';
		if (empty($id_field)) 	$id_field='rowid';

        $this->db->begin();

        $sql = "UPDATE ".MAIN_DB_PREFIX.$table." SET ";
        if ($format == 'text') $sql.= $field." = '".$this->db->escape($value)."'";
        else if ($format == 'date') $sql.= $field." = '".$this->db->idate($value)."'";
        if (is_object($user)) $sql.=", fk_user_modif = ".$user->id;
        $sql.= " WHERE ".$id_field." = ".$id;

        dol_syslog(get_class($this)."::".__FUNCTION__."", LOG_DEBUG);
        $resql = $this->db->query($sql);
        if ($resql)
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

    /**
     *      Load properties id_previous and id_next
     *
     *      @param	string	$filter		Optional filter
     *	 	@param  int		$fieldid   	Name of field to use for the select MAX and MIN
     *		@param	int		$nodbprefix	Do not include DB prefix to forge table name
     *      @return int         		<0 if KO, >0 if OK
     */
    function load_previous_next_ref($filter,$fieldid,$nodbprefix=0)
    {
        global $conf, $user;

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
        $sql.= " WHERE te.".$fieldid." < '".$this->db->escape($this->ref)."'";
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
        $sql.= " WHERE te.".$fieldid." > '".$this->db->escape($this->ref)."'";
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

    /**
     *  Save a new position (field rang) for details lines.
     *  You can choose to set position for lines with already a position or lines without any position defined.
     *
     * 	@param		boolean		$renum				true to renum all already ordered lines, false to renum only not already ordered lines.
     * 	@param		string		$rowidorder			ASC or DESC
     * 	@param		boolean		$fk_parent_line		Table with fk_parent_line field or not
     * 	@return		void
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
    		dol_syslog(get_class($this)."::upate_note Parameter suffix must be empty, '_private' or '_public'", LOG_ERR);
			return -2;
		}

    	$sql = 'UPDATE '.MAIN_DB_PREFIX.$this->table_element;
    	$sql.= " SET note".$suffix." = ".(!empty($note)?("'".$this->db->escape($note)."'"):"NULL");
    	$sql.= " WHERE rowid =". $this->id;

    	dol_syslog(get_class($this)."::update_note", LOG_DEBUG);
    	if ($this->db->query($sql))
    	{
    		if ($suffix == '_public') $this->note_public = $note;
    		else if ($suffix == '_private') $this->note_private = $note;
    		else $this->note = $note;
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
     *  @param      string		$note		New value for note
     *  @return     int      		   		<0 if KO, >0 if OK
     *  @deprecated
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
     *  @param  string	$roundingadjust    	'none'=Do nothing, 'auto'=Use default method (MAIN_ROUNDOFTOTAL_NOT_TOTALOFROUND if defined, or '0'), '0'=Force use total of rounding, '1'=Force use rounding of total
     *  @param	int		$nodatabaseupdate	1=Do not update database. Update only properties of object.
     *  @param	Societe	$seller				If roundingadjust is '0' or '1', it means we recalculate total for lines before calculating total for object. For this, we need seller object.
     *	@return	int    			           	<0 if KO, >0 if OK
     */
    function update_price($exclspec=0,$roundingadjust='none',$nodatabaseupdate=0,$seller='')
    {
    	global $conf;

        include_once DOL_DOCUMENT_ROOT.'/core/lib/price.lib.php';

        if ($roundingadjust == '-1') $roundingadjust='auto';	// For backward compatibility

        $forcedroundingmode=$roundingadjust;
        if ($forcedroundingmode == 'auto' && isset($conf->global->MAIN_ROUNDOFTOTAL_NOT_TOTALOFROUND)) $forcedroundingmode=$conf->global->MAIN_ROUNDOFTOTAL_NOT_TOTALOFROUND;
        if ($forcedroundingmode == 'auto') $forcedroundingmode='0';

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

            $num = $this->db->num_rows($resql);
            $i = 0;
            while ($i < $num)
            {
                $obj = $this->db->fetch_object($resql);

                // Note: There is no check on detail line and no check on total, if $forcedroundingmode = 'none'

                if ($forcedroundingmode == '0')	// Check if data on line are consistent. This may solve lines that were not consistent because set with $forcedroundingmode='auto'
                {
                	$localtax_array=array($obj->localtax1_type,$obj->localtax1_tx,$obj->localtax2_type,$obj->localtax2_tx);
                	$tmpcal=calcul_price_total($obj->qty, $obj->up, $obj->remise_percent, $obj->vatrate, $obj->localtax1_tx, $obj->localtax2_tx, 0, 'HT', $obj->info_bits, $obj->product_type, $seller, $localtax_array);
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
            if ($this->element == 'askpricesupplier')                                      $fieldttc='total';

            if (empty($nodatabaseupdate))
            {
                $sql = 'UPDATE '.MAIN_DB_PREFIX.$this->table_element.' SET';
                $sql .= " ".$fieldht."='".price2num($this->total_ht)."',";
                $sql .= " ".$fieldtva."='".price2num($this->total_tva)."',";
                $sql .= " ".$fieldlocaltax1."='".price2num($this->total_localtax1)."',";
                $sql .= " ".$fieldlocaltax2."='".price2num($this->total_localtax2)."',";
                $sql .= " ".$fieldttc."='".price2num($this->total_ttc)."'";
                $sql .= ' WHERE rowid = '.$this->id;

                //print "xx".$sql;
                dol_syslog(get_class($this)."::update_price", LOG_DEBUG);
                $resql=$this->db->query($sql);
                if (! $resql)
                {
                    $error++;
                    $this->error=$this->db->lasterror();
                    $this->error[]=$this->db->lasterror();
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
     *
     *	@param	int		$sourceid		Object source id
     *	@param  string	$sourcetype		Object source type
     *	@param  int		$targetid		Object target id
     *	@param  string	$targettype		Object target type
     *	@param  string	$clause			'OR' or 'AND' clause used when both source id and target id are provided
     *	@return	void
     *  @see	add_object_linked, updateObjectLinked, deleteObjectLinked
     */
	function fetchObjectLinked($sourceid='',$sourcetype='',$targetid='',$targettype='',$clause='OR')
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
        	$justsource=true;
        	if (! empty($targettype)) $withtargettype=true;
        }
        if (! empty($targetid) && ! empty($targettype) && empty($sourceid))
        {
        	$justtarget=true;
        	if (! empty($sourcetype)) $withsourcetype=true;
        }

        $sourceid = (! empty($sourceid) ? $sourceid : $this->id);
        $targetid = (! empty($targetid) ? $targetid : $this->id);
        $sourcetype = (! empty($sourcetype) ? $sourcetype : $this->element);
        $targettype = (! empty($targettype) ? $targettype : $this->element);

        if (empty($sourceid) && empty($targetid))
        {
        	dol_syslog('Bad usage of function. No source nor target id defined (nor as parameter nor as object id)', LOG_ERROR);
        	return -1;
        }

        // Links beetween objects are stored in this table
        $sql = 'SELECT fk_source, sourcetype, fk_target, targettype';
        $sql.= ' FROM '.MAIN_DB_PREFIX.'element_element';
        $sql.= " WHERE ";
        if ($justsource || $justtarget)
        {
            if ($justsource)
            {
            	$sql.= "fk_source = '".$sourceid."' AND sourcetype = '".$sourcetype."'";
            	if ($withtargettype) $sql.= " AND targettype = '".$targettype."'";
            }
            else if ($justtarget)
            {
            	$sql.= "fk_target = '".$targetid."' AND targettype = '".$targettype."'";
            	if ($withsourcetype) $sql.= " AND sourcetype = '".$sourcetype."'";
            }
        }
        else
		{
            $sql.= "(fk_source = '".$sourceid."' AND sourcetype = '".$sourcetype."')";
            $sql.= " ".$clause." (fk_target = '".$targetid."' AND targettype = '".$targettype."')";
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
                if ($obj->fk_source == $sourceid)
                {
                    $this->linkedObjectsIds[$obj->targettype][]=$obj->fk_target;
                }
                if ($obj->fk_target == $targetid)
                {
                    $this->linkedObjectsIds[$obj->sourcetype][]=$obj->fk_source;
                }
                $i++;
            }

            if (! empty($this->linkedObjectsIds))
            {
                foreach($this->linkedObjectsIds as $objecttype => $objectids)
                {
                    // Parse element/subelement (ex: project_task)
                    $module = $element = $subelement = $objecttype;
                    if ($objecttype != 'order_supplier' && $objecttype != 'invoice_supplier' && preg_match('/^([^_]+)_([^_]+)/i',$objecttype,$regs))
                    {
                        $module = $element = $regs[1];
                        $subelement = $regs[2];
                    }

                    $classpath = $element.'/class';

                    // To work with non standard path
                    if ($objecttype == 'facture')			{
                        $classpath = 'compta/facture/class';
                    }
                    else if ($objecttype == 'propal')			{
                        $classpath = 'comm/propal/class';
                    }
                    else if ($objecttype == 'askpricesupplier')			{
                        $classpath = 'comm/askpricesupplier/class';
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

                    // TODO ajout temporaire - MAXIME MANGIN
                    else if ($objecttype == 'contratabonnement')	{
                        $classpath = 'contrat/class'; $subelement = 'contrat'; $module = 'contratabonnement';
                    }

                    $classfile = strtolower($subelement); $classname = ucfirst($subelement);
                    if ($objecttype == 'invoice_supplier') {
                        $classfile = 'fournisseur.facture'; $classname = 'FactureFournisseur';
                    }
                    else if ($objecttype == 'order_supplier')   {
                        $classfile = 'fournisseur.commande'; $classname = 'CommandeFournisseur';
                    }

                    if ($conf->$module->enabled && $element != $this->element)
                    {
                        dol_include_once('/'.$classpath.'/'.$classfile.'.class.php');

                        $num=count($objectids);

                        for ($i=0;$i<$num;$i++)
                        {
                            $object = new $classname($this->db);
                            $ret = $object->fetch($objectids[$i]);
                            if ($ret >= 0)
                            {
                                $this->linkedObjects[$objecttype][$i] = $object;
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
    function updateObjectLinked($sourceid='', $sourcetype='', $targetid='', $targettype='')
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
	 *	@return     int	>0 if OK, <0 if KO
	 *	@see	add_object_linked, updateObjectLinked, fetchObjectLinked
	 */
	function deleteObjectLinked($sourceid='', $sourcetype='', $targetid='', $targettype='')
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

		dol_syslog(get_class($this)."::deleteObjectLinked", LOG_DEBUG);
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
     *      Set status of an object
     *
     *      @param	int		$status			Status to set
     *      @param	int		$elementId		Id of element to force (use this->id by default)
     *      @param	string	$elementType	Type of element to force (use this->table_element by default)
     *      @return int						<0 if KO, >0 if OK
     */
    function setStatut($status,$elementId='',$elementType='')
    {
    	global $user,$langs,$conf;

        $elementId = (!empty($elementId)?$elementId:$this->id);
        $elementTable = (!empty($elementType)?$elementType:$this->table_element);

        $this->db->begin();

        $fieldstatus="fk_statut";
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
        		$this->statut = $status;
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
     *  @param	int		$id			Id of object
     *  @return	int					<0 if KO, 0 if not used, >0 if already used
     */
    function isObjectUsed($id)
    {
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
            $nb=0;
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
            	$discount_percent_line = $obj->remise_percent;
            	$total_ht = $obj->total_ht;

        		$total_discount_line = price2num(($pu_ht * $qty) - $total_ht, 'MT');
        		$total_discount += $total_discount_line;

        		$i++;
        	}
        }

        //print $total_discount; exit;
        return price2num($total_discount);
    }

    /**
     *	Set extra parameters
     *
     *	@return	void
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
     *
     *    @return	string	incoterms info
     */
    function display_incoterms()
    {
        $out = '';
		$this->incoterms_libelle = '';
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

		$out .= ' - '.$this->location_incoterms;

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
			$res = $this->db->fetch_object($resql);
			return 'Incoterm : '.$res->code.' - '.$this->location_incoterms;
		}
		else
		{
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
			$sql.= ", location_incoterms = '".($id_incoterm > 0 ? $this->db->escape($location) : "null")."'";
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
                return -1;
            }
        }
        else return -1;
    }


    /**
     *  Return if a country is inside the EEC (European Economic Community)
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

    /* This is to show linked object block */

    /**
     *  Show linked object block
     *  TODO Move this into html.class.php
     *  But for the moment we don't know if it's possible as we keep a method available on overloaded objects.
     *
     *  @return	int
     */
    function showLinkedObjectBlock()
    {
        global $conf,$langs,$hookmanager;
        global $bc;

        $this->fetchObjectLinked();

        // Bypass the default method
        $hookmanager->initHooks(array('commonobject'));
        $parameters=array();
        $reshook=$hookmanager->executeHooks('showLinkedObjectBlock',$parameters,$this,$action);    // Note that $action and $object may have been modified by hook

        if (empty($reshook))
        {
        	$num = count($this->linkedObjects);

        	foreach($this->linkedObjects as $objecttype => $objects)
        	{
        		$tplpath = $element = $subelement = $objecttype;

        		if (preg_match('/^([^_]+)_([^_]+)/i',$objecttype,$regs))
        		{
        			$element = $regs[1];
        			$subelement = $regs[2];
        			$tplpath = $element.'/'.$subelement;
        		}

        		// To work with non standard path
        		if ($objecttype == 'facture')          {
        			$tplpath = 'compta/'.$element;
        			if (empty($conf->facture->enabled)) continue;	// Do not show if module disabled
        		}
        		else if ($objecttype == 'propal')           {
        			$tplpath = 'comm/'.$element;
        			if (empty($conf->propal->enabled)) continue;	// Do not show if module disabled
        		}
        		else if ($objecttype == 'askpricesupplier')           {
        			$tplpath = 'comm/'.$element;
        			if (empty($conf->askpricesupplier->enabled)) continue;	// Do not show if module disabled
        		}
        		else if ($objecttype == 'shipping' || $objecttype == 'shipment') {
        			$tplpath = 'expedition';
        			if (empty($conf->expedition->enabled)) continue;	// Do not show if module disabled
        		}
        		else if ($objecttype == 'delivery')         {
        			$tplpath = 'livraison';
        			if (empty($conf->expedition->enabled)) continue;	// Do not show if module disabled
        		}
        		else if ($objecttype == 'invoice_supplier') {
        			$tplpath = 'fourn/facture';
        		}
        		else if ($objecttype == 'order_supplier')   {
        			$tplpath = 'fourn/commande';
        		}

        		global $linkedObjectBlock;
        		$linkedObjectBlock = $objects;

        		// Output template part (modules that overwrite templates must declare this into descriptor)
        		$dirtpls=array_merge($conf->modules_parts['tpl'],array('/'.$tplpath.'/tpl'));
        		foreach($dirtpls as $reldir)
        		{
        			$res=@include dol_buildpath($reldir.'/linkedobjectblock.tpl.php');
        			if ($res) break;
        		}
        	}

        	return $num;
        }
    }



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
	 *	@param	string		$selected		   	Object line selected
	 *	@param  int	    	$dateSelector      	1=Show also date range input fields
	 *	@return	void
	 */
	function printObjectLines($action, $seller, $buyer, $selected=0, $dateSelector=0)
	{
		global $conf, $hookmanager, $inputalsopricewithtax, $usemargins, $langs, $user;

		// Define usemargins
		$usemargins=0;
		if (! empty($conf->margin->enabled) && ! empty($this->element) && in_array($this->element,array('facture','propal','commande'))) $usemargins=1;

		print '<tr class="liste_titre nodrag nodrop">';

		if (! empty($conf->global->MAIN_VIEW_LINE_NUMBER)) print '<td align="center" width="5">&nbsp;</td>';

		// Description
		print '<td>'.$langs->trans('Description').'</td>';

		if ($this->element == 'askpricesupplier')
		{
			print '<td align="right"><span id="title_fourn_ref">'.$langs->trans("AskPriceSupplierRefFourn").'</span></td>';
		}

		// VAT
		print '<td align="right" width="50">'.$langs->trans('VAT').'</td>';

		// Price HT
		print '<td align="right" width="80">'.$langs->trans('PriceUHT').'</td>';

		if ($inputalsopricewithtax) print '<td align="right" width="80">'.$langs->trans('PriceUTTC').'</td>';

		// Qty
		print '<td align="right" width="50">'.$langs->trans('Qty').'</td>';

		// Reduction short
		print '<td align="right" width="50">'.$langs->trans('ReductionShort').'</td>';

		if ($this->situation_cycle_ref) {
			print '<td align="right" width="50">' . $langs->trans('Progress') . '</td>';
		}

		if ($usemargins && ! empty($conf->margin->enabled) && empty($user->societe_id))
		{
			if ($conf->global->MARGIN_TYPE == "1")
				print '<td align="right" class="margininfos" width="80">'.$langs->trans('BuyingPrice').'</td>';
			else
				print '<td align="right" class="margininfos" width="80">'.$langs->trans('CostPrice').'</td>';

			if (! empty($conf->global->DISPLAY_MARGIN_RATES) && $user->rights->margins->liretous)
				print '<td align="right" class="margininfos" width="50">'.$langs->trans('MarginRate').'</td>';
			if (! empty($conf->global->DISPLAY_MARK_RATES) && $user->rights->margins->liretous)
				print '<td align="right" class="margininfos" width="50">'.$langs->trans('MarkRate').'</td>';
		}

		// Total HT
		print '<td align="right" width="50">'.$langs->trans('TotalHTShort').'</td>';

		print '<td></td>';  // No width to allow autodim

		print '<td width="10"></td>';

		print '<td width="10"></td>';

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

			if (is_object($hookmanager) && (($line->product_type == 9 && ! empty($line->special_code)) || ! empty($line->fk_parent_line)))
			{
				if (empty($line->fk_parent_line))
				{
					$parameters = array('line'=>$line,'var'=>$var,'num'=>$num,'i'=>$i,'dateSelector'=>$dateSelector,'seller'=>$seller,'buyer'=>$buyer,'selected'=>$selected, 'extrafieldsline'=>$extrafieldsline);
					$reshook=$hookmanager->executeHooks('printObjectLine', $parameters, $this, $action);    // Note that $action and $object may have been modified by some hooks
				}
				else
				{
					$parameters = array('line'=>$line,'var'=>$var,'num'=>$num,'i'=>$i,'dateSelector'=>$dateSelector,'seller'=>$seller,'buyer'=>$buyer,'selected'=>$selected, 'extrafieldsline'=>$extrafieldsline);
					$reshook=$hookmanager->executeHooks('printObjectSubLine', $parameters, $this, $action);    // Note that $action and $object may have been modified by some hooks
				}
			}
			else
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
	 *	@param	string		$selected		   	Object line selected
	 *  @param  object		$extrafieldsline	Object of extrafield line attribute
	 *	@return	void
	 */
	function printObjectLine($action,$line,$var,$num,$i,$dateSelector,$seller,$buyer,$selected=0,$extrafieldsline=0)
	{
		global $conf,$langs,$user,$object,$hookmanager;
		global $form,$bc,$bcdd, $object_rights;

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

			$pu_ttc = price2num($line->subprice * (1 + ($line->tva_tx/100)), 'MU');

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
        global $langs, $hookmanager;

        print '<tr class="liste_titre">';
        print '<td>'.$langs->trans('Ref').'</td>';
        print '<td>'.$langs->trans('Description').'</td>';
        print '<td align="right">'.$langs->trans('VAT').'</td>';
        print '<td align="right">'.$langs->trans('PriceUHT').'</td>';
        print '<td align="right">'.$langs->trans('Qty').'</td>';
        print '<td align="right">'.$langs->trans('ReductionShort').'</td></tr>';

        $num = count($this->lines);
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
                    $reshook=$hookmanager->executeHooks('printOriginObjectLine',$parameters,$this,$action);    // Note that $action and $object may have been modified by some hooks
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
     * 	@param	array	$line		Line
     * 	@param	string	$var		Var
     * 	@return	void
     */
    function printOriginLine($line,$var)
    {
        global $conf,$langs,$bc;

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

        $this->tpl['vat_rate'] = vatrate($line->tva_tx, true);
        $this->tpl['price'] = price($line->subprice);
        $this->tpl['qty'] = (($line->info_bits & 2) != 2) ? $line->qty : '&nbsp;';
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
	 *	get Margin info
	 *
	 * 	@param 	string 	$force_price	True of not
	 * 	@return mixed					Array with info
	 */
	function getMarginInfos($force_price=false)
	{
		global $conf;
		require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.product.class.php';

		$marginInfos = array(
				'pa_products' => 0,
				'pv_products' => 0,
				'margin_on_products' => 0,
				'margin_rate_products' => '',
				'mark_rate_products' => '',
				'pa_services' => 0,
				'pv_services' => 0,
				'margin_on_services' => 0,
				'margin_rate_services' => '',
				'mark_rate_services' => '',
				'pa_total' => 0,
				'pv_total' => 0,
				'total_margin' => 0,
				'total_margin_rate' => '',
				'total_mark_rate' => ''
		);

		foreach($this->lines as $line) {
			if (empty($line->pa_ht) && isset($line->fk_fournprice) && !$force_price) {
				$product = new ProductFournisseur($this->db);
				if ($product->fetch_product_fournisseur_price($line->fk_fournprice))
					$line->pa_ht = $product->fourn_unitprice * (1 - $product->fourn_remise_percent / 100);
				if (isset($conf->global->MARGIN_TYPE) && $conf->global->MARGIN_TYPE == "2" && $product->fourn_unitcharges > 0)
					$line->pa_ht += $product->fourn_unitcharges;
			}
			// si prix d'achat non renseigné et devrait l'être, alors prix achat = prix vente
			if ((!isset($line->pa_ht) || $line->pa_ht == 0) && $line->subprice > 0 && (isset($conf->global->ForceBuyingPriceIfNull) && $conf->global->ForceBuyingPriceIfNull == 1)) {
				$line->pa_ht = $line->subprice * (1 - ($line->remise_percent / 100));
			}

			// calcul des marges
			if (isset($line->fk_remise_except) && isset($conf->global->MARGIN_METHODE_FOR_DISCOUNT)) {    // remise
			    $pa = $line->qty * $line->pa_ht;
			    $pv = $line->qty * $line->subprice * (1 - $line->remise_percent / 100);
				if ($conf->global->MARGIN_METHODE_FOR_DISCOUNT == '1') { // remise globale considérée comme produit
					$marginInfos['pa_products'] += $pa;
					$marginInfos['pv_products'] += $pv;
					$marginInfos['pa_total'] +=  $pa;
					$marginInfos['pv_total'] +=  $pv;
					// if credit note, margin = -1 * (abs(selling_price) - buying_price)
					if ($pv < 0)
						$marginInfos['margin_on_products'] += -1 * (abs($pv) - $pa);
					else
						$marginInfos['margin_on_products'] += $pv - $pa;
				}
				elseif ($conf->global->MARGIN_METHODE_FOR_DISCOUNT == '2') { // remise globale considérée comme service
					$marginInfos['pa_services'] += $pa;
					$marginInfos['pv_services'] += $pv;
					$marginInfos['pa_total'] +=  $pa;
					$marginInfos['pv_total'] +=  $pv;
					// if credit note, margin = -1 * (abs(selling_price) - buying_price)
					if ($pv < 0)
						$marginInfos['margin_on_services'] += -1 * (abs($pv) - $pa);
					else
						$marginInfos['margin_on_services'] += $pv - $pa;
				}
				elseif ($conf->global->MARGIN_METHODE_FOR_DISCOUNT == '3') { // remise globale prise en compte uniqt sur total
					$marginInfos['pa_total'] += $pa;
					$marginInfos['pv_total'] += $pv;
				}
			}
			else {
				$type=$line->product_type?$line->product_type:$line->fk_product_type;
				if ($type == 0) {  // product
				    $pa = $line->qty * $line->pa_ht;
				    $pv = $line->qty * $line->subprice * (1 - $line->remise_percent / 100);
					$marginInfos['pa_products'] += $pa;
					$marginInfos['pv_products'] += $pv;
					$marginInfos['pa_total'] +=  $pa;
					$marginInfos['pv_total'] +=  $pv;
					// if credit note, margin = -1 * (abs(selling_price) - buying_price)
					if ($pv < 0)
						$marginInfos['margin_on_products'] += -1 * (abs($pv) - $pa);
					else
						$marginInfos['margin_on_products'] += $pv - $pa;
				}
				elseif ($type == 1) {  // service
				    $pa = $line->qty * $line->pa_ht;
				    $pv = $line->qty * $line->subprice * (1 - $line->remise_percent / 100);
					$marginInfos['pa_services'] += $pa;
					$marginInfos['pv_services'] += $pv;
					$marginInfos['pa_total'] +=  $pa;
					$marginInfos['pv_total'] +=  $pv;
					// if credit note, margin = -1 * (abs(selling_price) - buying_price)
					if ($pv < 0)
						$marginInfos['margin_on_services'] += -1 * (abs($pv) - $pa);
					else
						$marginInfos['margin_on_services'] += $pv - $pa;
				}
			}
		}
		if ($marginInfos['pa_products'] > 0)
			$marginInfos['margin_rate_products'] = 100 * $marginInfos['margin_on_products'] / $marginInfos['pa_products'];
		if ($marginInfos['pv_products'] > 0)
			$marginInfos['mark_rate_products'] = 100 * $marginInfos['margin_on_products'] / $marginInfos['pv_products'];

		if ($marginInfos['pa_services'] > 0)
			$marginInfos['margin_rate_services'] = 100 * $marginInfos['margin_on_services'] / $marginInfos['pa_services'];
		if ($marginInfos['pv_services'] > 0)
			$marginInfos['mark_rate_services'] = 100 * $marginInfos['margin_on_services'] / $marginInfos['pv_services'];

		// if credit note, margin = -1 * (abs(selling_price) - buying_price)
		if ($marginInfos['pv_total'] < 0)
			$marginInfos['total_margin'] = -1 * (abs($marginInfos['pv_total']) - $marginInfos['pa_total']);
		else
			$marginInfos['total_margin'] = $marginInfos['pv_total'] - $marginInfos['pa_total'];
		if ($marginInfos['pa_total'] > 0)
			$marginInfos['total_margin_rate'] = 100 * $marginInfos['total_margin'] / $marginInfos['pa_total'];
		if ($marginInfos['pv_total'] > 0)
			$marginInfos['total_mark_rate'] = 100 * $marginInfos['total_margin'] / $marginInfos['pv_total'];

		return $marginInfos;
	}

	/**
	 * Show the array with all margin infos
	 *
	 * @param 	string 	$force_price	Force price
	 * @return	void
	 */
	function displayMarginInfos($force_price=false)
	{
		global $langs, $conf, $user;

    	if (! empty($user->societe_id)) return;

    	if (! $user->rights->margins->liretous) return;

        $rounding = min($conf->global->MAIN_MAX_DECIMALS_UNIT, $conf->global->MAIN_MAX_DECIMALS_TOT);

		$marginInfo = $this->getMarginInfos($force_price);

		if (! empty($conf->global->MARGIN_ADD_SHOWHIDE_BUTTON))	// FIXME Warning this feature rely on an external js file that may be removed. Using native js function document.cookie should be better
		{
			print $langs->trans('ShowMarginInfos').' : ';
	        $hidemargininfos = $_COOKIE['DOLUSER_MARGININFO_HIDE_SHOW'];
	    	print '<span id="showMarginInfos" class="linkobject '.(!empty($hidemargininfos)?'':'hideobject').'">'.img_picto($langs->trans("Disabled"),'switch_off').'</span>';
	    	print '<span id="hideMarginInfos" class="linkobject '.(!empty($hidemargininfos)?'hideobject':'').'">'.img_picto($langs->trans("Enabled"),'switch_on').'</span>';

    	    print '<script>$(document).ready(function() {
        	    $("span#showMarginInfos").click(function() { $.getScript( "'.dol_buildpath('/includes/jquery/plugins/jquerytreeview/lib/jquery.cookie.js', 1).'", function( data, textStatus, jqxhr ) { $.cookie("DOLUSER_MARGININFO_HIDE_SHOW", 0); $(".margininfos").show(); $("span#showMarginInfos").addClass("hideobject"); $("span#hideMarginInfos").removeClass("hideobject");})});
        	    $("span#hideMarginInfos").click(function() { $.getScript( "'.dol_buildpath('/includes/jquery/plugins/jquerytreeview/lib/jquery.cookie.js', 1).'", function( data, textStatus, jqxhr ) { $.cookie("DOLUSER_MARGININFO_HIDE_SHOW", 1); $(".margininfos").hide(); $("span#hideMarginInfos").addClass("hideobject"); $("span#showMarginInfos").removeClass("hideobject");})});
      	        });</script>';
    	    if (!empty($hidemargininfos)) print '<script>$(document).ready(function() {$(".margininfos").hide();});</script>';
		}

		print '<table class="noborder margintable" width="100%">';
		print '<tr class="liste_titre">';
		print '<td class="liste_titre">'.$langs->trans('Margins').'</td>';
		print '<td class="liste_titre" align="right">'.$langs->trans('SellingPrice').'</td>';
		if ($conf->global->MARGIN_TYPE == "1")
			print '<td class="liste_titre" align="right">'.$langs->trans('BuyingPrice').'</td>';
		else
			print '<td class="liste_titre" align="right">'.$langs->trans('CostPrice').'</td>';
		print '<td class="liste_titre" align="right">'.$langs->trans('Margin').'</td>';
		if (! empty($conf->global->DISPLAY_MARGIN_RATES))
			print '<td class="liste_titre" align="right">'.$langs->trans('MarginRate').'</td>';
		if (! empty($conf->global->DISPLAY_MARK_RATES))
			print '<td class="liste_titre" align="right">'.$langs->trans('MarkRate').'</td>';
		print '</tr>';

		if (! empty($conf->product->enabled))
		{
			//if ($marginInfo['margin_on_products'] != 0 && $marginInfo['margin_on_services'] != 0) {
			print '<tr class="impair">';
			print '<td>'.$langs->trans('MarginOnProducts').'</td>';
			print '<td align="right">'.price($marginInfo['pv_products'], null, null, null, null, $rounding).'</td>';
			print '<td align="right">'.price($marginInfo['pa_products'], null, null, null, null, $rounding).'</td>';
			print '<td align="right">'.price($marginInfo['margin_on_products'], null, null, null, null, $rounding).'</td>';
			if (! empty($conf->global->DISPLAY_MARGIN_RATES))
				print '<td align="right">'.(($marginInfo['margin_rate_products'] == '')?'':price($marginInfo['margin_rate_products'], null, null, null, null, $rounding).'%').'</td>';
			if (! empty($conf->global->DISPLAY_MARK_RATES))
				print '<td align="right">'.(($marginInfo['mark_rate_products'] == '')?'':price($marginInfo['mark_rate_products'], null, null, null, null, $rounding).'%').'</td>';
			print '</tr>';
		}

		if (! empty($conf->service->enabled))
		{
			print '<tr class="pair">';
			print '<td>'.$langs->trans('MarginOnServices').'</td>';
			print '<td align="right">'.price($marginInfo['pv_services'], null, null, null, null, $rounding).'</td>';
			print '<td align="right">'.price($marginInfo['pa_services'], null, null, null, null, $rounding).'</td>';
			print '<td align="right">'.price($marginInfo['margin_on_services'], null, null, null, null, $rounding).'</td>';
			if (! empty($conf->global->DISPLAY_MARGIN_RATES))
				print '<td align="right">'.(($marginInfo['margin_rate_services'] == '')?'':price($marginInfo['margin_rate_services'], null, null, null, null, $rounding).'%').'</td>';
			if (! empty($conf->global->DISPLAY_MARK_RATES))
				print '<td align="right">'.(($marginInfo['mark_rate_services'] == '')?'':price($marginInfo['mark_rate_services'], null, null, null, null, $rounding).'%').'</td>';
			print '</tr>';
		}

		if (! empty($conf->product->enabled) && ! empty($conf->service->enabled))
		{
			print '<tr class="impair">';
			print '<td>'.$langs->trans('TotalMargin').'</td>';
			print '<td align="right">'.price($marginInfo['pv_total'], null, null, null, null, $rounding).'</td>';
			print '<td align="right">'.price($marginInfo['pa_total'], null, null, null, null, $rounding).'</td>';
			print '<td align="right">'.price($marginInfo['total_margin'], null, null, null, null, $rounding).'</td>';
			if (! empty($conf->global->DISPLAY_MARGIN_RATES))
				print '<td align="right">'.(($marginInfo['total_margin_rate'] == '')?'':price($marginInfo['total_margin_rate'], null, null, null, null, $rounding).'%').'</td>';
			if (! empty($conf->global->DISPLAY_MARK_RATES))
				print '<td align="right">'.(($marginInfo['total_mark_rate'] == '')?'':price($marginInfo['total_mark_rate'], null, null, null, null, $rounding).'%').'</td>';
			print '</tr>';
		}
		print '</table>';
	}


	/**
	 *	Add resources to the current object : add entry into llx_element_resources
	 *Need $this->element & $this->id
	 *
	 *	@param		int		$resource_id		Resource id
	 *	@param		string	$resource_element	Resource element
	 *	@param		int		$busy				Busy or not
	 *	@param		int		$mandatory			Mandatory or not
	 *	@return		int							<=0 if KO, >0 if OK
	 */
	function add_element_resource($resource_id,$resource_element,$busy=0,$mandatory=0)
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
		$sql.= ", '".$resource_element."'";
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
	    global $user,$langs,$conf;


	    $this->db->begin();

	    $sql = "DELETE FROM ".MAIN_DB_PREFIX."element_resources";
	    $sql.= " WHERE rowid =".$rowid;

	    dol_syslog(get_class($this)."::delete_resource", LOG_DEBUG);
	    if ($this->db->query($sql))
	    {
	        if (! $notrigger)
	        {
	            $result=$this->call_trigger(strtoupper($element).'_DELETE_RESOURCE', $user);
	            if ($result < 0) { $this->db->rollback(); return -1; }
	        }

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
            	$this->lines[$i] = dol_clone($this->lines[$i]);
        	}
        }
    }

	/**
	 * Common function for all objects extending CommonObject for generating documents
	 *
	 * @param string $modelspath Relative folder where models are placed
	 * @param string $modele Model to use
	 * @param Translate $outputlangs Language to use
	 * @param int $hidedetails 1 to hide details. 0 by default
	 * @param int $hidedesc 1 to hide product description. 0 by default
	 * @param int $hideref 1 to hide product reference. 0 by default
	 * @return int 1 if OK -1 if not OK
	 */
	protected function commonGenerateDocument($modelspath, $modele, $outputlangs, $hidedetails, $hidedesc, $hideref)
	{
		global $conf, $langs;

		$srctemplatepath='';

		// Increase limit for PDF build
		$err=error_reporting();
		error_reporting(0);
		@set_time_limit(120);
		error_reporting($err);

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
			foreach(array('doc','pdf') as $prefix)
			{
				$file = $prefix."_".$modele.".modules.php";

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

		// Charge le modele
		if ($filefound)
		{
			require_once $file;

			$obj = new $classname($this->db);
			//$obj->message = $message;

			// We save charset_output to restore it because write_file can change it if needed for
			// output format that does not support UTF8.
			$sav_charset_output=$outputlangs->charset_output;
			if ($obj->write_file($this, $outputlangs, $srctemplatepath, $hidedetails, $hidedesc, $hideref) > 0)
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
				dol_print_error($this->db,"Error generating document for ".__CLASS__.". Error: ".$obj->error);
				return -1;
			}

		}
		else
		{
			dol_print_error('',$langs->trans("Error")." ".$langs->trans("ErrorFileDoesNotExists",$file));
			return -1;
		}
	}

	/**
	 *  Build thumb
	 *
	 *  @param      string	$file           Path file in UTF8 to original file to create thumbs from.
	 *	@return		void
	 */
	function add_thumb($file)
	{
		global $maxwidthsmall, $maxheightsmall, $maxwidthmini, $maxheightmini, $quality;

		require_once DOL_DOCUMENT_ROOT .'/core/lib/images.lib.php';		// This define also $maxwidthsmall, $quality, ...

		$file_osencoded=dol_osencode($file);
		if (file_exists($file_osencoded))
		{
			// Create small thumbs for company (Ratio is near 16/9)
	        // Used on logon for example
	        $imgThumbSmall = vignette($file_osencoded, $maxwidthsmall, $maxheightsmall, '_small', $quality);

	        // Create mini thumbs for company (Ratio is near 16/9)
	        // Used on menu or for setup page for example
	        $imgThumbMini = vignette($file_osencoded, $maxwidthmini, $maxheightmini, '_mini', $quality);
		}
	}


	/* Functions common to commonobject and commonobjectline */


	/* For triggers */


    /**
     * Call trigger based on this instance
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
    			$this->errors=array_merge($this->errors,$interface->errors);
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
    function fetch_optionals($rowid='',$optionsArray='')
    {
    	if (empty($rowid)) $rowid=$this->id;

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
                    	// Test fetch_array ! is_int($key) because fetch_array seult is a mix table with Key as alpha and Key as int (depend db engine)
                        if ($key != 'rowid' && $key != 'tms' && $key != 'fk_member' && ! is_int($key))
                        {
                            // we can add this attribute to adherent object
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
		global $langs;

		$error=0;

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
     *  This function delte record with all extrafields and insert them again from the array $this->array_options.
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
            $optionsArray = $extrafields->fetch_name_optionals_label($this->table_element);

            foreach($this->array_options as $key => $value)
            {
               	$attributeKey = substr($key,8);   // Remove 'options_' prefix
               	$attributeType  = $extrafields->attribute_type[$attributeKey];
               	$attributeSize  = $extrafields->attribute_size[$attributeKey];
               	$attributeLabel = $extrafields->attribute_label[$attributeKey];
               	$attributeParam = $extrafields->attribute_param[$attributeKey];
               	switch ($attributeType)
               	{
               		case 'int':
              			if (!is_numeric($value) && $value!='')
               			{
               				$error++; $this->errors[]=$langs->trans("ExtraFieldHasWrongValue",$attributeLabel);
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
				$object = new $InfoFieldList[0]($this->db);
				if ($value)
				{
					$object->fetch(0,$value);
					$this->array_options[$key]=$object->id;
				}
				break;
               	}
            }
            $this->db->begin();

            $sql_del = "DELETE FROM ".MAIN_DB_PREFIX.$this->table_element."_extrafields WHERE fk_object = ".$this->id;
            dol_syslog(get_class($this)."::insertExtraFields delete", LOG_DEBUG);
            $this->db->query($sql_del);
            $sql = "INSERT INTO ".MAIN_DB_PREFIX.$this->table_element."_extrafields (fk_object";
            foreach($this->array_options as $key => $value)
            {
            	$attributeKey = substr($key,8);   // Remove 'options_' prefix
                // Add field of attribut
            	if ($extrafields->attribute_type[$attributeKey] != 'separate') // Only for other type of separate
                	$sql.=",".$attributeKey;
            }
            $sql .= ") VALUES (".$this->id;
            foreach($this->array_options as $key => $value)
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
     * Function to show lines of extrafields with output datas
     *
     * @param	object	$extrafields	Extrafield Object
     * @param	string	$mode			Show output (view) or input (edit) for extrafield
	 * @param	array	$params			Optionnal parameters
	 * @param	string	$keyprefix		Prefix string to add into name and id of field (can be used to avoid duplicate names)
     *
     * @return string
     */
    function showOptionals($extrafields, $mode='view', $params=0, $keyprefix='')
    {
		global $_POST, $conf;

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
						$value=(isset($_POST["options_".$key])?$_POST["options_".$key]:$this->array_options["options_".$key]);
						break;
				}
				if ($extrafields->attribute_type[$key] == 'separate')
				{
					$out .= $extrafields->showSeparator($key);
				}
				else
				{
					$csstyle='';
					if (is_array($params) && count($params)>0) {
						if (array_key_exists('style',$params)) {
							$csstyle=$params['style'];
						}
					}
					if ( !empty($conf->global->MAIN_EXTRAFIELDS_USE_TWO_COLUMS) && ($e % 2) == 0)
					{
						$out .= '<tr '.$csstyle.'>';
						$colspan='0';
					}
					else
					{
						$out .= '<tr '.$csstyle.'>';
					}
					// Convert date into timestamp format
					if (in_array($extrafields->attribute_type[$key],array('date','datetime')))
					{
						$value = isset($_POST["options_".$key])?dol_mktime($_POST["options_".$key."hour"], $_POST["options_".$key."min"], 0, $_POST["options_".$key."month"], $_POST["options_".$key."day"], $_POST["options_".$key."year"]):$this->db->jdate($this->array_options['options_'.$key]);
					}

					if($extrafields->attribute_required[$key])
						$label = '<span class="fieldrequired">'.$label.'</span>';

					$out .= '<td>'.$label.'</td>';
					$out .='<td'.($colspan?' colspan="'.$colspan.'"':'').'>';

					switch($mode) {
					case "view":
						$out .= $extrafields->showOutputField($key,$value);
						break;
					case "edit":
						$out .= $extrafields->showInputField($key,$value,'',$keyprefix);
						break;
					}

					$out .= '</td>';

					if (! empty($conf->global->MAIN_EXTRAFIELDS_USE_TWO_COLUMS) && (($e % 2) == 1)) $out .= '</tr>';
					else $out .= '</tr>';
					$e++;
				}
			}
			$out .= "\n";
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

		return $user->rights->{$this->element};
	}


}
