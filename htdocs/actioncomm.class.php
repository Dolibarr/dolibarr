<?php
/* Copyright (C) 2002-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *       \file       htdocs/actioncomm.class.php
 *       \ingroup    commercial
 *       \brief      Fichier de la classe des actions commerciales
 *       \version    $Id$
 */
require_once(DOL_DOCUMENT_ROOT.'/cactioncomm.class.php');


/**     \class      ActionComm
 *	    \brief      Classe permettant la gestion des actions commerciales
 */
class ActionComm
{
    var $db;
    var $error;
    
    var $type_id;
    var $type_code;
    var $type;
	
    var $id;
    var $label;

    var $datec;			// Date creation record (datec)
    var $datem;			// Date modification record (tms)
    var $author;		// Object user that create action
    var $usermod;		// Object user that modified action

    var $datep;			// Date action start (datep)
    var $datef;			// Date action end (datep2)
    var $durationp = -1;
    //var $date;			// Date action realise debut (datea)	// deprecated
    //var $dateend; 		// Date action realise fin (datea2)		// deprecated
    //var $durationa = -1;	// deprecated
	var $priority;
	var $punctual = 1;
	
    var $usertodo;		// Object user that must do action
    var $userdone;	 	// Object user that did action
	
    var $societe;		// Company linked to action (optionnal)
    var $contact;		// Contact linked tot action (optionnal)
    var $note;
    var $percentage;
    
	
    /**
     *      \brief      Constructeur
     *      \param      db      Handler d'acc�s base de donn�e
     */
    function ActionComm($db)
    {
        $this->db = $db;
        /*
        $this->societe = new Societe($db);
        $this->author = new User($db);
        $this->usermod = new User($db);
        $this->usertodo = new User($db);
        $this->userdone = new User($db);
        if (class_exists("Contact"))
        {
            $this->contact = new Contact($db);
        }
		*/
    }

