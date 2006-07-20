<?php
//============================================================+
// File name   : i25aobject.php
// Begin       : 2002-07-31
// Last Update : 2004-12-29
// Author      : Karim Mribti [barcode@mribti.com]
//             : Nicola Asuni [info@tecnick.com]
// Version     : 0.0.8a  2001-04-01 (original code)
// License     : GNU LGPL (Lesser General Public License) 2.1
//               http://www.gnu.org/copyleft/lesser.txt
// Source Code : http://www.mribti.com/barcode/
//
// Description : I25 Barcode Render Class for PHP using
//               the GD graphics library.
//               Interleaved 2 of 5 is a numeric only bar code
//               with a optional check number.
//
// NOTE:
// This version contains changes by Nicola Asuni:
//  - porting to PHP4
//  - code style and formatting
//  - automatic php documentation in PhpDocumentor Style
//    (www.phpdoc.org)
//  - minor bug fixing
//============================================================+

/**
 * I25 Barcode Render Class for PHP using the GD graphics library.<br<
 * Interleaved 2 of 5 is a numeric only bar code with a optional check number.
 * @author Karim Mribti, Nicola Asuni
 * @name BarcodeObject
 * @package com.tecnick.tcpdf
 * @version 0.0.8a  2001-04-01 (original code)
 * @since 2001-03-25
 * @license http://www.gnu.org/copyleft/lesser.html LGPL
 */

/**
 * I25 Barcode Render Class for PHP using the GD graphics library.<br<
 * Interleaved 2 of 5 is a numeric only bar code with a optional check number.
 * @author Karim Mribti, Nicola Asuni
 * @name BarcodeObject
 * @package com.tecnick.tcpdf
 * @version 0.0.8a  2001-04-01 (original code)
 * @since 2001-03-25
 * @license http://www.gnu.org/copyleft/lesser.html LGPL
 */
class I25Object extends BarcodeObject {
	
	/**
	 * Class Constructor.
	 * @param int $Width Image width in pixels.
	 * @param int $Height Image height in pixels. 
	 * @param int $Style Barcode style.
	 * @param int $Value value to print on barcode.
	 */
	function I25Object($Width, $Height, $Style, $Value) {
		$this->BarcodeObject($Width, $Height, $Style);
		$this->mValue = $Value;
		$this->mCharSet = array (
		/* 0 */ "00110",
		/* 1 */ "10001",
		/* 2 */ "01001",
		/* 3 */ "11000",
		/* 4 */ "00101",
		/* 5 */ "10100",
		/* 6 */ "01100",
		/* 7 */ "00011",
		/* 8 */ "10010",
		/* 9 */ "01010"
		);
	}
	
	/**
	 * Returns barcode size.
	 * @param int $xres Horizontal resolution.
	 * @return barcode size.
	 * @access private
	 */
	function GetSize($xres) {
		$len = strlen($this->mValue);

		if ($len == 0)  {
			$this->mError = "Null value";
			return false;
		}

		for ($i=0;$i<$len;$i++) {
			if ((ord($this->mValue[$i])<48) || (ord($this->mValue[$i])>57)) {
				$this->mError = "I25 is numeric only";
				return false;
			}
		}

		if (($len%2) != 0) {
			$this->mError = "The length of barcode value must be even";
			return false;
		}
		$StartSize = BCD_I25_NARROW_BAR * 4  * $xres;
		$StopSize  = BCD_I25_WIDE_BAR * $xres + 2 * BCD_I25_NARROW_BAR * $xres;
		$cPos = 0;
		$sPos = 0;
		do {
			$c1    = $this->mValue[$cPos];
			$c2    = $this->mValue[$cPos+1];
			$cset1 = $this->mCharSet[$c1];
			$cset2 = $this->mCharSet[$c2];

			for ($i=0;$i<5;$i++) {
				$type1 = ($cset1[$i]==0) ? (BCD_I25_NARROW_BAR  * $xres) : (BCD_I25_WIDE_BAR * $xres);
				$type2 = ($cset2[$i]==0) ? (BCD_I25_NARROW_BAR  * $xres) : (BCD_I25_WIDE_BAR * $xres);
				$sPos += ($type1 + $type2);
			}
			$cPos+=2;
		} while ($cPos<$len);

		return $sPos + $StartSize + $StopSize;
	}

