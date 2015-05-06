#!/usr/bin/php
<?php
/* Copyright (C) 2008-2014 Laurent Destailleur  <eldy@users.sourceforge.net>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
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
 *	\file       dev/skeletons/build_class_from_table.php
 *  \ingroup    core
 *  \brief      Create a complete class file from a table in database
 */

$sapi_type = php_sapi_name();
$script_file = basename(__FILE__);
$path=dirname(__FILE__).'/';

// Test if batch mode
if (substr($sapi_type, 0, 3) == 'cgi') {
    echo "Error: You are using PHP for CGI. To execute ".$script_file." from command line, you must use PHP for CLI mode.\n";
    exit;
}

// Include Dolibarr environment
require_once($path."../../htdocs/master.inc.php");
// After this $db is a defined handler to database.

// Main
$version='3.2';
@set_time_limit(0);
$error=0;

$langs->load("main");


print "***** $script_file ($version) *****\n";


// -------------------- START OF BUILD_CLASS_FROM_TABLE SCRIPT --------------------

// Check parameters
if (! isset($argv[1]) || (isset($argv[2]) && ! isset($argv[6])))
{
    print "Usage: $script_file tablename [server port databasename user pass]\n";
    exit;
}

if (isset($argv[2]) && isset($argv[3]) && isset($argv[4]) && isset($argv[5]) && isset($argv[6]))
{
	print 'Use specific database ids'."\n";
	$db=getDoliDBInstance('mysqli',$argv[2],$argv[5],$argv[6],$argv[4],$argv[3]);
}

if ($db->type != 'mysql' && $db->type != 'mysqli')
{
	print "Error: This script works with mysql or mysqli driver only\n";
	exit;
}

// Show parameters
print 'Tablename='.$argv[1]."\n";
print "Current dir is ".getcwd()."\n";


// Define array with list of properties
$property=array();
$table=$argv[1];
$foundprimary=0;
$resql=$db->DDLDescTable($table);
if ($resql)
{
	$i=0;
	while($obj=$db->fetch_object($resql))
	{
		//var_dump($obj);
		$i++;
		$property[$i]['field']=$obj->Field;
		if ($obj->Key == 'PRI')
		{
			$property[$i]['primary']=1;
			$foundprimary=1;
		}
		else
		{
			$property[$i]['primary']=1;
		}
		$property[$i]['type'] =$obj->Type;
		$property[$i]['null'] =$obj->Null;
		$property[$i]['extra']=$obj->Extra;
		if ($property[$i]['type'] == 'date'
			|| $property[$i]['type'] == 'datetime'
			|| $property[$i]['type'] == 'timestamp')
		{
			$property[$i]['istime']=true;
		}
		else
		{
			$property[$i]['istime']=false;
		}
		if (preg_match('/varchar/i',$property[$i]['type'])
			|| preg_match('/text/i',$property[$i]['type']))
		{
			$property[$i]['ischar']=true;
		}
		else
		{
			$property[$i]['ischar']=false;
		}
	}
}
else
{
	print "Error: Failed to get description for table '".$table."'.\n";
	return false;
}
//var_dump($property);

// Define substitute fetch/select parameters
$varpropselect="\n";
$cleanparam='';
$i=0;
foreach($property as $key => $prop)
{
    $i++;
    if ($prop['field'] != 'rowid')
    {
        $varpropselect.="\t\t\$sql.= \" ";
        $varpropselect.="t.".$prop['field'];
        if ($i < count($property)) $varpropselect.=",";
        $varpropselect.="\";";
        $varpropselect.="\n";
    }
}



//--------------------------------
// Build skeleton_class.class.php
//--------------------------------

// Define working variables
$table=strtolower($table);
$tablenoprefix=preg_replace('/'.preg_quote(MAIN_DB_PREFIX).'/i','',$table);
$classname=preg_replace('/_/','',ucfirst($tablenoprefix));
$classmin=preg_replace('/_/','',strtolower($classname));


// Read skeleton_class.class.php file
$skeletonfile=$path.'skeleton_class.class.php';
$sourcecontent=file_get_contents($skeletonfile);
if (! $sourcecontent)
{
	print "\n";
	print "Error: Failed to read skeleton sample '".$skeletonfile."'\n";
	print "Try to run script from skeletons directory.\n";
	exit;
}

// Define output variables
$outfile='out.'.$classmin.'.class.php';
$targetcontent=$sourcecontent;

// Substitute class name
$targetcontent=preg_replace('/skeleton_class\.class\.php/', $classmin.'.class.php', $targetcontent);
$targetcontent=preg_replace('/\$element=\'skeleton\'/', '\$element=\''.$classmin.'\'', $targetcontent);
$targetcontent=preg_replace('/\$table_element=\'skeleton\'/', '\$table_element=\''.$tablenoprefix.'\'', $targetcontent);
$targetcontent=preg_replace('/Skeleton_Class/', $classname, $targetcontent);

