<?php
/* Copyright (C) 2011 Regis Houssin  <regis@dolibarr.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 * or see http://www.gnu.org/
 */

/**
 *	\file       htdocs/core/modules/barcode/barcodecoder.modules.php
 *	\ingroup    barcode
 *	\brief      Fichier contenant la classe du modele de generation code barre Barcode Coder
 */

require_once(DOL_DOCUMENT_ROOT ."/core/modules/barcode/modules_barcode.php");

/**
 * 		\class      modBarcodeCoder
 *		\brief      Classe du modele de numerotation de generation code barre Barcode Coder
 */
class modBarcodeCoder extends ModeleBarCode
{
    var $version='dolibarr';		// 'development', 'experimental', 'dolibarr'
    var $error='';


    /**
     *  Return if a module can be used or not
     *
     *  @return		boolean     true if module can be used
     */
    function isEnabled()
    {
        return true;
    }


    /**
     *  Return description
     *
     *  @return     string      Texte descripif
     */
    function info()
    {
        global $langs;

        return 'Barcode Coder';
    }

    /**
     *  Test si les numeros deja en vigueur dans la base ne provoquent pas de
     *  de conflits qui empechera cette numerotation de fonctionner
     *
     *  @return     boolean     false si conflit, true si ok
     */
    function canBeActivated()
    {
        global $langs;

        return true;
    }


    /**
     *	Return true if encodinf is supported
     *
     *	@return		int		>0 if supported, 0 if not
     */
    function encodingIsSupported($encoding)
    {
        global $genbarcode_loc;

        $supported=0;
        if ($encoding == 'EAN13') $supported=1;
        if ($encoding == 'ISBN')  $supported=1;
        if ($encoding == 'EAN8')  $supported=1;
        if ($encoding == 'UPC')   $supported=1;
        if ($encoding == 'C39')   $supported=1;
        if ($encoding == 'C128')  $supported=1;

        return $supported;
    }

    /**
     *	Return an image file on the fly (no need to write on disk)
     *
     *	@param   	$code			Value to encode
     *	@param   	$encoding		Mode of encoding
     *	@param   	$readable		Code can be read
     */
    function buildBarCode($code,$encoding,$readable='Y')
    {
        global $_GET,$_SERVER;
        global $conf;
        global $genbarcode_loc, $bar_color, $bg_color, $text_color, $font_loc;

        if (! $this->encodingIsSupported($encoding)) return -1;

        if ($encoding == 'EAN8' || $encoding == 'EAN13') $encoding = 'EAN';
        if ($encoding == 'C39' || $encoding == 'C128')   $encoding = substr($encoding,1);

        $scale=1; $mode='png';

        $_GET["code"]=$code;
        $_GET["encoding"]=$encoding;
        $_GET["scale"]=$scale;
        $_GET["mode"]=$mode;

        $font     = DOL_DOCUMENT_ROOT.'/includes/barcode/barcode-coder/NOTTB___.TTF';

        $fontSize = 10;   // GD1 in px ; GD2 in point
        $marge    = 10;   // between barcode and hri in pixel
        $x        = 125;  // barcode center
        $y        = 125;  // barcode center
        $height   = 50;   // barcode height in 1D ; module size in 2D
        $width    = 2;    // barcode height in 1D ; not use in 2D
        $angle    = 90;   // rotation in degrees : nb : non horizontable barcode might not be usable because of pixelisation
        $type     = 'ean13';

        $im     = imagecreatetruecolor(300, 300);
        $black  = ImageColorAllocate($im,0x00,0x00,0x00);
        $white  = ImageColorAllocate($im,0xff,0xff,0xff);
        $red    = ImageColorAllocate($im,0xff,0x00,0x00);
        $blue   = ImageColorAllocate($im,0x00,0x00,0xff);
        imagefilledrectangle($im, 0, 0, 300, 300, $white);

        require_once(DOL_DOCUMENT_ROOT.'/includes/barcode/barcode-coder/php-barcode-latest.php');
        dol_syslog("modBarcodeCoder::buildBarCode $code,$encoding,$scale,$mode");


        if ($code) $data = Barcode::gd($im, $black, $x, $y, $angle, $type, array('code'=>$code), $width, $height);
        if ( isset($font) ){
            $box = imagettfbbox($fontSize, 0, $font, $data['hri']);
            $len = $box[2] - $box[0];
            Barcode::rotate(-$len / 2, ($data['height'] / 2) + $fontSize + $marge, $angle, $xt, $yt);
            imagettftext($im, $fontSize, $angle, $x + $xt, $y + $yt, $blue, $font, $data['hri']);
        }


        $rot = imagerotate($im, 45, $white);
        //imagedestroy($im);
        $im     = imagecreatetruecolor(900, 300);
        $black  = ImageColorAllocate($im,0x00,0x00,0x00);
        $white  = ImageColorAllocate($im,0xff,0xff,0xff);
        $red    = ImageColorAllocate($im,0xff,0x00,0x00);
        $blue   = ImageColorAllocate($im,0x00,0x00,0xff);
        imagefilledrectangle($im, 0, 0, 900, 300, $white);

        // Barcode rotation : 90°
        $angle = 90;
        $data = Barcode::gd($im, $black, $x, $y, $angle, $type, array('code'=>$code), $width, $height);
        Barcode::rotate(-$len / 2, ($data['height'] / 2) + $fontSize + $marge, $angle, $xt, $yt);
        imagettftext($im, $fontSize, $angle, $x + $xt, $y + $yt, $blue, $font, $data['hri']);
        imagettftext($im, 10, 0, 60, 290, $black, $font, 'BARCODE ROTATION : 90°');

        // barcode rotation : 135
        $angle = 135;
        Barcode::gd($im, $black, $x+300, $y, $angle, $type, array('code'=>$code), $width, $height);
        Barcode::rotate(-$len / 2, ($data['height'] / 2) + $fontSize + $marge, $angle, $xt, $yt);
        imagettftext($im, $fontSize, $angle, $x + 300 + $xt, $y + $yt, $blue, $font, $data['hri']);
        imagettftext($im, 10, 0, 360, 290, $black, $font, 'BARCODE ROTATION : 135°');

        // last one : image rotation
        imagecopy($im, $rot, 580, -50, 0, 0, 300, 300);
        imagerectangle($im, 0, 0, 299, 299, $black);
        imagerectangle($im, 299, 0, 599, 299, $black);
        imagerectangle($im, 599, 0, 899, 299, $black);
        imagettftext($im, 10, 0, 690, 290, $black, $font, 'IMAGE ROTATION');

        /*
         if (! is_array($result))
         {
         $this->error=$result;
         print $this->error;exit;
         return -1;
         }
         */

        imagepng($im);
        imagedestroy($im);

        return 1;
    }

    /**
     *	Save an image file on disk (with no output)
     *
     *	@param   	$code			Value to encode
     *	@param   	$encoding		Mode of encoding
     *	@param   	$readable		Code can be read
     */
    function writeBarCode($code,$encoding,$readable='Y')
    {
        global $conf,$filebarcode;

        create_exdir($conf->barcode->dir_temp);

        $file=$conf->barcode->dir_temp.'/barcode_'.$code.'_'.$encoding.'.png';

        $filebarcode=$file;	// global var to be used in barcode_outimage called by barcode_print in buildBarCode

        $result=$this->buildBarCode($code,$encoding,$readable);

        return $result;
    }

}

?>
