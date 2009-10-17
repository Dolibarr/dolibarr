<?php
/* Copyright (C) 2006-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
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
* along with this program; if not, write to the Free Software
* Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
* or see http://www.gnu.org/
*/

/**
 *		\file       htdocs/includes/modules/import/import_csv.modules.php
 *		\ingroup    import
 *		\brief      File to load import files with CSV format
 *		\author	    Laurent Destailleur
 *		\version    $Id$
 */

require_once(DOL_DOCUMENT_ROOT ."/includes/modules/import/modules_import.php");


/**
 *	    \class      ImportCsv
 *		\brief      Classe permettant de lire les fichiers imports CSV
 */
class ImportCsv extends ModeleImports
{
    var $id;
    var $error;
    var $errors=array();

    var $label;
    var $extension;
    var $version;

    var $label_lib;
    var $version_lib;

    var $separator;

    var $handle;    // Handle fichier


    /**
     *		\brief      Constructeur
     *		\param	    db      Handler acces base de donnee
     */
    function ImportCsv($db)
    {
        global $conf;
        $this->db = $db;

        $this->separator=',';
        if (! empty($conf->global->EXPORT_CSV_SEPARATOR_TO_USE)) $this->separator=$conf->global->EXPORT_CSV_SEPARATOR_TO_USE;
        $this->enclosure='"';
        $this->escape='"';

        $this->id='csv';                // Same value then xxx in file name export_xxx.modules.php
        $this->label='Csv';             // Label of driver
        $this->desc='<b>Comma Separated Value</b> file format (.csv).<br>This is a text file format where fields are separated by separator [ '.$this->separator.' ]. If separator is found inside a field content, field is rounded by round character [ '.$this->enclosure.' ]. Escape character to escape round character is [ '.$this->escape.' ].';
        $this->extension='csv';         // Extension for generated file by this driver
        $this->picto='mime/other';		// Picto
        $ver=split(' ','$Revision$');
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
	 * 	\brief		Output header of an example file for this format
	 * 	\param		langs		Output language
	 */
    function write_header_example($outputlangs)
    {
		return '';
    }

    /**
	 * 	\brief		Output title line of an example file for this format
	 * 	\param		langs		Output language
	 */
    function write_title_example($outputlangs,$headerlinefields)
    {
    	$s='';
		$s.=join($this->separator,$headerlinefields);
    	return $s."\n";
    }

    /**
	 * 	\brief		Output record of an example file for this format
	 * 	\param		langs		Output language
	 */
    function write_record_example($outputlangs,$contentlinevalues)
    {
    	$s='';
		$s.=join($this->separator,$contentlinevalues);
    	return $s."\n";
    }

	/**
	 * 	\brief		Output footer of an example file for this format
	 * 	\param		langs		Output language
	 */
    function write_footer_example($outputlangs)
    {
		return '';
    }



    /**
 	 *	\brief		Open input file
	 *	\param		file		Path of filename
	 *	\return		int			<0 if KO, >=0 if OK
	 */
	function import_open_file($file)
    {
    	global $langs;
		$ret=1;

        dol_syslog("ImportCsv::open_file file=".$file);

		$newfile=utf8_check($file)?utf8_decode($file):$file;	// fopen need ISO file name
		$this->handle = fopen($newfile, "r");
        if (! $this->handle)
		{
			$langs->load("errors");
			$this->error=$langs->trans("ErrorFailToOpenFile",$file);
			$ret=-1;
		}
		else
		{
			$this->file=$file;
		}

		return $ret;
    }

	/**
	 * 	\brief		Input header line from file
	 */
    function import_read_header()
    {
		return 0;
    }


	/**
	 * 	\brief		Return array of next record in input file.
	 * 	\return		Array		Array of field values. Data are UTF8 encoded.
	 * 							[0]=>(['val']=>val,['type']=>-1=null,0=blank,1=string)
	 */
    function import_read_record()
    {
    	global $conf;

    	$arrayres=array();
    	if (version_compare(phpversion(), '5.3') < 0)
		{
	    	$arrayres=fgetcsv($this->handle,100000,$this->separator,$this->enclosure);
		}
		else
		{
	    	$arrayres=fgetcsv($this->handle,100000,$this->separator,$this->enclosure,$this->escape);
		}

		//var_dump($this->handle);
	    //var_dump($arrayres);exit;
		$newarrayres=array();
		if ($arrayres && is_array($arrayres))
        {
			foreach($arrayres as $key => $val)
			{
		    	if (! empty($conf->global->IMPORT_CSV_FORCE_CHARSET))	// Forced charset
		    	{
		    		if (strtolower($conf->global->IMPORT_CSV_FORCE_CHARSET) == 'utf8')
		    		{
		    			$newarrayres[$key]['val']=$val;
		    			$newarrayres[$key]['type']=1;
		    		}
		    		else
		    		{
		    			$newarrayres[$key]['val']=utf8_encode($val);
		    			$newarrayres[$key]['type']=1;
		    		}
		    	}
		    	else	// Autodetect format (UTF8 or ISO)
		    	{
					if (utf8_check($val))
					{
						$newarrayres[$key]['val']=$val;
						$newarrayres[$key]['type']=1;
					}
					else
					{
						$newarrayres[$key]['val']=utf8_encode($val);
						$newarrayres[$key]['type']=1;
					}
		    	}
			}

        	$this->col=sizeof($newarrayres);
        }

        return $newarrayres;
    }

	/**
	 * 	\brief		Close file handle
	 */
    function import_close_file()
    {
        fclose($this->handle);
        return 0;
    }


    /**
     * Insert a record into database
     * @param 	arrayrecord						Array of field values
     * @param	array_match_file_to_database
     * @param 	objimport
     * @param	maxfields						Max number of fiels to use
     * @return	int								<0 if KO, >0 if OK
     */
    function import_insert($arrayrecord,$array_match_file_to_database,$objimport,$maxfields,$importid)
    {
    	global $langs,$conf,$user;

    	$error=0;
    	$warning=0;
    	$this->errors=array();
    	$this->warnings=array();

    	//dol_syslog("import_csv.modules maxfields=".$maxfields." importid=".$importid);

		//var_dump($array_match_file_to_database);
		//var_dump($arrayrecord);
    	$sort_array_match_file_to_database=$array_match_file_to_database;
    	ksort($sort_array_match_file_to_database);
		//var_dump($sort_array_match_file_to_database);

		if (sizeof($arrayrecord) == 0 ||
			(sizeof($arrayrecord) == 1 && empty($arrayrecord[0]['val'])))
		{
			//print 'W';
			$this->warnings[$warning]['lib']=$langs->trans('EmptyLine');
			$this->warnings[$warning]['type']='EMPTY';
			$warning++;
		}
		else
		{
			// For each table to insert, me make a separate insert
			foreach($objimport->array_import_tables[0] as $alias => $tablename)
			{
				// Build sql request
				$sql='';
				$listfields='';
				$listvalues='';
				$i=0;
				$errorforthistable=0;
				// Loop on each fields in the match array
				foreach($sort_array_match_file_to_database as $key => $val)
				{
					if ($key <= $maxfields)
					{
						if ($listfields) { $listfields.=', '; $listvalues.=', '; }
						$listfields.=eregi_replace('^.*\.','',$val);
						$newval='';
						if ($arrayrecord[($key-1)]['type'] < 0)
						{
							$listvalues.="null";
						}
						else if ($arrayrecord[($key-1)]['type'] == 0)
						{
							$listvalues.="''";
						}
						else if ($arrayrecord[($key-1)]['type'] > 0)
						{
							$newval=$arrayrecord[($key-1)]['val'];
							$listvalues.="'".$arrayrecord[($key-1)]['val']."'";
						}

						// Make some tests

						// Required field is ok
						if (eregi('\*',$objimport->array_import_fields[0][$val]) && ($newval==''))
						{
							$this->errors[$error]['lib']=$langs->trans('ErrorMissingMandatoryValue',$key);
							$this->errors[$error]['type']='NOTNULL';
							$errorforthistable++;
							$error++;
						}
						// Test format only if field is not a missing mandatory field
						else {
							if (! empty($objimport->array_import_regex[0][$val]) && ! eregi($objimport->array_import_regex[0][$val],$newval))
							{
								$this->errors[$error]['lib']=$langs->trans('ErrorWrongValueForField',$key,$newval,$objimport->array_import_regex[0][$val]);
								$this->errors[$error]['type']='REGEX';
								$errorforthistable++;
								$error++;
							}

							// Other tests
							// ...
						}
					}
					$i++;
				}
				if (! $errorforthistable)
				{
					if ($listfields)
					{
						// Build SQL request
						$sql ='INSERT INTO '.$tablename.'('.$listfields.', import_key';
						if (! empty($objimport->array_import_tables_creator[0][$alias])) $sql.=', '.$objimport->array_import_tables_creator[0][$alias];
						$sql.=') VALUES('.$listvalues.", '".$importid."'";
						if (! empty($objimport->array_import_tables_creator[0][$alias])) $sql.=', '.$user->id;
						$sql.=')';
		    			dol_syslog("import_csv.modules sql=".$sql);

						//print '> '.join(',',$arrayrecord);
						//print 'sql='.$sql;
						//print '<br>'."\n";

						// Run insert request
						if ($sql)
						{
							$resql=$this->db->query($sql);
							if ($resql)
							{
								//print '.';
							}
							else
							{
								//print 'E';
								$this->errors[$error]['lib']=$this->db->lasterror();
								$this->errors[$error]['type']='SQL';
								$error++;
							}
						}
					}
					else
					{
						dol_print_error('Ne doit pas arriver AAA');
					}
				}
			}
		}

		return 1;
	}

}

?>
