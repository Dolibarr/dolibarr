<?php

// ******************************************************* ©2003 Pitoo.com *****
// *****                   CODES A BARRES - Php script                     *****
// *****************************************************************************
// *****              (c) 2002 - pitoo.com - mail@pitoo.com                *****
// *****************************************************************************
// *****************************************************************************
// ***** Ce script est "FREEWARE",  il  peut  etre  librement copie et reutilise
// ***** dans vos propres pages et applications.  Il peut egalement etre modifie
// ***** ou ameliore.
// ***** CEPENDANT :  par  respect  pour l'auteur,  avant d'utiliser,  recopier, 
// ***** modifier ce code vous vous engagez a :
// ***** - conserver intact l'entete de ce fichier ( les commentaires comportant
// *****   Le nom du script,  le copyright le nom de l'auteur et son e-mail,  ce
// *****   texte et l'historique des mises a jour ).
// ***** - envoyer un  e-mail  a l'auteur  <mail@pitoo.com>  lui indiquant votre
// *****   intention d'utiliser le resultat de son travail.
// *****************************************************************************
// ***** Toute remarque, tout commentaire, tout rapport de bug, toute recompense
// ***** sont la bienvenue : <mail@pitoo.com>.
// *****************************************************************************
// *****************************************************************************
// *****                       Historique des versions                     *****
// *****************************************************************************
$last_version = "V2.05" ;
// ***** V2.05 - 13/06/2006 - pitoo.com
// *****       - Suppression des fonctions inutiles (V1)
// *****       - Ajout de commentaires
// ***** V2.04 - 23/01/2006 - pitoo.com
// *****       - Correction erreur codage Lettre A du code 39
// ***** V2.03 - 20/11/2004 - pitoo.com
// *****       - Supression de messages warning php
// ***** V2.02 - 07/04/2004 - pitoo.com
// *****       - Suppression du checksum et des Start/Stop sur le code KIX
// ***** V2.01 - 18/12/2003 - pitoo.com
// *****       - Correction de bug pour checksum C128 = 100 / 101 / 102
// ***** V2.00 - 19/06/2003 - pitoo.com
// *****       - Réécriture de toutes les fonctions pour génération directe de
// *****         l'image du code barre en PNG plutôt que d'utiliser une multitude
// *****         de petits fichiers GIFs
// ***** V1.32 - 21/12/2002 - pitoo.com
// *****       - Ecriture du code 39
// *****       - Amelioration des codes UPC et 25 ()
// ***** V1.31 - 17/12/2002 - pitoo.com
// *****       - Amelioration du code 128 (ajout du Set de caracteres C)
// *****       - Amelioration du code 128 (ajout du code lisible en dessous )
// ***** V1.3  - 12/12/2002 - pitoo.com
// *****       - Ecriture du code 128 B
// ***** V1.2  - 01/08/2002 - pitoo.com
// *****       - Ecriture du code UPC / EAN
// ***** V1.0  - 01/01/2002 - pitoo.com
// *****       - Ecriture du code 25


