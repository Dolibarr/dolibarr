<?php
/* Copyright (C) 2003      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2011 Regis Houssin        <regis@dolibarr.fr>
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
 *  \file       htdocs/includes/modules/propale/modules_propale.php
 *  \ingroup    propale
 *  \brief      Fichier contenant la classe mere de generation des propales en PDF
 *  			et la classe mere de numerotation des propales
 */

require_once(DOL_DOCUMENT_ROOT."/core/class/commondocgenerator.class.php");
require_once(DOL_DOCUMENT_ROOT."/compta/bank/class/account.class.php");   // Requis car utilise dans les classes qui heritent


/**
 *	\class      ModelePDFPropales
 *	\brief      Classe mere des modeles de propale
 */
abstract class ModelePDFPropales extends CommonDocGenerator
{
	var $error='';


	/**
	 *      Return list of active generation modules
	 * 		@param		$db		Database handler
	 */
	function liste_modeles($db)
	{
		global $conf;

		$type='propal';
		$liste=array();

		include_once(DOL_DOCUMENT_ROOT.'/lib/functions2.lib.php');
		$liste=getListOfModels($db,$type,'');

		return $liste;
	}
}


/**
 *	\class      ModeleNumRefPropales
 *	\brief      Classe mere des modeles de numerotation des references de propales
 */
abstract class ModeleNumRefPropales
{
	var $error='';

	/**     \brief     	Return if a module can be used or not
	 *      	\return		boolean     true if module can be used
	 */
	function isEnabled()
	{
		return true;
	}

	/**     \brief      Renvoi la description par defaut du modele de numerotation
	 *      \return     string      Texte descripif
	 */
	function info()
	{
		global $langs;
		$langs->load("propale");
		return $langs->trans("NoDescription");
	}

	/**     \brief      Renvoi un exemple de numerotation
	 *      \return     string      Example
	 */
	function getExample()
	{
		global $langs;
		$langs->load("propale");
		return $langs->trans("NoExample");
	}

	/**     \brief      Test si les numeros deja en vigueur dans la base ne provoquent pas de
	 *                  de conflits qui empechera cette numerotation de fonctionner.
	 *      \return     boolean     false si conflit, true si ok
	 */
	function canBeActivated()
	{
		return true;
	}

	/**     \brief      Renvoi prochaine valeur attribuee
	 *      \return     string      Valeur
	 */
	function getNextValue()
	{
		global $langs;
		return $langs->trans("NotAvailable");
	}

	/**     \brief      Renvoi version du module numerotation
	 *      	\return     string      Valeur
	 */
	function getVersion()
	{
		global $langs;
		$langs->load("admin");

		if ($this->version == 'development') return $langs->trans("VersionDevelopment");
		if ($this->version == 'experimental') return $langs->trans("VersionExperimental");
		if ($this->version == 'dolibarr') return DOL_VERSION;
		return $langs->trans("NotAvailable");
	}
}


/**
 *  Create a document onto disk accordign to template module.
 *
 * 	@param	    DoliDB		$db  			Database handler
 * 	@param	    Object		$object			Object proposal
 * 	@param	    string		$modele			Force model to use ('' to not force)
 * 	@param		Translate	$outputlangs	Object langs to use for output
 *  @param      int			$hidedetails    Hide details of lines
 *  @param      int			$hidedesc       Hide description
 *  @param      int			$hideref        Hide ref
 *  @param      HookManager	$hookmanager	Hook manager instance
 * 	@return     int         				0 if KO, 1 if OK
 */
