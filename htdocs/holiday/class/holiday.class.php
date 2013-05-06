<?php
/* Copyright (C) 2011	Dimitri Mouillard	<dmouillard@teclib.com>
 * Copyright (C) 2012	Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2012	Regis Houssin		<regis.houssin@capnetworks.com>
 * Copyright (C) 2013   Florian Henry		  	<florian.henry@open-concept.pro>
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
 *    \file       holiday.class.php
 *    \ingroup    holiday
 *    \brief      Class file of the module paid holiday.
 */
require_once DOL_DOCUMENT_ROOT .'/core/class/commonobject.class.php';


/**
 *	Class of the module paid holiday. Developed by Teclib ( http://www.teclib.com/ )
 */
class Holiday extends CommonObject
{
	public $element='holiday';
	public $table_element='holiday';

	var $db;
    var $error;
    var $errors=array();

    var $rowid;
    var $ref;

    var $fk_user;
    var $date_create='';
    var $description;
    var $date_debut='';
    var $date_fin='';
    var $halfday='';
    var $statut='';
    var $fk_validator;
    var $date_valid='';
    var $fk_user_valid;
    var $date_refuse='';
    var $fk_user_refuse;
    var $date_cancel='';
    var $fk_user_cancel;
    var $detail_refuse='';

    var $holiday = array();
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
     * updateSold
     *
     * @return	int			Return 1
     */
    function updateSold()
    {
	    // Mets à jour les congés payés en début de mois
	    $this->updateSoldeCP();

	    // Vérifie le nombre d'utilisateur et mets à jour si besoin
	    $this->verifNbUsers($this->countActiveUsers(),$this->getConfCP('nbUser'));
	    return 1;
    }

