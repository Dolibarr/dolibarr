<?php
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
//        + : Now Positions start @ (1,1).. then the first image @top-left of a page is (1,1)
//        + : Added in the description of a label : 
//				font-size	: defaut char size (can be changed by calling Set_Char_Size(xx);
//				paper-size	: Size of the paper for this sheet (thanx to Al Canton)
//				metric		: type of unit used in this description
//							  You can define your label properties in inches by setting metric to 'in'
//							  and printing in millimiter by setting unit to 'mm' in constructor.
//			  Added some labels :
//				5160, 5161, 5162, 5163,5164 : thanx to Al Canton : acanton@adams-blake.com
//				8600 						: thanx to Kunal Walia : kunal@u.washington.edu
//        + : Added 3mm to the position of labels to avoid errors 
// 1.2  : + : Added Set_Font_Name method
//        = : Bug of positionning
//        = : Set_Font_Size modified -> Now, just modify the size of the font
//        = : Set_Char_Size renamed to Set_Font_Size
////////////////////////////////////////////////////

/**
 * Modifié par Regis Houssin
 * PDF_Label - PDF label editing
 * @package PDF_Label
 * @author Laurent PASSEBECQ <lpasseb@numericable.fr>
 * @copyright 2003 Laurent PASSEBECQ
 * $Id$
 * $Source$
**/

require_once(FPDF_PATH."fpdf.php");

class PDF_Label extends FPDF {

	// Propriétés privées
	var $_Avery_Name	= '';				// Nom du format de l'étiquette
	var $_Margin_Left	= 0;				// Marge de gauche de l'étiquette
	var $_Margin_Top	= 0;				// marge en haut de la page avant la première étiquette
	var $_X_Space 		= 0;				// Espace horizontal entre 2 bandes d'étiquettes
	var $_Y_Space 		= 0;				// Espace vertical entre 2 bandes d'étiquettes
	var $_X_Number 		= 0;				// Nombre d'étiquettes sur la largeur de la page
	var $_Y_Number 		= 0;				// Nombre d'étiquettes sur la hauteur de la page
	var $_Width 		= 0;				// Largeur de chaque étiquette
	var $_Height 		= 0;				// Hauteur de chaque étiquette
	var $_Char_Size		= 10;				// Hauteur des caractères
	var $_Line_Height	= 10;				// Hauteur par défaut d'une ligne
	var $_Metric 		= 'mm';				// Type of metric for labels.. Will help to calculate good values
	var $_Metric_Doc 	= 'mm';				// Type of metric for the document
	var $_Font_Name		= 'Arial';			// Name of the font

	var $_COUNTX = 1;
	var $_COUNTY = 1;


