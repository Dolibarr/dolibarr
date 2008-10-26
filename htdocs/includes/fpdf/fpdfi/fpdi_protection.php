<?php
/****************************************************************************
* Software: FPDI_Protection                                                 *
* Version:  1.0.2                                                             *
* Date:     2006/11/20                                                      *
* Author:   Klemen VODOPIVEC, Jan Slabon                                    *
* License:  Freeware                                                        *
*                                                                           *
* You may use and modify this software as you wish as stated in original    *
* FPDF package.                                                             *
*                                                                           *
* Infos (by Jan Slabon):                                                    *
* This class extends the FPDI-class available at http://www.setasign.de     *
* so that you can import pages and create new protected pdf files.          *
*                                                                           *
* This release is dedicated to my son Fin Frederik (*2005/06/04)            *
*                                                                           *
****************************************************************************/

require_once("fpdi.php");

class FPDI_Protection extends FPDI {
	
	var $encrypted;          //whether document is protected
    var $Uvalue;             //U entry in pdf document
    var $Ovalue;             //O entry in pdf document
    var $Pvalue;             //P entry in pdf document
    var $enc_obj_id;         //encryption object id
    var $last_rc4_key;       //last RC4 key encrypted (cached for optimisation)
    var $last_rc4_key_c;     //last RC4 computed key
    var $padding = '';
    
    function FPDI_Protection($orientation='P',$unit='mm',$format='A4')
    {
        parent::FPDI($orientation,$unit,$format);
        $this->_current_obj_id =& $this->current_obj_id; // for FPDI 1.1 compatibility
        
        $this->encrypted=false;
        $this->last_rc4_key = '';
        $this->padding = "\x28\xBF\x4E\x5E\x4E\x75\x8A\x41\x64\x00\x4E\x56\xFF\xFA\x01\x08".
                         "\x2E\x2E\x00\xB6\xD0\x68\x3E\x80\x2F\x0C\xA9\xFE\x64\x53\x69\x7A";
    }

    /**
    * Function to set permissions as well as user and owner passwords
    *
    * - permissions is an array with values taken from the following list:
    *   40bit:  copy, print, modify, annot-forms
    *   128bit: fill-in, screenreaders, assemble, degraded-print
    *   If a value is present it means that the permission is granted
    * - If a user password is set, user will be prompted before document is opened
    * - If an owner password is set, document can be opened in privilege mode with no
    *   restriction if that password is entered
    */
    function SetProtection($permissions=array(),$user_pass='',$owner_pass=null)
    {
        $options = array('print' => 4, 'modify' => 8, 'copy' => 16, 'annot-forms' => 32 );
        $protection = 192;
        foreach($permissions as $permission){
            if (!isset($options[$permission]))
                $this->Error('Incorrect permission: '.$permission);
            $protection += $options[$permission];
        }
        if ($owner_pass === null)
            $owner_pass = uniqid(rand());
        $this->encrypted = true;
        $this->_generateencryptionkey($user_pass, $owner_pass, $protection);
    }


    function _putstream($s)
    {
        if ($this->encrypted) {
            $s = $this->_RC4($this->_objectkey($this->_current_obj_id), $s);
        }
        parent::_putstream($s);
    }


    function _textstring($s)
    {
        if ($this->encrypted) {
            $s = $this->_RC4($this->_objectkey($this->_current_obj_id), $s);
        }
        return parent::_textstring($s);
    }


    /**
    * Compute key depending on object number where the encrypted data is stored
    */
    function _objectkey($n)
    {
        return substr($this->_md5_16($this->encryption_key.pack('VXxx',$n)),0,10);
    }


    /**
    * Escape special characters
    */
    function _escape($s)
    {
        return str_replace(
        	array('\\',')','(',"\r"),
        	array('\\\\','\\)','\\(','\\r'),$s);
    }

    function _putresources()
    {
        parent::_putresources();
        if ($this->encrypted) {
            $this->_newobj();
            $this->enc_obj_id = $this->_current_obj_id;
            $this->_out('<<');
            $this->_putencryption();
            $this->_out('>>');
        }
    }

