<?PHP
/* Copyright (C) 2008 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 */

/**
        \file       dev/skeletons/build_class_from_table.php
        \ingroup    core
        \brief      Create a complete class file from a table in database
        \version    $Id$
*/

// Test if batch mode
$sapi_type = php_sapi_name();
$script_file=__FILE__; 
if (eregi('([^\\\/]+)$',$script_file,$reg)) $script_file=$reg[1];
$path=eregi_replace($script_file,'',$_SERVER["PHP_SELF"]);

if (substr($sapi_type, 0, 3) == 'cgi')
{
	echo "Error: You use PHP for CGI mode. To execute $script_file as a command line program, you must use PHP for CLI mode (try php-cli).\n";
	exit;
}

// Include Dolibarr environment
require_once($path."../../htdocs/master.inc.php");
// After this $db is a defined handler to database.

// Main
$version='$Revision$';
@set_time_limit(0);
$error=0;

$langs->load("main");


print "***** $script_file ($version) *****\n";


// -------------------- START OF BUILD_CLASS_FROM_TABLE SCRIPT --------------------

// Check parameters
if (! isset($argv[1]))
{
    print "Usage: $script_file tablename\n";
    exit;
}

if ($db->type != 'mysql' && $db->type != 'mysqli')
{
	print "Error: This script works with mysql driver only\n";
	exit;
}

// Show parameters
print 'Tablename='.$argv[1]."\n";

$table=$argv[1];
$property=array();
$foundprimary=0;

