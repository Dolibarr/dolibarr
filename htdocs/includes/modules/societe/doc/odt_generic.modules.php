<?php
/* Copyright (C) 2010 Laurent Destailleur <ely@users.sourceforge.net>

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
 *	\file       htdocs/includes/modules/societe/doc/odt_generic.modules.php
 *	\ingroup    project
 *	\brief      File of class to build ODT documents for third parties
 *	\author	    Laurent Destailleur
 *	\version    $Id$
 */

require_once(DOL_DOCUMENT_ROOT."/includes/modules/societe/modules_societe.class.php");
require_once(DOL_DOCUMENT_ROOT."/societe.class.php");
require_once(DOL_DOCUMENT_ROOT."/lib/company.lib.php");


/**
 *	\class      odt_generic
 *	\brief      Classe permettant de generer les projets au modele Baleine
 */
class odt_generic extends ModeleDocProjects
{
	var $emetteur;	// Objet societe qui emet

	/**
	 *		\brief  Constructor
	 *		\param	db		Database handler
	 */
	function odt_generic($db)
	{
		global $conf,$langs,$mysoc;

		$langs->load("main");
		$langs->load("companies");

		$this->db = $db;
		$this->name = "Generic ODT";
		$this->description = $langs->trans("DocumentModelOdt");

		// Dimension page pour format A4
		$this->type = 'odt';
		$this->page_largeur = 0;
		$this->page_hauteur = 0;
		$this->format = array($this->page_largeur,$this->page_hauteur);
		$this->marge_gauche=0;
		$this->marge_droite=0;
		$this->marge_haute=0;
		$this->marge_basse=0;

		// Recupere emmetteur
		$this->emetteur=$mysoc;
		if (! $this->emetteur->pays_code) $this->emetteur->pays_code=substr($langs->defaultlang,-2);    // Par defaut, si n'�tait pas d�fini
	}

	/**		\brief      Renvoi la description du module
	 *      \return     string      Texte descripif
	 */
	function info($langs)
	{
		global $conf,$langs;

		$langs->load("companies");
		$langs->load("errors");

		$form = new Form($db);

		$texte = $this->description.".<br>\n";
		$texte.= '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
		$texte.= '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
		$texte.= '<input type="hidden" name="action" value="setModuleOptions">';
		$texte.= '<input type="hidden" name="param1" value="COMPANY_ADDON_PDF_ODTPATH">';
		$texte.= '<table class="nobordernopadding" width="100%">';

		// List of directories area
		$texte.= '<tr><td>'.$langs->trans("ListOfDirectoriesForModelGenODT").' : ';

		$listofdir=explode(',',preg_replace('/\r\n/',',',$conf->global->COMPANY_ADDON_PDF_ODTPATH));
		foreach($listofdir as $tmpdir)
		{
			$tmpdir=preg_replace('/DOL_DATA_ROOT/',DOL_DATA_ROOT,$tmpdir);
			if (! is_dir($tmpdir)) $texte.=img_warning($langs->trans("ErrorDirNotFound",$tmpdir),0);
		}
		//var_dump($listofdir);

		$texte.= '<br>';

		$texte.= '<textarea class="flat" cols="80" name="value1">';
		$texte.=$conf->global->COMPANY_ADDON_PDF_ODTPATH;
		$texte.= '</textarea></td><td valign="top" rowspan="2">';
		$texte.= $langs->trans("ExampleOfDirectoriesForModelGen");
		$texte.= '</td>';
		$texte.= '</tr>';

		// Example
		$texte.= '<tr><td align="center">';
		$texte.= '<input type="submit" class="button" value="'.$langs->trans("Modify").'" name="Button">';
		$texte.= '</td>';
		$texte.= '</tr>';

		$texte.= '</table>';
		$texte.= '</form>';

		return $texte;
	}

	/**
	 *	\brief      Fonction generant le projet sur le disque
	 *	\param	    delivery		Object project a generer
	 *	\param		outputlangs		Lang output object
	 *	\return	    int         	1 if OK, <=0 if KO
	 */
	function write_file($object,$outputlangs)
	{
		global $user,$langs,$conf;

		if (! is_object($outputlangs)) $outputlangs=$langs;
		// Force output charset to ISO, because, FPDF expect text encoded in ISO
		$sav_charset_output=$outputlangs->charset_output;
		$outputlangs->charset_output='ISO-8859-1';

		$outputlangs->load("main");
		$outputlangs->load("dict");
		$outputlangs->load("companies");
		$outputlangs->load("projects");

		if ($conf->projet->dir_output)
		{
			// If $object is id instead of object
			if (! is_object($object))
			{
				$id = $object;
				$object = new Societe($this->db);
				$object->fetch($id);

				if ($result < 0)
				{
					dol_print_error($db,$object->error);
				}
			}

			$objectref = dol_sanitizeFileName($object->ref);
			$dir = $conf->projet->dir_output;
			if (! preg_match('/specimen/i',$objectref)) $dir.= "/" . $objectref;
			$file = $dir . "/" . $objectref . ".pdf";

			if (! file_exists($dir))
			{
				if (create_exdir($dir) < 0)
				{
					$this->error=$langs->transnoentities("ErrorCanNotCreateDir",$dir);
					return 0;
				}
			}

			if (file_exists($dir))
			{
				// $file




				if (! empty($conf->global->MAIN_UMASK))
				@chmod($file, octdec($conf->global->MAIN_UMASK));

				return 1;   // Pas d'erreur
			}
			else
			{
				$this->error=$langs->transnoentities("ErrorCanNotCreateDir",$dir);
				return 0;
			}
		}

		$this->error=$langs->transnoentities("ErrorConstantNotDefined","LIVRAISON_OUTPUTDIR");
		return 0;
	}

}

?>
