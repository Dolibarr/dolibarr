<?php
/* Copyright (C) 2001-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2002-2003 Jean-Louis Bergamo   <jlb@j1b.org>
 * Copyright (C) 2006-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 */

/* Inspire de PDF_Label
 * PDF_Label - PDF label editing
 * @package PDF_Label
 * @author Laurent PASSEBECQ <lpasseb@numericable.fr>
 * @copyright 2003 Laurent PASSEBECQ
 * disponible ici : http://www.fpdf.org/fr/script/script29.php
 */

////////////////////////////////////////////////////
// PDF_Label
//
// Classe afin d'éditer au format PDF des étiquettes
// au format Avery ou personnalisé
//
//
// Copyright (C) 2003 Laurent PASSEBECQ (LPA)
// Basé sur les fonctions de Steve Dillon : steved@mad.scientist.com
//
//-------------------------------------------------------------------
// VERSIONS :
// 1.0  : Initial release
// 1.1  : +	: Added unit in the constructor
//	  + : Now Positions start @ (1,1).. then the first image @top-left of a page is (1,1)
//	  + : Added in the description of a label :
//		font-size	: defaut char size (can be changed by calling Set_Char_Size(xx);
//		paper-size	: Size of the paper for this sheet (thanx to Al Canton)
//		metric		: type of unit used in this description
//				  You can define your label properties in inches by setting metric to 'in'
//				  and printing in millimiter by setting unit to 'mm' in constructor.
//	  Added some labels :
//	        5160, 5161, 5162, 5163,5164 : thanx to Al Canton : acanton@adams-blake.com
//		8600 						: thanx to Kunal Walia : kunal@u.washington.edu
//	  + : Added 3mm to the position of labels to avoid errors
////////////////////////////////////////////////////

/**
 \file       htdocs/adherents/cartes/PDF_card.class.php
 \ingroup    adherent
 \brief      Fichier de la classe permettant d'éditer au format PDF des étiquettes au format Avery ou personnalisé
 \author     Steve Dillon
 \author	    Laurent Passebecq
 \author	    Rodolphe Quiedville
 \author	    Jean Louis Bergamo.
 \version    $Id$
 */

require_once(DOL_DOCUMENT_ROOT.'/lib/pdf.lib.php');
require_once(DOL_DOCUMENT_ROOT.'/includes/fpdf/fpdfi/fpdi_protection.php');


/**
 \class      PDF_card
 \brief      Classe afin d'éditer au format PDF des étiquettes au format Avery ou personnalisé
 */
class PDF_card extends FPDF {

	// Propriétés privées
	var $_Avery_Name	= '';	// Nom du format de l'étiquette
	var $_Margin_Left	= 0;	// Marge de gauche de l'étiquette
	var $_Margin_Top	= 0;	// marge en haut de la page avant la première étiquette
	var $_X_Space 	= 0;	// Espace horizontal entre 2 bandes d'étiquettes
	var $_Y_Space 	= 0;	// Espace vertical entre 2 bandes d'étiquettes
	var $_X_Number 	= 0;	// Nombre d'étiquettes sur la largeur de la page
	var $_Y_Number 	= 0;	// Nombre d'étiquettes sur la hauteur de la page
	var $_Width 		= 0;	// Largeur de chaque étiquette
	var $_Height 		= 0;	// Hauteur de chaque étiquette
	var $_Char_Size	= 10;	// Hauteur des caractères
	var $_Line_Height	= 10;	// Hauteur par défaut d'une ligne
	var $_Metric 		= 'mm';	// Type of metric.. Will help to calculate good values
	var $_Metric_Doc 	= 'mm';	// Type of metric for the doc..

	var $_COUNTX = 1;
	var $_COUNTY = 1;
	var $_First = 1;