	/**
	 * Draws the start code.
	 * @param int $DrawPos Drawing position.
	 * @param int $yPos Vertical position.
	 * @param int $ySize Vertical size.
	 * @param int $xres Horizontal resolution.
	 * @return int drawing position.
	 * @access private
	 */
	function DrawStart($DrawPos, $yPos, $ySize, $xres) {
		/* Start code is "0000" */
		$this->DrawSingleBar($DrawPos, $yPos, BCD_I25_NARROW_BAR  * $xres , $ySize);
		$DrawPos += BCD_I25_NARROW_BAR  * $xres;
		$DrawPos += BCD_I25_NARROW_BAR  * $xres;
		$this->DrawSingleBar($DrawPos, $yPos, BCD_I25_NARROW_BAR  * $xres , $ySize);
		$DrawPos += BCD_I25_NARROW_BAR  * $xres;
		$DrawPos += BCD_I25_NARROW_BAR  * $xres;
		return $DrawPos;
	}
	
	/**
	 * Draws the stop code.
	 * @param int $DrawPos Drawing position.
	 * @param int $yPos Vertical position.
	 * @param int $ySize Vertical size.
	 * @param int $xres Horizontal resolution.
	 * @return int drawing position.
	 * @access private
	 */
	function DrawStop($DrawPos, $yPos, $ySize, $xres) {
		/* Stop code is "100" */
		$this->DrawSingleBar($DrawPos, $yPos, BCD_I25_WIDE_BAR * $xres , $ySize);
		$DrawPos += BCD_I25_WIDE_BAR  * $xres;
		$DrawPos += BCD_I25_NARROW_BAR  * $xres;
		$this->DrawSingleBar($DrawPos, $yPos, BCD_I25_NARROW_BAR  * $xres , $ySize);
		$DrawPos += BCD_I25_NARROW_BAR  * $xres;
		return $DrawPos;
	}

	/**
	 * Draws the barcode object.
	 * @param int $xres Horizontal resolution.
	 * @return bool true in case of success.
	 */
	function DrawObject($xres) {
		$len = strlen($this->mValue);

		if (($size = $this->GetSize($xres))==0) {
			return false;
		}

		$cPos  = 0;

		if ($this->mStyle & BCS_DRAW_TEXT) $ysize = $this->mHeight - BCD_DEFAULT_MAR_Y1 - BCD_DEFAULT_MAR_Y2 - $this->GetFontHeight($this->mFont);
		else $ysize = $this->mHeight - BCD_DEFAULT_MAR_Y1 - BCD_DEFAULT_MAR_Y2;

		if ($this->mStyle & BCS_ALIGN_CENTER) $sPos = (integer)(($this->mWidth - $size ) / 2);
		else if ($this->mStyle & BCS_ALIGN_RIGHT) $sPos = $this->mWidth - $size;
		else $sPos = 0;

		if ($this->mStyle & BCS_DRAW_TEXT) {
			if ($this->mStyle & BCS_STRETCH_TEXT) {
				/* Stretch */
				for ($i=0;$i<$len;$i++) {
					$this->DrawChar($this->mFont, $sPos+BCD_I25_NARROW_BAR*4*$xres+($size/$len)*$i,
					$ysize + BCD_DEFAULT_MAR_Y1 + BCD_DEFAULT_TEXT_OFFSET , $this->mValue[$i]);
				}
			}else {/* Center */
			$text_width = $this->GetFontWidth($this->mFont) * strlen($this->mValue);
			$this->DrawText($this->mFont, $sPos+(($size-$text_width)/2)+(BCD_I25_NARROW_BAR*4*$xres),
			$ysize + BCD_DEFAULT_MAR_Y1 + BCD_DEFAULT_TEXT_OFFSET, $this->mValue);
			}
		}

		$sPos = $this->DrawStart($sPos, BCD_DEFAULT_MAR_Y1, $ysize, $xres);
		do {
			$c1 = $this->mValue[$cPos];
			$c2 = $this->mValue[$cPos+1];
			$cset1 = $this->mCharSet[$c1];
			$cset2 = $this->mCharSet[$c2];

			for ($i=0;$i<5;$i++) {
				$type1 = ($cset1[$i]==0) ? (BCD_I25_NARROW_BAR * $xres) : (BCD_I25_WIDE_BAR * $xres);
				$type2 = ($cset2[$i]==0) ? (BCD_I25_NARROW_BAR * $xres) : (BCD_I25_WIDE_BAR * $xres);
				$this->DrawSingleBar($sPos, BCD_DEFAULT_MAR_Y1, $type1 , $ysize);
				$sPos += ($type1 + $type2);
			}
			$cPos+=2;
		} while ($cPos<$len);
		$sPos =  $this->DrawStop($sPos, BCD_DEFAULT_MAR_Y1, $ysize, $xres);
		return true;
	}
}

//============================================================+
// END OF FILE
//============================================================+
?>