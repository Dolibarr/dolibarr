<?php
/* Copyright (C) 2003      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2007 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2008      Raphael Bertrand (Resultic)       <raphael.bertrand@resultic.fr>
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
 * 	\file       htdocs/includes/modules/propale/pdf_propale_jaune.modules.php
 *	\ingroup    propale
 *	\brief      Fichier de la classe permettant de g�n�rer les propales au mod�le Jaune
 *	\version    $Id$
 */

require_once(DOL_DOCUMENT_ROOT ."/includes/modules/propale/modules_propale.php");
require_once(DOL_DOCUMENT_ROOT."/lib/company.lib.php");


/**
 * 	    \class      pdf_propale_jaune
 *		\brief      Classe permettant de g�n�rer les propales au mod�le Jaune
 */
class pdf_propale_jaune extends ModelePDFPropales
{
	var $emetteur;	// Objet societe qui emet


	/**
	 * 		\brief  Constructeur
	 *		\param	db		Database access handler
	 */
	function pdf_propale_jaune($db=0)
	{
		global $conf,$langs,$mysoc;

		$langs->load("main");
		$langs->load("bills");
		
		$this->db = $db;
		$this->name = "jaune";
		$this->description = $langs->trans('DocModelJauneDescription');

		// Dimension page pour format A4
		$this->type = 'pdf';
		$this->page_largeur = 210;
		$this->page_hauteur = 297;
		$this->format = array($this->page_largeur,$this->page_hauteur);
		$this->marge_gauche=10;
		$this->marge_droite=10;
		$this->marge_haute=10;
		$this->marge_basse=10;

		$this->option_draft_watermark = 1;		   //Support add of a watermark on drafts

		$this->error = "";

		// Recupere emmetteur
		$this->emetteur=$mysoc;
		if (! $this->emetteur->pays_code) $this->emetteur->pays_code=substr($langs->defaultlang,-2);    // Par defaut, si on trouve pas
	}


	/**	\brief      Renvoi derni�re erreur
	 \return     string      Derni�re erreur
	 */
	function pdferror()
	{
		return $this->error;
	}


