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
        \file       htdocs/actioncomm.class.php
        \ingroup    commercial
        \brief      Fichier de la classe des actions commerciales
        \version    $Id$
*/

require_once(DOL_DOCUMENT_ROOT.'/cactioncomm.class.php');


/**     \class      ActionComm
	    \brief      Classe permettant la gestion des actions commerciales
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

    var $datec;			// Date creation enregistrement (datec)
    var $datem;			// Date modif enregistrement (tms)
    var $author;		// User that create action
    var $usermod;		// User that modified action

    var $datep;			// Date action planifie debut (datep)
    var $datef;			// Date action planifie fin (datep2)
    var $date;			// Date action realise debut (datea)
    var $dateend; 		// Date action realise fin (datea2)
    var $priority;

    var $usertodo;		// User that must do action
    var $userdone;	 	// User that did action
	
    var $societe;
    var $contact;
    var $note;
    var $percentage;
    
	
    /**
     *      \brief      Constructeur
     *      \param      db      Handler d'accès base de donnée
     */
    function ActionComm($db)
    {
        $this->db = $db;
        $this->societe = new Societe($db);
        $this->author = new User($db);
        $this->usermod = new User($db);
        $this->usertodo = new User($db);
        $this->userdone = new User($db);
        if (class_exists("Contact"))
        {
            $this->contact = new Contact($db);
        }
    }

    /**
     *    \brief      Ajout d'une action en base
     *    \param      author      	auteur de la creation de l'action
 	 *    \param      notrigger		1 ne declenche pas les triggers, 0 sinon
     *    \return     int         	id de l'action créée, < 0 si erreur
     */
    function add($author,$notrigger=0)
    {
        global $langs,$conf;
    
        if (! $this->percentage)  $this->percentage = 0;
        if (! $this->priority) $this->priority = 0;
        
		$this->db->begin();

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
				$this->db->rollback();
				return -1;
			}
		}
		
		if (! $this->type_id)
		{
			$this->error="ErrorWrongParameters";
			return -1;
		}
		
        $sql = "INSERT INTO ".MAIN_DB_PREFIX."actioncomm";
        $sql.= "(datec,";
        if ($this->datep) $sql.= "datep,";
        if ($this->date) $sql.= "datea,";
        $sql.= "fk_action,fk_soc,note,";
		$sql.= "fk_contact,";
		$sql.= "fk_user_author,";
		$sql.= "fk_user_action,";
		$sql.= "fk_user_done,";
		$sql.= "label,percent,priority,";
        $sql.= "fk_facture,propalrowid,fk_commande)";
        $sql.= " VALUES (now(),";
        if ($this->datep) $sql.= "'".$this->db->idate($this->datep)."',";
        if ($this->date) $sql.= "'".$this->db->idate($this->date)."',";
        $sql.= "'".$this->type_id."', '".$this->societe->id."' ,'".addslashes($this->note)."',";
        $sql.= ($this->contact->id > 0?"'".$this->contact->id."'":"null").",";
        $sql.= "'".$author->id."',";
		$sql.= ($this->usertodo->id > 0?"'".$this->usertodo->id."'":"null").",";
		$sql.= ($this->userdone->id > 0?"'".$this->userdone->id."'":"null").",";
		$sql.= "'".addslashes($this->label)."','".$this->percentage."','".$this->priority."',";
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
	            $result=$interface->run_triggers('ACTION_CREATE',$this,$author,$langs,$conf);
                if ($result < 0) { $error++; $this->errors=$interface->errors; }
	            // Fin appel triggers
			}
			
			$this->db->commit();
            return $this->id;
        }
        else
        {
			$this->error=$this->db->lasterror().' sql='.$sql;
			$this->db->rollback();
            return -1;
        }
    
    }

	/**
	*    \brief      Charge l'objet action depuis la base
	*    \param      id      id de l'action a récupérer
	*/
	function fetch($id)
	{
		global $langs;
	
		$sql = "SELECT a.id, ".$this->db->pdate("a.datea")." as datea,";
		$sql.= " ".$this->db->pdate("a.datep")." as datep,";
		$sql.= " ".$this->db->pdate("a.datec")." as datec, tms as datem,";
		$sql.= " a.note, a.label, a.fk_action as type_id,";
		$sql.= " a.fk_soc,";
		$sql.= " a.fk_user_author, a.fk_user_mod,";
		$sql.= " a.fk_user_action, a.fk_user_done,";
		$sql.= " a.fk_contact, a.fk_facture, a.percent as percentage, a.fk_commande,";
		$sql.= " a.priority,";
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
				$this->type_id   = $obj->type_id;
				$this->type_code = $obj->type_code;
				$transcode=$langs->trans("Action".$obj->code);
				$type_libelle=($transcode!="Action".$obj->code?$transcode:$obj->libelle);
				$this->type = $type_libelle;
				$this->label = $obj->label;
				$this->date  = $obj->datea;
				$this->datep = $obj->datep;
				$this->datec = $obj->datec;
				$this->datem = $obj->datem;
				$this->note =$obj->note;
				$this->percentage =$obj->percentage;
				$this->societe->id = $obj->fk_soc;
				$this->author->id  = $obj->fk_user_author;
				$this->usermod->id  = $obj->fk_user_mod;

				$this->usertodo->id  = $obj->fk_user_action;
				$this->userdone->id  = $obj->fk_user_done;
				$this->priority = $obj->priority;

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
					$this->objet_url = img_object($langs->trans("ShowPropal"),'propal').' '.'<a href="'. DOL_URL_ROOT . '/propal/fiche.php?rowid='.$this->fk_propal.'">'.$langs->trans("Propal").'</a>';
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
        if ($this->percentage > 100) $this->percentage = 100;
    
		// Check parameters
		if ($this->percentage == 0 && $this->userdone->id > 0)
		{
			$this->error="ErrorCantSaveADoneUserWithZeroPercentage";
			return -1;
		}
		
        $sql = "UPDATE ".MAIN_DB_PREFIX."actioncomm ";
        $sql.= " SET percent='".$this->percentage."'";
        $sql.= ", label = ".($this->label ? "'".addslashes($this->label)."'":"null");
        $sql.= ", datep = ".($this->datep ? "'".$this->db->idate($this->datep)."'" : 'null');
        $sql.= ", datea = ".($this->date ? "'".$this->db->idate($this->date)."'" : 'null');
        $sql.= ", note = ".($this->note ? "'".addslashes($this->note)."'":"null");
        $sql.= ", fk_contact =". ($this->contact->id > 0 ? "'".$this->contact->id."'":"null");
        $sql.= ", priority = '".$this->priority."'";
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
        $sql = "SELECT a.id,".$this->db->pdate("a.datea")." as da";
        if (!$user->rights->commercial->client->voir && !$user->societe_id) $sql .= ", sc.fk_soc, sc.fk_user";
        $sql.= " FROM ".MAIN_DB_PREFIX."actioncomm as a";
        if (!$user->rights->commercial->client->voir && !$user->societe_id) $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
        $sql.= " WHERE a.percent < 100";
        if ($user->societe_id) $sql.=" AND a.fk_soc = ".$user->societe_id;
        if (!$user->rights->commercial->client->voir && !$user->societe_id) $sql .= " AND a.fk_soc = sc.fk_soc AND sc.fk_user = " .$user->id;
        $resql=$this->db->query($sql);
        if ($resql)
        {
            while ($obj=$this->db->fetch_object($resql))
            {
                $this->nbtodo++;
                if ($obj->da < (time() - $conf->actions->warning_delay)) $this->nbtodolate++;
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
	 *    	\brief      Retourne le libellé du statut de la commande
	 *    	\param      mode        0=libellé long, 1=libellé court, 2=Picto + Libellé court, 3=Picto, 4=Picto + Libellé long, 5=Libellé court + Picto
	 *    	\return     string      Libellé
	 */
	function getLibStatut($mode)
	{
		return $this->LibStatut($this->percentage,$mode);
	}

	/**
	 *		\brief      Renvoi le libellé d'un statut donné
	 *    	\param      percent     Pourcentage avancement
	 *    	\param      mode        0=libellé long, 1=libellé court, 2=Picto + Libellé court, 3=Picto, 4=Picto + Libellé long, 5=Libellé court + Picto
	 *    	\return     string		Libellé
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
	 *		\param		withpicto		Inclut le picto dans le lien
	 *		\param		maxlength		Nombre de caractères max dans libellé
	 *		\return		string			Chaine avec URL
	 *		\remarks	Utilise $this->id, $this->code et $this->libelle
	 */
	function getNomUrl($withpicto=0,$maxlength)
	{
		global $langs;
		
		$result='';
		$lien = '<a href="'.DOL_URL_ROOT.'/comm/action/fiche.php?id='.$this->id.'">';
		$lienfin='</a>';

        if ($langs->trans("Action".$this->code) != "Action".$this->code)
        {
        	$libelle=$langs->trans("Action".$this->code);
        	$libelleshort=$langs->trans("Action".$this->code,'','','','',$maxlength);
        }
        else
        {
        	$libelle=$this->libelle;
        	$libelleshort=dolibarr_trunc($this->libelle,$maxlength);
        }
		
		if ($withpicto) $result.=($lien.img_object($langs->trans("ShowTask").': '.$libelle,'task').$lienfin.' ');
		$result.=$lien.$libelleshort.$lienfin;
		return $result;
	}

}

?>