    /**
     *    \brief      Ajout d'une action en base
     *    \param      user      	auteur de la creation de l'action
 	 *    \param      notrigger		1 ne declenche pas les triggers, 0 sinon
     *    \return     int         	id de l'action creee, < 0 si erreur
     */
    function add($user,$notrigger=0)
    {
        global $langs,$conf;

        // Clean parameters
		$this->label=trim($this->label);
		$this->location=trim($this->location);
		$this->note=trim($this->note);
        if (! $this->percentage) $this->percentage = 0;
        if (! $this->priority)   $this->priority = 0;
        if (! $this->punctual)   $this->punctual = 0;
        if ($this->percentage > 100) $this->percentage = 100;
        if ($this->percentage == 100 && ! $this->dateend) $this->dateend = $this->date;
		if ($this->datep && $this->datef)   $this->durationp=($this->datef - $this->datep);
		if ($this->date  && $this->dateend) $this->durationa=($this->dateend - $this->date);
		if ($this->datep && $this->datef && $this->datep > $this->datef) $this->datef=$this->datep;
		if ($this->date  && $this->dateend && $this->date > $this->dateend) $this->dateend=$this->date;

		$now=time();
		if (! $this->type_id && $this->type_code)
		{
			# Get id from code
			$cactioncomm=new CActionComm($this->db);
			$result=$cactioncomm->fetch($this->type_code);
			if ($result)
			{
				$this->type_id=$cactioncomm->id;
			}
			else
			{
				$this->error=$cactioncomm->error;
				return -1;
			}
		}

		// Check parameters
		if (! $this->type_id)
		{
			$this->error="ErrorWrongParameters";
			return -1;
		}
		
		
		$this->db->begin("ActionComm::add");

        $sql = "INSERT INTO ".MAIN_DB_PREFIX."actioncomm";
        $sql.= "(datec,";
        $sql.= "datep,";
        $sql.= "datep2,";
        $sql.= "datea,";
        $sql.= "datea2,";
        $sql.= "durationp,";
        $sql.= "durationa,";
        $sql.= "fk_action,";
        $sql.= "fk_soc,";
        $sql.= "note,";
		$sql.= "fk_contact,";
		$sql.= "fk_user_author,";
		$sql.= "fk_user_action,";
		$sql.= "fk_user_done,";
		$sql.= "label,percent,priority,location,punctual,";
        $sql.= "fk_facture,propalrowid,fk_commande)";
        $sql.= " VALUES (";
        $sql.= "'".$this->db->idate($now)."',";
        $sql.= (strval($this->datep)!=''?"'".$this->db->idate($this->datep)."'":"null").",";
        $sql.= (strval($this->datef)!=''?"'".$this->db->idate($this->datef)."'":"null").",";
        $sql.= (strval($this->date)!=''?"'".$this->db->idate($this->date)."'":"null").",";
        $sql.= (strval($this->dateend)!=''?"'".$this->db->idate($this->dateend)."'":"null").",";
        $sql.= ($this->durationp >= 0 && $this->durationp != ''?"'".$this->durationp."'":"null").",";
        $sql.= ($this->durationa >= 0 && $this->durationa != ''?"'".$this->durationa."'":"null").",";
        $sql.= " '".$this->type_id."',";
        $sql.= ($this->societe->id>0?" '".$this->societe->id."'":"null").",";
        $sql.= " '".addslashes($this->note)."',";
        $sql.= ($this->contact->id > 0?"'".$this->contact->id."'":"null").",";
        $sql.= ($user->id > 0 ? "'".$user->id."'":"null").",";
		$sql.= ($this->usertodo->id > 0?"'".$this->usertodo->id."'":"null").",";
		$sql.= ($this->userdone->id > 0?"'".$this->userdone->id."'":"null").",";
		$sql.= "'".addslashes($this->label)."','".$this->percentage."','".$this->priority."','".addslashes($this->location)."','".$this->punctual."',";
        $sql.= ($this->facid?$this->facid:"null").",";
        $sql.= ($this->propalrowid?$this->propalrowid:"null").",";
        $sql.= ($this->orderrowid?$this->orderrowid:"null");
        $sql.= ")";
    
        dolibarr_syslog("ActionComm::add sql=".$sql);
        $resql=$this->db->query($sql);
		if ($resql)
        {
            $this->id = $this->db->last_insert_id(MAIN_DB_PREFIX."actioncomm");
    
            if (! $notrigger)
            {
	            // Appel des triggers
	            include_once(DOL_DOCUMENT_ROOT . "/interfaces.class.php");
	            $interface=new Interfaces($this->db);
	            $result=$interface->run_triggers('ACTION_CREATE',$this,$user,$langs,$conf);
                if ($result < 0) { $error++; $this->errors=$interface->errors; }
	            // Fin appel triggers
			}
			
			$this->db->commit("ActionComm::add");
            return $this->id;
        }
        else
        {
			$this->error=$this->db->lasterror().' sql='.$sql;
			$this->db->rollback("ActionComm::add");
            return -1;
        }
    
    }