	/**
	 \brief      Fonction g�n�rant la propale sur le disque
	 \param	    propale			Objet propal
		\param		outputlangs		Lang object for output language
		\return	    int     		1=ok, 0=ko
		*/
	function write_file($propale,$outputlangs='')
	{
		global $user,$langs,$conf;

		if (! is_object($outputlangs)) $outputlangs=$langs;
		// Force output charset to ISO, because, FPDF expect text encoded in ISO
		$outputlangs->charset_output=$outputlangs->character_set_client='ISO-8859-1';
		
		$outputlangs->load("main");
		$outputlangs->load("dict");
		$outputlangs->load("companies");
		$outputlangs->load("bills");
		$outputlangs->load("propal");
		$outputlangs->load("products");

		$outputlangs->setPhpLang();

		if ($conf->propal->dir_output)
		{
			// D�finition de l'objet $propal (pour compatibilite ascendante)
			if (! is_object($propale))
			{
				$id = $propale;
				$propale = new Propal($this->db,"",$id);
				$ret=$propale->fetch($id);
			}

			// D�finition de $dir et $file
			if ($propale->specimen)
			{
				$dir = $conf->propal->dir_output;
				$file = $dir . "/SPECIMEN.pdf";
			}
			else
			{
				$propref = sanitize_string($propale->ref);
				$dir = $conf->propal->dir_output . "/" . $propref;
				$file = $dir . "/" . $propref . ".pdf";
			}

			if (! file_exists($dir))
			{
				if (create_exdir($dir) < 0)
				{
					$this->error=$langs->trans("ErrorCanNotCreateDir",$dir);
					return 0;
				}
			}

			if (file_exists($dir))
			{
				// Protection et encryption du pdf
				if ($conf->global->PDF_SECURITY_ENCRYPTION)
				{
					$pdf=new FPDI_Protection('P','mm',$this->format);
					$pdfrights = array('print'); // Ne permet que l'impression du document
					$pdfuserpass = ''; // Mot de passe pour l'utilisateur final
					$pdfownerpass = NULL; // Mot de passe du propri�taire, cr�� al�atoirement si pas d�fini
					$pdf->SetProtection($pdfrights,$pdfuserpass,$pdfownerpass);
				}
				else
				{
					$pdf=new FPDI('P','mm',$this->format);
				}

				$pdf->Open();
				$pdf->AddPage();
				
				$pdf->SetTitle($propale->ref);
				$pdf->SetSubject("Proposition commerciale");
				$pdf->SetCreator("Dolibarr ".DOL_VERSION);
				$pdf->SetAuthor($user->fullname);

				$pdf->SetMargins($this->marge_gauche, $this->marge_haute, $this->marge_droite);   // Left, Top, Right
				$pdf->SetAutoPageBreak(1,0);
				
				// Tete de page
				$this->_pagehead($pdf, $propale, $outputlangs);

				/*
				 */
				$tab_top = 100;
				$tab_height = 150;
				/*
				 *
				 */

				$pdf->SetFillColor(242,239,119);

				$pdf->SetTextColor(0,0,0);
				$pdf->SetFont('Arial','', 10);

				$pdf->SetXY (10, $tab_top + 10 );

				$iniY = $pdf->GetY();
				$curY = $pdf->GetY();
				$nexY = $pdf->GetY();
				$nblignes = sizeof($propale->lignes);

				for ($i = 0 ; $i < $nblignes ; $i++)
				{
					$curY = $nexY;

					$pdf->SetXY (30, $curY );
					$pdf->MultiCell(102, 5, $propale->lignes[$i]->desc, 0, 'J', 0);

					$nexY = $pdf->GetY();

					$pdf->SetXY (10, $curY );
					$pdf->MultiCell(20, 5, $propale->lignes[$i]->ref, 0, 'C', 0);

					$pdf->SetXY (132, $curY );
					$pdf->MultiCell(12, 5, $propale->lignes[$i]->tva_tx, 0, 'C', 0);

					$pdf->SetXY (144, $curY );
					$pdf->MultiCell(10, 5, $propale->lignes[$i]->qty, 0, 'C', 0);

					$pdf->SetXY (154, $curY );
					$pdf->MultiCell(22, 5, price($propale->lignes[$i]->price), 0, 'R', 0);

					$pdf->SetXY (176, $curY );
					$pdf->MultiCell(24, 5, price($propale->lignes[$i]->total_ht), 0, 'R', 0);

					$pdf->line(10, $curY, 200, $curY );

					if ($nexY > 240 && $i < $nblignes - 1)
					{
						$this->_pagefoot($pdf,$propale,$outputlangs);

						$this->_tableau($pdf, $tab_top, $tab_height, $nexY);
						$pdf->AddPage();
						$nexY = $iniY;
						$this->_pagehead($pdf, $propale, $outputlangs);
						$pdf->SetTextColor(0,0,0);
						$pdf->SetFont('Arial','', 10);
					}
				}

				$this->_tableau($pdf, $tab_top, $tab_height, $nexY);
				/*
				 *
				 */
				$tab2_top = 254;
				$tab2_lh = 7;
				$tab2_height = $tab2_lh * 3;

				$pdf->SetFont('Arial','', 11);

				$pdf->Rect(132, $tab2_top, 68, $tab2_height);

				$pdf->line(132, $tab2_top + $tab2_height - ($tab2_lh*3), 200, $tab2_top + $tab2_height - ($tab2_lh*3) );
				$pdf->line(132, $tab2_top + $tab2_height - ($tab2_lh*2), 200, $tab2_top + $tab2_height - ($tab2_lh*2) );
				$pdf->line(132, $tab2_top + $tab2_height - $tab2_lh, 200, $tab2_top + $tab2_height - $tab2_lh );

				$pdf->line(174, $tab2_top, 174, $tab2_top + $tab2_height);

				$pdf->SetXY (132, $tab2_top + 0);
				$pdf->MultiCell(42, $tab2_lh, $outputlangs->transnoentities("TotalHT"), 0, 'R', 0);

				$pdf->SetXY (132, $tab2_top + $tab2_lh);
				$pdf->MultiCell(42, $tab2_lh, $outputlangs->transnoentities("TotalVAT"), 0, 'R', 0);

				$pdf->SetXY (132, $tab2_top + ($tab2_lh*2));
				$pdf->MultiCell(42, $tab2_lh, $outputlangs->transnoentities("TotalTTC"), 1, 'R', 1);

				$pdf->SetXY (174, $tab2_top + 0);
				$pdf->MultiCell(26, $tab2_lh, price($propale->total_ht), 0, 'R', 0);

				$pdf->SetXY (174, $tab2_top + $tab2_lh);
				$pdf->MultiCell(26, $tab2_lh, price($propale->total_tva), 0, 'R', 0);

				$pdf->SetXY (174, $tab2_top + ($tab2_lh*2));
				$pdf->MultiCell(26, $tab2_lh, price($propale->total_ttc), 1, 'R', 1);

				// Pied de page
				$this->_pagefoot($pdf,$propale,$outputlangs);
				$pdf->AliasNbPages();

				$pdf->Close();

				$pdf->Output($file);

				$langs->setPhpLang();	// On restaure langue session
				return 1;
			}
		}
	}

