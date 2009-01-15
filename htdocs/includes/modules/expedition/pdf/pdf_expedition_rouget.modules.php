<?php
/* Copyright (C) 2005      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2005-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *	\file       htdocs/includes/modules/expedition/pdf/pdf_expedition_rouget.modules.php
 *	\ingroup    expedition
 *	\brief      Fichier de la classe permettant de generer les bordereaux envoi au mod�le Rouget
 *	\version    $Id$
 */

require_once DOL_DOCUMENT_ROOT."/includes/modules/expedition/pdf/ModelePdfExpedition.class.php";
require_once(DOL_DOCUMENT_ROOT."/lib/company.lib.php");


/**
 *	\class      pdf_expedition_dorade
 *	\brief      Classe permettant de generer les borderaux envoi au modele Rouget
 */
Class pdf_expedition_rouget extends ModelePdfExpedition
{
	var $emetteur;	// Objet societe qui emet


	/**
	 \brief  Constructeur
	 \param	db		Handler acc�s base de donn�e
	 */
	function pdf_expedition_rouget($db=0)
	{
		global $conf,$langs,$mysoc;

		$this->db = $db;
		$this->name = "rouget";
		$this->description = $langs->trans("DocumentModelSimple");

		$this->type = 'pdf';
		$this->page_largeur = 210;
		$this->page_hauteur = 297;
		$this->format = array($this->page_largeur,$this->page_hauteur);
		$this->marge_gauche=10;
		$this->marge_droite=10;
		$this->marge_haute=10;
		$this->marge_basse=10;

		$this->option_logo = 0;

		// Recupere emmetteur
		$this->emetteur=$mysoc;
		if (! $this->emetteur->pays_code) $this->emetteur->pays_code=substr($langs->defaultlang,-2);    // Par defaut, si n'�tait pas d�fini
	}

	/*
	 *   	\param      pdf     		Objet PDF
	 *   	\param      exp     		Objet expedition
	 *      \param      showadress      0=non, 1=oui
	 *      \param      outputlang		Objet lang cible
	 */
	function _pagehead(&$pdf, $exp, $showadress=1, $outputlangs)
	{
		global $conf;

		if ($conf->barcode->enabled)
		{
			$posx=105;
		}
		else
		{
			$posx=$this->marge_gauche+3;
		}

		$pdf->Rect($this->marge_gauche, $this->marge_haute, $this->page_largeur-$this->marge_gauche-$this->marge_droite, 30);

		if ($conf->barcode->enabled)
		{
			// TODO Build code bar with function writeBarCode of barcode module for sending ref $this->expe->ref
			//$pdf->SetXY($this->marge_gauche+3, $this->marge_haute+3);
			//$pdf->Image($logo,10, 5, 0, 24);
		}

		$pdf->SetDrawColor(128,128,128);

		$pdf->SetFont('Arial','', 14);
		$pdf->Text($posx, 16, $outputlangs->transnoentities("SendingSheet"));	// Bordereau expedition
		$pdf->Text($posx, 22, $outputlangs->transnoentities("Ref") ." : ".$this->expe->ref);
		$pdf->Text($posx, 28, $outputlangs->transnoentities("Date")." : ".dolibarr_print_date($this->expe->date,"%d %b %Y",false,$outputlangs));
		$pdf->Text($posx, 34, $outputlangs->transnoentities("Page")." : ".$pdf->PageNo() ."/{nb}", 0);

		if ($conf->barcode->enabled)
		{
			// TODO Build code bar with function writeBarCode of barcode module for sending ref $this->expe->ref
			//$pdf->SetXY($this->marge_gauche+3, $this->marge_haute+3);
			//$pdf->Image($logo,10, 5, 0, 24);
		}

		$pdf->SetFont('Arial','', 14);
		$pdf->Text($posx, 48, $outputlangs->transnoentities("Order"));
		$pdf->Text($posx, 54, $outputlangs->transnoentities("Ref") ." : ".$this->expe->commande->ref);
		$pdf->Text($posx, 60, $outputlangs->transnoentities("Date")." : ".dolibarr_print_date($this->expe->commande->date,"%d %b %Y"));
	}


	/**
	 *		\brief      Fonction g�n�rant le document sur le disque
	 *		\param	    obj				Objet expedition � g�n�rer (ou id si ancienne methode)
	 *		\param		outputlangs		Lang output object
	 * 	 	\return	    int     		1=ok, 0=ko
	 */
	function write_file(&$obj, $outputlangs)
	{
		global $user,$conf,$langs;

		if (! is_object($outputlangs)) $outputlangs=$langs;
		// Force output charset to ISO, because, FPDF expect text encoded in ISO
		$outputlangs->charset_output='ISO-8859-1';

		$outputlangs->load("main");
		$outputlangs->load("companies");
		$outputlangs->load("bills");
		$outputlangs->load("propal");
		$outputlangs->load("products");

		$outputlangs->setPhpLang();

		if ($conf->expedition_bon->dir_output)
		{
			$this->expe = $obj;

			// D�finition de $dir et $file
			if ($this->expe->specimen)
			{
				$dir = $conf->expedition_bon->dir_output;
				$file = $dir . "/SPECIMEN.pdf";
			}
			else
			{
				$expref = sanitizeFileName($this->expe->ref);
				$dir = $conf->expedition_bon->dir_output . "/" . $expref;
				$file = $dir . "/" . $expref . ".pdf";
			}

			if (! file_exists($dir))
			{
				if (create_exdir($dir) < 0)
				{
					$this->error=$outputlangs->transnoentities("ErrorCanNotCreateDir",$dir);
					return 0;
				}
			}

			if (file_exists($dir))
			{
				$pdf=new ModelePdfExpedition();
				//$this = new ModelePdfExpedition();
				//$this->expe = &$this->expe;

				$pdf->Open();
				$pdf->AliasNbPages();
				$pdf->AddPage();

				$pdf->SetTitle($outputlangs->convToOutputCharset($this->expe->ref));
				$pdf->SetSubject($outputlangs->transnoentities("Sending"));
				$pdf->SetCreator("Dolibarr ".DOL_VERSION);
				$pdf->SetAuthor($outputlangs->convToOutputCharset($user->fullname));
				$pdf->SetKeyWords($outputlangs->convToOutputCharset($fac->ref)." ".$outputlangs->transnoentities("Sending"));

				$pdf->SetMargins($this->marge_gauche, $this->marge_haute, $this->marge_droite);   // Left, Top, Right
				$pdf->SetAutoPageBreak(1,0);

				$this->_pagehead($pdf,$this->exp,0,$outputlangs);

				$pdf->SetFont('Arial','', 14);
				$pdf->SetTextColor(0,0,0);

				$tab_top = 90;
				$height_note = 200;
				$pdf->Rect($this->marge_gauche, 80, $this->page_largeur-$this->marge_gauche-$this->marge_droite, 210);
				$pdf->Rect($this->marge_gauche, $tab_top, $this->page_largeur-$this->marge_gauche-$this->marge_droite, $height_note);
				if ($this->barcode->enabled)
				{
					$this->posxdesc=$this->marge_gauche+35;
				}
				else
				{
					$this->posxdesc=$this->marge_gauche+1;
				}
				$this->tableau_top = 80;

				$pdf->SetFont('Arial','', 10);
				$curY = $this->tableau_top + 4;
				$pdf->writeHTMLCell(100, 3, 12,  $curY, $outputlangs->trans("Description"), 0, 0);
				$curY = $this->tableau_top + 4;
				$pdf->writeHTMLCell(30, 3, 140, $curY, $outputlangs->trans("QtyOrdered"), 0, 0);
				$curY = $this->tableau_top + 4;
				$pdf->writeHTMLCell(30, 3, 170, $curY, $outputlangs->trans("QtyToShip"), 0, 0);

				$this->expe->fetch_lines();
				for ($i = 0 ; $i < sizeof($this->expe->lignes) ; $i++)
				{
					$curY = $this->tableau_top + 14 + ($i * 7);

					if ($this->barcode->enabled)
					{
						$pdf->i25($this->marge_gauche+3, ($curY - 2), "000000".$this->expe->lignes[$i]->fk_product, 1, 8);
					}

					// Description de la ligne produit
					$libelleproduitservice=dol_htmlentitiesbr($this->expe->lignes[$i]->description,1);
					if ($this->expe->lignes[$i]->description && $this->expe->lignes[$i]->description!=$com->lignes[$i]->libelle)
					{
						if ($libelleproduitservice) $libelleproduitservice.="<br>";
						$libelleproduitservice.=dol_htmlentitiesbr($this->expe->lignes[$i]->description,1);
					}
					// Si ligne associ�e � un code produit
					if ($this->expe->lignes[$i]->fk_product)
					{
						$prodser = new Product($this->db);
						$prodser->fetch($this->expe->lignes[$i]->fk_product);

						// On ajoute la ref
						if ($prodser->ref)
						{
							$prefix_prodserv = "";
							if($prodser->isservice())
							$prefix_prodserv = $outputlangs->transnoentities("Service")." ";
							else
							$prefix_prodserv = $outputlangs->transnoentities("Product")." ";

							$libelleproduitservice=$prefix_prodserv.$outputlangs->convToOutputCharset($prodser->ref)." - ".$outputlangs->convToOutputCharset($libelleproduitservice);
						}

					}

					$pdf->SetFont('Arial','', 9);   // Dans boucle pour g�rer multi-page

					$pdf->writeHTMLCell(150, 3, $this->posxdesc, $curY, $outputlangs->convToOutputCharset($libelleproduitservice), 0, 1);

					$pdf->SetXY (160, $curY);
					$pdf->MultiCell(30, 3, $this->expe->lignes[$i]->qty_asked);

					$pdf->SetXY (186, $curY);
					$pdf->MultiCell(30, 3, $this->expe->lignes[$i]->qty_shipped);
				}
				$pdf->AliasNbPages();

				$pdf->Close();

				$pdf->Output($file);

				$langs->setPhpLang();	// On restaure langue session
				return 1;
			}
			else
			{
				$this->error=$langs->transnoentities("ErrorCanNotCreateDir",$dir);
				$langs->setPhpLang();	// On restaure langue session
				return 0;
			}
		}
		else
		{
			$this->error=$langs->transnoentities("ErrorConstantNotDefined","EXP_OUTPUTDIR");
			$langs->setPhpLang();	// On restaure langue session
			return 0;
		}
		$this->error=$langs->transnoentities("ErrorUnknown");
		$langs->setPhpLang();	// On restaure langue session
		return 0;   // Erreur par defaut
	}
}

?>
