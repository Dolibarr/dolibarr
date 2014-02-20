<?php
/* Copyright (C) 2011-2014 Alexandre Spangaro  <alexandre.spangaro@gmail.com> 
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
 *    \file       contract.class.php
 *    \ingroup    employee contract
 *    \brief      Class file of the module employee's contract.
 */
require_once DOL_DOCUMENT_ROOT .'/core/class/commonobject.class.php';


/**
 *	Class of the module employee contract.
 */
class Emcontract extends CommonObject
{
	public $element='emcontract';
	public $table_element='emcontract';

	  var $db;
    var $error;
    var $errors=array();

    var $id;
    var $rowid;
    var $ref;

    var $fk_employee;
    var $datec='';
    var $description;
    var $date_start_contract='';
    var $date_end_contract='';
    var $date_dpae='';
    var $date_medicalexam='';
    var $date_sign_employee='';
    var $date_sign_management='';
    var $type_contract='';
    var $fk_user_author;
    var $datem='';
    var $fk_user_modif;

    var $contract = array();
    var $events = array();
    var $logs = array();

    var $optName = '';
    var $optValue = '';
    var $optRowid = '';

    /**
     *   Constructor
     *
     *   @param		DoliDB		$db      Database handler
     */
    function __construct($db)
    {
        $this->db = $db;
    }

    /**
     *   Create a contract into database
     *
     *   @param		User	$user        	User that create
     *   @param     int		$notrigger	    0=launch triggers after, 1=disable triggers
     *   @return    int			         	<0 if KO, Id of created object if OK
     */
    function create($user, $notrigger=0)
    {
        global $conf, $langs;
        $error=0;

        $now=dol_now();

        // Check parameters
        if (empty($this->fk_employee) || ! is_numeric($this->fk_employee) || $this->fk_employee < 0) { $this->error="ErrorBadParameter"; return -1; }
        
        // Insert request
        $sql = "INSERT INTO ".MAIN_DB_PREFIX."emcontract(";
        $sql.= "fk_employee,";
        $sql.= "datec,";
        $sql.= "type_contract,";
        $sql.= "date_dpae,";
        $sql.= "date_medicalexam,";
        $sql.= "date_sign_employee,";
        $sql.= "date_sign_management,";
        $sql.= "description,";
        $sql.= "date_start_contract,";
        $sql.= "date_end_contract,";
        $sql.= "fk_user_author";
        $sql.= ") VALUES (";

        // User
        $sql.= "'".$this->fk_employee."',";
        $sql.= " '".$this->db->idate($now)."',";
        $sql.= " ".$this->type_contract.",";
        $sql.= " '".$this->db->idate($this->date_dpae)."',";
        $sql.= " '".$this->db->idate($this->date_medicalexam)."',";
        $sql.= " '".$this->db->idate($this->date_sign_employee)."',";
        $sql.= " '".$this->db->idate($this->date_sign_management)."',";
        $sql.= " '".$this->db->escape($this->description)."',";
        $sql.= " '".$this->db->idate($this->date_start_contract)."',";
        $sql.= " '".$this->db->idate($this->date_end_contract)."',";
        
        $sql.= " '".$this->fk_user_author."'";

        $sql.= ")";

        $this->db->begin();

        dol_syslog(get_class($this)."::create sql=".$sql, LOG_DEBUG);
        $resql=$this->db->query($sql);
        if (! $resql) {
            $error++; $this->errors[]="Error ".$this->db->lasterror();
        }

        if (! $error)
        {
            $this->rowid = $this->db->last_insert_id(MAIN_DB_PREFIX."emcontract");

        }

        // Commit or rollback
        if ($error)
        {
            foreach($this->errors as $errmsg)
            {
                dol_syslog(get_class($this)."::create ".$errmsg, LOG_ERR);
                $this->error.=($this->error?', '.$errmsg:$errmsg);
            }
            $this->db->rollback();
            return -1*$error;
        }
        else
        {
            $this->db->commit();
            return $this->rowid;
        }
    }


