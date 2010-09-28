<?php
/* Copyright (C) 2003-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2005 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Eric Seigne          <eric.seigne@ryxeo.com>
 * Copyright (C) 2005-2010 Regis Houssin        <regis@dolibarr.fr>
 * Copyright (C) 2006      Andre Cianfarani     <acianfa@free.fr>
 * Copyright (C) 2010      Juanjo Menent        <jmenent@2byte.es>
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
 *		\file       htdocs/includes/modules/supplier/order/modules_commandefournisseur.php
 *      \ingroup    commande
 *      \brief      File that contain parent class for supplier orders models
 *                  and parent class for supplier orders numbering models
 *      \version    $Id$
 */
require_once(DOL_DOCUMENT_ROOT.'/lib/pdf.lib.php');
require_once(DOL_DOCUMENT_ROOT.'/includes/fpdf/fpdfi/fpdi_protection.php');


/**
 *	\class      ModelePDFSuppliersOrders
 *	\brief      Parent class for supplier orders models
 */
class ModelePDFSuppliersOrders
{
	var $error='';


	/**
	 *      \brief      Return list of active generation modules
	 * 		\param		$db		Database handler
	 */
	function liste_modeles($db)
	{
		global $conf;

		$type='supplier_order';
		$liste=array();

		include_once(DOL_DOCUMENT_ROOT.'/lib/functions2.lib.php');
		$liste=getListOfModels($db,$type,'');

		return $liste;
	}

}



/**
 *	\class      ModeleNumRefSuppliersOrders
 *	\brief      Classe mere des modeles de numerotation des references de commandes fournisseurs
 */
class ModeleNumRefSuppliersOrders
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
		$langs->load("orders");
		return $langs->trans("NoDescription");
	}

	/**     \brief      Renvoi un exemple de num�rotation
	 *      \return     string      Example
	 */
	function getExample()
	{
		global $langs;
		$langs->load("orders");
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
 *		Create object on disk
 *		@param	    db  			objet base de donnee
 *		@param	    object			object supplier order
 *		@param	    model			force le modele a utiliser ('' to not force)
 *		@param		outputlangs		objet lang a utiliser pour traduction
 *      @return     int         	0 si KO, 1 si OK
 */
function supplier_order_pdf_create($db, $object, $model, $outputlangs)
{
	global $langs;
	$langs->load("suppliers");

	$dir = DOL_DOCUMENT_ROOT."/includes/modules/supplier/order/";

	// Positionne modele sur le nom du modele de commande fournisseur a utiliser
	if (! dol_strlen($model))
	{
		if (! empty($conf->global->COMMANDE_SUPPLIER_ADDON_PDF))
		{
			$model = $conf->global->COMMANDE_SUPPLIER_ADDON_PDF;
		}
		else
		{
			print $langs->trans("Error")." ".$langs->trans("Error_COMMANDE_SUPPLIER_ADDON_PDF_NotDefined");
			return 0;
		}
	}
	// Charge le modele
	$file = "pdf_".$model.".modules.php";
	if (file_exists($dir.$file))
	{
		$classname = "pdf_".$model;
		require_once($dir.$file);

		$obj = new $classname($db);

		// We save charset_output to restore it because write_file can change it if needed for
		// output format that does not support UTF8.
		$sav_charset_output=$outputlangs->charset_output;
		if ($obj->write_file($object,$outputlangs) > 0)
		{
			// on supprime l'image correspondant au preview
			supplier_order_delete_preview($db, $object->id);

			$outputlangs->charset_output=$sav_charset_output;
			return 1;
		}
		else
		{
			$outputlangs->charset_output=$sav_charset_output;
			dol_syslog("Erreur dans supplier_order_pdf_create");
			dol_print_error($db,$obj->error);
			return 0;
		}
	}
	else
	{
		print $langs->trans("Error")." ".$langs->trans("ErrorFileDoesNotExists",$dir.$file);
		return 0;
	}
}

/**
 * Enter description here...
 *
 * @param   $db
 * @param   $propalid
 * @return  int
 */
function supplier_order_delete_preview($db, $objectid)
{
	global $langs,$conf;

	$comfourn = new CommandeFournisseur($db,"",$objectid);
	$comfourn->fetch($objectid);
	$client = new Societe($db);
	$client->fetch($comfourn->socid);

	if ($conf->fournisseur->dir_output.'/commande')
	{
		$comfournref = dol_sanitizeFileName($comfourn->ref);
		$dir = $conf->commande->dir_output . "/" . $comfournref ;
		$file = $dir . "/" . $comfournref . ".pdf.png";

		if ( file_exists( $file ) && is_writable( $file ) )
		{
			if ( ! dol_delete_file($file) )
			{
				$this->error=$langs->trans("ErrorFailedToOpenFile",$file);
				return 0;
			}
		}
	}

	return 1;
}
?>
