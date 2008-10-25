<?php
/* Copyright (C) 2003      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2006 Regis Houssin        <regis@dolibarr.fr>
 * Copyright (C) 2007      Franky Van Liedekerke <franky.van.liedekerke@telenet.be>
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
 *	\file       htdocs/includes/modules/livraison/pdf/pdf_sirocco.modules.php
 *	\ingroup    livraison
 *	\brief      Fichier de la classe permettant de generer les bons de livraison au mod�le Sirocco
 *	\version    $Id$
 */

require_once(DOL_DOCUMENT_ROOT."/includes/modules/livraison/modules_livraison.php");


/**
 *	\class      pdf_sirocco
 *	\brief      Classe permettant de generer les bons de livraison au modele Sirocco
 */

class pdf_sirocco extends ModelePDFDeliveryOrder
{

	/**		\brief      Constructor
	 *		\param	    db	    Database handler
	 */
	function pdf_sirocco($db)
	{
		global $conf,$langs,$mysoc;
		
        $langs->load("main");
        $langs->load("bills");
		
        $this->db = $db;
		$this->name = "sirocco";
		$this->description = $langs->trans("DocumentModelSirocco");
		
		// Dimension page pour format A4
		$this->type = 'pdf';
		$this->page_largeur = 210;
		$this->page_hauteur = 297;
		$this->format = array($this->page_largeur,$this->page_hauteur);
		$this->marge_gauche=10;
		$this->marge_droite=10;
		$this->marge_haute=10;
		$this->marge_basse=10;

		// Recupere emmetteur
        $this->emetteur=$mysoc;
        if (! $this->emetteur->pays_code) $this->emetteur->pays_code=substr($langs->defaultlang,-2);    // Par defaut, si n'�tait pas d�fini
		
		$this->error = "";
	}


	/**		\brief      Renvoi derniere erreur
	 *		\return     string      Derniere erreur
	 */
	function pdferror()
	{
		return $this->error;
	}


