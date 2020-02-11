#!/usr/bin/env php
<?php
/*
 * Copyright (C) 2005-2011 James Grant 			<james@lightbox.org> 			Lightbox Technologies Inc.
 * Copyright (C) 2020 	   Laurent Destailleur 	<eldy@users.sourceforge.net>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 *
 * This file is base on pg2mysql provided as Open source by lightbox.org.
 * It was enhanced and updated by the Dolibarr team.
 */

/**
 * \file 	dev/tools/dolibarr-postgres2mysql.php
 * \brief 	Script to migrate a postgresql dump into a mysql dump
 */

$sapi_type = php_sapi_name();
$script_file = basename(__FILE__);
$path = dirname(__FILE__) . '/';

// Test si mode batch
$sapi_type = php_sapi_name();
if (substr($sapi_type, 0, 3) == 'cgi') {
	echo "Error: You are using PHP for CGI. To execute " . $script_file . " from command line, you must use PHP for CLI mode.\n";
	exit();
}

error_reporting(E_ALL & ~ E_DEPRECATED);
define('PRODUCT', "pg2mysql");
define('VERSION', "2.0");

// this is the default, it can be overridden here, or specified as the third parameter on the command line
$config['engine'] = "InnoDB";

if (! ($argv[1] && $argv[2])) {
	echo "Usage: php pg2mysql_cli.php <inputfilename> <outputfilename> [engine]\n";
	exit();
} else {
	if (isset($argv[3]))
		$config['engine'] = $argv[3];
	pg2mysql_large($argv[1], $argv[2]);

	echo <<<XHTML
Notes:
 - No its not perfect
 - Yes it discards ALL stored procedures
 - Yes it discards ALL queries except for CREATE TABLE and INSERT INTO
 - If you're having problems creating your postgres dump, make sure you use "--format p --inserts"
 - Default output engine if not specified is InnoDB

XHTML;
}

/**
 * getfieldname
 *
 * @param	string		$l		String
 * @return	string|null			Field name
 */
function getfieldname($l)
{
	// first check if its in nice quotes for us
	$regs = array();
	if (preg_match("/`(.*)`/", $l, $regs)) {
		if ($regs[1])
			return $regs[1];
		else
			return null;
	} // if its not in quotes, then it should (we hope!) be the first "word" on the line, up to the first space.
	elseif (preg_match("/([^\ ]*)/", trim($l), $regs)) {
		if ($regs[1])
			return $regs[1];
		else
			return null;
	}
}


/**
 * formatsize
 *
 * @param 	string $s	Size to format
 * @return 	string		Formated size
 */
function formatsize($s)
{
	if ($s < pow(2, 14))
		return "{$s}B";
	elseif ($s < pow(2, 20))
		return sprintf("%.1f", round($s / 1024, 1)) . "K";
	elseif ($s < pow(2, 30))
		return sprintf("%.1f", round($s / 1024 / 1024, 1)) . "M";
	else
		return sprintf("%.1f", round($s / 1024 / 1024 / 1024, 1)) . "G";
}

/**
 * pg2mysql_large
 *
 * @param string	$infilename			Input filename
 * @param string	$outfilename		Output filename
 * @return int							<0 if KO, >=0 if OK
 */
