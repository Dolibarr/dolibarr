<?php

// -----------------------------------------------
// Cryptographp v1.4
// (c) 2006-2007 Sylvain BRISON 
//
// www.cryptographp.com 
// cryptographp@alphpa.com 
//
// Licence CeCILL modifiée
// => Voir fichier Licence_CeCILL_V2-fr.txt)
// -----------------------------------------------


error_reporting(E_ALL ^ E_NOTICE);
srand((double)microtime()*1000000); 

if  ((!isset($_COOKIE['cryptcookietest'])) and ($_GET[$_GET['sn']]==""))
    {
    header("Content-type: image/png");
    readfile('images/erreur3.png'); 
    exit;
    }

if ($_GET[$_GET['sn']]=="") unset ($_GET['sn']); 
session_start();


// N'accepte que les fichiers de config du meme répertoire
if (is_file($_GET['cfg']) and dirname($_GET['cfg'])=='.' ) $_SESSION['configfile']=$_GET['cfg']; 
   else  $_SESSION['configfile']="cryptographp.cfg.php";

include($_SESSION['configfile']);  


// Vérifie si l'utilisateur a le droit de (re)générer un cryptogramme
if ($_SESSION['cryptcptuse']>=$cryptusemax) {
   header("Content-type: image/png");
   readfile('images/erreur1.png'); 
   exit;
   }

$delai = time()-$_SESSION['crypttime'];
if ($delai < $cryptusetimer) { 
   switch ($cryptusertimererror) {
          case 2  : header("Content-type: image/png");
                    readfile('images/erreur2.png'); 
                    exit;
          case 3  : sleep ($cryptusetimer-$delai);
                    break; // Fait une pause
          case 1  :          
          default : exit;  // Quitte le script sans rien faire
          }
   }

// Création du cryptogramme temporaire
$imgtmp = imagecreatetruecolor($cryptwidth,$cryptheight);
$blank  = imagecolorallocate($imgtmp,255,255,255);
$black   = imagecolorallocate($imgtmp,0,0,0);
imagefill($imgtmp,0,0,$blank);


$word ='';
$x = 10; 
$pair = rand(0,1);
$charnb = rand($charnbmin,$charnbmax);
for ($i=1;$i<= $charnb;$i++) {              
     $tword[$i]['font'] =  $tfont[array_rand($tfont,1)];
     $tword[$i]['angle'] = (rand(1,2)==1)?rand(0,$charanglemax):rand(360-$charanglemax,360);
     
     if ($crypteasy) $tword[$i]['element'] =(!$pair)?$charelc{rand(0,strlen($charelc)-1)}:$charelv{rand(0,strlen($charelv)-1)};
        else $tword[$i]['element'] = $charel{rand(0,strlen($charel)-1)};

     $pair=!$pair;
     $tword[$i]['size'] = rand($charsizemin,$charsizemax);
     $tword[$i]['y'] = ($charup?($cryptheight/2)+rand(0,($cryptheight/5)):($cryptheight/1.5));
     $word .=$tword[$i]['element'];
     
     $lafont="fonts/".$tword[$i]['font'];
     imagettftext($imgtmp,$tword[$i]['size'],$tword[$i]['angle'],$x,$tword[$i]['y'],$black,$lafont,$tword[$i]['element']);

     $x +=$charspace;
     } 

// Calcul du racadrage horizontal du cryptogramme temporaire
$xbegin=0;
$x=0;
while (($x<$cryptwidth)and(!$xbegin)) {
     $y=0;
     while (($y<$cryptheight)and(!$xbegin)) {
           if (imagecolorat($imgtmp,$x,$y) != $blank) $xbegin = $x;
           $y++;
           }
     $x++;
     } 
    
$xend=0;
$x=$cryptwidth-1;
while (($x>0)and(!$xend)) {
     $y=0;
     while (($y<$cryptheight)and(!$xend)) {
           if (imagecolorat($imgtmp,$x,$y) != $blank) $xend = $x;
           $y++;
           }
     $x--;
     } 
     
$xvariation = round(($cryptwidth/2)-(($xend-$xbegin)/2));
imagedestroy ($imgtmp);


// Création du cryptogramme définitif
// Création du fond
$img = imagecreatetruecolor($cryptwidth,$cryptheight); 

if ($bgimg and is_dir($bgimg)) {
                    $dh  = opendir($bgimg);
                    while (false !== ($filename = readdir($dh))) 
                          if(eregi(".[gif|jpg|png]$", $filename))  $files[] = $filename;
                    closedir($dh);
                    $bgimg = $bgimg.'/'.$files[array_rand($files,1)];
                    }
if ($bgimg) {
            list($getwidth, $getheight, $gettype, $getattr) = getimagesize($bgimg);
            switch ($gettype) {
                   case "1": $imgread = imagecreatefromgif($bgimg); break;
			             case "2": $imgread = imagecreatefromjpeg($bgimg); break;
			             case "3": $imgread = imagecreatefrompng($bgimg); break;
                   }
	          imagecopyresized ($img, $imgread, 0,0,0,0,$cryptwidth,$cryptheight,$getwidth,$getheight);
		        imagedestroy ($imgread);
            }
            else {
                 $bg = imagecolorallocate($img,$bgR,$bgG,$bgB);
                 imagefill($img,0,0,$bg);
                 if ($bgclear) imagecolortransparent($img,$bg);
                 }


