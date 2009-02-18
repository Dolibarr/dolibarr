<?php
/* Copyright (C) 2004-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2008 Regis Houssin        <regis@dolibarr.fr>
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
 *	\file       htdocs/includes/modules/supplier_order/pdf/pdf_muscadet.modules.php
 *	\ingroup    fournisseur
 *	\brief      Fichier de la classe permettant de g�n�rer les commandes fournisseurs au mod�le Muscadet
 *	\author	    Regis Houssin
 *	\version    $Id$
 */

require_once(DOL_DOCUMENT_ROOT."/includes/modules/supplier_order/modules_commandefournisseur.php");
require_once(DOL_DOCUMENT_ROOT."/fourn/fournisseur.commande.class.php");
require_once(DOL_DOCUMENT_ROOT."/product.class.php");
require_once(DOL_DOCUMENT_ROOT."/lib/company.lib.php");


/**
 *	\class      pdf_muscadet
 *	\brief      Classe permettant de g�n�rer les commandes fournisseurs au mod�le Muscadet
 */
class pdf_muscadet extends ModelePDFSuppliersOrders
{

	/**
	 *	\brief      Constructeur
	 *	\param	    db		Handler acc�s base de donn�e
	 */
	function pdf_muscadet($db)
	{
		global $conf,$langs,$mysoc;

		$langs->load("main");
		$langs->load("bills");

		$this->db = $db;
		$this->name = "muscadet";
		$this->description = "Modele de commandes fournisseur complet (logo...)";

		// Dimension page pour format A4
		$this->type = 'pdf';
		$this->page_largeur = 210;
		$this->page_hauteur = 297;
		$this->format = array($this->page_largeur,$this->page_hauteur);
		$this->marge_gauche=10;
		$this->marge_droite=10;
		$this->marge_haute=10;
		$this->marge_basse=10;

		$this->option_logo = 1;                    // Affiche logo
		$this->option_tva = 1;                     // Gere option tva FACTURE_TVAOPTION
		$this->option_modereg = 1;                 // Affiche mode r�glement
		$this->option_condreg = 1;                 // Affiche conditions r�glement
		$this->option_codeproduitservice = 1;      // Affiche code produit-service
		$this->option_multilang = 1;               // Dispo en plusieurs langues

		if (defined("FACTURE_TVAOPTION") && FACTURE_TVAOPTION == 'franchise')
		$this->franchise=1;

		// Recupere emmetteur
		$this->emetteur=$mysoc;
		if (! $this->emetteur->pays_code) $this->emetteur->pays_code=substr($langs->defaultlang,-2);    // Par defaut, si n'�tait pas d�fini

		// Defini position des colonnes
		$this->posxdesc=$this->marge_gauche+1;
		$this->posxtva=121;
		$this->posxup=132;
		$this->posxqty=151;
		$this->posxdiscount=162;
		$this->postotalht=177;

		$this->tva=array();
		$this->atleastoneratenotnull=0;
		$this->atleastonediscount=0;
	}

	/**
	 \brief      Renvoi derni�re erreur
	 \return     string      Derni�re erreur
	 */
	function pdferror()
	{
		return $this->error;
	}

