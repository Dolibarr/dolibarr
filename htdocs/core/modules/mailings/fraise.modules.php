<?php
/* Copyright (C) 2005      Laurent Destailleur <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009 Regis Houssin       <regis@dolibarr.fr>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 * or see http://www.gnu.org/
 */

/**
 * \file       htdocs/core/modules/mailings/fraise.modules.php
 * \ingroup    mailing
 * \brief      File of class to generate target according to rule Fraise
 */

include_once DOL_DOCUMENT_ROOT.'/core/modules/mailings/modules_mailings.php';
include_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';


/**
 *	    \class      mailing_fraise
 *		\brief      Class to generate target according to rule Fraise
 */
class mailing_fraise extends MailingTargets
{
	// CHANGE THIS: Put here a name not already used
	var $name='FundationMembers';                    // Identifiant du module mailing
	// CHANGE THIS: Put here a description of your selector module.
	// This label is used if no translation found for key MailingModuleDescXXX where XXX=name is found
    var $desc='Foundation members with emails (by status)';
	// CHANGE THIS: Set to 1 if selector is available for admin users only
    var $require_admin=0;

    var $require_module=array('adherent');
    var $picto='user';

    var $db;


	/**
	 *	Constructor
	 *
	 *  @param		DoliDB		$db      Database handler
	 */
    function mailing_fraise($db)
    {
        $this->db=$db;
    }


    /**
	 *	On the main mailing area, there is a box with statistics.
	 *	If you want to add a line in this report you must provide an
	 *	array of SQL request that returns two field:
	 *	One called "label", One called "nb".
	 *
	 *	@return		array		Array with SQL requests
	 */
    function getSqlArrayForStats()
	{
        global $langs;

        $langs->load("members");

		// Array for requests for statistics board
	    $statssql=array();

        $statssql[0] ="SELECT '".$this->db->escape($langs->trans("FundationMembers"))."' as label, count(*) as nb";
		$statssql[0].=" FROM ".MAIN_DB_PREFIX."adherent where statut = 1";

		return $statssql;
	}


    /*
     *		\brief		Return here number of distinct emails returned by your selector.
     *					For example if this selector is used to extract 500 different
     *					emails from a text file, this function must return 500.
     *		\return		int
     */
    function getNbOfRecipients()
    {
        $sql  = "SELECT count(distinct(a.email)) as nb";
        $sql .= " FROM ".MAIN_DB_PREFIX."adherent as a";
        $sql .= " WHERE (a.email IS NOT NULL AND a.email != '')";

        // La requete doit retourner un champ "nb" pour etre comprise
        // par parent::getNbOfRecipients
        return parent::getNbOfRecipients($sql);
    }


    /**
     *   Affiche formulaire de filtre qui apparait dans page de selection des destinataires de mailings
     *
     *   @return     string      Retourne zone select
     */
    function formFilter()
    {
        global $langs;
        $langs->load("members");

        $form=new Form($this->db);

        $s='';
        $s.=$langs->trans("Status").': ';
        $s.='<select name="filter" class="flat">';
        $s.='<option value="none">&nbsp;</option>';
        $s.='<option value="-1">'.$langs->trans("MemberStatusDraft").'</option>';
        $s.='<option value="1a">'.$langs->trans("MemberStatusActiveShort").' ('.$langs->trans("MemberStatusPaidShort").')</option>';
        $s.='<option value="1b">'.$langs->trans("MemberStatusActiveShort").' ('.$langs->trans("MemberStatusActiveLateShort").')</option>';
        $s.='<option value="0">'.$langs->trans("MemberStatusResiliatedShort").'</option>';
        $s.='</select>';
        $s.='<br>';
        $s.=$langs->trans("DateEndSubscription").': &nbsp;';
        $s.=$langs->trans("After").' > '.$form->select_date(-1,'subscriptionafter',0,0,1,'fraise',1,0,1,0);
        $s.=' &nbsp; ';
        $s.=$langs->trans("Before").' < '.$form->select_date(-1,'subscriptionbefore',0,0,1,'fraise',1,0,1,0);

        return $s;
    }