function pg2mysql_large($infilename, $outfilename)
{
	$infp = fopen($infilename, "rt");
	$outfp = fopen($outfilename, "wt");

	$outputatend = '';
	$arrayofprimaryalreadyintabledef = array();

	// we read until we get a semicolon followed by a newline (;\n);
	$pgsqlchunk = array();
	$chunkcount = 1;
	$linenum = 0;
	$inquotes = false;
	$first = true;

	if (empty($infp)) {
		print 'Failed to open file '.$infilename."\n";
		return -1;
	}

	$fs = filesize($infilename);
	echo "Filesize: " . formatsize($fs) . "\n";

	while ($instr = fgets($infp)) {
		$linenum ++;
		$memusage = round(memory_get_usage(true) / 1024 / 1024);
		$len = strlen($instr);
		$pgsqlchunk[] = $instr;
		$c = substr_count($instr, "'");
		// we have an odd number of ' marks
		if ($c % 2 != 0) {
			if ($inquotes)
				$inquotes = false;
			else
				$inquotes = true;
		}

		if ($linenum % 10000 == 0) {
			$currentpos = ftell($infp);
			$percent = round($currentpos / $fs * 100);
			$position = formatsize($currentpos);
			printf("Reading    progress: %3d%%   position: %7s   line: %9d   sql chunk: %9d  mem usage: %4dM\r", $percent, $position, $linenum, $chunkcount, $memusage);
		}

		if (strlen($instr) > 3 && ($instr[$len - 3] == ")" && $instr[$len - 2] == ";" && $instr[$len - 1] == "\n") && $inquotes == false) {
			$chunkcount ++;

			if ($linenum % 10000 == 0) {
				$currentpos = ftell($infp);
				$percent = round($currentpos / $fs * 100);
				$position = formatsize($currentpos);
				printf("Processing progress: %3d%%   position: %7s   line: %9d   sql chunk: %9d  mem usage: %4dM\r", $percent, $position, $linenum, $chunkcount, $memusage);
			}
			/*
			 * echo "sending chunk:\n";
			 * echo "=======================\n";
			 * print_r($pgsqlchunk);
			 * echo "=======================\n";
			 */

			/*
			 * foreach ($pgsqlchunk as $aaa) {
			 * if (preg_match('/MAIN_ENABLE_DEFAULT|MAIN_MAIL_SMTP_SE/', $aaa)) {
			 * var_dump($pgsqlchunk);
			 * }
			 * }
			 */

			$mysqlchunk = pg2mysql($pgsqlchunk, $arrayofprimaryalreadyintabledef, $first);
			fputs($outfp, $mysqlchunk['output']);

			/*
			 * $break = false;
			 * foreach ($pgsqlchunk as $aaa) {
			 * if (preg_match('/MAIN_ENABLE_DEFAULT|MAIN_MAIL_SMTP_SE/', $aaa)) {
			 * var_dump($mysqlchunk);
			 * }
			 * if (preg_match('/MAIN_MAIL_SMTP_SE/', $aaa)) {
			 * $break = true;
			 * }
			 * }
			 * if ($break) break;
			 */

			$outputatend .= $mysqlchunk['outputatend'];

			$first = false;
			$pgsqlchunk = array();
			$mysqlchunk = "";
		}
	}
	echo "\n\n";

	fputs($outfp, $outputatend);

	fputs($outfp, "\n");

	fputs($outfp, '/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;' . "\n");
	fputs($outfp, '/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;' . "\n");
	fputs($outfp, '/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;' . "\n");
	fputs($outfp, '/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;' . "\n");
	fputs($outfp, '/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;' . "\n");
	fputs($outfp, '/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;' . "\n");
	fputs($outfp, '/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;' . "\n");

	printf("Completed! %9d lines   %9d sql chunks\n\n", $linenum, $chunkcount);

	fclose($infp);
	fclose($outfp);

	return 0;
}

/**
 * pg2mysql
 *
 * @param array		$input								Array of input
 * @param array		$arrayofprimaryalreadyintabledef	Array of table already output with a primary key set into definition
 * @param boolean 	$header								Boolean
 * @return string[]										Array of output
 */