	/**
	*    \brief      Charge l'objet action depuis la base
	*    \param      id      id de l'action a r�cup�rer
	*/
	function fetch($id)
	{
		global $langs;
	
		$sql = "SELECT a.id,";
		$sql.= " ".$this->db->pdate("a.datea")." as datea,";
		$sql.= " ".$this->db->pdate("a.datea2")." as datea2,";
		$sql.= " ".$this->db->pdate("a.datep")." as datep,";
		$sql.= " ".$this->db->pdate("a.datep2")." as datep2,";
		$sql.= " ".$this->db->pdate("a.datec")." as datec, tms as datem,";
		$sql.= " a.note, a.label, a.fk_action as type_id,";
		$sql.= " a.fk_soc,";
		$sql.= " a.fk_user_author, a.fk_user_mod,";
		$sql.= " a.fk_user_action, a.fk_user_done,";
		$sql.= " a.fk_contact, a.percent as percentage, a.fk_facture, a.fk_commande, a.propalrowid,";
		$sql.= " a.priority, a.location,";
		$sql.= " c.id as type_id, c.code as type_code, c.libelle";
		$sql.= " FROM ".MAIN_DB_PREFIX."actioncomm as a, ".MAIN_DB_PREFIX."c_actioncomm as c";
		$sql.= " WHERE a.id=".$id." AND a.fk_action=c.id";
	
		dolibarr_syslog("ActionComm::fetch sql=".$sql);
		$resql=$this->db->query($sql);
		if ($resql)
		{
			if ($this->db->num_rows($resql))
			{
				$obj = $this->db->fetch_object($resql);
	
				$this->id        = $obj->id;
				$this->ref       = $obj->id;
				
				$this->type_id   = $obj->type_id;
				$this->type_code = $obj->type_code;
				$transcode=$langs->trans("Action".$obj->type_code);
				$type_libelle=($transcode!="Action".$obj->type_code?$transcode:$obj->libelle);
				$this->type    = $type_libelle;
				
				$this->label   = $obj->label;
				$this->datep   = $obj->datep;
				$this->datef   = $obj->datep2;
				$this->date    = $obj->datea;
				$this->dateend = $obj->datea2;

				$this->datec = $obj->datec;
				$this->datem = $obj->datem;
				$this->note =$obj->note;
				$this->percentage =$obj->percentage;

				$this->author->id  = $obj->fk_user_author;
				$this->usermod->id  = $obj->fk_user_mod;

				$this->usertodo->id  = $obj->fk_user_action;
				$this->userdone->id  = $obj->fk_user_done;
				$this->priority = $obj->priority;
				$this->location = $obj->location;
				
				$this->societe->id = $obj->fk_soc;
				$this->contact->id = $obj->fk_contact;

				$this->fk_facture = $obj->fk_facture;
				if ($this->fk_facture)
				{
					$this->objet_url = img_object($langs->trans("ShowBill"),'bill').' '.'<a href="'. DOL_URL_ROOT . '/compta/facture.php?facid='.$this->fk_facture.'">'.$langs->trans("Bill").'</a>';
					$this->objet_url_type = 'facture';
				}

				$this->fk_propal = $obj->propalrowid;
				if ($this->fk_propal)
				{
					$this->objet_url = img_object($langs->trans("ShowPropal"),'propal').' '.'<a href="'. DOL_URL_ROOT . '/comm/propal.php?propalid='.$this->fk_propal.'">'.$langs->trans("Propal").'</a>';
					$this->objet_url_type = 'propal';
				}

				$this->fk_commande = $obj->fk_commande;
				if ($this->fk_commande)
				{
					$this->objet_url = img_object($langs->trans("ShowOrder"),'order').' '.'<a href="'. DOL_URL_ROOT . '/commande/fiche.php?id='.$this->fk_commande.'">'.$langs->trans("Order").'</a>';
					$this->objet_url_type = 'order';
				}
	
			}
			$this->db->free($resql);
			return 1;
		}
		else
		{
			$this->error=$this->db->error();
			return -1;
		}
	}

	/**
	*    \brief      Supprime l'action de la base
	*    \return     int     <0 si ko, >0 si ok
	*/
	function delete()
    {      
        $sql = "DELETE FROM ".MAIN_DB_PREFIX."actioncomm";
        $sql.= " WHERE id=".$this->id;

        dolibarr_syslog("ActionComm::delete sql=".$sql, LOG_DEBUG);
        if ($this->db->query($sql))
        {
            return 1;
        }
        else
        {
        	$this->error=$this->db->error()." sql=".$sql;
        	return -1;
        }
    }