// Substitute comments
$targetcontent=preg_replace('/This file is an example to create a new class file/', 'Put here description of this class', $targetcontent);
$targetcontent=preg_replace('/\s*\/\/\.\.\./', '', $targetcontent);
$targetcontent=preg_replace('/Put here some comments/','Initialy built by build_class_from_table on '.strftime('%Y-%m-%d %H:%M',mktime()), $targetcontent);

// Substitute table name
$targetcontent=preg_replace('/MAIN_DB_PREFIX."mytable/', 'MAIN_DB_PREFIX."'.$tablenoprefix, $targetcontent);

// Substitute declaration parameters
$varprop="\n";
$cleanparam='';
foreach($property as $key => $prop)
{
	if ($prop['field'] != 'rowid' && $prop['field'] != 'id')
	{
		$varprop.="\tvar \$".$prop['field'];
		if ($prop['istime']) $varprop.="=''";
		$varprop.=";";
		if ($prop['comment']) $varprop.="\t// ".$prop['extra'];
		$varprop.="\n";
	}
}
$targetcontent=preg_replace('/var \$prop1;/', $varprop, $targetcontent);
$targetcontent=preg_replace('/var \$prop2;/', '', $targetcontent);

// Substitute clean parameters
$varprop="\n";
$cleanparam='';
foreach($property as $key => $prop)
{
	if ($prop['field'] != 'rowid' && $prop['field'] != 'id' && ! $prop['istime'])
	{
		$varprop.="\t\tif (isset(\$this->".$prop['field'].")) \$this->".$prop['field']."=trim(\$this->".$prop['field'].");";
		$varprop.="\n";
	}
}
$targetcontent=preg_replace('/if \(isset\(\$this->prop1\)\) \$this->prop1=trim\(\$this->prop1\);/', $varprop, $targetcontent);
$targetcontent=preg_replace('/if \(isset\(\$this->prop2\)\) \$this->prop2=trim\(\$this->prop2\);/', '', $targetcontent);

// Substitute insert into parameters
$varprop="\n";
$cleanparam='';
$i=0;
foreach($property as $key => $prop)
{
	$i++;
	$addfield=1;
	if ($prop['field'] == 'tms') $addfield=0;	// This is a field of type timestamp edited automatically
	if ($prop['extra'] == 'auto_increment') $addfield=0;

	if ($addfield)
	{
		$varprop.="\t\t\$sql.= \"".$prop['field'];
		if ($i < count($property)) $varprop.=",";
		$varprop.="\";";
		$varprop.="\n";
	}
}
$targetcontent=preg_replace('/\$sql\.= " field1,";/', $varprop, $targetcontent);
$targetcontent=preg_replace('/\$sql\.= " field2";/', '', $targetcontent);

// Substitute insert values parameters
$varprop="\n";
$cleanparam='';
$i=0;
foreach($property as $key => $prop)
{
	$i++;
	$addfield=1;
	if ($prop['field'] == 'tms') $addfield=0;	// This is a field of type timestamp edited automatically
	if ($prop['extra'] == 'auto_increment') $addfield=0;

	if ($addfield)
	{
		$varprop.="\t\t\$sql.= \" ";
		if ($prop['field']=='datec')
		{
			$varprop.='"."\'".$this->db->idate(dol_now())."\'"."';
			if ($i < count($property)) $varprop.=",";
			$varprop.='";';
		}
		elseif ($prop['istime'])
		{
			$varprop.='".(! isset($this->'.$prop['field'].') || dol_strlen($this->'.$prop['field'].')==0?\'NULL\':"\'".$this->db->idate(';
			$varprop.="\$this->".$prop['field']."";
			$varprop.=')."\'")."';
			if ($i < count($property)) $varprop.=",";
			$varprop.='";';
		}
		elseif ($prop['ischar'])
		{
			$varprop.='".(! isset($this->'.$prop['field'].')?\'NULL\':"\'".';
			$varprop.='$this->db->escape($this->'.$prop['field'].')';
			$varprop.='."\'")."';
			if ($i < count($property)) $varprop.=",";
			$varprop.='";';
		}
		elseif ($prop['field']=='fk_user_mod' || $prop['field']=='fk_user_author')
		{
			$varprop.='".$user->id."';
			if ($i < count($property)) $varprop.=",";
			$varprop.='";';
		}
		else
		{
			$varprop.='".(! isset($this->'.$prop['field'].')?\'NULL\':"\'".';
			$varprop.="\$this->".$prop['field']."";
			$varprop.='."\'")."';
			if ($i < count($property)) $varprop.=",";
			$varprop.='";';
		}
		$varprop.="\n";
	}
}
$targetcontent=preg_replace('/\$sql\.= " \'".\$this->prop1\."\',";/', $varprop, $targetcontent);
$targetcontent=preg_replace('/\$sql\.= " \'".\$this->prop2\."\'";/', '', $targetcontent);

