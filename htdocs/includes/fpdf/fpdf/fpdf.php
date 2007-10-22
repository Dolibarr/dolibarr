<?php
/*******************************************************************************
* Modifié par Régis Houssin                                                    *
* Logiciel : FPDF                                                              *
* Version :  1.53                                                              *
* Date :     31/12/2004                                                        *
* Auteur :   Olivier PLATHEY                                                   *
* Licence :  Freeware                                                          *
*                                                                              *
* Vous pouvez utiliser et modifier ce logiciel comme vous le souhaitez.        *
*******************************************************************************/

/*
* $Id$
* $Source$
*/

/**
 * height of cell repect font height
 */
define("K_CELL_HEIGHT_RATIO", 1.25);

/**
 * Répertoire des documents de fckeditor
 */
define ("K_PATH_CACHE", $conf->fckeditor->dir_output);

/**
 * url qui sera substituer par le K_PATH_CACHE lorsqu'une image sera intégrée au pdf
 */
define ("K_PATH_URL_CACHE", $dolibarr_main_url_root."/document.php?modulepart=editor&amp;file=");

if(!class_exists('FPDF'))
{
define('FPDF_VERSION','1.53');

class FPDF
{
//Private properties
var $page;               //current page number
var $n;                  //current object number
var $offsets;            //array of object offsets
var $buffer;             //buffer holding in-memory PDF
var $pages;              //array containing pages
var $state;              //current document state
var $compress;           //compression flag
var $DefOrientation;     //default orientation
var $CurOrientation;     //current orientation
var $OrientationChanges; //array indicating orientation changes
var $k;                  //scale factor (number of points in user unit)
var $fwPt,$fhPt;         //dimensions of page format in points
var $fw,$fh;             //dimensions of page format in user unit
var $wPt,$hPt;           //current dimensions of page in points
var $w,$h;               //current dimensions of page in user unit
var $lMargin;            //left margin
var $tMargin;            //top margin
var $rMargin;            //right margin
var $bMargin;            //page break margin
var $cMargin;            //cell margin
var $x,$y;               //current position in user unit for cell positioning
var $lasth;              //height of last cell printed
var $LineWidth;          //line width in user unit
var $CoreFonts;          //array of standard font names
var $fonts;              //array of used fonts
var $FontFiles;          //array of font files
var $diffs;              //array of encoding differences
var $images;             //array of used images
var $PageLinks;          //array of links in pages
var $links;              //array of internal links
var $FontFamily;         //current font family
var $FontStyle;          //current font style
var $underline;          //underlining flag
var $CurrentFont;        //current font info
var $FontSizePt;         //current font size in points
var $FontSize;           //current font size in user unit
var $DrawColor;          //commands for drawing color
var $FillColor;          //commands for filling color
var $TextColor;          //commands for text color
var $ColorFlag;          //indicates whether fill and text colors are different
var $ws;                 //word spacing
var $AutoPageBreak;      //automatic page breaking
var $PageBreakTrigger;   //threshold used to trigger page breaks
var $InFooter;           //flag set when processing footer
var $ZoomMode;           //zoom display mode
var $LayoutMode;         //layout display mode
var $title;              //title
var $subject;            //subject
var $author;             //author
var $keywords;           //keywords
var $creator;            //creator
var $AliasNbPages;       //alias for total number of pages
var $PDFVersion;         //PDF version number
var $prevFontFamily;     //store previous font family
var $prevFontStyle;      //store previous style family

var $DisplayPreferences=''; //préférences d'affichage

		// variables pour HTML PARSER
		
		/**
		 * @var HTML PARSER: store current link.
		 * @access private
		 */
		var $HREF;
		
		/**
		 * @var HTML PARSER: store font list.
		 * @access private
		 */
		var $fontList;
		
		/**
		 * @var HTML PARSER: true when font attribute is set.
		 * @access private
		 */
		var $issetfont;
		
		/**
		 * @var HTML PARSER: true when color attribute is set.
		 * @access private
		 */
		var $issetcolor;
		
		/**
		 * @var HTML PARSER: true in case of ordered list (OL), false otherwise.
		 * @access private
		 */
		var $listordered = false;
		
		/**
		 * @var HTML PARSER: count list items.
		 * @access private
		 */
		var $listcount = 0;
		
		/**
		 * @var HTML PARSER: size of table border.
		 * @access private
		 */
		var $tableborder = 0;
		
		/**
		 * @var HTML PARSER: true at the beginning of table.
		 * @access private
		 */
		var $tdbegin = false;
		
		/**
		 * @var HTML PARSER: table width.
		 * @access private
		 */
		var $tdwidth = 0;
		
		/**
		 * @var HTML PARSER: table height.
		 * @access private
		 */
		var $tdheight = 0;
		
		/**
		 * @var HTML PARSER: table align.
		 * @access private
		 */
		var $tdalign = "L";
		
		/**
		 * @var HTML PARSER: table background color.
		 * @access private
		 */
		var $tdbgcolor = false;
		
		/**
		 * @var Bold font style status.
		 * @access private
		 */
		var $b;
		
		/**
		 * @var Underlined font style status.
		 * @access private
		 */
		var $u;
		
		/**
		 * @var Italic font style status.
		 * @access private
		 */
		var $i;
		
		/**
		 * @var spacer for LI tags.
		 * @access private
		 */
		var $lispacer = "";
		

/*******************************************************************************
*                                                                              *
*                               Public methods                                 *
*                                                                              *
*******************************************************************************/
function FPDF($orientation='P',$unit='mm',$format='A4')
{
	// ajout pour HTML PARSER
	$this->fontlist = array("arial", "times", "courier", "helvetica", "symbol");
	$this->b = 0;
	$this->i = 0;
	$this->u = 0;
	$this->HREF = '';
	$this->issetfont = false;
	$this->issetcolor = false;
	$this->tableborder = 0;
	$this->tdbegin = false;
	$this->tdwidth=  0;
	$this->tdheight = 0;
	$this->tdalign = "L";
	$this->tdbgcolor = false;
	
	//Some checks
	$this->_dochecks();
	//Initialization of properties
	$this->page=0;
	$this->n=2;
	$this->buffer='';
	$this->pages=array();
	$this->OrientationChanges=array();
	$this->state=0;
	$this->fonts=array();
	$this->FontFiles=array();
	$this->diffs=array();
	$this->images=array();
	$this->links=array();
	$this->InFooter=false;
	$this->lasth=0;
	$this->FontFamily='';
	$this->FontStyle='';
	$this->FontSizePt=12;
	$this->underline=false;
	$this->DrawColor='0 G';
	$this->FillColor='0 g';
	$this->TextColor='0 g';
	$this->ColorFlag=false;
	$this->ws=0;
	//Standard fonts
	$this->CoreFonts=array('courier'=>'Courier','courierB'=>'Courier-Bold','courierI'=>'Courier-Oblique','courierBI'=>'Courier-BoldOblique',
		'helvetica'=>'Helvetica','helveticaB'=>'Helvetica-Bold','helveticaI'=>'Helvetica-Oblique','helveticaBI'=>'Helvetica-BoldOblique',
		'times'=>'Times-Roman','timesB'=>'Times-Bold','timesI'=>'Times-Italic','timesBI'=>'Times-BoldItalic',
		'symbol'=>'Symbol','zapfdingbats'=>'ZapfDingbats');
	//Scale factor
	if($unit=='pt')
		$this->k=1;
	elseif($unit=='mm')
		$this->k=72/25.4;
	elseif($unit=='cm')
		$this->k=72/2.54;
	elseif($unit=='in')
		$this->k=72;
	else
		$this->Error('Incorrect unit: '.$unit);
	//Page format
	if(is_string($format))
	{
		// Added new page formats (45 standard ISO paper formats and 4 american common formats).
		// Paper cordinates are calculated in this way: (inches * 72) where (1 inch = 2.54 cm)
		$format=strtolower($format);
		if($format=='4a0')
			$format=array(4767.87,6740.79);
		elseif($format=='2a0')
			$format=array(3370.39,4767.87);
		elseif($format=='a0')
			$format=array(2383.94,3370.39);
		elseif($format=='a1')
			$format=array(1683.78,2383.94);
		elseif($format=='a2')
			$format=array(1190.55,1683.78);
		elseif($format=='a3')
			$format=array(841.89,1190.55);
		elseif($format=='a4')
			$format=array(595.28,841.89);
		elseif($format=='a5')
			$format=array(420.94,595.28);
		elseif($format=='a6')
			$format=array(297.64,419.53);
		elseif($format=='a7')
			$format=array(209.76,297.64);
		elseif($format=='a8')
			$format=array(147.40,209.76);
		elseif($format=='a9')
			$format=array(104.88,147.40);
		elseif($format=='a10')
			$format=array(73.70,104.88);
		elseif($format=='b0')
			$format=array(2834.65,4008.19);
		elseif($format=='b1')
			$format=array(2004.09,2834.65);
		elseif($format=='b2')
			$format=array(1417.32,2004.09);
		elseif($format=='b3')
			$format=array(1000.63,1417.32);
		elseif($format=='b4')
			$format=array(708.66,1000.63);
		elseif($format=='b5')
			$format=array(498.90,708.66);
		elseif($format=='b6')
			$format=array(354.33,498.90);
		elseif($format=='b7')
			$format=array(249.45,354.33);
		elseif($format=='b8')
			$format=array(175.75,249.45);
		elseif($format=='b9')
			$format=array(124.72,175.75);
		elseif($format=='b10')
			$format=array(87.87,124.72);
		elseif($format=='c0')
			$format=array(2599.37,3676.54);
		elseif($format=='c1')
			$format=array(1836.85,2599.37);
		elseif($format=='c2')
			$format=array(1298.27,1836.85);
		elseif($format=='c3')
			$format=array(918.43,1298.27);
		elseif($format=='c4')
			$format=array(649.13,918.43);
		elseif($format=='c5')
			$format=array(459.21,649.13);
		elseif($format=='c6')
			$format=array(323.15,459.21);
		elseif($format=='c7')
			$format=array(229.61,323.15);
		elseif($format=='c8')
			$format=array(161.57,229.61);
		elseif($format=='c9')
			$format=array(113.39,161.57);
		elseif($format=='c10')
			$format=array(79.37,113.39);
		elseif($format=='ra0')
			$format=array(2437.80,3458.27);
		elseif($format=='ra1')
			$format=array(1729.13,2437.80);
		elseif($format=='ra2')
			$format=array(1218.90,1729.13);
		elseif($format=='ra3')
			$format=array(864.57,1218.90);
		elseif($format=='ra4')
			$format=array(609.45,864.57);
		elseif($format=='sra0')
			$format=array(2551.18,3628.35);
		elseif($format=='sra1')
			$format=array(1814.17,2551.18);
		elseif($format=='sra2')
			$format=array(1275.59,1814.17);
		elseif($format=='sra3')
			$format=array(907.09,1275.59);
		elseif($format=='sra4')
			$format=array(637.80,907.09);
		elseif($format=='letter')
			$format=array(612,792);
		elseif($format=='legal')
			$format=array(612,1008);
		elseif($format=='executive')
			$format=array(521.86,756);
		elseif($format=='folio')
			$format=array(612,936);
		else
			$this->Error('Unknown page format: '.$format);
		$this->fwPt=$format[0];
		$this->fhPt=$format[1];
	}
	else
	{
		$this->fwPt=$format[0]*$this->k;
		$this->fhPt=$format[1]*$this->k;
	}
	$this->fw=$this->fwPt/$this->k;
	$this->fh=$this->fhPt/$this->k;
	//Page orientation
	$orientation=strtolower($orientation);
	if($orientation=='p' || $orientation=='portrait')
	{
		$this->DefOrientation='P';
		$this->wPt=$this->fwPt;
		$this->hPt=$this->fhPt;
	}
	elseif($orientation=='l' || $orientation=='landscape')
	{
		$this->DefOrientation='L';
		$this->wPt=$this->fhPt;
		$this->hPt=$this->fwPt;
	}
	else
		$this->Error('Incorrect orientation: '.$orientation);
	$this->CurOrientation=$this->DefOrientation;
	$this->w=$this->wPt/$this->k;
	$this->h=$this->hPt/$this->k;
	//Page margins (1 cm)
	$margin=28.35/$this->k;
	$this->SetMargins($margin,$margin);
	//Interior cell margin (1 mm)
	$this->cMargin=$margin/10;
	//Line width (0.2 mm)
	$this->LineWidth=.567/$this->k;
	//Automatic page break
	$this->SetAutoPageBreak(true,2*$margin);
	//Full width display mode
	$this->SetDisplayMode('fullwidth');
	//Enable compression
	$this->SetCompression(true);
	//Set default PDF version number
	$this->PDFVersion='1.3';
}

function SetMargins($left,$top,$right=-1)
{
	//Set left, top and right margins
	$this->lMargin=$left;
	$this->tMargin=$top;
	if($right==-1)
		$right=$left;
	$this->rMargin=$right;
}

function SetLeftMargin($margin)
{
	//Set left margin
	$this->lMargin=$margin;
	if($this->page>0 && $this->x<$margin)
		$this->x=$margin;
}

function SetTopMargin($margin)
{
	//Set top margin
	$this->tMargin=$margin;
}

function SetRightMargin($margin)
{
	//Set right margin
	$this->rMargin=$margin;
}

function SetAutoPageBreak($auto,$margin=0)
{
	//Set auto page break mode and triggering margin
	$this->AutoPageBreak=$auto;
	$this->bMargin=$margin;
	$this->PageBreakTrigger=$this->h-$margin;
}

function SetDisplayMode($zoom,$layout='continuous')
{
	//Set display mode in viewer
	if($zoom=='fullpage' || $zoom=='fullwidth' || $zoom=='real' || $zoom=='default' || !is_string($zoom))
		$this->ZoomMode=$zoom;
	else
		$this->Error('Incorrect zoom display mode: '.$zoom);
	if($layout=='single' || $layout=='continuous' || $layout=='two' || $layout=='default')
		$this->LayoutMode=$layout;
	else
		$this->Error('Incorrect layout display mode: '.$layout);
}

function SetCompression($compress)
{
	//Set page compression
	if(function_exists('gzcompress'))
		$this->compress=$compress;
	else
		$this->compress=false;
}

function SetTitle($title)
{
	//Title of document
	$this->title=$title;
}

function SetSubject($subject)
{
	//Subject of document
	$this->subject=$subject;
}

function SetAuthor($author)
{
	//Author of document
	$this->author=$author;
}

function SetKeywords($keywords)
{
	//Keywords of document
	$this->keywords=$keywords;
}

function SetCreator($creator)
{
	//Creator of document
	$this->creator=$creator;
}

function AliasNbPages($alias='{nb}')
{
	//Define an alias for total number of pages
	$this->AliasNbPages=$alias;
}

function Error($msg)
{
	//Fatal error
	die('<B>FPDF error: </B>'.$msg);
}

function Open()
{
	//Begin document
	$this->state=1;
}

function Close()
{
	//Terminate document
	if($this->state==3)
		return;
	if($this->page==0)
		$this->AddPage();
	//Page footer
	$this->InFooter=true;
	$this->Footer();
	$this->InFooter=false;
	//Close page
	$this->_endpage();
	//Close document
	$this->_enddoc();
}

function AddPage($orientation='')
{
	//Start a new page
	if($this->state==0)
		$this->Open();
	$family=$this->FontFamily;
	$style=$this->FontStyle.($this->underline ? 'U' : '');
	$size=$this->FontSizePt;
	$lw=$this->LineWidth;
	$dc=$this->DrawColor;
	$fc=$this->FillColor;
	$tc=$this->TextColor;
	$cf=$this->ColorFlag;
	if($this->page>0)
	{
		//Page footer
		$this->InFooter=true;
		$this->Footer();
		$this->InFooter=false;
		//Close page
		$this->_endpage();
	}
	//Start new page
	$this->_beginpage($orientation);
	//Set line cap style to square
	$this->_out('2 J');
	//Set line width
	$this->LineWidth=$lw;
	$this->_out(sprintf('%.2f w',$lw*$this->k));
	//Set font
	if($family)
		$this->SetFont($family,$style,$size);
	//Set colors
	$this->DrawColor=$dc;
	if($dc!='0 G')
		$this->_out($dc);
	$this->FillColor=$fc;
	if($fc!='0 g')
		$this->_out($fc);
	$this->TextColor=$tc;
	$this->ColorFlag=$cf;
	//Page header
	$this->Header();
	//Restore line width
	if($this->LineWidth!=$lw)
	{
		$this->LineWidth=$lw;
		$this->_out(sprintf('%.2f w',$lw*$this->k));
	}
	//Restore font
	if($family)
		$this->SetFont($family,$style,$size);
	//Restore colors
	if($this->DrawColor!=$dc)
	{
		$this->DrawColor=$dc;
		$this->_out($dc);
	}
	if($this->FillColor!=$fc)
	{
		$this->FillColor=$fc;
		$this->_out($fc);
	}
	$this->TextColor=$tc;
	$this->ColorFlag=$cf;
}

function Header()
{
	//To be implemented in your own inherited class
}

function Footer()
{
	//To be implemented in your own inherited class
}

function PageNo()
{
	//Get current page number
	return $this->page;
}

function SetDrawColor($r,$g=-1,$b=-1)
{
	//Set color for all stroking operations
	if(($r==0 && $g==0 && $b==0) || $g==-1)
		$this->DrawColor=sprintf('%.3f G',$r/255);
	else
		$this->DrawColor=sprintf('%.3f %.3f %.3f RG',$r/255,$g/255,$b/255);
	if($this->page>0)
		$this->_out($this->DrawColor);
}

function SetFillColor($r,$g=-1,$b=-1)
{
	//Set color for all filling operations
	if(($r==0 && $g==0 && $b==0) || $g==-1)
		$this->FillColor=sprintf('%.3f g',$r/255);
	else
		$this->FillColor=sprintf('%.3f %.3f %.3f rg',$r/255,$g/255,$b/255);
	$this->ColorFlag=($this->FillColor!=$this->TextColor);
	if($this->page>0)
		$this->_out($this->FillColor);
}

function SetTextColor($r,$g=-1,$b=-1)
{
	//Set color for text
	if(($r==0 && $g==0 && $b==0) || $g==-1)
		$this->TextColor=sprintf('%.3f g',$r/255);
	else
		$this->TextColor=sprintf('%.3f %.3f %.3f rg',$r/255,$g/255,$b/255);
	$this->ColorFlag=($this->FillColor!=$this->TextColor);
}

function GetStringWidth($s)
{
	//Get width of a string in the current font
	$s=(string)$s;
	$cw=&$this->CurrentFont['cw'];
	$w=0;
	$l=strlen($s);
	for($i=0;$i<$l;$i++)
		$w+=$cw[$s{$i}];
	return $w*$this->FontSize/1000;
}

function SetLineWidth($width)
{
	//Set line width
	$this->LineWidth=$width;
	if($this->page>0)
		$this->_out(sprintf('%.2f w',$width*$this->k));
}

function Line($x1,$y1,$x2,$y2)
{
	//Draw a line
	$this->_out(sprintf('%.2f %.2f m %.2f %.2f l S',$x1*$this->k,($this->h-$y1)*$this->k,$x2*$this->k,($this->h-$y2)*$this->k));
}

function Rect($x,$y,$w,$h,$style='')
{
	//Draw a rectangle
	if($style=='F')
		$op='f';
	elseif($style=='FD' || $style=='DF')
		$op='B';
	else
		$op='S';
	$this->_out(sprintf('%.2f %.2f %.2f %.2f re %s',$x*$this->k,($this->h-$y)*$this->k,$w*$this->k,-$h*$this->k,$op));
}

function AddFont($family,$style='',$file='')
{
	//Add a TrueType or Type1 font
	$family=strtolower($family);
	if($file=='')
		$file=str_replace(' ','',$family).strtolower($style).'.php';
	if($family=='arial')
		$family='helvetica';
	$style=strtoupper($style);
	if($style=='IB')
		$style='BI';
	$fontkey=$family.$style;
	if(isset($this->fonts[$fontkey]))
		$this->Error('Font already added: '.$family.' '.$style);
	include($this->_getfontpath().$file);
	if(!isset($name))
		$this->Error('Could not include font definition file');
	$i=count($this->fonts)+1;
	$this->fonts[$fontkey]=array('i'=>$i,'type'=>$type,'name'=>$name,'desc'=>$desc,'up'=>$up,'ut'=>$ut,'cw'=>$cw,'enc'=>$enc,'file'=>$file);
	if($diff)
	{
		//Search existing encodings
		$d=0;
		$nb=count($this->diffs);
		for($i=1;$i<=$nb;$i++)
		{
			if($this->diffs[$i]==$diff)
			{
				$d=$i;
				break;
			}
		}
		if($d==0)
		{
			$d=$nb+1;
			$this->diffs[$d]=$diff;
		}
		$this->fonts[$fontkey]['diff']=$d;
	}
	if($file)
	{
		if($type=='TrueType')
			$this->FontFiles[$file]=array('length1'=>$originalsize);
		else
			$this->FontFiles[$file]=array('length1'=>$size1,'length2'=>$size2);
	}
}

function SetFont($family,$style='',$size=0)
{
	// save previous values
	$this->prevFontFamily = $this->FontFamily;
	$this->prevFontStyle = $this->FontStyle;
	
	//Select a font; size given in points
	global $fpdf_charwidths;
	$family=strtolower($family);
	if($family=='')
		$family=$this->FontFamily;
	if($family=='arial')
		$family='helvetica';
	elseif($family=='symbol' || $family=='zapfdingbats' || $family=='courier')
		$style='';
	$style=strtoupper($style);
	if(strpos($style,'U')!==false)
	{
		$this->underline=true;
		$style=str_replace('U','',$style);
	}
	else
		$this->underline=false;
	if($style=='IB')
		$style='BI';
	if($size==0)
		$size=$this->FontSizePt;
	//Test if font is already selected
	if($this->FontFamily==$family && $this->FontStyle==$style && $this->FontSizePt==$size)
		return;
	//Test if used for the first time
	$fontkey=$family.$style;
	if(!isset($this->fonts[$fontkey]))
	{
		//Check if one of the standard fonts
		if(isset($this->CoreFonts[$fontkey]))
		{
			if(!isset($fpdf_charwidths[$fontkey]))
			{
				//Load metric file
				$file=$family;
				if($family=='times' || $family=='helvetica')
					$file.=strtolower($style);
				include($this->_getfontpath().$file.'.php');
				if(!isset($fpdf_charwidths[$fontkey]))
					$this->Error('Could not include font metric file');
			}
			$i=count($this->fonts)+1;
			$this->fonts[$fontkey]=array('i'=>$i,'type'=>'core','name'=>$this->CoreFonts[$fontkey],'up'=>-100,'ut'=>50,'cw'=>$fpdf_charwidths[$fontkey]);
		}
		else
			$this->Error('Undefined font - family: '.$family.' - style: '.$style);
	}
	//Select it
	$this->FontFamily=$family;
	$this->FontStyle=$style;
	$this->FontSizePt=$size;
	$this->FontSize=$size/$this->k;
	$this->CurrentFont=&$this->fonts[$fontkey];
	if($this->page>0)
		$this->_out(sprintf('BT /F%d %.2f Tf ET',$this->CurrentFont['i'],$this->FontSizePt));
}

function SetFontSize($size)
{
	//Set font size in points
	if($this->FontSizePt==$size)
		return;
	$this->FontSizePt=$size;
	$this->FontSize=$size/$this->k;
	if($this->page>0)
		$this->_out(sprintf('BT /F%d %.2f Tf ET',$this->CurrentFont['i'],$this->FontSizePt));
}

function AddLink()
{
	//Create a new internal link
	$n=count($this->links)+1;
	$this->links[$n]=array(0,0);
	return $n;
}

function SetLink($link,$y=0,$page=-1)
{
	//Set destination of internal link
	if($y==-1)
		$y=$this->y;
	if($page==-1)
		$page=$this->page;
	$this->links[$link]=array($page,$y);
}

function Link($x,$y,$w,$h,$link)
{
	//Put a link on the page
	$this->PageLinks[$this->page][]=array($x*$this->k,$this->hPt-$y*$this->k,$w*$this->k,$h*$this->k,$link);
}

function Text($x,$y,$txt)
{
	//Output a string
	$s=sprintf('BT %.2f %.2f Td (%s) Tj ET',$x*$this->k,($this->h-$y)*$this->k,$this->_escape($txt));
	if($this->underline && $txt!='')
		$s.=' '.$this->_dounderline($x,$y,$txt);
	if($this->ColorFlag)
		$s='q '.$this->TextColor.' '.$s.' Q';
	$this->_out($s);
}

function AcceptPageBreak()
{
	//Accept automatic page break or not
	return $this->AutoPageBreak;
}

function Cell($w,$h=0,$txt='',$border=0,$ln=0,$align='',$fill=0,$link='')
{
	//Output a cell
	$k=$this->k;
	if($this->y+$h>$this->PageBreakTrigger && !$this->InFooter && $this->AcceptPageBreak())
	{
		//Automatic page break
		$x=$this->x;
		$ws=$this->ws;
		if($ws>0)
		{
			$this->ws=0;
			$this->_out('0 Tw');
		}
		$this->AddPage($this->CurOrientation);
		$this->x=$x;
		if($ws>0)
		{
			$this->ws=$ws;
			$this->_out(sprintf('%.3f Tw',$ws*$k));
		}
	}
	if($w==0)
		$w=$this->w-$this->rMargin-$this->x;
	$s='';
	if($fill==1 || $border==1)
	{
		if($fill==1)
			$op=($border==1) ? 'B' : 'f';
		else
			$op='S';
		$s=sprintf('%.2f %.2f %.2f %.2f re %s ',$this->x*$k,($this->h-$this->y)*$k,$w*$k,-$h*$k,$op);
	}
	if(is_string($border))
	{
		$x=$this->x;
		$y=$this->y;
		if(strpos($border,'L')!==false)
			$s.=sprintf('%.2f %.2f m %.2f %.2f l S ',$x*$k,($this->h-$y)*$k,$x*$k,($this->h-($y+$h))*$k);
		if(strpos($border,'T')!==false)
			$s.=sprintf('%.2f %.2f m %.2f %.2f l S ',$x*$k,($this->h-$y)*$k,($x+$w)*$k,($this->h-$y)*$k);
		if(strpos($border,'R')!==false)
			$s.=sprintf('%.2f %.2f m %.2f %.2f l S ',($x+$w)*$k,($this->h-$y)*$k,($x+$w)*$k,($this->h-($y+$h))*$k);
		if(strpos($border,'B')!==false)
			$s.=sprintf('%.2f %.2f m %.2f %.2f l S ',$x*$k,($this->h-($y+$h))*$k,($x+$w)*$k,($this->h-($y+$h))*$k);
	}
	if($txt!=='')
	{
		if($align=='R')
			$dx=$w-$this->cMargin-$this->GetStringWidth($txt);
		elseif($align=='C')
			$dx=($w-$this->GetStringWidth($txt))/2;
		else
			$dx=$this->cMargin;
		if($this->ColorFlag)
			$s.='q '.$this->TextColor.' ';
		$txt2=str_replace(')','\\)',str_replace('(','\\(',str_replace('\\','\\\\',$txt)));
		$s.=sprintf('BT %.2f %.2f Td (%s) Tj ET',($this->x+$dx)*$k,($this->h-($this->y+.5*$h+.3*$this->FontSize))*$k,$txt2);
		if($this->underline)
			$s.=' '.$this->_dounderline($this->x+$dx,$this->y+.5*$h+.3*$this->FontSize,$txt);
		if($this->ColorFlag)
			$s.=' Q';
		if($link)
			$this->Link($this->x+$dx,$this->y+.5*$h-.5*$this->FontSize,$this->GetStringWidth($txt),$this->FontSize,$link);
	}
	if($s)
		$this->_out($s);
	$this->lasth=$h;
	if($ln>0)
	{
		//Go to next line
		$this->y+=$h;
		if($ln==1)
			$this->x=$this->lMargin;
	}
	else
		$this->x+=$w;
}

function MultiCell($w,$h,$txt,$border=0,$align='J',$fill=0)
{
	//Output text with automatic or explicit line breaks
	$cw=&$this->CurrentFont['cw'];
	if($w==0)
		$w=$this->w-$this->rMargin-$this->x;
	$wmax=($w-2*$this->cMargin)*1000/$this->FontSize;
	$s=str_replace("\r",'',$txt);
	$nb=strlen($s);
	if($nb>0 && $s[$nb-1]=="\n")
		$nb--;
	$b=0;
	if($border)
	{
		if($border==1)
		{
			$border='LTRB';
			$b='LRT';
			$b2='LR';
		}
		else
		{
			$b2='';
			if(strpos($border,'L')!==false)
				$b2.='L';
			if(strpos($border,'R')!==false)
				$b2.='R';
			$b=(strpos($border,'T')!==false) ? $b2.'T' : $b2;
		}
	}
	$sep=-1;
	$i=0;
	$j=0;
	$l=0;
	$ns=0;
	$nl=1;
	while($i<$nb)
	{
		//Get next character
		$c=$s{$i};
		if($c=="\n")
		{
			//Explicit line break
			if($this->ws>0)
			{
				$this->ws=0;
				$this->_out('0 Tw');
			}
			$this->Cell($w,$h,substr($s,$j,$i-$j),$b,2,$align,$fill);
			$i++;
			$sep=-1;
			$j=$i;
			$l=0;
			$ns=0;
			$nl++;
			if($border && $nl==2)
				$b=$b2;
			continue;
		}
		if($c==' ')
		{
			$sep=$i;
			$ls=$l;
			$ns++;
		}
		$l+=$cw[$c];
		if($l>$wmax)
		{
			//Automatic line break
			if($sep==-1)
			{
				if($i==$j)
					$i++;
				if($this->ws>0)
				{
					$this->ws=0;
					$this->_out('0 Tw');
				}
				$this->Cell($w,$h,substr($s,$j,$i-$j),$b,2,$align,$fill);
			}
			else
			{
				if($align=='J')
				{
					$this->ws=($ns>1) ? ($wmax-$ls)/1000*$this->FontSize/($ns-1) : 0;
					$this->_out(sprintf('%.3f Tw',$this->ws*$this->k));
				}
				$this->Cell($w,$h,substr($s,$j,$sep-$j),$b,2,$align,$fill);
				$i=$sep+1;
			}
			$sep=-1;
			$j=$i;
			$l=0;
			$ns=0;
			$nl++;
			if($border && $nl==2)
				$b=$b2;
		}
		else
			$i++;
	}
	//Last chunk
	if($this->ws>0)
	{
		$this->ws=0;
		$this->_out('0 Tw');
	}
	if($border && strpos($border,'B')!==false)
		$b.='B';
	$this->Cell($w,$h,substr($s,$j,$i-$j),$b,2,$align,$fill);
	$this->x=$this->lMargin;
}

function Write($h,$txt,$link='')
{
	//Output text in flowing mode
	$cw=&$this->CurrentFont['cw'];
	$w=$this->w-$this->rMargin-$this->x;
	$wmax=($w-2*$this->cMargin)*1000/$this->FontSize;
	$s=str_replace("\r",'',$txt);
	$nb=strlen($s);
	$sep=-1;
	$i=0;
	$j=0;
	$l=0;
	$nl=1;
	while($i<$nb)
	{
		//Get next character
		$c=$s{$i};
		if($c=="\n")
		{
			//Explicit line break
			$this->Cell($w,$h,substr($s,$j,$i-$j),0,2,'',0,$link);
			$i++;
			$sep=-1;
			$j=$i;
			$l=0;
			if($nl==1)
			{
				$this->x=$this->lMargin;
				$w=$this->w-$this->rMargin-$this->x;
				$wmax=($w-2*$this->cMargin)*1000/$this->FontSize;
			}
			$nl++;
			continue;
		}
		if($c==' ')
			$sep=$i;
		$l+=$cw[$c];
		if($l>$wmax)
		{
			//Automatic line break
			if($sep==-1)
			{
				if($this->x>$this->lMargin)
				{
					//Move to next line
					$this->x=$this->lMargin;
					$this->y+=$h;
					$w=$this->w-$this->rMargin-$this->x;
					$wmax=($w-2*$this->cMargin)*1000/$this->FontSize;
					$i++;
					$nl++;
					continue;
				}
				if($i==$j)
					$i++;
				$this->Cell($w,$h,substr($s,$j,$i-$j),0,2,'',0,$link);
			}
			else
			{
				$this->Cell($w,$h,substr($s,$j,$sep-$j),0,2,'',0,$link);
				$i=$sep+1;
			}
			$sep=-1;
			$j=$i;
			$l=0;
			if($nl==1)
			{
				$this->x=$this->lMargin;
				$w=$this->w-$this->rMargin-$this->x;
				$wmax=($w-2*$this->cMargin)*1000/$this->FontSize;
			}
			$nl++;
		}
		else
			$i++;
	}
	//Last chunk
	if($i!=$j)
		$this->Cell($l/1000*$this->FontSize,$h,substr($s,$j),0,0,'',0,$link);
}

function Image($file,$x,$y,$w=0,$h=0,$type='',$link='')
{
	//Put an image on the page
	if(!isset($this->images[$file]))
	{
		//First use of image, get info
		if($type=='')
		{
			$pos=strrpos($file,'.');
			if(!$pos)
				$this->Error('Image file has no extension and no type was specified: '.$file);
			$type=substr($file,$pos+1);
		}
		$type=strtolower($type);
		$mqr=get_magic_quotes_runtime();
		set_magic_quotes_runtime(0);
		if($type=='jpg' || $type=='jpeg')
			$info=$this->_parsejpg($file);
		elseif($type=='png')
			$info=$this->_parsepng($file);
		else
		{
			//Allow for additional formats
			$mtd='_parse'.$type;
			if(!method_exists($this,$mtd))
				$this->Error('Unsupported image type: '.$type);
			$info=$this->$mtd($file);
		}
		set_magic_quotes_runtime($mqr);
		$info['i']=count($this->images)+1;
		$this->images[$file]=$info;
	}
	else
		$info=$this->images[$file];
	//Automatic width and height calculation if needed
	if($w==0 && $h==0)
	{
		//Put image at 72 dpi
		$w=$info['w']/$this->k;
		$h=$info['h']/$this->k;
	}
	if($w==0)
		$w=$h*$info['w']/$info['h'];
	if($h==0)
		$h=$w*$info['h']/$info['w'];
	$this->_out(sprintf('q %.2f 0 0 %.2f %.2f %.2f cm /I%d Do Q',$w*$this->k,$h*$this->k,$x*$this->k,($this->h-($y+$h))*$this->k,$info['i']));
	if($link)
		$this->Link($x,$y,$w,$h,$link);
}

function Ln($h='')
{
	//Line feed; default value is last cell height
	$this->x=$this->lMargin;
	if(is_string($h))
		$this->y+=$this->lasth;
	else
		$this->y+=$h;
}

function GetX()
{
	//Get x position
	return $this->x;
}

function SetX($x)
{
	//Set x position
	if($x>=0)
		$this->x=$x;
	else
		$this->x=$this->w+$x;
}

function GetY()
{
	//Get y position
	return $this->y;
}

function SetY($y)
{
	//Set y position and reset x
	$this->x=$this->lMargin;
	if($y>=0)
		$this->y=$y;
	else
		$this->y=$this->h+$y;
}

function SetXY($x,$y)
{
	//Set x and y positions
	$this->SetY($y);
	$this->SetX($x);
}

function Output($name='',$dest='')
{
	//Output PDF to some destination
	//Finish document if necessary
	if($this->state<3)
		$this->Close();
	//Normalize parameters
	if(is_bool($dest))
		$dest=$dest ? 'D' : 'F';
	$dest=strtoupper($dest);
	if($dest=='')
	{
		if($name=='')
		{
			$name='doc.pdf';
			$dest='I';
		}
		else
			$dest='F';
	}
	switch($dest)
	{
		case 'I':
			//Send to standard output
			if(ob_get_contents())
				$this->Error('Some data has already been output, can\'t send PDF file');
			if(php_sapi_name()!='cli')
			{
				//We send to a browser
				header('Content-Type: application/pdf');
				if(headers_sent())
					$this->Error('Some data has already been output to browser, can\'t send PDF file');
				header('Content-Length: '.strlen($this->buffer));
				header('Content-disposition: inline; filename="'.$name.'"');
			}
			echo $this->buffer;
			break;
		case 'D':
			//Download file
			if(ob_get_contents())
				$this->Error('Some data has already been output, can\'t send PDF file');
			if(isset($_SERVER['HTTP_USER_AGENT']) && strpos($_SERVER['HTTP_USER_AGENT'],'MSIE'))
				header('Content-Type: application/force-download');
			else
				header('Content-Type: application/octet-stream');
			if(headers_sent())
				$this->Error('Some data has already been output to browser, can\'t send PDF file');
			header('Content-Length: '.strlen($this->buffer));
			header('Content-disposition: attachment; filename="'.$name.'"');
			echo $this->buffer;
			break;
		case 'F':
			//Save to local file
			$f=fopen($name,'wb');
			if(!$f)
				$this->Error('Unable to create output file: '.$name);
			fwrite($f,$this->buffer,strlen($this->buffer));
			fclose($f);
			break;
		case 'S':
			//Return as a string
			return $this->buffer;
		default:
			$this->Error('Incorrect output destination: '.$dest);
	}
	return '';
}

/*******************************************************************************
*                                                                              *
*                              Protected methods                               *
*                                                                              *
*******************************************************************************/
function _dochecks()
{
	//Check for locale-related bug
	if(1.1==1)
		$this->Error('Don\'t alter the locale before including class file');
	//Check for decimal separator
	if(sprintf('%.1f',1.0)!='1.0')
		setlocale(LC_NUMERIC,'C');
}

function _getfontpath()
{
	if(!defined('FPDF_FONTPATH') && is_dir(dirname(__FILE__).'/font'))
		define('FPDF_FONTPATH',dirname(__FILE__).'/font/');
	return defined('FPDF_FONTPATH') ? FPDF_FONTPATH : '';
}

function _putpages()
{
	$nb=$this->page;
	if(!empty($this->AliasNbPages))
	{
		//Replace number of pages
		for($n=1;$n<=$nb;$n++)
			$this->pages[$n]=str_replace($this->AliasNbPages,$nb,$this->pages[$n]);
	}
	if($this->DefOrientation=='P')
	{
		$wPt=$this->fwPt;
		$hPt=$this->fhPt;
	}
	else
	{
		$wPt=$this->fhPt;
		$hPt=$this->fwPt;
	}
	$filter=($this->compress) ? '/Filter /FlateDecode ' : '';
	for($n=1;$n<=$nb;$n++)
	{
		//Page
		$this->_newobj();
		$this->_out('<</Type /Page');
		$this->_out('/Parent 1 0 R');
		if(isset($this->OrientationChanges[$n]))
			$this->_out(sprintf('/MediaBox [0 0 %.2f %.2f]',$hPt,$wPt));
		$this->_out('/Resources 2 0 R');
		if(isset($this->PageLinks[$n]))
		{
			//Links
			$annots='/Annots [';
			foreach($this->PageLinks[$n] as $pl)
			{
				$rect=sprintf('%.2f %.2f %.2f %.2f',$pl[0],$pl[1],$pl[0]+$pl[2],$pl[1]-$pl[3]);
				$annots.='<</Type /Annot /Subtype /Link /Rect ['.$rect.'] /Border [0 0 0] ';
				if(is_string($pl[4]))
					$annots.='/A <</S /URI /URI '.$this->_textstring($pl[4]).'>>>>';
				else
				{
					$l=$this->links[$pl[4]];
					$h=isset($this->OrientationChanges[$l[0]]) ? $wPt : $hPt;
					$annots.=sprintf('/Dest [%d 0 R /XYZ 0 %.2f null]>>',1+2*$l[0],$h-$l[1]*$this->k);
				}
			}
			$this->_out($annots.']');
		}
		$this->_out('/Contents '.($this->n+1).' 0 R>>');
		$this->_out('endobj');
		//Page content
		$p=($this->compress) ? gzcompress($this->pages[$n]) : $this->pages[$n];
		$this->_newobj();
		$this->_out('<<'.$filter.'/Length '.strlen($p).'>>');
		$this->_putstream($p);
		$this->_out('endobj');
	}
	//Pages root
	$this->offsets[1]=strlen($this->buffer);
	$this->_out('1 0 obj');
	$this->_out('<</Type /Pages');
	$kids='/Kids [';
	for($i=0;$i<$nb;$i++)
		$kids.=(3+2*$i).' 0 R ';
	$this->_out($kids.']');
	$this->_out('/Count '.$nb);
	$this->_out(sprintf('/MediaBox [0 0 %.2f %.2f]',$wPt,$hPt));
	$this->_out('>>');
	$this->_out('endobj');
}

function _putfonts()
{
	$nf=$this->n;
	foreach($this->diffs as $diff)
	{
		//Encodings
		$this->_newobj();
		$this->_out('<</Type /Encoding /BaseEncoding /WinAnsiEncoding /Differences ['.$diff.']>>');
		$this->_out('endobj');
	}
	$mqr=get_magic_quotes_runtime();
	set_magic_quotes_runtime(0);
	foreach($this->FontFiles as $file=>$info)
	{
		//Font file embedding
		$this->_newobj();
		$this->FontFiles[$file]['n']=$this->n;
		$font='';
		$f=fopen($this->_getfontpath().$file,'rb',1);
		if(!$f)
			$this->Error('Font file not found');
		while(!feof($f))
			$font.=fread($f,8192);
		fclose($f);
		$compressed=(substr($file,-2)=='.z');
		if(!$compressed && isset($info['length2']))
		{
			$header=(ord($font{0})==128);
			if($header)
			{
				//Strip first binary header
				$font=substr($font,6);
			}
			if($header && ord($font{$info['length1']})==128)
			{
				//Strip second binary header
				$font=substr($font,0,$info['length1']).substr($font,$info['length1']+6);
			}
		}
		$this->_out('<</Length '.strlen($font));
		if($compressed)
			$this->_out('/Filter /FlateDecode');
		$this->_out('/Length1 '.$info['length1']);
		if(isset($info['length2']))
			$this->_out('/Length2 '.$info['length2'].' /Length3 0');
		$this->_out('>>');
		$this->_putstream($font);
		$this->_out('endobj');
	}
	set_magic_quotes_runtime($mqr);
	foreach($this->fonts as $k=>$font)
	{
		//Font objects
		$this->fonts[$k]['n']=$this->n+1;
		$type=$font['type'];
		$name=$font['name'];
		if($type=='core')
		{
			//Standard font
			$this->_newobj();
			$this->_out('<</Type /Font');
			$this->_out('/BaseFont /'.$name);
			$this->_out('/Subtype /Type1');
			if($name!='Symbol' && $name!='ZapfDingbats')
				$this->_out('/Encoding /WinAnsiEncoding');
			$this->_out('>>');
			$this->_out('endobj');
		}
		elseif($type=='Type1' || $type=='TrueType')
		{
			//Additional Type1 or TrueType font
			$this->_newobj();
			$this->_out('<</Type /Font');
			$this->_out('/BaseFont /'.$name);
			$this->_out('/Subtype /'.$type);
			$this->_out('/FirstChar 32 /LastChar 255');
			$this->_out('/Widths '.($this->n+1).' 0 R');
			$this->_out('/FontDescriptor '.($this->n+2).' 0 R');
			if($font['enc'])
			{
				if(isset($font['diff']))
					$this->_out('/Encoding '.($nf+$font['diff']).' 0 R');
				else
					$this->_out('/Encoding /WinAnsiEncoding');
			}
			$this->_out('>>');
			$this->_out('endobj');
			//Widths
			$this->_newobj();
			$cw=&$font['cw'];
			$s='[';
			for($i=32;$i<=255;$i++)
				$s.=$cw[chr($i)].' ';
			$this->_out($s.']');
			$this->_out('endobj');
			//Descriptor
			$this->_newobj();
			$s='<</Type /FontDescriptor /FontName /'.$name;
			foreach($font['desc'] as $k=>$v)
				$s.=' /'.$k.' '.$v;
			$file=$font['file'];
			if($file)
				$s.=' /FontFile'.($type=='Type1' ? '' : '2').' '.$this->FontFiles[$file]['n'].' 0 R';
			$this->_out($s.'>>');
			$this->_out('endobj');
		}
		else
		{
			//Allow for additional types
			$mtd='_put'.strtolower($type);
			if(!method_exists($this,$mtd))
				$this->Error('Unsupported font type: '.$type);
			$this->$mtd($font);
		}
	}
}

function _putimages()
{
	$filter=($this->compress) ? '/Filter /FlateDecode ' : '';
	reset($this->images);
	while(list($file,$info)=each($this->images))
	{
		$this->_newobj();
		$this->images[$file]['n']=$this->n;
		$this->_out('<</Type /XObject');
		$this->_out('/Subtype /Image');
		$this->_out('/Width '.$info['w']);
		$this->_out('/Height '.$info['h']);
		if($info['cs']=='Indexed')
			$this->_out('/ColorSpace [/Indexed /DeviceRGB '.(strlen($info['pal'])/3-1).' '.($this->n+1).' 0 R]');
		else
		{
			$this->_out('/ColorSpace /'.$info['cs']);
			if($info['cs']=='DeviceCMYK')
				$this->_out('/Decode [1 0 1 0 1 0 1 0]');
		}
		$this->_out('/BitsPerComponent '.$info['bpc']);
		if(isset($info['f']))
			$this->_out('/Filter /'.$info['f']);
		if(isset($info['parms']))
			$this->_out($info['parms']);
		if(isset($info['trns']) && is_array($info['trns']))
		{
			$trns='';
			for($i=0;$i<count($info['trns']);$i++)
				$trns.=$info['trns'][$i].' '.$info['trns'][$i].' ';
			$this->_out('/Mask ['.$trns.']');
		}
		$this->_out('/Length '.strlen($info['data']).'>>');
		$this->_putstream($info['data']);
		unset($this->images[$file]['data']);
		$this->_out('endobj');
		//Palette
		if($info['cs']=='Indexed')
		{
			$this->_newobj();
			$pal=($this->compress) ? gzcompress($info['pal']) : $info['pal'];
			$this->_out('<<'.$filter.'/Length '.strlen($pal).'>>');
			$this->_putstream($pal);
			$this->_out('endobj');
		}
	}
}

function _putxobjectdict()
{
	foreach($this->images as $image)
		$this->_out('/I'.$image['i'].' '.$image['n'].' 0 R');
}

function _putresourcedict()
{
	$this->_out('/ProcSet [/PDF /Text /ImageB /ImageC /ImageI]');
	$this->_out('/Font <<');
	foreach($this->fonts as $font)
		$this->_out('/F'.$font['i'].' '.$font['n'].' 0 R');
	$this->_out('>>');
	$this->_out('/XObject <<');
	$this->_putxobjectdict();
	$this->_out('>>');
}

function _putresources()
{
	$this->_putfonts();
	$this->_putimages();
	//Resource dictionary
	$this->offsets[2]=strlen($this->buffer);
	$this->_out('2 0 obj');
	$this->_out('<<');
	$this->_putresourcedict();
	$this->_out('>>');
	$this->_out('endobj');
}

function _putinfo()
{
	$this->_out('/Producer '.$this->_textstring('FPDF '.FPDF_VERSION));
	if(!empty($this->title))
		$this->_out('/Title '.$this->_textstring($this->title));
	if(!empty($this->subject))
		$this->_out('/Subject '.$this->_textstring($this->subject));
	if(!empty($this->author))
		$this->_out('/Author '.$this->_textstring($this->author));
	if(!empty($this->keywords))
		$this->_out('/Keywords '.$this->_textstring($this->keywords));
	if(!empty($this->creator))
		$this->_out('/Creator '.$this->_textstring($this->creator));
	$this->_out('/CreationDate '.$this->_textstring('D:'.date('YmdHis')));
}

function _putcatalog()
{
	$this->_out('/Type /Catalog');
	$this->_out('/Pages 1 0 R');
	if($this->ZoomMode=='fullpage')
		$this->_out('/OpenAction [3 0 R /Fit]');
	elseif($this->ZoomMode=='fullwidth')
		$this->_out('/OpenAction [3 0 R /FitH null]');
	elseif($this->ZoomMode=='real')
		$this->_out('/OpenAction [3 0 R /XYZ null null 1]');
	elseif(!is_string($this->ZoomMode))
		$this->_out('/OpenAction [3 0 R /XYZ null null '.($this->ZoomMode/100).']');
	if($this->LayoutMode=='single')
		$this->_out('/PageLayout /SinglePage');
	elseif($this->LayoutMode=='continuous')
		$this->_out('/PageLayout /OneColumn');
	elseif($this->LayoutMode=='two')
		$this->_out('/PageLayout /TwoColumnLeft');
	
	//Préférences d'affichage - @author Michel Poulain
	//affiche le document en plein écran (escape pour revenir en mode normal)
	if(is_int(strpos($this->DisplayPreferences,'FullScreen')))
        $this->_out('/PageMode /FullScreen');
    if($this->DisplayPreferences) {
        $this->_out('/ViewerPreferences<<');
        //masque la barre de menu
        if(is_int(strpos($this->DisplayPreferences,'HideMenubar')))
            $this->_out('/HideMenubar true');
        //masque les barres d'outils
        if(is_int(strpos($this->DisplayPreferences,'HideToolbar')))
            $this->_out('/HideToolbar true');
        //masque tous les éléments de la fenêtre (barres de défilement, contrôles de navigation, signets...)
        if(is_int(strpos($this->DisplayPreferences,'HideWindowUI')))
            $this->_out('/HideWindowUI true');
        //affiche le titre du document au lieu du nom du fichier
        if(is_int(strpos($this->DisplayPreferences,'DisplayDocTitle')))
            $this->_out('/DisplayDocTitle true');
        //centre la fenêtre
        if(is_int(strpos($this->DisplayPreferences,'CenterWindow')))
            $this->_out('/CenterWindow true');
        //ajuste la taille de la fenêtre (lorsqu'elle n'est pas maximisée) sur celle de la page
        if(is_int(strpos($this->DisplayPreferences,'FitWindow')))
            $this->_out('/FitWindow true');
        $this->_out('>>');
    }
}

function _putheader()
{
	$this->_out('%PDF-'.$this->PDFVersion);
}

function _puttrailer()
{
	$this->_out('/Size '.($this->n+1));
	$this->_out('/Root '.$this->n.' 0 R');
	$this->_out('/Info '.($this->n-1).' 0 R');
}

function _enddoc()
{
	$this->_putheader();
	$this->_putpages();
	$this->_putresources();
	//Info
	$this->_newobj();
	$this->_out('<<');
	$this->_putinfo();
	$this->_out('>>');
	$this->_out('endobj');
	//Catalog
	$this->_newobj();
	$this->_out('<<');
	$this->_putcatalog();
	$this->_out('>>');
	$this->_out('endobj');
	//Cross-ref
	$o=strlen($this->buffer);
	$this->_out('xref');
	$this->_out('0 '.($this->n+1));
	$this->_out('0000000000 65535 f ');
	for($i=1;$i<=$this->n;$i++)
		$this->_out(sprintf('%010d 00000 n ',$this->offsets[$i]));
	//Trailer
	$this->_out('trailer');
	$this->_out('<<');
	$this->_puttrailer();
	$this->_out('>>');
	$this->_out('startxref');
	$this->_out($o);
	$this->_out('%%EOF');
	$this->state=3;
}

function _beginpage($orientation)
{
	$this->page++;
	$this->pages[$this->page]='';
	$this->state=2;
	$this->x=$this->lMargin;
	$this->y=$this->tMargin;
	$this->FontFamily='';
	//Page orientation
	if(!$orientation)
		$orientation=$this->DefOrientation;
	else
	{
		$orientation=strtoupper($orientation{0});
		if($orientation!=$this->DefOrientation)
			$this->OrientationChanges[$this->page]=true;
	}
	if($orientation!=$this->CurOrientation)
	{
		//Change orientation
		if($orientation=='P')
		{
			$this->wPt=$this->fwPt;
			$this->hPt=$this->fhPt;
			$this->w=$this->fw;
			$this->h=$this->fh;
		}
		else
		{
			$this->wPt=$this->fhPt;
			$this->hPt=$this->fwPt;
			$this->w=$this->fh;
			$this->h=$this->fw;
		}
		$this->PageBreakTrigger=$this->h-$this->bMargin;
		$this->CurOrientation=$orientation;
	}
}

function _endpage()
{
	//End of page contents
	$this->state=1;
}

function _newobj()
{
	//Begin a new object
	$this->n++;
	$this->offsets[$this->n]=strlen($this->buffer);
	$this->_out($this->n.' 0 obj');
}

function _dounderline($x,$y,$txt)
{
	//Underline text
	$up=$this->CurrentFont['up'];
	$ut=$this->CurrentFont['ut'];
	$w=$this->GetStringWidth($txt)+$this->ws*substr_count($txt,' ');
	return sprintf('%.2f %.2f %.2f %.2f re f',$x*$this->k,($this->h-($y-$up/1000*$this->FontSize))*$this->k,$w*$this->k,-$ut/1000*$this->FontSizePt);
}

function _parsejpg($file)
{
	//Extract info from a JPEG file
	$a=GetImageSize($file);
	if(!$a)
		$this->Error('Missing or incorrect image file: '.$file);
	if($a[2]!=2)
		$this->Error('Not a JPEG file: '.$file);
	if(!isset($a['channels']) || $a['channels']==3)
		$colspace='DeviceRGB';
	elseif($a['channels']==4)
		$colspace='DeviceCMYK';
	else
		$colspace='DeviceGray';
	$bpc=isset($a['bits']) ? $a['bits'] : 8;
	//Read whole file
	$f=fopen($file,'rb');
	$data='';
	while(!feof($f))
		$data.=fread($f,4096);
	fclose($f);
	return array('w'=>$a[0],'h'=>$a[1],'cs'=>$colspace,'bpc'=>$bpc,'f'=>'DCTDecode','data'=>$data);
}

function _parsepng($file)
{
	//Extract info from a PNG file
	$f=fopen($file,'rb');
	if(!$f)
		$this->Error('Can\'t open image file: '.$file);
	//Check signature
	if(fread($f,8)!=chr(137).'PNG'.chr(13).chr(10).chr(26).chr(10))
		$this->Error('Not a PNG file: '.$file);
	//Read header chunk
	fread($f,4);
	if(fread($f,4)!='IHDR')
		$this->Error('Incorrect PNG file: '.$file);
	$w=$this->_freadint($f);
	$h=$this->_freadint($f);
	$bpc=ord(fread($f,1));
	if($bpc>8)
		$this->Error('16-bit depth not supported: '.$file);
	$ct=ord(fread($f,1));
	if($ct==0)
		$colspace='DeviceGray';
	elseif($ct==2)
		$colspace='DeviceRGB';
	elseif($ct==3)
		$colspace='Indexed';
	else
		$this->Error('Alpha channel not supported: '.$file);
	if(ord(fread($f,1))!=0)
		$this->Error('Unknown compression method: '.$file);
	if(ord(fread($f,1))!=0)
		$this->Error('Unknown filter method: '.$file);
	if(ord(fread($f,1))!=0)
		$this->Error('Interlacing not supported: '.$file);
	fread($f,4);
	$parms='/DecodeParms <</Predictor 15 /Colors '.($ct==2 ? 3 : 1).' /BitsPerComponent '.$bpc.' /Columns '.$w.'>>';
	//Scan chunks looking for palette, transparency and image data
	$pal='';
	$trns='';
	$data='';
	do
	{
		$n=$this->_freadint($f);
		$type=fread($f,4);
		if($type=='PLTE')
		{
			//Read palette
			$pal=fread($f,$n);
			fread($f,4);
		}
		elseif($type=='tRNS')
		{
			//Read transparency info
			$t=fread($f,$n);
			if($ct==0)
				$trns=array(ord(substr($t,1,1)));
			elseif($ct==2)
				$trns=array(ord(substr($t,1,1)),ord(substr($t,3,1)),ord(substr($t,5,1)));
			else
			{
				$pos=strpos($t,chr(0));
				if($pos!==false)
					$trns=array($pos);
			}
			fread($f,4);
		}
		elseif($type=='IDAT')
		{
			//Read image data block
			$data.=fread($f,$n);
			fread($f,4);
		}
		elseif($type=='IEND')
			break;
		else
			fread($f,$n+4);
	}
	while($n);
	if($colspace=='Indexed' && empty($pal))
		$this->Error('Missing palette in '.$file);
	fclose($f);
	return array('w'=>$w,'h'=>$h,'cs'=>$colspace,'bpc'=>$bpc,'f'=>'FlateDecode','parms'=>$parms,'pal'=>$pal,'trns'=>$trns,'data'=>$data);
}

function _freadint($f)
{
	//Read a 4-byte integer from file
	$a=unpack('Ni',fread($f,4));
	return $a['i'];
}

function _textstring($s)
{
	//Format a text string
	return '('.$this->_escape($s).')';
}

function _escape($s)
{
	//Add \ before \, ( and )
	return str_replace(')','\\)',str_replace('(','\\(',str_replace('\\','\\\\',$s)));
}

function _putstream($s)
{
	$this->_out('stream');
	$this->_out($s);
	$this->_out('endstream');
}

function _out($s)
{
	//Add a line to the document
	if($this->state==2)
		$this->pages[$this->page].=$s."\n";
	else
		$this->buffer.=$s."\n";
}

    // --- HTML PARSER FUNCTIONS ---
    
    /**
		 * Allows to preserve some HTML formatting.<br />
		 * Supports: h1, h2, h3, h4, h5, h6, b, u, i, a, img, p, br, strong, em, font, blockquote, li, ul, ol, hr, td, th, tr, table, sup, sub, small
		 * @param string $html text to display
		 * @param boolean $ln if true add a new line after text (default = true)
		 * @param int $fill Indicates if the background must be painted (1) or transparent (0). Default value: 0.
		 */
		function writeHTML($html, $ln=true, $fill=0) {
						
			// store some variables
			$html=strip_tags($html,"<h1><h2><h3><h4><h5><h6><b><u><i><a><img><p><br><br/><strong><em><font><blockquote><li><ul><ol><hr><td><th><tr><table><sup><sub><small>"); //remove all unsupported tags
			//replace carriage returns, newlines and tabs
			$repTable = array("\t" => " ", "\n" => "<br>", "\r" => " ", "\0" => " ", "\x0B" => " "); 
			$html = strtr($html, $repTable);
			$pattern = '/(<[^>]+>)/Uu';
			$a = preg_split($pattern, $html, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY); //explodes the string
			
			if (empty($this->lasth)) {
				//set row height
				$this->lasth = $this->FontSize * K_CELL_HEIGHT_RATIO; 
			}
			
			foreach($a as $key=>$element) {
				$element = ereg_replace('&ndash;','-',$element); //remplace les &ndash; par un tiret
				$element = ereg_replace('&rsquo;','\'',$element); //remplace les &rsquo; par un apostrophe
				$element = ereg_replace('&quot;','"',$element); //remplace les &quot; par une guillemet
				if (!preg_match($pattern, $element)) {
					//Text
					if($this->HREF) {
						$this->addHtmlLink($this->HREF, $element, $fill);
					}
					elseif($this->tdbegin) {
						if((strlen(trim($element)) > 0) AND ($element != "&nbsp;")) {
							// Cette version ne gère pas UTF8
							//$this->Cell($this->tdwidth, $this->tdheight, $this->unhtmlentities($element), $this->tableborder, '', $this->tdalign, $this->tdbgcolor);
							$this->Cell($this->tdwidth, $this->tdheight, utf8_decode($this->unhtmlentities($element)), $this->tableborder, '', $this->tdalign, $this->tdbgcolor);
						}
						elseif($element == "&nbsp;") {
							$this->Cell($this->tdwidth, $this->tdheight, '', $this->tableborder, '', $this->tdalign, $this->tdbgcolor);
						}
					}
					else {
						// cette version ne gère pas UTF8
						//$this->Write($this->lasth, stripslashes($this->unhtmlentities($element)), '', $fill);
						$this->Write($this->lasth, stripslashes(utf8_decode($this->unhtmlentities($element))), '', $fill);
					}
				}
				else {
					$element = substr($element, 1, -1);
					//Tag
					if($element{0}=='/') {
						$this->closedHTMLTagHandler(strtolower(substr($element, 1)));
					}
					else {
						//Extract attributes
						// get tag name
						preg_match('/([a-zA-Z0-9]*)/', $element, $tag);
						$tag = strtolower($tag[0]);
						// get attributes
						preg_match_all('/([^=\s]*)=["\']?([^"\']*)["\']?/', $element, $attr_array, PREG_PATTERN_ORDER);
						$attr = array(); // reset attribute array
						while(list($id,$name)=each($attr_array[1])) {
							$attr[strtolower($name)] = $attr_array[2][$id];
						}
						$this->openHTMLTagHandler($tag, $attr, $fill);
					}
				}
			}
			if ($ln) {
				$this->Ln($this->lasth);
			}
		}
		
		/**
		 * Prints a cell (rectangular area) with optional borders, background color and html text string. The upper-left corner of the cell corresponds to the current position. After the call, the current position moves to the right or to the next line.<br />
		 * If automatic page breaking is enabled and the cell goes beyond the limit, a page break is done before outputting.
		 * @param float $w Cell width. If 0, the cell extends up to the right margin.
		 * @param float $h Cell minimum height. The cell extends automatically if needed.
		 * @param float $x upper-left corner X coordinate
		 * @param float $y upper-left corner Y coordinate
		 * @param string $html html text to print. Default value: empty string.
		 * @param mixed $border Indicates if borders must be drawn around the cell. The value can be either a number:<ul><li>0: no border (default)</li><li>1: frame</li></ul>or a string containing some or all of the following characters (in any order):<ul><li>L: left</li><li>T: top</li><li>R: right</li><li>B: bottom</li></ul>
		 * @param int $ln Indicates where the current position should go after the call. Possible values are:<ul><li>0: to the right</li><li>1: to the beginning of the next line</li><li>2: below</li></ul>
	Putting 1 is equivalent to putting 0 and calling Ln() just after. Default value: 0.
		 * @param int $fill Indicates if the cell background must be painted (1) or transparent (0). Default value: 0.
		 * @see Cell()
		 */
		function writeHTMLCell($w, $h, $x, $y, $html='', $border=0, $ln=0, $fill=0) {
			
			if (empty($this->lasth)) {
				//set row height
				$this->lasth = $this->FontSize * K_CELL_HEIGHT_RATIO; 
			}
			
			if (empty($x)) {
				$x = $this->GetX();
			}
			if (empty($y)) {
				$y = $this->GetY();
			}
			
			// get current page number
			$pagenum = $this->page;
			
			$this->SetX($x);
			$this->SetY($y);
					
			if(empty($w)) {
				$w = $this->w - $x - $this->rMargin;
			}
			
			// store original margin values
			$lMargin = $this->lMargin;
			$rMargin = $this->rMargin;
			
			// set new margin values
			$this->SetLeftMargin($x);
			$this->SetRightMargin($this->w - $x - $w);
					
			// calculate remaining vertical space on page
			$restspace = $this->getPageHeight() - $this->GetY() - $this->getBreakMargin();
			
			$this->writeHTML($html, true, $fill); // write html text
			
			$currentY =  $this->GetY();
			
			// check if a new page has been created
			if ($this->page > $pagenum) {
				// design a cell around the text on first page
				$currentpage = $this->page;
				$this->page = $pagenum;
				$this->SetY($this->getPageHeight() - $restspace - $this->getBreakMargin());
				$h = $restspace - 1;
				$this->Cell($w, $h, "", $border, $ln, 'L', 0);
				// design a cell around the text on last page
				$this->page = $currentpage;
				$h = $currentY - $this->tMargin;
				$this->SetY($this->tMargin); // put cursor at the beginning of text
				$this->Cell($w, $h, "", $border, $ln, 'L', 0);
			} else {
				$h = max($h, ($currentY - $y));
				$this->SetY($y); // put cursor at the beginning of text
				// design a cell around the text
				$this->Cell($w, $h, "", $border, $ln, 'L', 0);
			}
			
			// restore original margin values
			$this->SetLeftMargin($lMargin);
			$this->SetRightMargin($rMargin);
			
			if ($ln) {
				$this->Ln(0);
			}
		}
		
		/**
		 * Process opening tags.
		 * @param string $tag tag name (in uppercase)
		 * @param string $attr tag attribute (in uppercase)
		 * @param int $fill Indicates if the cell background must be painted (1) or transparent (0). Default value: 0.
		 * @access private
		 */
		function openHTMLTagHandler($tag, $attr, $fill=0) {
			//Opening tag
			switch($tag) {
				case 'table': {
					if ((isset($attr['border'])) AND ($attr['border'] != '')) {
						$this->tableborder = $attr['border'];
					}
					else {
						$this->tableborder = 0;
					}
					break;
				}
				case 'tr': {
					break;
				}
				case 'td':
				case 'th': {
					if ((isset($attr['width'])) AND ($attr['width'] != '')) {
						$this->tdwidth = ($attr['width']/4);
					}
					else {
						$this->tdwidth = (($this->w - $this->lMargin - $this->rMargin) / $this->default_table_columns);
					}
					if ((isset($attr['height'])) AND ($attr['height'] != '')) {
						$this->tdheight=($attr['height'] / $this->k);
					}
					else {
						$this->tdheight = $this->lasth;
					}
					if ((isset($attr['align'])) AND ($attr['align'] != '')) {
						switch ($attr['align']) {
							case 'center': {
								$this->tdalign = "C";
								break;
							}
							case 'right': {
								$this->tdalign = "R";
								break;
							}
							default:
							case 'left': {
								$this->tdalign = "L";
								break;
							}
						}
					}
					if ((isset($attr['bgcolor'])) AND ($attr['bgcolor'] != '')) {
						$coul = $this->convertColorHexToDec($attr['bgcolor']);
						$this->SetFillColor($coul['R'], $coul['G'], $coul['B']);
						$this->tdbgcolor=true;
					}
					$this->tdbegin=true;
					break;
				}
				case 'hr': {
					$this->Ln();
					if ((isset($attr['width'])) AND ($attr['width'] != '')) {
						$hrWidth = $attr['width'];
					}
					else {
						$hrWidth = $this->w - $this->lMargin - $this->rMargin;
					}
					$x = $this->GetX();
					$y = $this->GetY();
					$this->SetLineWidth(0.2);
					$this->Line($x, $y, $x + $hrWidth, $y);
					$this->SetLineWidth(0.2);
					$this->Ln();
					break;
				}
				case 'strong': {
					$this->setStyle('b', true);
					break;
				}
				case 'em': {
					$this->setStyle('i', true);
					break;
				}
				case 'b':
				case 'i':
				case 'u': {
					$this->setStyle($tag, true);
					break;
				}
				case 'a': {
					$this->HREF = $attr['href'];
					break;
				}
				case 'img': {
					if(isset($attr['src'])) {
						// replace relative path with real server path
						$attr['src'] = str_replace(K_PATH_URL_CACHE, K_PATH_CACHE, $attr['src']);
						if(!isset($attr['width'])) {
							$attr['width'] = 0;
						}
						if(!isset($attr['height'])) {
							$attr['height'] = 0;
						}
						
						$this->Image($attr['src'], $this->GetX(),$this->GetY(), $this->pixelsToMillimeters($attr['width']), $this->pixelsToMillimeters($attr['height']));
						//$this->SetX($this->img_rb_x);
						$this->SetY($this->img_rb_y);
						
					}
					break;
				}
				case 'ul': {
					$this->listordered = false;
					$this->listcount = 0;
					break;
				}
				case 'ol': {
					$this->listordered = true;
					$this->listcount = 0;
					break;
				}
				case 'li': {
					$this->Ln();
					if ($this->listordered) {
						$this->lispacer = "    ".(++$this->listcount).". ";
					}
					else {
						//unordered list simbol
						$this->lispacer = "    -  ";
					}
					$this->Write($this->lasth, $this->lispacer, '', $fill);
					break;
				}
				case 'tr':
				case 'blockquote':
				case 'br': {
					$this->Ln();
					if(strlen($this->lispacer) > 0) {
						$this->x += $this->GetStringWidth($this->lispacer);
					}
					break;
				}
				case 'p': {
					$this->Ln();
					$this->Ln();
					break;
				}
				case 'sup': {
					$currentFontSize = $this->FontSize;
					$this->tempfontsize = $this->FontSizePt;
					$this->SetFontSize($this->FontSizePt * K_SMALL_RATIO);
					$this->SetXY($this->GetX(), $this->GetY() - (($currentFontSize - $this->FontSize)*(K_SMALL_RATIO)));
					break;
				}
				case 'sub': {
					$currentFontSize = $this->FontSize;
					$this->tempfontsize = $this->FontSizePt;
					$this->SetFontSize($this->FontSizePt * K_SMALL_RATIO);
					$this->SetXY($this->GetX(), $this->GetY() + (($currentFontSize - $this->FontSize)*(K_SMALL_RATIO)));
					break;
				}
				case 'small': {
					$currentFontSize = $this->FontSize;
					$this->tempfontsize = $this->FontSizePt;
					$this->SetFontSize($this->FontSizePt * K_SMALL_RATIO);
					$this->SetXY($this->GetX(), $this->GetY() + (($currentFontSize - $this->FontSize)/3));
					break;
				}
				case 'font': {
					if (isset($attr['color']) AND $attr['color']!='') {
						$coul = $this->convertColorHexToDec($attr['color']);
						$this->SetTextColor($coul['R'],$coul['G'],$coul['B']);
						$this->issetcolor=true;
					}
					
					// convertir les noms des polices de FckEditor
					$attr['face'] = $this->convertNameFont($attr['face']);
					
					if (isset($attr['face']) and in_array(strtolower($attr['face']), $this->fontlist)) {
						$this->SetFont(strtolower($attr['face']));
						$this->issetfont=true;
					}
					if (isset($attr['size'])) {
						$headsize = intval($attr['size']);
					} else {
						$headsize = 0;
					}
					$currentFontSize = $this->FontSize;
					$this->tempfontsize = $this->FontSizePt;
					//$this->SetFontSize($this->FontSizePt + $headsize);
					$this->SetFontSize($this->FontSizePt + $headsize - 3); // Todo: correction pour xx-small
					$this->lasth = $this->FontSize * K_CELL_HEIGHT_RATIO;
					break;
				}
				case 'h1': 
				case 'h2': 
				case 'h3': 
				case 'h4': 
				case 'h5': 
				case 'h6': {
					$headsize = (4 - substr($tag, 1)) * 2;
					$currentFontSize = $this->FontSize;
					$this->tempfontsize = $this->FontSizePt;
					$this->SetFontSize($this->FontSizePt + $headsize);
					$this->setStyle('b', true);
					$this->lasth = $this->FontSize * K_CELL_HEIGHT_RATIO;
					break;
				}
			}
		}
		
		/**
		 * Process closing tags.
		 * @param string $tag tag name (in uppercase)
		 * @access private
		 */
		function closedHTMLTagHandler($tag) {
			//Closing tag
			switch($tag) {
				case 'td':
				case 'th': {
					$this->tdbegin = false;
					$this->tdwidth = 0;
					$this->tdheight = 0;
					$this->tdalign = "L";
					$this->tdbgcolor = false;
					$this->SetFillColor($this->prevFillColor[0], $this->prevFillColor[1], $this->prevFillColor[2]);
					break;
				}
				case 'tr': {
					$this->Ln();
					break;
				}
				case 'table': {
					$this->tableborder=0;
					break;
				}
				case 'strong': {
					$this->setStyle('b', false);
					break;
				}
				case 'em': {
					$this->setStyle('i', false);
					break;
				}
				case 'b':
				case 'i':
				case 'u': {
					$this->setStyle($tag, false);
					break;
				}
				case 'a': {
					$this->HREF = '';
					break;
				}
				case 'sup': {
					$currentFontSize = $this->FontSize;
					$this->SetFontSize($this->tempfontsize);
					$this->tempfontsize = $this->FontSizePt;
					$this->SetXY($this->GetX(), $this->GetY() - (($currentFontSize - $this->FontSize)*(K_SMALL_RATIO)));
					break;
				}
				case 'sub': {
					$currentFontSize = $this->FontSize;
					$this->SetFontSize($this->tempfontsize);
					$this->tempfontsize = $this->FontSizePt;
					$this->SetXY($this->GetX(), $this->GetY() + (($currentFontSize - $this->FontSize)*(K_SMALL_RATIO)));
					break;
				}
				case 'small': {
					$currentFontSize = $this->FontSize;
					$this->SetFontSize($this->tempfontsize);
					$this->tempfontsize = $this->FontSizePt;
					$this->SetXY($this->GetX(), $this->GetY() - (($this->FontSize - $currentFontSize)/3));
					break;
				}
				case 'font': {
					if ($this->issetcolor == true) {
						$this->SetTextColor($this->prevTextColor[0], $this->prevTextColor[1], $this->prevTextColor[2]);
					}
					if ($this->issetfont) {
						$this->FontFamily = $this->prevFontFamily;
						$this->FontStyle = $this->prevFontStyle;
						$this->SetFont($this->FontFamily);
						$this->issetfont = false;
					}
					$currentFontSize = $this->FontSize;
					$this->SetFontSize($this->tempfontsize);
					$this->tempfontsize = $this->FontSizePt;
					//$this->TextColor = $this->prevTextColor;
					$this->lasth = $this->FontSize * K_CELL_HEIGHT_RATIO;
					break;
				}
				case 'ul': {
					$this->Ln();
					break;
				}
				case 'ol': {
					$this->Ln();
					break;
				}
				case 'li': {
					$this->lispacer = "";
					break;
				}
				case 'h1': 
				case 'h2': 
				case 'h3': 
				case 'h4': 
				case 'h5': 
				case 'h6': {
					$currentFontSize = $this->FontSize;
					$this->SetFontSize($this->tempfontsize);
					$this->tempfontsize = $this->FontSizePt;
					$this->setStyle('b', false);
					$this->Ln();
					$this->lasth = $this->FontSize * K_CELL_HEIGHT_RATIO;
					break;
				}
				default : {
					break;
				}
			}
		}
		
		/**
		 * Sets font style.
		 * @param string $tag tag name (in lowercase)
		 * @param boolean $enable
		 * @access private
		 */
		function setStyle($tag, $enable) {
			//Modify style and select corresponding font
			$this->$tag += ($enable ? 1 : -1);
			$style='';
			foreach(array('b', 'i', 'u') as $s) {
				if($this->$s > 0) {
					$style .= $s;
				}
			}
			$this->SetFont('', $style);
		}
		
		/**
		 * Output anchor link.
		 * @param string $url link URL
		 * @param string $name link name
		 * @param int $fill Indicates if the cell background must be painted (1) or transparent (0). Default value: 0.
		 * @access public
		 */
		function addHtmlLink($url, $name, $fill=0) {
			//Put a hyperlink
			$this->SetTextColor(0, 0, 255);
			$this->setStyle('u', true);
			$this->Write($this->lasth, $name, $url, $fill);
			$this->setStyle('u', false);
			$this->SetTextColor(0);
		}
		
		/**
		 * Returns an associative array (keys: R,G,B) from 
		 * a hex html code (e.g. #3FE5AA).
		 * @param string $color hexadecimal html color [#rrggbb]
		 * @return array
		 * @access private
		 */
		function convertColorHexToDec($color = "#000000"){
			$tbl_color = array();
			$tbl_color['R'] = hexdec(substr($color, 1, 2));
			$tbl_color['G'] = hexdec(substr($color, 3, 2));
			$tbl_color['B'] = hexdec(substr($color, 5, 2));
			return $tbl_color;
		}
		
		/**
		 * Converts pixels to millimeters in 72 dpi.
		 * @param int $px pixels
		 * @return float millimeters
		 * @access private
		 */
		function pixelsToMillimeters($px){
			return $px * 25.4 / 72;
		}
			
		/**
		 * Reverse function for htmlentities.
		 * Convert entities in UTF-8.
		 *
		 * @param $text_to_convert Text to convert.
		 * @return string converted
		 */
		function unhtmlentities($text_to_convert) {
			require_once(dirname(__FILE__).'/html_entity_decode_php4.php');
			return html_entity_decode_php4($text_to_convert);
		}
		
				/**
		* Set the image scale.
		* @param float $scale image scale.
		* @author Nicola Asuni
		* @since 1.5.2
		*/
		function setImageScale($scale) {
			$this->imgscale=$scale;
		}

		/**
		* Returns the image scale.
		* @return float image scale.
		* @author Nicola Asuni
		* @since 1.5.2
		*/
		function getImageScale() {
			return $this->imgscale;
		}

		/**
		* Returns the page width in units.
		* @return int page width.
		* @author Nicola Asuni
		* @since 1.5.2
		*/
		function getPageWidth() {
			return $this->w;
		}

		/**
		* Returns the page height in units.
		* @return int page height.
		* @author Nicola Asuni
		* @since 1.5.2
		*/
		function getPageHeight() {
			return $this->fh;
		}

		/**
		* Returns the page break margin.
		* @return int page break margin.
		* @author Nicola Asuni
		* @since 1.5.2
		*/
		function getBreakMargin() {
			return $this->bMargin;
		}

		/**
		* Returns the scale factor (number of points in user unit).
		* @return int scale factor.
		* @author Nicola Asuni
		* @since 1.5.2
		*/
		function getScaleFactor() {
			return $this->k;
		}
		
		/**
		* Converti les noms des polices FckEditor.
		* @string string chaine à convertir
		* @return string chaine convertie.
		* @author Régis Houssin
		*/
		function convertNameFont($namefont)
		{
			if ($namefont == "Times New Roman") 
			{
				$name = "times";
			}
			else if ($namefont == "Arial")
			{
				$name = "helvetica";
			}
			else if ($namefont == "Verdana")
			{
				$name = "helvetica";
			}
			else if ($namefont == "Comic Sans MS")
			{
				$name = "helvetica";
			}
			else if ($namefont == "Courier New")
			{
				$name = "courier";
			}
			else if ($namefont == "Tahoma")
			{
				$name = "helvetica";
			}
			return $name;
		}
		
		/**
		* Paramétrage des préférences d'affichage.
		* @string preference liste des préférences d'affichage (voir la fonction _putcatalog)
		* @ex: $pdf->DisplayPreferences('HideMenubar,HideToolbar,HideWindowUI')
		* @author Michel Poulain
		*/
		function DisplayPreferences($preferences)
		{
			$this->DisplayPreferences.=$preferences;
		}

//End of class
}

//Handle special IE contype request
if(isset($_SERVER['HTTP_USER_AGENT']) && $_SERVER['HTTP_USER_AGENT']=='contype')
{
	header('Content-Type: application/pdf');
	exit;
}

}
?>
