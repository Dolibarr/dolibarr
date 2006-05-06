<?php
/* Copyright (C) 2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2005 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005 Regis Houssin        <regis.houssin@cap-networks.com>
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
 *
 * $Id$
 * $Source$
 */

require_once DOL_DOCUMENT_ROOT."/expedition/mods/pdf/ModelePdfExpedition.class.php";
require_once DOL_DOCUMENT_ROOT."/contact.class.php";

Class pdf_expedition_merou extends ModelePdfExpedition
{

	function pdf_expedition_merou($db=0)
	{ 
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
	}

	//*****************************
	//Creation du Document
	//Initialisation des données
	//*****************************
	function generate(&$objExpe){
      		global $user,$langs,$conf;
		//Initialisation des langues
		$langs->load("main");
		$langs->load("bills");
		$langs->load("products");
		//Generation de la fiche
		$this->expe = $objExpe;
		$this->expe->fetch_commande();
		//Creation du Client
		$this->soc = new Societe($this->db);
		$this->soc->fetch($this->expe->commande->soc_id);
		//Creation de l expediteur
		$this->expediteur = $this->soc;
		//Creation du destinataire
		$this->destinataire = new Contact($this->db);
//		$this->expe->commande->fetch($this->commande->id);
//print_r($this->expe);
		$idcontact = $this->expe->commande->getIdContact('external','DESTINATAIRE');
		$this->destinataire->fetch($idcontact[0]);
		//Creation du livreur
		$idcontact = $this->expe->commande->getIdContact('internal','LIVREUR');
		$this->livreur = new User($this->db,$idcontact[0]);
		if ($idcontact[0]) $this->livreur->fetch();

		//Verification de la configuration
        if ($conf->expedition->dir_output)
        {
			$forbidden_chars=array("/","\\",":","*","?","\"","<",">","|","[","]",",",";","=");
			$expref = str_replace($forbidden_chars,"_",$this->expe->ref);
			$dir = $conf->expedition->dir_output . "/" . $this->expe->ref . "/" ;
			$file = $dir .$this->expe->ref . ".pdf";
			//Si le dossier n existe pas 
	  		if (! file_exists($dir)){
	      			umask(0);
				//On tente de le creer
	      			if (! mkdir($dir, 0755)){
                    			$this->error=$langs->trans("ErrorCanNotCreateDir",$dir);
                    			return 0;
				}
	    		}
			//Si le dossier existe
			if (file_exists($dir))
			{
            	// Initialisation Bon vierge
				$this->FPDF('l','mm','A5');
				$this->Open();
				$this->AddPage();
				//Generation de l entete du fichier
				$this->SetTitle($this->expe->ref);
				$this->SetSubject($langs->trans("Sending"));
				$this->SetCreator("EXPRESSIV Dolibarr ".DOL_VERSION);
				$this->SetAuthor($user->fullname);
				$this->SetMargins(10, 10, 10);
				$this->SetAutoPageBreak(1,0);
				//Insertion de l entete
				$this->_pagehead($this->expe);
				//Initiailisation des coordonnées
				$tab_top = 53;
				$tab_height = 70;
				$this->SetFillColor(240,240,240);
				$this->SetTextColor(0,0,0);
				$this->SetFont('Arial','', 7);
				$this->SetXY (10, $tab_top + 5 );
				$iniY = $this->GetY();
				$curY = $this->GetY();
				$nexY = $this->GetY();
				//Generation du tableau
				$this->_tableau($tab_top, $tab_height, $nexY);
				//Recuperation des produits de la commande.
				$this->expe->commande->fetch_lignes();
				$Produits = $this->expe->commande->lignes;
				$nblignes = sizeof($Produits);
				for ($i = 0 ; $i < $nblignes ; $i++){
					//Generation du produit
					$Prod = new Product($this->db);
					$Prod->fetch($Produits[$i]->product_id);
					//Creation des cases à cocher
					$this->rect(10+3, $curY+1, 3, 3);
					$this->rect(20+3, $curY+1, 3, 3);
					//Insertion de la reference du produit
					$this->SetXY (30, $curY );
					$this->SetFont('Arial','B', 7);			
					$this->MultiCell(20, 5, $Prod->ref, 0, 'L', 0);
					//Insertion du libelle
					$this->SetFont('Arial','', 7);			
					$this->SetXY (50, $curY );
					$this->MultiCell(130, 5, stripslashes($Prod->libelle), 0, 'L', 0);
					//Insertion de la quantite
					$this->SetFont('Arial','', 7);			
					$this->SetXY (180, $curY );
					$this->MultiCell(20, 5, $Produits[$i]->qty, 0, 'L', 0);
					//Generation de la page 2
					$curY += 4;
					$nexY = $curY; 
					if ($nexY > ($tab_top+$tab_height-10) && $i < $nblignes - 1){
						$this->_tableau($tab_top, $tab_height, $nexY);
						$this->_pagefoot();
						$this->AliasNbPages();
						$this->AddPage();
						$nexY = $iniY;
						$this->_pagehead($this->expe);
						$this->SetTextColor(0,0,0);
						$this->SetFont('Arial','', 7);
					}
				}
			//Insertio ndu pied de page
			$this->_pagefoot($propale);
			$this->AliasNbPages();
			//Cloture du pdf
			$this->Close();
			//Ecriture du pdf
			$this->Output($file);
			return 1;
			}
		}
	}

	//********************************
	// Generation du tableau
	//********************************
	function _tableau($tab_top, $tab_height, $nexY){
		global $langs;
		$langs->load("main");
		$langs->load("bills");
		$this->SetFont('Arial','B',8);
		$this->SetXY(10,$tab_top);
		$this->MultiCell(10,5,"LS",0,'C',1);
		$this->line(20, $tab_top, 20, $tab_top + $tab_height);
		$this->SetXY(20,$tab_top);
		$this->MultiCell(10,5,"LR",0,'C',1);
		$this->line(30, $tab_top, 30, $tab_top + $tab_height);
		$this->SetXY(30,$tab_top);
		$this->MultiCell(20,5,$langs->trans("Ref"),0,'C',1);
		$this->SetXY(50,$tab_top);
		$this->MultiCell(130,5,$langs->trans("Description"),0,'L',1);
		$this->SetXY(180,$tab_top);
		$this->MultiCell(20,5,$langs->trans("Quantity"),0,'L',1);
		$this->Rect(10, $tab_top, 190, $tab_height);
	}

	//********************************
	// Generation du Pied de page
	//********************************
	function _pagefoot(){
		$this->SetFont('Arial','',8);
		$this->SetY(-23);
		$this->MultiCell(100, 3, "Déclare avoir reçu les marchandises ci-dessus en bon état,", 0, 'L');
		$this->SetY(-13);
		$this->MultiCell(100, 3, "A___________________________________ le ____/_____/__________" , 0, 'C');
		$this->SetXY(120,-23);
		$this->MultiCell(100, 3, "Nom et Signature : " , 0, 'C');
		$this->SetXY(-10,-10);
		$this->MultiCell(10, 3, $this->PageNo().'/{nb}', 0, 'R');
	}


	//********************************
	// Generation de l entete
	//********************************
	function _pagehead($exp){
		GLOBAL $langs;
		$tab4_top = 60;
		$tab4_hl = 6;
		$tab4_sl = 4;
		$ligne = 2;
		//*********************LOGO****************************
	        if (defined("FAC_PDF_LOGO") && FAC_PDF_LOGO){
            		$this->SetXY(10,5);
            		if (file_exists(FAC_PDF_LOGO)) {
                		$this->Image(FAC_PDF_LOGO, 10, 5,85.0, 17.0, 'PNG');
            		}else {
				//Cas Erreur Fichier introuvable
				$this->SetTextColor(200,0,0);
				$this->SetFont('Arial','B',8);
				$this->MultiCell(80, 3, $langs->trans("ErrorLogoFileNotFound",FAC_PDF_LOGO), 0, 'L');
				$this->MultiCell(80, 3, $langs->trans("ErrorGoToModuleSetup"), 0, 'L');
			}
        	}else if (defined("FAC_PDF_INTITULE")){
            		$this->MultiCell(80, 6, FAC_PDF_INTITULE, 0, 'L');
        	}
		//*********************Entete****************************
		//Nom du Document
		$Yoff = 0;
		$this->SetXY(60,7);
		$this->SetFont('Arial','B',14);
		$this->SetTextColor(0,0,0);
		$this->MultiCell(0, 8, "BON DE LIVRAISON", '' , 'L');
		//Num Expedition
		$Yoff = $Yoff+7;
		$Xoff = 115;
//		$this->rect($Xoff, $Yoff, 85, 8);
		$this->SetXY($Xoff,$Yoff);
		$this->SetFont('Arial','',8);
		$this->SetTextColor(0,0,0);
		$this->MultiCell(0, 8, "Num Bon de Livraison : ".$exp->ref, '' , 'L');
		$this->Code39($Xoff+43, $Yoff+1, $this->expe->ref,$ext = true, $cks = false, $w = 0.4, $h = 4, $wide = true);
		//Num Commande
		$Yoff = $Yoff+10;
//		$this->rect($Xoff, $Yoff, 85, 8);
		$this->SetXY($Xoff,$Yoff);
		$this->SetFont('Arial','',8);
		$this->SetTextColor(0,0,0);
		$this->MultiCell(0, 8, "Num Commande : ".$exp->commande->ref, '' , 'L');
		$this->Code39($Xoff+43, $Yoff+1, $exp->commande->ref,$ext = true, $cks = false, $w = 0.4, $h = 4, $wide = true);
		//Definition Emplacement du bloc Societe
		$blSocX=11;
		$blSocY=25;
		$blSocW=50;
		$blSocX2=$blSocW+$blSocXs;
		$this->SetTextColor(0,0,0);
		//Adresse Internet
		if (defined("FAC_PDF_WWW")){
			$this->SetXY($blSocX,$blSocY);
			$this->SetFont('Arial','B',8);
			$this->MultiCell($blSocW, 3, FAC_PDF_WWW, '' , 'L');
		}
		if (defined("FAC_PDF_ADRESSE")){
			$this->SetFont('Arial','',7);
			$this->SetXY($blSocX,$blSocY+3);
			$this->MultiCell($blSocW, 3, FAC_PDF_ADRESSE, '' , 'L');
		}
		
		if (defined("FAC_PDF_ADRESSE2")){
			$this->SetFont('Arial','',7);
			$this->SetXY($blSocX,$blSocY+6);
			$this->MultiCell($blSocW, 3, FAC_PDF_ADRESSE2, '' , 'L');
		}
		
		if (defined("FAC_PDF_TEL")){
			$this->SetFont('Arial','',7);
			$this->SetXY($blSocX,$blSocY+10);
			$this->MultiCell($blSocW, 3, "Tel : " . FAC_PDF_TEL, '' , 'L');
		}
		
		if (defined("FAC_PDF_MEL")){
			$this->SetFont('Arial','',7);
			$this->SetXY($blSocX,$blSocY+13);
			$this->MultiCell(40, 3, "Email : " . FAC_PDF_MEL, '' , 'L');
		}
		
		if (defined("FAC_PDF_FAX")){
			$this->SetFont('Arial','',7);
			$this->SetXY($blSocX,$blSocY+16);
			$this->MultiCell(40, 3, "Fax : " . FAC_PDF_FAX, '' , 'L');
		}
		
		if (defined("MAIN_INFO_SIRET")){
			$this->SetFont('Arial','',7);
			$this->SetXY($blSocX2,$blSocY+10);
			$this->MultiCell($blSocW, 3, "SIRET : " . MAIN_INFO_SIRET, '' , 'L');
		}
		
		if (defined("MAIN_INFO_APE")){
			$this->SetFont('Arial','',7);
			$this->SetXY($blSocX2,$blSocY+13);
			$this->MultiCell($blSocW, 3, "APE : " . MAIN_INFO_APE, '' , 'L');
		}
		
		if (defined("MAIN_INFO_TVAINTRA")){
			$this->SetFont('Arial','',7);
			$this->SetXY($blSocX2,$blSocY+16);
			$this->MultiCell($blSocW, 3, "ICOMM : " . MAIN_INFO_TVAINTRA, '' , 'L');
		}
	
		//Date Expedition
		$Yoff = $Yoff+7;
		$this->SetXY($blSocX,$blSocY+20);
		$this->SetFont('Arial','B',8);
		$this->SetTextColor(0,0,0);
		$this->MultiCell(50, 8, "Date : " . strftime("%d %b %Y", $exp->date), '' , 'L');
		//Date Expedition
		$this->SetXY($blSocX2,$blSocY+20);
		$this->SetFont('Arial','B',8);
		$this->SetTextColor(0,0,0);
		$this->MultiCell(50, 8, "Livreur(s) : ".$this->livreur->fullname, '' , 'L');
		/**********************************/
		//Emplacement Informations Expediteur (Client)
		/**********************************/
		$Ydef = $Yoff;
		$blExpX=$Xoff-20;
		$blW=50;
		$Yoff = $Yoff+5;
		$Ydef = $Yoff;
		$blSocY = 1;
		//Titre
		$this->SetXY($blExpX,$Yoff-3);
		$this->SetFont('Arial','B',7);
		$this->MultiCell($blW,3, 'Expéditeur', 0, 'L');
		$this->Rect($blExpX, $Yoff, $blW, 20);
		//Nom Client
		$this->SetXY($blExpX,$Yoff+$blSocY);
		$this->SetFont('Arial','B',7);
		$this->MultiCell($blW,3, $this->expediteur->nom, 0, 'C');
		$this->SetFont('Arial','',7);
		$blSocY+=3;
		//Adresse Client
		//Gestion des Retours chariots
		$Out=split("\n",$this->expediteur->adresse);
		for ($i=0;$i<count($Out);$i++) {
			$this->SetXY($blExpX,$Yoff+$blSocY);
			$this->MultiCell($blW,5,urldecode($Out[$i]),  0, 'L');
			$blSocY+=3;
		}
		$this->SetXY($blExpX,$Yoff+$blSocY);
		$this->MultiCell($blW,5, $this->expediteur->cp . " " . $this->expediteur->ville,  0, 'L');
		$blSocY+=4;
		//Tel Client
		$this->SetXY($blExpX,$Yoff+$blSocY);
		$this->SetFont('Arial','',7);
		$this->MultiCell($blW,3, "Tel : ".$this->expediteur->tel, 0, 'L');
	
		/**********************************/
		//Emplacement Informations Destinataire (Contact livraison)
		/**********************************/
		$blDestX=$blExpX+55;
		$blW=50;
		$Yoff = $Ydef;
		$blSocY = 1;
		//Titre
		$this->SetXY($blDestX,$Yoff-3);
		$this->SetFont('Arial','B',7);
		$this->MultiCell($blW,3, 'Destinataire', 0, 'L');
		$this->Rect($blDestX, $Yoff, $blW, 20);
		//Nom Client
		$this->SetXY($blDestX,$Yoff+$blSocY);
		$this->SetFont('Arial','B',7);
		$this->MultiCell($blW,3, $this->destinataire->fullname, 0, 'C');
		$this->SetFont('Arial','',7);
		$blSocY+=3;
		//Adresse Client
		//Gestion des Retours chariots
		$Out=split("\n",$this->destinataire->address);
		for ($i=0;$i<count($Out);$i++) {
			$this->SetXY($blDestX,$Yoff+$blSocY);
			$this->MultiCell($blW,5,urldecode($Out[$i]),  0, 'L');
			$blSocY+=3;
		}
		$this->SetXY($blDestX,$Yoff+$blSocY);
		$this->MultiCell($blW,5, $this->destinataire->cp . " " . $this->destinataire->ville,  0, 'L');
		$blSocY+=4;
		//Tel Client
		$this->SetXY($blDestX,$Yoff+$blSocY);
		$this->SetFont('Arial','',7);
		$this->MultiCell($blW,3, "Tel : ".$this->destinataire->phone_pro, 0, 'L');
	}
}
?>