	/**
	 * 	\brief      Fonction generant la commande sur le disque
	 * 	\param	    id	        	Id de la commande a generer
	 *	\param		outputlangs		Lang output object
	 *	\return	    int         	1=ok, 0=ko
	 */
	function write_file($com,$outputlangs='')
	{
		global $user,$langs,$conf;

		if (! is_object($outputlangs)) $outputlangs=$langs;
		// Force output charset to ISO, because, FPDF expect text encoded in ISO
		$outputlangs->charset_output='ISO-8859-1';

		$outputlangs->load("main");
		$outputlangs->load("dict");
		$outputlangs->load("companies");
		$outputlangs->load("bills");
		$outputlangs->load("products");
		$outputlangs->load("orders");

		$outputlangs->setPhpLang();

		if ($conf->fournisseur->commande->dir_output)
		{
			// D�finition de l'objet $com (pour compatibilite ascendante)
			if (! is_object($com))
			{
				$id = $com;
				$com = new CommandeFournisseur($this->db);
				$ret=$com->fetch($id);
			}
			$deja_regle = "";

			// D�finition de $dir et $file
			if ($com->specimen)
			{
				$dir = $conf->fournisseur->commande->dir_output;
				$file = $dir . "/SPECIMEN.pdf";
			}
			else
			{
				$comref = sanitizeFileName($com->ref);
				$dir = $conf->fournisseur->commande->dir_output . "/" . $comref;
				$file = $dir . "/" . $comref . ".pdf";
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
				$nblignes = sizeof($com->lignes);

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
				$pagenb=0;
				$pdf->SetDrawColor(128,128,128);

				$pdf->SetTitle($outputlangs->convToOutputCharset($com->ref));
				$pdf->SetSubject($outputlangs->transnoentities("Order"));
				$pdf->SetCreator("Dolibarr ".DOL_VERSION);
				$pdf->SetAuthor($outputlangs->convToOutputCharset($user->fullname));
				$pdf->SetKeyWords($outputlangs->convToOutputCharset($com->ref)." ".$outputlangs->transnoentities("Order"));
				if ($conf->global->MAIN_DISABLE_PDF_COMPRESSION) $pdf->SetCompression(false);

				$pdf->SetMargins($this->marge_gauche, $this->marge_haute, $this->marge_droite);   // Left, Top, Right
				$pdf->SetAutoPageBreak(1,0);

				// Positionne $this->atleastonediscount si on a au moins une remise
				for ($i = 0 ; $i < $nblignes ; $i++)
				{
					if ($com->lignes[$i]->remise_percent)
					{
						$this->atleastonediscount++;
					}
				}

				// New page
				$pdf->AddPage();
				$pagenb++;
				$this->_pagehead($pdf, $com, 1, $outputlangs);
				$pdf->SetFont('Arial','', 9);
				$pdf->MultiCell(0, 3, '', 0, 'J');		// Set interline to 3
				$pdf->SetTextColor(0,0,0);

				$tab_top = 90;
				$tab_top_newpage = 50;
				$tab_height = 110;
				$tab_height_newpage = 150;

				// Affiche notes
				if (! empty($com->note_public))
				{
					$tab_top = 88;

					$pdf->SetFont('Arial','', 9);   // Dans boucle pour g�rer multi-page
					$pdf->SetXY ($this->posxdesc-1, $tab_top);
					$pdf->MultiCell(190, 3, $outputlangs->convToOutputCharset($com->note_public), 0, 'J');
					$nexY = $pdf->GetY();
					$height_note=$nexY-$tab_top;

					// Rect prend une longueur en 3eme param
					$pdf->SetDrawColor(192,192,192);
					$pdf->Rect($this->marge_gauche, $tab_top-1, $this->page_largeur-$this->marge_gauche-$this->marge_droite, $height_note+1);

					$tab_height = $tab_height - $height_note;
					$tab_top = $nexY+6;
				}
				else
				{
					$height_note=0;
				}

				$iniY = $tab_top + 7;
				$curY = $tab_top + 7;
				$nexY = $tab_top + 7;

				// Boucle sur les lignes
				for ($i = 0 ; $i < $nblignes ; $i++)
				{
					$curY = $nexY;

					// Description de la ligne produit
					$libelleproduitservice=pdf_getlinedesc($com->lignes[$i],$outputlangs);

					$pdf->SetFont('Arial','', 9);   // Dans boucle pour g�rer multi-page

					$pdf->writeHTMLCell(108, 3, $this->posxdesc-1, $curY, $outputlangs->convToOutputCharset($libelleproduitservice), 0, 1);

					$pdf->SetFont('Arial','', 9);   // On repositionne la police par d�faut

					$nexY = $pdf->GetY();

					// TVA
					$pdf->SetXY ($this->posxtva, $curY);
					$pdf->MultiCell(10, 3, ($com->lignes[$i]->tva_tx < 0 ? '*':'').abs($com->lignes[$i]->tva_tx), 0, 'R');

					// Prix unitaire HT avant remise
					$pdf->SetXY ($this->posxup, $curY);
					$pdf->MultiCell(18, 3, price($com->lignes[$i]->subprice), 0, 'R', 0);

					// Quantity
					$pdf->SetXY ($this->posxqty, $curY);
					$pdf->MultiCell(10, 3, $com->lignes[$i]->qty, 0, 'R');

					// Remise sur ligne
					$pdf->SetXY ($this->posxdiscount, $curY);
					if ($com->lignes[$i]->remise_percent)
					{
						$pdf->MultiCell(14, 3, $com->lignes[$i]->remise_percent."%", 0, 'R');
					}

					// Total HT ligne
					$pdf->SetXY ($this->postotalht, $curY);
					$total = price($com->lignes[$i]->total_ht);
					$pdf->MultiCell(23, 3, $total, 0, 'R', 0);

					// Collecte des totaux par valeur de tva dans $this->tva["taux"]=total_tva
					$tvaligne=$com->lignes[$i]->total_tva;
					if ($com->remise_percent) $tvaligne-=($tvaligne*$com->remise_percent)/100;
					$vatrate=(string) $com->lignes[$i]->tva_tx;
					if ($com->lignes[$i]->info_bits & 0x01 == 0x01) $vatrate.='*';
					$this->tva[$vatrate] += $tvaligne;

					$nexY+=2;    // Passe espace entre les lignes

					// Test if a new page is required
					if ($pagenb == 1)
					{
						$tab_top_in_current_page=$tab_top;
						$tab_height_in_current_page=$tab_height;
					}
					else
					{
						$tab_top_in_current_page=$tab_top_newpage;
						$tab_height_in_current_page=$tab_height_newpage;
					}
					if (($nexY+$nblineFollowDesc) > ($tab_top_in_current_page+$tab_height_in_current_page) && $i < ($nblignes - 1))
					{
						if ($pagenb == 1)
						{
							$this->_tableau($pdf, $tab_top, $tab_height + 20, $nexY, $outputlangs);
						}
						else
						{
							$this->_tableau($pdf, $tab_top_newpage, $tab_height_newpage, $nexY, $outputlangs);
						}

						$this->_pagefoot($pdf, $outputlangs);

						// New page
						$pdf->AddPage();
						$pagenb++;
						$this->_pagehead($pdf, $com, 0, $outputlangs);
						$pdf->SetFont('Arial','', 9);
						$pdf->MultiCell(0, 3, '', 0, 'J');		// Set interline to 3
						$pdf->SetTextColor(0,0,0);

						$nexY = $tab_top_newpage + 7;
					}

				}

				// Show square
				if ($pagenb == 1)
				{
					$this->_tableau($pdf, $tab_top, $tab_height, $nexY, $outputlangs);
					$bottomlasttab=$tab_top + $tab_height + 1;
				}
				else
				{
					$this->_tableau($pdf, $tab_top_newpage, $tab_height_newpage, $nexY, $outputlangs);
					$bottomlasttab=$tab_top_newpage + $tab_height_newpage + 1;
				}

				$posy=$this->_tableau_tot($pdf, $com, $deja_regle, $bottomlasttab, $outputlangs);

				if ($deja_regle)
				{
					$this->_tableau_versements($pdf, $fac, $posy);
				}

				/*
				 * Mode de reglement
				 */
				if ((! defined("FACTURE_CHQ_NUMBER") || ! FACTURE_CHQ_NUMBER) && (! defined("FACTURE_RIB_NUMBER") || ! FACTURE_RIB_NUMBER))
				{
					$pdf->SetXY ($this->marge_gauche, 228);
					$pdf->SetTextColor(200,0,0);
					$pdf->SetFont('Arial','B',8);
					$pdf->MultiCell(90, 3, $outputlangs->transnoentities("ErrorNoPaiementModeConfigured"),0,'L',0);
					$pdf->MultiCell(90, 3, $outputlangs->transnoentities("ErrorCreateBankAccount"),0,'L',0);
					$pdf->SetTextColor(0,0,0);
				}

				/*
				 * Pied de page
				 */
				$this->_pagefoot($pdf, $outputlangs);
				$pdf->AliasNbPages();

				$pdf->Close();

				$pdf->Output($file);
				if (! empty($conf->global->MAIN_UMASK))
				@chmod($file, octdec($conf->global->MAIN_UMASK));

				return 1;   // Pas d'erreur
			}
			else
			{
				$this->error=$langs->trans("ErrorCanNotCreateDir",$dir);
				return 0;
			}
		}
		else
		{
			$this->error=$langs->trans("ErrorConstantNotDefined","PROP_OUTPUTDIR");
			return 0;
		}
		$this->error=$langs->trans("ErrorUnknown");
		return 0;   // Erreur par defaut
	}

