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
 *		\file       htdocs/core/modules/import/import_csv.modules.php
 *		\ingroup    import
 *		\brief      File to load import files with CSV format
 */

require_once(DOL_DOCUMENT_ROOT ."/core/modules/import/modules_import.php");


/**
 *	Class to import CSV files
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

	var $cacheconvert=array();      // Array to cache list of value found after a convertion
	var $cachefieldtable=array();   // Array to cache list of value found into fields@tables


	/**
	 *	Constructor
	 *
	 *	@param	DoliDB		$db		Database handler
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
	 *
	 * 	@param	Translate	$outputlangs		Output language
	 *  @return	string
	 */
	function write_header_example($outputlangs)
	{
		return '';
	}

	/**
	 * 	Output title line of an example file for this format
	 *
	 * 	@param	Translate	$outputlangs		Output language
	 * 	@return	string
	 */
	function write_title_example($outputlangs,$headerlinefields)
	{
		$s.=join($this->separator,array_map('cleansep',$headerlinefields));
		return $s."\n";
	}

	/**
	 * 	Output record of an example file for this format
	 *
	 * 	@param	Translate	$outputlangs		Output language
	 * 	@param	array		$contentlinevalues	Array of lines
	 * 	@return	string
	 */
	function write_record_example($outputlangs,$contentlinevalues)
	{
		$s=join($this->separator,array_map('cleansep',$contentlinevalues));
		return $s."\n";
	}

	/**
	 * 	Output footer of an example file for this format
	 *
	 * 	@param	Translate	$outputlangs		Output language
	 *  @return	string
	 */
	function write_footer_example($outputlangs)
	{
		return '';
	}



	/**
	 *	Open input file
	 *
	 *	@param	string	$file		Path of filename
	 *	@return	int					<0 if KO, >=0 if OK
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
	 * 	Input header line from file
	 *
	 * 	@return		int		<0 if KO, >=0 if OK
	 */
	function import_read_header()
	{
		return 0;
	}


	/**
	 * 	Return array of next record in input file.
	 *
	 * 	@return		Array		Array of field values. Data are UTF8 encoded. [fieldpos] => (['val']=>val, ['type']=>-1=null,0=blank,1=string)
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
	 * 	Close file handle
	 *
	 *  @return	void
	 */
	function import_close_file()
	{
		fclose($this->handle);
		return 0;
	}


	/**
	 * Insert a record into database
	 *
	 * @param	array	$arrayrecord					Array of read values: [fieldpos] => (['val']=>val, ['type']=>-1=null,0=blank,1=string), [fieldpos+1]...
	 * @param	array	$array_match_file_to_database	Array of target fields where to insert data: [fieldpos] => 's.fieldname', [fieldpos+1]...
	 * @param 	Object	$objimport						Object import (contains objimport->import_tables_array, objimport->import_fields_array, objimport->import_convertvalue_array, ...)
	 * @param	int		$maxfields						Max number of fields to use
	 * @return	int										<0 if KO, >0 if OK
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

		if (count($arrayrecord) == 0 || (count($arrayrecord) == 1 && empty($arrayrecord[0]['val'])))
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

				// Loop on each fields in the match array: $key = 1..n, $val=alias of field (s.nom)
				foreach($sort_array_match_file_to_database as $key => $val)
				{
				    $fieldalias=preg_replace('/\..*$/i','',$val);
				    $fieldname=preg_replace('/^.*\./i','',$val);

				    if ($alias != $fieldalias) continue;    // Not a field of current table

					if ($key <= $maxfields)
					{
						// Set $newval with value to insert and set $listvalues with sql request part for insert
						$newval='';
						if ($arrayrecord[($key-1)]['type'] > 0) $newval=$arrayrecord[($key-1)]['val'];    // If type of field is not null or '' but string

						// Make some tests on $newval

						// Is it a required field ?
						if (preg_match('/\*/',$objimport->array_import_fields[0][$val]) && ($newval==''))
						{
							$this->errors[$error]['lib']=$langs->trans('ErrorMissingMandatoryValue',$key);
							$this->errors[$error]['type']='NOTNULL';
							$errorforthistable++;
							$error++;
						}
						// Test format only if field is not a missing mandatory field
						else
						{
						    // We convert fields if required
						    if (! empty($objimport->array_import_convertvalue[0][$val]))
						    {
                                //print 'Must convert '.$newval.' with rule '.join(',',$objimport->array_import_convertvalue[0][$val]).'. ';
                                if ($objimport->array_import_convertvalue[0][$val]['rule']=='fetchidfromcodeid' || $objimport->array_import_convertvalue[0][$val]['rule']=='fetchidfromref')
                                {
                                    if (! is_numeric($newval))    // If value into input import file is not a numeric, we apply the function defined into descriptor
                                    {
                                        $file=$objimport->array_import_convertvalue[0][$val]['classfile'];
                                        $class=$objimport->array_import_convertvalue[0][$val]['class'];
                                        $method=$objimport->array_import_convertvalue[0][$val]['method'];
                                        if (empty($this->cacheconvert[$file.'_'.$class.'_'.$method.'_'][$newval]))
                                        {
                                            dol_include_once($file);
                                            $classinstance=new $class($this->db);
                                            call_user_func_array(array($classinstance, $method),array('', $newval));
                                            $this->cacheconvert[$file.'_'.$class.'_'.$method.'_'][$newval]=$classinstance->id;
                                            //print 'We have made a '.$class.'->'.$method.' to get id from code '.$newval.'. ';
                                            if (! empty($classinstance->id))
                                            {
                                                $newval=$classinstance->id;
                                            }
                                            else
                                            {
                                                if (!empty($objimport->array_import_convertvalue[0][$val]['dict'])) $this->errors[$error]['lib']=$langs->trans('ErrorFieldValueNotIn',$key,$newval,'code',$langs->transnoentitiesnoconv($objimport->array_import_convertvalue[0][$val]['dict']));
                                                else if (!empty($objimport->array_import_convertvalue[0][$val]['element'])) $this->errors[$error]['lib']=$langs->trans('ErrorFieldRefNotIn',$key,$newval,$langs->transnoentitiesnoconv($objimport->array_import_convertvalue[0][$val]['element']));
                                                else $this->errors[$error]['lib']='ErrorFieldValueNotIn';
                                                $this->errors[$error]['type']='FOREIGNKEY';
                                                $errorforthistable++;
                                                $error++;
                                            }
                                        }
                                        else
                                        {
                                            $newval=$this->cacheconvert[$file.'_'.$class.'_'.$method.'_'][$newval];
                                        }
                                    }

                                }

                                //print 'Val to use as insert is '.$newval.'<br>';
						    }

						    // Test regexp
							if (! empty($objimport->array_import_regex[0][$val]) && ($newval != ''))
							{
								// If test is "Must exist in a field@table"
								if (preg_match('/^(.*)@(.*)$/',$objimport->array_import_regex[0][$val],$reg))
								{
									$field=$reg[1];
									$table=$reg[2];

									// Load content of field@table into cache array
									if (! is_array($this->cachefieldtable[$field.'@'.$table])) // If content of field@table not already loaded into cache
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
									    $errorforthistable++;
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

						// Define $listfields and $listvalues to build SQL request
						if ($listfields) { $listfields.=', '; $listvalues.=', '; }
						$listfields.=$fieldname;
						if ($arrayrecord[($key-1)]['type'] < 0)      	$listvalues.="null";
						elseif ($arrayrecord[($key-1)]['type'] == 0) 	$listvalues.="''";
						elseif ($arrayrecord[($key-1)]['type'] > 0)	$listvalues.="'".$this->db->escape($newval)."'";
					}
					$i++;
				}

				// We add hidden fields (but only if there is at least one field to add into table)
				if ($listfields && is_array($objimport->array_import_fieldshidden[0]))
				{
    				// Loop on each hidden fields to add them into listfields/listvalues
				    foreach($objimport->array_import_fieldshidden[0] as $key => $val)
    				{
    				    if (! preg_match('/^'.preg_quote($alias).'\./', $key)) continue;    // Not a field of current table
    				    if ($listfields) { $listfields.=', '; $listvalues.=', '; }
    				    if ($val == 'user->id')
    				    {
    				        $listfields.=preg_replace('/^'.preg_quote($alias).'\./','',$key);
    				        $listvalues.=$user->id;
    				    }
    				    elseif (preg_match('/^lastrowid-/',$val))
    				    {
    				        $tmp=explode('-',$val);
                            $lastinsertid=$this->db->last_insert_id($tmp[1]);
    				        $listfields.=preg_replace('/^'.preg_quote($alias).'\./','',$key);
                            $listvalues.=$lastinsertid;
    				        //print $key."-".$val."-".$listfields."-".$listvalues."<br>";exit;
    				    }
    				}
				}
				//print 'listfields='.$listfields.'<br>listvalues='.$listvalues.'<br>';

				// If no error for this $alias/$tablename, we have a complete $listfields and $listvalues that are defined
				if (! $errorforthistable)
				{
				    //print "$alias/$tablename/$listfields/$listvalues<br>";
					if ($listfields)
					{
					    //var_dump($objimport->array_import_convertvalue); exit;

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
					/*else
					{
						dol_print_error('','ErrorFieldListEmptyFor '.$alias."/".$tablename);
					}*/
				}
			}
		}

		return 1;
	}

}

/**
 *	Clean a string from separator
 *
 *	@param	string	$value	Remove separator
 *	@return	string			String without separator
 */
function cleansep($value)
{
	return str_replace(',','/',$value);
};

?>