$resql=$db->DDLDescTable($table);
if ($resql)
{
	$i=0;
	while($obj=$db->fetch_object($resql))
	{
	var_dump($obj);
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
		if (eregi('varchar',$property[$i]['type'])
			|| eregi('text',$property[$i]['type']))
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
}
//var_dump($property);


// Read skeleton_class.class.php file
$skeletonfile='skeleton_class.class.php';
$sourcecontent=file_get_contents($skeletonfile);
if (! $sourcecontent)
{
	print "\n";
	print "Error: Failed to read skeleton sample '".$skeletonfile."'\n";
	print "Try to run script from skeletons directory.\n";
	exit;
}


// Define content
$table=strtolower($table);
$tablenollx=eregi_replace('llx_','',$table);
$class=ucfirst($tablenollx);
$classmin=strtolower($class);
$outfile='out.'.$classmin.'.class.php';
$targetcontent=$sourcecontent;


// Substitute class name
$targetcontent=preg_replace('/skeleton_class\.class\.php/', $classmin.'.class.php', $targetcontent);
$targetcontent=preg_replace('/\$element=\'skeleton\'/', '\$element=\''.$classmin.'\'', $targetcontent);
$targetcontent=preg_replace('/\$table_element=\'skeleton\'/', '\$table_element=\''.$classmin.'\'', $targetcontent);
$targetcontent=preg_replace('/Skeleton_class/', $class, $targetcontent);

// Substitute comments
$targetcontent=preg_replace('/This file is an example to create a new class file/', 'Put here description of this class', $targetcontent);
$targetcontent=preg_replace('/\s*\/\/\.\.\./', '', $targetcontent);
$targetcontent=preg_replace('/Put here some comments/','Initialy built by build_class_from_table on '.strftime('%Y-%m-%d %H:%M',mktime()), $targetcontent);

// Substitute table name
$targetcontent=preg_replace('/MAIN_DB_PREFIX."mytable/', 'MAIN_DB_PREFIX."'.$tablenollx, $targetcontent);

// Substitute declaration parameters
$varprop="\n";
$cleanparam='';
foreach($property as $key => $prop)
{
	if ($prop['field'] != 'rowid')
	{
		$varprop.="\tvar \$".$prop['field'].";";
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
	if ($prop['field'] != 'rowid' && ! $prop['istime'])
	{
		$varprop.="\t\t\$this->".$prop['field']."=trim(\$this->".$prop['field'].");";
		$varprop.="\n";
	}
}
$targetcontent=preg_replace('/\$this->prop1=trim\(\$this->prop1\);/', $varprop, $targetcontent);
$targetcontent=preg_replace('/\$this->prop2=trim\(\$this->prop2\);/', '', $targetcontent);

// Substitute insert into parameters
$varprop="\n";
$cleanparam='';
$i=0;
foreach($property as $key => $prop)
{
	$i++;
	if ($prop['field'] != 'rowid' || $prop['extra'] != 'auto_increment')
	{
		$varprop.="\t\t\$sql.= \"".$prop['field'];
		if ($i < sizeof($property)) $varprop.=",";
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
	if ($prop['field'] != 'rowid' || $prop['extra'] != 'auto_increment')
	{
		$varprop.="\t\t\$sql.= \" ";
		if ($prop['istime']) $varprop.='".$this->db->idate(';
		else $varprop.="'\".";
		$varprop.="\$this->".$prop['field']."";
		if ($prop['istime']) $varprop.=')."';
		else $varprop.=".\"'";
		if ($i < sizeof($property)) $varprop.=",";
		$varprop.="\";";
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
	if ($prop['field'] != 'rowid')
	{
		$varprop.="\t\t\$sql.= \" ";
		$varprop.=$prop['field'].'=';
		if ($prop['istime']) $varprop.='".$this->db->idate(';
		else $varprop.="'\".";
		if ($prop['istime']) $varprop.="\$this->".$prop['field'];
		else if ($prop['ischar']) $varprop.="addslashes(\$this->".$prop['field'].")";
		else $varprop.="\$this->".$prop['field'];
		if ($prop['istime']) $varprop.=')."';
		else $varprop.=".\"'";
		if ($i < sizeof($property)) $varprop.=",";
		$varprop.="\";";
		$varprop.="\n";
	}
}
$targetcontent=preg_replace('/\$sql.= " field1=\'".addslashes\(\$this->field1\)."\',";/', $varprop, $targetcontent);
$targetcontent=preg_replace('/\$sql.= " field2=\'".addslashes\(\$this->field2\)."\'";/', '', $targetcontent);

// Substitute select parameters
$varprop="\n";
$cleanparam='';
$i=0;
foreach($property as $key => $prop)
{
	$i++;
	if ($prop['field'] != 'rowid')
	{
		$varprop.="\t\t\$sql.= \" ";
		if ($prop['istime']) $varprop.="\".\$this->db->pdate('";
		$varprop.="t.".$prop['field'];
		if ($prop['istime']) $varprop.="').\" as ".$prop['field'];
		if ($i < sizeof($property)) $varprop.=",";
		$varprop.="\";";
		$varprop.="\n";
	}
}
$targetcontent=preg_replace('/\$sql\.= " t\.field1,";/', $varprop, $targetcontent);
$targetcontent=preg_replace('/\$sql\.= " t\.field2";/', '', $targetcontent);

// Substitute select set parameters
$varprop="\n";
$cleanparam='';
$i=0;
foreach($property as $key => $prop)
{
	$i++;
	if ($prop['field'] != 'rowid')
	{
		$varprop.="\t\t\t\t\$this->".$prop['field']." = ";
		$varprop.="\$obj->".$prop['field'];
		$varprop.=";";
		$varprop.="\n";
	}
}
$targetcontent=preg_replace('/\$this->prop1 = \$obj->field1;/', $varprop, $targetcontent);
$targetcontent=preg_replace('/\$this->prop2 = \$obj->field2;/', '', $targetcontent);


// Substitute initasspecimen parameters
$varprop="\n";
$cleanparam='';
foreach($property as $key => $prop)
{
	if ($prop['field'] != 'rowid')
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
	return 1;
}
else
{
	return -1;
}

// -------------------- END OF BUILD_CLASS_FROM_TABLE SCRIPT --------------------

return $error;
?>
