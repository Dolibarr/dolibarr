<?php
/* Copyright (C) 2005 Laurent Destailleur <eldy@users.sourceforge.net>
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
 * or see http://www.gnu.org/
 */

/**
       	\file       htdocs/includes/modules/mailings/fraise.modules.php
		\ingroup    mailing
		\brief      Fichier de la classe permettant de générer la liste de destinataires Fraise
		\version    $Id$
*/

include_once DOL_DOCUMENT_ROOT.'/includes/modules/mailings/modules_mailings.php';


/**
	    \class      mailing_fraise
		\brief      Classe permettant de générer la liste des destinataires Fraise
*/

class mailing_fraise extends MailingTargets
{
    var $name='FundationMembers';                    // Identifiant du module mailing
    var $desc='Membres de l\'association';           // Libellé utilisé si aucune traduction pour MailingModuleDescXXX ou XXX=name trouvée
    var $require_module=array('adherent');  // Module mailing actif si modules require_module actifs
    var $require_admin=0;                   // Module mailing actif pour user admin ou non
    var $picto='user';

    var $db;


    function mailing_fraise($DB)
    {
        $this->db=$DB;
    }


	function getSqlArrayForStats()
	{
        global $langs;
        $langs->load("members");

		// Array for requests for statistics board
	    $statssql=array();

        $statssql[0] ="SELECT '".addslashes($langs->trans("FundationMembers"))."' as label, count(*) as nb";
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
        $sql .= " WHERE a.email IS NOT NULL";

        // La requete doit retourner un champ "nb" pour etre comprise
        // par parent::getNbOfRecipients
        return parent::getNbOfRecipients($sql);
    }


    /**
     *      \brief      Affiche formulaire de filtre qui apparait dans page de selection
     *                  des destinataires de mailings
     *      \return     string      Retourne zone select
     */
    function formFilter()
    {
        global $langs;
        $langs->load("members");

        $s='';
        $s.='<select name="filter" class="flat">';
        $s.='<option value="-1">'.$langs->trans("MemberStatusDraft").'</option>';
        $s.='<option value="1a">'.$langs->trans("MemberStatusActiveShort").' ('.$langs->trans("MemberStatusPayedShort").')</option>';
        $s.='<option value="1b">'.$langs->trans("MemberStatusActiveShort").' ('.$langs->trans("MemberStatusActiveLateShort").')</option>';
        $s.='<option value="0">'.$langs->trans("MemberStatusResiliatedShort").'</option>';
        $s.='</select>';
        return $s;
    }


    /**
     *      \brief      Renvoie url lien vers fiche de la source du destinataire du mailing
     *      \return     string      Url lien
     */
    function url($id)
    {
        return '<a href="'.DOL_URL_ROOT.'/adherents/fiche.php?rowid='.$id.'">'.img_object('',"user").'</a>';
    }


    /**
     *    \brief      Ajoute destinataires dans table des cibles
     *    \param      mailing_id    Id du mailing concerné
     *    \param      filterarray   Requete sql de selection des destinataires
     *    \return     int           < 0 si erreur, nb ajout si ok
     */
    function add_to_target($mailing_id,$filtersarray=array())
    {
    	global $langs;
		$langs->load("members");

    	$cibles = array();

        // La requete doit retourner: id, email, fk_contact, name, firstname
        $sql = "SELECT a.rowid as id, a.email as email, null as fk_contact, ";
        $sql.= " a.nom as name, a.prenom as firstname,";
        $sql.= " a.datefin";	// Other fields
        $sql.= " FROM ".MAIN_DB_PREFIX."adherent as a";
        $sql.= " WHERE a.email IS NOT NULL";
        foreach($filtersarray as $key)
        {
            if ($key == '-1') $sql.= " AND a.statut=-1";
            if ($key == '1a')  $sql.= " AND a.statut=1 AND datefin >= ".$this->db->idate(mktime());
            if ($key == '1b')  $sql.= " AND a.statut=1 AND datefin < ".$this->db->idate(mktime());
            if ($key == '0')  $sql.= " AND a.statut=0";
        }
        $sql.= " ORDER BY a.email";

        // Stocke destinataires dans cibles
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
                    			'other' => $obj->datefin?($langs->transnoentities("DateEnd").'='.dol_print_date($this->db->jdate($obj->datefin),'day')):'',
                    			'url' => $this->url($obj->id)
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