if ( !class_exists( "pi_barcode" ) ) {
class pi_barcode
{
    /**
    * Définition des symbologies
    */
	
	var $C128 = array(
				0 => "11011001100",     1 => "11001101100",     2 => "11001100110",
				3 => "10010011000",     4 => "10010001100",     5 => "10001001100",
				6 => "10011001000",     7 => "10011000100",     8 => "10001100100",
				9 => "11001001000",     10 => "11001000100",    11 => "11000100100",
				12 => "10110011100",    13 => "10011011100",    14 => "10011001110",
				15 => "10111001100",    16 => "10011101100",    17 => "10011100110",
				18 => "11001110010",    19 => "11001011100",    20 => "11001001110",
				21 => "11011100100",    22 => "11001110100",    23 => "11101101110",
				24 => "11101001100",    25 => "11100101100",    26 => "11100100110",
				27 => "11101100100",    28 => "11100110100",    29 => "11100110010",
				30 => "11011011000",    31 => "11011000110",    32 => "11000110110",
				33 => "10100011000",    34 => "10001011000",    35 => "10001000110",
				36 => "10110001000",    37 => "10001101000",    38 => "10001100010",
				39 => "11010001000",    40 => "11000101000",    41 => "11000100010",
				42 => "10110111000",    43 => "10110001110",    44 => "10001101110",
				45 => "10111011000",    46 => "10111000110",    47 => "10001110110",
				48 => "11101110110",    49 => "11010001110",    50 => "11000101110",
				51 => "11011101000",    52 => "11011100010",    53 => "11011101110",
				54 => "11101011000",    55 => "11101000110",    56 => "11100010110",
				57 => "11101101000",    58 => "11101100010",    59 => "11100011010",
				60 => "11101111010",    61 => "11001000010",    62 => "11110001010",
				63 => "10100110000",    64 => "10100001100",    65 => "10010110000",
				66 => "10010000110",    67 => "10000101100",    68 => "10000100110",
				69 => "10110010000",    70 => "10110000100",    71 => "10011010000",
				72 => "10011000010",    73 => "10000110100",    74 => "10000110010",
				75 => "11000010010",    76 => "11001010000",    77 => "11110111010",
				78 => "11000010100",    79 => "10001111010",    80 => "10100111100",
				81 => "10010111100",    82 => "10010011110",    83 => "10111100100",
				84 => "10011110100",    85 => "10011110010",    86 => "11110100100",
				87 => "11110010100",    88 => "11110010010",    89 => "11011011110",
				90 => "11011110110",    91 => "11110110110",    92 => "10101111000",
				93 => "10100011110",    94 => "10001011110",    95 => "10111101000",
				96 => "10111100010",    97 => "11110101000",    98 => "11110100010",
				99  => "10111011110",    // 99 et 'c' sont identiques ne nous sert que pour le checksum
				100 => "10111101110",    // 100 et 'b' sont identiques ne nous sert que pour le checksum
				101 => "11101011110",    // 101 et 'a' sont identiques ne nous sert que pour le checksum
				102 => "11110101110",    // 102 correspond à FNC1 ne nous sert que pour le checksum
				'c' => "10111011110",   'b' => "10111101110",   'a' => "11101011110",
				'A' => "11010000100",   'B' => "11010010000",   'C' => "11010011100",
				'S' => "1100011101011"
			);
	
    var $C25 =  array(
                0 => "11331",           1 => "31113",
                2 => "13113",           3 => "33111",
                4 => "11313",           5 => "31311",
                6 => "13311",           7 => "11133",
                8 => "31131",           9 => "13131",
                'D' => "111011101",       'F' => "111010111", // Code 2 parmi 5
                'd' => "1010",          'f' => "11101"   // Code 2/5 entrelacé
			);
				 
    var $C39 =  array(
                '0' => "101001101101",  '1' => "110100101011",  '2' => "101100101011",
                '3' => "110110010101",  '4' => "101001101011",  '5' => "110100110101",
                '6' => "101100110101",  '7' => "101001011011",  '8' => "110100101101",
                '9' => "101100101101",  'A' => "110101001011",  'B' => "101101001011",
                'C' => "110110100101",  'D' => "101011001011",  'E' => "110101100101",
                'F' => "101101100101",  'G' => "101010011011",  'H' => "110101001101",
                'I' => "101101001101",  'J' => "101011001101",  'K' => "110101010011",
                'L' => "101101010011",  'M' => "110110101001",  'N' => "101011010011",
                'O' => "110101101001",  'P' => "101101101001",  'Q' => "101010110011",
                'R' => "110101011001",  'S' => "101101011001",  'T' => "101011011001",
                'U' => "110010101011",  'V' => "100110101011",  'W' => "110011010101",
                'X' => "100101101011",  'Y' => "110010110101",  'Z' => "100110110101",
                '-' => "100101011011",  '.' => "110010101101",  ' ' => "100110101101",
                '$' => "100100100101",  '/' => "100100101001",  '+' => "100101001001",
                '%' => "101001001001",  '*' => "100101101101"
			);
				 
    var $codabar = array(
                '0' => "101010011",     '1' => "101011001",     '2' => "101001011",
                '3' => "110010101",     '4' => "101101001",     '5' => "110101001",
                '6' => "100101011",     '7' => "100101101",     '8' => "100110101",
                '9' => "110100101",     '-' => "101001101",     '$' => "101100101",
                ':' => "1101011011",    '/' => "1101101011",    '.' => "1101101101",
                '+' => "1011011011",    'A' => "1011001001",    'B' => "1010010011",
                'C' => "1001001011",    'D' => "1010011001"
			);
			
    var $MSI = array(
                0 => "100100100100", 
                1 => "100100100110", 
                2 => "100100110100", 
                3 => "100100110110", 
                4 => "100110100100", 
                5 => "100110100110", 
                6 => "100110110100", 
                7 => "100110110110", 
                8 => "110100100100", 
                9 => "110100100110", 
                'D' => "110", 
                'F' => "1001"
			);
				 
    var $C11 = array(
                '0' => "101011", 
                '1' => "1101011", 
                '2' => "1001011", 
                '3' => "1100101", 
                '4' => "1011011", 
                '5' => "1101101", 
                '6' => "1001101", 
                '7' => "1010011", 
                '8' => "1101001", 
                '9' => "110101", 
                '-' => "101101", 
                'S' => "1011001" 
			);

    var $postnet = array(
                '0' => "11000", 
                '1' => "00011", 
                '2' => "00101", 
                '3' => "00110", 
                '4' => "01001", 
                '5' => "01010", 
                '6' => "01100", 
                '7' => "10001", 
                '8' => "10010", 
                '9' => "10100"
			);

    var $kix = array(       //0=haut, 1=bas, 2=milieu, 3=toute la hauteur
                '0' => '2233',          '1' => '2103',          '2' => '2130',
                '3' => '1203',          '4' => '1230',          '5' => '1100',
                '6' => '2013',          '7' => '2323',          '8' => '2310',
				'9' => '1023',          'A' => '1010',          'B' => '1320',
                'C' => '2031',          'D' => '2301',          'E' => '2332',
				'F' => '1001',          'G' => '1032',          'H' => '1302',
                'I' => '0213',          'J' => '0123',          'K' => '0110',
				'L' => '3223',          '2' => '3210',          'N' => '3120',
                'O' => '0231',          'P' => '0101',          'Q' => '0132',
                'R' => '3201',          'S' => '3232',          'T' => '3102',
                'U' => '0011',          'V' => '0321',          'W' => '0312',
                'X' => '3021',          'Y' => '3021',          'Z' => '3322'
			);

    var $CMC7 = array(
                0 => "0,3-0,22|2,1-2,24|4,0-4,8|4,18-4,25|8,0-8,8|8,18-8,25|12,0-12,8|12,18-12,25|14,1-14,24|16,3-16,22",
                1 => "0,5-0,12|0,17-0,25|4,3-4,10|4,17-4,25|6,2-6,9|6,17-6,25|8,1-8,25|10,0-10,25|14,14-14,25|16,14-16,25",
                2 => "0,2-0,9|0,17-0,25|2,0-2,9|2,16-2,25|6,0-6,6|6,13-6,25|10,0-10,6|10,11-10,17|10,20-10,25|12,0-12,6|12,10-12,16|12,20-12,25|14,0-14,14|14,20-14,25|16,2-16,13|16,20-16,25",
                3 => "0,2-0,9|0,17-0,23|4,0-4,9|4,17-4,25|6,0-6,8|6,18-6,25|10,0-10,7|10,10-10,16|10,19-10,25|12,0-12,7|12,10-12,16|12,19-12,25|14,0-14,25|16,2-16,12|16,14-16,23",
                4 => "0,6-0,21|4,4-4,21|6,3-6,11|6,16-6,21|8,2-8,10|8,16-8,21|12,0-12,8|12,15-12,25|14,0-14,8|14,15-14,25|16,0-16,8|16,15-16,25",
                5 => "0,0-0,14|0,19-0,25|2,0-2,14|2,19-2,25|4,0-4,6|4,9-4,14|4,19-4,25|6,0-6,6|6,9-6,14|6,19-6,25|10,0-10,6|10,9-10,14|10,19-10,25|14,0-14,6|14,9-14,25|16,0-16,6|16,11-16,23",
                6 => "0,2-0,23|2,0-2,25|4,0-4,6|4,10-4,15|4,19-4,25|8,0-8,6|8,10-8,15|8,19-8,25|10,0-10,6|10,10-10,15|10,19-10,25|14,0-14,7|14,10-14,25|16,2-16,7|16,12-16,23",
                7 => "0,0-0,9|0,19-0,25|4,0-4,6|4,16-4,25|8,0-8,6|8,12-8,21|10,0-10,6|10,9-10,19|12,0-12,17|14,0-14,15|16,0-16,13",
                8 => "0,2-0,10|0,15-0,23|2,0-2,11|2,14-2,25|6,0-6,6|6,10-6,15|6,19-6,25|8,0-8,6|8,10-8,15|8,19-8,25|10,0-10,6|10,10-10,15|10,19-10,25|14,0-14,11|14,14-14,25|16,2-16,10|16,15-16,23",
                9 => "0,2-0,13|0,18-0,23|2,0-2,15|2,18-2,25|6,0-6,6|6,10-6,15|6,19-6,25|8,0-8,6|8,10-8,15|8,19-8,25|12,0-12,6|12,10-12,15|12,19-12,25|14,0-14,25|16,2-16,23",
                'A' => "0,4-0,15|0,19-0,24|2,4-2,15|2,19-2,24|4,4-4,15|4,19-4,24|8,4-8,15|8,19-8,24|10,4-10,15|10,19-10,24|12,4-12,15|12,19-12,24|16,4-16,15|16,19-16,24",
                'B' => "0,9-0,24|4,7-4,22|6,6-6,21|8,5-8,20|10,4-10,19|12,3-12,18|16,1-16,16",
                'C' => "0,4-0,12|0,16-0,24|2,4-2,12|2,16-2,24|4,4-4,12|4,16-4,24|6,4-6,12|6,16-6,24|10,7-10,21|12,7-12,21|16,7-16,21",
                'D' => "0,10-0,24|2,10-2,24|6,10-6,24|8,10-8,24|10,4-10,24|12,4-12,24|16,4-16,24",
                'E' => "0,7-0,12|0,16-0,25|2,5-2,23|4,3-4,21|6,1-6,19|8,0-8,18|12,3-12,21|16,7-16,12|16,16-16,25",
			);
			
    var $EANbars = array('A' => array(
                0 => "0001101",         1 => "0011001",
                2 => "0010011",         3 => "0111101",
                4 => "0100011",         5 => "0110001",
                6 => "0101111",         7 => "0111011",
                8 => "0110111",         9 => "0001011"
                ),
                'B' => array(
                0 => "0100111",         1 => "0110011",
                2 => "0011011",         3 => "0100001",
                4 => "0011101",         5 => "0111001",
                6 => "0000101",         7 => "0010001",
                8 => "0001001",         9 => "0010111"
                ),
                'C' => array(
                0 => "1110010",         1 => "1100110",
                2 => "1101100",         3 => "1000010",
                4 => "1011100",         5 => "1001110",
                6 => "1010000",         7 => "1000100",
                8 => "1001000",         9 => "1110100"
                )
            );
    
    var $EANparity = array(
                0 => array('A','A','A','A','A','A'),
                1 => array('A','A','B','A','B','B'),
                2 => array('A','A','B','B','A','B'),
                3 => array('A','A','B','B','B','A'),
                4 => array('A','B','A','A','B','B'),
                5 => array('A','B','B','A','A','B'),
                6 => array('A','B','B','B','A','A'),
                7 => array('A','B','A','B','A','B'),
                8 => array('A','B','A','B','B','A'),
                9 => array('A','B','B','A','B','A')
            );
    
    /**
    * Constructeur
    *
    * Initialise la classe
    *
    * @CODE            string        code CODE
    *
    * return            void
    */
    function pi_barcode($TYPE, $CODE, $HEIGHT=10, $HR="Y", $WIDTH=0, $SHOWTYPE="N")
    {
		$this->TYPE = $TYPE;
		$this->HEIGHT = $HEIGHT;
		$this->WIDTH = $WIDTH;
		if( $HR == "Y" ) $this->HR = $CODE;
		$this->SHOWTYPE = $SHOWTYPE;
		
        settype($CODE,'string');
        
		$lencode = strlen($CODE);
    $strCode="";
        //Transformation de la chaine en tableau
        for($i=0; $i < $lencode ; $i++)
        {
            $this->CODE[$i] = substr($CODE,$i,1);
        }

		switch( $TYPE ) {
		
			case "EAN" :
			case "UPC" :
				if( $lencode == 8 ) {
                    $strCode = '101'; //Premier séparateur (101)
                    for ($i=0; $i<4; $i++) $strCode .= $this->EANbars['A'][$this->CODE[$i]]; //Codage partie gauche (tous de classe A)
                    $strCode .= '01010'; //Séparateur central (01010) //Codage partie droite (tous de classe C)
                    for ($i=4; $i<8; $i++) $strCode .= $this->EANbars['C'][$this->CODE[$i]];
                    $strCode .= '101'; //Dernier séparateur (101)
				} else {
                    $parity = $this->EANparity[$this->CODE[0]]; //On récupère la classe de codage de la partie qauche
                    $strCode = '101'; //Premier séparateur (101)
                    for ($i=1; $i<7; $i++) $strCode .= $this->EANbars[$parity[$i-1]][$this->CODE[$i]]; //Codage partie gauche
                    $strCode .= '01010'; //Séparateur central (01010) //Codage partie droite (tous de classe C)
                    for ($i=7; $i<13; $i++) $strCode .= $this->EANbars['C'][$this->CODE[$i]];
                    $strCode .= '101'; //Dernier séparateur (101)
				}
    
			  break;
			case "C128C" :
                $strCode = $this->C128['C']; //Start
				$checksum = 105 ;
				$j = 1 ;
			    for($i=0;$i<strlen($CODE);$i+=2) {
				    $tmp = intval(substr($CODE, $i, 2)) ;
					$checksum += ( $j++ * $tmp ) ;
				    $strCode .= $this->C128[$tmp];
				}
				$checksum %= 103 ;
				$strCode .= $this->C128[$checksum];
                $strCode .= $this->C128['S']; //Stop
			  break;
			case "C128" :
                $strCode = $this->C128['B']; //Start
				$checksum = 104 ;
				$j = 1 ;
			    for($i=0;$i<$lencode;$i++) {
				    $tmp = ord($this->CODE[$i]) - 32 ;
					$checksum += ( $j++ * $tmp ) ;
				    $strCode .= $this->C128[$tmp];
				}
				$checksum %= 103 ;
				$strCode .= $this->C128[$checksum];
                $strCode .= $this->C128['S']; //Stop
			  break;
			case "C25" :
                $strCode = $this->C25['D']."0"; //Start
			    for($i=0;$i<$lencode;$i++) {
				    $num = intval($this->CODE[$i]) ;
					$tmp = $this->C25[$num];
    			    for($j=0;$j<5;$j++) {
    				    $tmp2 = intval(substr($tmp,$j,1)) ;
    				    for($k=1;$k<=$tmp2;$k++) $strCode .= "1";
						$strCode .= "0";
    				}
				}
                $strCode .= $this->C25['F']; //Stop
			  break;
			case "C25I" :
                $strCode = $this->C25['d']; //Start
				$checksum = 0;
			    for($i=0;$i<$lencode;$i+=2) {
				    $num1 = intval($this->CODE[$i]) ;
				    $num2 = intval($this->CODE[$i+1]) ;
					$checksum += ($num1+$num2);
					$tmp1 = $this->C25[$num1];
					$tmp2 = $this->C25[$num2];
    			    for($j=0;$j<5;$j++) {
    				    $t1 = intval(substr($tmp1,$j,1)) ;
    				    $t2 = intval(substr($tmp2,$j,1)) ;
    				    for($k=1;$k<=$t1;$k++) $strCode .= "1";
    				    for($k=1;$k<=$t2;$k++) $strCode .= "0";
    				}
				}
                $strCode .= $this->C25['f']; //Stop
			  break;
			case "C39" :
			    for($i=0;$i<$lencode;$i++) {
				    $tmp = $this->CODE[$i];
				    $strCode .= $this->C39[$tmp] . "0";
				}
				$strCode = substr($strCode,0,-1);
			  break;
			case "CODABAR" :
			    for($i=0;$i<$lencode;$i++) {
				    $tmp = $this->CODE[$i];
				    $strCode .= $this->codabar[$tmp] . "0";
				}
				$strCode = substr($strCode,0,-1);
			  break;
			case "MSI" :
                $strCode = $this->MSI['D']; //Start
			    for($i=0;$i<$lencode;$i++) {
				    $tmp = intval($this->CODE[$i]);
				    $strCode .= $this->MSI[$tmp];
				}
                $strCode .= $this->MSI['F']; //Stop
			  break;
			case "C11" :
                $strCode = $this->C11['S']."0"; //Start
			    for($i=0;$i<$lencode;$i++) {
				    $tmp = $this->CODE[$i];
				    $strCode .= $this->C11[$tmp]."0";
				}
                $strCode .= $this->C11['S']; //Stop
			  break;
			case "POSTNET" :
                $strCode = "1"; //Start
			    for($i=0;$i<$lencode;$i++) {
				    $tmp = $this->CODE[$i];
				    $strCode .= $this->postnet[$tmp];
				}
                $strCode .= "1"; //Stop
				
				$tmp = ( strlen($strCode) * 4 ) + 20;
		        if( $HR == "Y" ) $this->HEIGHT = 32;
				else $this->HEIGHT = 22;
				$this->WIDTH = $tmp;
			  break;
			case "KIX" :
//                $strCode = "31"; //Start
                $strCode = "";
			    for($i=0;$i<$lencode;$i++) {
				    $tmp = $this->CODE[$i];
				    $strCode .= $this->kix[$tmp];
				}
//                $strCode .= "32"; //Stop
				
				$tmp = ( strlen($strCode) * 4 ) + 20;
		        if( $HR == "Y" ) $this->HEIGHT = 32;
				else $this->HEIGHT = 22;
				$this->WIDTH = $tmp;
			  break;
			case "CMC7" :
                $strCode = "";
			    for($i=0;$i<$lencode;$i++) {
				    $tmp = $this->CODE[$i];
					
				    $strCode .= $tmp;
				}
								
				$this->WIDTH = ( strlen($CODE) * 24 ) + 12 ;
		        $this->HEIGHT = 35;
			  break;
			case "ERR" :
				$this->HEIGHT = 36;
				$this->WIDTH = 200;
			  break;
			  
		}

        $this->strCode = $strCode;
		$tmp = strlen($this->strCode) + 20;
		if($this->WIDTH < $tmp) $this->WIDTH = $tmp;
    }
    
    
    /**
    * Création de l'image
    *
    * Crée une image GIF ou PNG du code généré par giveCode
    *
    * return            void
    */
    function makeImage()
    {
        //Initialisation de l'image
        $posX = 10; // position X
        $posY = 0; // position Y
        $intL = 1; // largeur de la barre
		$nb_elem = strlen($this->strCode);
        
        $img=imagecreate($this->WIDTH, $this->HEIGHT);
        
        $color[0] = ImageColorAllocate($img, 255,255,255);
        $color[1] = ImageColorAllocate($img, 0,0,0);
        $color[2] = ImageColorAllocate($img, 160,160,160);
        
        imagefilledrectangle($img, 0, 0, $nb_elem+20, $this->HEIGHT, $color[0]);
        
        for($i=0;$i<$nb_elem;$i++)
        {
		
            $intH = $this->HEIGHT; // hauteur du code
			
            if( $this->HR != "" ) switch($this->TYPE){
		      case "EAN" :
		      case "UPC" :
				if($i<=2 OR $i>=($nb_elem-3) OR ($i>=($nb_elem/2)-2 AND $i<=($nb_elem/2)+2)) $intH-=6; else $intH-=11;
              break;
		      default :
				if($i>0 AND $i<($nb_elem-1)) $intH-=11;
			}
            
            $fill_color = substr($this->strCode,$i,1);
            
			if($this->TYPE == "POSTNET") {
			    if($fill_color == "1") imagefilledrectangle($img, $posX, ($posY+1), $posX+1, ($posY+20), $color[1]);
			    else imagefilledrectangle($img, $posX, ($posY+12), $posX+1, ($posY+20), $color[1]);
				$intL = 4 ;
			} elseif($this->TYPE == "KIX") {
			    if($fill_color == "0") imagefilledrectangle($img, $posX, ($posY+1), $posX+1, ($posY+13), $color[1]);
			    elseif($fill_color == "1") imagefilledrectangle($img, $posX, ($posY+7), $posX+1, ($posY+19), $color[1]);
			    elseif($fill_color == "2") imagefilledrectangle($img, $posX, ($posY+7), $posX+1, ($posY+13), $color[1]);
			    else imagefilledrectangle($img, $posX, ($posY+1), $posX+1, ($posY+19), $color[1]);
				$intL = 4 ;
			} elseif($this->TYPE == "CMC7") {
			
				$tmp = $this->CMC7[$fill_color];
                $coord = explode( "|", $tmp );
				
                for( $j=0; $j<sizeof($coord); $j++) {
                	$pts = explode( "-", $coord[$j] );
                	$deb = explode( ",", $pts[0] );
                	$X1 = $deb[0] + $posX ;
                	$Y1 = $deb[1] + 5 ;
                	$fin = explode( ",", $pts[1] );
                	$X2 = $fin[0] + $posX ;
                	$Y2 = $fin[1] + 5 ;
                	
                	imagefilledrectangle($img, $X1, $Y1, $X2, $Y2, $color[1]);
                }
                $intL = 24 ;
            } 
    		else  {
			    if($fill_color == "1") imagefilledrectangle($img, $posX, $posY, $posX, ($posY+$intH), $color[1]);
			}
//            imagefilledrectangle($img, $posX, $posY, $posX, ($posY+$intH), $color[$fill_color]);
            
            //Deplacement du pointeur
            $posX += $intL;
        }
        
        $ifw = imagefontwidth(3);
        $ifh = imagefontheight(3) - 1;
        switch($this->TYPE){
		  case "ERR" :
			$ifw = imagefontwidth(3);
            imagestring($img, 3, floor(($this->WIDTH/2)-(($ifw * 7)/2)), 1, "ERROR :", $color[1]); 
			$ifw = imagefontwidth(2);
            imagestring($img, 2, floor(($this->WIDTH/2)-(($ifw * strlen(implode('',$this->CODE)))/2)), 13, implode('',$this->CODE), $color[1]); 
			$ifw = imagefontwidth(1);
            //imagestring($img, 1, ($this->WIDTH)-($ifw * 9)-2, 26, "Pitoo.com", $color[2]); 
          break;
		  case "EAN" :
                if(strlen($this->HR) > 10) imagestring($img, 3, 3, $this->HEIGHT - $ifh, substr($this->HR,-13,1), $color[1]); 
		  case "UPC" :
		    if(strlen($this->HR) > 10) {
                imagestring($img, 3, 14, $this->HEIGHT - $ifh, substr($this->HR,1,6), $color[1]); 
                imagestring($img, 3, 60, $this->HEIGHT - $ifh, substr($this->HR,7,6), $color[1]); 
		    } else {
                imagestring($img, 3, 14, $this->HEIGHT - $ifh, substr($this->HR,0,4), $color[1]); 
                imagestring($img, 3, 46, $this->HEIGHT - $ifh, substr($this->HR,4,4), $color[1]); 
		    }
          break;
		  case "CMC7" ;
		  break;
		  default :
			$ifw = imagefontwidth(3);
			$ifh = imagefontheight(3) - 1;
            imagestring($img, 3, intval(($this->WIDTH/2)-(($ifw * strlen($this->HR))/2))+1, $this->HEIGHT - $ifh, $this->HR, $color[1]); 
        }
        
		$ifw = imagefontwidth(1) * 9;
        if( (rand(0,50)<1) AND ($this->HEIGHT >= $ifw) ) {
		    imagestringup($img, 1, $nb_elem + 12, $this->HEIGHT - 2, "", $color[2]); 
		}
        if( $this->SHOWTYPE == "Y" ) {
			if(($this->TYPE == "EAN") AND (substr($this->HR,-13,1) != "0") AND (strlen($this->HR) > 10)) {
		        imagestringup($img, 1, 0, $this->HEIGHT - 12, $this->TYPE, $color[2]); 
			} elseif($this->TYPE == "POSTNET") {
		        imagestringup($img, 1, 0, $this->HEIGHT - 2, "POST", $color[2]); 
			} else {
		        imagestringup($img, 1, 0, $this->HEIGHT - 2, $this->TYPE, $color[2]); 
			}
		}
        
        Header( "Content-type: image/png"); 
        imagepng($img); 
        imagedestroy($img); 
    }
    
    
}//Fin de la classe
}
extract($_GET);
$type = strtoupper($type);
if ($code){
switch( $type ) {
    case "C128C" :
	
	    if (preg_match("/^[0-9]{2,48}$/", $code)){
            $tmp = strlen("$code");
            if(($tmp%2)!=0) $code = "0$code";
		} else {
          $type = "ERR";
          $code = "CODE 128C REQUIRES DIGITS ONLY";
        }
		
	case "C128" :
	    
		$carok = true;
		for($i=0;$i<strlen($code);$i++) {
		    $tmp = ord(substr($code,$i,1)) ;
			if($tmp < 32 OR $tmp > 126) $carok = false;
		}
		if( !$carok ) {
          $type = "ERR";
          $code = "UNAUTHORIZED CHARS IN 128 CODE";
		}
	
	  break;
	case "UPC" :
	
        $code = "0$code";
		
	case "EAN" :
	
        $long = strlen( $code ) ;
		$factor = 3;
		$sum = 0;
		
        if (preg_match("/^[0-9]{8}$/", $code) OR preg_match("/^[0-9]{13}$/", $code)){
//        if ($long==13){
       
    		for ($index = ($long - 1); $index > 0; $index--) {
    			$sum += substr( $code, $index - 1, 1 ) * $factor ;
    			$factor = 4 - $factor ;
    		}
    		$cc = ( ( 1000 - $sum ) % 10 ) ;
    
    		if ( substr( $code, -1, 1) != $cc ) {
                $type = "ERR";
                $code = "CHECKSUM ERROR IN EAN/UPC CODE";
			}
       
        } elseif (preg_match("/^[0-9]{7}$/", $code) OR preg_match("/^[0-9]{12}$/", $code)){
//        } elseif ($long==12){
       
    		for ($index = ($long ); $index > 0; $index--) {
    			$sum += substr( $code, $index - 1, 1 ) * $factor ;
    			$factor = 4 - $factor ;
    		}
    		$cc = ( ( 1000 - $sum ) % 10 ) ;
    
    		$code .= "$cc" ;
       
        } else {
          $type = "ERR";
          $code = "THIS CODE IS NOT EAN/UPC TYPE";
        }
    
	  break;
	case "C25I" :
	
        $tmp = strlen("$code");
        if(($tmp%2)==0) { $code = "0$code"; $tmp++; }

	case "C25" :
	
	    if (preg_match("/^[0-9]{1,48}$/", $code)){
			$checksum = 0;
			$factor = 3;
            $tmp = strlen("$code");
			for($i=$tmp-1; $i>=0; $i--) {
			    $checksum += (intval(substr($code,$i,1))*$factor);
				$factor = 4-$factor;
			}
			$checksum = 10-($checksum%10);
			if($checksum==10) $checksum = 0;
			$code .= "$checksum";
		} else {
          $type = "ERR";
          $code = "CODE C25 REQUIRES DIGITS ONLY";
        }
		
	  break;
	case "C39" :
	    
		if(preg_match("/^[0-9A-Z\-\.\$\/+% ]{1,48}$/i", $code)) {
		  $code = "*$code*";
		} else {
          $type = "ERR";
          $code = "UNAUTHORIZED CHARS IN CODE 39";
		}
		
	  break;
	case "CODABAR" :
	
		if(!preg_match("/^(A|B|C|D)[0-9\-\$:\/\.\+]{1,48}(A|B|C|D)$/i", $code)) {
          $type = "ERR";
          $code = "CODABAR START/STOP : ABCD";
		}
		
	  break;
	case "MSI" :
	
	    if (preg_match("/^[0-9]{1,48}$/", $code)){
			$checksum = 0;
			$factor = 1;
            $tmp = strlen("$code");
			for($i=0; $i<$tmp; $i++) {
			    $checksum += (intval(substr($code,$i,1))*$factor);
				$factor++;
				if($factor > 10) $factor = 1; 
			}
			$checksum = (1000-$checksum)%10;
			$code .= "$checksum";
		} else {
          $type = "ERR";
          $code = "CODE MSI REQUIRES DIGITS ONLY";
        }
		
	  break;
	case "C11" :
	
	    if (preg_match("/^[0-9\-]{1,48}$/", $code)){
			$checksum = 0;
			$factor = 1;
            $tmp = strlen("$code");
			for($i=$tmp-1; $i>=0; $i--) {
				$tmp = substr($code,$i,1);
				if($tmp == "-") $tmp=10;
				else $tmp = intval($tmp);
			    $checksum += ($tmp*$factor);
			    $factor++; 
				if($factor>10) $factor=1;
			}
			$checksum = $checksum%11;
			if($checksum==10) $code .= $checksum . "-";
			else $code .= "$checksum";
		} else {
          $type = "ERR";
          $code = "UNAUTHORIZED CHARS IN CODE 11";
        }
		
	  break;
	case "POSTNET" :
	
	    if (preg_match("/^[0-9]{5}$/", $code) OR preg_match("/^[0-9]{9}$/", $code) OR preg_match("/^[0-9]{11}$/", $code)){
			$checksum = 0;
            $tmp = strlen("$code");
			for($i=$tmp-1; $i>=0; $i--) {
			    $checksum += intval(substr($code,$i,1));
			}
			$checksum = 10-($checksum%10);
			if($checksum==10) $checksum=0;
			$code .= "$checksum";
		} else {
          $type = "ERR";
          $code = "POSTNET MUST BE 5/9/11 DIGITS";
        }
		
	  break;
	case "KIX" :
	
	    if (preg_match("/^[A-Z0-9]{1,50}$/", $code)){
// ***** LE CODE KIX n'a pas de checksum (correction V2.02)
//			$checksum = 0;
//			$tmp = strlen("$code");
//			for($i=$tmp-1; $i>=0; $i--) {
//			    $checksum += intval(substr($code,$i,1));
//			}
//			$checksum = 10-($checksum%10);
//			if($checksum==10) $checksum=0;
//			$code .= "$checksum";
		} else {
          $type = "ERR";
          $code = "UNAUTHORIZED CHARS IN KIX CODE";
        }
		
	  break;
	case "CMC7" :
	
		if(!preg_match("/^[0-9A-E]{1,48}$/", $code)) {
          $type = "ERR";
          $code = "CMC7 MUST BE NUMERIC or ABCDE";
		}
		
	  break;
	default :
	
        $type = "ERR";
        $code = "UNKWOWN BARCODE TYPE";
		
	  break;
}
} else {
          $type = "";
          $code = "";
        }

// ***** Largeur par défaut
if( isset( $width ) && ( $width >= 10 ) ) { $hw = $width ; }
else { $hw = 10 ; }

// ***** Hauteur par défaut
if( isset( $height ) && ( $height > 0 ) ) { $hh = $height ; } 
else { $hh = 10 ; }

// ***** Autres valeurs par défaut
if( isset( $readable ) && ( $readable == "Y" ) ) { $hr = "Y" ; }
else{ $hr = "N" ; }
if( !isset( $showtype ) ) { $showtype = "N" ; }

// ***** Création de l'objet
$objCode = new pi_barcode( $type, $code, $hh, $hr, $hw, $showtype ) ;
$objCode -> makeImage() ;