	// Listing of labels size
	var $_Avery_Labels = array (
			      '5160'=>array('name'=>'5160',
					    'paper-size'=>'letter',
					    'metric'=>'mm',
					    'marginLeft'=>1.762,
					    'marginTop'=>10.7,
					    'NX'=>3,
					    'NY'=>10,
					    'SpaceX'=>3.175,
					    'SpaceY'=>0,
					    'width'=>66.675,
					    'height'=>25.4,
					    'font-size'=>8),
			      '5161'=>array('name'=>'5161',
					    'paper-size'=>'letter',
					    'metric'=>'mm',
					    'marginLeft'=>0.967,
					    'marginTop'=>10.7,
					    'NX'=>2,
					    'NY'=>10,
					    'SpaceX'=>3.967,
					    'SpaceY'=>0,
					    'width'=>101.6,
					    'height'=>25.4,
					    'font-size'=>8),
			      '5162'=>array('name'=>'5162',
					    'paper-size'=>'letter',
					    'metric'=>'mm',
					    'marginLeft'=>0.97,
					    'marginTop'=>20.224,
					    'NX'=>2,
					    'NY'=>7,
					    'SpaceX'=>4.762,
					    'SpaceY'=>0,
					    'width'=>100.807,
					    'height'=>35.72,
					    'font-size'=>8),
			      '5163'=>array('name'=>'5163',
					    'paper-size'=>'letter',
					    'metric'=>'mm',
					    'marginLeft'=>1.762,
					    'marginTop'=>10.7,
					    'NX'=>2,
					    'NY'=>5,
					    'SpaceX'=>3.175,
					    'SpaceY'=>0,
					    'width'=>101.6,
					    'height'=>50.8,
					    'font-size'=>8),
			      '5164'=>array('name'=>'5164',
					    'paper-size'=>'letter',
					    'metric'=>'in',
					    'marginLeft'=>0.148,
					    'marginTop'=>0.5,
					    'NX'=>2,
					    'NY'=>3,
					    'SpaceX'=>0.2031,
					    'SpaceY'=>0,
					    'width'=>4.0,
					    'height'=>3.33,
					    'font-size'=>12),
			      '8600'=>array('name'=>'8600',
					    'paper-size'=>'letter',
					    'metric'=>'mm',
					    'marginLeft'=>7.1,
					    'marginTop'=>19,
					    'NX'=>3,
					    'NY'=>10,
					    'SpaceX'=>9.5,
					    'SpaceY'=>3.1,
					    'width'=>66.6,
					    'height'=>25.4,
					    'font-size'=>8),
			      'L7163'=>array('name'=>'L7163',
					     'paper-size'=>'A4',
					     'metric'=>'mm',
					     'marginLeft'=>5,
					     'marginTop'=>15,
					     'NX'=>2,
					     'NY'=>7,
					     'SpaceX'=>25,
					     'SpaceY'=>0,
					     'width'=>99.1,
					     'height'=>38.1,
					     'font-size'=>10),
			      'FREELUG'=>array('name'=>'FREELUG',
					       'paper-size'=>'A4',
					       'metric'=>'mm',
					       'marginLeft'=>0,
					       'marginTop'=>0,
					       'NX'=>2,
					       'NY'=>8,
					       'SpaceX'=>0,
					       'SpaceY'=>0,
					       'width'=>105,
					       'height'=>38,
					       'font-size'=>10),
			      'CARD'=>array('name'=>'CARD',
					    'paper-size'=>'A4',
					    'metric'=>'mm',
					    'marginLeft'=>15,
					    'marginTop'=>15,
					    'NX'=>2,
					    'NY'=>5,
					    'SpaceX'=>0,
					    'SpaceY'=>0,
					    'width'=>85,
					    'height'=>54,
					    'font-size'=>10,
					    'logo1'=>'logo1.jpg',
					    'logo2'=>'logo2.jpg',
					    'fond'=>'fond.jpg')
	);



	/**
	 * Constructor
	 *
	 * @param unknown_type $format		Avery format of label paper. For example 5160, 5161, 5162, 5163, 5164, 8600, L7163
	 * @param unknown_type $posX
	 * @param unknown_type $posY
	 * @param unknown_type $unit
	 * @return PDF_card
	 */
	function PDF_card ($format, $posX=1, $posY=1, $unit='mm')
	{
		if (is_array($format)) {
			// Si c'est un format personnel alors on maj les valeurs
			$Tformat = $format;
		} else {
			// Si c'est un format avery on stocke le nom de ce format selon la norme Avery.
			// Permettra d'aller récupérer les valeurs dans le tableau _Avery_Labels
			$Tformat = $this->_Avery_Labels[$format];
		}

		parent::FPDF('P', $unit, $Tformat['paper-size']);
		$this->SetMargins(0,0);
		$this->SetAutoPageBreak(false);

		$this->_Metric_Doc = $unit;
		// Permet de commencer l'impression à l'étiquette désirée dans le cas où la page a déjà servie
		if ($posX > 0) $posX--; else $posX=0;
		if ($posY > 0) $posY--; else $posY=0;
		$this->_COUNTX = $posX;
		$this->_COUNTY = $posY;
		$this->_Set_Format($Tformat);
	}