	/**
 	 *    \brief      	Met a jour l'action en base.
 	 *					Si percentage = 100, on met a jour date 100%
 	 *    \return     	int     <0 si ko, >0 si ok
	 */
    function update($user)
    {
        // Clean parameters
		$this->label=trim($this->label);
        $this->note=trim($this->note);
		if (! $this->percentage) $this->percentage = 0;
        if (! $this->priority)   $this->priority = 0;
        if ($this->percentage > 100) $this->percentage = 100;
        if ($this->percentage == 100 && ! $this->dateend) $this->dateend = $this->date;
		if ($this->datep && $this->datef)   $this->durationp=($this->datef - $this->datep);
		if ($this->date  && $this->dateend) $this->durationa=($this->dateend - $this->date);
		if ($this->datep && $this->datef && $this->datep > $this->datef) $this->datef=$this->datep;
		if ($this->date  && $this->dateend && $this->date > $this->dateend) $this->dateend=$this->date;
		
		// Check parameters
		if ($this->percentage == 0 && $this->userdone->id > 0)
		{
			$this->error="ErrorCantSaveADoneUserWithZeroPercentage";
			return -1;
		}
		
		//print 'eeea'.$this->datep.'-'.(strval($this->datep) != '').'-'.$this->db->idate($this->datep);
		$sql = "UPDATE ".MAIN_DB_PREFIX."actioncomm ";
        $sql.= " SET percent='".$this->percentage."'";
        $sql.= ", label = ".($this->label ? "'".addslashes($this->label)."'":"null");
        $sql.= ", datep = ".(strval($this->datep)!='' ? "'".$this->db->idate($this->datep)."'" : 'null');
        $sql.= ", datep2 = ".(strval($this->datef)!='' ? "'".$this->db->idate($this->datef)."'" : 'null');
        $sql.= ", datea = ".(strval($this->date)!='' ? "'".$this->db->idate($this->date)."'" : 'null');
        $sql.= ", datea2 = ".(strval($this->dateend)!='' ? "'".$this->db->idate($this->dateend)."'" : 'null');
        $sql.= ", note = ".($this->note ? "'".addslashes($this->note)."'":"null");
        $sql.= ", fk_soc =". ($this->societe->id > 0 ? "'".$this->societe->id."'":"null");
        $sql.= ", fk_contact =". ($this->contact->id > 0 ? "'".$this->contact->id."'":"null");
        $sql.= ", priority = '".$this->priority."'";
        $sql.= ", location = ".($this->location ? "'".addslashes($this->location)."'":"null");
        $sql.= ", fk_user_mod = '".$user->id."'";
		$sql.= ", fk_user_action=".($this->usertodo->id > 0 ? "'".$this->usertodo->id."'":"null");
		$sql.= ", fk_user_done=".($this->userdone->id > 0 ? "'".$this->userdone->id."'":"null");
        $sql.= " WHERE id=".$this->id;
    
		dolibarr_syslog("ActionComm::update sql=".$sql);
        if ($this->db->query($sql))
        {
            return 1;
        }
        else
        {
        	$this->error=$this->db->error();
			dolibarr_syslog("ActionComm::update ".$this->error,LOG_ERR);
        	return -1;
    	}
    }
    
    
    /**
     *      \brief        Charge indicateurs this->nbtodo et this->nbtodolate de tableau de bord
     *      \param        user    Objet user
     *      \return       int     <0 si ko, >0 si ok
     */
    function load_board($user)
    {
        global $conf, $user;
        
        $this->nbtodo=$this->nbtodolate=0;
        $sql = "SELECT a.id,".$this->db->pdate("a.datep")." as dp";
        if (!$user->rights->societe->client->voir && !$user->societe_id) $sql .= ", sc.fk_soc, sc.fk_user";
        $sql.= " FROM ".MAIN_DB_PREFIX."actioncomm as a";
        if (!$user->rights->societe->client->voir && !$user->societe_id) $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
        $sql.= " WHERE a.percent < 100";
        if ($user->societe_id) $sql.=" AND a.fk_soc = ".$user->societe_id;
        if (!$user->rights->societe->client->voir && !$user->societe_id) $sql .= " AND a.fk_soc = sc.fk_soc AND sc.fk_user = " .$user->id;
        $resql=$this->db->query($sql);
        if ($resql)
        {
            while ($obj=$this->db->fetch_object($resql))
            {
                $this->nbtodo++;
                if ($obj->dp < (time() - $conf->actions->warning_delay)) $this->nbtodolate++;
            }
            return 1;
        }
        else 
        {
            $this->error=$this->db->error();
            return -1;
        }
    }


	/**
	 *      \brief     Charge les informations d'ordre info dans l'objet facture
	 *      \param     id       	Id de la facture a charger
	 */
	function info($id)
	{
		$sql = 'SELECT a.id, '.$this->db->pdate('a.datec').' as datec,';
		$sql.= ' '.$this->db->pdate('tms').' as datem,';
		$sql.= ' fk_user_author,';
		$sql.= ' fk_user_mod';
		$sql.= ' FROM '.MAIN_DB_PREFIX.'actioncomm as a';
		$sql.= ' WHERE a.id = '.$id;

		dolibarr_syslog("ActionComm::info sql=".$sql);
		$result=$this->db->query($sql);
		if ($result)
		{
			if ($this->db->num_rows($result))
			{
				$obj = $this->db->fetch_object($result);
				$this->id = $obj->id;
				if ($obj->fk_user_author)
				{
					$cuser = new User($this->db, $obj->fk_user_author);
					$cuser->fetch();
					$this->user_creation     = $cuser;
				}
				if ($obj->fk_user_mod)
				{
					$muser = new User($this->db, $obj->fk_user_mod);
					$muser->fetch();
					$this->user_modification = $muser;
				}

				$this->date_creation     = $obj->datec;
				$this->date_modification = $obj->datem;
			}
			$this->db->free($result);
		}
		else
		{
			dolibarr_print_error($this->db);
		}
	}