function propale_pdf_create($db, $object, $modele, $outputlangs, $hidedetails=0, $hidedesc=0, $hideref=0, $hookmanager=false)
{
	global $conf,$user,$langs;
	$langs->load("propale");

	$dir = "/includes/modules/propale/";
	$srctemplatepath='';
	$modelisok=0;

	// Positionne le modele sur le nom du modele a utiliser
	if (! dol_strlen($modele))
	{
	    if (! empty($conf->global->PROPALE_ADDON_PDF))
	    {
	        $modele = $conf->global->PROPALE_ADDON_PDF;
	    }
	    else
	    {
	        $modele = 'azur';
	    }
	}

	// Positionne modele sur le nom du modele de propale a utiliser
	$file = "pdf_propale_".$modele.".modules.php";

	// On verifie l'emplacement du modele
	$file = dol_buildpath($dir.$file);

	if ($modele && file_exists($file)) $modelisok=1;

	// Si model pas encore bon
	if (! $modelisok)
	{
		if ($conf->global->PROPALE_ADDON_PDF) $modele = $conf->global->PROPALE_ADDON_PDF;
		$file = "pdf_propale_".$modele.".modules.php";
		// On verifie l'emplacement du modele
		$file = dol_buildpath($dir.$file);
		if (file_exists($file)) $modelisok=1;
	}

	// Si model pas encore bon
	if (! $modelisok && is_array($liste))
	{
		$liste=ModelePDFPropales::liste_modeles($db);
		$modele=key($liste);        // Renvoie premiere valeur de cle trouve dans le tableau
		$file = "pdf_propale_".$modele.".modules.php";
		$file = dol_buildpath($dir.$file);
		if (file_exists($file)) $modelisok=1;
	}


	// Charge le modele
	if ($modelisok)
	{
		$classname = "pdf_propale_".$modele;
		require_once($file);

		$obj = new $classname($db);

		// We save charset_output to restore it because write_file can change it if needed for
		// output format that does not support UTF8.
		$sav_charset_output=$outputlangs->charset_output;
		if ($obj->write_file($object, $outputlangs, $srctemplatepath, $hidedetails, $hidedesc, $hideref, $hookmanager) > 0)
		{
			$outputlangs->charset_output=$sav_charset_output;
			// on supprime l'image correspondant au preview
			propale_delete_preview($db, $object->id);

			// Appel des triggers
			include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
			$interface=new Interfaces($db);
			$result=$interface->run_triggers('PROPAL_BUILDDOC',$object,$user,$langs,$conf);
			if ($result < 0) { $error++; $this->errors=$interface->errors; }
			// Fin appel triggers

			return 1;
		}
		else
		{
			$outputlangs->charset_output=$sav_charset_output;
			dol_syslog("modules_propale::propale_pdf_create error");
			dol_print_error($db,$obj->error);
			return 0;
		}
	}
	else
	{
		if (! $conf->global->PROPALE_ADDON_PDF)
		{
			print $langs->trans("Error")." ".$langs->trans("Error_PROPALE_ADDON_PDF_NotDefined");
		}
		else
		{
			print $langs->trans("Error")." ".$langs->trans("ErrorFileDoesNotExists",$file);
		}
		return 0;
	}
}

/**
 *  Delete preview files
 * 	@param	    db  		objet base de donnee
 * 	@param	    propalid	id de la propal a effacer
 * 	@param      propalref   reference de la propal si besoin
 */
function propale_delete_preview($db, $propalid, $propalref='')
{
	global $langs,$conf;
    require_once(DOL_DOCUMENT_ROOT."/lib/files.lib.php");

	if (!$propalref)
	{
		$propal = new Propal($db,"",$propalid);
		$propal->fetch($propalid);
		$propalref = $propal->ref;
	}

	if ($conf->propale->dir_output)
	{
		$propalref = dol_sanitizeFileName($propalref);
		$dir = $conf->propale->dir_output . "/" . $propalref ;
		$file = $dir . "/" . $propalref . ".pdf.png";
		$multiple = $file . ".";

		if ( file_exists( $file ) && is_writable( $file ) )
		{
			if ( ! dol_delete_file($file,1) )
			{
				$this->error=$langs->trans("ErrorFailedToOpenFile",$file);
				return 0;
			}
		}
		else
		{
			for ($i = 0; $i < 20; $i++)
			{
				$preview = $multiple.$i;

				if ( file_exists( $preview ) && is_writable( $preview ) )
				{
					if ( ! unlink($preview) )
					{
						$this->error=$langs->trans("ErrorFailedToOpenFile",$preview);
						return 0;
					}
				}
			}
		}
	}

	return 1;
}
?>
