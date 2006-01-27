<?php

/*
 * Copyleft 2002 Johann Hanne
 *
 * This is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * This software is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this software; if not, write to the
 * Free Software Foundation, Inc., 59 Temple Place,
 * Suite 330, Boston, MA  02111-1307 USA
 */

/*
 * This is the OLE::Storage_Lite Perl package ported to PHP
 * OLE::Storage_Lite was written by Kawai Takanori, kwitknr@cpan.org
 */

require_once "class.ole_pps.php";
require_once "functions.ole.php";

class ole_pps_root extends ole_pps {

    function ole_pps_root($raTime1st=false, $raTime2nd=false, $raChild=false) {
        $this->No         = false;
        $this->Name       = Asc2Ucs('Root Entry');
        $this->Type       = PpsType_Root;
        $this->PrevPps    = false;
        $this->NextPps    = false;
        $this->DirPps     = false;
        $this->Time1st    = $raTime1st;
        $this->Time2nd    = $raTime2nd;
        $this->StartBlock = false;
        $this->Size       = false;
        $this->Data       = false;
        $this->Child      = $raChild;
    }

#------------------------------------------------------------------------------
# save (OLE::Storage_Lite::PPS::Root)
#------------------------------------------------------------------------------
function save($sFile, $bNoAs=false, $rhInfo=false) {
#0.Initial Setting for saving
/*
  if (!$rhInfo) {
    $rhInfo=new object();
  }
*/

  $rhInfo->_BIG_BLOCK_SIZE=pow(2, (($rhInfo->_BIG_BLOCK_SIZE) ?
                                  _adjust2($rhInfo->_BIG_BLOCK_SIZE) : 9));
  $rhInfo->_SMALL_BLOCK_SIZE=pow(2, (($rhInfo->_SMALL_BLOCK_SIZE) ?
                                    _adjust2($rhInfo->_SMALL_BLOCK_SIZE) : 6));
  $rhInfo->_SMALL_SIZE = 0x1000;
  $rhInfo->_PPS_SIZE = 0x80;

#1.Open File
#1.1 $sFile is Ref of scalar
  if(is_resource($sFile)) {
    $oIo=$sFile;
    $rhInfo->_FILEH_ = $oIo;
  }
#1.2 $sFile is a simple filename string
  else {
    $oIo=fopen("$sFile", "wb");
    $rhInfo->_FILEH_ = $oIo;
  }

  $iBlk = 0;
#1. Make an array of PPS (for Save)
  $aList=array();
  $list=array(&$this);
  if($bNoAs) {
    $this->_savePpsSetPnt2($list, $aList, $rhInfo);
  } else {
    $this->_savePpsSetPnt($list, $aList, $rhInfo);
  }
  list($iSBDcnt, $iBBcnt, $iPPScnt) = $this->_calcSize($aList, $rhInfo);
#2.Save Header
  $this->_saveHeader($rhInfo, $iSBDcnt, $iBBcnt, $iPPScnt);

#3.Make Small Data string (write SBD)
  $sSmWk = $this->_makeSmallData($aList, $rhInfo);
  $this->Data = $sSmWk;  #Small Datas become RootEntry Data

#4. Write BB
  $iBBlk = $iSBDcnt;
  $this->_saveBigData($iBBlk, $aList, $rhInfo);
#5. Write PPS
  $this->_savePps($aList, $rhInfo);
#6. Write BD and BDList and Adding Header informations
  $this->_saveBbd($iSBDcnt, $iBBcnt, $iPPScnt,  $rhInfo); 
#7.Close File
  fclose($rhInfo->_FILEH_);
}

#------------------------------------------------------------------------------
# _calcSize (OLE::Storage_Lite::PPS)
#------------------------------------------------------------------------------
function _calcSize(&$raList, $rhInfo) {

#0. Calculate Basic Setting
  $iSBDcnt=0;
  $iBBcnt=0;
  $iPPScnt = 0;
  $iSmallLen = 0;
  $iSBcnt = 0;

  for ($c=0;$c<sizeof($raList);$c++) {
      $oPps=&$raList[$c];

      if($oPps->Type==PpsType_File) {
        $oPps->Size = $oPps->_DataLen();  #Mod
        if($oPps->Size < $rhInfo->_SMALL_SIZE) {
          $iSBcnt += floor($oPps->Size / $rhInfo->_SMALL_BLOCK_SIZE) +
                     (($oPps->Size % $rhInfo->_SMALL_BLOCK_SIZE) ? 1 : 0);
        } else {
          $iBBcnt += 
            (floor($oPps->Size/ $rhInfo->_BIG_BLOCK_SIZE) +
                (($oPps->Size % $rhInfo->_BIG_BLOCK_SIZE)? 1: 0));
        }
      }
  }
  $iSmallLen = $iSBcnt * $rhInfo->_SMALL_BLOCK_SIZE;
  $iSlCnt = floor($rhInfo->_BIG_BLOCK_SIZE / LongIntSize);
  $iSBDcnt = floor($iSBcnt / $iSlCnt)+ (($iSBcnt % $iSlCnt) ? 1 : 0);
  $iBBcnt +=  (floor($iSmallLen/ $rhInfo->_BIG_BLOCK_SIZE) +
                (( $iSmallLen% $rhInfo->_BIG_BLOCK_SIZE) ? 1 : 0));
  $iCnt = sizeof($raList);
  $iBdCnt = $rhInfo->_BIG_BLOCK_SIZE/PpsSize;
  $iPPScnt = (floor($iCnt/$iBdCnt) + (($iCnt % $iBdCnt) ? 1 : 0));

  return array($iSBDcnt, $iBBcnt, $iPPScnt);
}

#------------------------------------------------------------------------------
# _adjust2 (OLE::Storage_Lite::PPS::Root)
#------------------------------------------------------------------------------
function _adjust2($i2) {
  $iWk = log($i2)/log(2);
  return ($iWk > int($iWk)) ? floor($iWk)+1 : $iWk;
}

#------------------------------------------------------------------------------
# _saveHeader (OLE::Storage_Lite::PPS::Root)
#------------------------------------------------------------------------------
function _saveHeader($rhInfo, $iSBDcnt, $iBBcnt, $iPPScnt) {
  $FILE = $rhInfo->_FILEH_;

#0. Calculate Basic Setting
  $iBlCnt = $rhInfo->_BIG_BLOCK_SIZE / LongIntSize;
  $i1stBdL = ($rhInfo->_BIG_BLOCK_SIZE - 0x4C) / LongIntSize;

  $iBdExL = 0;
  $iAll = $iBBcnt + $iPPScnt + $iSBDcnt;
  $iAllW = $iAll;
  $iBdCntW = floor($iAllW / $iBlCnt) + (($iAllW % $iBlCnt) ? 1 : 0);
  $iBdCnt = floor(($iAll + $iBdCntW) / $iBlCnt) + ((($iAllW+$iBdCntW) % $iBlCnt) ? 1 : 0);
  //my $i;

#0.1 Calculate BD count
  if ($iBdCnt > $i1stBdL) {
// TODO: is do-while correct here?
   do {
      $iBdExL++;
      $iAllW++;
      $iBdCntW = floor($iAllW / $iBlCnt) + (($iAllW % $iBlCnt) ? 1 : 0);
      $iBdCnt = floor(($iAllW + $iBdCntW) / $iBlCnt) + ((($iAllW+$iBdCntW) % $iBlCnt) ? 1 : 0);
    } while($iBdCnt > ($iBdExL*$iBlCnt+ $i1stBdL));
  }

#1.Save Header
  fputs($FILE,
            "\xD0\xCF\x11\xE0\xA1\xB1\x1A\xE1".
            "\x00\x00\x00\x00".
            "\x00\x00\x00\x00".
            "\x00\x00\x00\x00".
            "\x00\x00\x00\x00".
            pack("v", 0x3b).
            pack("v", 0x03).
            pack("v", -2).
            pack("v", 9).
            pack("v", 6).
            pack("v", 0).
            "\x00\x00\x00\x00".
            "\x00\x00\x00\x00".
            pack("V", $iBdCnt).
            pack("V", $iBBcnt+$iSBDcnt). #ROOT START
            pack("V", 0).
            pack("V", 0x1000).
            pack("V", 0).                  #Small Block Depot
            pack("V", 1)
    );
#2. Extra BDList Start, Count
  if($iBdCnt < $i1stBdL) {
    fputs($FILE, 
                pack("V", -2).     #Extra BDList Start
                pack("V", 0)       #Extra BDList Count
        );
  } else {
    fputs($FILE,
            pack("V", $iAll+$iBdCnt).
            pack("V", $iBdExL)
        );
  }

#3. BDList
    for ($i=0;($i<$i1stBdL) && ($i < $iBdCnt); $i++) {
        fputs($FILE, pack("V", $iAll+$i));
    }
    if ($i<$i1stBdL) {
// TODO: Check, if str_repeat is binary safe
        fputs($FILE, str_repeat((pack("V", -1)), ($i1stBdL-$i)));
    }
}

#------------------------------------------------------------------------------
# _saveBigData (OLE::Storage_Lite::PPS)
#------------------------------------------------------------------------------
function _saveBigData(&$iStBlk, &$raList, $rhInfo) {

//return;//!!!

  $iRes = 0;
  $FILE = $rhInfo->_FILEH_;

#1.Write Big (ge 0x1000) Data into Block
  for ($c=0;$c<sizeof($raList);$c++) {
    $oPps=&$raList[$c];
    if($oPps->Type!=PpsType_Dir) {
#print "PPS: $oPps DEF:", defined($oPps->{Data}), "\n";
        $oPps->Size = $oPps->_DataLen();  #Mod
        if(($oPps->Size >= $rhInfo->_SMALL_SIZE) ||
            (($oPps->Type == PpsType_Root) && $oPps->Data!==false)) {
            #1.1 Write Data
            #Check for update
            if($oPps->_PPS_FILE) {
                //my $sBuff;
                $iLen = 0;
                fseek($oPps->_PPS_FILE, 0, SEEK_SET); #To The Top
                while ($sBuff=fread($oPps->_PPS_FILE, 4096)) {
                    $iLen += length($sBuff);
                    fputs($FILE, $sBuff);           #Check for update
                }
            } else {
                fputs($FILE, $oPps->Data);
            }
            if ($oPps->Size % $rhInfo->_BIG_BLOCK_SIZE) {
// TODO: Check, if str_repeat() is binary safe
              fputs($FILE, str_repeat("\x00", 
                                      ($rhInfo->_BIG_BLOCK_SIZE - 
                                       ($oPps->Size % $rhInfo->_BIG_BLOCK_SIZE)))
                    );
            }
            #1.2 Set For PPS
            $oPps->StartBlock = $iStBlk;
            $iStBlk += 
                    (floor($oPps->Size/ $rhInfo->_BIG_BLOCK_SIZE) +
                        (($oPps->Size % $rhInfo->_BIG_BLOCK_SIZE) ? 1 : 0));
        }
    }
  }
}

#------------------------------------------------------------------------------
# _savePps (OLE::Storage_Lite::PPS::Root)
#------------------------------------------------------------------------------
function _savePps(&$raList, $rhInfo) 
{
#0. Initial
  $FILE = $rhInfo->_FILEH_;
#2. Save PPS
  for ($c=0;$c<sizeof($raList);$c++) {
    $oItem=&$raList[$c];
    $oItem->_savePpsWk($rhInfo);
  }
#3. Adjust for Block
  $iCnt = sizeof($raList);
  $iBCnt = $rhInfo->_BIG_BLOCK_SIZE / $rhInfo->_PPS_SIZE;
  if($iCnt % $iBCnt) {
    fputs($FILE, str_repeat("\x00", (($iBCnt - ($iCnt % $iBCnt)) * $rhInfo->_PPS_SIZE)));
  }
  return (floor($iCnt / $iBCnt) + (($iCnt % $iBCnt) ? 1 : 0));
}

#------------------------------------------------------------------------------
# _savePpsSetPnt2 (OLE::Storage_Lite::PPS::Root)
#  For Test
#------------------------------------------------------------------------------
function _savePpsSetPnt2(&$aThis, &$raList, $rhInfo) {
#1. make Array as Children-Relations
#1.1 if No Children
  if (!is_array($aThis) || sizeof($aThis)==0) {
      return 0xFFFFFFFF;
  } elseif (sizeof($aThis)==1) {
#1.2 Just Only one
      array_push($raList, &$aThis[0]);
      $aThis[0]->No = sizeof($raList)-1;
      $aThis[0]->PrevPps = 0xFFFFFFFF;
      $aThis[0]->NextPps = 0xFFFFFFFF;
      $aThis[0]->DirPps = $this->_savePpsSetPnt2($aThis[0]->Child, $raList, $rhInfo);
      return $aThis[0]->No;
  } else {
#1.3 Array
      $iCnt = sizeof($aThis);
#1.3.1 Define Center
      $iPos = 0; #int($iCnt/ 2);     #$iCnt 

      $aWk = $aThis;
      $aPrev = (sizeof($aThis) > 2) ? array_splice($aWk, 1, 1) : array(); #$iPos);
      $aNext = array_splice($aWk, 1); #, $iCnt - $iPos -1);
      $aThis[$iPos]->PrevPps = $this->_savePpsSetPnt2($aPrev, $raList, $rhInfo);
      array_push($raList, $aThis[$iPos]);
      $aThis[$iPos]->No = sizeof($raList)-1;

#1.3.2 Devide a array into Previous,Next
      $aThis[$iPos]->NextPps = $this->_savePpsSetPnt2($aNext, $raList, $rhInfo);
      $aThis[$iPos]->DirPps = $this->_savePpsSetPnt2($aThis[$iPos]->Child, $raList, $rhInfo);
      return $aThis[$iPos]->No;
  }
}

#------------------------------------------------------------------------------
# _savePpsSetPnt2 (OLE::Storage_Lite::PPS::Root)
#  For Test
#------------------------------------------------------------------------------
function _savePpsSetPnt2s(&$aThis, &$raList, $rhInfo) {
#1. make Array as Children-Relations
#1.1 if No Children
  if (!is_array($aThis) || sizeof($aThis)==0) {
      return 0xFFFFFFFF;
  } elseif (sizeof($aThis)==1) {
#1.2 Just Only one
      array_push($raList, &$aThis[0]);
      $aThis[0]->No = sizeof($raList)-1;
      $aThis[0]->PrevPps = 0xFFFFFFFF;
      $aThis[0]->NextPps = 0xFFFFFFFF;
      $aThis[0]->DirPps = $this->_savePpsSetPnt2($aThis[0]->Child, $raList, $rhInfo);
      return $aThis[0]->No;
  } else {
#1.3 Array
      $iCnt = sizeof($aThis);
#1.3.1 Define Center
      $iPos = 0; #int($iCnt/ 2);     #$iCnt 
      array_push($raList, $aThis[$iPos]);
      $aThis[$iPos]->No = sizeof($raList)-1;
      $aWk = $aThis;
#1.3.2 Devide a array into Previous,Next
      $aPrev = array_splice($aWk, 0, $iPos);
      $aNext = array_splice($aWk, 1, $iCnt - $iPos - 1);
      $aThis[$iPos]->PrevPps = $this->_savePpsSetPnt2($aPrev, $raList, $rhInfo);
      $aThis[$iPos]->NextPps = $this->_savePpsSetPnt2($aNext, $raList, $rhInfo);
      $aThis[$iPos]->DirPps = $this->_savePpsSetPnt2($aThis[$iPos]->Child, $raList, $rhInfo);
      return $aThis[$iPos]->No;
  }
}

#------------------------------------------------------------------------------
# _savePpsSetPnt (OLE::Storage_Lite::PPS::Root)
#------------------------------------------------------------------------------
function _savePpsSetPnt(&$aThis, &$raList, $rhInfo) {

//print "yyy type: ".gettype($aThis)."<br>\n";
//print "yyy name: ".$aThis[0]->Name."<br>\n";

#1. make Array as Children-Relations
#1.1 if No Children
  if (!is_array($aThis) || sizeof($aThis)==0) {
      return 0xFFFFFFFF;
  } elseif (sizeof($aThis)==1) {
#1.2 Just Only one
      array_push($raList, &$aThis[0]);
      $aThis[0]->No = sizeof($raList)-1;
      $aThis[0]->PrevPps = 0xFFFFFFFF;
      $aThis[0]->NextPps = 0xFFFFFFFF;
      $aThis[0]->DirPps = $this->_savePpsSetPnt($aThis[0]->Child, $raList, $rhInfo);
      return $aThis[0]->No;
  } else {
#1.3 Array
      $iCnt = sizeof($aThis);
#1.3.1 Define Center
      $iPos = floor($iCnt/2);     #$iCnt 
      array_push($raList, $aThis[$iPos]);
      $aThis[$iPos]->No = sizeof($raList)-1;
      $aWk = $aThis;
#1.3.2 Devide a array into Previous,Next
      $aPrev = splice($aWk, 0, $iPos);
      $aNext = splice($aWk, 1, $iCnt - $iPos - 1);
      $aThis[$iPos]->PrevPps = $this->_savePpsSetPnt($aPrev, $raList, $rhInfo);
      $aThis[$iPos]->NextPps = $this->_savePpsSetPnt($aNext, $raList, $rhInfo);
      $aThis[$iPos]->DirPps = $this->_savePpsSetPnt($aThis[$iPos]->Child, $raList, $rhInfo);
      return $aThis[$iPos]->No;
  }
}

#------------------------------------------------------------------------------
# _savePpsSetPnt (OLE::Storage_Lite::PPS::Root)
#------------------------------------------------------------------------------
function _savePpsSetPnt1(&$aThis, &$raList, $rhInfo) {
#1. make Array as Children-Relations
#1.1 if No Children
  if (!is_array($aThis) || sizeof($aThis)==0) {
      return 0xFFFFFFFF;
  } elseif (sizeof($aThis)==1) {
#1.2 Just Only one
      array_push($raList, &$aThis[0]);
      $aThis[0]->No = sizeof($raList)-1;
      $aThis[0]->PrevPps = 0xFFFFFFFF;
      $aThis[0]->NextPps = 0xFFFFFFFF;
      $aThis[0]->DirPps = $this->_savePpsSetPnt($aThis[0]->Child, $raList, $rhInfo);
      return $aThis[0]->No;
  } else {
#1.3 Array
      $iCnt = sizeof($aThis);
#1.3.1 Define Center
      $iPos = floor($iCnt / 2);     #$iCnt 
      array_push($raList, $aThis[$iPos]);
      $aThis[$iPos]->No = sizeof($raList)-1;
      $aWk = $aThis;
#1.3.2 Devide a array into Previous,Next
      $aPrev = splice($aWk, 0, $iPos);
      $aNext = splice($aWk, 1, $iCnt - $iPos - 1);
      $aThis[$iPos]->PrevPps = $this->_savePpsSetPnt($aPrev, $raList, $rhInfo);
      $aThis[$iPos]->NextPps = $this->_savePpsSetPnt($aNext, $raList, $rhInfo);
      $aThis[$iPos]->DirPps = $this->_savePpsSetPnt($aThis[$iPos]->Child, $raList, $rhInfo);
      return $aThis[$iPos]->No;
  }
}

#------------------------------------------------------------------------------
# _saveBbd (OLE::Storage_Lite)
#------------------------------------------------------------------------------
function _saveBbd($iSbdSize, $iBsize, $iPpsCnt, $rhInfo) {
  $FILE = $rhInfo->_FILEH_;
#0. Calculate Basic Setting
  $iBbCnt = $rhInfo->_BIG_BLOCK_SIZE / LongIntSize;
  $i1stBdL = ($rhInfo->_BIG_BLOCK_SIZE - 0x4C) / LongIntSize;

  $iBdExL = 0;
  $iAll = $iBsize + $iPpsCnt + $iSbdSize;
  $iAllW = $iAll;
  $iBdCntW = floor($iAllW / $iBbCnt) + (($iAllW % $iBbCnt) ? 1 : 0);
  $iBdCnt = floor(($iAll + $iBdCntW) / $iBbCnt) + ((($iAllW+$iBdCntW) % $iBbCnt)? 1: 0);
  //my $i;
#0.1 Calculate BD count
  if ($iBdCnt >$i1stBdL) {
// TODO: do-while correct here?
    do {
      $iBdExL++;
      $iAllW++;
      $iBdCntW = floor($iAllW / $iBbCnt) + (($iAllW % $iBbCnt) ? 1 : 0);
      $iBdCnt = floor(($iAllW + $iBdCntW) / $iBbCnt) + ((($iAllW+$iBdCntW) % $iBbCnt) ? 1 : 0);
    } while ($iBdCnt > ($iBdExL*$iBbCnt+$i1stBdL));
  }

#1. Making BD
#1.1 Set for SBD
  if($iSbdSize > 0) {
    for ($i = 0; $i<($iSbdSize-1); $i++) {
      fputs($FILE, pack("V", $i+1));
    }
    fputs($FILE, pack("V", -2));
  }
#1.2 Set for B
  for ($i = 0; $i<($iBsize-1); $i++) {
      fputs($FILE, pack("V", $i+$iSbdSize+1));
  }
  fputs($FILE, pack("V", -2));

#1.3 Set for PPS
  for ($i = 0; $i<($iPpsCnt-1); $i++) {
      fputs($FILE, pack("V", $i+$iSbdSize+$iBsize+1));
  }
  fputs($FILE, pack("V", -2));
#1.4 Set for BBD itself ( 0xFFFFFFFD : BBD)
  for ($i=0; $i<$iBdCnt;$i++) {
    fputs($FILE, pack("V", 0xFFFFFFFD));
  }
#1.5 Set for ExtraBDList
  for ($i=0; $i<$iBdExL;$i++) {
    fputs($FILE, pack("V", 0xFFFFFFFC));
  }
#1.6 Adjust for Block
  if(($iAllW + $iBdCnt) % $iBbCnt) {
    fputs($FILE, str_repeat(pack("V", -1), ($iBbCnt - (($iAllW + $iBdCnt) % $iBbCnt))));
  }

#2.Extra BDList
  if($iBdCnt > $i1stBdL)  {
    $iN=0;
    $iNb=0;
    for ($i=$i1stBdL;$i<$iBdCnt; $i++, $iN++) {
      if($iN>=($iBbCnt-1)) {
          $iN = 0;
          $iNb++;
          fputs($FILE, pack("V", $iAll+$iBdCnt+$iNb));
      }
      fputs($FILE, pack("V", $iBsize+$iSbdSize+$iPpsCnt+$i));
    }
    if(($iBdCnt-$i1stBdL) % ($iBbCnt-1)) {
      fputs($FILE, str_repeat(pack("V", -1), (($iBbCnt-1) - (($iBdCnt-$i1stBdL) % ($iBbCnt-1)))));
    }
    fputs($FILE, pack("V", -2));
  }
}

}

?>