function pg2mysql(&$input, &$arrayofprimaryalreadyintabledef, $header = true)
{
	global $config;

	if (is_array($input)) {
		$lines = $input;
	} else {
		$lines = split("\n", $input);
	}

	if ($header) {
		$output = "-- Converted with " . PRODUCT . "-" . VERSION . "\n";
		$output .= "-- Converted on " . date("r") . "\n";
		$output .= "\n";

		$output .= "/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;\n";
		$output .= "/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;\n";
		$output .= "/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;\n";
		$output .= "/*!40101 SET NAMES utf8 */;\n";
		$output .= "/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;\n";
		$output .= "/*!40103 SET TIME_ZONE='+00:00' */;\n";
		$output .= "/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;\n";
		$output .= "/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;\n";
		$output .= "/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;\n";
		$output .= "/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;\n";
		$output .= "\n";

		$outputatend = "";
	} else {
		$output = "";
		$outputatend = "";
	}

	$in_create_table = $in_insert = false;

	$linenumber = 0;
	$tbl_extra = "";
	while (isset($lines[$linenumber])) {
		$line = $lines[$linenumber];
		// $line =str_replace('ALTER TABLE public\.', '', $line);

		$reg = array();
		if (preg_match('/CREATE SEQUENCE (?:public\.)(.*)_(id|rowid|id_comment)_seq/', $line, $reg)) {
			$outputatend .= '-- Make field ' . $reg[2] . ' auto_increment for table ' . $reg[1] . "\n";
			$outputatend .= 'ALTER TABLE ' . $reg[1] . ' CHANGE COLUMN ' . $reg[2] . ' ' . $reg[2] . ' INTEGER NOT NULL AUTO_INCREMENT;' . "\n\n";
			// var_dump($outputatend);
		}

		if (substr($line, 0, 12) == "CREATE TABLE") {
			$in_create_table = true;
			$line = str_replace("\"", "`", $line);
			$line = str_replace('public.', '', $line);

			$reg2 = array();
			if (preg_match('/CREATE TABLE ([^\s]+)/', $line, $reg2)) {
				$in_create_table = $reg2[1];
			}

			$reg2 = array();
			if (preg_match('/CREATE TABLE ([^\s]+)/', $line, $reg2)) {
				$output .= 'DROP TABLE IF EXISTS `' . $reg2[1] . '`;' . "\n";
			}
			$output .= $line;
			$linenumber ++;
			continue;
		}

		if (substr($line, 0, 2) == ");" && $in_create_table) {
			$in_create_table = false;
			$line = ") ENGINE={$config['engine']};\n\n";

			$output .= $tbl_extra;
			$output .= $line;

			$linenumber ++;
			$tbl_extra = "";
			continue;
		}

		if ($in_create_table) {
			$regs = array();
			$line = str_replace("\"", "`", $line);
			$line = str_replace(" integer", " int(11)", $line);
			$line = str_replace(" int_unsigned", " int(11) UNSIGNED", $line);
			$line = str_replace(" smallint_unsigned", " smallint UNSIGNED", $line);
			$line = str_replace(" bigint_unsigned", " bigint UNSIGNED", $line);
			$line = str_replace(" serial ", " int(11) auto_increment ", $line);
			$line = str_replace(" bytea", " BLOB", $line);
			$line = str_replace(" boolean", " bool", $line);
			$line = str_replace(" bool DEFAULT true", " bool DEFAULT 1", $line);
			$line = str_replace(" bool DEFAULT false", " bool DEFAULT 0", $line);
			if (preg_match("/ character varying\(([0-9]*)\)/", $line, $regs)) {
				$num = $regs[1];
				if ($num <= 255)
					$line = preg_replace("/ character varying\([0-9]*\)/", " varchar($num)", $line);
				else
					$line = preg_replace("/ character varying\([0-9]*\)/", " text", $line);
			}
			// character varying with no size, we will default to varchar(255)
			if (preg_match("/ character varying/", $line)) {
				$line = preg_replace("/ character varying/", " varchar(255)", $line);
			}

			if (preg_match("/ DEFAULT \('([0-9]*)'::int/", $line, $regs) || preg_match("/ DEFAULT \('([0-9]*)'::smallint/", $line, $regs) || preg_match("/ DEFAULT \('([0-9]*)'::bigint/", $line, $regs)) {
				$num = $regs[1];
				$line = preg_replace("/ DEFAULT \('([0-9]*)'[^ ,]*/", " DEFAULT $num ", $line);
			}
			if (preg_match("/ DEFAULT \(([0-9\-]*)\)/", $line, $regs)) {
				$num = $regs[1];
				$line = preg_replace("/ DEFAULT \(([0-9\-]*)\)/", " DEFAULT $num ", $line);
			}
			$line = preg_replace("/ DEFAULT nextval\(.*\) /", " auto_increment ", $line);
			$line = preg_replace("/::.*,/", ",", $line);
			$line = preg_replace("/::.*$/", "\n", $line);
			if (preg_match("/character\(([0-9]*)\)/", $line, $regs)) {
				$num = $regs[1];
				if ($num <= 255)
					$line = preg_replace("/ character\([0-9]*\)/", " varchar($num)", $line);
				else
					$line = preg_replace("/ character\([0-9]*\)/", " text", $line);
			}
			// timestamps
			$line = str_replace(" timestamp with time zone", " datetime", $line);
			$line = str_replace(" timestamp without time zone", " datetime", $line);

			// time
			$line = str_replace(" time with time zone", " time", $line);
			$line = str_replace(" time without time zone", " time", $line);

			$line = str_replace(" timestamp DEFAULT now()", " timestamp DEFAULT CURRENT_TIMESTAMP", $line);
			$line = str_replace(" timestamp without time zone DEFAULT now()", " timestamp DEFAULT CURRENT_TIMESTAMP", $line);

			if (strstr($line, "auto_increment") || preg_match('/ rowid int/', $line) || preg_match('/ id int/', $line)) {
				$field = getfieldname($line);
				$tbl_extra .= ", PRIMARY KEY(`$field`)\n";
				$arrayofprimaryalreadyintabledef[$in_create_table] = $in_create_table;
			}

			$specialfields = array("repeat","status","type","call");

			$field = getfieldname($line);
			if (in_array($field, $specialfields)) {
				$line = str_replace("$field ", "`$field` ", $line);
			}

			// text/blob fields are not allowed to have a default, so if we find a text DEFAULT, change it to varchar(255) DEFAULT
			if (strstr($line, "text DEFAULT")) {
				$line = str_replace(" text DEFAULT ", " varchar(255) DEFAULT ", $line);
			}

			// just skip a CONSTRAINT line
			if (strstr($line, " CONSTRAINT ")) {
				$line = "";
				// and if the previous output ended with a , remove the ,
				$lastchr = substr($output, - 2, 1);
				// echo "lastchr=$lastchr";
				if ($lastchr == ",") {
					$output = substr($output, 0, - 2) . "\n";
				}
			}

			$output .= $line;
		}

		if (substr($line, 0, 11) == "INSERT INTO") {
			$line = str_replace('public.', '', $line);

			if (substr($line, - 3, - 1) == ");") {
				// we have a complete insert on one line
				list ($before, $after) = explode(" VALUES ", $line, 2);
				// we only replace the " with ` in what comes BEFORE the VALUES
				// (ie, field names, like INSERT INTO table ("bla","bla2") VALUES ('s:4:"test"','bladata2');
				// should convert to INSERT INTO table (`bla`,`bla2`) VALUES ('s:4:"test"','bladata2');

				$before = str_replace("\"", "`", $before);

				// in after, we need to watch out for escape format strings, ie (E'escaped \r in a string'), and ('bla',E'escaped \r in a string'), but could also be (number, E'string'); so we cant search for the previoous '
				// ugh i guess its possible these strings could exist IN the data as well, but the only way to solve that is to process these lines one character
				// at a time, and thats just stupid, so lets just hope this doesnt appear anywhere in the actual data
				$after = str_replace(" (E'", " ('", $after);
				$after = str_replace(", E'", ", '", $after);

				$output .= $before . " VALUES " . $after;
				$linenumber ++;
				continue;
			} else {
				// this insert spans multiple lines, so keep dumping the lines until we reach a line
				// that ends with ");"

				list ($before, $after) = explode(" VALUES ", $line, 2);
				// we only replace the " with ` in what comes BEFORE the VALUES
				// (ie, field names, like INSERT INTO table ("bla","bla2") VALUES ('s:4:"test"','bladata2');
				// should convert to INSERT INTO table (`bla`,`bla2`) VALUES ('s:4:"test"','bladata2');

				$before = str_replace("\"", "`", $before);

				// in after, we need to watch out for escape format strings, ie (E'escaped \r in a string'), and ('bla',E'escaped \r in a string')
				// ugh i guess its possible these strings could exist IN the data as well, but the only way to solve that is to process these lines one character
				// at a time, and thats just stupid, so lets just hope this doesnt appear anywhere in the actual data
				$after = str_replace(" (E'", " ('", $after);
				$after = str_replace(", E'", ", '", $after);

				$c = substr_count($line, "'");
				// we have an odd number of ' marks
				if ($c % 2 != 0) {
					$inquotes = true;
				} else {
					$inquotes = false;
				}

				$output .= $before . " VALUES " . $after;
				do {
					$linenumber ++;

					// in after, we need to watch out for escape format strings, ie (E'escaped \r in a string'), and ('bla',E'escaped \r in a string')
					// ugh i guess its possible these strings could exist IN the data as well, but the only way to solve that is to process these lines one character
					// at a time, and thats just stupid, so lets just hope this doesnt appear anywhere in the actual data

					// after the first line, we only need to check for it in the middle, not at the beginning of an insert (becuase the beginning will be on the first line)
					// $after=str_replace(" (E'","' ('",$after);
					$line = $lines[$linenumber];
					$line = str_replace("', E'", "', '", $line);
					$output .= $line;

					// printf("inquotes: %d linenumber: %4d line: %s\n",$inquotes,$linenumber,$lines[$linenumber]);

					$c = substr_count($line, "'");
					// we have an odd number of ' marks
					if ($c % 2 != 0) {
						if ($inquotes)
							$inquotes = false;
						else
							$inquotes = true;
						// echo "inquotes=$inquotes\n";
					}
				} while (substr($lines[$linenumber], - 3, - 1) != ");" || $inquotes);
			}
		}
		if (substr($line, 0, 16) == "ALTER TABLE ONLY") {
			$line = preg_replace('/ ONLY/', '', $line);
			$line = str_replace("\"", "`", $line);
			$line = str_replace("public.", "", $line);
			$pkey = $line;

			$linenumber ++;
			if (! empty($lines[$linenumber])) {
				$line = $lines[$linenumber];
			} else {
				$line = '';
			}

			if (strstr($line, " PRIMARY KEY ") && substr($line, - 3, - 1) == ");") {
				$reg2 = array();
				if (preg_match('/ALTER TABLE ([^\s]+)/', $pkey, $reg2)) {
					if (empty($arrayofprimaryalreadyintabledef[$reg2[1]])) {
						// looks like we have a single line PRIMARY KEY definition, lets go ahead and add it
						$output .= str_replace("\n", "", $pkey);
						// the postgres and mysql syntax for this is (at least, in the example im looking at)
						// identical, so we can just add it as is.
						$output .= $line . "\n";
					} else {
						$output .= '-- ' . str_replace("\n", "", $pkey);
						$output .= '-- ' . $line . "\n";
					}
				} else {
					$output .= '-- ' . str_replace("\n", "", $pkey);
					$output .= '-- ' . $line . "\n";
				}
			}
		}

		// while we're here, we might as well catch CREATE INDEX as well
		if (substr($line, 0, 12) == "CREATE INDEX") {
			$matches = array();
			preg_match('/CREATE INDEX "?([a-zA-Z0-9_]*)"? ON "?([a-zA-Z0-9_\.]*)"? USING btree \((.*)\);/', $line, $matches);
			if (! empty($matches[3])) {
				$indexname = $matches[1];
				$tablename = str_replace('public.', '', $matches[2]);
				$columns = $matches[3];
				if ($tablename && $columns) {
					$output .= "ALTER TABLE `" . $tablename . "` ADD INDEX " . $indexname . "( {$columns} ) ;\n";
				}
			}
		}
		if (substr($line, 0, 19) == "CREATE UNIQUE INDEX") {
			$matches = array();
			preg_match('/CREATE UNIQUE INDEX "?([a-zA-Z0-9_]*)"? ON "?([a-zA-Z0-9_\.]*)"? USING btree \((.*)\);/', $line, $matches);
			if (! empty($matches[3])) {
				$indexname = $matches[1];
				$tablename = str_replace('public.', '', $matches[2]);
				$columns = str_replace('"', '', $matches[3]);
				if ($tablename && $columns) {
					$output .= "ALTER TABLE `" . $tablename . "` ADD UNIQUE INDEX " . $indexname . " ( {$columns} ) ;\n";
				}
			}
		}

		if (substr($line, 0, 13) == 'DROP DATABASE')
			$output .= $line;

		if (substr($line, 0, 15) == 'CREATE DATABASE') {
			$matches = array();
			preg_match('/CREATE DATABASE ([a-zA-Z0-9_]*) .* ENCODING = \'(.*)\'/', $line, $matches);
			$output .= "CREATE DATABASE `$matches[1]` DEFAULT CHARACTER SET $matches[2];\n\n";
		}

		if (substr($line, 0, 8) == '\\connect') {
			$matches = array();
			preg_match('/connect ([a-zA-Z0-9_]*)/', $line, $matches);
			$output .= "USE `$matches[1]`;\n\n";
		}

		if (substr($line, 0, 5) == 'COPY ') {
			$matches = array();
			preg_match('/COPY (.*) FROM stdin/', $line, $matches);
			$heads = str_replace('"', "`", $matches[1]);
			$values = array();
			$in_insert = true;
		} elseif ($in_insert) {
			if ($line == "\\.\n") {
				$in_insert = false;
				if ($values) {
					$output .= "INSERT INTO $heads VALUES\n" . implode(",\n", $values) . ";\n\n";
				}
			} else {
				$vals = explode('	', $line);
				foreach ($vals as $i => $val) {
					$vals[$i] = ($val == '\\N') ? 'NULL' : "'" . str_replace("'", "\\'", trim($val)) . "'";
				}
				$values[] = '(' . implode(',', $vals) . ')';
				if (count($values) >= 1000) {
					$output .= "INSERT INTO $heads VALUES\n" . implode(",\n", $values) . ";\n";
					$values = array();
				}
			}
		}

		$linenumber ++;
	}

	return array('output' => $output,'outputatend' => $outputatend);
}
