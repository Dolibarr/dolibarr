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
 *	\file       htdocs/core/modules/mailings/xinputfile.modules.php
 *	\ingroup    mailing
 *	\brief      File of class to offer a selector of emailing targets with Rule 'xinputfile'.
 */
include_once DOL_DOCUMENT_ROOT.'/core/modules/mailings/modules_mailings.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';


/**
 *	Class to offer a selector of emailing targets with Rule 'xinputfile'.
 */
class mailing_xinputfile extends MailingTargets
{
    public $name = 'EmailsFromFile'; // Identifiant du module mailing
    // This label is used if no translation is found for key XXX neither MailingModuleDescXXX where XXX=name is found
    public $desc = 'EMails from a file'; // Libelle utilise si aucune traduction pour MailingModuleDescXXX ou XXX=name trouv�e
    public $require_module = array(); // Module mailing actif si modules require_module actifs
    public $require_admin = 0; // Module mailing actif pour user admin ou non

    /**
     * @var string String with name of icon for myobject. Must be the part after the 'object_' into object_myobject.png
     */
    public $picto = 'generic';
    public $tooltip = 'UseFormatFileEmailToTarget';


    /**
     *  Constructor
     *
     *  @param      DoliDB      $db      Database handler
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
	 *  @param      string	$sql        Sql request to count
	 *	@return		string				'' means NA
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
		global $langs;
		return $langs->trans('LineInFile', $id);
		//' - '.$langs->trans("File").' '.dol_trunc(,12);
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
		$s .= '<input type="file" name="username" class="flat">';
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

		// For compatibility with Unix, MS-Dos or Macintosh
		ini_set('auto_detect_line_endings', true);

		$cibles = array();

		$upload_dir = $conf->mailing->dir_temp;

		if (dol_mkdir($upload_dir) >= 0)
		{
			$resupload = dol_move_uploaded_file($_FILES['username']['tmp_name'], $upload_dir."/".$_FILES['username']['name'], 1, 0, $_FILES['username']['error']);
			if (is_numeric($resupload) && $resupload > 0)
			{
				$cpt = 0;

				$file = $upload_dir."/".$_FILES['username']['name'];
				$handle = @fopen($file, "r");
				if ($handle)
				{
					$i = 0;
		            $j = 0;

            		$old = '';
					while (!feof($handle))
					{
						$cpt++;
				        $buffer = trim(fgets($handle));
			        	$tab = explode(';', $buffer, 4);
				        $email = $tab[0];
				        $name = $tab[1];
				        $firstname = $tab[2];
				        $other = $tab[3];
				        if (!empty($buffer))
				        {
			        		//print 'xx'.dol_strlen($buffer).empty($buffer)."<br>\n";
				        	$id = $cpt;
					        if (isValidEMail($email))
					        {
		   						if ($old <> $email)
								{
									$cibles[$j] = array(
					                    			'email' => $email,
					                    			'lastname' => $name,
					                    			'firstname' => $firstname,
													'other' => $other,
                                                    'source_url' => '',
                                                    'source_id' => '',
                                                    'source_type' => 'file'
									);
									$old = $email;
									$j++;
								}
					        }
					        else
					        {
					        	$i++;
					        	$langs->load("errors");
                                $msg = $langs->trans("ErrorFoundBadEmailInFile", $i, $cpt, $email);
					        	if (!empty($msg)) $this->error = $msg;
                                else $this->error = 'ErrorFoundBadEmailInFile '.$i.' '.$cpt.' '.$email; // We experience case where $langs->trans return an empty string.
					        }
				        }
				    }
				    fclose($handle);

				    if ($i > 0)
				    {
				    	return -$i;
				    }
				}
				else
				{
					$this->error = $langs->trans("ErrorFaildToOpenFile");
					return -1;
				}

				dol_syslog(get_class($this)."::add_to_target mailing ".$cpt." targets found");
			}
			else
			{
				$langs->load("errors");
				if ($resupload < 0)	// Unknown error
				{
					$this->error = '<div class="error">'.$langs->trans("ErrorFileNotUploaded").'</div>';
				}
				elseif (preg_match('/ErrorFileIsInfectedWithAVirus/', $resupload))	// Files infected by a virus
				{
					$this->error = '<div class="error">'.$langs->trans("ErrorFileIsInfectedWithAVirus").'</div>';
				}
				else	// Known error
				{
					$this->error = '<div class="error">'.$langs->trans($resupload).'</div>';
				}
			}
		}

		ini_set('auto_detect_line_endings', false);

		return parent::addTargetsToDatabase($mailing_id, $cibles);
	}
}