    /**
     *	Load object in memory from database
     *
     *  @param	int		$id         Id object
     *  @return int         		<0 if KO, >0 if OK
     */
    function fetch($id)
    {
        global $langs;

        $sql = "SELECT";
        $sql.= " em.rowid,";
        $sql.= " em.fk_employee,";
        $sql.= " em.datec,";
        $sql.= " em.type_contract,";
        $sql.= " em.date_dpae,";
        $sql.= " em.date_medicalexam,";
        $sql.= " em.date_sign_employee,";
        $sql.= " em.date_sign_management,";
        $sql.= " em.description,";
        $sql.= " em.date_start_contract,";
        $sql.= " em.date_end_contract,";

        $sql.= " em.fk_user_author,";
        $sql.= " em.fk_user_modif,";
        $sql.= " em.datem";
        $sql.= " FROM ".MAIN_DB_PREFIX."emcontract as em";
        $sql.= " WHERE em.rowid = ".$id;

        dol_syslog(get_class($this)."::fetch sql=".$sql, LOG_DEBUG);
        $resql=$this->db->query($sql);
        if ($resql)
        {
            if ($this->db->num_rows($resql))
            {
                $obj = $this->db->fetch_object($resql);

                $this->id    = $obj->rowid;
                $this->rowid    = $obj->rowid;	// deprecated
                $this->ref    = $obj->rowid;
                $this->fk_employee = $obj->fk_employee;
                $this->datec = $this->db->jdate($obj->datec);
                $this->type_contract = $obj->type_contract;
                $this->date_dpae = $this->db->jdate($obj->date_dpae);
                $this->date_medicalexam = $this->db->jdate($obj->date_medicalexam);
                $this->date_sign_employee = $this->db->jdate($obj->date_sign_employee);
                $this->date_sign_management = $this->db->jdate($obj->date_sign_management);
                $this->description = $obj->description;
                $this->date_start_contract = $this->db->jdate($obj->date_start_contract);
                $this->date_end_contract = $this->db->jdate($obj->date_end_contract);
                $this->fk_user_author = $obj->fk_user_author;
                $this->fk_user_modif = $obj->fk_user_modif;
                $this->datem = $this->db->jdate($obj->datem);
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
     *	List contracts for a particular user
     *
     *  @param		int		$user_id    ID of user to list
     *  @param      string	$order      Sort order
     *  @param      string	$filter     SQL Filter
     *  @return     int      			-1 if KO, 1 if OK, 2 if no result
     */
    function fetchByUser($user_id,$order='',$filter='')
    {
        global $langs, $conf;

        $sql = "SELECT";
        $sql.= " em.rowid,";
        $sql.= " em.fk_employee,";
        $sql.= " em.datec,";
        $sql.= " em.type_contract,";
        $sql.= " em.date_dpae,";
        $sql.= " em.date_medicalexam,";
        $sql.= " em.date_sign_employee,";
        $sql.= " em.date_sign_management,";
        $sql.= " em.description,";
        $sql.= " em.date_start_contract,";
        $sql.= " em.date_end_contract,";
        
		    $sql.= " e.lastname as lastname,";
        $sql.= " e.firstname as firstname";

        $sql.= " FROM ".MAIN_DB_PREFIX."emcontract as em, ".MAIN_DB_PREFIX."employee as e";
    		$sql.= " WHERE em.fk_employee = e.rowid"; // Hack pour la recherche sur le tableau
        $sql.= " AND e.rowid = '".$user_id."'";

        // Filtre de séléction
        if(!empty($filter)) {
            $sql.= $filter;
        }

        // Ordre d'affichage du résultat
        if(!empty($order)) {
            $sql.= $order;
        }

        dol_syslog(get_class($this)."::fetchByUser sql=".$sql, LOG_DEBUG);
        $resql=$this->db->query($sql);

        // Si pas d'erreur SQL
        if ($resql) {

            $i = 0;
            $tab_result = $this->emcontract;
            $num = $this->db->num_rows($resql);

            // Si pas d'enregistrement
            if(!$num) {
                return 2;
            }

            // Liste les enregistrements et les ajoutent au tableau
            while($i < $num) {

                $obj = $this->db->fetch_object($resql);

                $tab_result[$i]['rowid'] = $obj->rowid;
                $tab_result[$i]['ref'] = $obj->rowid;
                $tab_result[$i]['fk_employee'] = $obj->fk_employee;
                $tab_result[$i]['datec'] = $this->db->jdate($obj->datec);
                $tab_result[$i]['type_contract'] = $obj->type_contract;
                $tab_result[$i]['date_dpae'] = $this->db->jdate($obj->date_dpae);
                $tab_result[$i]['date_medicalexam'] = $this->db->jdate($obj->date_medicalexam);
                $tab_result[$i]['date_sign_employee'] = $this->db->jdate($obj->date_sign_employee);
                $tab_result[$i]['date_sign_management'] = $this->db->jdate($obj->date_sign_management);
                $tab_result[$i]['description'] = $obj->description;
                $tab_result[$i]['date_start_contract'] = $this->db->jdate($obj->date_start_contract);
                $tab_result[$i]['date_end_contract'] = $this->db->jdate($obj->date_end_contract);

                $tab_result[$i]['firstname'] = $obj->firstname;
                $tab_result[$i]['lastname'] = $obj->lastname;

                $i++;
            }

            // Retourne 1 avec le tableau rempli
            $this->emcontract = $tab_result;
            return 1;
        }
        else
        {
            // Erreur SQL
            $this->error="Error ".$this->db->lasterror();
            dol_syslog(get_class($this)."::fetchByUser ".$this->error, LOG_ERR);
            return -1;
        }
    }

    /**
     *	List all contracts of all users
     *
     *  @param      string	$order      Sort order
     *  @param      string	$filter     SQL Filter
     *  @return     int      			-1 if KO, 1 if OK, 2 if no result
     */
    function fetchAll($order,$filter)
    {
        global $langs;

        $sql = "SELECT";
        $sql.= " em.rowid,";

        $sql.= " em.fk_employee,";
        $sql.= " em.datec,";
        $sql.= " em.type_contract,";
        $sql.= " em.date_dpae,";
        $sql.= " em.date_medicalexam,";
        $sql.= " em.date_sign_employee,";
        $sql.= " em.date_sign_management,";
        $sql.= " em.description,";
        $sql.= " em.date_start_contract,";
        $sql.= " em.date_end_contract,";

        $sql.= " e.lastname as lastname,";
        $sql.= " e.firstname as firstname";

        $sql.= " FROM ".MAIN_DB_PREFIX."emcontract as em, ".MAIN_DB_PREFIX."employee as e";
        $sql.= " WHERE em.fk_user = e.rowid";

        // Filtrage de séléction
        if(!empty($filter)) {
            $sql.= $filter;
        }

        // Ordre d'affichage
        if(!empty($order)) {
            $sql.= $order;
        }

        dol_syslog(get_class($this)."::fetchAll sql=".$sql, LOG_DEBUG);
        $resql=$this->db->query($sql);

        // Si pas d'erreur SQL
        if ($resql) {

            $i = 0;
            $tab_result = $this->emcontract;
            $num = $this->db->num_rows($resql);

            // Si pas d'enregistrement
            if(!$num) {
                return 2;
            }

            // On liste les résultats et on les ajoutent dans le tableau
            while($i < $num) {

                $obj = $this->db->fetch_object($resql);

                $tab_result[$i]['rowid'] = $obj->rowid;
                $tab_result[$i]['ref'] = $obj->rowid;
                $tab_result[$i]['fk_employee'] = $obj->fk_employee;
                $tab_result[$i]['datec'] = $this->db->jdate($obj->datec);
                $tab_result[$i]['type_contract'] = $obj->type_contract;
                $tab_result[$i]['date_dpae'] = $this->db->jdate($obj->date_dpae);
                $tab_result[$i]['date_medicalexam'] = $this->db->jdate($obj->date_medicalexam);
                $tab_result[$i]['date_sign_employee'] = $this->db->jdate($obj->date_sign_employee);
                $tab_result[$i]['date_sign_management'] = $this->db->jdate($obj->date_sign_management);
                $tab_result[$i]['description'] = $obj->description;
                $tab_result[$i]['date_start_contract'] = $this->db->jdate($obj->date_start_contract);
                $tab_result[$i]['date_end_contract'] = $this->db->jdate($obj->date_end_contract);
                
                $tab_result[$i]['firstname'] = $obj->firstname;
                $tab_result[$i]['lastname'] = $obj->lastname;

                $i++;
            }
            // Retourne 1 et ajoute le tableau à la variable
            $this->emcontract = $tab_result;
            return 1;
        }
        else
        {
            // Erreur SQL
            $this->error="Error ".$this->db->lasterror();
            dol_syslog(get_class($this)."::fetchAll ".$this->error, LOG_ERR);
            return -1;
        }
    }

    /**
     *	Update database
     *
     *  @param	User	$user        	User that modify
     *  @param  int		$notrigger	    0=launch triggers after, 1=disable triggers
     *  @return int         			<0 if KO, >0 if OK
     */
    function update($user=0, $notrigger=0)
    {
        global $conf, $langs;
        $error=0;
        
        $now=dol_now();

        // Update request
        $sql = "UPDATE ".MAIN_DB_PREFIX."emcontract SET";

        if(!empty($this->date_start_contract)) {
            $sql.= " date_start_contract = '".$this->db->idate($this->date_start_contract)."',";
        } else {
            $error++;
        }
        if(!empty($this->date_end_contract)) {
            $sql.= " date_end_contract = '".$this->db->idate($this->date_end_contract)."',";
        } else {
            $error++;
        }
        if(!empty($this->date_dpae)) {
            $sql.= " date_dpae = '".$this->db->idate($this->date_dpae)."',";
        } else {
            $error++;
        }
        if(!empty($this->date_medicalexam)) {
            $sql.= " date_medicalexam = '".$this->db->idate($this->date_medicalexam)."',";
        } else {
            $error++;
        }
        if(!empty($this->date_sign_employee)) {
            $sql.= " date_sign_employee = '".$this->db->idate($this->date_sign_employee)."',";
        } else {
            $error++;
        }
        if(!empty($this->date_sign_management)) {
            $sql.= " date_sign_management = '".$this->db->idate($this->date_sign_management)."',";
        } else {
            $error++;
        }
        if(!empty($this->type_contract) && is_numeric($this->type_contract)) {
            $sql.= " type_contract = '".$this->type_contract."',";
        } else {
            $error++;
        }
        if(!empty($this->fk_employee)) {
            $sql.= " fk_employee = '".$this->fk_employee."',";
        } else {
            $sql.= " fk_employee = NULL,";
        }
        if(!empty($this->description)) {
            $sql.= " description = '".$this->description."',";
        } else {
            $sql.= " description = NULL,";
        }
        
        $sql.= " datem= '".$this->db->idate($now)."',";
        
        if(!empty($this->fk_user_modif)) {
            $sql.= " fk_user_modif = '".$this->fk_user_modif."'";
        } else {
            $sql.= " fk_user_modif = NULL";
        }
        
        $sql.= " WHERE rowid= '".$this->rowid."'";

        $this->db->begin();

        dol_syslog(get_class($this)."::update sql=".$sql, LOG_DEBUG);
        $resql = $this->db->query($sql);
        if (! $resql) {
            $error++; $this->errors[]="Error ".$this->db->lasterror();
        }

        if (! $error)
        {

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
     *   Delete object in database
     *
     *	 @param		User	$user        	User that delete
     *   @param     int		$notrigger	    0=launch triggers after, 1=disable triggers
     *	 @return	int						<0 if KO, >0 if OK
     */
    function delete($id)
    {
        global $conf, $langs;
        $error=0;

        $sql = "DELETE FROM ".MAIN_DB_PREFIX."emcontract";
        $sql.= " WHERE rowid=".$id;

        $this->db->begin();

        dol_syslog(get_class($this)."::delete sql=".$sql);
        $resql = $this->db->query($sql);
        if (! $resql) {
            $error++; $this->errors[]="Error ".$this->db->lasterror();
        }

        if (! $error)
        {

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
     *	Return clicable name (with picto eventually)
     *
     *	@param		int			$withpicto		0=_No picto, 1=Includes the picto in the linkn, 2=Picto only
     *	@return		string						String with URL
     */
    function getNomUrl($withpicto=0)
    {
    	global $langs;

    	$result='';

    	$lien = '<a href="'.DOL_URL_ROOT.'/employees/emcontract/fiche.php?id='.$this->id.'">';
    	$lienfin='</a>';

    	$label=$langs->trans("Show").': '.$this->ref;

    	if ($withpicto) $result.=($lien.img_object($label,$picto).$lienfin);
    	if ($withpicto && $withpicto != 2) $result.=' ';
    	if ($withpicto != 2) $result.=$lien.$this->ref.$lienfin;
    	return $result;
    }
    
    /**
  	*  Affiche formulaire de selection du type de contrat
  	*
  	*  @param  int		$selected    	Id or code preselected
  	*  @param  string	$htmlname   	Nom du formulaire select
  	*	@param	int		$empty			Add empty value in list
  	*	@return	void
  	*/
  	function select_typec($selected='', $htmlname='type_contract', $empty=0)
  	{
    	global $langs;
  
  		print '<select class="flat" name="'.$htmlname.'">';
      print '<option value="">&nbsp;</option>';
      print '<option ';
      if ($selected == 1) 
      { 
        print 'selected="selected"';
      }
      print ' value="1">'.$langs->trans("CDI").'</option>';
      print '<option ';
      if ($selected == 2) 
      { 
        print 'selected="selected"';
      }
      print ' value="2">'.$langs->trans("CDD").'</option>';
      print '<option ';
      if ($selected == 3) 
      { 
        print 'selected="selected"';
      }
      print ' value="3">'.$langs->trans("CA").'</option>';
      print '<option ';
      if ($selected == 4) 
      { 
        print 'selected="selected"';
      }
      print ' value="4">'.$langs->trans("CP").'</option>';
  		print '</select>';
  	}
  
    /**
  	*  Affiche formulaire de selection du type de contrat
  	*
  	*  @param  int		$selected    	Id or code preselected
  	*  @param  string	$htmlname   	Nom du formulaire select
  	*	@param	int		$empty			Add empty value in list
  	*	@return	void
  	*/
    function LibTypeContract($libtc)
  	{
    	global $langs;
      
      if ($libtc == 1) return $langs->trans('CDI');
      if ($libtc == 2) return $langs->trans('CDD');
      if ($libtc == 3) return $langs->trans('CA');
      if ($libtc == 4) return $langs->trans('CP');
    		
    	return $libtc;
    }
    
    /**
  	* Information on record
  	*
  	* @param	int		$id      Id of record
  	* @return	void
  	*/
  	function info($id)
  	{
  		$sql = 'SELECT em.rowid, em.datec, em.fk_user_author, em.fk_user_modif,';
  		$sql.= ' em.datem';
  		$sql.= ' FROM '.MAIN_DB_PREFIX.'emcontract as em';
  		$sql.= ' WHERE em.rowid = '.$id;
  
  		dol_syslog(get_class($this).'::info sql='.$sql);
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
  				if ($obj->fk_user_modif)
  				{
  					$muser = new User($this->db);
  					$muser->fetch($obj->fk_user_modif);
  					$this->user_modification = $muser;
  				}
  				$this->date_creation     = $this->db->jdate($obj->datec);
  				$this->date_modification = $this->db->jdate($obj->datem);
  			}
  			$this->db->free($result);
  		}
  		else
  		{
  			dol_print_error($this->db);
  		}
  	}
}
?>