	//Méthode qui permet de modifier la taille des caractères
	// Cela modiera aussi l'espace entre chaque ligne
	function Set_Char_Size($pt) {
		if ($pt > 3) {
			$this->_Char_Size = $pt;
			$this->_Line_Height = $this->_Get_Height_Chars($pt);
			$this->SetFont('Arial','',$pt);
		}
	}


	// On imprime une étiqette
	function Add_PDF_card($texte,$header='',$footer='',$outputlangs)
	{
		global $langs;

		if (! is_object($outputlangs)) $outputlangs=$langs;
		// Force output charset to ISO, because, FPDF expects text to be encoded in ISO
		$outputlangs->charset_output='ISO-8859-1';

		$outputlangs->load("main");
		$outputlangs->load("dict");
		$outputlangs->load("companies");
		$outputlangs->load("members");

//		$outputlangs->setPhpLang();

		// We are in a new page, then we must add a page
		if (($this->_COUNTX ==0) and ($this->_COUNTY==0) and (!$this->_First==1)) {
			$this->AddPage();
		}
		$this->_First=0;
		$_PosX = $this->_Margin_Left+($this->_COUNTX*($this->_Width+$this->_X_Space));
		$_PosY = $this->_Margin_Top+($this->_COUNTY*($this->_Height+$this->_Y_Space));

		if ($this->_Avery_Name == "CARD")
		{
			$Tformat=$this->_Avery_Labels["CARD"];
			//$this->_Pointille($_PosX,$_PosY,$_PosX+$this->_Width,$_PosY+$this->_Height,0.3,25);
			$this->_Croix($_PosX,$_PosY,$_PosX+$this->_Width,$_PosY+$this->_Height,0.3,10);
			if($Tformat['fond'] != '' and file_exists($Tformat['fond'])){
				$this->image($Tformat['fond'],$_PosX,$_PosY,$this->_Width,$this->_Height);
			}
			if($Tformat['logo1'] != '' and file_exists($Tformat['logo1'])){
				$this->image($Tformat['logo1'],$_PosX+$this->_Width-21,$_PosY+1,20,20);
			}
			if($Tformat['logo2'] != '' and file_exists($Tformat['logo2'])){
				$this->image($Tformat['logo2'],$_PosX+$this->_Width-21,$_PosY+25,20,20);
			}

			//$this->image('logo1.jpg',$_PosX+$this->_Width-21,$_PosY+1,20);
			if ($header!=''){
				$this->SetXY($_PosX, $_PosY+1);
				$this->Cell($this->_Width, $this->_Line_Height, $outputlangs->convToOutputCharset($header),0,1,'C');
			}
			$this->SetXY($_PosX+3, $_PosY+3+$this->_Line_Height);
			$this->MultiCell($this->_Width, $this->_Line_Height, $outputlangs->convToOutputCharset($texte));
			if ($footer!=''){
				$this->SetXY($_PosX, $_PosY+$this->_Height-$this->_Line_Height-1);
				$this->Cell($this->_Width, $this->_Line_Height, $outputlangs->convToOutputCharset($footer),0,1,'C');
			}

		}
		else
		{
			$this->SetXY($_PosX+3, $_PosY+3);
			$this->MultiCell($this->_Width, $this->_Line_Height, $outputlangs->convToOutputCharset($texte));
		}
		$this->_COUNTY++;

		if ($this->_COUNTY == $this->_Y_Number) {
			// Si on est en bas de page, on remonte le 'curseur' de position
			$this->_COUNTX++;
			$this->_COUNTY=0;
		}

		if ($this->_COUNTX == $this->_X_Number) {
			// Si on est en bout de page, alors on repart sur une nouvelle page
			$this->_COUNTX=0;
			$this->_COUNTY=0;
		}

//		$langs->setPhpLang();	// On restaure langue session
	}


