<?php
/*******************************************************************************
* Utility to generate font definition files                                    *
*                                                                              *
* Version: 1.31                                                                *
* Date:    2019-12-07                                                          *
* Author:  Olivier PLATHEY                                                     *
*******************************************************************************/

require('ttfparser.php');

function Message($txt, $severity='')
{
	if(PHP_SAPI=='cli')
	{
		if($severity)
			echo "$severity: ";
		echo "$txt\n";
	}
	else
	{
		if($severity)
			echo "<b>$severity</b>: ";
		echo "$txt<br>";
	}
}

function Notice($txt)
{
	Message($txt, 'Notice');
}

function Warning($txt)
{
	Message($txt, 'Warning');
}

function Error($txt)
{
	Message($txt, 'Error');
	exit;
}

function LoadMap($enc)
{
	$file = dirname(__FILE__).'/'.strtolower($enc).'.map';
	$a = file($file);
	if(empty($a))
		Error('Encoding not found: '.$enc);
	$map = array_fill(0, 256, array('uv'=>-1, 'name'=>'.notdef'));
	foreach($a as $line)
	{
		$e = explode(' ', rtrim($line));
		$c = hexdec(substr($e[0],1));
		$uv = hexdec(substr($e[1],2));
		$name = $e[2];
		$map[$c] = array('uv'=>$uv, 'name'=>$name);
	}
	return $map;
}

function GetInfoFromTrueType($file, $embed, $subset, $map)
{
	// Return information from a TrueType font
	try
	{
		$ttf = new TTFParser($file);
		$ttf->Parse();
	}
	catch(Exception $e)
	{
		Error($e->getMessage());
	}
	if($embed)
	{
		if(!$ttf->embeddable)
			Error('Font license does not allow embedding');
		if($subset)
		{
			$chars = array();
			foreach($map as $v)
			{
				if($v['name']!='.notdef')
					$chars[] = $v['uv'];
			}
			$ttf->Subset($chars);
			$info['Data'] = $ttf->Build();
		}
		else
			$info['Data'] = file_get_contents($file);
		$info['OriginalSize'] = strlen($info['Data']);
	}
	$k = 1000/$ttf->unitsPerEm;
	$info['FontName'] = $ttf->postScriptName;
	$info['Bold'] = $ttf->bold;
	$info['ItalicAngle'] = $ttf->italicAngle;
	$info['IsFixedPitch'] = $ttf->isFixedPitch;
	$info['Ascender'] = round($k*$ttf->typoAscender);
	$info['Descender'] = round($k*$ttf->typoDescender);
	$info['UnderlineThickness'] = round($k*$ttf->underlineThickness);
	$info['UnderlinePosition'] = round($k*$ttf->underlinePosition);
	$info['FontBBox'] = array(round($k*$ttf->xMin), round($k*$ttf->yMin), round($k*$ttf->xMax), round($k*$ttf->yMax));
	$info['CapHeight'] = round($k*$ttf->capHeight);
	$info['MissingWidth'] = round($k*$ttf->glyphs[0]['w']);
	$widths = array_fill(0, 256, $info['MissingWidth']);
	foreach($map as $c=>$v)
	{
		if($v['name']!='.notdef')
		{
			if(isset($ttf->chars[$v['uv']]))
			{
				$id = $ttf->chars[$v['uv']];
				$w = $ttf->glyphs[$id]['w'];
				$widths[$c] = round($k*$w);
			}
			else
				Warning('Character '.$v['name'].' is missing');
		}
	}
	$info['Widths'] = $widths;
	return $info;
}