    /**
     *  Renvoie url lien vers fiche de la source du destinataire du mailing
     *
     *  @param	int		$id		ID
     *  @return     string      Url lien
     */
    function url($id)
    {
        return '<a href="'.DOL_URL_ROOT.'/adherents/fiche.php?rowid='.$id.'">'.img_object('',"user").'</a>';
    }


    /**
     *  Ajoute destinataires dans table des cibles
     *
     *  @param	int		$mailing_id    	Id of emailing
     *  @param  array	$filtersarray   Param to filter sql request. Deprecated. Should use $_POST instead.
     *  @return int           			< 0 si erreur, nb ajout si ok
     */
    function add_to_target($mailing_id,$filtersarray=array())
    {
    	global $langs,$_POST;
		$langs->load("members");
        $langs->load("companies");

    	$cibles = array();
        $now=dol_now();

        $dateendsubscriptionafter=dol_mktime($_POST['subscriptionafterhour'],$_POST['subscriptionaftermin'],$_POST['subscriptionaftersec'],$_POST['subscriptionaftermonth'],$_POST['subscriptionafterday'],$_POST['subscriptionafteryear']);
        $dateendsubscriptionbefore=dol_mktime($_POST['subscriptionbeforehour'],$_POST['subscriptionbeforemin'],$_POST['subscriptionbeforesec'],$_POST['subscriptionbeforemonth'],$_POST['subscriptionbeforeday'],$_POST['subscriptionbeforeyear']);

        // La requete doit retourner: id, email, fk_contact, name, firstname
        $sql = "SELECT a.rowid as id, a.email as email, null as fk_contact, ";
        $sql.= " a.nom as name, a.prenom as firstname,";
        $sql.= " a.datefin, a.civilite, a.login, a.societe";	// Other fields
        $sql.= " FROM ".MAIN_DB_PREFIX."adherent as a";
        $sql.= " WHERE a.email IS NOT NULL";
        if (isset($_POST["filter"]) && $_POST["filter"] == '-1') $sql.= " AND a.statut=-1";
        if (isset($_POST["filter"]) && $_POST["filter"] == '1a') $sql.= " AND a.statut=1 AND a.datefin >= '".$this->db->idate($now)."'";
        if (isset($_POST["filter"]) && $_POST["filter"] == '1b') $sql.= " AND a.statut=1 AND (a.datefin IS NULL or a.datefin < '".$this->db->idate($now)."')";
        if (isset($_POST["filter"]) && $_POST["filter"] == '0')  $sql.= " AND a.statut=0";
        if ($dateendsubscriptionafter > 0)  $sql.=" AND datefin > '".$this->db->idate($dateendsubscriptionafter)."'";
        if ($dateendsubscriptionbefore > 0) $sql.=" AND datefin < '".$this->db->idate($dateendsubscriptionbefore)."'";
        $sql.= " ORDER BY a.email";
        //print $sql;

        // Add targets into table
        dol_syslog(get_class($this)."::add_to_target sql=".$sql);
        $result=$this->db->query($sql);
        if ($result)
        {
            $num = $this->db->num_rows($result);
            $i = 0;
            $j = 0;

            dol_syslog(get_class($this)."::add_to_target mailing ".$num." targets found");

            $old = '';
            while ($i < $num)
            {
                $obj = $this->db->fetch_object($result);
                if ($old <> $obj->email)
                {
                    $cibles[$j] = array(
                    			'email' => $obj->email,
                    			'fk_contact' => $obj->fk_contact,
                    			'name' => $obj->name,
                    			'firstname' => $obj->firstname,
                    			'other' =>
                                ($langs->transnoentities("Login").'='.$obj->login).';'.
                                ($langs->transnoentities("Civility").'='.($obj->civilite?$langs->transnoentities("Civility".$obj->civilite):'')).';'.
                                ($langs->transnoentities("DateEnd").'='.dol_print_date($this->db->jdate($obj->datefin),'day')).';'.
                                ($langs->transnoentities("Company").'='.$obj->societe),
                                'source_url' => $this->url($obj->id),
                                'source_id' => $obj->id,
                                'source_type' => 'member'
                    			);
                    $old = $obj->email;
                    $j++;
                }

                $i++;
            }
        }
        else
        {
            dol_syslog($this->db->error());
            $this->error=$this->db->error();
            return -1;
        }

        return parent::add_to_target($mailing_id, $cibles);
	}

}

?>