	/**
	 *   \brief      Affiche le total � payer
	 *   \param      pdf         	Objet PDF
	 *   \param      object        	Objet order
	 *   \param      deja_regle  	Montant deja regle
	 *   \return     y              Position pour suite
	 */
	function _tableau_tot(&$pdf, $object, $deja_regle, $posy, $outputlangs)
	{
		$tab2_top = $posy;
		$tab2_hl = 5;
		$tab2_height = $tab2_hl * 4;
		$pdf->SetFont('Arial','', 9);

		// Affiche la mention TVA non applicable selon option
		$pdf->SetXY ($this->marge_gauche, $tab2_top + 0);
		if ($this->franchise==1)
		{
			$pdf->MultiCell(100, $tab2_hl, "* TVA non applicable art-293B du CGI", 0, 'L', 0);
		}

		// Tableau total
		$lltot = 200; $col1x = 120; $col2x = 182; $largcol2 = $lltot - $col2x;

		// Total HT
		$pdf->SetFillColor(255,255,255);
		$pdf->SetXY ($col1x, $tab2_top + 0);
		$pdf->MultiCell($col2x-$col1x, $tab2_hl, $outputlangs->transnoentities("TotalHT"), 0, 'L', 1);

		$pdf->SetXY ($col2x, $tab2_top + 0);
		$pdf->MultiCell($largcol2, $tab2_hl, price($object->total_ht + $object->remise), 0, 'R', 1);

		// Remise globale
		if ($object->remise > 0)
		{
			$pdf->SetXY ($col1x, $tab2_top + $tab2_hl);
			$pdf->MultiCell($col2x-$col1x, $tab2_hl, $outputlangs->transnoentities("GlobalDiscount"), 0, 'L', 1);

			$pdf->SetXY ($col2x, $tab2_top + $tab2_hl);
			$pdf->MultiCell($largcol2, $tab2_hl, "-".$object->remise_percent."%", 0, 'R', 1);

			$pdf->SetXY ($col1x, $tab2_top + $tab2_hl * 2);
			$pdf->MultiCell($col2x-$col1x, $tab2_hl, "Total HT apres remise", 0, 'L', 1);

			$pdf->SetXY ($col2x, $tab2_top + $tab2_hl * 2);
			$pdf->MultiCell($largcol2, $tab2_hl, price($object->total_ht), 0, 'R', 0);

			$index = 2;
		}
		else
		{
			$index = 0;
		}

		// Affichage des totaux de TVA par taux (conform�ment � r�glementation)
		$pdf->SetFillColor(248,248,248);

		foreach( $this->tva as $tvakey => $tvaval )
		{
			if ($tvakey)    // On affiche pas taux 0
			{
				$this->atleastoneratenotnull++;

				$index++;
				$pdf->SetXY ($col1x, $tab2_top + $tab2_hl * $index);

				$tvacompl='';

				if (eregi('\*',$tvakey))
				{
					$tvakey=eregi_replace('\*','',$tvakey);
					$tvacompl = " (".$outputlangs->transnoentities("NonPercuRecuperable").")";
				}

				$totalvat =$outputlangs->transnoentities("TotalVAT").' ';
				$totalvat.=vatrate($tvakey,1).$tvacompl;
				$pdf->MultiCell($col2x-$col1x, $tab2_hl, $totalvat, 0, 'L', 1);

				$pdf->SetXY ($col2x, $tab2_top + $tab2_hl * $index);
				$pdf->MultiCell($largcol2, $tab2_hl, price($tvaval), 0, 'R', 1);
			}
		}
		if (! $this->atleastoneratenotnull) // If not vat at all
		{
			$index++;
			$pdf->SetXY ($col1x, $tab2_top + $tab2_hl * $index);
			$pdf->MultiCell($col2x-$col1x, $tab2_hl, $outputlangs->transnoentities("TotalVAT"), 0, 'L', 1);
			$pdf->SetXY ($col2x, $tab2_top + $tab2_hl * $index);
			$pdf->MultiCell($largcol2, $tab2_hl, price($object->total_tva), 0, 'R', 1);
		}

		$useborder=0;

		$index++;
		$pdf->SetXY ($col1x, $tab2_top + $tab2_hl * $index);
		$pdf->SetTextColor(0,0,60);
		$pdf->SetFillColor(224,224,224);
		$pdf->MultiCell($col2x-$col1x, $tab2_hl, $outputlangs->transnoentities("TotalTTC"), $useborder, 'L', 1);

		$pdf->SetXY ($col2x, $tab2_top + $tab2_hl * $index);
		$pdf->MultiCell($largcol2, $tab2_hl, price($object->total_ttc), $useborder, 'R', 1);
		$pdf->SetFont('Arial','', 9);
		$pdf->SetTextColor(0,0,0);

		if ($deja_regle > 0)
		{
			$index++;

			$pdf->SetXY ($col1x, $tab2_top + $tab2_hl * $index);
			$pdf->MultiCell($col2x-$col1x, $tab2_hl, $outputlangs->transnoentities("AlreadyPayed"), 0, 'L', 0);

			$pdf->SetXY ($col2x, $tab2_top + $tab2_hl * $index);
			$pdf->MultiCell($largcol2, $tab2_hl, price($deja_regle), 0, 'R', 0);

			$index++;
			$pdf->SetTextColor(0,0,60);
			//$pdf->SetFont('Arial','B', 9);
			$pdf->SetXY ($col1x, $tab2_top + $tab2_hl * $index);
			$pdf->MultiCell($col2x-$col1x, $tab2_hl, $outputlangs->transnoentities("RemainderToPay"), $useborder, 'L', 1);

			$pdf->SetXY ($col2x, $tab2_top + $tab2_hl * $index);
			$pdf->MultiCell($largcol2, $tab2_hl, price($object->total_ttc - $deja_regle), $useborder, 'R', 1);
			$pdf->SetFont('Arial','', 9);
			$pdf->SetTextColor(0,0,0);
		}

		$index++;
		return ($tab2_top + ($tab2_hl * $index));
	}

