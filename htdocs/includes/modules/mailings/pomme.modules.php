<?php
/* Copyright (C) 2005-2009 Laurent Destailleur <eldy@users.sourceforge.net>
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
       	\file       htdocs/includes/modules/mailings/pomme.modules.php
		\ingroup    mailing
		\brief      Fichier de la classe permettant de générer la liste de destinataires Pomme
		\version    $Id$
*/

include_once DOL_DOCUMENT_ROOT.'/includes/modules/mailings/modules_mailings.php';


/**
	    \class      mailing_pomme
		\brief      Classe permettant de générer la liste des destinataires Pomme
*/

class mailing_pomme extends MailingTargets
{
    var $name='DolibarrUsers';                      // Identifiant du module mailing
    var $desc='Utilisateurs de Dolibarr avec emails';  // Libellé utilisé si aucune traduction pour MailingModuleDescXXX ou XXX=name trouvée
    var $require_module=array();                    // Module mailing actif si modules require_module actifs
    var $require_admin=1;                           // Module mailing actif pour user admin ou non
    var $picto='user';

    var $db;


    function mailing_pomme($DB)
    {
        $this->db=$DB;
    }


	function getSqlArrayForStats()
	{
        global $langs;
        $langs->load("users");

	    $statssql=array();
        $sql = "SELECT '".$langs->trans("DolibarrUsers")."' as label, count(distinct(email)) as nb FROM ".MAIN_DB_PREFIX."user as u";
        $sql.= " WHERE u.email != ''"; // u.email IS NOT NULL est implicite dans ce test
        $statssql[0]=$sql;

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
        $sql  = "SELECT count(distinct(u.email)) as nb";
        $sql .= " FROM ".MAIN_DB_PREFIX."user as u";
        $sql .= " WHERE u.email != ''"; // u.email IS NOT NULL est implicite dans ce test

        // La requete doit retourner un champ "nb" pour etre comprise
        // par parent::getNbOfRecipients
        return parent::getNbOfRecipients($sql);
    }


    /**
     *      \brief      Renvoie url lien vers fiche de la source du destinataire du mailing
     *      \return     string      Url lien
     */
    function url($id)
    {
        return '<a href="'.DOL_URL_ROOT.'/user/fiche.php?id='.$id.'">'.img_object('',"user").'</a>';
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

        $cibles = array();

        // La requete doit retourner: id, email, fk_contact, name, firstname
        $sql = "SELECT u.rowid as id, u.email as email, null as fk_contact,";
        $sql.= " u.name as name, u.firstname as firstname, u.login, u.office_phone";
        $sql.= " FROM ".MAIN_DB_PREFIX."user as u";
        $sql.= " WHERE u.email != ''"; // u.email IS NOT NULL est implicite dans ce test
        $sql.= " ORDER BY u.email";

        // Stocke destinataires dans cibles
        $result=$this->db->query($sql);
        if ($result)
        {
            $num = $this->db->num_rows($result);
            $i = 0;
            $j = 0;

            dolibarr_syslog(get_class($this)."::add_to_target mailing ".$num." targets found");

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
                    			'other' => $langs->transnoentities("Login").'='.$obj->login.';'.$langs->transnoentities("PhonePro").'='.$obj->office_phone,
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
            dolibarr_syslog($this->db->error());
            $this->error=$this->db->error();
            return -1;
        }

        return parent::add_to_target($mailing_id, $cibles);
    }

}

?>
