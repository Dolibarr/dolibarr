<?php
/* Copyright (C) 2005-2011 Laurent Destailleur <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009 Regis Houssin       <regis.houssin@capnetworks.com>
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
 * or see http://www.gnu.org/
 */

/**
 *	\file       htdocs/core/modules/mailings/pomme.modules.php
 *	\ingroup    mailing
 *	\brief      File of class to offer a selector of emailing targets with Rule 'Pomme'.
 */
include_once DOL_DOCUMENT_ROOT.'/core/modules/mailings/modules_mailings.php';


/**
 *	\class      mailing_pomme
 *	\brief      Class to offer a selector of emailing targets with Rule 'Peche'.
 */
class mailing_pomme extends MailingTargets
{
	var $name='DolibarrUsers';                      // Identifiant du module mailing
	var $desc='Dolibarr users with emails';  		// Libelle utilise si aucune traduction pour MailingModuleDescXXX ou XXX=name trouv�e
	var $require_module=array();                    // Module mailing actif si modules require_module actifs
	var $require_admin=1;                           // Module mailing actif pour user admin ou non
	var $picto='user';

	var $db;


	/**
	 *	Constructor
	 *
	 *  @param		DoliDB		$db      Database handler
	 */
	function __construct($db)
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
		global $conf, $langs;

		$langs->load("users");

		$statssql=array();
		$sql = "SELECT '".$langs->trans("DolibarrUsers")."' as label,";
		$sql.= " count(distinct(u.email)) as nb";
		$sql.= " FROM ".MAIN_DB_PREFIX."user as u";
		$sql.= " WHERE u.email != ''"; // u.email IS NOT NULL est implicite dans ce test
		$sql.= " AND u.entity IN (0,".$conf->entity.")";
		$statssql[0]=$sql;

		return $statssql;
	}


    /**
     *	Return here number of distinct emails returned by your selector.
     *	For example if this selector is used to extract 500 different
     *	emails from a text file, this function must return 500.
     *
     *	@param	string	$sql		SQL request to use to count
     *	@return	int					Number of recipients
     */
	function getNbOfRecipients($sql='')
	{
		global $conf;

		$sql = "SELECT count(distinct(u.email)) as nb";
		$sql.= " FROM ".MAIN_DB_PREFIX."user as u";
		$sql.= " WHERE u.email != ''"; // u.email IS NOT NULL est implicite dans ce test
		$sql.= " AND u.entity IN (0,".$conf->entity.")";

		// La requete doit retourner un champ "nb" pour etre comprise
		// par parent::getNbOfRecipients
		return parent::getNbOfRecipients($sql);
	}


	/**
	 *  Affiche formulaire de filtre qui apparait dans page de selection des destinataires de mailings
	 *
	 *  @return     string      Retourne zone select
	 */
	function formFilter()
	{
		global $langs;

		$langs->load("users");

		$s='';
		$s.='<select name="filter" class="flat">';
		$s.='<option value="-1"></option>';
		$s.='<option value="1">'.$langs->trans("Enabled").'</option>';
		$s.='<option value="0">'.$langs->trans("Disabled").'</option>';
		$s.='</select>';
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
		return '<a href="'.DOL_URL_ROOT.'/user/fiche.php?id='.$id.'">'.img_object('',"user").'</a>';
	}


	/**
	 *  Ajoute destinataires dans table des cibles
	 *
	 *  @param	int		$mailing_id    	Id of emailing
	 *  @param  array	$filtersarray   Requete sql de selection des destinataires
	 *  @return int           			< 0 si erreur, nb ajout si ok
	 */
	function add_to_target($mailing_id,$filtersarray=array())
	{
		global $conf, $langs;

		$cibles = array();

		// La requete doit retourner: id, email, fk_contact, name, firstname
		$sql = "SELECT u.rowid as id, u.email as email, null as fk_contact,";
		$sql.= " u.lastname as name, u.firstname as firstname, u.login, u.office_phone";
		$sql.= " FROM ".MAIN_DB_PREFIX."user as u";
		$sql.= " WHERE u.email <> ''"; // u.email IS NOT NULL est implicite dans ce test
		$sql.= " AND u.entity IN (0,".$conf->entity.")";
		$sql.= " AND u.email NOT IN (SELECT email FROM ".MAIN_DB_PREFIX."mailing_cibles WHERE fk_mailing=".$mailing_id.")";
		foreach($filtersarray as $key)
		{
			if ($key == '1') $sql.= " AND u.statut=1";
			if ($key == '0') $sql.= " AND u.statut=0";
		}
		$sql.= " ORDER BY u.email";

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
                    			'lastname' => $obj->lastname,
                    			'firstname' => $obj->firstname,
                    			'other' =>
					            ($langs->transnoentities("Login").'='.$obj->login).';'.
//                                ($langs->transnoentities("UserTitle").'='.$obj->civilite).';'.
					            ($langs->transnoentities("PhonePro").'='.$obj->office_phone),
                                'source_url' => $this->url($obj->id),
                                'source_id' => $obj->id,
                                'source_type' => 'user'
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
