<?php

define(PpsType_Root, 5);
define(PpsType_Dir, 1);
define(PpsType_File, 2);
define(DataSizeSmall, 0x1000);
define(LongIntSize, 4);
define(PpsSize, 0x80);

function Asc2Ucs($sAsc) {
    return implode("\x00", (preg_split('//', $sAsc, -1, PREG_SPLIT_NO_EMPTY)))."\x00";
}

function Ucs2Asc($sUcs) {
    $chars=explode("\x00", $sUcs);
    array_pop($chars);
    return implode("", $chars);
}

function OLEDate2Local($sDateTime) {
/* TODO!!!
  //my($iSec, $iMin, $iHour, $iDay, $iMon, $iYear);
  //my($iDate);
  //my($iDt, $iYDays);
#1.Divide Day and Time
  my $iBigDt = Math::BigInt->new(0);
  foreach my $sWk (reverse(split //, $sDateTime)) {
    $iBigDt *= 0x100;
    $iBigDt += ord($sWk);
  }
  my $iHSec = $iBigDt % 10000000;
  $iBigDt /= 10000000;
  my $iBigDay = int($iBigDt / (24*3600)) + 1;
  my $iTime = int($iBigDt % (24*3600));
#2. Year->Day(1601/1/2?)
  $iDt = $iBigDay;
  $iYear = 1601;
  $iYDays = _yearDays($iYear); #Not 365 (365 days is Only in Excel World)
  while($iDt > $iYDays) {
    $iDt -= $iYDays;
    $iYear++;
    $iYDays = _yearDays($iYear);
  }
  my $iMD;
  for($iMon=1;$iMon < 12; $iMon++){
    $iMD = _monthDays($iMon, $iYear);
    last if($iDt <= $iMD);
    $iDt -= $iMD;
  }
  $iDay = $iDt;
#3. Hour->iSec
  $iHour  = int($iTime / 3600);
  $iMin   = int(($iTime % 3600) / 60);
  $iSec   = $iTime % 60;
  return ($iSec, $iMin, $iHour, $iDay, $iMon - 1, $iYear-1900, $iHSec);
*/
}

#------------------------------------------------------------------------------
# Localtime->OLE Date
#------------------------------------------------------------------------------
function LocalDate2OLE($raDate) {
/* TODO!!!
  if (!$raDate) {
    return str_repeat("\x00", 8);
  }

  list($iSec, $iMin, $iHour, $iDay, $iMon, $iYear, $iHSec) = $raDate;
  $iSec ||=0; $iMin ||=0; $iHour ||=0;
  $iDay ||=0; $iMon ||=0; $iYear ||=0; $iHSec ||=0;

  //my($iDate);
  //my($iDt, $iYDays);
#1. Year -> Days
  $iDate = -1;
  for ($iY=1601;$iY<($iYear+1900);$iY++){
    $iDate += _yearDays($iY);
  }
  for ($iM=0;$iM < $iMon;$iM++){
    $iDate += _monthDays($iM+1, ($iYear+1900));
  }
  $iDate += $iDay;
#2. Hours->Sec + HighReso
  my $iBigDt = Math::BigInt->new(0);
  $iBigDt += $iHour*3600 + $iMin*60+ $iSec;
  $iBigDt += ($iDate*(24*3600));
  $iBigDt *= 10000000;
  $iBigDt += $iHSec if($iHSec);
#3. Make HEX string
  //my $iHex;
  $sRes = '';
  for($i=0;$i<8;$i++) {
    $iHex = $iBigDt % 0x100;
    $sRes .= pack('c', $iHex);
    $iBigDt /= 0x100;
  }
  return $sRes;
*/
}

function _leapYear($iYear) {
    return ((($iYear % 4)==0) && (($iYear % 100) || ($iYear % 400)==0)) ? 1 : 0;
}

function _yearDays($iYear) {
    return _leapYear($iYear) ? 366 : 365;
}

function _monthDays($iMon, $iYear) {
    if ($iMon == 1 || $iMon ==  3 || $iMon ==  5 || $iMon == 7 ||
        $iMon == 8 || $iMon == 10 || $iMon == 12) {
        return 31;
    } elseif ($iMon == 4 || $iMon == 6 || $iMon == 9 || $iMon == 11) {
        return 30;
    } elseif ($iMon == 2) {
        return _leapYear($iYear) ? 29 : 28;
    }
}

?>
