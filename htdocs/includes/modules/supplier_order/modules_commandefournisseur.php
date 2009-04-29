<?php
/* Copyright (C) 2003-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2005 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Eric Seigne          <eric.seigne@ryxeo.com>
 * Copyright (C) 2006      Andre Cianfarani     <acianfa@free.fr>
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
 *		\file       htdocs/includes/modules/supplier_order/modules_commandefournisseur.php
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

class ModelePDFSuppliersOrders extends FPDF
{
	var $error='';

	/**
	 \brief Renvoi le dernier message d'erreur de cr�ation de PDF de commande
	 */
	function pdferror()
	{
		return $this->error;
	}

	/**
	 *      \brief      Renvoi la liste des mod�les actifs
	 */
	function liste_modeles($db)
	{
		$type='supplier_order';
		$liste=array();
		$sql ="SELECT nom as id, nom as lib";
		$sql.=" FROM ".MAIN_DB_PREFIX."document_model";
		$sql.=" WHERE type = '".$type."'";

		$resql = $db->query($sql);
		if ($resql)
		{
			$num = $db->num_rows($resql);
			$i = 0;
			while ($i < $num)
			{
				$row = $db->fetch_row($resql);
				$liste[$row[0]]=$row[1];
				$i++;
			}
		}
		else
		{
			$this->error=$db->error();
			return -1;
		}
		return $liste;
	}

}



/**
 \class      ModeleNumRefSuppliersOrders
 \brief      Classe m�re des mod�les de num�rotation des r�f�rences de commandes fournisseurs
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

	/**     \brief      Renvoi la description par defaut du modele de num�rotation
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

	/**     \brief      Test si les num�ros d�j� en vigueur dans la base ne provoquent pas de
	 *                  de conflits qui empechera cette num�rotation de fonctionner.
	 *      \return     boolean     false si conflit, true si ok
	 */
	function canBeActivated()
	{
		return true;
	}

	/**     \brief      Renvoi prochaine valeur attribu�e
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
 *		\brief      Create object on disk
 *		\param	    db  			objet base de donn�e
 *		\param	    deliveryid		id object
 *		\param	    modele			force le modele � utiliser ('' to not force)
 *		\param		outputlangs		objet lang a utiliser pour traduction
 *      \return     int         	0 si KO, 1 si OK
 */
function supplier_order_pdf_create($db, $comid, $modele,$outputlangs)
{
	global $langs;
	$langs->load("suppliers");

	$dir = DOL_DOCUMENT_ROOT."//includes/modules/supplier_order/pdf/";

	// Positionne modele sur le nom du modele de commande fournisseur � utiliser
	if (! strlen($modele))
	{
		if (defined("COMMANDE_SUPPLIER_ADDON_PDF") && COMMANDE_SUPPLIER_ADDON_PDF)
		{
			$modele = COMMANDE_SUPPLIER_ADDON_PDF;
		}
		else
		{
			print $langs->trans("Error")." ".$langs->trans("Error_COMMANDE_SUPPLIER_ADDON_PDF_NotDefined");
			return 0;
		}
	}
	// Charge le modele
	$file = "pdf_".$modele.".modules.php";
	if (file_exists($dir.$file))
	{
		$classname = "pdf_".$modele;
		require_once($dir.$file);

		$obj = new $classname($db);

		// We save charset_output to restore it because write_file can change it if needed for
		// output format that does not support UTF8.
		$sav_charset_output=$outputlangs->charset_output;
		if ($obj->write_file($comid,$outputlangs) > 0)
		{
			// on supprime l'image correspondant au preview
			supplier_order_delete_preview($db, $comid);

			$outputlangs->charset_output=$sav_charset_output;
			return 1;
		}
		else
		{
			$outputlangs->charset_output=$sav_charset_output;
			dol_syslog("Erreur dans supplier_order_pdf_create");
			dol_print_error($db,$obj->pdferror());
			return 0;
		}
	}
	else
	{
		print $langs->trans("Error")." ".$langs->trans("ErrorFileDoesNotExists",$dir.$file);
		return 0;
	}
}
function supplier_order_delete_preview($db, $propalid)
{
	global $langs,$conf;

	$comfourn = new CommandeFournisseur($db,"",$propalid);
	$comfourn->fetch($propalid);
	$client = new Societe($db);
	$client->fetch($comfourn->socid);

	if ($conf->fournisseur->dir_commande)
	{
		$comfournref = dol_sanitizeFileName($comfourn->ref);
		$dir = $conf->commande->dir_output . "/" . $comfournref ;
		$file = $dir . "/" . $comfournref . ".pdf.png";

		if ( file_exists( $file ) && is_writable( $file ) )
		{
			if ( ! unlink($file) )
			{
				$this->error=$langs->trans("ErrorFailedToOpenFile",$file);
				return 0;
			}
		}
	}
}
?>
