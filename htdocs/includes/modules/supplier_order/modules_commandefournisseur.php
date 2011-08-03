<?php
/* Copyright (C) 2003-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2005 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Eric Seigne          <eric.seigne@ryxeo.com>
 * Copyright (C) 2005-2010 Regis Houssin        <regis@dolibarr.fr>
 * Copyright (C) 2006      Andre Cianfarani     <acianfa@free.fr>
 * Copyright (C) 2011      Philippe Grand       <philippe.grand@atoo-net.com>
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
 *		\file       htdocs/includes/modules/supplier_order/modules_commandefournisseur.php
 *      \ingroup    commande
 *      \brief      File that contain parent class for supplier orders models
 *                  and parent class for supplier orders numbering models
 *      \version    $Id: modules_commandefournisseur.php,v 1.22 2011/07/31 23:28:17 eldy Exp $
 */
require_once(DOL_DOCUMENT_ROOT.'/lib/pdf.lib.php');
require_once(DOL_DOCUMENT_ROOT.'/includes/fpdf/fpdfi/fpdi_protection.php');
require_once(DOL_DOCUMENT_ROOT."/compta/bank/class/account.class.php");	// requis car utilise par les classes qui heritent


/**
 *	\class      ModelePDFSuppliersOrders
 *	\brief      Parent class for supplier orders models
 */
class ModelePDFSuppliersOrders
{
	var $error='';


	/**
	 *  Return list of active generation modules
	 *  @param		$db		Database handler
	 */
	function liste_modeles($db)
	{
		global $conf;

		$type='order_supplier';
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

	/**  Return if a module can be used or not
	 *   @return		boolean     true if module can be used
	 */
	function isEnabled()
	{
		return true;
	}

	/**  Renvoie la description par defaut du modele de numerotation
	 *   @return     string      Texte descripif
	 */
	function info()
	{
		global $langs;
		$langs->load("orders");
		return $langs->trans("NoDescription");
	}

	/**   Renvoie un exemple de numerotation
	 *    @return     string      Example
	 */
	function getExample()
	{
		global $langs;
		$langs->load("orders");
		return $langs->trans("NoExample");
	}

	/**  Test si les numeros deja en vigueur dans la base ne provoquent pas de conflits qui empecheraient cette numerotation de fonctionner.
	 *   @return     boolean     false si conflit, true si ok
	 */
	function canBeActivated()
	{
		return true;
	}

	/**  Renvoie prochaine valeur attribuee
	 *   @return     string      Valeur
	 */
	function getNextValue()
	{
		global $langs;
		return $langs->trans("NotAvailable");
	}

	/**   Renvoie version du module numerotation
	 *    @return     string      Valeur
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
 *  Cree un bon de commande sur disque en fonction d'un modele
 *  @param	    db  			data base object
 *  @param	    object			object order
 *  @param	    modele			force le modele a utiliser ('' to not force)
 *  @param		outputlangs		objet lang a utiliser pour traduction
 *  @param      hidedetails     Hide details of lines
 *  @param      hidedesc        Hide description
 *  @param      hideref         Hide ref
 *  @return     int             0 if KO, 1 if OK
 */
function supplier_order_pdf_create($db, $object, $model, $outputlangs, $hidedetails=0, $hidedesc=0, $hideref=0)
{
	global $conf,$langs;
	$langs->load("suppliers");

	$dir = "/includes/modules/supplier_order/pdf/";
	$srctemplatepath='';
	$modelisok=0;
	$liste=array();

	// Positionne modele sur le nom du modele de commande fournisseur a utiliser
	$file = "pdf_".$model.".modules.php";
	// On verifie l'emplacement du modele
	$file = dol_buildpath($dir.$file);
	if ($model && file_exists($file))   $modelisok=1;

	// Si model pas encore bon
	if (! $modelisok)
	{
		if ($conf->global->COMMANDE_SUPPLIER_ADDON_PDF) $modele = $conf->global->COMMANDE_SUPPLIER_ADDON_PDF;
		$file = "pdf_".$model.".modules.php";
		// On verifie l'emplacement du modele
        $file = dol_buildpath($dir.$file);
		if (file_exists($file))   $modelisok=1;
	}
	
	// Si model pas encore bon
	if (! $modelisok)
	{
		$modele=new ModelePDFSuppliersOrders();
		$liste=$modele->liste_modeles($db);
		$modele=key($liste);        // Renvoie la premiere valeur de cle trouvee dans le tableau
		$file = "pdf_".$model.".modules.php";
		// On verifie l'emplacement du modele
        $file = dol_buildpath($dir.$file);
		if (file_exists($file))   $modelisok=1;
	}

	// Charge le modele
	if ($modelisok)
	{
		$classname = "pdf_".$model;
		require_once($file);

		$obj = new $classname($db);

		// We save charset_output to restore it because write_file can change it if needed for
		// output format that does not support UTF8.
		$sav_charset_output=$outputlangs->charset_output;
		if ($obj->write_file($object, $outputlangs, $srctemplatepath, $hidedetails, $hidedesc) > 0)
		{
			$outputlangs->charset_output=$sav_charset_output;
			// on supprime l'image correspondant au preview
			supplier_order_delete_preview($db, $object->id);		
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
		if (! $conf->global->COMMANDE_SUPPLIER_ADDON_PDF)
		{
			print $langs->trans("Error")." ".$langs->trans("Error_COMMANDE_SUPPLIER_ADDON_PDF_NotDefined");
		}
		else
		{
			print $langs->trans("Error")." ".$langs->trans("ErrorFileDoesNotExists",$dir.$file);
		}
		return 0;
	}
}

/**
 * Delete preview files, pour le cas de regeneration de commande
 * @param   $db		   data base object
 * @param   $comfournid  id de la commande a effacer
 * @param   $comfournref reference de la commande si besoin
 * @return  int
 */
function supplier_order_delete_preview($db, $comfournid, $comfournref='')
{
	global $langs,$conf;
    require_once(DOL_DOCUMENT_ROOT."/lib/files.lib.php");

	if (!$comfournref)
	{
		$comfourn = new CommandeFournisseur($db,"",$comfournid);
		$comfourn->fetch($comfournid);
		$comfournref = $comfourn->ref;
		$soc = new Societe($db);
		$soc->fetch($comfourn->socid);
	}
	
	

	if ($conf->fournisseur->dir_output.'/commande')
	{
		$suppordref = dol_sanitizeFileName($comfournref);
		$dir = $conf->fournisseur->dir_output . "/" . $suppordref ;
		$file = $dir . "/" . $suppordref . ".pdf.png";
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
					if ( ! dol_delete_file($preview,1) )
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