	/**
	 *    	\brief      Retourne le libell� du statut de la commande
	 *    	\param      mode        0=libell� long, 1=libell� court, 2=Picto + Libell� court, 3=Picto, 4=Picto + Libell� long, 5=Libell� court + Picto
	 *    	\return     string      Libell�
	 */
	function getLibStatut($mode)
	{
		return $this->LibStatut($this->percentage,$mode);
	}

	/**
	 *		\brief      Renvoi le libell� d'un statut donn�
	 *    	\param      percent     Pourcentage avancement
	 *    	\param      mode        0=libell� long, 1=libell� court, 2=Picto + Libell� court, 3=Picto, 4=Picto + Libell� long, 5=Libell� court + Picto
	 *    	\return     string		Libell�
	 */
	function LibStatut($percent,$mode)
	{
		global $langs;
		
        if ($mode == 0)
        {
        	if ($percent==0) return $langs->trans('StatusActionToDo');
        	if ($percent > 0 && $percent < 100) return $langs->trans('StatusActionInProcess').' ('.$percent.'%)';
        	if ($percent >= 100) return $langs->trans('StatusActionDone').' (100%)';
		}
        if ($mode == 1)
        {
        	if ($percent==0) return $langs->trans('StatusActionToDo');
        	if ($percent > 0 && $percent < 100) return $percent.'%';
        	if ($percent >= 100) return $langs->trans('StatusActionDone');
        }
        if ($mode == 2)
        {
        	if ($percent==0) return img_picto($langs->trans('StatusActionToDo'),'statut1').' '.$langs->trans('StatusActionToDo');
        	if ($percent > 0 && $percent < 100) return img_picto($langs->trans('StatusActionInProcess'),'statut3').' '. $percent.'%';
        	if ($percent >= 100) return img_picto($langs->trans('StatusActionDone'),'statut6').' '.$langs->trans('StatusActionDone');
        }
        if ($mode == 3)
        {
        	if ($percent==0) return img_picto($langs->trans("Status").': '.$langs->trans('StatusActionToDo'),'statut1');
        	if ($percent > 0 && $percent < 100) return img_picto($langs->trans("Status").': '.$langs->trans('StatusActionInProcess').' ('.$percent.'%)','statut3');
        	if ($percent >= 100) return img_picto($langs->trans("Status").': '.$langs->trans('StatusActionDone'),'statut6');
        }
        if ($mode == 4)
        {
        	if ($percent==0) return img_picto($langs->trans('StatusActionToDo'),'statut1').' '.$langs->trans('StatusActionToDo').' (0%)';
        	if ($percent > 0 && $percent < 100) return img_picto($langs->trans('StatusActionInProcess'),'statut3').' '.$langs->trans('StatusActionInProcess').' ('.$percent.'%)';;
        	if ($percent >= 100) return img_picto($langs->trans('StatusActionDone'),'statut6').' '.$langs->trans('StatusActionDone').' (100%)';
        }
        if ($mode == 5)
        {
        	if ($percent==0) return '0% '.img_picto($langs->trans('StatusActionToDo'),'statut1');
        	if ($percent > 0 && $percent < 100) return $percent.'% '.img_picto($langs->trans('StatusActionInProcess'),'statut3');
        	if ($percent >= 100) return $langs->trans('StatusActionDone').' '.img_picto($langs->trans('StatusActionDone'),'statut6');
        }
	}

