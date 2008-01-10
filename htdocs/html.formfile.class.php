<?php
/* Copyright (c) 2008 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *
 * $Id$
 */

/**
        \file       htdocs/html.formfile.class.php
        \brief      Fichier de la classe des fonctions prédéfinie de composants html fichiers
*/


/**
        \class      FormFile
        \brief      Classe permettant la génération de composants html fichiers
*/
class FormFile
{
	var $db;
	var $error;
	

	/**
	*		\brief     Constructeur
	*		\param     DB      handler d'accès base de donnée
	*/
	function FormFile($DB)
	{
		$this->db = $DB;
		
		return 1;
	}


	/**
	*    	\brief      Affiche formulaire ajout fichier
	*    	\param      url				Url
	*    	\param      titre			Titre zone
	*    	\param      addcancel		1=Ajoute un bouton 'Annuler'
	*		\return		int				<0 si ko, >0 si ok
	*/
	function form_attach_new_file($url,$titre='',$addcancel=0)
	{
		global $conf,$langs;
		
		if ($conf->upload != 0)
		{
			print "\n\n<!-- Start form attach new file -->\n";
			
			if (! $titre) $titre=$langs->trans("AttachANewFile");
			print_titre($titre);

			print '<form name="userfile" action="'.$url.'" enctype="multipart/form-data" method="POST">';
			
			print '<table width="100%" class="noborder">';
			print '<tr><td width="50%" valign="top">';
			
			$max=$conf->upload;							// En Kb
			$maxphp=@ini_get('upload_max_filesize');	// En inconnu
			if (eregi('m$',$maxphp)) $maxphp=$maxphp*1024;
			if (eregi('k$',$maxphp)) $maxphp=$maxphp;
			// Now $max and $maxphp are in Kb
			if ($maxphp > 0) $max=min($max,$maxphp);
			
			if ($conf->upload > 0)
			{
				print '<input type="hidden" name="max_file_size" value="'.($max*1024).'">';
			}
			print '<input class="flat" type="file" name="userfile" size="70">';
			print ' &nbsp; ';
			print '<input type="submit" class="button" name="sendit" value="'.$langs->trans("Upload").'">';
			
			if ($addcancel)
			{
				print ' &nbsp; ';
				print '<input type="submit" class="button" name="cancel" value="'.$langs->trans("Cancel").'">';
			}
			
			print ' ('.$langs->trans("MaxSize").': '.$max.' '.$langs->trans("Kb").')';
			
			print "</td></tr>";
			print "</table>";

			print '</form>';
			print '<br>';
			
			print "\n<!-- End form attach new file -->\n\n";
		}
		
		return 1;
	}	
}

?>
