<?php
/* Copyright (C) 2005      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2005-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *	\file       htdocs/includes/modules/expedition/pdf/pdf_expedition_merou.modules.php
 *	\ingroup    expedition
 *	\brief      Fichier de la classe permettant de generer les bordereaux envoi au modele Merou
 *	\version    $Id$
 */

require_once DOL_DOCUMENT_ROOT."/includes/modules/expedition/pdf/ModelePdfExpedition.class.php";
require_once DOL_DOCUMENT_ROOT."/contact/class/contact.class.php";
require_once(DOL_DOCUMENT_ROOT."/lib/company.lib.php");


/**
 *	\class      pdf_expedition_merou
 *	\brief      Classe permettant de generer les borderaux envoi au modele Merou
 */
Class pdf_expedition_merou extends ModelePdfExpedition
{
	var $emetteur;	// Objet societe qui emet


	/**
	 *	\brief  Constructor
	 *	\param	db		Database handler
	 */
	function pdf_expedition_merou($db=0)
	{
		global $conf,$langs,$mysoc;

		$this->db = $db;
		$this->name = "merou";
		//$this->description = "Modele Merou A5";
		$this->description = $langs->trans("DocumentModelMerou");

		$this->type = 'pdf';
		$this->page_largeur = 148.5;
		$this->page_hauteur = 210;
		$this->format = array($this->page_largeur,$this->page_hauteur);

		$this->option_logo = 1;                    // Affiche logo

		// Recupere emmetteur
		$this->emetteur=$mysoc;
		if (! $this->emetteur->pays_code) $this->emetteur->pays_code=substr($langs->defaultlang,-2);    // Par defaut, si n'etait pas defini
	}


	/**
	 *		\brief      Fonction generant le document sur le disque
	 *		\param	    obj				Objet expedition a generer (ou id si ancienne methode)
	 *		\param		outputlangs		Lang output object
	 * 	 	\return	    int     		1=ok, 0=ko
	 */
	function write_file(&$object, $outputlangs)
	{
		global $user,$conf,$langs,$mysoc;

		if (! is_object($outputlangs)) $outputlangs=$langs;
		// Force output charset to ISO, because, FPDF expect text encoded in ISO
		$outputlangs->charset_output='ISO-8859-1';

		$outputlangs->load("main");
		$outputlangs->load("dict");
		$outputlangs->load("companies");
		$outputlangs->load("bills");
		$outputlangs->load("products");
		$outputlangs->load("propal");
		$outputlangs->load("sendings");
		$outputlangs->load("deliveries");

		//Generation de la fiche
		$this->expe = $object;

		//Verification de la configuration
		if ($conf->expedition->dir_output."/sending")
		{
			$object->fetch_thirdparty();
			
			$origin = $object->origin;

			//Creation de l expediteur
			$this->expediteur = $mysoc;

			//Creation du destinataire
			$this->destinataire = new Contact($this->db);
			//		$pdf->expe->commande->fetch($pdf->commande->id);
			//print_r($pdf->expe);
			$idcontact = $object->$origin->getIdContact('external','SHIPPING');
			$this->destinataire->fetch($idcontact[0]);

			//Creation du livreur
			$idcontact = $object->$origin->getIdContact('internal','LIVREUR');
			$this->livreur = new User($this->db);
			if ($idcontact[0]) $this->livreur->fetch($idcontact[0]);


			// Definition de $dir et $file
			if ($object->specimen)
			{
				$dir = $conf->expedition->dir_output."/sending";
				$file = $dir . "/SPECIMEN.pdf";
			}
			else
			{
				$expref = dol_sanitizeFileName($object->ref);
				$dir = $conf->expedition->dir_output . "/sending/" . $expref;
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
				// Protection et encryption du pdf
				if ($conf->global->PDF_SECURITY_ENCRYPTION)
				{
					$pdf=new FPDI_Protection('l','mm',$this->format);
					$pdfrights = array('print'); // Ne permet que l'impression du document
					$pdfuserpass = ''; // Mot de passe pour l'utilisateur final
					$pdfownerpass = NULL; // Mot de passe du proprietaire, cree aleatoirement si pas defini
					$pdf->SetProtection($pdfrights,$pdfuserpass,$pdfownerpass);
				}
				else
				{
					$pdf=new FPDI('l','mm',$this->format);
				}

                if (class_exists('TCPDF'))
                {
                    $pdf->setPrintHeader(false);
                    $pdf->setPrintFooter(false);
                }
				$pdf->SetFont('Helvetica');

				$pdf->Open();
				$pagenb=0;
				$pdf->SetDrawColor(128,128,128);

				//Generation de l entete du fichier
				$pdf->SetTitle($outputlangs->convToOutputCharset($object->ref));
				$pdf->SetSubject($outputlangs->transnoentities("Sending"));
				$pdf->SetCreator("Dolibarr ".DOL_VERSION);
				$pdf->SetAuthor($outputlangs->convToOutputCharset($user->getFullName($outputlangs)));
				$pdf->SetKeyWords($outputlangs->convToOutputCharset($object->ref)." ".$outputlangs->transnoentities("Sending"));
				if ($conf->global->MAIN_DISABLE_PDF_COMPRESSION) $pdf->SetCompression(false);

				$pdf->SetMargins(10, 10, 10);
				$pdf->SetAutoPageBreak(1,0);

				$pdf->SetFont('','', 7);

				// New page
				$pdf->AddPage();
				$pagenb++;
				$this->_pagehead($pdf, $this->expe, $outputlangs);
				$pdf->SetFont('','', 7);
				$pdf->MultiCell(0, 3, '', 0, 'J');		// Set interline to 3
				$pdf->SetTextColor(0,0,0);

				//Initialisation des coordonnees
				$tab_top = 53;
				$tab_height = 70;
				$pdf->SetFillColor(240,240,240);
				$pdf->SetTextColor(0,0,0);
				$pdf->SetFont('','', 7);
				$pdf->SetXY (10, $tab_top + 5 );
				$iniY = $pdf->GetY();
				$curY = $pdf->GetY();
				$nexY = $pdf->GetY();
				//Generation du tableau
				$this->_tableau($pdf, $tab_top, $tab_height, $nexY, $outputlangs);

				//Recuperation des produits de la commande.
				$nblignes = sizeof($object->$origin->lines);

				for ($i = 0 ; $i < $nblignes ; $i++)
				{
					// Description de la ligne produit
					$libelleproduitservice=pdf_getlinedesc($object->$origin,$i,$outputlangs);
					//if ($i==1) { print $object->commande->lignes[$i]->libelle.' - '.$libelleproduitservice; exit; }

					//Creation des cases a cocher
					$pdf->rect(10+3, $curY+1, 3, 3);
					$pdf->rect(20+3, $curY+1, 3, 3);
					//Insertion de la reference du produit
					$pdf->SetXY (30, $curY+1 );
					$pdf->SetFont('','B', 7);
					$pdf->MultiCell(24, 3, $outputlangs->convToOutputCharset($object->$origin->lines[$i]->ref), 0, 'L', 0);
					//Insertion du libelle
					$pdf->SetFont('','', 7);
					$pdf->SetXY (50, $curY+1 );
					$pdf->writeHTMLCell(90, 3, 50, $curY+1, $outputlangs->convToOutputCharset($libelleproduitservice), 0, 'L', 0);
					//Insertion de la quantite commandee
					$pdf->SetFont('','', 7);
					$pdf->SetXY (140, $curY+1 );
					$pdf->MultiCell(30, 3, $object->lines[$i]->qty_asked, 0, 'C', 0);
					//Insertion de la quantite a envoyer
					$pdf->SetFont('','', 7);
					$pdf->SetXY (170, $curY+1 );
					$pdf->MultiCell(30, 3, $object->lines[$i]->qty_shipped, 0, 'C', 0);

					//Generation de la page 2
					$curY += (dol_nboflines_bis($libelleproduitservice,0,$outputlangs->charset_output)*3+1);
					$nexY = $curY;
					if ($nexY > ($tab_top+$tab_height-10) && $i < $nblignes - 1)
					{
						$this->_tableau($pdf, $tab_top, $tab_height, $nexY, $outputlangs);
						$this->_pagefoot($pdf, $object, $outputlangs);
						$pdf->AliasNbPages();

						$curY = $iniY;

						// New page
						$pdf->AddPage();
						$pagenb++;
						$this->_pagehead($pdf, $this->expe, $outputlangs);
						$pdf->MultiCell(0, 3, '', 0, 'J');		// Set interline to 3
						$pdf->SetTextColor(0,0,0);
						$pdf->SetFont('','', 7);
					}
				}
				//Insertion du pied de page
				$this->_pagefoot($pdf, $object, $outputlangs);

				$pdf->AliasNbPages();

				$pdf->Close();

				$pdf->Output($file,'F');
                if (! empty($conf->global->MAIN_UMASK))
                    @chmod($file, octdec($conf->global->MAIN_UMASK));

				return 1;
			}
			else
			{
				$this->error=$outputlangs->transnoentities("ErrorCanNotCreateDir",$dir);
				return 0;
			}
		}
		else
		{
			$this->error=$outputlangs->transnoentities("ErrorConstantNotDefined","EXP_OUTPUTDIR");
			return 0;
		}
		$this->error=$outputlangs->transnoentities("ErrorUnknown");
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

		$pdf->SetFont('','B',8);
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

	/**
	 *   	\brief      Show footer of page
	 *   	\param      pdf     		PDF factory
	 * 		\param		object			Object invoice
	 *      \param      outputlangs		Object lang for output
	 */
	function _pagefoot(&$pdf, $object, $outputlangs)
	{
		$pdf->SetFont('','',8);
		$pdf->SetY(-23);
		$pdf->MultiCell(100, 3, $outputlangs->transnoentities("GoodStatusDeclaration") , 0, 'L');
		$pdf->SetY(-13);
		$pdf->MultiCell(100, 3, $outputlangs->transnoentities("ToAndDate") , 0, 'C');
		$pdf->SetXY(120,-23);
		$pdf->MultiCell(100, 3, $outputlangs->transnoentities("NameAndSignature") , 0, 'C');
		$pdf->SetXY(-10,-10);
		$pdf->MultiCell(10, 3, $pdf->PageNo().'/{nb}', 0, 'R');
	}


	/**
	 *   	\brief      Show header of page
	 *      \param      pdf             Object PDF
	 *      \param      object          Object invoice
	 *      \param      showadress      0=no, 1=yes
	 *      \param      outputlang		Object lang for output
	 */
	function _pagehead(&$pdf, $object, $outputlangs)
	{
		global $conf, $langs;
		
		$origin = $object->origin;

		pdf_pagehead($pdf,$outputlangs,$this->page_hauteur);

			//Affiche le filigrane brouillon - Print Draft Watermark
		if($object->statut==0 && (! empty($conf->global->SENDING_DRAFT_WATERMARK)) )
		{
            pdf_watermark($pdf,$outputlangs,$this->page_hauteur,$this->page_largeur,'mm',$conf->global->SENDING_DRAFT_WATERMARK);
		}

		$Xoff = 90;
		$Yoff = 0;

		$tab4_top = 60;
		$tab4_hl = 6;
		$tab4_sl = 4;
		$ligne = 2;

		//*********************LOGO****************************
		$pdf->SetXY(11,7);
		$logo=$conf->mycompany->dir_output.'/logos/'.$this->emetteur->logo;
		if ($this->emetteur->logo)
		{
			if (is_readable($logo))
			{
				$pdf->Image($logo,10, 5, 0, 22);
			}
			else
			{
				$pdf->SetTextColor(200,0,0);
				$pdf->SetFont('','B',8);
				$pdf->MultiCell(100, 3, $langs->transnoentities("ErrorLogoFileNotFound",$logo), 0, 'L');
				$pdf->MultiCell(100, 3, $langs->transnoentities("ErrorGoToModuleSetup"), 0, 'L');
			}
		}
		else
		{
			$text=$this->emetteur->nom;
			$pdf->MultiCell(100, 3, $outputlangs->convToOutputCharset($text), 0, 'L');
		}

		//*********************Entete****************************
		//Nom du Document
		$pdf->SetXY($Xoff,7);
		$pdf->SetFont('','B',12);
		$pdf->SetTextColor(0,0,0);
		$pdf->MultiCell(0, 3, $outputlangs->transnoentities("SendingSheet"), '' , 'L');	// Bordereau expedition
		//Num Expedition
		$Yoff = $Yoff+7;
		$Xoff = 142;
		//$pdf->rect($Xoff, $Yoff, 85, 8);
		$pdf->SetXY($Xoff,$Yoff);
		$pdf->SetFont('','',8);
		$pdf->SetTextColor(0,0,0);
		$pdf->MultiCell(0, 3, $outputlangs->transnoentities("RefSending").': '.$outputlangs->convToOutputCharset($object->ref), '' , 'R');
		//$this->Code39($Xoff+43, $Yoff+1, $object->ref,$ext = true, $cks = false, $w = 0.4, $h = 4, $wide = true);

		// Add list of linked orders
	    $object->load_object_linked();

	    if ($conf->commande->enabled)
		{
			$outputlangs->load('orders');
			foreach($object->linked_object as $key => $val)
			{
				if ($key == $origin)
				{
					for ($i = 0; $i<sizeof($val);$i++)
					{
						$classname = ucfirst($origin);
						$linkedobject = new $classname($this->db);
						$result=$linkedobject->fetch($val[$i]);
						if ($result >= 0)
						{
							$Yoff = $Yoff+4;
							$pdf->SetXY($Xoff,$Yoff);
							$pdf->SetFont('','',8);
							$text=$linkedobject->ref;
							if ($linkedobject->ref_client) $text.=' ('.$linkedobject->ref_client.')';
							$pdf->MultiCell(0, 3, $outputlangs->transnoentities("RefOrder")." : ".$outputlangs->transnoentities($text), '', 'R');
						}
					}
				}
			}
		}

		//$this->Code39($Xoff+43, $Yoff+1, $object->commande->ref,$ext = true, $cks = false, $w = 0.4, $h = 4, $wide = true);
		//Definition Emplacement du bloc Societe
		$Xoff = 110;
		$blSocX=90;
		$blSocY=24;
		$blSocW=50;
		$blSocX2=$blSocW+$blSocXs;

		// Sender name
		$pdf->SetTextColor(0,0,60);
		$pdf->SetXY($blSocX,$blSocY);
		$pdf->MultiCell(80, 3, $outputlangs->convToOutputCharset($this->emetteur->nom), 0, 'L');
		$pdf->SetTextColor(0,0,0);

		// Sender properties
		$carac_emetteur = pdf_build_address($outputlangs,$this->emetteur);

		$pdf->SetFont('','',7);
		$pdf->SetXY($blSocX,$blSocY+3);
		$pdf->MultiCell(80, 2, $carac_emetteur);



		if ($object->client->code_client)
		{
			$Yoff+=7;
			$posy=$Yoff;
			$pdf->SetXY(100,$posy);
			$pdf->SetTextColor(0,0,60);
			$pdf->MultiCell(100, 3, $outputlangs->transnoentities("CustomerCode")." : " . $outputlangs->transnoentities($object->client->code_client), '', 'R');
		}

		//Date Expedition
		$Yoff = $Yoff+7;
		$pdf->SetXY($blSocX-80,$blSocY+20);
		$pdf->SetFont('','B',8);
		$pdf->SetTextColor(0,0,0);
		$pdf->MultiCell(50, 8, $outputlangs->transnoentities("Date")." : " . dol_print_date($object->date_delivery,'day',false,$outputlangs,true), '' , 'L');

		// Deliverer
		$pdf->SetXY($blSocX-80,$blSocY+23);
		$pdf->SetFont('','',8);
		$pdf->SetTextColor(0,0,0);
		$pdf->MultiCell(50, 8, $outputlangs->transnoentities("Deliverer")." ".$outputlangs->convToOutputCharset($this->livreur->getFullName($outputlangs)), '' , 'L');


		/**********************************/
		//Emplacement Informations Expediteur (My Company)
		/**********************************/
		$Ydef = $Yoff;
		$blExpX=$Xoff-20;
		$blW=52;
		$Yoff = $Yoff+5;
		$Ydef = $Yoff;
		$blSocY = 1;
		$pdf->Rect($blExpX, $Yoff, $blW, 20);

		$object->fetch_thirdparty();

		// If SHIPPING contact defined on order, we use it
		$usecontact=false;
		$arrayidcontact=$object->$origin->getIdContact('external','SHIPPING');
		if (sizeof($arrayidcontact) > 0)
		{
			$usecontact=true;
			$result=$object->fetch_contact($arrayidcontact[0]);
		}

		// Recipient name
		if (! empty($usecontact))
		{
			// On peut utiliser le nom de la societe du contact
			if ($conf->global->MAIN_USE_COMPANY_NAME_OF_CONTACT) $socname = $object->contact->socname;
			else $socname = $object->client->nom;
			$carac_client_name=$outputlangs->convToOutputCharset($socname);
		}
		else
		{
			$carac_client_name=$outputlangs->convToOutputCharset($object->client->nom);
		}

		$carac_client=pdf_build_address($outputlangs,$this->emetteur,$object->client,$object->contact,$usecontact,'target');


		$blDestX=$blExpX+55;
		$blW=50;
		$Yoff = $Ydef +1;

		$pdf->Rect($blDestX, $Yoff-1, $blW, 20);

		//Titre
		$pdf->SetFont('','B',7);
		$pdf->SetXY($blDestX,$Yoff-4);
		$pdf->MultiCell($blW,3, $outputlangs->transnoentities("Recipient"), 0, 'L');

		// Show customer/recipient
		$pdf->SetFont('','B',7);
		$pdf->SetXY($blDestX,$Yoff);
		$pdf->MultiCell($blW,3, $carac_client_name, 0, 'L');

		$pdf->SetFont('','',7);
		//$posy=$pdf->GetY(); //Auto Y coord readjust for multiline name
		$pdf->SetXY($blDestX,$pdf->GetY());
		$pdf->MultiCell($blW,2, $carac_client);
	}
}
?>