    function _putencryption()
    {
        $this->_out('/Filter /Standard');
        $this->_out('/V 1');
        $this->_out('/R 2');
        $this->_out('/O ('.$this->_escape($this->Ovalue).')');
        $this->_out('/U ('.$this->_escape($this->Uvalue).')');
        $this->_out('/P '.$this->Pvalue);
    }


    function _puttrailer()
    {
        parent::_puttrailer();
        if ($this->encrypted) {
            $this->_out('/Encrypt '.$this->enc_obj_id.' 0 R');
            $id = isset($this->fileidentifier) ? $this->fileidentifier : '';
            $this->_out('/ID [<'.$id.'><'.$id.'>]');
        }
    }


    /**
    * RC4 is the standard encryption algorithm used in PDF format
    */
    function _RC4($key, $text)
    {
    	if ($this->last_rc4_key != $key) {
            $k = str_repeat($key, 256/strlen($key)+1);
            $rc4 = range(0,255);
            $j = 0;
            for ($i=0; $i<256; $i++){
                $t = $rc4[$i];
                $j = ($j + $t + ord($k{$i})) % 256;
                $rc4[$i] = $rc4[$j];
                $rc4[$j] = $t;
            }
            $this->last_rc4_key = $key;
            $this->last_rc4_key_c = $rc4;
        } else {
            $rc4 = $this->last_rc4_key_c;
        }

        $len = strlen($text);
        $a = 0;
        $b = 0;
        $out = '';
        for ($i=0; $i<$len; $i++){
            $a = ($a+1)%256;
            $t= $rc4[$a];
            $b = ($b+$t)%256;
            $rc4[$a] = $rc4[$b];
            $rc4[$b] = $t;
            $k = $rc4[($rc4[$a]+$rc4[$b])%256];
            $out.=chr(ord($text{$i}) ^ $k);
        }

        return $out;
    }
    

    /**
    * Get MD5 as binary string
    */
    function _md5_16($string)
    {
        return pack('H*',md5($string));
    }

    /**
    * Compute O value
    */
    function _Ovalue($user_pass, $owner_pass)
    {
        $tmp = $this->_md5_16($owner_pass);
        $owner_RC4_key = substr($tmp,0,5);
        return $this->_RC4($owner_RC4_key, $user_pass);
    }


    /**
    * Compute U value
    */
    function _Uvalue()
    {
        return $this->_RC4($this->encryption_key, $this->padding);
    }


    /**
    * Compute encryption key
    */
    function _generateencryptionkey($user_pass, $owner_pass, $protection)
    {
        // Pad passwords
        $user_pass = substr($user_pass.$this->padding,0,32);
        $owner_pass = substr($owner_pass.$this->padding,0,32);
        // Compute O value
        $this->Ovalue = $this->_Ovalue($user_pass,$owner_pass);
        // Compute encyption key
        $tmp = $this->_md5_16($user_pass.$this->Ovalue.chr($protection)."\xFF\xFF\xFF");
        $this->encryption_key = substr($tmp,0,5);
        // Compute U value
        $this->Uvalue = $this->_Uvalue();
        // Compute P value
        $this->Pvalue = -(($protection^255)+1);
    }

    
    function pdf_write_value(&$value) {
    	switch ($value[0]) {
    		case PDF_TYPE_STRING :
				if ($this->encrypted) {
                    $value[1] = $this->_RC4($this->_objectkey($this->_current_obj_id), $value[1]);
                 	$value[1] = $this->_escape($value[1]);
                } 
    			break;
    			
			case PDF_TYPE_STREAM :
				if ($this->encrypted) {
                    $value[2][1] = $this->_RC4($this->_objectkey($this->_current_obj_id), $value[2][1]);
                }
                break;
                
            case PDF_TYPE_HEX :
				
            	if ($this->encrypted) {
                	$value[1] = $this->hex2str($value[1]);
                	$value[1] = $this->_RC4($this->_objectkey($this->_current_obj_id), $value[1]);
                    
                	// remake hexstring of encrypted string
    				$value[1] = $this->str2hex($value[1]);
                }
                break;
    	}	
    	
    	parent::pdf_write_value($value);
    }
    
    
    function hex2str($hex) {
    	return pack("H*", str_replace(array("\r","\n"," "),"", $hex));
    }
    
    function str2hex($str) {
        return current(unpack("H*",$str));
    }
}

?>