	/**
		\brief      Fonction g�n�rant le bon de livraison sur le disque
		\param	    delivery		Object livraison � g�n�rer
		\param		outputlangs		Output language
		\return	    int         	1 if OK, <=0 if KO
	 */
	function write_file($delivery,$outputlangs='')
	{
		global $user,$conf,$langs;

		if (! is_object($outputlangs)) $outputlangs=$langs;
		// Force output charset to ISO, because, FPDF expect text encoded in ISO
		$outputlangs->charset_output=$outputlangs->character_set_client='ISO-8859-1';

		$outputlangs->load("main");
		$outputlangs->load("bills");
		$outputlangs->load("products");
		$outputlangs->load("deliveries");

		$outputlangs->setPhpLang();

		if ($conf->livraison_bon->dir_output)
		{
			// If $delivery is id instead of object
			if (! is_object($delivery))
			{
				$id = $delivery;
				$delivery = new Livraison($this->db);
				$delivery->fetch($id);
				$delivery->id = $id;
				if ($result < 0)
				{
					dolibarr_print_error($db,$delivery->error);
				}
			}

			$deliveryref = sanitizeFileName($delivery->ref);
			$dir = $conf->livraison_bon->dir_output;
			if (! eregi('specimen',$deliveryref)) $dir.= "/" . $deliveryref;
			$file = $dir . "/" . $deliveryref . ".pdf";

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
				// Protection et encryption du pdf
				if ($conf->global->PDF_SECURITY_ENCRYPTION)
				{
					$pdf=new FPDI_Protection('P','mm',$this->format);
					$pdfrights = array('print'); // Ne permet que l'impression du document
					$pdfuserpass = ''; // Mot de passe pour l'utilisateur final
					$pdfownerpass = NULL; // Mot de passe du proprietaire, cree aleatoirement si pas defini
					$pdf->SetProtection($pdfrights,$pdfuserpass,$pdfownerpass);
				}
				else
				{
					$pdf=new FPDI('P','mm',$this->format);
				}

				$pdf->Open();
				$pdf->AddPage();

				$pdf->SetTitle($delivery->ref);
				$pdf->SetSubject($outputlangs->transnoentities("DeliveryOrder"));
				$pdf->SetCreator("Dolibarr ".DOL_VERSION);
				$pdf->SetAuthor($user->fullname);

				$this->_pagehead($pdf, $delivery, $outputlangs);

				$pagenb = 1;
				$tab_top = 100;
				$tab_height = 140;

				$pdf->SetFillColor(220,220,220);

				$pdf->SetTextColor(0,0,0);
				$pdf->SetFont('Arial','', 10);

				$pdf->SetXY (10, $tab_top + 10 );

				$iniY = $pdf->GetY();
				$curY = $pdf->GetY();
				$nexY = $pdf->GetY();
				$nblignes = sizeof($delivery->lignes);

				for ($i = 0 ; $i < $nblignes ; $i++)
				{
					$curY = $nexY;

					// Description de la ligne produit
					$libelleproduitservice=dol_htmlentitiesbr($delivery->lignes[$i]->label,1);
					if ($delivery->lignes[$i]->description && $delivery->lignes[$i]->description!=$delivery->lignes[$i]->label)
					{
						if ($libelleproduitservice) $libelleproduitservice.="<br>";
						$libelleproduitservice.=dol_htmlentitiesbr($delivery->lignes[$i]->description,1);
					}
					// Si ligne associee a un code produit
					if ($delivery->lignes[$i]->fk_product)
					{
						$prodser = new Product($this->db);
						$prodser->fetch($delivery->lignes[$i]->fk_product);
						if ($prodser->ref)
						{
							$prefix_prodserv = "";
							if($prodser->isservice())
							{
								// Un service peur aussi etre livre
								$prefix_prodserv = $outputlangs->transnoentities("Service")." ";
							}
							else
							{
								$prefix_prodserv = $outputlangs->transnoentities("Product")." ";
							}
							$libelleproduitservice=$prefix_prodserv.$prodser->ref." - ".$libelleproduitservice;
						}
					}
					if ($delivery->lignes[$i]->date_start && $delivery->lignes[$i]->date_end)
					{
						// Affichage duree si il y en a une
						$libelleproduitservice.="<br>".dol_htmlentitiesbr("(".$outputlangs->transnoentities("From")." ".dolibarr_print_date($delivery->lignes[$i]->date_start)." ".$outputlangs->transnoentities("to")." ".dolibarr_print_date($delivery->lignes[$i]->date_end).")",1);
					}

					$pdf->SetXY (30, $curY );

					$pdf->MultiCell(100, 5, $libelleproduitservice, 0, 'J', 0);

					$nexY = $pdf->GetY();

					$pdf->SetXY (10, $curY );

					$pdf->MultiCell(20, 5, $delivery->lignes[$i]->ref, 0, 'C');

					// \TODO Field not yet saved in database
					//$pdf->SetXY (133, $curY );
					//$pdf->MultiCell(10, 5, $delivery->lignes[$i]->tva_tx, 0, 'C');

					$pdf->SetXY (145, $curY );
					$pdf->MultiCell(10, 5, $delivery->lignes[$i]->qty_shipped, 0, 'C');

					// \TODO Field not yet saved in database
					//$pdf->SetXY (156, $curY );
					//$pdf->MultiCell(18, 5, price($delivery->lignes[$i]->price), 0, 'R', 0);

					// \TODO Field not yet saved in database
					//$pdf->SetXY (174, $curY );
					//$total = price($delivery->lignes[$i]->price * $delivery->lignes[$i]->qty_shipped);
					//$pdf->MultiCell(26, 5, $total, 0, 'R', 0);

					$pdf->line(10, $curY, 200, $curY );

					if ($nexY > 240 && $i < $nblignes - 1)
					{
						$this->_tableau($pdf, $tab_top, $tab_height, $nexY, $outputlangs);
						$pdf->AddPage();
						$nexY = $iniY;
						$this->_pagehead($pdf, $delivery, $outputlangs);
						$pdf->SetTextColor(0,0,0);
						$pdf->SetFont('Arial','', 10);
					}
				}

				$this->_tableau($pdf, $tab_top, $tab_height, $nexY, $outputlangs);

				$pdf->Output($file);
				if (! empty($conf->global->MAIN_UMASK)) 
					@chmod($file, octdec($conf->global->MAIN_UMASK));
				
				return 1;
			}
		}
		else
		{
			$this->error=$langs->transnoentities("ErrorConstantNotDefined","LIVRAISON_OUTPUTDIR");
			return 0;
		}
	}

	/**
	 *   \brief      Affiche la grille des lignes de propales
	 *   \param      pdf     objet PDF
	 */
	function _tableau(&$pdf, $tab_top, $tab_height, $nexY, $outputlangs)
	{
		global $langs,$conf;
		$langs->load("main");
		$langs->load("bills");

		$pdf->SetFont('Arial','',11);

		$pdf->Text(30,$tab_top + 5,$langs->transnoentities("Designation"));

		//		$pdf->line(132, $tab_top, 132, $tab_top + $tab_height);
		//		$pdf->Text(134,$tab_top + 5,$langs->transnoentities("VAT"));

		$pdf->line(144, $tab_top, 144, $tab_top + $tab_height);
		$pdf->Text(147,$tab_top + 5,$langs->transnoentities("QtyShipped"));

		//		$pdf->line(156, $tab_top, 156, $tab_top + $tab_height);
		//		$pdf->Text(160,$tab_top + 5,$langs->transnoentities("PriceU"));

		//		$pdf->line(174, $tab_top, 174, $tab_top + $tab_height);
		//		$pdf->Text(187,$tab_top + 5,$langs->transnoentities("Total"));

		//      $pdf->Rect(10, $tab_top, 190, $nexY - $tab_top);
		$pdf->Rect(10, $tab_top, 190, $tab_height);


		$pdf->SetTextColor(0,0,0);
		$pdf->SetFont('Arial','',10);
		//		$titre = $langs->transnoentities("AmountInCurrency",$langs->transnoentities("Currency".$conf->monnaie));
		//		$pdf->Text(200 - $pdf->GetStringWidth($titre), 98, $titre);

	}

	/**
	 *   	\brief      Affiche en-tete propale
	 *   	\param      pdf     objet PDF
	 *   	\param      fac     objet propale
	 *      \param      showadress      0=non, 1=oui
	 */
	function _pagehead(&$pdf, $delivery, $outputlangs)
	{
		global $langs;

		$outputlangs->load("deliveries");
		$pdf->SetXY(10,5);
		if (defined("MAIN_INFO_SOCIETE_NOM"))
		{
			$pdf->SetTextColor(0,0,200);
			$pdf->SetFont('Arial','B',14);
			$pdf->MultiCell(76, 8, MAIN_INFO_SOCIETE_NOM, 0, 'L');
		}

		$pdf->SetTextColor(70,70,170);
		if (defined("FAC_PDF_ADRESSE"))
		{
			$pdf->SetFont('Arial','',12);
			$pdf->MultiCell(76, 5, FAC_PDF_ADRESSE);
		}
		if (defined("FAC_PDF_TEL"))
		{
			$pdf->SetFont('Arial','',10);
			$pdf->MultiCell(76, 5, "Tel : ".FAC_PDF_TEL);
		}
		if (defined("MAIN_INFO_SIREN"))
		{
			$pdf->SetFont('Arial','',10);
			$pdf->MultiCell(76, 5, "SIREN : ".MAIN_INFO_SIREN);
		}

		if (defined("FAC_PDF_INTITULE2"))
		{
			$pdf->SetXY(100,5);
			$pdf->SetFont('Arial','B',14);
			$pdf->SetTextColor(0,0,200);
			$pdf->MultiCell(100, 10, FAC_PDF_INTITULE2, '' , 'R');
		}
		/*
		 * Adresse Client
		 */
		$pdf->SetTextColor(0,0,0);
		$pdf->SetFont('Arial','B',12);
		$client = new Societe($this->db);
		/*
		 * if a delivery address is used, use that, else use the client address
		 */
		if ($commande->adresse_livraison_id>0)
		{
			$client->fetch_adresse_livraison($commande->adresse_livraison_id);
		}
		else
		{
			$client->fetch($delivery->socid);
		}
		$delivery->client = $client;

		$pdf->SetXY(102,42);
		$pdf->MultiCell(96,5, $delivery->client->nom);
		$pdf->SetFont('Arial','B',11);
		$pdf->SetXY(102,47);
		$pdf->MultiCell(96,5, $delivery->client->adresse . "\n" . $delivery->client->cp . " " . $delivery->client->ville);
		$pdf->rect(100, 40, 100, 40);


		$pdf->SetTextColor(200,0,0);
		$pdf->SetFont('Arial','B',12);
		$pdf->Text(11, 88, $outputlangs->transnoentities("Date")." : " . dolibarr_print_date($delivery->date_valid,"day"));
		$pdf->Text(11, 94, $outputlangs->transnoentities("DeliveryOrder")." ".$delivery->ref);

		$pdf->SetFont('Arial','B',9);
		$commande = new Commande ($this->db);
		if ($commande->fetch($delivery->commande_id) >0)
		{
			$pdf->Text(11, 98, $outputlangs->transnoentities("RefOrder")." ".$commande->ref);
		}
	}

	/**
	 *   \brief      Affiche le pied de page
	 *   \param      pdf     objet PDF
	 */
	function _pagefoot(&$pdf,$outputlangs)
	{
		return pdf_pagefoot($pdf,$outputlangs,'DELIVERY_FREE_TEXT',$this->emetteur,$this->marge_basse,$this->marge_gauche,$this->page_hauteur);
	}
}

?>
