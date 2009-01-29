<?php
/* Copyright (C) 2005      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2005-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005      Regis Houssin        <regis@dolibarr.fr>
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
 *	\file       htdocs/includes/modules/expedition/pdf/pdf_expedition_merou.modules.php
 *	\ingroup    expedition
 *	\brief      Fichier de la classe permettant de generer les bordereaux envoi au mod�le Merou
 *	\version    $Id$
 */

require_once DOL_DOCUMENT_ROOT."/includes/modules/expedition/pdf/ModelePdfExpedition.class.php";
require_once DOL_DOCUMENT_ROOT."/contact.class.php";
require_once(DOL_DOCUMENT_ROOT."/lib/company.lib.php");


/**
 *	\class      pdf_expedition_merou
 *	\brief      Classe permettant de generer les borderaux envoi au modele Merou
 */
Class pdf_expedition_merou extends ModelePdfExpedition
{
	var $emetteur;	// Objet societe qui emet


	/**
	 \brief  Constructeur
	 \param	db		Handler acc�s base de donn�e
	 */
	function pdf_expedition_merou($db=0)
	{
		global $conf,$langs,$mysoc;

		$this->db = $db;
		$this->name = "Merou";
		$this->description = "Modele Merou 2xA5 \n
	Attention !! Il est necessaire de creer 4 nouveaux types de contact : \n
	 |element->commande,source->internal,code->LIVREUR \n
	 |element->commande,source->external,code->LIVREUR \n
	 |element->commande,source->external,code->EXPEDITEUR \n
	 |element->commande,source->external,code->DESTINATAIRE \n
";

		$this->type = 'pdf';
		$this->page_largeur = 148.5;
		$this->page_hauteur = 210;
		$this->format = array($this->page_largeur,$this->page_hauteur);

		$this->option_logo = 1;                    // Affiche logo

		// Recupere emmetteur
		$this->emetteur=$mysoc;
		if (! $this->emetteur->pays_code) $this->emetteur->pays_code=substr($langs->defaultlang,-2);    // Par defaut, si n'�tait pas d�fini
	}


	/**
	 *		\brief      Fonction generant le document sur le disque
	 *		\param	    obj				Objet expedition a generer (ou id si ancienne methode)
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
		$outputlangs->load("sendings");

		$outputlangs->setPhpLang();

		//Generation de la fiche
		$this->expe = $obj;

		//Verification de la configuration
		if ($conf->expedition_bon->dir_output)
		{
			//Creation du Client
			$soc = new Societe($this->db);
			$soc->fetch($this->expe->commande->socid);

			//Creation de l expediteur
			$this->expediteur = $soc;
			//Creation du destinataire
			$this->destinataire = new Contact($this->db);
			//		$pdf->expe->commande->fetch($pdf->commande->id);
			//print_r($pdf->expe);
			$idcontact = $this->expe->commande->getIdContact('external','DESTINATAIRE');
			$this->destinataire->fetch($idcontact[0]);

			//Creation du livreur
			$idcontact = $this->expe->commande->getIdContact('internal','LIVREUR');
			$this->livreur = new User($this->db,$idcontact[0]);
			if ($idcontact[0]) $this->livreur->fetch();


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

			//Si le dossier existe
			if (file_exists($dir))
			{
				// Initialisation Bon vierge
				$pdf = new FPDI_Protection('l','mm',$this->format);

				// Protection et encryption du pdf
				if ($conf->global->PDF_SECURITY_ENCRYPTION)
				{
					$pdfrights = array('print'); // Ne permet que l'impression du document
					$pdfuserpass = ''; // Mot de passe pour l'utilisateur final
					$pdfownerpass = NULL; // Mot de passe du propri�taire, cr�� al�atoirement si pas d�fini
					$pdf->SetProtection($pdfrights,$pdfuserpass,$pdfownerpass);
				}

				$pdf->Open();
				$pagenb=0;
				$pdf->SetDrawColor(128,128,128);

				//Generation de l entete du fichier
				$pdf->SetTitle($outputlangs->convToOutputCharset($this->expe->ref));
				$pdf->SetSubject($outputlangs->transnoentities("Sending"));
				$pdf->SetCreator("Dolibarr ".DOL_VERSION);
				$pdf->SetAuthor($outputlangs->convToOutputCharset($user->fullname));
				$pdf->SetKeyWords($outputlangs->convToOutputCharset($this->expe->ref)." ".$outputlangs->transnoentities("Sending"));
				if ($conf->global->MAIN_DISABLE_PDF_COMPRESSION) $pdf->SetCompression(false);

				$pdf->SetMargins(10, 10, 10);
				$pdf->SetAutoPageBreak(1,0);

				$pdf->SetFont('Arial','', 7);

				// New page
				$pdf->AddPage();
				$pagenb++;
				$this->_pagehead($pdf, $this->expe, $outputlangs);
				$pdf->SetFont('Arial','', 7);
				$pdf->MultiCell(0, 3, '', 0, 'J');		// Set interline to 3
				$pdf->SetTextColor(0,0,0);

				//Initialisation des coordonnees
				$tab_top = 53;
				$tab_height = 70;
				$pdf->SetFillColor(240,240,240);
				$pdf->SetTextColor(0,0,0);
				$pdf->SetFont('Arial','', 7);
				$pdf->SetXY (10, $tab_top + 5 );
				$iniY = $pdf->GetY();
				$curY = $pdf->GetY();
				$nexY = $pdf->GetY();
				//Generation du tableau
				$this->_tableau($pdf, $tab_top, $tab_height, $nexY, $outputlangs);

				//Recuperation des produits de la commande.
				if ($this->expe->ref != 'SPECIMEN') $this->expe->commande->fetch_lines(1);

				$Produits = $this->expe->commande->lignes;
				$nblignes = sizeof($Produits);

				for ($i = 0 ; $i < $nblignes ; $i++){
					//Generation du produit
					$Prod = new Product($this->db);
					$Prod->fetch($Produits[$i]->fk_product);
					//Creation des cases a cocher
					$pdf->rect(10+3, $curY+1, 3, 3);
					$pdf->rect(20+3, $curY+1, 3, 3);
					//Insertion de la reference du produit
					$pdf->SetXY (30, $curY );
					$pdf->SetFont('Arial','B', 7);
					$pdf->MultiCell(24, 5, $outputlangs->convToOutputCharset($Prod->ref), 0, 'L', 0);
					//Insertion du libelle
					$pdf->SetFont('Arial','', 7);
					$pdf->SetXY (50, $curY );
					$pdf->MultiCell(90, 5, $outputlangs->convToOutputCharset($Prod->libelle), 0, 'L', 0);
					//Insertion de la quantite commandee
					$pdf->SetFont('Arial','', 7);
					$pdf->SetXY (140, $curY );
					$pdf->MultiCell(30, 5, $this->expe->lignes[$i]->qty_asked, 0, 'C', 0);
					//Insertion de la quantite a envoyer
					$pdf->SetFont('Arial','', 7);
					$pdf->SetXY (170, $curY );
					$pdf->MultiCell(30, 5, $this->expe->lignes[$i]->qty_shipped, 0, 'C', 0);

					//Generation de la page 2
					$curY += 4;
					$nexY = $curY;
					if ($nexY > ($tab_top+$tab_height-10) && $i < $nblignes - 1)
					{
						$this->_tableau($pdf, $tab_top, $tab_height, $nexY, $outputlangs);
						$this->_pagefoot($pdf, $outputlangs);
						$pdf->AliasNbPages();

						$nexY = $iniY;

						// New page
						$pdf->AddPage();
						$pagenb++;
						$this->_pagehead($pdf, $this->expe, $outputlangs);
						$pdf->MultiCell(0, 3, '', 0, 'J');		// Set interline to 3
						$pdf->SetTextColor(0,0,0);
						$pdf->SetFont('Arial','', 7);
					}
				}
				//Insertion du pied de page
				$this->_pagefoot($pdf, $outputlangs);

				$pdf->AliasNbPages();

				$pdf->Close();

				$pdf->Output($file);

				$langs->setPhpLang();	// On restaure langue session
				return 1;
			}
			else
			{
				$this->error=$outputlangs->transnoentities("ErrorCanNotCreateDir",$dir);
				$langs->setPhpLang();	// On restaure langue session
				return 0;
			}
		}
		else
		{
			$this->error=$outputlangs->transnoentities("ErrorConstantNotDefined","EXP_OUTPUTDIR");
			$langs->setPhpLang();	// On restaure langue session
			return 0;
		}
		$this->error=$outputlangs->transnoentities("ErrorUnknown");
		$langs->setPhpLang();	// On restaure langue session
		return 0;   // Erreur par defaut

	}

	//********************************
	// Generation du tableau
	//********************************
	function _tableau(&$pdf, $tab_top, $tab_height, $nexY, $outputlangs)
	{
		global $langs;

		$langs->load("main");
		$langs->load("bills");

		$pdf->SetFont('Arial','B',8);
		$pdf->SetXY(10,$tab_top);
		$pdf->MultiCell(10,5,"LS",0,'C',1);
		$pdf->line(20, $tab_top, 20, $tab_top + $tab_height);
		$pdf->SetXY(20,$tab_top);
		$pdf->MultiCell(10,5,"LR",0,'C',1);
		$pdf->line(30, $tab_top, 30, $tab_top + $tab_height);
		$pdf->SetXY(30,$tab_top);
		$pdf->MultiCell(20,5,$outputlangs->transnoentities("Ref"),0,'C',1);
		$pdf->SetXY(50,$tab_top);
		$pdf->MultiCell(90,5,$outputlangs->transnoentities("Description"),0,'L',1);
		$pdf->SetXY(140,$tab_top);
		$pdf->MultiCell(30,5,$outputlangs->transnoentities("QtyOrdered"),0,'C',1);
		$pdf->SetXY(170,$tab_top);
		$pdf->MultiCell(30,5,$outputlangs->transnoentities("QtyToShip"),0,'C',1);
		$pdf->Rect(10, $tab_top, 190, $tab_height);
	}

	//********************************
	// Generation du Pied de page
	//********************************
	function _pagefoot(&$pdf, $outputlangs)
	{
		$pdf->SetFont('Arial','',8);
		$pdf->SetY(-23);
		$pdf->MultiCell(100, 3, $outputlangs->transnoentities("GoodStatusDeclaration") , 0, 'L');
		$pdf->SetY(-13);
		$pdf->MultiCell(100, 3, $outputlangs->transnoentities("ToAndDate") , 0, 'C');
		$pdf->SetXY(120,-23);
		$pdf->MultiCell(100, 3, $outputlangs->transnoentities("NameAndSignature") , 0, 'C');
		$pdf->SetXY(-10,-10);
		$pdf->MultiCell(10, 3, $pdf->PageNo().'/{nb}', 0, 'R');
	}


	//********************************
	// Generation de l entete
	//********************************
	function _pagehead(&$pdf, $exp, $outputlangs)
	{
		global $conf, $langs;

		$tab4_top = 60;
		$tab4_hl = 6;
		$tab4_sl = 4;
		$ligne = 2;

		//*********************LOGO****************************
		$logo=$conf->societe->dir_logos.'/'.$this->emetteur->logo;
		if ($this->emetteur->logo)
		{
			if (is_readable($logo))
			{
				$pdf->Image($logo,10, 5, 0, 24);
			}
			else
			{
				$pdf->SetTextColor(200,0,0);
				$pdf->SetFont('Arial','B',8);
				$pdf->MultiCell(100, 3, $langs->transnoentities("ErrorLogoFileNotFound",$logo), 0, 'L');
				$pdf->MultiCell(100, 3, $langs->transnoentities("ErrorGoToModuleSetup"), 0, 'L');
			}
		}
		else $pdf->MultiCell(100, 4, $outputlangs->convToOutputCharset($this->emetteur->nom), 0, 'L');

		//*********************Entete****************************
		//Nom du Document
		$Xoff = 94;
		$Yoff = 0;
		$pdf->SetXY($Xoff,7);
		$pdf->SetFont('Arial','B',12);
		$pdf->SetTextColor(0,0,0);
		$pdf->MultiCell(0, 8, $outputlangs->transnoentities("SendingSheet"), '' , 'L');	// Bordereau expedition
		//Num Expedition
		$Yoff = $Yoff+7;
		$Xoff = 160;
		//		$pdf->rect($Xoff, $Yoff, 85, 8);
		$pdf->SetXY($Xoff,$Yoff);
		$pdf->SetFont('Arial','',8);
		$pdf->SetTextColor(0,0,0);
		$pdf->MultiCell(0, 8, $outputlangs->transnoentities("RefSending").': '.$outputlangs->convToOutputCharset($exp->ref), '' , 'L');
		//$this->Code39($Xoff+43, $Yoff+1, $this->expe->ref,$ext = true, $cks = false, $w = 0.4, $h = 4, $wide = true);
		//Num Commande
		$Yoff = $Yoff+4;
		//		$pdf->rect($Xoff, $Yoff, 85, 8);
		$pdf->SetXY($Xoff,$Yoff);
		$pdf->SetFont('Arial','',8);
		$pdf->SetTextColor(0,0,0);
		$pdf->MultiCell(0, 8, $outputlangs->transnoentities("RefOrder").': '.$outputlangs->convToOutputCharset($exp->commande->ref), '' , 'L');

		//$this->Code39($Xoff+43, $Yoff+1, $exp->commande->ref,$ext = true, $cks = false, $w = 0.4, $h = 4, $wide = true);
		//Definition Emplacement du bloc Societe
		$Xoff = 115;
		$blSocX=11;
		$blSocY=25;
		$blSocW=50;
		$blSocX2=$blSocW+$blSocXs;
		$pdf->SetTextColor(0,0,0);

		// Caracteristiques emetteur
		$carac_emetteur = '';
		$carac_emetteur .= ($carac_emetteur ? "\n" : '' ).$outputlangs->convToOutputCharset($this->emetteur->adresse);
		$carac_emetteur .= ($carac_emetteur ? "\n" : '' ).$outputlangs->convToOutputCharset($this->emetteur->cp).' '.$outputlangs->convToOutputCharset($this->emetteur->ville);
		$carac_emetteur .= "\n";
		// Tel
		if ($this->emetteur->tel) $carac_emetteur .= ($carac_emetteur ? "\n" : '' ).$outputlangs->transnoentities("Phone").": ".$outputlangs->convToOutputCharset($this->emetteur->tel);
		// Fax
		if ($this->emetteur->fax) $carac_emetteur .= ($carac_emetteur ? "\n" : '' ).$outputlangs->transnoentities("Fax").": ".$outputlangs->convToOutputCharset($this->emetteur->fax);
		// EMail
		if ($this->emetteur->email) $carac_emetteur .= ($carac_emetteur ? "\n" : '' ).$outputlangs->transnoentities("Email").": ".$outputlangs->convToOutputCharset($this->emetteur->email);
		// Web
		if ($this->emetteur->url) $carac_emetteur .= ($carac_emetteur ? "\n" : '' ).$outputlangs->transnoentities("Web").": ".$outputlangs->convToOutputCharset($this->emetteur->url);

		$pdf->SetFont('Arial','',7);
		$pdf->SetXY($blSocX,$blSocY+4);
		$pdf->MultiCell(80,2, $carac_emetteur);

		//Date Expedition
		$Yoff = $Yoff+7;
		$pdf->SetXY($blSocX,$blSocY+20);
		$pdf->SetFont('Arial','B',8);
		$pdf->SetTextColor(0,0,0);
		$pdf->MultiCell(50, 8, $outputlangs->transnoentities("Date")." : " . dolibarr_print_date($exp->date,'day',false,$outputlangs), '' , 'L');
		//Date Expedition
		$pdf->SetXY($blSocX2,$blSocY+20);
		$pdf->SetFont('Arial','B',8);
		$pdf->SetTextColor(0,0,0);
		$pdf->MultiCell(50, 8, $outputlangs->transnoentities("Deliverer")." ".$outputlangs->convToOutputCharset($livreur->fullname), '' , 'L');


		/**********************************/
		//Emplacement Informations Expediteur (Client)
		/**********************************/
		$Ydef = $Yoff;
		$blExpX=$Xoff-20;
		$blW=52;
		$Yoff = $Yoff+5;
		$Ydef = $Yoff;
		$blSocY = 1;
		//Titre
		$pdf->SetXY($blExpX,$Yoff-3);
		$pdf->SetFont('Arial','B',7);
		$pdf->MultiCell($blW,3, $outputlangs->transnoentities("Sender"), 0, 'L');
		$pdf->Rect($blExpX, $Yoff, $blW, 20);
		//Nom Client
		$pdf->SetXY($blExpX,$Yoff+$blSocY);
		$pdf->SetFont('Arial','B',7);
		$pdf->MultiCell($blW,3, $outputlangs->convToOutputCharset($this->expediteur->nom), 0, 'C');
		$pdf->SetFont('Arial','',7);
		$blSocY+=3;
		//Adresse Client
		//Gestion des Retours chariots
		$Out=split("\n",$outputlangs->convToOutputCharset($this->expediteur->adresse));
		for ($i=0;$i<count($Out);$i++) {
			$pdf->SetXY($blExpX,$Yoff+$blSocY);
			$pdf->MultiCell($blW,5,urldecode($Out[$i]),  0, 'L');
			$blSocY+=3;
		}
		$pdf->SetXY($blExpX,$Yoff+$blSocY);
		$pdf->MultiCell($blW,5, $outputlangs->convToOutputCharset($this->expediteur->cp) . " " . $outputlangs->convToOutputCharset($this->expediteur->ville),  0, 'L');
		$blSocY+=4;
		//Tel Client
		$pdf->SetXY($blExpX,$Yoff+$blSocY);
		$pdf->SetFont('Arial','',7);
		$pdf->MultiCell($blW,3, $outputlangs->transnoentities("Tel")." : ".$outputlangs->convToOutputCharset($this->expediteur->tel), 0, 'L');

		/**********************************/
		//Emplacement Informations Destinataire (Contact livraison)
		/**********************************/
		$blDestX=$blExpX+55;
		$blW=50;
		$Yoff = $Ydef;
		$blSocY = 1;
		//Titre
		$pdf->SetXY($blDestX,$Yoff-3);
		$pdf->SetFont('Arial','B',7);
		$pdf->MultiCell($blW,3, $outputlangs->transnoentities("Recipient"), 0, 'L');
		$pdf->Rect($blDestX, $Yoff, $blW, 20);
		//Nom Client
		$pdf->SetXY($blDestX,$Yoff+$blSocY);
		$pdf->SetFont('Arial','B',7);
		$pdf->MultiCell($blW,3, $outputlangs->convToOutputCharset($this->destinataire->fullname), 0, 'C');
		$pdf->SetFont('Arial','',7);
		$blSocY+=3;
		//Adresse Client
		//Gestion des Retours chariots
		$Out=split("\n",$outputlangs->convToOutputCharset($this->destinataire->address));
		for ($i=0;$i<count($Out);$i++) {
			$pdf->SetXY($blDestX,$Yoff+$blSocY);
			$pdf->MultiCell($blW,5,urldecode($Out[$i]),  0, 'L');
			$blSocY+=3;
		}
		$pdf->SetXY($blDestX,$Yoff+$blSocY);
		$pdf->MultiCell($blW,5, $outputlangs->convToOutputCharset($this->destinataire->cp) . " " . $outputlangs->convToOutputCharset($this->destinataire->ville),  0, 'L');
		$blSocY+=4;
		//Tel Client
		$pdf->SetXY($blDestX,$Yoff+$blSocY);
		$pdf->SetFont('Arial','',7);
		$pdf->MultiCell($blW,3, $outputlangs->transnoentities("Tel")." : ".$this->destinataire->phone_pro, 0, 'L');
	}
}
?>