	/**
	 *   \brief      Affiche la grille des lignes de propales
	 *   \param      pdf     objet PDF
	 */
	function _tableau(&$pdf, $tab_top, $tab_height, $nexY, $outputlangs)
	{
		global $conf;

		// Montants exprim�s en     (en tab_top - 1
		$pdf->SetTextColor(0,0,0);
		$pdf->SetFont('Arial','',8);
		$titre = $outputlangs->transnoentities("AmountInCurrency",$outputlangs->transnoentitiesnoconv("Currency".$conf->monnaie));
		$pdf->Text($this->page_largeur - $this->marge_droite - $pdf->GetStringWidth($titre), $tab_top-1, $titre);

		$pdf->SetDrawColor(128,128,128);

		// Rect prend une longueur en 3eme param
		$pdf->Rect($this->marge_gauche, $tab_top, $this->page_largeur-$this->marge_gauche-$this->marge_droite, $tab_height);
		// line prend une position y en 3eme param
		$pdf->line($this->marge_gauche, $tab_top+6, $this->page_largeur-$this->marge_droite, $tab_top+6);

		$pdf->SetFont('Arial','',10);

		$pdf->SetXY ($this->posxdesc-1, $tab_top+2);
		$pdf->MultiCell(108,2, $outputlangs->transnoentities("Designation"),'','L');

		$pdf->line($this->posxtva-1, $tab_top, $this->posxtva-1, $tab_top + $tab_height);
		$pdf->SetXY ($this->posxtva-1, $tab_top+2);
		$pdf->MultiCell(12,2, $outputlangs->transnoentities("VAT"),'','C');

		$pdf->line($this->posxup-1, $tab_top, $this->posxup-1, $tab_top + $tab_height);
		$pdf->SetXY ($this->posxup-1, $tab_top+2);
		$pdf->MultiCell(18,2, $outputlangs->transnoentities("PriceUHT"),'','C');

		$pdf->line($this->posxqty-1, $tab_top, $this->posxqty-1, $tab_top + $tab_height);
		$pdf->SetXY ($this->posxqty-1, $tab_top+2);
		$pdf->MultiCell(11,2, $outputlangs->transnoentities("Qty"),'','C');

		$pdf->line($this->posxdiscount-1, $tab_top, $this->posxdiscount-1, $tab_top + $tab_height);
		if ($this->atleastonediscount)
		{
			$pdf->SetXY ($this->posxdiscount-1, $tab_top+2);
			$pdf->MultiCell(16,2, $outputlangs->transnoentities("ReductionShort"),'','C');
		}

		if ($this->atleastonediscount)
		{
			$pdf->line($this->postotalht, $tab_top, $this->postotalht, $tab_top + $tab_height);
		}
		$pdf->SetXY ($this->postotalht-1, $tab_top+2);
		$pdf->MultiCell(23,2, $outputlangs->transnoentities("TotalHT"),'','C');

	}