	function _Pointille($x1=0,$y1=0,$x2=210,$y2=297,$epaisseur=1,$nbPointilles=15)
	{
		$this->SetLineWidth($epaisseur);
		$longueur=abs($x1-$x2);
		$hauteur=abs($y1-$y2);
		if($longueur>$hauteur) {
			$Pointilles=($longueur/$nbPointilles)/2; // taille des pointilles
		}
		else {
			$Pointilles=($hauteur/$nbPointilles)/2;
		}
		for($i=$x1;$i<=$x2;$i+=$Pointilles+$Pointilles) {
			for($j=$i;$j<=($i+$Pointilles);$j++) {
				if($j<=($x2-1)) {
	    $this->Line($j,$y1,$j+1,$y1); // on trace le pointill? du haut, point par point
	    $this->Line($j,$y2,$j+1,$y2); // on trace le pointill? du bas, point par point
				}
			}
		}
		for($i=$y1;$i<=$y2;$i+=$Pointilles+$Pointilles) {
			for($j=$i;$j<=($i+$Pointilles);$j++) {
				if($j<=($y2-1)) {
	    $this->Line($x1,$j,$x1,$j+1); // on trace le pointill? du haut, point par point
	    $this->Line($x2,$j,$x2,$j+1); // on trace le pointill? du bas, point par point
				}
			}
		}
	}

	/*
	 * Fonction realisant une croix aux 4 coins des cartes
	 */
	function _Croix($x1=0,$y1=0,$x2=210,$y2=297,$epaisseur=1,$taille=5)
	{
		//$this->Color('#888888');

		$this->SetLineWidth($epaisseur);
		$lg=$taille/2;
		// croix haut gauche
		$this->Line($x1,$y1-$lg,$x1,$y1+$lg);
		$this->Line($x1-$lg,$y1,$x1+$lg,$y1);
		// croix bas gauche
		$this->Line($x1,$y2-$lg,$x1,$y2+$lg);
		$this->Line($x1-$lg,$y2,$x1+$lg,$y2);
		// croix haut droit
		$this->Line($x2,$y1-$lg,$x2,$y1+$lg);
		$this->Line($x2-$lg,$y1,$x2+$lg,$y1);
		// croix bas droit
		$this->Line($x2,$y2-$lg,$x2,$y2+$lg);
		$this->Line($x2-$lg,$y2,$x2+$lg,$y2);

		//$this->Color('#000000');
	}

	// convert units (in to mm, mm to in)
	// $src and $dest must be 'in' or 'mm'
	function _Convert_Metric ($value, $src, $dest) {
		if ($src != $dest) {
			$tab['in'] = 39.37008;
			$tab['mm'] = 1000;
			return $value * $tab[$dest] / $tab[$src];
		} else {
			return $value;
		}
	}

	// Give the height for a char size given.
	function _Get_Height_Chars($pt) {
		// Tableau de concordance entre la hauteur des caractères et de l'espacement entre les lignes
		$_Table_Hauteur_Chars = array(6=>2, 7=>2.5, 8=>3, 9=>4, 10=>5, 11=>6, 12=>7, 13=>8, 14=>9, 15=>10);
		if (in_array($pt, array_keys($_Table_Hauteur_Chars))) {
			return $_Table_Hauteur_Chars[$pt];
		} else {
			return 100; // There is a prob..
		}
	}

	function _Set_Format($format) {
		$this->_Metric 	= $format['metric'];
		$this->_Avery_Name 	= $format['name'];
		$this->_Margin_Left	= $this->_Convert_Metric ($format['marginLeft'], $this->_Metric, $this->_Metric_Doc);
		$this->_Margin_Top	= $this->_Convert_Metric ($format['marginTop'], $this->_Metric, $this->_Metric_Doc);
		$this->_X_Space 	= $this->_Convert_Metric ($format['SpaceX'], $this->_Metric, $this->_Metric_Doc);
		$this->_Y_Space 	= $this->_Convert_Metric ($format['SpaceY'], $this->_Metric, $this->_Metric_Doc);
		$this->_X_Number 	= $format['NX'];
		$this->_Y_Number 	= $format['NY'];
		$this->_Width 	= $this->_Convert_Metric ($format['width'], $this->_Metric, $this->_Metric_Doc);
		$this->_Height	= $this->_Convert_Metric ($format['height'], $this->_Metric, $this->_Metric_Doc);
		$this->Set_Char_Size( $format['font-size']);
	}

}
?>
