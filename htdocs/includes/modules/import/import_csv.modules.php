<?php
/* Copyright (C) 2006-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2009-2010 Regis Houssin        <regis@dolibarr.fr>
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
 *		\file       htdocs/includes/modules/import/import_csv.modules.php
 *		\ingroup    import
 *		\brief      File to load import files with CSV format
 *		\author	    Laurent Destailleur
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

	var $cachefieldtable=array();   // Array to cache list of value into fields@tables


	/**
	 *		\brief      Constructeur
	 *		\param	    db      Handler acces base de donnee
	 */
	function ImportCsv($db)
	{
		global $conf,$langs;
		$this->db = $db;

		$this->separator=',';	// Change also function cleansep
		if (! empty($conf->global->IMPORT_CSV_SEPARATOR_TO_USE)) $this->separator=$conf->global->IMPORT_CSV_SEPARATOR_TO_USE;
		$this->enclosure='"';
		$this->escape='"';

		$this->id='csv';                // Same value then xxx in file name export_xxx.modules.php
		$this->label='Csv';             // Label of driver
		$this->desc=$langs->trans("CSVFormatDesc",$this->separator,$this->enclosure,$this->escape);
		$this->extension='csv';         // Extension for generated file by this driver
		$this->picto='mime/other';		// Picto
		$this->version='1.34';         // Driver version

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
	 * 	Output header of an example file for this format
	 * 	@param		outputlangs		Output language
	 */
	function write_header_example($outputlangs)
	{
		return '';
	}

	/**
	 * 	Output title line of an example file for this format
	 * 	@param		outputlangs		Output language
	 */
	function write_title_example($outputlangs,$headerlinefields)
	{
		$s.=join($this->separator,array_map('cleansep',$headerlinefields));
		return $s."\n";
	}

	/**
	 * 	Output record of an example file for this format
	 * 	@param		outputlangs		Output language
	 */
	function write_record_example($outputlangs,$contentlinevalues)
	{
		$s=join($this->separator,array_map('cleansep',$contentlinevalues));
		return $s."\n";
	}

	/**
	 * 	Output footer of an example file for this format
	 * 	@param		outputlangs		Output language
	 */
	function write_footer_example($outputlangs)
	{
		return '';
	}



	/**
	 *	Open input file
	 *	@param		file		Path of filename
	 *	@return		int			<0 if KO, >=0 if OK
	 */
	function import_open_file($file)
	{
		global $langs;
		$ret=1;

		dol_syslog("ImportCsv::open_file file=".$file);

		ini_set('auto_detect_line_endings',1);	// For MAC compatibility

		$this->handle = fopen(dol_osencode($file), "r");
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
	 * 							[0] => (['val']=>val, ['type']=>-1=null,0=blank,1=string)
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
						$newarrayres[$key]['type']=(dol_strlen($val)?1:-1);	// If empty we considere it's null
					}
					else
					{
						$newarrayres[$key]['val']=utf8_encode($val);
						$newarrayres[$key]['type']=(dol_strlen($val)?1:-1);	// If empty we considere it's null
					}
				}
				else	// Autodetect format (UTF8 or ISO)
				{
					if (utf8_check($val))
					{
						$newarrayres[$key]['val']=$val;
						$newarrayres[$key]['type']=(dol_strlen($val)?1:-1);	// If empty we considere it's null
					}
					else
					{
						$newarrayres[$key]['val']=utf8_encode($val);
						$newarrayres[$key]['type']=(dol_strlen($val)?1:-1);	// If empty we considere it's null
					}
				}
			}

			$this->col=count($newarrayres);
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
		$array_match_database_to_file=array_flip($array_match_file_to_database);
		$sort_array_match_file_to_database=$array_match_file_to_database;
		ksort($sort_array_match_file_to_database);

		//var_dump($sort_array_match_file_to_database);

		if (count($arrayrecord) == 0 ||
		(count($arrayrecord) == 1 && empty($arrayrecord[0]['val'])))
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
				// Loop on each fields in the match array ($key = 1..n, $val=alias of field)
				foreach($sort_array_match_file_to_database as $key => $val)
				{
					if ($key <= $maxfields)
					{
						if ($listfields) { $listfields.=', '; $listvalues.=', '; }
						$listfields.=preg_replace('/^.*\./i','',$val);
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
							$listvalues.="'".$this->db->escape($arrayrecord[($key-1)]['val'])."'";
						}

						// Make some tests

						// Required field is ok
						if (preg_match('/\*/',$objimport->array_import_fields[0][$val]) && ($newval==''))
						{
							$this->errors[$error]['lib']=$langs->trans('ErrorMissingMandatoryValue',$key);
							$this->errors[$error]['type']='NOTNULL';
							$errorforthistable++;
							$error++;
						}
						// Test format only if field is not a missing mandatory field
						else {
							if (! empty($objimport->array_import_regex[0][$val]))
							{
								// If test is "Must exist in a field@table"
								if (preg_match('/^(.*)@(.*)$/',$objimport->array_import_regex[0][$val],$reg))
								{
									$field=$reg[1];
									$table=$reg[2];

									if (! is_array($this->cachefieldtable[$field.'@'.$table])) // If content of field@table no already loaded into cache
									{
										$sql="SELECT ".$field." as aliasfield FROM ".$table;
										$resql=$this->db->query($sql);
										if ($resql)
										{
											$num=$this->db->num_rows($resql);
											$i=0;
											while ($i < $num)
											{
												$obj=$this->db->fetch_object($resql);
												if ($obj) $this->cachefieldtable[$field.'@'.$table][]=$obj->aliasfield;
												$i++;
											}
										}
										else
										{
											dol_print_error($this->db);
										}
									}

									// Now we check cache is not empty (should not) and key is into cache
									if (! is_array($this->cachefieldtable[$field.'@'.$table]) || ! in_array($newval,$this->cachefieldtable[$field.'@'.$table]))
									{
										$this->errors[$error]['lib']=$langs->trans('ErrorFieldValueNotIn',$key,$newval,$field,$table);
										$this->errors[$error]['type']='FOREIGNKEY';
										$error++;
									}
								}
								// If test is just a static regex
								else if (! preg_match('/'.$objimport->array_import_regex[0][$val].'/i',$newval))
								{
									$this->errors[$error]['lib']=$langs->trans('ErrorWrongValueForField',$key,$newval,$objimport->array_import_regex[0][$val]);
									$this->errors[$error]['type']='REGEX';
									$errorforthistable++;
									$error++;
								}
							}

							// Other tests
							// ...
						}
					}
					$i++;
				}
				//print $listvalues;

				if (! $errorforthistable)
				{
					if ($listfields)
					{
						// If some values need to be found somewhere else than in source file: Case we need a rowid found from a fetch on a reference.
						// This is used when insert must be done when a parent row already exists
						// $objimport->array_import_convertvalue=array('s.fk_soc'=>array('rule'=>'fetchfromref',file='/societe.class.php','class'=>'Societe','method'=>'fetch'));
						foreach($objimport->array_import_convertvalue as $alias => $rulearray)
						{
							if (empty($rulearray['rule']) || $rulearray['rule']!='fetchfromref') continue;
							dol_syslog("We need to get rowid from ref=".$alias." using value found in column ".$array_match_database_to_file." in source file, so ".$arrayrecord[$array_match_database_to_file]['val']);
						}

						// If some values need to be found somewhere else than in source file: Case we need lastinsert id from previous insert
						// This is used when insert must be done in several tables
						// $objimport->array_import_convertvalue=array('s.fk_soc'=>array('rule'=>'lastrowid',table='t');
						// TODO

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

/**
 *	Clean a string from separator
 */
function cleansep($value)
{
	return str_replace(',','/',$value);
};

?>
