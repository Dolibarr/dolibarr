<?php
/* Modifié par Rodolphe Quiédeville
 * Auteur : Pierre-André Vullioud
 * Licence : Freeware
 * Source : http://www.fpdf.org
 */

class PDF_Indexes extends FPDF
{
  var $RefActive=0;        //Flag indicating that the index is being processed
  var $ChangePage=0;       //Flag indicating that a page break has occurred
  var $Reference=array();  //Array containing the references
  var $col=0;              //Current column number
  var $NbCol;              //Total number of columns
  var $y0;                 //Top ordinate of columns
  
  function Header()
  {
    if($this->RefActive==1)
      {
        //Title of index pages
        $this->SetFont('Arial','',15);
        $this->Cell(0,5,'Index',0,1,'C');
        $this->Ln();
      }
  }
  
  function Reference($txt, $index)
  {
    $Present=0;
    $size=sizeof($this->Reference[$index]);
    
    //Search the reference in the array
    for ($i=0;$i<$size;$i++)
      {
	if ($this->Reference[$index][$i]['t']==$txt)
	  {
	    $Present=1;
	    $this->Reference[$index][$i]['p'].=','.$this->PageNo();
	  }
      }

    //If not found, add it
    if ($Present==0)
      {
	$this->Reference[$index][]=array('t'=>$txt,'p'=>$this->PageNo());
      }
  }
  

  function CreateReference($NbCol, $index)
  {
    //    var_dump($this->Reference[$index]);

    //Initialization
    $this->RefActive=1;
    $this->SetFontSize(8);
    
    //New page
    $this->AddPage();
    
    //Save the ordinate
    $this->y0=$this->GetY();
    $this->NbCol=$NbCol;
    $size=sizeof($this->Reference[$index]);
    $PageWidth=$this->w-$this->lMargin-$this->rMargin;
    $last = '';
    for ($i=0;$i<$size;$i++)
      {
      
	//Handles page break and new position
	if ($this->ChangePage==1)
	  {
	    $this->ChangePage=0;
	    $this->y0=$this->GetY()-$this->FontSize-1;
	  }
	
	//LibellLabel
	if (is_array($this->Reference[$index][$i]['t']))
	  {
	    if ($last <> $this->Reference[$index][$i]['t'][0])
	      {
		$this->MultiCell(90, 4,$this->Reference[$index][$i]['t'][0],0,1,'L');
		$last = $this->Reference[$index][$i]['t'][0];
	      }
	    $str= "  ".$this->Reference[$index][$i]['t'][1];
	  }
	else
	  {
	    $str= $this->Reference[$index][$i]['t'];
	  }
	
	$strsize=$this->GetStringWidth($str);

	$this->Cell($strsize+2,$this->FontSize+2,$str,0,0,'R');
	
	//Dots
	//Computes the widths
	$ColWidth = ($PageWidth/$NbCol)-2;
	$w=$ColWidth-$this->GetStringWidth($this->Reference[$index][$i]['p'])-($strsize+4);
	if ($w<15)
	  $w=15;
	$nb=$w/$this->GetStringWidth('.');
	$dots=str_repeat('.',$nb-2);
	$this->Cell($w,$this->FontSize+2,$dots,0,0,'L');
	
	//Page number
	$Largeur=$ColWidth-$strsize-$w;
	$this->MultiCell($Largeur,$this->FontSize+1,$this->Reference[$index][$i]['p'],0,1,'R');
      }
    $this->RefActive==0;
  }

  function SortReference($index)
  {
    $ar = $this->Reference[$index];

    $size=sizeof($this->Reference[$index]);
    $cats = array();
    $last = '';
    for ($i=0;$i<$size;$i++)
      {
	//print $ar[$i]['t'][0] . "<br>";
	$cat = $ar[$i]['t'][0];
	if (! array_key_exists($cat, $cats))
	  {
	    $cats[$cat] = array();
	  }
	array_push($cats[$cat], array($ar[$i]['t'][1], $ar[$i]['p']));
      }
    
    ksort($cats);
    //var_dump($cats);
    $i = 0;
    foreach ($cats as $key => $value)
      {
	foreach ($value as $skey => $svalue)
	  {	    
	    $this->Reference[$index][$i]['t'][0] = $key;
	    $this->Reference[$index][$i]['t'][1] = $svalue[0];
	    $this->Reference[$index][$i]['p'] = $svalue[1];
	    $i++;
	  }
					 
      }

  }
  
  function SetCol($col)
  {
    //Set position on a column
    $this->col=$col;
    $x=$this->rMargin+$col*($this->w-$this->rMargin-$this->rMargin)/$this->NbCol;
    $this->SetLeftMargin($x);
    $this->SetX($x);
  }
  
  function AcceptPageBreak()
  {
    if ($this->RefActive==1)
      {
	if($this->col<$this->NbCol-1)
	  {
	    //Go to the next column
	    $this->SetCol($this->col+1);
	    $this->SetY($this->y0);
	    //Stay on the page
	    return false;
	  }
	else
	  {
	    //Go back to the first column
	    $this->SetCol(0);
	    $this->ChangePage=1;
	    //Page break
	    return true;
	  }
      }
    else
      {
        return true;
      }
  }
}
?>