// Substitute update values parameters
$varprop="\n";
$cleanparam='';
$i=0;
foreach($property as $key => $prop)
{
	$i++;
	if ($prop['field'] != 'rowid' && $prop['field'] != 'id' && $prop['field'] != 'datec' && $prop['field'] != 'fk_user_author')
	{
		$varprop.="\t\t\$sql.= \" ";
		$varprop.=$prop['field'].'=';
		if ($prop['field']=='tms') {
			$varprop.='".(dol_strlen($this->'.$prop['field'].')!=0 ? "\'".$this->db->idate(';
			$varprop.='$this->'.$prop['field'];
			$varprop.=')."\'" : "\'".$this->db->idate(dol_now())."\'").';
			$varprop.='"';
		}
		elseif ($prop['istime'])
		{
			// (dol_strlen($this->datep)!=0 ? "'".$this->db->idate($this->datep)."'" : 'null')
			$varprop.='".(dol_strlen($this->'.$prop['field'].')!=0 ? "\'".$this->db->idate(';
			$varprop.='$this->'.$prop['field'];
			$varprop.=')."\'" : \'null\').';
			$varprop.='"';
		}
		elseif ($prop['field']=='fk_user_mod') {
			$varprop.='".$user->id."';
		}
		else
		{
			$varprop.="\".";
			// $sql.= " field1=".(isset($this->field1)?"'".$this->db->escape($this->field1)."'":"null").",";
			if ($prop['ischar']) $varprop.='(isset($this->'.$prop['field'].')?"\'".$this->db->escape($this->'.$prop['field'].')."\'":"null")';
			// $sql.= " field1=".(isset($this->field1)?$this->field1:"null").",";
			else $varprop.='(isset($this->'.$prop['field'].')?$this->'.$prop['field'].':"null")';
			$varprop.=".\"";
		}

		if ($i < count($property)) $varprop.=',';
		$varprop.='";';
		$varprop.="\n";
	}
}
$targetcontent=preg_replace('/\$sql.= " field1=".\(isset\(\$this->field1\)\?"\'".\$this->db->escape\(\$this->field1\)."\'":"null"\).",";/', $varprop, $targetcontent);
$targetcontent=preg_replace('/\$sql.= " field2=".\(isset\(\$this->field2\)\?"\'".\$this->db->escape\(\$this->field2\)."\'":"null"\)."";/', '', $targetcontent);

// Substitute fetch/select parameters
$targetcontent=preg_replace('/\$sql\.= " t\.field1,";/', $varpropselect, $targetcontent);
$targetcontent=preg_replace('/\$sql\.= " t\.field2";/', '', $targetcontent);

// Substitute select set parameters
$varprop="\n";
$varpropline="\n";
$cleanparam='';
$i=0;
foreach($property as $key => $prop)
{
	$i++;
	if ($prop['field'] != 'rowid' && $prop['field'] != 'id')
	{
		$varprop.="\t\t\t\t\$this->".$prop['field']." = ";
		if ($prop['istime']) $varprop.='$this->db->jdate(';
		$varprop.='$obj->'.$prop['field'];
		if ($prop['istime']) $varprop.=')';
		$varprop.=";";
		$varprop.="\n";
		
		$varpropline.="\t\t\t\t\$line->".$prop['field']." = ";
		if ($prop['istime']) $varpropline.='$this->db->jdate(';
		$varpropline.='$obj->'.$prop['field'];
		if ($prop['istime']) $varpropline.=')';
		$varpropline.=";";
		$varpropline.="\n";
	}
}
$targetcontent=preg_replace('/\$this->prop1 = \$obj->field1;/', $varprop, $targetcontent);
$targetcontent=preg_replace('/\$this->prop2 = \$obj->field2;/', '', $targetcontent);

//Substirute fetchAll
$targetcontent=preg_replace('/\$line->prop1 = \$obj->field1;/', $varpropline, $targetcontent);
$targetcontent=preg_replace('/\$line->prop2 = \$obj->field2;/', '', $targetcontent);


// Substitute initasspecimen parameters
$varprop="\n";
$cleanparam='';
foreach($property as $key => $prop)
{
	if ($prop['field'] != 'rowid' && $prop['field'] != 'id')
	{
		$varprop.="\t\t\$this->".$prop['field']."='';";
		$varprop.="\n";
	}
}
$targetcontent=preg_replace('/\$this->prop1=\'prop1\';/', $varprop, $targetcontent);
$targetcontent=preg_replace('/\$this->prop2=\'prop2\';/', '', $targetcontent);