	/**
	 *    	\brief      Renvoie nom clicable (avec eventuellement le picto)
	 * 		\param		withpicto		0=Pas de picto, 1=Inclut le picto dans le lien, 2=Picto seul
	 *		\param		maxlength		Nombre de caracteres max dans libelle
	 *		\param		class			Force style class on a link
	 * 		\param		option			''=Link to action,'birthday'=Link to contact
	 *		\return		string			Chaine avec URL
	 *		\remarks	Utilise $this->id, $this->code et $this->libelle
	 */
	function getNomUrl($withpicto=0,$maxlength,$class='',$option='')
	{
		global $langs;
		
		$result='';
		if ($option=='birthday') $lien = '<a '.($class?'class="'.$class.'" ':'').'href="'.DOL_URL_ROOT.'/contact/perso.php?id='.$this->id.'">';
		else $lien = '<a '.($class?'class="'.$class.'" ':'').'href="'.DOL_URL_ROOT.'/comm/action/fiche.php?id='.$this->id.'">';
		$lienfin='</a>';

        if ($langs->trans("Action".$this->type_code) != "Action".$this->type_code || ! $this->libelle)
        {
        	$libelle=$langs->trans("Action".$this->type_code);
        	$libelleshort=$langs->trans("Action".$this->type_code,'','','','',$maxlength);
        }
        else
        {
        	$libelle=$this->libelle;
        	$libelleshort=dolibarr_trunc($this->libelle,$maxlength);
        }
		
		if ($withpicto) $result.=($lien.img_object($langs->trans("ShowAction").': '.$libelle,'task').$lienfin);
		if ($withpicto==1) $result.=' '; 
		$result.=$lien.$libelleshort.$lienfin;
		return $result;
	}

	
    /**
     *		\brief      Export events from database into a cal file.
	 *		\param		format			'ical' or 'vcal'
	 *		\param		type			'event' or 'journal'
	 *		\param		cachedelay		Do not rebuild file if date older than cachedelay seconds	
	 *		\param		filename		Force filename
	 *		\param		filters			Array of filters
     *		\return     int     		<0 if error, nb of events in new file if ok
     */
	function build_exportfile($format,$type,$cachedelay,$filename,$filters)
	{
		global $conf,$langs,$dolibarr_main_url_root;

		require_once (DOL_DOCUMENT_ROOT ."/lib/xcal.lib.php");

		dolibarr_syslog("ActionComm::build_exportfile Build export file format=".$format.", type=".$type.", cachedelay=".$cachedelay.", filename=".$filename.", filters size=".sizeof($filters), LOG_DEBUG);

		// Check parameters
		if (empty($format)) return -1;

		// Clean parameters
		if (! $filename)
		{
			$extension='vcs';
			if ($format == 'ical') $extension='ics';
			$filename=$format.'.'.$extension;
		}
		
		$result=create_exdir($conf->agenda->dir_temp);
		$outputfile=$conf->agenda->dir_temp.'/'.$filename;
		$result=0;

		$buildfile=true;
		$login='';$logina='';$logind='';$logint='';
		
		if ($cachedelay)
		{
			// \TODO Check cache
		}
		
		if ($buildfile)
		{
			// Build event array
			$eventarray=array();
			
			$sql = "SELECT a.id,";
			$sql.= " a.datep,";
			$sql.= " a.datep2,";
			//$sql.= " datea,";
			//$sql.= " datea2,";
			$sql.= " a.durationp, a.durationa,";
			$sql.= " a.datec, tms as datem,";
			$sql.= " a.note, a.label, a.fk_action as type_id,";
			$sql.= " a.fk_soc,";
			$sql.= " a.fk_user_author, a.fk_user_mod,";
			$sql.= " a.fk_user_action, a.fk_user_done,";
			$sql.= " a.fk_contact, a.fk_facture, a.percent as percentage, a.fk_commande,";
			$sql.= " a.priority,a.location,";
			$sql.= " c.id as type_id, c.code as type_code, c.libelle";
			$sql.= " FROM ".MAIN_DB_PREFIX."actioncomm as a, ".MAIN_DB_PREFIX."c_actioncomm as c";
			$sql.= " WHERE a.fk_action=c.id";
			foreach ($filters as $key => $value)
			{
				if ($key == 'year')     $sql.=' AND ';
				if ($key == 'idaction') $sql.=' AND a.id='.$value;
				if ($key == 'login')    
				{
					$login=$value;
					$userforfilter=new User($this->db);
					$userforfilter->fetch($value);
					$sql.= " AND (";
					$sql.= " a.fk_user_author = ".$userforfilter->id;
					$sql.= " OR a.fk_user_action = ".$userforfilter->id;
					$sql.= " OR a.fk_user_done = ".$userforfilter->id;
					$sql.= ")";
				}
				if ($key == 'logina')    
				{
					$logina=$value;
					$userforfilter=new User($this->db);
					$userforfilter->fetch($value);
					$sql.= " AND a.fk_user_author = ".$userforfilter->id;
				}
				if ($key == 'logint')    
				{
					$logint=$value;
					$userforfilter=new User($this->db);
					$userforfilter->fetch($value);
					$sql.= " AND a.fk_user_action = ".$userforfilter->id;
				}
				if ($key == 'logind')    
				{
					$logind=$value;
					$userforfilter=new User($this->db);
					$userforfilter->fetch($value);
					$sql.= " AND a.fk_user_done = ".$userforfilter->id;
				}
			}
			$sql.= " AND a.datep IS NOT NULL";		// To exclude corrupted events and avoid errors in lightning/sunbird import
			//$sql.= " AND a.datep != 'null'";	// To exclude corrupted events and avoid errors in lightning/sunbird import
			$sql.= " ORDER by datep";

			dolibarr_syslog("ActionComm::build_exportfile select events sql=".$sql);
			$resql=$this->db->query($sql);
			if ($resql)
			{
				// Note: Output of sql request is encoded in $conf->character_set_client
				while ($obj=$this->db->fetch_object($resql))
				{
					$qualified=true;
					
					// 'eid','startdate','duration','enddate','title','summary','category','email','url','desc','author'
					$event=array();
					$event['uid']='dolibarragenda-'.$this->db->database_name.'-'.$obj->id."@".$_SERVER["SERVER_NAME"];
					$event['type']=$type;
					//$datestart=$obj->datea?$obj->datea:$obj->datep;
					//$dateend=$obj->datea2?$obj->datea2:$obj->datep2;
					//$duration=$obj->durationa?$obj->durationa:$obj->durationp;
					$datestart=dolibarr_stringtotime($obj->datep);
					//print $datestart.'x'; exit;
					$dateend=dolibarr_stringtotime($obj->datep2);
					$duration=$obj->durationp;
					$event['summary']=$langs->convToOutputCharset($obj->label);
					$event['desc']=$langs->convToOutputCharset($obj->note);
					$event['startdate']=$datestart;
					$event['duration']=$duration;	// Not required with type 'journal'
					$event['enddate']=$dateend;		// Not required with type 'journal'
					$event['author']=$obj->fk_user_author;
					$event['priority']=$obj->priority;
					$event['location']=$langs->convToOutputCharset($obj->location);
					$event['transparency']='TRANSPARENT';		// TRANSPARENT or OPAQUE
					$event['category']=$langs->convToOutputCharset($obj->libelle);	// libelle type action
					$url=$dolibarr_main_url_root;
					if (! eregi('\/$',$url)) $url.='/';
					$url.='comm/action/fiche.php?id='.$obj->id;
					$event['url']=$url;
					
					if ($qualified && $datestart)
					{
						$eventarray[$datestart]=$event;
					}
				}
			}
			else
			{
				$this->error=$this->db->lasterror();
				dolibarr_syslog("ActionComm::build_exportfile ".$this->db->lasterror(), LOG_ERR);
				return -1;
			}
			
			$langs->load("agenda");
			
			// Define title and desc
			$more='';
			if ($login)  $more=$langs->transnoentities("User").' '.$langs->convToOutputCharset($login);
			if ($logina) $more=$langs->transnoentities("ActionsAskedBy").' '.$langs->convToOutputCharset($logina);
			if ($logint) $more=$langs->transnoentities("ActionsToDoBy").' '.$langs->convToOutputCharset($logint);
			if ($logind) $more=$langs->transnoentities("ActionsDoneBy").' '.$langs->convToOutputCharset($logind);
			if ($more)
			{ 
				$title=$langs->convToOutputCharset('Dolibarr actions - ').$more;
				$desc=$more.$langs->convToOutputCharset(' - built by Dolibarr');
			}
			else
			{
				$title=$langs->convToOutputCharset('Dolibarr actions');
				$desc=$langs->transnoentities('ListOfActions').$langs->convToOutputCharset(' - built by Dolibarr');
			}
			
			// Write file
			if ($format == 'ical') $result=build_calfile($format,$title,$desc,$eventarray,$outputfile);
			if ($format == 'vcal') $result=build_calfile($format,$title,$desc,$eventarray,$outputfile);
			if ($format == 'rss')  $result=build_rssfile($format,$title,$desc,$eventarray,$outputfile);
		}
		
		return $result;
	}

}

?>