    /**
     *   Créer un congés payés dans la base de données
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
        if (empty($this->fk_user) || ! is_numeric($this->fk_user) || $this->fk_user < 0) { $this->error="ErrorBadParameter"; return -1; }
        if (empty($this->fk_validator) || ! is_numeric($this->fk_validator) || $this->fk_validator < 0)  { $this->error="ErrorBadParameter"; return -1; }

        // Insert request
        $sql = "INSERT INTO ".MAIN_DB_PREFIX."holiday(";
        $sql.= "fk_user,";
        $sql.= "date_create,";
        $sql.= "description,";
        $sql.= "date_debut,";
        $sql.= "date_fin,";
        $sql.= "halfday,";
        $sql.= "statut,";
        $sql.= "fk_validator";
        $sql.= ") VALUES (";

        // User
        $sql.= "'".$this->fk_user."',";
        $sql.= " '".$this->db->idate($now)."',";
        $sql.= " '".$this->db->escape($this->description)."',";
        $sql.= " '".$this->db->idate($this->date_debut)."',";
        $sql.= " '".$this->db->idate($this->date_fin)."',";
        $sql.= " ".$this->halfday.",";
        $sql.= " '1',";
        $sql.= " '".$this->fk_validator."'";

        $sql.= ")";

        $this->db->begin();

        dol_syslog(get_class($this)."::create sql=".$sql, LOG_DEBUG);
        $resql=$this->db->query($sql);
        if (! $resql) {
            $error++; $this->errors[]="Error ".$this->db->lasterror();
        }

        if (! $error)
        {
            $this->rowid = $this->db->last_insert_id(MAIN_DB_PREFIX."holiday");

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
        $sql.= " cp.rowid,";
        $sql.= " cp.fk_user,";
        $sql.= " cp.date_create,";
        $sql.= " cp.description,";
        $sql.= " cp.date_debut,";
        $sql.= " cp.date_fin,";
        $sql.= " cp.halfday,";
        $sql.= " cp.statut,";
        $sql.= " cp.fk_validator,";
        $sql.= " cp.date_valid,";
        $sql.= " cp.fk_user_valid,";
        $sql.= " cp.date_refuse,";
        $sql.= " cp.fk_user_refuse,";
        $sql.= " cp.date_cancel,";
        $sql.= " cp.fk_user_cancel,";
        $sql.= " cp.detail_refuse,";
        $sql.= " cp.note_private,";
        $sql.= " cp.note_public";
        $sql.= " FROM ".MAIN_DB_PREFIX."holiday as cp";
        $sql.= " WHERE cp.rowid = ".$id;

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
                $this->fk_user = $obj->fk_user;
                $this->date_create = $this->db->jdate($obj->date_create);
                $this->description = $obj->description;
                $this->date_debut = $this->db->jdate($obj->date_debut);
                $this->date_fin = $this->db->jdate($obj->date_fin);
                $this->halfday = $obj->halfday;
                $this->statut = $obj->statut;
                $this->fk_validator = $obj->fk_validator;
                $this->date_valid = $this->db->jdate($obj->date_valid);
                $this->fk_user_valid = $obj->fk_user_valid;
                $this->date_refuse = $this->db->jdate($obj->date_refuse);
                $this->fk_user_refuse = $obj->fk_user_refuse;
                $this->date_cancel = $this->db->jdate($obj->date_cancel);
                $this->fk_user_cancel = $obj->fk_user_cancel;
                $this->detail_refuse = $obj->detail_refuse;
                $this->note_private = $obj->note_private;
                $this->note_public = $obj->note_public;
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
     *	List holidays for a particular user
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
        $sql.= " cp.rowid,";

        $sql.= " cp.fk_user,";
        $sql.= " cp.date_create,";
        $sql.= " cp.description,";
        $sql.= " cp.date_debut,";
        $sql.= " cp.date_fin,";
        $sql.= " cp.halfday,";
        $sql.= " cp.statut,";
        $sql.= " cp.fk_validator,";
        $sql.= " cp.date_valid,";
        $sql.= " cp.fk_user_valid,";
        $sql.= " cp.date_refuse,";
        $sql.= " cp.fk_user_refuse,";
        $sql.= " cp.date_cancel,";
        $sql.= " cp.fk_user_cancel,";
        $sql.= " cp.detail_refuse,";

		$sql.= " uu.lastname as user_lastname,";
        $sql.= " uu.firstname as user_firstname,";

        $sql.= " ua.lastname as validator_lastname,";
        $sql.= " ua.firstname as validator_firstname";

        $sql.= " FROM ".MAIN_DB_PREFIX."holiday as cp, ".MAIN_DB_PREFIX."user as uu, ".MAIN_DB_PREFIX."user as ua";
		$sql.= " WHERE cp.fk_user = uu.rowid AND cp.fk_validator = ua.rowid "; // Hack pour la recherche sur le tableau
        $sql.= " AND cp.fk_user = '".$user_id."'";

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
            $tab_result = $this->holiday;
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
                $tab_result[$i]['fk_user'] = $obj->fk_user;
                $tab_result[$i]['date_create'] = $this->db->jdate($obj->date_create);
                $tab_result[$i]['description'] = $obj->description;
                $tab_result[$i]['date_debut'] = $this->db->jdate($obj->date_debut);
                $tab_result[$i]['date_fin'] = $this->db->jdate($obj->date_fin);
                $tab_result[$i]['halfday'] = $obj->halfday;
                $tab_result[$i]['statut'] = $obj->statut;
                $tab_result[$i]['fk_validator'] = $obj->fk_validator;
                $tab_result[$i]['date_valid'] = $this->db->jdate($obj->date_valid);
                $tab_result[$i]['fk_user_valid'] = $obj->fk_user_valid;
                $tab_result[$i]['date_refuse'] = $this->db->jdate($obj->date_refuse);
                $tab_result[$i]['fk_user_refuse'] = $obj->fk_user_refuse;
                $tab_result[$i]['date_cancel'] = $this->db->jdate($obj->date_cancel);
                $tab_result[$i]['fk_user_cancel'] = $obj->fk_user_cancel;
                $tab_result[$i]['detail_refuse'] = $obj->detail_refuse;

                $tab_result[$i]['user_firstname'] = $obj->user_firstname;
                $tab_result[$i]['user_lastname'] = $obj->user_lastname;

                $tab_result[$i]['validator_firstname'] = $obj->validator_firstname;
                $tab_result[$i]['validator_lastname'] = $obj->validator_lastname;

                $i++;
            }

            // Retourne 1 avec le tableau rempli
            $this->holiday = $tab_result;
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
     *	List all holidays of all users
     *
     *  @param      string	$order      Sort order
     *  @param      string	$filter     SQL Filter
     *  @return     int      			-1 if KO, 1 if OK, 2 if no result
     */
    function fetchAll($order,$filter)
    {
        global $langs;

        $sql = "SELECT";
        $sql.= " cp.rowid,";

        $sql.= " cp.fk_user,";
        $sql.= " cp.date_create,";
        $sql.= " cp.description,";
        $sql.= " cp.date_debut,";
        $sql.= " cp.date_fin,";
        $sql.= " cp.halfday,";
        $sql.= " cp.statut,";
        $sql.= " cp.fk_validator,";
        $sql.= " cp.date_valid,";
        $sql.= " cp.fk_user_valid,";
        $sql.= " cp.date_refuse,";
        $sql.= " cp.fk_user_refuse,";
        $sql.= " cp.date_cancel,";
        $sql.= " cp.fk_user_cancel,";
        $sql.= " cp.detail_refuse,";

        $sql.= " uu.lastname as user_lastname,";
        $sql.= " uu.firstname as user_firstname,";

        $sql.= " ua.lastname as validator_lastname,";
        $sql.= " ua.firstname as validator_firstname";

        $sql.= " FROM ".MAIN_DB_PREFIX."holiday as cp, ".MAIN_DB_PREFIX."user as uu, ".MAIN_DB_PREFIX."user as ua";
        $sql.= " WHERE cp.fk_user = uu.rowid AND cp.fk_validator = ua.rowid "; // Hack pour la recherche sur le tableau

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
            $tab_result = $this->holiday;
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
                $tab_result[$i]['fk_user'] = $obj->fk_user;
                $tab_result[$i]['date_create'] = $this->db->jdate($obj->date_create);
                $tab_result[$i]['description'] = $obj->description;
                $tab_result[$i]['date_debut'] = $this->db->jdate($obj->date_debut);
                $tab_result[$i]['date_fin'] = $this->db->jdate($obj->date_fin);
                $tab_result[$i]['halfday'] = $obj->halfday;
                $tab_result[$i]['statut'] = $obj->statut;
                $tab_result[$i]['fk_validator'] = $obj->fk_validator;
                $tab_result[$i]['date_valid'] = $this->db->jdate($obj->date_valid);
                $tab_result[$i]['fk_user_valid'] = $obj->fk_user_valid;
                $tab_result[$i]['date_refuse'] = $obj->date_refuse;
                $tab_result[$i]['fk_user_refuse'] = $obj->fk_user_refuse;
                $tab_result[$i]['date_cancel'] = $obj->date_cancel;
                $tab_result[$i]['fk_user_cancel'] = $obj->fk_user_cancel;
                $tab_result[$i]['detail_refuse'] = $obj->detail_refuse;

                $tab_result[$i]['user_firstname'] = $obj->user_firstname;
                $tab_result[$i]['user_lastname'] = $obj->user_lastname;

                $tab_result[$i]['validator_firstname'] = $obj->validator_firstname;
                $tab_result[$i]['validator_lastname'] = $obj->validator_lastname;

                $i++;
            }
            // Retourne 1 et ajoute le tableau à la variable
            $this->holiday = $tab_result;
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

        // Update request
        $sql = "UPDATE ".MAIN_DB_PREFIX."holiday SET";

        $sql.= " description= '".$this->db->escape($this->description)."',";

