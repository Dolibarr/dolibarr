<?php
/* Copyright (C) 2010      Juanjo Menent        <jmenent@2byte.es>
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
 *		\file       htdocs/includes/modules/supplier_invoice/modules_facturefournisseur.php
 *      \ingroup    facture fourniseur
 *      \brief      File that contain parent class for supplier invoices models
 */
require_once(DOL_DOCUMENT_ROOT."/core/class/commondocgenerator.class.php");


/**
 *	\class      ModelePDFSuppliersInvoices
 *	\brief      Parent class for supplier invoices models
 */
abstract class ModelePDFSuppliersInvoices extends CommonDocGenerator
{
	var $error='';


	/**
	 *  Return list of active generation modules
	 *
	 * 	@param		DoliDB		$db		Database handler
	 */
	function liste_modeles($db)
	{
		global $conf;

		$type='invoice_supplier';
		$liste=array();

		include_once(DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php');
		$liste=getListOfModels($db,$type,'');

		return $liste;
	}

}

/**
 *	Create object on disk.
 *
 *	@param	    DoliDB		$db  			objet base de donnee
 *	@param	    Object		$object			object supplier invoice
 *	@param	    string		$model			force le modele a utiliser ('' to not force)
 *	@param		Translate	$outputlangs	objet lang a utiliser pour traduction
 *  @return     int         				0 if KO, 1 if OK
 */
function supplier_invoice_pdf_create($db, $object, $model, $outputlangs)
{
	global $conf, $langs;

	$langs->load("suppliers");

	$dir = DOL_DOCUMENT_ROOT."/includes/modules/supplier_invoice/pdf/";

	// Positionne modele sur le nom du modele de invoice fournisseur a utiliser
	if (! dol_strlen($model))
	{
		if (! empty($conf->global->INVOICE_SUPPLIER_ADDON_PDF))
		{
			$model = $conf->global->INVOICE_SUPPLIER_ADDON_PDF;
		}
		else
		{
		    $model = 'canelle';
			//print $langs->trans("Error")." ".$langs->trans("Error_INVOICE_SUPPLIER_ADDON_PDF_NotDefined");
			//return 0;
		}
	}
	// Charge le modele
	$file = "pdf_".$model.".modules.php";
	if (file_exists($dir.$file))
	{
		$classname = "pdf_".$model;
		require_once($dir.$file);

		$obj = new $classname($db,$object);

		// We save charset_output to restore it because write_file can change it if needed for
		// output format that does not support UTF8.
		$sav_charset_output=$outputlangs->charset_output;
		if ($obj->write_file($object,$outputlangs) > 0)
		{
			// on supprime l'image correspondant au preview
			supplier_invoice_delete_preview($db, $object->id);

			$outputlangs->charset_output=$sav_charset_output;
			return 1;
		}
		else
		{
			$outputlangs->charset_output=$sav_charset_output;
			dol_syslog("Erreur dans supplier_invoice_pdf_create");
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
 * Delete preview files
 * @param   $db
 * @param   $objectid
 * @return  int
 */
function supplier_invoice_delete_preview($db, $objectid)
{
	global $langs,$conf;
    require_once(DOL_DOCUMENT_ROOT."/lib/files.lib.php");

	$comfourn = new FactureFournisseur($db);
	$comfourn->fetch($objectid);
	$client = new Societe($db);
	$client->fetch($comfourn->socid);

	if ($conf->fournisseur->dir_output.'/facture')
	{
		$comfournref = dol_sanitizeFileName($comfourn->ref);
		$dir = $conf->facture->dir_output . "/" . $comfournref ;
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
