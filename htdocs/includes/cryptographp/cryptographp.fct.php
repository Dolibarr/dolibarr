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

 if(session_id() == "") session_start();
 
 $_SESSION['cryptdir']= DOL_URL_ROOT.'/includes/cryptographp';
 //$_SESSION['cryptdir']= dirname($cryptinstall);
 
 function dsp_crypt($cfg=0,$reload=1) {
 // Affiche le cryptogramme
 print "<td align='left'><img id='cryptogram' src='".$_SESSION['cryptdir']."/cryptographp.php?cfg=".$cfg."&".SID."'>";
 if ($reload) print "<a title='".($reload==1?'':$reload)."' style=\"cursor:pointer\" onclick=\"javascript:document.images.cryptogram.src='".$_SESSION['cryptdir']."/cryptographp.php?cfg=".$cfg."&".SID."&'+Math.round(Math.random(0)*1000)+1\"><img src=\"".$_SESSION['cryptdir']."/images/reload.png\"></a></td>";
 }


 function chk_crypt($code) {
 // Vérifie si le code est correct
 include ($_SESSION['configfile']);
 $code = addslashes ($code);
 $code = str_replace(' ','',$code);  // supprime les espaces saisis par erreur.
 $code = ($difuplow?$code:strtoupper($code));
 switch (strtoupper($cryptsecure)) {    
        case "MD5"  : $code = md5($code); break;
        case "SHA1" : $code = sha1($code); break;
        }
 if ($_SESSION['cryptcode'] and ($_SESSION['cryptcode'] == $code))
    {
    unset($_SESSION['cryptreload']);
    if ($cryptoneuse) unset($_SESSION['cryptcode']);    
    return true;
    }
    else {
         $_SESSION['cryptreload']= true;
         return false;
         }
 }

?>
