<?php
/* Copyright (C) 2005      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2005-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 \file       htdocs/expedition/mods/pdf/pdf_expedition_merou.modules.php
 \ingroup    expedition
 \brief      Fichier de la classe permettant de générer les bordereaux envoi au modèle Merou
 \version    $Id$
 */

require_once DOL_DOCUMENT_ROOT."/expedition/mods/pdf/ModelePdfExpedition.class.php";
require_once DOL_DOCUMENT_ROOT."/contact.class.php";

/**
 \class      pdf_expedition_dorade
 \brief      Classe permettant de générer les borderaux envoi au modèle Merou
 */

Class pdf_expedition_merou extends ModelePdfExpedition
{
	var $emetteur;	// Objet societe qui emet


	/**
	 \brief  Constructeur
	 \param	db		Handler accès base de donnée
	 */
	function pdf_expedition_merou($db=0)
	{
		global $conf,$langs,$mysoc;

		$this->db = $db;
		$this->name = "Merou";
		$this->description = "Modele Merou 2xA5 \n
	Attention !! Il est nécessaire de creer 4 nouveaux types de contact : \n 
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
		if (! $this->emetteur->pays_code) $this->emetteur->pays_code=substr($langs->defaultlang,-2);    // Par defaut, si n'était pas défini
	}


	/**
	 *		\brief      Fonction générant le document sur le disque
	 *		\param	    obj		Objet expedition à générer (ou id si ancienne methode)
	 *		\return	    int     1=ok, 0=ko
	 */
	function write_file(&$obj, $outputlangs='')
	{
		global $user,$conf,$langs;

		if (! is_object($outputlangs)) $outputlangs=$langs;
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
				

			// Définition de $dir et $file
			if ($this->expe->specimen)
			{
				$dir = $conf->expedition_bon->dir_output;
				$file = $dir . "/SPECIMEN.pdf";
			}
			else
			{
				$expref = sanitize_string($this->expe->ref);
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
					$pdfownerpass = NULL; // Mot de passe du propriétaire, créé aléatoirement si pas défini
					$pdf->SetProtection($pdfrights,$pdfuserpass,$pdfownerpass);
				}

				$pdf->Open();
				$pdf->AddPage();
				//Generation de l entete du fichier
				$pdf->SetTitle($this->expe->ref);
				$pdf->SetSubject($langs->transnoentities("Sending"));
				$pdf->SetCreator("EXPRESSIV Dolibarr ".DOL_VERSION);
				$pdf->SetAuthor($user->fullname);
				$pdf->SetMargins(10, 10, 10);
				$pdf->SetAutoPageBreak(1,0);

				$pdf->SetFont('Arial','', 7);

				//Insertion de l entete
				$this->_pagehead($pdf, $this->expe);

				//Initialisation des coordonnées
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
				$this->_tableau($pdf, $tab_top, $tab_height, $nexY);
				//Recuperation des produits de la commande.
				$this->expe->commande->fetch_lines(1);
				$Produits = $this->expe->commande->lignes;
				$nblignes = sizeof($Produits);
				for ($i = 0 ; $i < $nblignes ; $i++){
					//Generation du produit
					$Prod = new Product($this->db);
					$Prod->fetch($Produits[$i]->fk_product);
					//Creation des cases à cocher
					$pdf->rect(10+3, $curY+1, 3, 3);
					$pdf->rect(20+3, $curY+1, 3, 3);
					//Insertion de la reference du produit
					$pdf->SetXY (30, $curY );
					$pdf->SetFont('Arial','B', 7);
					$pdf->MultiCell(20, 5, $Prod->ref, 0, 'L', 0);
					//Insertion du libelle
					$pdf->SetFont('Arial','', 7);
					$pdf->SetXY (50, $curY );
					$pdf->MultiCell(130, 5, $Prod->libelle, 0, 'L', 0);
					//Insertion de la quantite
					$pdf->SetFont('Arial','', 7);
					$pdf->SetXY (180, $curY );
					$pdf->MultiCell(20, 5, $Produits[$i]->qty, 0, 'L', 0);
					//Generation de la page 2
					$curY += 4;
					$nexY = $curY;
					if ($nexY > ($tab_top+$tab_height-10) && $i < $nblignes - 1){
						$this->_tableau($pdf, $tab_top, $tab_height, $nexY);
						$this->_pagefoot($pdf);
						$pdf->AliasNbPages();
						$pdf->AddPage();
						$nexY = $iniY;
						$this->_pagehead($pdf, $this->expe);
						$pdf->SetTextColor(0,0,0);
						$pdf->SetFont('Arial','', 7);
					}
				}
				//Insertion du pied de page
				$this->_pagefoot($pdf);

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
	function _tableau(&$pdf, $tab_top, $tab_height, $nexY)
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
		$pdf->MultiCell(20,5,$langs->transnoentities("Ref"),0,'C',1);
		$pdf->SetXY(50,$tab_top);
		$pdf->MultiCell(130,5,$langs->transnoentities("Description"),0,'L',1);
		$pdf->SetXY(180,$tab_top);
		$pdf->MultiCell(20,5,$langs->transnoentities("Quantity"),0,'L',1);
		$pdf->Rect(10, $tab_top, 190, $tab_height);
	}

	//********************************
	// Generation du Pied de page
	//********************************
	function _pagefoot(&$pdf)
	{
		$pdf->SetFont('Arial','',8);
		$pdf->SetY(-23);
		$pdf->MultiCell(100, 3, "Déclare avoir reçu les marchandises ci-dessus en bon état,", 0, 'L');
		$pdf->SetY(-13);
		$pdf->MultiCell(100, 3, "A___________________________________ le ____/_____/__________" , 0, 'C');
		$pdf->SetXY(120,-23);
		$pdf->MultiCell(100, 3, "Nom et Signature : " , 0, 'C');
		$pdf->SetXY(-10,-10);
		$pdf->MultiCell(10, 3, $pdf->PageNo().'/{nb}', 0, 'R');
	}


	//********************************
	// Generation de l entete
	//********************************
	function _pagehead(&$pdf, $exp)
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
		else if(defined("MAIN_INFO_SOCIETE_NOM") && FAC_PDF_SOCIETE_NOM)
		{
			$pdf->MultiCell(100, 4, MAIN_INFO_SOCIETE_NOM, 0, 'L');
		}

		//*********************Entete****************************
		//Nom du Document
		$Yoff = 0;
		$pdf->SetXY(60,7);
		$pdf->SetFont('Arial','B',14);
		$pdf->SetTextColor(0,0,0);
		$pdf->MultiCell(0, 8, $langs->transnoentities("SendingSheet"), '' , 'L');	// Bordereau expedition
		//Num Expedition
		$Yoff = $Yoff+7;
		$Xoff = 140;
		//		$pdf->rect($Xoff, $Yoff, 85, 8);
		$pdf->SetXY($Xoff,$Yoff);
		$pdf->SetFont('Arial','',8);
		$pdf->SetTextColor(0,0,0);
		$pdf->MultiCell(0, 8, $langs->transnoentities("RefSending").': '.$exp->ref, '' , 'L');
		//$this->Code39($Xoff+43, $Yoff+1, $this->expe->ref,$ext = true, $cks = false, $w = 0.4, $h = 4, $wide = true);
		//Num Commande
		$Yoff = $Yoff+4;
		//		$pdf->rect($Xoff, $Yoff, 85, 8);
		$pdf->SetXY($Xoff,$Yoff);
		$pdf->SetFont('Arial','',8);
		$pdf->SetTextColor(0,0,0);
		$pdf->MultiCell(0, 8, $langs->transnoentities("RefOrder").': '.$exp->commande->ref, '' , 'L');

		$Xoff = 115;
		//$this->Code39($Xoff+43, $Yoff+1, $exp->commande->ref,$ext = true, $cks = false, $w = 0.4, $h = 4, $wide = true);
		//Definition Emplacement du bloc Societe
		$blSocX=11;
		$blSocY=25;
		$blSocW=50;
		$blSocX2=$blSocW+$blSocXs;
		$pdf->SetTextColor(0,0,0);
		//Adresse Internet
		if (defined("FAC_PDF_WWW")){
			$pdf->SetXY($blSocX,$blSocY);
			$pdf->SetFont('Arial','B',8);
			$pdf->MultiCell($blSocW, 3, FAC_PDF_WWW, '' , 'L');
		}
		if (defined("FAC_PDF_ADRESSE")){
			$pdf->SetFont('Arial','',7);
			$pdf->SetXY($blSocX,$blSocY+3);
			$pdf->MultiCell($blSocW, 3, FAC_PDF_ADRESSE, '' , 'L');
		}

		if (defined("FAC_PDF_ADRESSE2")){
			$pdf->SetFont('Arial','',7);
			$pdf->SetXY($blSocX,$blSocY+6);
			$pdf->MultiCell($blSocW, 3, FAC_PDF_ADRESSE2, '' , 'L');
		}

		if (defined("FAC_PDF_TEL")){
			$pdf->SetFont('Arial','',7);
			$pdf->SetXY($blSocX,$blSocY+10);
			$pdf->MultiCell($blSocW, 3, "Tel : " . FAC_PDF_TEL, '' , 'L');
		}

		if (defined("FAC_PDF_MEL")){
			$pdf->SetFont('Arial','',7);
			$pdf->SetXY($blSocX,$blSocY+13);
			$pdf->MultiCell(40, 3, "Email : " . FAC_PDF_MEL, '' , 'L');
		}

		if (defined("FAC_PDF_FAX")){
			$pdf->SetFont('Arial','',7);
			$pdf->SetXY($blSocX,$blSocY+16);
			$pdf->MultiCell(40, 3, "Fax : " . FAC_PDF_FAX, '' , 'L');
		}

		if (defined("MAIN_INFO_SIRET")){
			$pdf->SetFont('Arial','',7);
			$pdf->SetXY($blSocX2,$blSocY+10);
			$pdf->MultiCell($blSocW, 3, "SIRET : " . MAIN_INFO_SIRET, '' , 'L');
		}

		if (defined("MAIN_INFO_APE")){
			$pdf->SetFont('Arial','',7);
			$pdf->SetXY($blSocX2,$blSocY+13);
			$pdf->MultiCell($blSocW, 3, "APE : " . MAIN_INFO_APE, '' , 'L');
		}

		if (defined("MAIN_INFO_TVAINTRA")){
			$pdf->SetFont('Arial','',7);
			$pdf->SetXY($blSocX2,$blSocY+16);
			$pdf->MultiCell($blSocW, 3, "ICOMM : " . MAIN_INFO_TVAINTRA, '' , 'L');
		}

		//Date Expedition
		$Yoff = $Yoff+7;
		$pdf->SetXY($blSocX,$blSocY+20);
		$pdf->SetFont('Arial','B',8);
		$pdf->SetTextColor(0,0,0);
		$pdf->MultiCell(50, 8, "Date : " . dolibarr_print_date($exp->date,'day'), '' , 'L');
		//Date Expedition
		$pdf->SetXY($blSocX2,$blSocY+20);
		$pdf->SetFont('Arial','B',8);
		$pdf->SetTextColor(0,0,0);
		$pdf->MultiCell(50, 8, "Livreur(s) : ".$livreur->fullname, '' , 'L');

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
		$pdf->MultiCell($blW,3, 'Expéditeur', 0, 'L');
		$pdf->Rect($blExpX, $Yoff, $blW, 20);
		//Nom Client
		$pdf->SetXY($blExpX,$Yoff+$blSocY);
		$pdf->SetFont('Arial','B',7);
		$pdf->MultiCell($blW,3, $this->expediteur->nom, 0, 'C');
		$pdf->SetFont('Arial','',7);
		$blSocY+=3;
		//Adresse Client
		//Gestion des Retours chariots
		$Out=split("\n",$this->expediteur->adresse);
		for ($i=0;$i<count($Out);$i++) {
			$pdf->SetXY($blExpX,$Yoff+$blSocY);
			$pdf->MultiCell($blW,5,urldecode($Out[$i]),  0, 'L');
			$blSocY+=3;
		}
		$pdf->SetXY($blExpX,$Yoff+$blSocY);
		$pdf->MultiCell($blW,5, $this->expediteur->cp . " " . $this->expediteur->ville,  0, 'L');
		$blSocY+=4;
		//Tel Client
		$pdf->SetXY($blExpX,$Yoff+$blSocY);
		$pdf->SetFont('Arial','',7);
		$pdf->MultiCell($blW,3, "Tel : ".$this->expediteur->tel, 0, 'L');

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
		$pdf->MultiCell($blW,3, 'Destinataire', 0, 'L');
		$pdf->Rect($blDestX, $Yoff, $blW, 20);
		//Nom Client
		$pdf->SetXY($blDestX,$Yoff+$blSocY);
		$pdf->SetFont('Arial','B',7);
		$pdf->MultiCell($blW,3, $this->destinataire->fullname, 0, 'C');
		$pdf->SetFont('Arial','',7);
		$blSocY+=3;
		//Adresse Client
		//Gestion des Retours chariots
		$Out=split("\n",$this->destinataire->address);
		for ($i=0;$i<count($Out);$i++) {
			$pdf->SetXY($blDestX,$Yoff+$blSocY);
			$pdf->MultiCell($blW,5,urldecode($Out[$i]),  0, 'L');
			$blSocY+=3;
		}
		$pdf->SetXY($blDestX,$Yoff+$blSocY);
		$pdf->MultiCell($blW,5, $this->destinataire->cp . " " . $this->destinataire->ville,  0, 'L');
		$blSocY+=4;
		//Tel Client
		$pdf->SetXY($blDestX,$Yoff+$blSocY);
		$pdf->SetFont('Arial','',7);
		$pdf->MultiCell($blW,3, "Tel : ".$this->destinataire->phone_pro, 0, 'L');
	}
}
?>
