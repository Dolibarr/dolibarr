<?php
/* Copyright (C) 2004-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 \file       htdocs/livraison/mods/pdf/pdf_typhon.modules.php
 \ingroup    livraison
 \brief      Fichier de la classe permettant de gï¿½nï¿½rer les bons de livraison au modï¿½le Typho
 \author	    Laurent Destailleur
 \version    $Id$
 */

require_once(DOL_DOCUMENT_ROOT."/livraison/mods/modules_livraison.php");
require_once(DOL_DOCUMENT_ROOT."/livraison/livraison.class.php");
require_once(DOL_DOCUMENT_ROOT."/lib/company.lib.php");


/**
 \class      pdf_typhon
 \brief      Classe permettant de gï¿½nï¿½rer les bons de livraison au modï¿½le Typho
 */

class pdf_typhon extends ModelePDFDeliveryOrder
{
	var $emetteur;	// Objet societe qui emet
	
    /**
     *		\brief  Constructor
     *		\param	db		Database handler
     */
	function pdf_typhon($db)
	{
		global $conf,$langs,$mysoc;

        $langs->load("main");
        $langs->load("bills");
		
        $this->db = $db;
		$this->name = "typhon";
		$this->description = "Modele de bon de livraison complet (logo...)";

		// Dimension page pour format A4
		$this->type = 'pdf';
		$this->page_largeur = 210;
		$this->page_hauteur = 297;
		$this->format = array($this->page_largeur,$this->page_hauteur);
		$this->marge_gauche=10;
		$this->marge_droite=10;
		$this->marge_haute=10;
		$this->marge_basse=10;

		$this->option_logo = 1;                    // Affiche logo FAC_PDF_LOGO
		$this->option_tva = 1;                     // Gere option tva FACTURE_TVAOPTION
		$this->option_modereg = 1;                 // Gere choix mode reglement FACTURE_CHQ_NUMBER, FACTURE_RIB_NUMBER
		$this->option_codeproduitservice = 1;      // Affiche code produit-service
		if (defined("FACTURE_TVAOPTION") && FACTURE_TVAOPTION == 'franchise')
			$this->franchise=1;

        // Recupere emmetteur
        $this->emetteur=$mysoc;
        if (! $this->emetteur->pays_code) $this->emetteur->pays_code=substr($langs->defaultlang,-2);    // Par defaut, si n'était pas défini

		$this->tva=array();

		// Defini position des colonnes
		$this->posxdesc=$this->marge_gauche+1;
		$this->posxtva=121;
		$this->posxup=132;
		$this->posxqty=151;
		$this->posxdiscount=162;
		$this->postotalht=177;
			
		$this->atleastoneratenotnull=0;
		$this->atleastonediscount=0;
	}

	/**
	 *	\brief      Renvoi dernere erreur
	 *	\return     string      Derniere erreur
	 */
	function pdferror()
	{
		return $this->error;
	}