        if(!empty($this->date_debut)) {
            $sql.= " date_debut = '".$this->db->idate($this->date_debut)."',";
        } else {
            $error++;
        }
        if(!empty($this->date_fin)) {
            $sql.= " date_fin = '".$this->db->idate($this->date_fin)."',";
        } else {
            $error++;
        }
       	$sql.= " halfday = ".$this->halfday.",";
        if(!empty($this->statut) && is_numeric($this->statut)) {
            $sql.= " statut = '".$this->statut."',";
        } else {
            $error++;
        }
        if(!empty($this->fk_validator)) {
            $sql.= " fk_validator = '".$this->fk_validator."',";
        } else {
            $error++;
        }
        if(!empty($this->date_valid)) {
            $sql.= " date_valid = '".$this->db->idate($this->date_valid)."',";
        } else {
            $sql.= " date_valid = NULL,";
        }
        if(!empty($this->fk_user_valid)) {
            $sql.= " fk_user_valid = '".$this->fk_user_valid."',";
        } else {
            $sql.= " fk_user_valid = NULL,";
        }
        if(!empty($this->date_refuse)) {
            $sql.= " date_refuse = '".$this->db->idate($this->date_refuse)."',";
        } else {
            $sql.= " date_refuse = NULL,";
        }
        if(!empty($this->fk_user_refuse)) {
            $sql.= " fk_user_refuse = '".$this->fk_user_refuse."',";
        } else {
            $sql.= " fk_user_refuse = NULL,";
        }
        if(!empty($this->date_cancel)) {
            $sql.= " date_cancel = '".$this->db->idate($this->date_cancel)."',";
        } else {
            $sql.= " date_cancel = NULL,";
        }
        if(!empty($this->fk_user_cancel)) {
            $sql.= " fk_user_cancel = '".$this->fk_user_cancel."',";
        } else {
            $sql.= " fk_user_cancel = NULL,";
        }
        if(!empty($this->detail_refuse)) {
            $sql.= " detail_refuse = '".$this->db->escape($this->detail_refuse)."'";
        } else {
            $sql.= " detail_refuse = NULL";
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
    function delete($user, $notrigger=0)
    {
        global $conf, $langs;
        $error=0;

        $sql = "DELETE FROM ".MAIN_DB_PREFIX."holiday";
        $sql.= " WHERE rowid=".$this->rowid;

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
     *	verifDateHolidayCP
     *
     * 	@param 	int		$fk_user		Id user
     * 	@param 	date	$dateDebut		Start date
     * 	@param 	date	$dateFin		End date
     *  @param	int		$halfday		Tag to define half day when holiday start and end
     * 	@return boolean
     */
    function verifDateHolidayCP($fk_user, $dateDebut, $dateFin, $halfday=0)
    {
        $this->fetchByUser($fk_user,'','');

        foreach($this->holiday as $infos_CP)
        {
        	if ($infos_CP['statut'] == 4) continue;		// ignore not validated holidays
        	if ($infos_CP['statut'] == 5) continue;		// ignore not validated holidays

        	// TODO Also use halfday for the check
            if ($dateDebut >= $infos_CP['date_debut'] && $dateDebut <= $infos_CP['date_fin'] || $dateFin <= $infos_CP['date_fin'] && $dateFin >= $infos_CP['date_debut'])
            {
                return false;
            }
        }

        return true;

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

    	$lien = '<a href="'.DOL_URL_ROOT.'/holiday/fiche.php?id='.$this->id.'">';
    	$lienfin='</a>';

    	$picto='holiday';

    	$label=$langs->trans("Show").': '.$this->ref;

    	if ($withpicto) $result.=($lien.img_object($label,$picto).$lienfin);
    	if ($withpicto && $withpicto != 2) $result.=' ';
    	if ($withpicto != 2) $result.=$lien.$this->ref.$lienfin;
    	return $result;
    }


    /**
     *	Returns the label status
     *
     *	@param      int		$mode       0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto
     *	@return     string      		Label
     */
    function getLibStatut($mode=0)
    {
    	return $this->LibStatut($this->statut, $mode, $this->date_debut);
    }

	/**
	 *	Returns the label of a statut
	 *
	 *	@param      int		$statut     id statut
	 *	@param      int		$mode       0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto
	 *  @param		date	$startdate	Date holiday should start
	 *	@return     string      		Label
	 */
	function LibStatut($statut, $mode=0, $startdate='')
	{
		global $langs;

		if ($mode == 0)
		{
            if ($statut == 1) return $langs->trans('DraftCP');
            if ($statut == 2) return $langs->trans('ToReviewCP');
            if ($statut == 3) return $langs->trans('ApprovedCP');
            if ($statut == 4) return $langs->trans('CancelCP');
            if ($statut == 5) return $langs->trans('RefuseCP');
		}
		if ($mode == 2)
		{
			$pictoapproved='statut6';
			if (! empty($startdate) && $startdate > dol_now()) $pictoapproved='statut4';
			if ($statut == 1) return img_picto($langs->trans('DraftCP'),'statut0').' '.$langs->trans('DraftCP');				// Draft
			if ($statut == 2) return img_picto($langs->trans('ToReviewCP'),'statut1').' '.$langs->trans('ToReviewCP');		// Waiting approval
			if ($statut == 3) return img_picto($langs->trans('ApprovedCP'),$pictoapproved).' '.$langs->trans('ApprovedCP');
			if ($statut == 4) return img_picto($langs->trans('CancelCP'),'statut5').' '.$langs->trans('CancelCP');
			if ($statut == 5) return img_picto($langs->trans('RefuseCP'),'statut5').' '.$langs->trans('RefuseCP');
		}
		if ($mode == 5)
		{
			$pictoapproved='statut6';
			if (! empty($startdate) && $startdate > dol_now()) $pictoapproved='statut4';
			if ($statut == 1) return $langs->trans('DraftCP').' '.img_picto($langs->trans('DraftCP'),'statut0');				// Draft
			if ($statut == 2) return $langs->trans('ToReviewCP').' '.img_picto($langs->trans('ToReviewCP'),'statut1');		// Waiting approval
			if ($statut == 3) return $langs->trans('ApprovedCP').' '.img_picto($langs->trans('ApprovedCP'),$pictoapproved);
			if ($statut == 4) return $langs->trans('CancelCP').' '.img_picto($langs->trans('CancelCP'),'statut5');
			if ($statut == 5) return $langs->trans('RefuseCP').' '.img_picto($langs->trans('RefuseCP'),'statut5');
		}

		return $statut;
    }


    /**
     *   Affiche un select HTML des statuts de congés payés
     *
     *   @param 	int		$selected   int du statut séléctionné par défaut
     *   @return    string				affiche le select des statuts
     */
    function selectStatutCP($selected='') {

        global $langs;

        // Liste des statuts
        $name = array('DraftCP','ToReviewCP','ApprovedCP','CancelCP','RefuseCP');
        $nb = count($name)+1;

        // Select HTML
        $statut = '<select name="select_statut" class="flat">'."\n";
        $statut.= '<option value="-1">&nbsp;</option>'."\n";

        // Boucle des statuts
        for($i=1; $i < $nb; $i++) {
            if($i==$selected) {
                $statut.= '<option value="'.$i.'" selected="selected">'.$langs->trans($name[$i-1]).'</option>'."\n";
            }
            else {
                $statut.= '<option value="'.$i.'">'.$langs->trans($name[$i-1]).'</option>'."\n";
            }
        }

        $statut.= '</select>'."\n";
        print $statut;

    }

    /**
     *	Retourne un select HTML des groupes d'utilisateurs
     *
     *  @param	string	$prefix     nom du champ dans le formulaire
     *  @return string				retourne le select des groupes
     */
    function selectUserGroup($prefix)
    {
        // On récupère le groupe déjà configuré
        $group.= "SELECT value";
        $group.= " FROM ".MAIN_DB_PREFIX."holiday_config";
        $group.= " WHERE name = 'userGroup'";

        $resultat = $this->db->query($group);
        $objet = $this->db->fetch_object($resultat);
        $groupe = $objet->value;

        // On liste les groupes de Dolibarr
        $sql = "SELECT u.rowid, u.nom";
        $sql.= " FROM ".MAIN_DB_PREFIX."usergroup as u";
        $sql.= " ORDER BY u.rowid";

        dol_syslog(get_class($this)."::selectUserGroup sql=".$sql,LOG_DEBUG);
        $result = $this->db->query($sql);

        // Si pas d'erreur SQL
        if ($result)
        {
            // On créer le select HTML
            $selectGroup = '<select name="'.$prefix.'" class="flat">'."\n";
            $selectGroup.= '<option value="-1">&nbsp;</option>'."\n";

            // On liste les utilisateurs
            while ($obj = $this->db->fetch_object($result))
            {
                if($groupe==$obj->rowid) {
                    $selectGroup.= '<option value="'.$obj->rowid.'" selected="selected">'.$obj->nom.'</option>'."\n";
                } else {
                    $selectGroup.= '<option value="'.$obj->rowid.'">'.$obj->nom.'</option>'."\n";
                }
            }
            $selectGroup.= '</select>'."\n";
            $this->db->free($result);
        }
        else
        {
            // Erreur SQL
            $this->error=$this->db->lasterror();
            dol_syslog(get_class($this)."::selectUserGroup ".$this->error, LOG_ERR);
            return -1;
        }

        // Retourne le select HTML
        return $selectGroup;
    }

    /**
     *  Met à jour une option du module Holiday Payés
     *
     *  @param	string	$name       nom du paramètre de configuration
     *  @param	string	$value      vrai si mise à jour OK sinon faux
     *  @return boolean				ok or ko
     */
    function updateConfCP($name,$value) {

        $sql = "UPDATE ".MAIN_DB_PREFIX."holiday_config SET";
        $sql.= " value = '".$value."'";
        $sql.= " WHERE name = '".$name."'";

        dol_syslog(get_class($this).'::updateConfCP name='.$name.' sql='.$sql);
        $result = $this->db->query($sql);
        if($result) {
            return true;
        }

        return false;
    }

    /**
     *  Retourne la valeur d'un paramètre de configuration
     *
     *  @param	string	$name       nom du paramètre de configuration
     *  @return string      		retourne la valeur du paramètre
     */
    function getConfCP($name)
    {
        $sql = "SELECT value";
        $sql.= " FROM ".MAIN_DB_PREFIX."holiday_config";
        $sql.= " WHERE name = '".$name."'";

        dol_syslog(get_class($this).'::getConfCP name='.$name.' sql='.$sql);
        $result = $this->db->query($sql);

        // Si pas d'erreur
        if($result) {

            $objet = $this->db->fetch_object($result);
            // Retourne la valeur
            return $objet->value;

        } else {

            // Erreur SQL
            $this->error=$this->db->lasterror();
            dol_syslog(get_class($this)."::getConfCP ".$this->error, LOG_ERR);
            return -1;
        }
    }

    /**
     *	Met à jour le timestamp de la dernière mise à jour du solde des CP
     *
     *	@param		int		$userID		Id of user
     *	@param		int		$nbHoliday	Nb of days
     *  @return     void
     */
    function updateSoldeCP($userID='',$nbHoliday='')
    {
        global $user, $langs;

        if (empty($userID) && empty($nbHoliday))
        {
            // Si mise à jour pour tout le monde en début de mois
			$now=dol_now();

            // Mois actuel
            $month = date('m',$now);
            $lastUpdate = $this->getConfCP('lastUpdate');
            $monthLastUpdate = $lastUpdate[4].$lastUpdate[5];
			//print 'month: '.$month.' '.$lastUpdate.' '.$monthLastUpdate;

            // Si la date du mois n'est pas la même que celle sauvegardée, on met à jour le timestamp
            if ($month != $monthLastUpdate)
            {
                $sql = "UPDATE ".MAIN_DB_PREFIX."holiday_config SET";
                $sql.= " value = '".dol_print_date($now,'%Y%m%d%H%M%S')."'";
                $sql.= " WHERE name = 'lastUpdate'";

                dol_syslog(get_class($this).'::updateSoldeCP sql='.$sql);
                $result = $this->db->query($sql);

                // On ajoute x jours à chaque utilisateurs
                $nb_holiday = $this->getConfCP('nbHolidayEveryMonth');
				if (empty($nb_holiday)) $nb_holiday=0;

                $users = $this->fetchUsers(false,false);
                $nbUser = count($users);

                $i = 0;

                while($i < $nbUser)
                {
                    $now_holiday = $this->getCPforUser($users[$i]['rowid']);
                    $new_solde = $now_holiday + $this->getConfCP('nbHolidayEveryMonth');

                    // On ajoute la modification dans le LOG
                    $this->addLogCP($user->id,$users[$i]['rowid'], $langs->trans('Event').': '.$langs->trans('HolidaysMonthlyUpdate'),$new_solde);

                    $i++;
                }

                $sql2 = "UPDATE ".MAIN_DB_PREFIX."holiday_users SET";
                $sql2.= " nb_holiday = nb_holiday + ".$nb_holiday;

                dol_syslog(get_class($this).'::updateSoldeCP sql='.$sql2);
                $this->db->query($sql2);
            }
        } else {
            // Mise à jour pour un utilisateur
            $nbHoliday = number_format($nbHoliday,2,'.','');

            // Mise à jour pour un utilisateur
            $sql = "UPDATE ".MAIN_DB_PREFIX."holiday_users SET";
            $sql.= " nb_holiday = ".$nbHoliday;
            $sql.= " WHERE fk_user = '".$userID."'";

			dol_syslog(get_class($this).'::updateSoldeCP sql='.$sql);
            $this->db->query($sql);
        }

    }

    /**
     *	Retourne un checked si vrai
     *
     *  @param	string	$name       nom du paramètre de configuration
     *  @return string      		retourne checked si > 0
     */
    function getCheckOption($name) {

        $sql = "SELECT value";
        $sql.= " FROM ".MAIN_DB_PREFIX."holiday_config";
        $sql.= " WHERE name = '".$name."'";

        $result = $this->db->query($sql);

        if($result) {
            $obj = $this->db->fetch_object($result);

            // Si la valeur est 1 on retourne checked
            if($obj->value) {
                return 'checked="checked"';
            }
        }
    }


    /**
     *  Créer les entrées pour chaque utilisateur au moment de la configuration
     *
     *  @param	boolean		$single		Single
     *  @param	int			$userid		Id user
     *  @return void
     */
    function createCPusers($single=false,$userid='')
    {
        // Si c'est l'ensemble des utilisateurs à ajoutés
        if(!$single)
        {
        	dol_syslog(get_class($this).'::createCPusers');
            foreach($this->fetchUsers(false,true) as $users) {
                $sql = "INSERT INTO ".MAIN_DB_PREFIX."holiday_users";
                $sql.= " (fk_user, nb_holiday)";
                $sql.= " VALUES ('".$users['rowid']."','0')";

                $this->db->query($sql);
            }
        } else {
            $sql = "INSERT INTO ".MAIN_DB_PREFIX."holiday_users";
            $sql.= " (fk_user, nb_holiday)";
            $sql.= " VALUES ('".$userid."','0')";

            $this->db->query($sql);
        }

    }

    /**
     *  Supprime un utilisateur du module Congés Payés
     *
     *  @param	int		$user_id        ID de l'utilisateur à supprimer
     *  @return boolean      			Vrai si pas d'erreur, faut si Erreur
     */
    function deleteCPuser($user_id) {

        $sql = "DELETE FROM ".MAIN_DB_PREFIX."holiday_users";
        $sql.= " WHERE fk_user = '".$user_id."'";

        $this->db->query($sql);

    }


    /**
     *  Retourne le solde de congés payés pour un utilisateur
     *
     *  @param	int		$user_id    ID de l'utilisateur
     *  @return float        		Retourne le solde de congés payés de l'utilisateur
     */
    function getCPforUser($user_id) {

        $sql = "SELECT nb_holiday";
        $sql.= " FROM ".MAIN_DB_PREFIX."holiday_users";
        $sql.= " WHERE fk_user = '".$user_id."'";

        dol_syslog(get_class($this).'::getCPforUser sql='.$sql);
        $result = $this->db->query($sql);
        if($result) {
            $obj = $this->db->fetch_object($result);
            return number_format($obj->nb_holiday,2);
        } else {
            return '0';
        }

    }

    /**
     *    Liste la liste des utilisateurs du module congés
     *    uniquement pour vérifier si il existe de nouveau utilisateur
     *
     *    @param      boolean	$liste	    si vrai retourne une liste, si faux retourne un array
     *    @param      boolean   $type		si vrai retourne pour Dolibarr si faux retourne pour CP
     *    @return     string      			retourne un tableau de tout les utilisateurs actifs
     */
    function fetchUsers($liste=true,$type=true)
    {
        // Si vrai donc pour user Dolibarr
        if($liste) {

            if($type) {
                // Si utilisateur de Dolibarr

                $sql = "SELECT u.rowid";
                $sql.= " FROM ".MAIN_DB_PREFIX."user as u";
                $sql.= " WHERE statut > '0'";

                dol_syslog(get_class($this)."::fetchUsers sql=".$sql, LOG_DEBUG);
                $resql=$this->db->query($sql);

                // Si pas d'erreur SQL
                if ($resql) {

                    $i = 0;
                    $num = $this->db->num_rows($resql);
                    $liste = '';

                    // Boucles du listage des utilisateurs
                    while($i < $num) {

                        $obj = $this->db->fetch_object($resql);

                        if($i == 0) {
                            $liste.= $obj->rowid;
                        } else {
                            $liste.= ', '.$obj->rowid;
                        }

                        $i++;
                    }
                    // Retoune le tableau des utilisateurs
                    return $liste;
                }
                else
                {
                    // Erreur SQL
                    $this->error="Error ".$this->db->lasterror();
                    dol_syslog(get_class($this)."::fetchUsers ".$this->error, LOG_ERR);
                    return -1;
                }

            } else { // Si utilisateur du module Congés Payés
                $sql = "SELECT u.fk_user";
                $sql.= " FROM ".MAIN_DB_PREFIX."holiday_users as u";

                dol_syslog(get_class($this)."::fetchUsers sql=".$sql, LOG_DEBUG);
                $resql=$this->db->query($sql);

                // Si pas d'erreur SQL
                if ($resql) {

                    $i = 0;
                    $num = $this->db->num_rows($resql);
                    $liste = '';

                    // Boucles du listage des utilisateurs
                    while($i < $num) {

                        $obj = $this->db->fetch_object($resql);

                        if($i == 0) {
                            $liste.= $obj->fk_user;
                        } else {
                            $liste.= ', '.$obj->fk_user;
                        }

                        $i++;
                    }
                    // Retoune le tableau des utilisateurs
                    return $liste;
                }
                else
                {
                    // Erreur SQL
                    $this->error="Error ".$this->db->lasterror();
                    dol_syslog(get_class($this)."::fetchUsers ".$this->error, LOG_ERR);
                    return -1;
                }
            }

        } else { // Si faux donc user Congés Payés

            // Si c'est pour les utilisateurs de Dolibarr
            if($type) {

                $sql = "SELECT u.rowid, u.lastname, u.firstname";
                $sql.= " FROM ".MAIN_DB_PREFIX."user as u";
                $sql.= " WHERE statut > '0'";

                dol_syslog(get_class($this)."::fetchUsers sql=".$sql, LOG_DEBUG);
                $resql=$this->db->query($sql);

                // Si pas d'erreur SQL
                if ($resql) {

                    $i = 0;
                    $tab_result = $this->holiday;
                    $num = $this->db->num_rows($resql);

                    // Boucles du listage des utilisateurs
                    while($i < $num) {

                        $obj = $this->db->fetch_object($resql);

                        $tab_result[$i]['rowid'] = $obj->rowid;
                        $tab_result[$i]['name'] = $obj->lastname;
                        $tab_result[$i]['firstname'] = $obj->firstname;

                        $i++;
                    }
                    // Retoune le tableau des utilisateurs
                    return $tab_result;
                }
                else
                {
                    // Erreur SQL
                    $this->error="Error ".$this->db->lasterror();
                    dol_syslog(get_class($this)."::fetchUsers ".$this->error, LOG_ERR);
                    return -1;
                }

                // Si c'est pour les utilisateurs du module Congés Payés
            } else {

                $sql = "SELECT cpu.fk_user, u.lastname, u.firstname";
                $sql.= " FROM ".MAIN_DB_PREFIX."holiday_users as cpu,";
                $sql.= " ".MAIN_DB_PREFIX."user as u";
                $sql.= " WHERE cpu.fk_user = u.rowid";

                dol_syslog(get_class($this)."::fetchUsers sql=".$sql, LOG_DEBUG);
                $resql=$this->db->query($sql);

                // Si pas d'erreur SQL
                if ($resql) {

                    $i = 0;
                    $tab_result = $this->holiday;
                    $num = $this->db->num_rows($resql);

                    // Boucles du listage des utilisateurs
                    while($i < $num) {

                        $obj = $this->db->fetch_object($resql);

                        $tab_result[$i]['rowid'] = $obj->fk_user;
                        $tab_result[$i]['name'] = $obj->lastname;
                        $tab_result[$i]['firstname'] = $obj->firstname;

                        $i++;
                    }
                    // Retoune le tableau des utilisateurs
                    return $tab_result;
                }
                else
                {
                    // Erreur SQL
                    $this->error="Error ".$this->db->lasterror();
                    dol_syslog(get_class($this)."::fetchUsers ".$this->error, LOG_ERR);
                    return -1;
                }
            }
        }
    }

    /**
     *	Compte le nombre d'utilisateur actifs dans Dolibarr
     *
     *  @return     int      retourne le nombre d'utilisateur
     */
    function countActiveUsers() {

        $sql = "SELECT count(u.rowid) as compteur";
        $sql.= " FROM ".MAIN_DB_PREFIX."user as u";
        $sql.= " WHERE statut > '0'";

        $result = $this->db->query($sql);
        $objet = $this->db->fetch_object($result);
        return $objet->compteur;

    }

    /**
     *  Compare le nombre d'utilisateur actif de Dolibarr à celui des utilisateurs des congés payés
     *
     *  @param    int	$userDolibarr	nombre d'utilisateur actifs dans Dolibarr
     *  @param    int	$userCP    		nombre d'utilisateur actifs dans le module congés payés
     *  @return   void
     */
    function verifNbUsers($userDolibarr,$userCP) {

    	if (empty($userCP)) $userCP=0;
    	dol_syslog(get_class($this).'::verifNbUsers userDolibarr='.$userDolibarr.' userCP='.$userCP);

        // Si il y a plus d'utilisateur Dolibarr que dans le module CP
        if ($userDolibarr > $userCP)
        {
            $this->updateConfCP('nbUser',$userDolibarr);

            $listUsersCP = $this->fetchUsers(true,false);

            // On séléctionne les utilisateurs qui ne sont pas déjà dans le module
            $sql = "SELECT u.rowid, u.lastname, u.firstname";
            $sql.= " FROM ".MAIN_DB_PREFIX."user as u";
            $sql.= " WHERE u.rowid NOT IN(".$listUsersCP.")";

            $result = $this->db->query($sql);

            // Si pas d'erreur SQL
            if($result) {

                $i = 0;
                $num = $this->db->num_rows($resql);

                while($i < $num) {

                    $obj = $this->db->fetch_object($resql);

                    // On ajoute l'utilisateur
                    $this->createCPusers(true,$obj->rowid);

                    $i++;
                }


            } else {
                // Erreur SQL
                $this->error="Error ".$this->db->lasterror();
                dol_syslog(get_class($this)."::verifNbUsers ".$this->error, LOG_ERR);
                return -1;
            }

        } else {
            // Si il y a moins d'utilisateur Dolibarr que dans le module CP

            $this->updateConfCP('nbUser',$userDolibarr);

            $listUsersDolibarr = $this->fetchUsers(true,true);

            // On séléctionne les utilisateurs qui ne sont pas déjà dans le module
            $sql = "SELECT u.fk_user";
            $sql.= " FROM ".MAIN_DB_PREFIX."holiday_users as u";
            $sql.= " WHERE u.fk_user NOT IN (".$listUsersDolibarr.")";

            $resql = $this->db->query($sql);

            // Si pas d'erreur SQL
            if ($resql) {

                $i = 0;
                $num = $this->db->num_rows($resql);

                while($i < $num) {

                    $obj = $this->db->fetch_object($resql);

                    // On ajoute l'utilisateur
                    $this->deleteCPuser($obj->fk_user);

                    $i++;
                }


            } else {
                // Erreur SQL
                $this->error="Error ".$this->db->lasterror();
                dol_syslog(get_class($this)."::verifNbUsers ".$this->error, LOG_ERR);
                return -1;
            }
        }

    }


    /**
     *  Liste les évènements de congés payés enregistré
     *
     *  @return     int         -1 si erreur, 1 si OK et 2 si pas de résultat
     */
    function fetchEventsCP()
    {
        global $langs;

        $sql = "SELECT";
        $sql.= " cpe.rowid,";
        $sql.= " cpe.name,";
        $sql.= " cpe.value";

        $sql.= " FROM ".MAIN_DB_PREFIX."holiday_events as cpe";

        dol_syslog(get_class($this)."::fetchEventsCP sql=".$sql, LOG_DEBUG);
        $resql=$this->db->query($sql);

        // Si pas d'erreur SQL
        if ($resql) {

            $i = 0;
            $tab_result = $this->events;
            $num = $this->db->num_rows($resql);

            // Si pas d'enregistrement
            if(!$num) {
                return 2;
            }

            // On liste les résultats et on les ajoutent dans le tableau
            while($i < $num) {

                $obj = $this->db->fetch_object($resql);

                $tab_result[$i]['rowid'] = $obj->rowid;
                $tab_result[$i]['name'] = $obj->name;
                $tab_result[$i]['value'] = $obj->value;

                $i++;
            }
            // Retourne 1 et ajoute le tableau à la variable
            $this->events = $tab_result;
            return 1;
        }
        else
        {
            // Erreur SQL
            $this->error="Error ".$this->db->lasterror();
            dol_syslog(get_class($this)."::fetchEventsCP ".$this->error, LOG_ERR);
            return -1;
        }
    }

    /**
     *  Créer un évènement de congés payés
     *
     *	@param	User	$user			User
     *	@param	int		$notrigger		No trigger
     *  @return int         			-1 si erreur, id si OK
     */
    function createEventCP($user, $notrigger=0)
    {
        global $conf, $langs;
        $error=0;

        // Insert request
        $sql = "INSERT INTO ".MAIN_DB_PREFIX."holiday_events (";

        $sql.= "name,";
        $sql.= "value";

        $sql.= ") VALUES (";

        $sql.= " '".$this->db->escape($this->optName)."',";
        $sql.= " '".$this->optValue."'";
        $sql.= ")";

        $this->db->begin();

        dol_syslog(get_class($this)."::createEventCP sql=".$sql, LOG_DEBUG);
        $resql=$this->db->query($sql);
        if (! $resql) {
            $error++; $this->errors[]="Error ".$this->db->lasterror();
        }

        if (! $error)
        {
            $this->optRowid = $this->db->last_insert_id(MAIN_DB_PREFIX."holiday_events");

        }

        // Commit or rollback
        if ($error)
        {
            foreach($this->errors as $errmsg)
            {
                dol_syslog(get_class($this)."::createEventCP ".$errmsg, LOG_ERR);
                $this->error.=($this->error?', '.$errmsg:$errmsg);
            }
            $this->db->rollback();
            return -1*$error;
        }
        else
        {
            $this->db->commit();
            return $this->optRowid;
        }
    }

    /**
     *  Met à jour les évènements de congés payés
     *
     *	@param	int		$rowid		Row id
     *	@param	string	$name		Name
     *	@param	value	$value		Value
     *  @return int         		-1 si erreur, id si OK
     */
    function updateEventCP($rowid, $name, $value) {

        $sql = "UPDATE ".MAIN_DB_PREFIX."holiday_events SET";
        $sql.= " name = '".$this->db->escape($name)."', value = '".$value."'";
        $sql.= " WHERE rowid = '".$rowid."'";

        $result = $this->db->query($sql);

        if($result) {
            return true;
        }

        return false;
    }

    /**
     * Select event
     *
     * @return string|boolean		Select Html to select type of holiday
     */
    function selectEventCP()
    {

        $sql = "SELECT rowid, name, value";
        $sql.= " FROM ".MAIN_DB_PREFIX."holiday_events";

        $result = $this->db->query($sql);
        if ($result)
        {
            $num = $this->db->num_rows($result);
            $i = 0;

            $out = '<select name="list_event" class="flat" >';
            $out.= '<option value="-1">&nbsp;</option>';
            while ($i < $num)
            {
                $obj = $this->db->fetch_object($result);

                $out.= '<option value="'.$obj->rowid.'">'.$obj->name.' ('.$obj->value.')</option>';
                $i++;
            }
            $out.= '</select>';

            return $out;
        }
        else
       {
            return false;
        }
    }

    /**
     * deleteEvent
     *
     * @param 	int		$rowid		Row id
     * @return 	boolean				Success or not
     */
    function deleteEventCP($rowid) {

        $sql = "DELETE FROM ".MAIN_DB_PREFIX."holiday_events";
        $sql.= " WHERE rowid = '".$rowid."'";

        $result = $this->db->query($sql);

        if($result) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * getValueEventCp
     *
     * @param 	int		$rowid		Row id
     * @return string|boolean
     */
    function getValueEventCp($rowid) {

        $sql = "SELECT value";
        $sql.= " FROM ".MAIN_DB_PREFIX."holiday_events";
        $sql.= " WHERE rowid = '".$rowid."'";

        $result = $this->db->query($sql);

        if($result) {
            $obj = $this->db->fetch_array($result);
            return number_format($obj['value'],2);
        } else {
            return false;
        }
    }

    /**
     * getNameEventCp
     *
     * @param 	int		$rowid		Row id
     * @return unknown|boolean
     */
    function getNameEventCp($rowid) {

        $sql = "SELECT name";
        $sql.= " FROM ".MAIN_DB_PREFIX."holiday_events";
        $sql.= " WHERE rowid = '".$rowid."'";

        $result = $this->db->query($sql);

        if($result) {
            $obj = $this->db->fetch_array($result);
            return $obj['name'];
        } else {
            return false;
        }
    }

    /**
     * addLogCP
     *
     * @param 	int		$fk_user_action		Id user creation
     * @param 	int		$fk_user_update		Id user update
     * @param 	int		$type				Type
     * @param 	int		$new_solde			New value
     * @return number|string
     */
    function addLogCP($fk_user_action,$fk_user_update,$type,$new_solde) {

        global $conf, $langs;

        $error=0;

        $prev_solde = $this->getCPforUser($fk_user_update);
        $new_solde = number_format($new_solde,2,'.','');

        // Insert request
        $sql = "INSERT INTO ".MAIN_DB_PREFIX."holiday_logs (";

        $sql.= "date_action,";
        $sql.= "fk_user_action,";
        $sql.= "fk_user_update,";
        $sql.= "type_action,";
        $sql.= "prev_solde,";
        $sql.= "new_solde";

        $sql.= ") VALUES (";

        $sql.= " NOW(), ";
        $sql.= " '".$fk_user_action."',";
        $sql.= " '".$fk_user_update."',";
        $sql.= " '".$this->db->escape($type)."',";
        $sql.= " '".$prev_solde."',";
        $sql.= " '".$new_solde."'";
        $sql.= ")";

        $this->db->begin();

   	   	dol_syslog(get_class($this)."::addLogCP sql=".$sql, LOG_DEBUG);
   	   	$resql=$this->db->query($sql);
       	if (! $resql) {
       	    $error++; $this->errors[]="Error ".$this->db->lasterror();
       	}

       	if (! $error)
       	{
       	    $this->optRowid = $this->db->last_insert_id(MAIN_DB_PREFIX."holiday_logs");

       	}

       	// Commit or rollback
       	if ($error)
       	{
       	    foreach($this->errors as $errmsg)
       	    {
   	            dol_syslog(get_class($this)."::addLogCP ".$errmsg, LOG_ERR);
   	            $this->error.=($this->error?', '.$errmsg:$errmsg);
       	    }
       	    $this->db->rollback();
       	    return -1*$error;
       	}
       	else
       	{
       	    $this->db->commit();
            return $this->optRowid;
       	}
    }

    /**
     *  Liste le log des congés payés
     *
     *  @param	string	$order      Filtrage par ordre
     *  @param  string	$filter     Filtre de séléction
     *  @return int         		-1 si erreur, 1 si OK et 2 si pas de résultat
     */
    function fetchLog($order,$filter)
    {
        global $langs;

        $sql = "SELECT";
        $sql.= " cpl.rowid,";
        $sql.= " cpl.date_action,";
        $sql.= " cpl.fk_user_action,";
        $sql.= " cpl.fk_user_update,";
        $sql.= " cpl.type_action,";
        $sql.= " cpl.prev_solde,";
        $sql.= " cpl.new_solde";

        $sql.= " FROM ".MAIN_DB_PREFIX."holiday_logs as cpl";
        $sql.= " WHERE cpl.rowid > '0'"; // Hack pour la recherche sur le tableau

        // Filtrage de séléction
        if(!empty($filter)) {
            $sql.= $filter;
        }

        // Ordre d'affichage
        if(!empty($order)) {
            $sql.= $order;
        }

        dol_syslog(get_class($this)."::fetchLog sql=".$sql, LOG_DEBUG);
        $resql=$this->db->query($sql);

        // Si pas d'erreur SQL
      		if ($resql) {

      		    $i = 0;
      		    $tab_result = $this->logs;
      		    $num = $this->db->num_rows($resql);

      		    // Si pas d'enregistrement
      		    if(!$num) {
                return 2;
      		    }

      		    // On liste les résultats et on les ajoutent dans le tableau
      		    while($i < $num) {

      		        $obj = $this->db->fetch_object($resql);

      		        $tab_result[$i]['rowid'] = $obj->rowid;
      		        $tab_result[$i]['date_action'] = $obj->date_action;
      		        $tab_result[$i]['fk_user_action'] = $obj->fk_user_action;
      		        $tab_result[$i]['fk_user_update'] = $obj->fk_user_update;
      		        $tab_result[$i]['type_action'] = $obj->type_action;
      		        $tab_result[$i]['prev_solde'] = $obj->prev_solde;
      		        $tab_result[$i]['new_solde'] = $obj->new_solde;

      		        $i++;
      		    }
      		    // Retourne 1 et ajoute le tableau à la variable
      		    $this->logs = $tab_result;
      		    return 1;
      		}
      		else
      		{
      		    // Erreur SQL
      		    $this->error="Error ".$this->db->lasterror();
      		    dol_syslog(get_class($this)."::fetchLog ".$this->error, LOG_ERR);
      		    return -1;
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
    	global $user,$langs;

    	// Initialise parameters
    	$this->id=0;
    	$this->specimen=1;

    	$this->fk_user=1;
    	$this->description='SPECIMEN description';
    	$this->date_debut=dol_now();
    	$this->date_fin=dol_now()+(24*3600);
    	$this->fk_validator=1;
    	$this->halfday=0;
    }

}
?>