	/**
	 *   	\brief      Show header of page
	 *    	\param      pdf     		Object PDF
	 *      \param      object          Object invoice
	 *      \param      showadress      0=no, 1=yes
	 *      \param      outputlang		Object lang for output
	 */
	function _pagehead(&$pdf, $object, $showadress=1, $outputlangs)
	{
		global $langs,$conf,$mysoc;

		$outputlangs->load("main");
		$outputlangs->load("bills");
		$outputlangs->load("orders");
		$outputlangs->load("companies");

		$pdf->SetTextColor(0,0,60);
		$pdf->SetFont('Arial','B',13);

		$posy=$this->marge_haute;

		$pdf->SetXY($this->marge_gauche,$posy);

		// Logo
		$logo=$conf->societe->dir_logos.'/'.$mysoc->logo;
		if ($mysoc->logo)
		{
			if (is_readable($logo))
			{
				$pdf->Image($logo, $this->marge_gauche, $posy, 0, 24);
			}
			else
			{
				$pdf->SetTextColor(200,0,0);
				$pdf->SetFont('Arial','B',8);
				$pdf->MultiCell(100, 3, $outputlangs->transnoentities("ErrorLogoFileNotFound",$logo), 0, 'L');
				$pdf->MultiCell(100, 3, $outputlangs->transnoentities("ErrorGoToModuleSetup"), 0, 'L');
			}
		}
		else $pdf->MultiCell(100, 4, $this->emetteur->nom, 0, 'L');

		$pdf->SetFont('Arial','B',13);
		$pdf->SetXY(100,$posy);
		$pdf->SetTextColor(0,0,60);
		$pdf->MultiCell(100, 4, $outputlangs->transnoentities("Order")." ".$outputlangs->convToOutputCharset($object->ref), '' , 'R');
		$pdf->SetFont('Arial','',12);

		$posy+=6;
		$pdf->SetXY(100,$posy);
		$pdf->SetTextColor(0,0,60);
		$pdf->MultiCell(100, 4, $outputlangs->transnoentities("Date")." : " . dol_print_date($object->date,"day",false,$outputlangs,true), '', 'R');

		if ($showadress)
		{
			// Emetteur
			$posy=42;
			$hautcadre=40;
			$pdf->SetTextColor(0,0,0);
			$pdf->SetFont('Arial','',8);
			$pdf->SetXY($this->marge_gauche,$posy-5);
			$pdf->MultiCell(66,5, $outputlangs->transnoentities("BillFrom").":");


			$pdf->SetXY($this->marge_gauche,$posy);
			$pdf->SetFillColor(230,230,230);
			$pdf->MultiCell(82, $hautcadre, "", 0, 'R', 1);



			// Nom emetteur
			$carac_emetteur_name=$outputlangs->convToOutputCharset($mysoc->nom);
			$pdf->SetTextColor(0,0,60);
			$pdf->SetFont('Arial','B',11);
			$pdf->SetXY($this->marge_gauche+2,$posy+3);
			$pdf->MultiCell(80, 4, $carac_emetteur_name, 0, 'L');

			// Caracteristiques emetteur
			$carac_emetteur = '';
			$carac_emetteur .= ($carac_emetteur ? "\n" : '' ).$outputlangs->convToOutputCharset($mysoc->adresse);
			$carac_emetteur .= ($carac_emetteur ? "\n" : '' ).$outputlangs->convToOutputCharset($mysoc->cp).' '.$outputlangs->convToOutputCharset($mysoc->ville);
			$carac_emetteur .= "\n";
			// Tel
			if ($mysoc->tel) $carac_emetteur .= ($carac_emetteur ? "\n" : '' ).$outputlangs->transnoentities("Phone").": ".$outputlangs->convToOutputCharset($mysoc->tel);
			// Fax
			if ($mysoc->fax) $carac_emetteur .= ($carac_emetteur ? "\n" : '' ).$outputlangs->transnoentities("Fax").": ".$outputlangs->convToOutputCharset($mysoc->fax);
			// EMail
			if ($mysoc->email) $carac_emetteur .= ($carac_emetteur ? "\n" : '' ).$outputlangs->transnoentities("Email").": ".$outputlangs->convToOutputCharset($mysoc->email);
			// Web
			if ($mysoc->url) $carac_emetteur .= ($carac_emetteur ? "\n" : '' ).$outputlangs->transnoentities("Web").": ".$outputlangs->convToOutputCharset($mysoc->url);

			$pdf->SetFont('Arial','',9);
			$pdf->SetXY($this->marge_gauche+2,$posy+8);
			$pdf->MultiCell(80,3, $carac_emetteur);

			// Client destinataire
			$posy=42;
			$pdf->SetTextColor(0,0,0);
			$pdf->SetFont('Arial','',8);
			$pdf->SetXY(100,$posy-5);
			$pdf->MultiCell(96,5, $outputlangs->transnoentities("BillTo").":");
			//
			$client = new Societe($this->db);
			$client->fetch($object->socid);
			$object->client = $client;
			//

			// Cadre client destinataire
			$pdf->rect(100, $posy, 100, $hautcadre);

			$carac_client_name = $outputlangs->convToOutputCharset($object->client->nom);

			$carac_client=$outputlangs->convToOutputCharset($object->client->adresse);
			$carac_client.="\n".$outputlangs->convToOutputCharset($object->client->cp) . " " . $outputlangs->convToOutputCharset($object->client->ville)."\n";
			if ($object->client->pays_code != $this->emetteur->pays_code) $carac_client.=$outputlangs->trans("Country".$object->client->pays_code)."\n";

			// Numero TVA intracom
			if ($object->client->tva_intra) $carac_client.="\n".$outputlangs->transnoentities("VATIntraShort").': '.$outputlangs->convToOutputCharset($object->client->tva_intra);

			// Show customer/recipient
			$pdf->SetXY(102,$posy+3);
			$pdf->SetFont('Arial','B',11);
			$pdf->MultiCell(96,4, $carac_client_name, 0, 'L');

			$pdf->SetFont('Arial','',9);
			$pdf->SetXY(102,$posy+8);
			$pdf->MultiCell(96,4, $carac_client);
		}
	}

	/**
	 *   	\brief      Show footer of page
	 *		\param      pdf     		Object PDF
	 *      \param      outputlang		Object lang for output
	 */
	function _pagefoot(&$pdf, $outputlangs)
	{
		return pdf_pagefoot($pdf,$outputlangs,'FACTURESUPPLIER_FREE_TEXT',$this->emetteur,$this->marge_basse,$this->marge_gauche,$this->page_hauteur);
	}

}

?>