function GetInfoFromType1($file, $embed, $map)
{
	// Return information from a Type1 font
	if($embed)
	{
		$f = fopen($file, 'rb');
		if(!$f)
			Error('Can\'t open font file');
		// Read first segment
		$a = unpack('Cmarker/Ctype/Vsize', fread($f,6));
		if($a['marker']!=128)
			Error('Font file is not a valid binary Type1');
		$size1 = $a['size'];
		$data = fread($f, $size1);
		// Read second segment
		$a = unpack('Cmarker/Ctype/Vsize', fread($f,6));
		if($a['marker']!=128)
			Error('Font file is not a valid binary Type1');
		$size2 = $a['size'];
		$data .= fread($f, $size2);
		fclose($f);
		$info['Data'] = $data;
		$info['Size1'] = $size1;
		$info['Size2'] = $size2;
	}

	$afm = substr($file, 0, -3).'afm';
	if(!file_exists($afm))
		Error('AFM font file not found: '.$afm);
	$a = file($afm);
	if(empty($a))
		Error('AFM file empty or not readable');
	foreach($a as $line)
	{
		$e = explode(' ', rtrim($line));
		if(count($e)<2)
			continue;
		$entry = $e[0];
		if($entry=='C')
		{
			$w = $e[4];
			$name = $e[7];
			$cw[$name] = $w;
		}
		elseif($entry=='FontName')
			$info['FontName'] = $e[1];
		elseif($entry=='Weight')
			$info['Weight'] = $e[1];
		elseif($entry=='ItalicAngle')
			$info['ItalicAngle'] = (int)$e[1];
		elseif($entry=='Ascender')
			$info['Ascender'] = (int)$e[1];
		elseif($entry=='Descender')
			$info['Descender'] = (int)$e[1];
		elseif($entry=='UnderlineThickness')
			$info['UnderlineThickness'] = (int)$e[1];
		elseif($entry=='UnderlinePosition')
			$info['UnderlinePosition'] = (int)$e[1];
		elseif($entry=='IsFixedPitch')
			$info['IsFixedPitch'] = ($e[1]=='true');
		elseif($entry=='FontBBox')
			$info['FontBBox'] = array((int)$e[1], (int)$e[2], (int)$e[3], (int)$e[4]);
		elseif($entry=='CapHeight')
			$info['CapHeight'] = (int)$e[1];
		elseif($entry=='StdVW')
			$info['StdVW'] = (int)$e[1];
	}

	if(!isset($info['FontName']))
		Error('FontName missing in AFM file');
	if(!isset($info['Ascender']))
		$info['Ascender'] = $info['FontBBox'][3];
	if(!isset($info['Descender']))
		$info['Descender'] = $info['FontBBox'][1];
	$info['Bold'] = isset($info['Weight']) && preg_match('/bold|black/i', $info['Weight']);
	if(isset($cw['.notdef']))
		$info['MissingWidth'] = $cw['.notdef'];
	else
		$info['MissingWidth'] = 0;
	$widths = array_fill(0, 256, $info['MissingWidth']);
	foreach($map as $c=>$v)
	{
		if($v['name']!='.notdef')
		{
			if(isset($cw[$v['name']]))
				$widths[$c] = $cw[$v['name']];
			else
				Warning('Character '.$v['name'].' is missing');
		}
	}
	$info['Widths'] = $widths;
	return $info;
}

function MakeFontDescriptor($info)
{
	// Ascent
	$fd = "array('Ascent'=>".$info['Ascender'];
	// Descent
	$fd .= ",'Descent'=>".$info['Descender'];
	// CapHeight
	if(!empty($info['CapHeight']))
		$fd .= ",'CapHeight'=>".$info['CapHeight'];
	else
		$fd .= ",'CapHeight'=>".$info['Ascender'];
	// Flags
	$flags = 0;
	if($info['IsFixedPitch'])
		$flags += 1<<0;
	$flags += 1<<5;
	if($info['ItalicAngle']!=0)
		$flags += 1<<6;
	$fd .= ",'Flags'=>".$flags;
	// FontBBox
	$fbb = $info['FontBBox'];
	$fd .= ",'FontBBox'=>'[".$fbb[0].' '.$fbb[1].' '.$fbb[2].' '.$fbb[3]."]'";
	// ItalicAngle
	$fd .= ",'ItalicAngle'=>".$info['ItalicAngle'];
	// StemV
	if(isset($info['StdVW']))
		$stemv = $info['StdVW'];
	elseif($info['Bold'])
		$stemv = 120;
	else
		$stemv = 70;
	$fd .= ",'StemV'=>".$stemv;
	// MissingWidth
	$fd .= ",'MissingWidth'=>".$info['MissingWidth'].')';
	return $fd;
}

function MakeWidthArray($widths)
{
	$s = "array(\n\t";
	for($c=0;$c<=255;$c++)
	{
		if(chr($c)=="'")
			$s .= "'\\''";
		elseif(chr($c)=="\\")
			$s .= "'\\\\'";
		elseif($c>=32 && $c<=126)
			$s .= "'".chr($c)."'";
		else
			$s .= "chr($c)";
		$s .= '=>'.$widths[$c];
		if($c<255)
			$s .= ',';
		if(($c+1)%22==0)
			$s .= "\n\t";
	}
	$s .= ')';
	return $s;
}

function MakeFontEncoding($map)
{
	// Build differences from reference encoding
	$ref = LoadMap('cp1252');
	$s = '';
	$last = 0;
	for($c=32;$c<=255;$c++)
	{
		if($map[$c]['name']!=$ref[$c]['name'])
		{
			if($c!=$last+1)
				$s .= $c.' ';
			$last = $c;
			$s .= '/'.$map[$c]['name'].' ';
		}
	}
	return rtrim($s);
}

