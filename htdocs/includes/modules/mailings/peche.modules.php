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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 * or see http://www.gnu.org/
 */

/**
 *	\file       htdocs/includes/modules/mailings/peche.modules.php
 *	\ingroup    mailing
 *	\brief      File of class to offer a selector of emailing targets with Rule 'Peche'.
 */
include_once DOL_DOCUMENT_ROOT.'/includes/modules/mailings/modules_mailings.php';
require_once(DOL_DOCUMENT_ROOT."/lib/functions2.lib.php");


/**
 *	\class      mailing_peche
 *	\brief      Class to offer a selector of emailing targets with Rule 'Peche'.
 */
class mailing_peche extends MailingTargets
{
	var $name='EmailsFromFile';              // Identifiant du module mailing
	var $desc='EMails issus d\'un fichier';  // Libelle utilise si aucune traduction pour MailingModuleDescXXX ou XXX=name trouv�e
	var $require_module=array();             // Module mailing actif si modules require_module actifs
	var $require_admin=1;                    // Module mailing actif pour user admin ou non
	var $picto='generic';

	var $db;


	function mailing_peche($DB)
	{
		$this->db=$DB;
	}


	function getSqlArrayForStats()
	{
		global $langs;
		$langs->load("users");

		$statssql=array();
		return $statssql;
	}


	/*
	 *		\brief		Return here number of distinct emails returned by your selector.
	 *					For example if this selector is used to extract 500 different
	 *					emails from a text file, this function must return 500.
	 *		\return		int			'' means NA
	 */
	function getNbOfRecipients()
	{
		return '';
	}


	/**
	 *      \brief      Renvoie url lien vers fiche de la source du destinataire du mailing
	 *      \return     string      Url lien
	 */
	function url($id)
	{
		global $langs;
		return $langs->trans('LineInFile',$id);
		//' - '.$langs->trans("File").' '.dol_trunc(,12);
	}


	/**
	 *      \brief      Affiche formulaire de filtre qui apparait dans page de selection
	 *                  des destinataires de mailings
	 *      \return     string      Retourne zone select
	 */
	function formFilter()
	{
		global $langs;

		$s='';
		$s.='<input type="file" name="username" class="flat">';
		return $s;
	}

	/**
	 *    \brief      Ajoute destinataires dans table des cibles
	 *    \param      mailing_id    Id of emailing
	 *    \param      filterarray   Requete sql de selection des destinataires
	 *    \return     int           < 0 si erreur, nb ajout si ok
	 */
	function add_to_target($mailing_id,$filtersarray=array())
	{
		global $conf,$langs,$_FILES;

		require_once(DOL_DOCUMENT_ROOT."/lib/files.lib.php");

		// For compatibility with Unix, MS-Dos or Macintosh
		ini_set('auto_detect_line_endings', true);

		$cibles = array();

		$upload_dir=$conf->mailing->dir_temp;

		if (create_exdir($upload_dir) >= 0)
		{
			$resupload = dol_move_uploaded_file($_FILES['username']['tmp_name'], $upload_dir . "/" . $_FILES['username']['name'], 1, 0, $_FILES['username']['error']);
			if (is_numeric($resupload) && $resupload > 0)
			{
				$cpt=0;

				//$mesg = '<div class="ok">'.$langs->trans("FileTransferComplete").'</div>';
				//print_r($_FILES);
				$file=$upload_dir . "/" . $_FILES['username']['name'];
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
			        	$tab=explode(';',$buffer,4);
				        $email=$tab[0];
				        $name=$tab[1];
				        $firstname=$tab[2];
				        $other=$tab[3];
				        if (! empty($buffer))
				        {
			        		//print 'xx'.dol_strlen($buffer).empty($buffer)."<br>\n";
				        	$id=$cpt;
					        if (isValidEMail($email))
					        {
		   						if ($old <> $email)
								{
									$cibles[$j] = array(
					                    			'email' => $email,
					                    			'name' => $name,
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
					        	$this->error = $langs->trans("ErrorFoundBadEmailInFile",$i,$cpt,$email);
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
				else if (preg_match('/ErrorFileIsInfectedWithAVirus/',$resupload))	// Files infected by a virus
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

		return parent::add_to_target($mailing_id, $cibles);
	}

}

?>