	// Listing of labels size
	var $_Avery_Labels = array (
		'5160'=>array('name'=>'5160',	'paper-size'=>'letter',	'metric'=>'mm',	'marginLeft'=>1.762,	'marginTop'=>10.7,		'NX'=>3,	'NY'=>10,	'SpaceX'=>3.175,	'SpaceY'=>0,	'width'=>66.675,	'height'=>25.4,		'font-size'=>8),
		'5161'=>array('name'=>'5161',	'paper-size'=>'letter',	'metric'=>'mm',	'marginLeft'=>0.967,	'marginTop'=>10.7,		'NX'=>2,	'NY'=>10,	'SpaceX'=>3.967,	'SpaceY'=>0,	'width'=>101.6,		'height'=>25.4,		'font-size'=>8),
		'5162'=>array('name'=>'5162',	'paper-size'=>'letter',	'metric'=>'mm',	'marginLeft'=>0.97,		'marginTop'=>20.224,	'NX'=>2,	'NY'=>7,	'SpaceX'=>4.762,	'SpaceY'=>0,	'width'=>100.807,	'height'=>35.72,	'font-size'=>8),
		'5163'=>array('name'=>'5163',	'paper-size'=>'letter',	'metric'=>'mm',	'marginLeft'=>1.762,	'marginTop'=>10.7, 		'NX'=>2,	'NY'=>5,	'SpaceX'=>3.175,	'SpaceY'=>0,	'width'=>101.6,		'height'=>50.8,		'font-size'=>8),
		'5164'=>array('name'=>'5164',	'paper-size'=>'letter',	'metric'=>'in',	'marginLeft'=>0.148,	'marginTop'=>0.5, 		'NX'=>2,	'NY'=>3,	'SpaceX'=>0.2031,	'SpaceY'=>0,	'width'=>4.0,		'height'=>3.33,		'font-size'=>12),
		'8600'=>array('name'=>'8600',	'paper-size'=>'letter',	'metric'=>'mm',	'marginLeft'=>7.1, 		'marginTop'=>19, 		'NX'=>3, 	'NY'=>10, 	'SpaceX'=>9.5, 		'SpaceY'=>3.1, 	'width'=>66.6, 		'height'=>25.4,		'font-size'=>8),
		'L7163'=>array('name'=>'L7163',	'paper-size'=>'A4',		'metric'=>'mm',	'marginLeft'=>5,		'marginTop'=>15, 		'NX'=>2,	'NY'=>7,	'SpaceX'=>25,		'SpaceY'=>0,	'width'=>99.1,		'height'=>38.1,		'font-size'=>9)
	);

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
		$this->_Metric 		= $format['metric'];
		$this->_Avery_Name 	= $format['name'];
		$this->_Margin_Left	= $this->_Convert_Metric ($format['marginLeft'], $this->_Metric, $this->_Metric_Doc);
		$this->_Margin_Top	= $this->_Convert_Metric ($format['marginTop'], $this->_Metric, $this->_Metric_Doc);
		$this->_X_Space 	= $this->_Convert_Metric ($format['SpaceX'], $this->_Metric, $this->_Metric_Doc);
		$this->_Y_Space 	= $this->_Convert_Metric ($format['SpaceY'], $this->_Metric, $this->_Metric_Doc);
		$this->_X_Number 	= $format['NX'];
		$this->_Y_Number 	= $format['NY'];
		$this->_Width 		= $this->_Convert_Metric ($format['width'], $this->_Metric, $this->_Metric_Doc);
		$this->_Height	 	= $this->_Convert_Metric ($format['height'], $this->_Metric, $this->_Metric_Doc);
		$this->Set_Font_Size($format['font-size']);
	}

	function PDF_Label ($format, $unit='mm', $posX=1, $posY=1) {
		if (is_array($format)) {
			// Si c'est un format personnel alors on maj les valeurs
			$Tformat = $format;
		} else {
			// Si c'est un format avery on stocke le nom de ce format selon la norme Avery. 
			// Permettra d'aller récupérer les valeurs dans le tableau _Avery_Labels
			$Tformat = $this->_Avery_Labels[$format];
		}

		parent::FPDF('P', $Tformat['metric'], $Tformat['paper-size']);
		$this->_Set_Format($Tformat);
		$this->Set_Font_Name('Arial');
		$this->SetMargins(0,0); 
		$this->SetAutoPageBreak(false); 

		$this->_Metric_Doc = $unit;
		// Permet de commencer l'impression à l'étiquette désirée dans le cas où la page a déjà servi
		if ($posX > 1) $posX--; else $posX=0;
		if ($posY > 1) $posY--; else $posY=0;
		if ($posX >=  $this->_X_Number) $posX =  $this->_X_Number-1;
		if ($posY >=  $this->_Y_Number) $posY =  $this->_Y_Number-1;
		$this->_COUNTX = $posX;
		$this->_COUNTY = $posY;
	}

	// Méthode qui permet de modifier la taille des caractères
	// Cela modifiera aussi l'espace entre chaque ligne
	function Set_Font_Size($pt) {
		if ($pt > 3) {
			$this->_Char_Size = $pt;
			$this->_Line_Height = $this->_Get_Height_Chars($pt);
			$this->SetFontSize($this->_Char_Size);
		}
	}

	// Method to change font name
	function Set_Font_Name($fontname) {
		if ($fontname != '') {
			$this->_Font_Name = $fontname;
			$this->SetFont($this->_Font_Name);
		}
	}

	// On imprime une étiqette
	function Add_PDF_Label($texte) {
		// We are in a new page, then we must add a page
		if (($this->_COUNTX ==0) and ($this->_COUNTY==0)) {
			$this->AddPage();
		}

		$_PosX = $this->_Margin_Left+($this->_COUNTX*($this->_Width+$this->_X_Space));
		$_PosY = $this->_Margin_Top+($this->_COUNTY*($this->_Height+$this->_Y_Space));
		$this->SetXY($_PosX+3, $_PosY+3);
		$this->MultiCell($this->_Width, $this->_Line_Height, $texte);
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
	}

}
?>
