<?php
/* Copyright (C) 2006-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
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
*/

/**
 *		\file       htdocs/includes/modules/export/export_csv.modules.php
 *		\ingroup    export
 *		\brief      File of class to build export files with format TSV
 *		\author	    Laurent Destailleur
 *		\version    $Id: export_tsv.modules.php,v 1.14 2011/08/03 01:38:52 eldy Exp $
*/

require_once(DOL_DOCUMENT_ROOT ."/includes/modules/export/modules_export.php");


/**
 *	    \class      ExportTsv
 *		\brief      Class to build export files with format TSV
 */
class ExportTsv extends ModeleExports
{
    var $id;
    var $label;
    var $extension;
    var $version;

    var $label_lib;
    var $version_lib;

    var $separator="\t";

    var $handle;    // Handle fichier


    /**
     *		\brief      Constructeur
     *		\param	    db      Database handler
     */
    function ExportTsv($db)
    {
        global $conf;
        $this->db = $db;

        $this->id='tsv';                // Same value then xxx in file name export_xxx.modules.php
        $this->label='Tsv';             // Label of driver
        $this->desc='<b>Tab Separated Value</b> file format (.tsv)<br>This is a text file format where fields are separated by separator [tab].';
        $this->extension='tsv';         // Extension for generated file by this driver
        $this->picto='mime/other';		// Picto
        $ver=explode(' ','$Revision: 1.14 $');
        $this->version=$ver[2];         // Driver version

        // If driver use an external library, put its name here
        $this->label_lib='Dolibarr';
        $this->version_lib=DOL_VERSION;
    }

    function getDriverId()
    {
        return $this->id;
    }

    function getDriverLabel()
    {
        return $this->label;
    }

    function getDriverDesc()
    {
        return $this->desc;
    }

    function getDriverExtension()
    {
        return $this->extension;
    }

    function getDriverVersion()
    {
        return $this->version;
    }

    function getLibLabel()
    {
        return $this->label_lib;
    }

    function getLibVersion()
    {
        return $this->version_lib;
    }


    /**
	*	\brief		Open output file
	*	\param		file		Path of filename
	*	\return		int			<0 if KO, >=0 if OK
	*/
	function open_file($file,$outputlangs)
    {
        global $langs;

        dol_syslog("ExportTsv::open_file file=".$file);

		$ret=1;

        $outputlangs->load("exports");
		$this->handle = fopen($file, "wt");
        if (! $this->handle)
		{
			$langs->load("errors");
			$this->error=$langs->trans("ErrorFailToCreateFile",$file);
			$ret=-1;
		}

		return $ret;
    }

	/**
	 * 	\brief		Output header into file
	 * 	\param		langs		Output language
	 */
    function write_header($outputlangs)
    {
        return 0;
    }


	/**
     *     Output title line into file
     *     @param      array_export_fields_label   Array with list of label of fields
     *     @param      array_selected_sorted       Array with list of field to export
     *     @param      outputlangs                 Object lang to translate values
	 */
    function write_title($array_export_fields_label,$array_selected_sorted,$outputlangs)
    {
        foreach($array_selected_sorted as $code => $value)
        {
            $newvalue=$outputlangs->transnoentities($array_export_fields_label[$code]);
			$newvalue=$this->tsv_clean($newvalue);

			fwrite($this->handle,$newvalue.$this->separator);
        }
        fwrite($this->handle,"\n");
        return 0;
    }


	/**
	 * 	   Output record line into file
	 *     @param      array_selected_sorted       Array with list of field to export
	 *     @param      objp                        A record from a fetch with all fields from select
	 *     @param      outputlangs                 Object lang to translate values
	 */
    function write_record($array_selected_sorted,$objp,$outputlangs)
    {
		$this->col=0;
 		foreach($array_selected_sorted as $code => $value)
        {
            $alias=str_replace(array('.','-'),'_',$code);
            if (empty($alias)) dol_print_error('','Bad value for field with code='.$code.'. Try to redefine export.');
            $newvalue=$objp->$alias;

            // Translation newvalue
			if (preg_match('/^\((.*)\)$/i',$newvalue,$reg))
			{
				$newvalue=$outputlangs->transnoentities($reg[1]);
			}

			$newvalue=$this->tsv_clean($newvalue);

			fwrite($this->handle,$newvalue.$this->separator);
            $this->col++;
		}
        fwrite($this->handle,"\n");
        return 0;
    }

	/**
	 * 	\brief		Output footer into file
	 * 	\param		outputlangs		Output language
	 */
    function write_footer($outputlangs)
    {
		return 0;
    }

	/**
	 * 	\brief		Close file handle
	 */
    function close_file()
    {
        fclose($this->handle);
        return 0;
    }

    /**
     * Clean a cell to respect rules of TSV file cells
     * @param 	newvalue	String to clean
     * @return 	string		Value cleaned
     */
    function tsv_clean($newvalue)
    {
		// Rule Dolibarr: No HTML
		$newvalue=dol_string_nohtmltag($newvalue);

		// Rule 1 TSV: No CR, LF in cells
    	$newvalue=str_replace("\r",'',$newvalue);
        $newvalue=str_replace("\n",'\n',$newvalue);

        // Rule 2 TSV: If value contains tab, we must replace by space
		if (preg_match('/'.$this->separator.'/',$newvalue))
		{
			$newvalue=str_replace("\t"," ",$newvalue);
		}

    	return $newvalue;
    }

}

?>