function MakeUnicodeArray($map)
{
	// Build mapping to Unicode values
	$ranges = array();
	foreach($map as $c=>$v)
	{
		$uv = $v['uv'];
		if($uv!=-1)
		{
			if(isset($range))
			{
				if($c==$range[1]+1 && $uv==$range[3]+1)
				{
					$range[1]++;
					$range[3]++;
				}
				else
				{
					$ranges[] = $range;
					$range = array($c, $c, $uv, $uv);
				}
			}
			else
				$range = array($c, $c, $uv, $uv);
		}
	}
	$ranges[] = $range;

	foreach($ranges as $range)
	{
		if(isset($s))
			$s .= ',';
		else
			$s = 'array(';
		$s .= $range[0].'=>';
		$nb = $range[1]-$range[0]+1;
		if($nb>1)
			$s .= 'array('.$range[2].','.$nb.')';
		else
			$s .= $range[2];
	}
	$s .= ')';
	return $s;
}

function SaveToFile($file, $s, $mode)
{
	$f = fopen($file, 'w'.$mode);
	if(!$f)
		Error('Can\'t write to file '.$file);
	fwrite($f, $s);
	fclose($f);
}

function MakeDefinitionFile($file, $type, $enc, $embed, $subset, $map, $info)
{
	$s = "<?php\n";
	$s .= '$type = \''.$type."';\n";
	$s .= '$name = \''.$info['FontName']."';\n";
	$s .= '$desc = '.MakeFontDescriptor($info).";\n";
	$s .= '$up = '.$info['UnderlinePosition'].";\n";
	$s .= '$ut = '.$info['UnderlineThickness'].";\n";
	$s .= '$cw = '.MakeWidthArray($info['Widths']).";\n";
	$s .= '$enc = \''.$enc."';\n";
	$diff = MakeFontEncoding($map);
	if($diff)
		$s .= '$diff = \''.$diff."';\n";
	$s .= '$uv = '.MakeUnicodeArray($map).";\n";
	if($embed)
	{
		$s .= '$file = \''.$info['File']."';\n";
		if($type=='Type1')
		{
			$s .= '$size1 = '.$info['Size1'].";\n";
			$s .= '$size2 = '.$info['Size2'].";\n";
		}
		else
		{
			$s .= '$originalsize = '.$info['OriginalSize'].";\n";
			if($subset)
				$s .= "\$subsetted = true;\n";
		}
	}
	$s .= "?>\n";
	SaveToFile($file, $s, 't');
}

function MakeFont($fontfile, $enc='cp1252', $embed=true, $subset=true)
{
	// Generate a font definition file
	if(!file_exists($fontfile))
		Error('Font file not found: '.$fontfile);
	$ext = strtolower(substr($fontfile,-3));
	if($ext=='ttf' || $ext=='otf')
		$type = 'TrueType';
	elseif($ext=='pfb')
		$type = 'Type1';
	else
		Error('Unrecognized font file extension: '.$ext);

	$map = LoadMap($enc);

	if($type=='TrueType')
		$info = GetInfoFromTrueType($fontfile, $embed, $subset, $map);
	else
		$info = GetInfoFromType1($fontfile, $embed, $map);

	$basename = substr(basename($fontfile), 0, -4);
	if($embed)
	{
		if(function_exists('gzcompress'))
		{
			$file = $basename.'.z';
			SaveToFile($file, gzcompress($info['Data']), 'b');
			$info['File'] = $file;
			Message('Font file compressed: '.$file);
		}
		else
		{
			$info['File'] = basename($fontfile);
			$subset = false;
			Notice('Font file could not be compressed (zlib extension not available)');
		}
	}

	MakeDefinitionFile($basename.'.php', $type, $enc, $embed, $subset, $map, $info);
	Message('Font definition file generated: '.$basename.'.php');
}

if(PHP_SAPI=='cli')
{
	// Command-line interface
	ini_set('log_errors', '0');
	if($argc==1)
		die("Usage: php makefont.php fontfile [encoding] [embed] [subset]\n");
	$fontfile = $argv[1];
	if($argc>=3)
		$enc = $argv[2];
	else
		$enc = 'cp1252';
	if($argc>=4)
		$embed = ($argv[3]=='true' || $argv[3]=='1');
	else
		$embed = true;
	if($argc>=5)
		$subset = ($argv[4]=='true' || $argv[4]=='1');
	else
		$subset = true;
	MakeFont($fontfile, $enc, $embed, $subset);
}
?>
