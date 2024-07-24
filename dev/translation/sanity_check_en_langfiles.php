#!/usr/bin/env php
<?php
/* Copyright (c) 2015 Tommaso Basilici          <t.basilici@19.coop>
 * Copyright (c) 2015 Laurent Destailleur       <eldy@destailleur.fr>
 * Copyright (C) 2014-2016  Juanjo Menent       <jmenent@2byte.es>
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
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

$sapi_type = php_sapi_name();
$script_file = basename(__FILE__);
$path=dirname(__FILE__).'/';

$web=0;

// Test if batch mode
if (substr($sapi_type, 0, 3) == 'cgi') {
	$web=1;
}


if ($web) {
	echo "<html>";
	echo "<head>";

	echo "<STYLE type=\"text/css\">

    table {
    	background: #f5f5f5;
    	border-collapse: separate;
    	box-shadow: inset 0 1px 0 #fff;
    	font-size: 12px;
    	line-height: 24px;
    	margin: 30px auto;
    	text-align: left;
    	width: 800px;
    }

    th {
    	background-color: #777;
    	border-left: 1px solid #555;
    	border-right: 1px solid #777;
    	border-top: 1px solid #555;
    	border-bottom: 1px solid #333;
    	color: #fff;
      	font-weight: bold;
    	padding: 10px 15px;
    	position: relative;
    	text-shadow: 0 1px 0 #000;
    }

    td {
    	border-right: 1px solid #fff;
    	border-left: 1px solid #e8e8e8;
    	border-top: 1px solid #fff;
    	border-bottom: 1px solid #e8e8e8;
    	padding: 10px 15px;
    	position: relative;
    }


    tr {
    	background-color: #f1f1f1;

    }

    tr:nth-child(odd) td {
    	background-color: #f1f1f1;
    }

    </STYLE>";

	echo "<body>";
}

echo "If you call this with argument \"unused=true\" it searches for the translation strings that exist in en_US but are never used.\n";
if ($web) {
	print "<br>";
}
echo "IMPORTANT: that can take quite a lot of time (up to 10 minutes), you need to tune the max_execution_time on your php.ini accordingly.\n";
if ($web) {
	print "<br>";
}



// STEP 1 - Search duplicates keys


// directory containing the php and lang files
$htdocs = $path."../../htdocs/";
$scripts = $path."../../scripts/";

// directory containing the english lang files
$workdir = $htdocs."langs/en_US/";


$files = scandir($workdir);
if (empty($files)) {
	echo "Can't scan workdir = ".$workdir;
	exit;
}

$dups=array();
$exludefiles = array('.','..','README');
$files = array_diff($files, $exludefiles);
// To force a file: $files=array('myfile.lang');
if (isset($argv[2])) {
	$files = array($argv[2]);
}
$langstrings_3d = array();
$langstrings_full = array();
foreach ($files as $file) {
	$path_file = pathinfo($file);
	// we're only interested in .lang files
	if ($path_file['extension']=='lang') {
		$content = file($workdir.$file);
		foreach ($content as $line => $row) {
			// don't want comment lines
			if (substr($row, 0, 1) !== '#') {
				// don't want lines without the separator (why should those even be here, anyway...)
				if (strpos($row, '=')!==false) {
					$row_array = explode('=', $row);		// $row_array[0] = key
					$langstrings_3d[$path_file['basename']][$line+1]=$row_array[0];
					$langstrings_3dtrans[$path_file['basename']][$line+1]=$row_array[1];
					$langstrings_full[]=$row_array[0];
					$langstrings_dist[$row_array[0]]=$row;
				}
			}
		}
	}
}

foreach ($langstrings_3d as $filename => $file) {
	foreach ($file as $linenum => $value) {
		$keys = array_keys($langstrings_full, $value);
		if (count($keys)>1) {
			foreach ($keys as $key) {
				$dups[$value][$filename][$linenum] = trim($langstrings_3dtrans[$filename][$linenum]);
			}
		}
	}
}

if ($web) {
	print "<h2>";
}
print "Duplicate strings in lang files in $workdir - ".count($dups)." found\n";
if ($web) {
	print "</h2>";
}

if ($web) {
	echo '<table border_bottom="1">'."\n";
	echo "<thead><tr><th align=\"center\">#</th><th>String</th><th>File and lines</th></thead>\n";
	echo "<tbody>\n";
}

$sduplicateinsamefile='';
$sinmainandother='';
$sininstallandadmin='';
$sother='';

$count = 0;
foreach ($dups as $string => $pages) {
	$count++;
	$s='';

	// Keyword $string
	if ($web) {
		$s.="<tr>";
	}
	if ($web) {
		$s.="<td align=\"center\">";
	}
	if ($web) {
		$s.=$count;
	}
	if ($web) {
		$s.="</td>";
	}
	if ($web) {
		$s.="<td>";
	}
	$s.=$string;
	if ($web) {
		$s.="</td>";
	}
	if ($web) {
		$s.="<td>";
	}
	if (! $web) {
		$s.= ' : ';
	}

	// Loop on each files keyword was found
	$duplicateinsamefile=0;
	$inmain=0;
	$inadmin=0;
	foreach ($pages as $file => $lines) {
		if ($file == 'main.lang') {
			$inmain=1;
			$inadmin=0;
		}
		if ($file == 'admin.lang' && ! $inmain) {
			$inadmin=1;
		}

		$s.=$file." ";

		// Loop on each line keword was found into file.
		$listoffilesforthisentry=array();
		foreach ($lines as $line => $translatedvalue) {
			if (!empty($listoffilesforthisentry[$file])) {
				$duplicateinsamefile=1;
			}
			$listoffilesforthisentry[$file]=1;

			$s.= "(".$line." - ".htmlentities($translatedvalue).") ";
		}
		if ($web) {
			$s.="<br>";
		}
	}
	if ($web) {
		$s.="</td></tr>";
	}
	$s.="\n";

	if ($duplicateinsamefile) {
		$sduplicateinsamefile .= $s;
	} elseif ($inmain) {
		$sinmainandother .= $s;
	} elseif ($inadmin) {
		$sininstallandadmin .= $s;
	} else {
		$sother .= $s;
	}
}

if (! $web) {
	print "\n***** Entries duplicated in same file\n";
}
print $sduplicateinsamefile;
if (! $web && empty($sduplicateinsamefile)) {
	print "None\n";
}
if (! $web) {
	print "\n";
}

if (! $web) {
	print "***** Entries in main and another (keep only entry in main)\n";
}
print $sinmainandother;
if (! $web && empty($sinmainandother)) {
	print "None\n";
}
if (! $web) {
	print "\n";
}

if (! $web) {
	print "***** Entries in admin and another\n";
}
print $sininstallandadmin;
if (! $web && empty($sininstallandadmin)) {
	print "None\n";
}
if (! $web) {
	print "\n";
}

if (! $web) {
	print "***** Other\n";
}
print $sother;
if (! $web && empty($sother)) {
	print "None\n";
}
if (! $web) {
	print "\n";
}

if ($web) {
	echo "</tbody>\n";
	echo "</table>\n";
}


// STEP 2 - Search key not used

if ((!empty($_REQUEST['unused']) && $_REQUEST['unused'] == 'true') || (isset($argv[1]) && $argv[1]=='unused=true')) {
	print "***** Strings in en_US that are never used:\n";

	$unused=array();
	foreach ($langstrings_dist as $value => $line) {
		$qualifiedforclean=1;
		// Check if we must keep this key to be into file for removal
		if (preg_match('/^Module\d+/', $value)) {
			$qualifiedforclean=0;
		}
		if (preg_match('/^Permission\d+/', $value)) {
			$qualifiedforclean=0;
		}
		if (preg_match('/^PermissionAdvanced\d+/', $value)) {
			$qualifiedforclean=0;
		}
		if (preg_match('/^ProfId\d+/', $value)) {
			$qualifiedforclean=0;
		}
		if (preg_match('/^Delays_/', $value)) {
			$qualifiedforclean=0;
		}
		if (preg_match('/^BarcodeDesc/', $value)) {
			$qualifiedforclean=0;
		}
		if (preg_match('/^Extrafield/', $value)) {
			$qualifiedforclean=0;
		}
		if (preg_match('/^LocalTax/', $value)) {
			$qualifiedforclean=0;
		}
		if (preg_match('/^Country/', $value)) {
			$qualifiedforclean=0;
		}
		if (preg_match('/^Civility/', $value)) {
			$qualifiedforclean=0;
		}
		if (preg_match('/^Currency/', $value)) {
			$qualifiedforclean=0;
		}
		if (preg_match('/^DemandReasonTypeSRC/', $value)) {
			$qualifiedforclean=0;
		}
		if (preg_match('/^PaperFormat/', $value)) {
			$qualifiedforclean=0;
		}
		if (preg_match('/^Duration/', $value)) {
			$qualifiedforclean=0;
		}
		if (preg_match('/^AmountLT/', $value)) {
			$qualifiedforclean=0;
		}
		if (preg_match('/^TotalLT/', $value)) {
			$qualifiedforclean=0;
		}
		if (preg_match('/^Month/', $value)) {
			$qualifiedforclean=0;
		}
		if (preg_match('/^MonthShort/', $value)) {
			$qualifiedforclean=0;
		}
		if (preg_match('/^Day\d/', $value)) {
			$qualifiedforclean=0;
		}
		if (preg_match('/^Short/', $value)) {
			$qualifiedforclean=0;
		}
		if (preg_match('/^ExportDataset_/', $value)) {
			$qualifiedforclean=0;
		}
		if (preg_match('/^ImportDataset_/', $value)) {
			$qualifiedforclean=0;
		}
		if (preg_match('/^ActionAC_/', $value)) {
			$qualifiedforclean=0;
		}
		if (preg_match('/^TypeLocaltax/', $value)) {
			$qualifiedforclean=0;
		}
		if (preg_match('/^StatusProspect/', $value)) {
			$qualifiedforclean=0;
		}
		if (preg_match('/^PL_/', $value)) {
			$qualifiedforclean=0;
		}
		if (preg_match('/^TE_/', $value)) {
			$qualifiedforclean=0;
		}
		if (preg_match('/^JuridicalStatus/', $value)) {
			$qualifiedforclean=0;
		}
		if (preg_match('/^CalcMode/', $value)) {
			$qualifiedforclean=0;
		}
		if (preg_match('/^newLT/', $value)) {
			$qualifiedforclean=0;
		}
		if (preg_match('/^LT[0-9]/', $value)) {
			$qualifiedforclean=0;
		}
		if (preg_match('/^TypeContact_contrat_/', $value)) {
			$qualifiedforclean=0;
		}
		if (preg_match('/^ErrorPriceExpression/', $value)) {
			$qualifiedforclean=0;
		}
		if (preg_match('/^Language_/', $value)) {
			$qualifiedforclean=0;
		}
		if (preg_match('/^DescADHERENT_/', $value)) {
			$qualifiedforclean=0;
		}
		if (preg_match('/^SubmitTranslation/', $value)) {
			$qualifiedforclean=0;
		}
		if (preg_match('/^ModuleCompanyCode/', $value)) {
			$qualifiedforclean=0;
		}
		if (preg_match('/InDolibarr$/', $value)) {
			$qualifiedforclean=0;
		}
		// admin.lang
		if (preg_match('/^DAV_ALLOW_PUBLIC_DIR/i', $value)) {
			$qualifiedforclean=0;
		}
		if (preg_match('/^DAV_ALLOW_ECM_DIR/i', $value)) {
			$qualifiedforclean=0;
		}
		// boxes.lang
		if (preg_match('/^BoxTitleLast/', $value)) {
			$qualifiedforclean=0;
		}
		if (preg_match('/^BoxTitleLatest/', $value)) {
			$qualifiedforclean=0;
		}
		// install.lang
		if (preg_match('/^KeepDefaultValues/', $value)) {
			$qualifiedforclean=0;
		}
		// mail.lang
		if (preg_match('/MailingModuleDesc/i', $value)) {
			$qualifiedforclean=0;
		}
		// main.lang
		if (preg_match('/^Duration/', $value)) {
			$qualifiedforclean=0;
		}
		if (preg_match('/^FormatDate/', $value)) {
			$qualifiedforclean=0;
		}
		if (preg_match('/^DateFormat/', $value)) {
			$qualifiedforclean=0;
		}
		if (preg_match('/^.b$/', $value)) {
			$qualifiedforclean=0;
		}
		if (preg_match('/^.*Bytes$/', $value)) {
			$qualifiedforclean=0;
		}
		if (preg_match('/^NoteSomeFeaturesAreDisabled/', $value)) {
			$qualifiedforclean=0;
		}
		if (preg_match('/^(DoTest|Under|Limits|Cards|CurrentValue|DateLimit|DateAndHour|NbOfLines|NbOfObjects|NbOfReferes|TotalTTCShort|VATs)/', $value)) {
			$qualifiedforclean=0;
		}
		// modulebuilder
		if (preg_match('/^ModuleBuilderDesc/', $value)) {
			$qualifiedforclean=0;
		}
		// orders
		if (preg_match('/^OrderSource/', $value)) {
			$qualifiedforclean=0;
		}
		if (preg_match('/^TypeContact_/', $value)) {
			$qualifiedforclean=0;
		}
		// other.lang
		if (preg_match('/^Notify_/', $value)) {
			$qualifiedforclean=0;
		}
		if (preg_match('/^PredefinedMail/', $value)) {
			$qualifiedforclean=0;
		}
		if (preg_match('/^DemoCompany/', $value)) {
			$qualifiedforclean=0;
		}
		if (preg_match('/^WeightUnit/', $value)) {
			$qualifiedforclean=0;
		}
		if (preg_match('/^LengthUnit/', $value)) {
			$qualifiedforclean=0;
		}
		if (preg_match('/^SurfaceUnit/', $value)) {
			$qualifiedforclean=0;
		}
		if (preg_match('/^VolumeUnit/', $value)) {
			$qualifiedforclean=0;
		}
		if (preg_match('/^SizeUnit/', $value)) {
			$qualifiedforclean=0;
		}
		if (preg_match('/^EMailText/', $value)) {
			$qualifiedforclean=0;
		}
		if (preg_match('/ById$/', $value)) {
			$qualifiedforclean=0;
		}
		if (preg_match('/ByLogin$/', $value)) {
			$qualifiedforclean=0;
		}
		// printing
		if (preg_match('/PrintingDriverDesc$/', $value)) {
			$qualifiedforclean=0;
		}
		if (preg_match('/PrintTestDesc$/', $value)) {
			$qualifiedforclean=0;
		}
		// products
		if (preg_match('/GlobalVariableUpdaterType$/', $value)) {
			$qualifiedforclean=0;
		}
		if (preg_match('/GlobalVariableUpdaterHelp$/', $value)) {
			$qualifiedforclean=0;
		}
		if (preg_match('/OppStatus/', $value)) {
			$qualifiedforclean=0;
		}
		if (preg_match('/AvailabilityType/', $value)) {
			$qualifiedforclean=0;
		}
		if (preg_match('/CardProduct/', $value)) {
			$qualifiedforclean=0;
		}

		if (preg_match('/sms/i', $value)) {
			$qualifiedforclean=0;
		}
		if (preg_match('/TF_/i', $value)) {
			$qualifiedforclean=0;
		}
		if (preg_match('/WithBankUsing/i', $value)) {
			$qualifiedforclean=0;
		}
		if (preg_match('/descWORKFLOW_/i', $value)) {
			$qualifiedforclean=0;
		}

		if (! $qualifiedforclean) {
			continue;
		}

		//$search = '\'trans("'.$value.'")\'';
		$search = '-e "\''.$value.'\'" -e \'"'.$value.'"\' -e "('.$value.')" -e "('.$value.',"';
		$string =  'grep -R -m 1 -F --exclude=includes/* --include=*.php '.$search.' '.$htdocs.'* '.$scripts.'*';
		//print $string."<br>\n";
		exec($string, $output);
		if (empty($output)) {
			$unused[$value] = $line;
			echo $line;        // $trad contains the \n
		} else {
			unset($output);
			//print 'X'.$output.'Y';
		}
	}

	if (empty($unused)) {
		print "No string not used found.\n";
	} else {
		$filetosave='/tmp/'.($argv[2] ? $argv[2] : "").'notused.lang';
		print "Strings in en_US that are never used are saved into file ".$filetosave.":\n";
		file_put_contents($filetosave, implode("", $unused));
		print "To remove from original file, run command :\n";
		if (($argv[2] ? $argv[2] : "")) {
			print 'cd htdocs/langs/en_US; mv '.($argv[2] ? $argv[2] : "")." ".($argv[2] ? $argv[2] : "").".tmp; ";
		}
		print "diff ".($argv[2] ? $argv[2] : "").".tmp ".$filetosave." | grep \< | cut  -b 3- > ".($argv[2] ? $argv[2] : "");
		if (($argv[2] ? $argv[2] : "")) {
			print "; rm ".($argv[2] ? $argv[2] : "").".tmp;\n";
		}
	}
}

echo "\n";
if ($web) {
	echo "</body>\n";
	echo "</html>\n";
}

exit;
