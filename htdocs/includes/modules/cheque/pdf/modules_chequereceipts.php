<?php
/* Copyright (C) 2003-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Eric Seigne          <eric.seigne@ryxeo.com>
 * Copyright (C) 2005-2009 Regis Houssin        <regis@dolibarr.fr>
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
 *	\file       htdocs/includes/modules/facture/modules_facture.php
 *	\ingroup    facture
 *	\brief      Fichier contenant la classe mere de generation des factures en PDF
 * 				et la classe mere de numerotation des factures
 *	\version    $Id$
 */

require_once(DOL_DOCUMENT_ROOT.'/lib/pdf.lib.php');
require_once(DOL_DOCUMENT_ROOT.'/includes/fpdf/fpdfi/fpdi_protection.php');
require_once(DOL_DOCUMENT_ROOT."/product/class/product.class.php");
require_once(DOL_DOCUMENT_ROOT."/compta/bank/class/account.class.php");   // Requis car utilise dans les classes qui heritent


/**
 *	\class      ModeleChequeReceipts
 *	\brief      Classe mere des modeles de facture
 */
class ModeleChequeReceipts
{
	var $error='';

	/**
	 *      \brief      Return list of active generation modules
	 * 		\param		$db		Database handler
	 */
	function liste_modeles($db)
	{
		global $conf;

		$type='chequereceipt';
		$liste=array();

		include_once(DOL_DOCUMENT_ROOT.'/lib/functions2.lib.php');
		$liste=getListOfModels($db,$type,'');
		// TODO Remove this to use getListOfModels only
		$liste = array('blochet'=>'blochet');

		return $liste;
	}
}


/**
 *	\brief   	Cree un bordereau remise de cheque
 *	\param   	db  			objet base de donnee
 *	\param   	id				Object invoice (or id of invoice)
 *	\param	    message			message
 *	\param	    modele			force le modele a utiliser ('' to not force)
 *	\param		outputlangs		objet lang a utiliser pour traduction
 *	\return  	int        		<0 if KO, >0 if OK
 * 	TODO
 */
function chequereceipt_pdf_create($db, $id, $message, $modele, $outputlangs)
{
	global $conf,$langs;
	$langs->load("bills");

	$dir = DOL_DOCUMENT_ROOT . "/includes/modules/cheque/pdf/";

	// Positionne modele sur le nom du modele a utiliser
	if (! strlen($modele))
	{
		if ($conf->global->FACTURE_ADDON_PDF)
		{
			$modele = $conf->global->FACTURE_ADDON_PDF;
		}
		else
		{
			//print $langs->trans("Error")." ".$langs->trans("Error_FACTURE_ADDON_PDF_NotDefined");
			//return 0;
			$modele = 'crabe';
		}
	}

	// Charge le modele
	$file = "pdf_".$modele.".modules.php";
	if (file_exists($dir.$file))
	{
		$classname = "pdf_".$modele;
		require_once($dir.$file);

		$obj = new $classname($db);
		$obj->message = $message;

		// We save charset_output to restore it because write_file can change it if needed for
		// output format that does not support UTF8.
		$sav_charset_output=$outputlangs->charset_output;
		if ($obj->write_file($id, $outputlangs) > 0)
		{
			if (! is_object($id))	// Old method
			{
				// Success in building document. We build meta file.
				facture_meta_create($db, $id);
				// et on supprime l'image correspondant au preview
				facture_delete_preview($db, $id);
			}
			else
			{
				// Success in building document. We build meta file.
				facture_meta_create($db, $id->id);
				// et on supprime l'image correspondant au preview
				facture_delete_preview($db, $id->id);
			}

			$outputlangs->charset_output=$sav_charset_output;
			return 1;
		}
		else
		{
			$outputlangs->charset_output=$sav_charset_output;
			dol_print_error($db,"facture_pdf_create Error: ".$obj->error);
			return -1;
		}

	}
	else
	{
		dol_print_error('',$langs->trans("Error")." ".$langs->trans("ErrorFileDoesNotExists",$dir.$file));
		return -1;
	}
}

/**
 *	\brief      Cree un meta fichier a cote de la facture sur le disque pour faciliter les recherches en texte plein.
 *              Pourquoi ? tout simplement parcequ'en fin d'exercice quand je suis avec mon comptable je n'ai pas de
 *              connexion internet "rapide" pour retrouver en 2 secondes une facture non payee ou compliquee a gerer ... avec un rgrep c'est vite fait bien fait [eric seigne]
 *	\param	    db  		Objet base de donnee
 *	\param	    facid		Id de la facture a creer
 *	\param      message     Message
 */
function facture_meta_create($db, $facid, $message="")
{
	global $langs,$conf;

	$fac = new Facture($db,"",$facid);
	$fac->fetch($facid);
	$fac->fetch_client();

	if ($conf->facture->dir_output)
	{
		$facref = dol_sanitizeFileName($fac->ref);
		$dir = $conf->facture->dir_output . "/" . $facref ;
		$file = $dir . "/" . $facref . ".meta";

		if (! is_dir($dir))
		{
			create_exdir($dir);
		}

		if (is_dir($dir))
		{
			$nblignes = sizeof($fac->lignes);
			$client = $fac->client->nom . " " . $fac->client->address . " " . $fac->client->cp . " " . $fac->client->ville;
			$meta = "REFERENCE=\"" . $fac->ref . "\"
			DATE=\"" . dol_print_date($fac->date,'') . "\"
			NB_ITEMS=\"" . $nblignes . "\"
			CLIENT=\"" . $client . "\"
			TOTAL_HT=\"" . $fac->total_ht . "\"
			TOTAL_TTC=\"" . $fac->total_ttc . "\"\n";

			for ($i = 0 ; $i < $nblignes ; $i++)
			{
				//Pour les articles
				$meta .= "ITEM_" . $i . "_QUANTITY=\"" . $fac->lignes[$i]->qty . "\"
				ITEM_" . $i . "_UNIT_PRICE=\"" . $fac->lignes[$i]->price . "\"
				ITEM_" . $i . "_TVA=\"" .$fac->lignes[$i]->tva_tx . "\"
				ITEM_" . $i . "_DESCRIPTION=\"" . str_replace("\r\n","",nl2br($fac->lignes[$i]->desc)) . "\"
				";
			}
		}
		$fp = fopen ($file,"w");
		fputs($fp,$meta);
		fclose($fp);
		if (! empty($conf->global->MAIN_UMASK))
		@chmod($file, octdec($conf->global->MAIN_UMASK));
	}
}


/**
 *	\brief      Supprime l'image de previsualitation, pour le cas de regeneration de facture
 *	\param	    db  		objet base de donnee
 *	\param	    facid		id de la facture a creer
 */
function facture_delete_preview($db, $facid)
{
	global $langs,$conf;

	$fac = new Facture($db,"",$facid);
	$fac->fetch($facid);

	if ($conf->facture->dir_output)
	{
		$facref = dol_sanitizeFileName($fac->ref);
		$dir = $conf->facture->dir_output . "/" . $facref ;
		$file = $dir . "/" . $facref . ".pdf.png";

		if ( file_exists( $file ) && is_writable( $file ) )
		{
			if ( ! dol_delete_file($file,1) )
			{
				return 0;
			}
		}
	}

	return 1;
}

?>