// Build file
$fp=fopen($outfile,"w");
if ($fp)
{
	fputs($fp, $targetcontent);
	fclose($fp);
	print "\n";
	print "File '".$outfile."' has been built in current directory.\n";
}
else $error++;


//--------------------------------
// Build skeleton_script.php
//--------------------------------

// Read skeleton_script.php file
$skeletonfile=$path.'skeleton_script.php';
$sourcecontent=file_get_contents($skeletonfile);
if (! $sourcecontent)
{
	print "\n";
	print "Error: Failed to read skeleton sample '".$skeletonfile."'\n";
	print "Try to run script from skeletons directory.\n";
	exit;
}

// Define output variables
$outfile='out.'.$classmin.'_script.php';
$targetcontent=$sourcecontent;

// Substitute class name
$targetcontent=preg_replace('/skeleton_class\.class\.php/', $classmin.'.class.php', $targetcontent);
$targetcontent=preg_replace('/skeleton_script\.php/', $classmin.'_script.php', $targetcontent);
$targetcontent=preg_replace('/\$element=\'skeleton\'/', '\$element=\''.$classmin.'\'', $targetcontent);
$targetcontent=preg_replace('/\$table_element=\'skeleton\'/', '\$table_element=\''.$classmin.'\'', $targetcontent);
$targetcontent=preg_replace('/Skeleton_Class/', $classname, $targetcontent);

// Substitute comments
$targetcontent=preg_replace('/This file is an example to create a new class file/', 'Put here description of this class', $targetcontent);
$targetcontent=preg_replace('/\s*\/\/\.\.\./', '', $targetcontent);
$targetcontent=preg_replace('/Put here some comments/','Initialy built by build_class_from_table on '.strftime('%Y-%m-%d %H:%M',mktime()), $targetcontent);

// Substitute table name
$targetcontent=preg_replace('/MAIN_DB_PREFIX."mytable/', 'MAIN_DB_PREFIX."'.$tablenoprefix, $targetcontent);

// Build file
$fp=fopen($outfile,"w");
if ($fp)
{
	fputs($fp, $targetcontent);
	fclose($fp);
	print "File '".$outfile."' has been built in current directory.\n";
}
else $error++;



//--------------------------------
// Build skeleton_page.php
//--------------------------------

// Read skeleton_page.php file
$skeletonfile=$path.'skeleton_page.php';
$sourcecontent=file_get_contents($skeletonfile);
if (! $sourcecontent)
{
    print "\n";
    print "Error: Failed to read skeleton sample '".$skeletonfile."'\n";
    print "Try to run script from skeletons directory.\n";
    exit;
}

// Define output variables
$outfile='out.'.$classmin.'_page.php';
$targetcontent=$sourcecontent;

// Substitute class name
$targetcontent=preg_replace('/skeleton_class\.class\.php/', $classmin.'.class.php', $targetcontent);
$targetcontent=preg_replace('/skeleton_script\.php/', $classmin.'_script.php', $targetcontent);
$targetcontent=preg_replace('/\$element=\'skeleton\'/', '\$element=\''.$classmin.'\'', $targetcontent);
$targetcontent=preg_replace('/\$table_element=\'skeleton\'/', '\$table_element=\''.$classmin.'\'', $targetcontent);
$targetcontent=preg_replace('/Skeleton_Class/', $classname, $targetcontent);
$targetcontent=preg_replace('/skeleton/', $classname, $targetcontent);

// Substitute comments
$targetcontent=preg_replace('/This file is an example to create a new class file/', 'Put here description of this class', $targetcontent);
$targetcontent=preg_replace('/\s*\/\/\.\.\./', '', $targetcontent);
$targetcontent=preg_replace('/Put here some comments/','Initialy built by build_class_from_table on '.strftime('%Y-%m-%d %H:%M',mktime()), $targetcontent);

// Substitute table name
$targetcontent=preg_replace('/MAIN_DB_PREFIX."mytable/', 'MAIN_DB_PREFIX."'.$tablenoprefix, $targetcontent);

// Substitute fetch/select parameters
$targetcontent=preg_replace('/\$sql\.= " t\.field1,";/', $varpropselect, $targetcontent);
$targetcontent=preg_replace('/\$sql\.= " t\.field2";/', '', $targetcontent);

// Build file
$fp=fopen($outfile,"w");
if ($fp)
{
    fputs($fp, $targetcontent);
    fclose($fp);
    print "File '".$outfile."' has been built in current directory.\n";
}
else $error++;


// -------------------- END OF BUILD_CLASS_FROM_TABLE SCRIPT --------------------

print "You can now rename generated files by removing the 'out.' prefix in their name and store them into directory /yourmodule/class.\n";
return $error;
