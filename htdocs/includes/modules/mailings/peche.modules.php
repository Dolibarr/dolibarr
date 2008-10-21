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
 \file       htdocs/includes/modules/mailings/peche.modules.php
 \ingroup    mailing
 \brief      Fichier de la classe permettant de g�n�rer la liste de destinataires Peche
 \version    $Id$
 */

include_once DOL_DOCUMENT_ROOT.'/includes/modules/mailings/modules_mailings.php';


/**
 \class      mailing_pomme
 \brief      Classe permettant de g�n�rer la liste des destinataires Pomme
 */

class mailing_peche extends MailingTargets
{
	var $name='EmailsFromFile';              // Identifiant du module mailing
	var $desc='EMails issus d\'un fichier';  // Libell� utilis� si aucune traduction pour MailingModuleDescXXX ou XXX=name trouv�e
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
		//' - '.$langs->trans("File").' '.dolibarr_trunc( ,12);
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
	 *    \param      mailing_id    Id du mailing concern�
	 *    \param      filterarray   Requete sql de selection des destinataires
	 *    \return     int           < 0 si erreur, nb ajout si ok
	 */
	function add_to_target($mailing_id,$filtersarray=array())
	{
		global $conf,$langs,$_FILES;
		
		$cibles = array();

		$upload_dir=$conf->mailings->dir_temp;
		
		// Save file
		if (! is_dir($upload_dir)) create_exdir($upload_dir);

		if (is_dir($upload_dir))
		{
			$result = dol_move_uploaded_file($_FILES['username']['tmp_name'], $upload_dir . "/" . $_FILES['username']['name'], 1);
			if ($result > 0)
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
			        	$tab=split(';',$buffer);
				        $email=$tab[0];
				        $name=$tab[1];
				        $firstname=$tab[2];
				        if (! empty($buffer))
				        {
			        		//print 'xx'.strlen($buffer).empty($buffer)."<br>\n";
				        	$id=$cpt;
					        if (ValidEMail($email))
					        {
		   						if ($old <> $email)
								{
									$cibles[$j] = array(
					                    			'email' => $email,
					                    			'name' => $name,
					                    			'firstname' => $firstname,
					                    			'url' => $this->url($id)
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
				
				dolibarr_syslog(get_class($this)."::add_to_target mailing ".$cpt." targets found");
			}
			else if ($result < 0)
			{
				// Echec transfert (fichier depassant la limite ?)
				$this->error = $langs->trans("ErrorFileNotUploaded");
				// print_r($_FILES);
				return -1;
			}
			else
			{
				// Fichier infecte par un virus
				$this->error = $langs->trans("ErrorFileIsInfectedWith",$result);
				return -1;
			}
		}

		return parent::add_to_target($mailing_id, $cibles);
	}

}

?>