function ecriture()
{
// Création de l'écriture
global  $img, $ink, $charR, $charG, $charB, $charclear, $xvariation, $charnb, $charcolorrnd, $charcolorrndlevel, $tword, $charspace;
if (function_exists ('imagecolorallocatealpha')) $ink = imagecolorallocatealpha($img,$charR,$charG,$charB,$charclear);
   else $ink = imagecolorallocate ($img,$charR,$charG,$charB);

$x = $xvariation;
for ($i=1;$i<=$charnb;$i++) {       
       
    if ($charcolorrnd){   // Choisit des couleurs au hasard
       $ok = false;
       do {
          $rndR = rand(0,255); $rndG = rand(0,255); $rndB = rand(0,255);
          $rndcolor = $rndR+$rndG+$rndB;
          switch ($charcolorrndlevel) {
                 case 1  : if ($rndcolor<200) $ok=true; break; // tres sombre
                 case 2  : if ($rndcolor<400) $ok=true; break; // sombre
                 case 3  : if ($rndcolor>500) $ok=true; break; // claires
                 case 4  : if ($rndcolor>650) $ok=true; break; // très claires
                 default : $ok=true;               
                 }
          } while (!$ok);
          
      if (function_exists ('imagecolorallocatealpha')) $rndink = imagecolorallocatealpha($img,$rndR,$rndG,$rndB,$charclear);
          else $rndink = imagecolorallocate ($img,$rndR,$rndG,$rndB);          
         }  
         
    $lafont="fonts/".$tword[$i]['font'];
    imagettftext($img,$tword[$i]['size'],$tword[$i]['angle'],$x,$tword[$i]['y'],$charcolorrnd?$rndink:$ink,$lafont,$tword[$i]['element']);

    $x +=$charspace;
    } 
}


function noisecolor()
// Fonction permettant de déterminer la couleur du bruit et la forme du pinceau
 {
 global $img, $noisecolorchar, $ink, $bg, $brushsize;
 switch ($noisecolorchar) {
         case 1  : $noisecol=$ink; break;
         case 2  : $noisecol=$bg; break;
         case 3  : 
         default : $noisecol=imagecolorallocate ($img,rand(0,255),rand(0,255),rand(0,255)); break;               
         }
 if ($brushsize and $brushsize>1 and function_exists('imagesetbrush')) {
    $brush = imagecreatetruecolor($brushsize,$brushsize);
    imagefill($brush,0,0,$noisecol);
    imagesetbrush($img,$brush);
    $noisecol=IMG_COLOR_BRUSHED;
    }
 return $noisecol;    
}


function bruit()
// Ajout de bruits: point, lignes et cercles aléatoires
{
global $noisepxmin, $noisepxmax, $noiselinemin, $noiselinemax, $nbcirclemin, $nbcirclemax,$img, $cryptwidth, $cryptheight;
$nbpx = rand($noisepxmin,$noisepxmax);
$nbline = rand($noiselinemin,$noiselinemax);
$nbcircle = rand($nbcirclemin,$nbcirclemax);
for ($i=1;$i<$nbpx;$i++) imagesetpixel ($img,rand(0,$cryptwidth-1),rand(0,$cryptheight-1),noisecolor());
for ($i=1;$i<=$nbline;$i++) imageline($img,rand(0,$cryptwidth-1),rand(0,$cryptheight-1),rand(0,$cryptwidth-1),rand(0,$cryptheight-1),noisecolor());
for ($i=1;$i<=$nbcircle;$i++) imagearc($img,rand(0,$cryptwidth-1),rand(0,$cryptheight-1),$rayon=rand(5,$cryptwidth/3),$rayon,0,360,noisecolor());
} 


if ($noiseup) {
   ecriture();
   bruit();
   } else {
          bruit();
          ecriture();
          }


// Création du cadre
if ($bgframe) {
   $framecol = imagecolorallocate($img,($bgR*3+$charR)/4,($bgG*3+$charG)/4,($bgB*3+$charB)/4);
   imagerectangle($img,0,0,$cryptwidth-1,$cryptheight-1,$framecol);
   }
 
            
// Transformations supplémentaires: Grayscale et Brouillage
// Vérifie si la fonction existe dans la version PHP installée
if (function_exists('imagefilter')) {
   if ($cryptgrayscal) imagefilter ( $img,IMG_FILTER_GRAYSCALE);
   if ($cryptgaussianblur) imagefilter ( $img,IMG_FILTER_GAUSSIAN_BLUR);
   }


// Conversion du cryptogramme en Majuscule si insensibilité à la casse
$word = ($difuplow?$word:strtoupper($word));


// Retourne 2 informations dans la session: 
// - Le code du cryptogramme (crypté ou pas)
// - La Date/Heure de la création du cryptogramme au format integer "TimeStamp" 
switch (strtoupper($cryptsecure)) {    
       case "MD5"  : $_SESSION['cryptcode'] = md5($word); break;
       case "SHA1" : $_SESSION['cryptcode'] = sha1($word); break;
       default     : $_SESSION['cryptcode'] = $word; break;
       }
$_SESSION['crypttime'] = time();
$_SESSION['cryptcptuse']++;       
  

// Envoi de l'image finale au navigateur 
switch (strtoupper($cryptformat)) {  
       case "JPG"  :
	     case "JPEG" : if (imagetypes() & IMG_JPG)  {
                        header("Content-type: image/jpeg");
                        imagejpeg($img, "", 80);
                        }
                     break;
	     case "GIF"  : if (imagetypes() & IMG_GIF)  {
                        header("Content-type: image/gif");
                        imagegif($img);
                        }
                     break;
	     case "PNG"  : 
	     default     : if (imagetypes() & IMG_PNG)  {
                        header("Content-type: image/png");
                        imagepng($img);
                        }
       }

imagedestroy ($img);
unset ($word,$tword);
unset ($_SESSION['cryptreload']); 
?>