	/**
		\brief      	Fonction gï¿½nï¿½rant le bon de livraison sur le disque
		\param	    	delivery		Object livraison ï¿½ gï¿½nï¿½rer
		\param			outputlangs		Output language
		\return	    	int         	1 if OK, <=0 if KO
	 */
	function write_file($delivery,$outputlangs='')
	{
		global $user,$langs,$conf;

		$langs->load("main");
		$langs->load("bills");
		$langs->load("products");
		$langs->load("deliveries");

		if ($conf->livraison_bon->dir_output)
		{
			// If $delivery is id instead of object
			if (! is_object($delivery))
			{
				$id = $delivery;
				$delivery = new Livraison($this->db);
				$delivery->fetch($id);

				if ($result < 0)
				{
					dolibarr_print_error($db,$delivery->error);
				}
			}

			$nblignes = sizeof($delivery->lignes);

			$deliveryref = sanitize_string($delivery->ref);
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

				$pdf->SetDrawColor(128,128,128);

				$pdf->SetTitle($delivery->ref);
				$pdf->SetSubject($langs->transnoentities("DeliveryOrder"));
				$pdf->SetCreator("Dolibarr ".DOL_VERSION);
				$pdf->SetAuthor($user->fullname);

				$pdf->SetMargins($this->marge_gauche, $this->marge_haute, $this->marge_droite);   // Left, Top, Right
				$pdf->SetAutoPageBreak(1,0);
				/*
				 // Positionne $this->atleastonediscount si on a au moins une remise
				 for ($i = 0 ; $i < $nblignes ; $i++)
				 {
				 if ($delivery->lignes[$i]->remise_percent)
				 {
				 $this->atleastonediscount++;
				 }
				 }
				 */
				$this->_pagehead($pdf, $delivery);

				$pagenb = 1;
				$tab_top = 90;
				$tab_top_newpage = 50;
				$tab_height = 150;

				$iniY = $tab_top + 8;
				$curY = $tab_top + 8;
				$nexY = $tab_top + 8;

				// Boucle sur les lignes
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
								$prefix_prodserv = $langs->transnoentities("Service")." ";
							}
							else
							{
								$prefix_prodserv = $langs->transnoentities("Product")." ";
							}
							$libelleproduitservice=$prefix_prodserv.$prodser->ref." - ".$libelleproduitservice;
						}
					}
					if ($delivery->lignes[$i]->date_start && $delivery->lignes[$i]->date_end)
					{
						// Affichage duree si il y en a une
						$libelleproduitservice.="<br>".dol_htmlentitiesbr("(".$langs->transnoentities("From")." ".dolibarr_print_date($delivery->lignes[$i]->date_start)." ".$langs->transnoentities("to")." ".dolibarr_print_date($delivery->lignes[$i]->date_end).")",1);
					}

					$pdf->SetFont('Arial','', 9);   // Dans boucle pour gerer multi-page

					$pdf->writeHTMLCell(108, 4, $this->posxdesc-1, $curY, $libelleproduitservice, 0, 1);

					$pdf->SetFont('Arial','', 9);   // On repositionne la police par defaut

					$nexY = $pdf->GetY();
					/*
					 // TVA
					 $pdf->SetXY ($this->posxtva, $curY);
					 $pdf->MultiCell(10, 4, ($delivery->lignes[$i]->tva_tx < 0 ? '*':'').abs($delivery->lignes[$i]->tva_tx), 0, 'R');

					 // Prix unitaire HT avant remise
					 $pdf->SetXY ($this->posxup, $curY);
					 $pdf->MultiCell(18, 4, price($delivery->lignes[$i]->subprice), 0, 'R', 0);
					 */
					// Quantity
					$pdf->SetXY ($this->posxqty, $curY);
					$pdf->MultiCell(40, 4, $delivery->lignes[$i]->qty_shipped, 0, 'R');
					/*
					 // Remise sur ligne
					 $pdf->SetXY ($this->posxdiscount, $curY);
					 if ($delivery->lignes[$i]->remise_percent)
					 {
					 $pdf->MultiCell(14, 4, $delivery->lignes[$i]->remise_percent."%", 0, 'R');
					 }

					 // Total HT ligne
					 $pdf->SetXY ($this->postotalht, $curY);
					 $total = price($delivery->lignes[$i]->price * $delivery->lignes[$i]->qty);
					 $pdf->MultiCell(23, 4, $total, 0, 'R', 0);

					 // Collecte des totaux par valeur de tva
					 // dans le tableau tva["taux"]=total_tva
					 $tvaligne=$delivery->lignes[$i]->price * $delivery->lignes[$i]->qty;
					 if ($delivery->remise_percent) $tvaligne-=($tvaligne*$delivery->remise_percent)/100;
					 $this->tva[ (string)$delivery->lignes[$i]->tva_tx ] += $tvaligne;
					 */
					$nexY+=2;    // Passe espace entre les lignes

					if ($nexY > 200 && $i < ($nblignes - 1))
					{
						$this->_tableau($pdf, $tab_top, $tab_height + 20, $nexY);
						$this->_pagefoot($pdf,$outputlangs);

						// Nouvelle page
						$pdf->AddPage();
						$pagenb++;
						$this->_pagehead($pdf, $delivery, 0);

						$nexY = $tab_top_newpage + 8;
						$pdf->SetTextColor(0,0,0);
						$pdf->SetFont('Arial','', 10);
					}

				}

				// Affiche cadre tableau
				if ($pagenb == 1)
				{
					$this->_tableau($pdf, $tab_top, $tab_height, $nexY);
					$bottomlasttab=$tab_top + $tab_height + 1;
				}
				else
				{
					$this->_tableau($pdf, $tab_top_newpage, $tab_height, $nexY);
					$bottomlasttab=$tab_top_newpage + $tab_height + 1;
				}

				/*
				 * Pied de page
				 */
				$this->_pagefoot($pdf,$outputlangs);
				$pdf->AliasNbPages();

				$pdf->Close();

				$pdf->Output($file);

				return 1;   // Pas d'erreur
			}
			else
			{
				$this->error=$langs->transnoentities("ErrorCanNotCreateDir",$dir);
				return 0;
			}
		}

		$this->error=$langs->transnoentities("ErrorConstantNotDefined","LIVRAISON_OUTPUTDIR");
		return 0;
	}


	/*
	 *   \brief      Affiche la grille des lignes de propales
	 *   \param      pdf     objet PDF
	 */
	function _tableau(&$pdf, $tab_top, $tab_height, $nexY)
	{
		global $langs,$conf;
		$langs->load("main");
		$langs->load("bills");

		// Montants exprimes en     (en tab_top - 1)
		$pdf->SetTextColor(0,0,0);
		$pdf->SetFont('Arial','',8);
		//$titre = $langs->transnoentities("AmountInCurrency",$langs->transnoentities("Currency".$conf->monnaie));
		//$pdf->Text($this->page_largeur - $this->marge_droite - $pdf->GetStringWidth($titre), $tab_top-1, $titre);

		$pdf->SetDrawColor(128,128,128);

		// Rect prend une longueur en 3eme param
		$pdf->Rect($this->marge_gauche, $tab_top, $this->page_largeur-$this->marge_gauche-$this->marge_droite, $tab_height);
		// line prend une position y en 3eme param
		$pdf->line($this->marge_gauche, $tab_top+6, $this->page_largeur-$this->marge_droite, $tab_top+6);

		$pdf->SetFont('Arial','',10);

		$pdf->SetXY ($this->posxdesc-1, $tab_top+2);
		$pdf->MultiCell(108,2, $langs->transnoentities("Designation"),'','L');
		$pdf->line($this->posxqty-1, $tab_top, $this->posxqty-1, $tab_top + $tab_height);
		$pdf->SetXY ($this->posxqty-1, $tab_top+2);
		$pdf->MultiCell(40, 2, $langs->transnoentities("QtyShipped"),'','R');
	}

	/*
	 *   	\brief      Affiche en-tete bon livraison
	 *   	\param      pdf     objet PDF
	 *   	\param      fac     objet propale
	 *      \param      showadress      0=non, 1=oui
	 */
	function _pagehead(&$pdf, $delivery, $showadress=1)
	{
		global $langs,$conf,$mysoc;

		$langs->load("main");
		$langs->load("bills");
		$langs->load("orders");
		$langs->load("companies");

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
				$pdf->MultiCell(100, 3, $langs->transnoentities("ErrorLogoFileNotFound",$logo), 0, 'L');
				$pdf->MultiCell(100, 3, $langs->transnoentities("ErrorGoToModuleSetup"), 0, 'L');
			}
		}
		else if(defined("MAIN_INFO_SOCIETE_NOM") && FAC_PDF_SOCIETE_NOM)
		{
			$pdf->MultiCell(100, 4, MAIN_INFO_SOCIETE_NOM, 0, 'L');
		}

		$pdf->SetFont('Arial','B',13);
		$pdf->SetXY(100,$posy);
		$pdf->SetTextColor(0,0,60);
		$pdf->MultiCell(100, 4, $langs->transnoentities("DeliveryOrder")." ".$delivery->ref, '' , 'R');
		$pdf->SetFont('Arial','',12);

		$posy+=6;
		$pdf->SetXY(100,$posy);
		$pdf->SetTextColor(0,0,60);
		if ($delivery->date_valid)
		{
			$pdf->MultiCell(100, 4, $langs->transnoentities("Date")." : " . dolibarr_print_date($delivery->date_valid,"%d %b %Y"), '', 'R');
		}
		else
		{
			$pdf->SetTextColor(255,0,0);
			$pdf->MultiCell(100, 4, $langs->transnoentities("DeliveryNotValidated"), '', 'R');
			$pdf->SetTextColor(0,0,60);
		}

		$posy+=6;
		$pdf->SetXY(100,$posy);
		$pdf->SetTextColor(0,0,60);
		$commande = new Commande ($this->db);
		if ($commande->fetch($delivery->commande_id) >0) {
			$pdf->MultiCell(100, 4, $langs->transnoentities("RefOrder")." : ".$commande->ref, '' , 'R');
		}

		if ($showadress)
		{
			// Emetteur
			$posy=42;
			$hautcadre=40;
			$pdf->SetTextColor(0,0,0);
			$pdf->SetFont('Arial','',8);
			$pdf->SetXY($this->marge_gauche,$posy-5);
			$pdf->MultiCell(66,5, $langs->transnoentities("BillFrom").":");


			$pdf->SetXY($this->marge_gauche,$posy);
			$pdf->SetFillColor(230,230,230);
			$pdf->MultiCell(82, $hautcadre, "", 0, 'R', 1);


			$pdf->SetXY($this->marge_gauche+2,$posy+3);

			// Nom emetteur
			$pdf->SetTextColor(0,0,60);
			$pdf->SetFont('Arial','B',11);
			if (defined("FAC_PDF_SOCIETE_NOM") && FAC_PDF_SOCIETE_NOM) $pdf->MultiCell(80, 4, FAC_PDF_SOCIETE_NOM, 0, 'L');
			else $pdf->MultiCell(80, 4, $mysoc->nom, 0, 'L');

			// Caractï¿½ristiques emetteur
			$carac_emetteur = '';
			if (defined("FAC_PDF_ADRESSE") && FAC_PDF_ADRESSE) $carac_emetteur .= ($carac_emetteur ? "\n" : '' ).FAC_PDF_ADRESSE;
			else {
				$carac_emetteur .= ($carac_emetteur ? "\n" : '' ).$mysoc->adresse;
				$carac_emetteur .= ($carac_emetteur ? "\n" : '' ).$mysoc->cp.' '.$mysoc->ville;
			}
			$carac_emetteur .= "\n";
			// Tel
			if (defined("FAC_PDF_TEL") && FAC_PDF_TEL) $carac_emetteur .= ($carac_emetteur ? "\n" : '' ).$langs->transnoentities("Phone").": ".FAC_PDF_TEL;
			elseif ($mysoc->tel) $carac_emetteur .= ($carac_emetteur ? "\n" : '' ).$langs->transnoentities("Phone").": ".$mysoc->tel;
			// Fax
			if (defined("FAC_PDF_FAX") && FAC_PDF_FAX) $carac_emetteur .= ($carac_emetteur ? "\n" : '' ).$langs->transnoentities("Fax").": ".FAC_PDF_FAX;
			elseif ($mysoc->fax) $carac_emetteur .= ($carac_emetteur ? "\n" : '' ).$langs->transnoentities("Fax").": ".$mysoc->fax;
			// EMail
			if (defined("FAC_PDF_MEL") && FAC_PDF_MEL) $carac_emetteur .= ($carac_emetteur ? "\n" : '' ).$langs->transnoentities("Email").": ".FAC_PDF_MEL;
			elseif ($mysoc->email) $carac_emetteur .= ($carac_emetteur ? "\n" : '' ).$langs->transnoentities("Email").": ".$mysoc->email;
			// Web
			if (defined("FAC_PDF_WWW") && FAC_PDF_WWW) $carac_emetteur .= ($carac_emetteur ? "\n" : '' ).$langs->transnoentities("Web").": ".FAC_PDF_WWW;
			elseif ($mysoc->url) $carac_emetteur .= ($carac_emetteur ? "\n" : '' ).$langs->transnoentities("Web").": ".$mysoc->url;

			$pdf->SetFont('Arial','',9);
			$pdf->SetXY($this->marge_gauche+2,$posy+8);
			$pdf->MultiCell(80,4, $carac_emetteur);

			// Client destinataire
			$posy=42;
			$pdf->SetTextColor(0,0,0);
			$pdf->SetFont('Arial','',8);
			$pdf->SetXY(102,$posy-5);
			$pdf->MultiCell(80,5, $langs->transnoentities("DeliveryAddress").":");

			/*
			 * if a delivery address is used, use that, else use the client address
			 */
			$client = new Societe($this->db);
			if ($commande->adresse_livraison_id > 0) {
				$client->fetch_adresse_livraison($commande->adresse_livraison_id);
			} else {
				$client->fetch($delivery->socid);
			}
			$delivery->client = $client;

			// Cadre client destinataire
			$pdf->rect(100, $posy, 100, $hautcadre);

			// Nom client
			$pdf->SetXY(102,$posy+3);
			$pdf->SetFont('Arial','B',11);
			$pdf->MultiCell(106,4, $delivery->client->nom, 0, 'L');

			// Caractï¿½ristiques client
			$carac_client=$delivery->client->adresse."\n";
			$carac_client.=$delivery->client->cp . " " . $delivery->client->ville."\n";

			// Pays si diffï¿½rent de l'ï¿½metteur
			if ($this->emetteur->pays_code != $delivery->client->pays_code)
			{
				$carac_client.=$delivery->client->pays."\n";
			}

			// Tva intracom
			if ($delivery->client->tva_intra) $carac_client.="\n".$langs->transnoentities("VATIntraShort").': '.$delivery->client->tva_intra;
			$pdf->SetFont('Arial','',9);
			$pdf->SetXY(102,$posy+8);
			$pdf->MultiCell(86,4, $carac_client);
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