	function _tableau(&$pdf, $tab_top, $tab_height, $nexY)
	{
		global $langs,$conf;
		$langs->load("main");
		$langs->load("bills");

		$pdf->SetFont('Arial','',11);

		$pdf->SetXY(10,$tab_top);
		$pdf->MultiCell(20,10,$langs->transnoentities("Ref"),0,'C',1);

		$pdf->SetXY(30,$tab_top);
		$pdf->MultiCell(102,10,$langs->transnoentities("Designation"),0,'L',1);

		$pdf->line(132, $tab_top, 132, $tab_top + $tab_height);
		$pdf->SetXY(132,$tab_top);
		$pdf->MultiCell(12, 10,$langs->transnoentities("VAT"),0,'C',1);

		$pdf->line(144, $tab_top, 144, $tab_top + $tab_height);
		$pdf->SetXY(144,$tab_top);
		$pdf->MultiCell(10,10,$langs->transnoentities("Qty"),0,'C',1);

		$pdf->line(154, $tab_top, 154, $tab_top + $tab_height);
		$pdf->SetXY(154,$tab_top);
		$pdf->MultiCell(22,10,$langs->transnoentities("PriceU"),0,'R',1);

		$pdf->line(176, $tab_top, 176, $tab_top + $tab_height);
		$pdf->SetXY(176,$tab_top);
		$pdf->MultiCell(24,10,$langs->transnoentities("Total"),0,'R',1);

		$pdf->Rect(10, $tab_top, 190, $tab_height);

		$pdf->SetTextColor(0,0,0);
		$pdf->SetFont('Arial','',10);
	}

	function _pagehead(&$pdf, $propale, $outputlangs)
	{
		//Affiche le filigrane brouillon - Print Draft Watermark
		if($propale->statut==0 && defined("PROPALE_DRAFT_WATERMARK") )
		{
			$watermark_angle=deg2rad(55);
			$watermark_x=5;
			$watermark_y=$this->page_hauteur-50;
			$watermark_width=300;
			$pdf->SetFont('Arial','B',50);
			$pdf->SetTextColor(255,192,203);
			//rotate
			$pdf->_out(sprintf('q %.5F %.5F %.5F %.5F %.2F %.2F cm 1 0 0 1 %.2F %.2F cm',cos($watermark_angle),sin($watermark_angle),-sin($watermark_angle),cos($watermark_angle),$watermark_x*$pdf->k,($pdf->h-$watermark_y)*$pdf->k,-$watermark_x*$pdf->k,-($pdf->h-$watermark_y)*$pdf->k));
			//print watermark
			$pdf->SetXY($watermark_x,$watermark_y);
			$pdf->Cell($watermark_width,25,clean_html(PROPALE_DRAFT_WATERMARK),0,2,"C",0);
			//antirotate
			$pdf->_out('Q');
		}

		//print Content

		$pdf->SetXY(12,42);
		if (defined("FAC_PDF_INTITULE"))
		{
	  $pdf->SetTextColor(0,0,200);
	  $pdf->SetFont('Arial','B',14);
	  $pdf->MultiCell(76, 8, FAC_PDF_INTITULE, 0, 'L');
		}

		$pdf->SetTextColor(70,70,170);
		if (defined("FAC_PDF_ADRESSE"))
		{
	  $pdf->SetX(12);
	  $pdf->SetFont('Arial','',12);
	  $pdf->MultiCell(76, 5, FAC_PDF_ADRESSE);
		}
		if (defined("FAC_PDF_TEL"))
		{
	  $pdf->SetX(12);
	  $pdf->SetFont('Arial','',10);
	  $pdf->MultiCell(76, 5, "T�l : ".FAC_PDF_TEL);
		}
		if (defined("MAIN_INFO_SIREN"))
		{
	  $pdf->SetX(12);
	  $pdf->SetFont('Arial','',10);
	  $pdf->MultiCell(76, 5, "SIREN : ".MAIN_INFO_SIREN);
		}
		$pdf->rect(10, 40, 80, 40);

		$pdf->SetXY(10,5);
		$pdf->SetFont('Arial','B',16);
		$pdf->SetTextColor(0,0,200);
		$pdf->MultiCell(200, 20, "PROPOSITION COMMERCIALE", '' , 'C');

		/*
		 * Adresse Client
		 */
		$pdf->SetTextColor(0,0,0);
		$pdf->SetFont('Arial','B',12);
		$propale->fetch_client();
		$pdf->SetXY(102,42);
		$pdf->MultiCell(96,5, $propale->client->nom);
		$pdf->SetFont('Arial','B',11);
		$pdf->SetXY(102,$pdf->GetY());
		$pdf->MultiCell(96,5, $propale->client->adresse . "\n" . $propale->client->cp . " " . $propale->client->ville);
		$pdf->rect(100, 40, 100, 40);

		$pdf->SetTextColor(200,0,0);
		$pdf->SetFont('Arial','B',12);

		$pdf->rect(10, 90, 100, 10);
		$pdf->rect(110, 90, 90, 10);

		$pdf->SetXY(10,90);
		$pdf->MultiCell(110, 10, $outputlangs->transnoentities("Ref")." : ".$propale->ref);
		$pdf->SetXY(110,90);
		$pdf->MultiCell(100, 10, $outputlangs->transnoentities("Date")." : " . dolibarr_print_date($propale->date,'day'));
	}

	/*
	 *   	\brief      Affiche le pied de page
	 *		\param      pdf     		Object PDF
	 * 		\param		object			Object proposal
	 */
	function _pagefoot(&$pdf,$object,$outputlangs)
	{
		return pdf_pagefoot($pdf,$outputlangs,'PROPALE_FREE_TEXT',$this->emetteur,$this->marge_basse,$this->marge_gauche,$this->page_hauteur);
	}

}
?>
