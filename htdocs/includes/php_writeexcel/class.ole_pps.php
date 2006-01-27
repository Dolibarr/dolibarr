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

class ole_pps {

    var $No;
    var $Name;
    var $Type;
    var $PrevPps;
    var $NextPps;
    var $DirPps;
    var $Time1st;
    var $Time2nd;
    var $StartBlock;
    var $Size;
    var $Data;
    var $Child;

#------------------------------------------------------------------------------
# new (OLE::Storage_Lite::PPS)
#------------------------------------------------------------------------------
// TODO: Do we need this function?
/*
function ole_pps($iNo, $sNm, $iType, $iPrev, $iNext, $iDir,
                 $raTime1st, $raTime2nd, $iStart, $iSize,
                 $sData=false, $raChild=false) {

#1. Constructor for General Usage

  if($iType == PpsType_File) { #FILE
    return OLE::Storage_Lite::PPS::File->_new
        ($iNo, $sNm, $iType, $iPrev, $iNext, $iDir, $raTime1st, $raTime2nd, 
         $iStart, $iSize, $sData, $raChild);
  }
  elsif($iType == PpsType_Dir) { #DIRECTRY
    return OLE::Storage_Lite::PPS::Dir->_new
        ($iNo, $sNm, $iType, $iPrev, $iNext, $iDir, $raTime1st, $raTime2nd, 
         $iStart, $iSize, $sData, $raChild);
  }
  elsif($iType == PpsType_Root) { #ROOT
    return OLE::Storage_Lite::PPS::Root->_new
        ($iNo, $sNm, $iType, $iPrev, $iNext, $iDir, $raTime1st, $raTime2nd, 
         $iStart, $iSize, $sData, $raChild);
  }
  else {
    die "Error PPS:$iType $sNm\n";
  }
}
*/

// We probably don't need a constructor at all because
// all derived classed do their own initialization stuff

    #------------------------------------------------------------------------------
    # _new (OLE::Storage_Lite::PPS)
    #   for OLE::Storage_Lite
    #------------------------------------------------------------------------------
    function ole_pps($iNo, $sNm, $iType, $iPrev, $iNext, $iDir,
                     $raTime1st, $raTime2nd, $iStart, $iSize,
                     $sData=false, $raChild=false) {

        #1. Constructor for OLE::Storage_Lite

        $this->No         = $iNo;
        $this->Name       = $sNm;
        $this->Type       = $iType;
        $this->PrevPps    = $iPrev;
        $this->NextPps    = $iNext;
        $this->DirPps     = $iDir;
        $this->Time1st    = $raTime1st;
        $this->Time2nd    = $raTime2nd;
        $this->StartBlock = $iStart;
        $this->Size       = $iSize;
        $this->Data       = $sData;
        $this->Child      = $raChild;
    }

    #------------------------------------------------------------------------------
    # _DataLen (OLE::Storage_Lite::PPS)
    # Check for update
    #------------------------------------------------------------------------------
    function _DataLen() {
        if ($this->Data===false) {
            return 0;
        }

        if ($this->_PPS_FILE) {
            return filesize($this->_PPS_FILE);
        }

        return strlen($this->Data);
    }

    #------------------------------------------------------------------------------
    # _makeSmallData (OLE::Storage_Lite::PPS)
    #------------------------------------------------------------------------------
    function _makeSmallData(&$aList, $rhInfo) {
        //my ($sRes);
        $FILE = $rhInfo->_FILEH_;
        $iSmBlk = 0;

        for ($c=0;$c<sizeof($aList);$c++) {
            $oPps=&$aList[$c];

            #1. Make SBD, small data string

            if ($oPps->Type==PpsType_File) {
                if ($oPps->Size<=0) {
                    continue;
                }

                if($oPps->Size < $rhInfo->_SMALL_SIZE) {
                    $iSmbCnt = floor($oPps->Size / $rhInfo->_SMALL_BLOCK_SIZE) +
                               (($oPps->Size % $rhInfo->_SMALL_BLOCK_SIZE) ? 1 : 0);
                    #1.1 Add to SBD
                    for ($i = 0; $i<($iSmbCnt-1); $i++) {
                        fputs($FILE, pack("V", $i+$iSmBlk+1));
                    }
                    fputs($FILE, pack("V", -2));

                    #1.2 Add to Data String(this will be written for RootEntry)
                    #Check for update
                    if ($oPps->_PPS_FILE) {
                        //my $sBuff;
                        fseek($oPps->_PPS_FILE, 0, SEEK_SET); #To The Top
                        while ($sBuff=fread($oPps->_PPS_FILE, 4096)) {
                            $sRes .= $sBuff;
                        }
                    } else {
                        $sRes .= $oPps->Data;
                    }
                    if($oPps->Size % $rhInfo->_SMALL_BLOCK_SIZE) {
                        $sRes .= (str_repeat("\x00",
                                  ($rhInfo->_SMALL_BLOCK_SIZE -
                                  ($oPps->Size % $rhInfo->_SMALL_BLOCK_SIZE))));
                    }
                    #1.3 Set for PPS
                    $oPps->StartBlock = $iSmBlk;
                    $iSmBlk += $iSmbCnt;
                }
            }

        }
        $iSbCnt = floor($rhInfo->_BIG_BLOCK_SIZE / LongIntSize);
        if ($iSmBlk  % $iSbCnt) {
            fputs($FILE, str_repeat(pack("V", -1), $iSbCnt - ($iSmBlk % $iSbCnt)));
        }
        #2. Write SBD with adjusting length for block
        return $sRes;
    }

    #------------------------------------------------------------------------------
    # _savePpsWk (OLE::Storage_Lite::PPS)
    #------------------------------------------------------------------------------
    function _savePpsWk($rhInfo) {
        #1. Write PPS
        $FILE=$rhInfo->_FILEH_;
        fputs($FILE,
              $this->Name.
              str_repeat("\x00", 64 - strlen($this->Name)).      #  64
              pack("v", strlen($this->Name) + 2).                #  66
              pack("c", $this->Type).                            #  67
              pack("c", 0x00). #UK                               #  68
              pack("V", $this->PrevPps). #Prev                   #  72
              pack("V", $this->NextPps). #Next                   #  76
              pack("V", $this->DirPps).  #Dir                    #  80
              "\x00\x09\x02\x00".                                #  84
              "\x00\x00\x00\x00".                                #  88
              "\xc0\x00\x00\x00".                                #  92
              "\x00\x00\x00\x46".                                #  96
              "\x00\x00\x00\x00".                                # 100
//TODO!!!              LocalDate2OLE($this->Time1st).                     # 108
"\x00\x00\x00\x00\x00\x00\x00\x00".
//TODO!!!              LocalDate2OLE($this->Time2nd).                     # 116
"\x00\x00\x00\x00\x00\x00\x00\x00".
              pack("V", ($this->StartBlock!==false) ?
                        $this->StartBlock : 0).                  # 120
              pack("V", ($this->Size!==false) ?
                        $this->Size : 0).                        # 124
              pack("V", 0)                                       # 128
        );
    }

}

?>
