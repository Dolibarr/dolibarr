<?php
/* Copyright (C) 2005-2012 Laurent Destailleur <eldy@users.sourceforge.net>
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
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 * or see https://www.gnu.org/
 */

/**
 *	\file       htdocs/core/modules/mailings/xinputuser.modules.php
 *	\ingroup    mailing
 *	\brief      File of class to offer a selector of emailing targets with Rule 'xinputuser'.
 */
include_once DOL_DOCUMENT_ROOT.'/core/modules/mailings/modules_mailings.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';


/**
 *	Class to offer a selector of emailing targets with Rule 'xinputuser'.
 */
class mailing_xinputuser extends MailingTargets
{
	public $name = 'EmailsFromUser'; // Identifiant du module mailing
	// This label is used if no translation is found for key XXX neither MailingModuleDescXXX where XXX=name is found
	public $desc = 'EMails input by user'; // Libelle utilise si aucune traduction pour MailingModuleDescXXX ou XXX=name trouv�e
	public $require_module = array(); // Module mailing actif si modules require_module actifs
	public $require_admin = 0; // Module mailing actif pour user admin ou non

	/**
	 * @var string String with name of icon for myobject. Must be the part after the 'object_' into object_myobject.png
	 */
	public $picto = 'generic';
	public $tooltip = 'UseFormatInputEmailToTarget';


	/**
	 *	Constructor
	 *
	 *  @param		DoliDB		$db      Database handler
	 */
    public function __construct($db)
	{
		$this->db = $db;
	}


    /**
	 *	On the main mailing area, there is a box with statistics.
	 *	If you want to add a line in this report you must provide an
	 *	array of SQL request that returns two field:
	 *	One called "label", One called "nb".
	 *
	 *	@return		array		Array with SQL requests
	 */
    public function getSqlArrayForStats()
	{
		global $langs;
		$langs->load("users");

		$statssql = array();
		return $statssql;
	}


	/**
	 *	Return here number of distinct emails returned by your selector.
	 *	For example if this selector is used to extract 500 different
	 *	emails from a text file, this function must return 500.
	 *
	 *  @param      string	$sql   	Sql request to count
	 *	@return		string			'' means NA
	 */
    public function getNbOfRecipients($sql = '')
	{
		return '';
	}


	/**
	 *  Renvoie url lien vers fiche de la source du destinataire du mailing
	 *
     *  @param	int		$id		ID
	 *  @return string      	Url lien
	 */
    public function url($id)
	{
		return '';
	}


	/**
	 *   Affiche formulaire de filtre qui apparait dans page de selection des destinataires de mailings
	 *
	 *   @return     string      Retourne zone select
	 */
    public function formFilter()
	{
		global $langs;

		$s = '';
		$s .= '<input type="text" name="xinputuser" class="flat minwidth300" value="'.GETPOST("xinputuser").'">';
		return $s;
	}

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Ajoute destinataires dans table des cibles
	 *
	 *  @param	int		$mailing_id    	Id of emailing
	 *  @return int           			< 0 si erreur, nb ajout si ok
	 */
    public function add_to_target($mailing_id)
	{
        // phpcs:enable
		global $conf, $langs, $_FILES;

		require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

		$tmparray = explode(';', GETPOST('xinputuser'));
		$email = $tmparray[0];
		$lastname = $tmparray[1];
		$firstname = $tmparray[2];
		$other = $tmparray[3];

		$cibles = array();
        if (!empty($email))
        {
			if (isValidEMail($email))
			{
				$cibles[] = array(
           			'email' => $email,
           			'lastname' => $lastname,
           			'firstname' => $firstname,
					'other' => $other,
                    'source_url' => '',
                    'source_id' => '',
                    'source_type' => 'file'
				);

				return parent::addTargetsToDatabase($mailing_id, $cibles);
			}
			else
			{
				$langs->load("errors");
				$this->error = $langs->trans("ErrorBadEMail", $email);
				return -1;
			}
		}
		else
		{
            $langs->load("errors");
            $this->error = $langs->trans("ErrorBadEmail", $email);
			return -1;
		}
	}
}
