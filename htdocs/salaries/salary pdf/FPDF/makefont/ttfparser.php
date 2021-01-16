<?php
/*******************************************************************************
* Class to parse and subset TrueType fonts                                     *
*                                                                              *
* Version: 1.1                                                                 *
* Date:    2015-11-29                                                          *
* Author:  Olivier PLATHEY                                                     *
*******************************************************************************/

class TTFParser
{
	protected $f;
	protected $tables;
	protected $numberOfHMetrics;
	protected $numGlyphs;
	protected $glyphNames;
	protected $indexToLocFormat;
	protected $subsettedChars;
	protected $subsettedGlyphs;
	public $chars;
	public $glyphs;
	public $unitsPerEm;
	public $xMin, $yMin, $xMax, $yMax;
	public $postScriptName;
	public $embeddable;
	public $bold;
	public $typoAscender;
	public $typoDescender;
	public $capHeight;
	public $italicAngle;
	public $underlinePosition;
	public $underlineThickness;
	public $isFixedPitch;

	function __construct($file)
	{
		$this->f = fopen($file, 'rb');
		if(!$this->f)
			$this->Error('Can\'t open file: '.$file);
	}

	function __destruct()
	{
		if(is_resource($this->f))
			fclose($this->f);
	}

	function Parse()
	{
		$this->ParseOffsetTable();
		$this->ParseHead();
		$this->ParseHhea();
		$this->ParseMaxp();
		$this->ParseHmtx();
		$this->ParseLoca();
		$this->ParseGlyf();
		$this->ParseCmap();
		$this->ParseName();
		$this->ParseOS2();
		$this->ParsePost();
	}

	function ParseOffsetTable()
	{
		$version = $this->Read(4);
		if($version=='OTTO')
			$this->Error('OpenType fonts based on PostScript outlines are not supported');
		if($version!="\x00\x01\x00\x00")
			$this->Error('Unrecognized file format');
		$numTables = $this->ReadUShort();
		$this->Skip(3*2); // searchRange, entrySelector, rangeShift
		$this->tables = array();
		for($i=0;$i<$numTables;$i++)
		{
			$tag = $this->Read(4);
			$checkSum = $this->Read(4);
			$offset = $this->ReadULong();
			$length = $this->ReadULong(4);
			$this->tables[$tag] = array('offset'=>$offset, 'length'=>$length, 'checkSum'=>$checkSum);
		}
	}	

	function ParseHead()
	{
		$this->Seek('head');
		$this->Skip(3*4); // version, fontRevision, checkSumAdjustment
		$magicNumber = $this->ReadULong();
		if($magicNumber!=0x5F0F3CF5)
			$this->Error('Incorrect magic number');
		$this->Skip(2); // flags
		$this->unitsPerEm = $this->ReadUShort();
		$this->Skip(2*8); // created, modified
		$this->xMin = $this->ReadShort();
		$this->yMin = $this->ReadShort();
		$this->xMax = $this->ReadShort();
		$this->yMax = $this->ReadShort();
		$this->Skip(3*2); // macStyle, lowestRecPPEM, fontDirectionHint
		$this->indexToLocFormat = $this->ReadShort();
	}

	function ParseHhea()
	{
		$this->Seek('hhea');
		$this->Skip(4+15*2);
		$this->numberOfHMetrics = $this->ReadUShort();
	}

	function ParseMaxp()
	{
		$this->Seek('maxp');
		$this->Skip(4);
		$this->numGlyphs = $this->ReadUShort();
	}

	function ParseHmtx()
	{
		$this->Seek('hmtx');
		$this->glyphs = array();
		for($i=0;$i<$this->numberOfHMetrics;$i++)
		{
			$advanceWidth = $this->ReadUShort();
			$lsb = $this->ReadShort();
			$this->glyphs[$i] = array('w'=>$advanceWidth, 'lsb'=>$lsb);
		}
		for($i=$this->numberOfHMetrics;$i<$this->numGlyphs;$i++)
		{
			$lsb = $this->ReadShort();
			$this->glyphs[$i] = array('w'=>$advanceWidth, 'lsb'=>$lsb);
		}
	}

	function ParseLoca()
	{
		$this->Seek('loca');
		$offsets = array();
		if($this->indexToLocFormat==0)
		{
			// Short format
			for($i=0;$i<=$this->numGlyphs;$i++)
				$offsets[] = 2*$this->ReadUShort();
		}
		else
		{
			// Long format
			for($i=0;$i<=$this->numGlyphs;$i++)
				$offsets[] = $this->ReadULong();
		}
		for($i=0;$i<$this->numGlyphs;$i++)
		{
			$this->glyphs[$i]['offset'] = $offsets[$i];
			$this->glyphs[$i]['length'] = $offsets[$i+1] - $offsets[$i];
		}
	}

	function ParseGlyf()
	{
		$tableOffset = $this->tables['glyf']['offset'];
		foreach($this->glyphs as &$glyph)
		{
			if($glyph['length']>0)
			{
				fseek($this->f, $tableOffset+$glyph['offset'], SEEK_SET);
				if($this->ReadShort()<0)
				{
					// Composite glyph
					$this->Skip(4*2); // xMin, yMin, xMax, yMax
					$offset = 5*2;
					$a = array();
					do
					{
						$flags = $this->ReadUShort();
						$index = $this->ReadUShort();
						$a[$offset+2] = $index;
						if($flags & 1) // ARG_1_AND_2_ARE_WORDS
							$skip = 2*2;
						else
							$skip = 2;
						if($flags & 8) // WE_HAVE_A_SCALE
							$skip += 2;
						elseif($flags & 64) // WE_HAVE_AN_X_AND_Y_SCALE
							$skip += 2*2;
						elseif($flags & 128) // WE_HAVE_A_TWO_BY_TWO
							$skip += 4*2;
						$this->Skip($skip);
						$offset += 2*2 + $skip;
					}
					while($flags & 32); // MORE_COMPONENTS
					$glyph['components'] = $a;
				}
			}
		}
	}

	function ParseCmap()
	{
		$this->Seek('cmap');
		$this->Skip(2); // version
		$numTables = $this->ReadUShort();
		$offset31 = 0;
		for($i=0;$i<$numTables;$i++)
		{
			$platformID = $this->ReadUShort();
			$encodingID = $this->ReadUShort();
			$offset = $this->ReadULong();
			if($platformID==3 && $encodingID==1)
				$offset31 = $offset;
		}
		if($offset31==0)
			$this->Error('No Unicode encoding found');

		$startCount = array();
		$endCount = array();
		$idDelta = array();
		$idRangeOffset = array();
		$this->chars = array();
		fseek($this->f, $this->tables['cmap']['offset']+$offset31, SEEK_SET);
		$format = $this->ReadUShort();
		if($format!=4)
			$this->Error('Unexpected subtable format: '.$format);
		$this->Skip(2*2); // length, language
		$segCount = $this->ReadUShort()/2;
		$this->Skip(3*2); // searchRange, entrySelector, rangeShift
		for($i=0;$i<$segCount;$i++)
			$endCount[$i] = $this->ReadUShort();
		$this->Skip(2); // reservedPad
		for($i=0;$i<$segCount;$i++)
			$startCount[$i] = $this->ReadUShort();
		for($i=0;$i<$segCount;$i++)
			$idDelta[$i] = $this->ReadShort();
		$offset = ftell($this->f);
		for($i=0;$i<$segCount;$i++)
			$idRangeOffset[$i] = $this->ReadUShort();

		for($i=0;$i<$segCount;$i++)
		{
			$c1 = $startCount[$i];
			$c2 = $endCount[$i];
			$d = $idDelta[$i];
			$ro = $idRangeOffset[$i];
			if($ro>0)
				fseek($this->f, $offset+2*$i+$ro, SEEK_SET);
			for($c=$c1;$c<=$c2;$c++)
			{
				if($c==0xFFFF)
					break;
				if($ro>0)
				{
					$gid = $this->ReadUShort();
					if($gid>0)
						$gid += $d;
				}
				else
					$gid = $c+$d;
				if($gid>=65536)
					$gid -= 65536;
				if($gid>0)
					$this->chars[$c] = $gid;
			}
		}
	}

	function ParseName()
	{
		$this->Seek('name');
		$tableOffset = $this->tables['name']['offset'];
		$this->postScriptName = '';
		$this->Skip(2); // format
		$count = $this->ReadUShort();
		$stringOffset = $this->ReadUShort();
		for($i=0;$i<$count;$i++)
		{
			$this->Skip(3*2); // platformID, encodingID, languageID
			$nameID = $this->ReadUShort();
			$length = $this->ReadUShort();
			$offset = $this->ReadUShort();
			if($nameID==6)
			{
				// PostScript name
				fseek($this->f, $tableOffset+$stringOffset+$offset, SEEK_SET);
				$s = $this->Read($length);
				$s = str_replace(chr(0), '', $s);
				$s = preg_replace('|[ \[\](){}<>/%]|', '', $s);
				$this->postScriptName = $s;
				break;
			}
		}
		if($this->postScriptName=='')
			$this->Error('PostScript name not found');
	}

	function ParseOS2()
	{
		$this->Seek('OS/2');
		$version = $this->ReadUShort();
		$this->Skip(3*2); // xAvgCharWidth, usWeightClass, usWidthClass
		$fsType = $this->ReadUShort();
		$this->embeddable = ($fsType!=2) && ($fsType & 0x200)==0;
		$this->Skip(11*2+10+4*4+4);
		$fsSelection = $this->ReadUShort();
		$this->bold = ($fsSelection & 32)!=0;
		$this->Skip(2*2); // usFirstCharIndex, usLastCharIndex
		$this->typoAscender = $this->ReadShort();
		$this->typoDescender = $this->ReadShort();
		if($version>=2)
		{
			$this->Skip(3*2+2*4+2);
			$this->capHeight = $this->ReadShort();
		}
		else
			$this->capHeight = 0;
	}

	function ParsePost()
	{
		$this->Seek('post');
		$version = $this->ReadULong();
		$this->italicAngle = $this->ReadShort();
		$this->Skip(2); // Skip decimal part
		$this->underlinePosition = $this->ReadShort();
		$this->underlineThickness = $this->ReadShort();
		$this->isFixedPitch = ($this->ReadULong()!=0);
		if($version==0x20000)
		{
			// Extract glyph names
			$this->Skip(4*4); // min/max usage
			$this->Skip(2); // numberOfGlyphs
			$glyphNameIndex = array();
			$names = array();
			$numNames = 0;
			for($i=0;$i<$this->numGlyphs;$i++)
			{
				$index = $this->ReadUShort();
				$glyphNameIndex[] = $index;
				if($index>=258 && $index-257>$numNames)
					$numNames = $index-257;
			}
			for($i=0;$i<$numNames;$i++)
			{
				$len = ord($this->Read(1));
				$names[] = $this->Read($len);
			}
			foreach($glyphNameIndex as $i=>$index)
			{
				if($index>=258)
					$this->glyphs[$i]['name'] = $names[$index-258];
				else
					$this->glyphs[$i]['name'] = $index;
			}
			$this->glyphNames = true;
		}
		else
			$this->glyphNames = false;
	}

	function Subset($chars)
	{
/*		$chars = array_keys($this->chars);
		$this->subsettedChars = $chars;
		$this->subsettedGlyphs = array();
		for($i=0;$i<$this->numGlyphs;$i++)
		{
			$this->subsettedGlyphs[] = $i;
			$this->glyphs[$i]['ssid'] = $i;
		}*/

		$this->AddGlyph(0);
		$this->subsettedChars = array();
		foreach($chars as $char)
		{
			if(isset($this->chars[$char]))
			{
				$this->subsettedChars[] = $char;
				$this->AddGlyph($this->chars[$char]);
			}
		}
	}

	function AddGlyph($id)
	{
		if(!isset($this->glyphs[$id]['ssid']))
		{
			$this->glyphs[$id]['ssid'] = count($this->subsettedGlyphs);
			$this->subsettedGlyphs[] = $id;
			if(isset($this->glyphs[$id]['components']))
			{
				foreach($this->glyphs[$id]['components'] as $cid)
					$this->AddGlyph($cid);
			}
		}
	}

	function Build()
	{
		$this->BuildCmap();
		$this->BuildHhea();
		$this->BuildHmtx();
		$this->BuildLoca();
		$this->BuildGlyf();
		$this->BuildMaxp();
		$this->BuildPost();
		return $this->BuildFont();
	}

	function BuildCmap()
	{
		if(!isset($this->subsettedChars))
			return;

		// Divide charset in contiguous segments
		$chars = $this->subsettedChars;
		sort($chars);
		$segments = array();
		$segment = array($chars[0], $chars[0]);
		for($i=1;$i<count($chars);$i++)
		{
			if($chars[$i]>$segment[1]+1)
			{
				$segments[] = $segment;
				$segment = array($chars[$i], $chars[$i]);
			}
			else
				$segment[1]++;
		}
		$segments[] = $segment;
		$segments[] = array(0xFFFF, 0xFFFF);
		$segCount = count($segments);

		// Build a Format 4 subtable
		$startCount = array();
		$endCount = array();
		$idDelta = array();
		$idRangeOffset = array();
		$glyphIdArray = '';
		for($i=0;$i<$segCount;$i++)
		{
			list($start, $end) = $segments[$i];
			$startCount[] = $start;
			$endCount[] = $end;
			if($start!=$end)
			{
				// Segment with multiple chars
				$idDelta[] = 0;
				$idRangeOffset[] = strlen($glyphIdArray) + ($segCount-$i)*2;
				for($c=$start;$c<=$end;$c++)
				{
					$ssid = $this->glyphs[$this->chars[$c]]['ssid'];
					$glyphIdArray .= pack('n', $ssid);
				}
			}
			else
			{
				// Segment with a single char
				if($start<0xFFFF)
					$ssid = $this->glyphs[$this->chars[$start]]['ssid'];
				else
					$ssid = 0;
				$idDelta[] = $ssid - $start;
				$idRangeOffset[] = 0;
			}
		}
		$entrySelector = 0;
		$n = $segCount;
		while($n!=1)
		{
			$n = $n>>1;
			$entrySelector++;
		}
		$searchRange = (1<<$entrySelector)*2;
		$rangeShift = 2*$segCount - $searchRange;
		$cmap = pack('nnnn', 2*$segCount, $searchRange, $entrySelector, $rangeShift);
		foreach($endCount as $val)
			$cmap .= pack('n', $val);
		$cmap .= pack('n', 0); // reservedPad
		foreach($startCount as $val)
			$cmap .= pack('n', $val);
		foreach($idDelta as $val)
			$cmap .= pack('n', $val);
		foreach($idRangeOffset as $val)
			$cmap .= pack('n', $val);
		$cmap .= $glyphIdArray;

		$data = pack('nn', 0, 1); // version, numTables
		$data .= pack('nnN', 3, 1, 12); // platformID, encodingID, offset
		$data .= pack('nnn', 4, 6+strlen($cmap), 0); // format, length, language
		$data .= $cmap;
		$this->SetTable('cmap', $data);
	}

	function BuildHhea()
	{
		$this->LoadTable('hhea');
		$numberOfHMetrics = count($this->subsettedGlyphs);
		$data = substr_replace($this->tables['hhea']['data'], pack('n',$numberOfHMetrics), 4+15*2, 2);
		$this->SetTable('hhea', $data);
	}

	function BuildHmtx()
	{
		$data = '';
		foreach($this->subsettedGlyphs as $id)
		{
			$glyph = $this->glyphs[$id];
			$data .= pack('nn', $glyph['w'], $glyph['lsb']);
		}
		$this->SetTable('hmtx', $data);
	}

	function BuildLoca()
	{
		$data = '';
		$offset = 0;
		foreach($this->subsettedGlyphs as $id)
		{
			if($this->indexToLocFormat==0)
				$data .= pack('n', $offset/2);
			else
				$data .= pack('N', $offset);
			$offset += $this->glyphs[$id]['length'];
		}
		if($this->indexToLocFormat==0)
			$data .= pack('n', $offset/2);
		else
			$data .= pack('N', $offset);
		$this->SetTable('loca', $data);
	}

	function BuildGlyf()
	{
		$tableOffset = $this->tables['glyf']['offset'];
		$data = '';
		foreach($this->subsettedGlyphs as $id)
		{
			$glyph = $this->glyphs[$id];
			fseek($this->f, $tableOffset+$glyph['offset'], SEEK_SET);
			$glyph_data = $this->Read($glyph['length']);
			if(isset($glyph['components']))
			{
				// Composite glyph
				foreach($glyph['components'] as $offset=>$cid)
				{
					$ssid = $this->glyphs[$cid]['ssid'];
					$glyph_data = substr_replace($glyph_data, pack('n',$ssid), $offset, 2);
				}
			}
			$data .= $glyph_data;
		}
		$this->SetTable('glyf', $data);
	}

	function BuildMaxp()
	{
		$this->LoadTable('maxp');
		$numGlyphs = count($this->subsettedGlyphs);
		$data = substr_replace($this->tables['maxp']['data'], pack('n',$numGlyphs), 4, 2);
		$this->SetTable('maxp', $data);
	}

	function BuildPost()
	{
		$this->Seek('post');
		if($this->glyphNames)
		{
			// Version 2.0
			$numberOfGlyphs = count($this->subsettedGlyphs);
			$numNames = 0;
			$names = '';
			$data = $this->Read(2*4+2*2+5*4);
			$data .= pack('n', $numberOfGlyphs);
			foreach($this->subsettedGlyphs as $id)
			{
				$name = $this->glyphs[$id]['name'];
				if(is_string($name))
				{
					$data .= pack('n', 258+$numNames);
					$names .= chr(strlen($name)).$name;
					$numNames++;
				}
				else
					$data .= pack('n', $name);
			}
			$data .= $names;
		}
		else
		{
			// Version 3.0
			$this->Skip(4);
			$data = "\x00\x03\x00\x00";
			$data .= $this->Read(4+2*2+5*4);
		}
		$this->SetTable('post', $data);
	}

	function BuildFont()
	{
		$tags = array();
		foreach(array('cmap', 'cvt ', 'fpgm', 'glyf', 'head', 'hhea', 'hmtx', 'loca', 'maxp', 'name', 'post', 'prep') as $tag)
		{
			if(isset($this->tables[$tag]))
				$tags[] = $tag;
		}
		$numTables = count($tags);
		$offset = 12 + 16*$numTables;
		foreach($tags as $tag)
		{
			if(!isset($this->tables[$tag]['data']))
				$this->LoadTable($tag);
			$this->tables[$tag]['offset'] = $offset;
			$offset += strlen($this->tables[$tag]['data']);
		}
//		$this->tables['head']['data'] = substr_replace($this->tables['head']['data'], "\x00\x00\x00\x00", 8, 4);

		// Build offset table
		$entrySelector = 0;
		$n = $numTables;
		while($n!=1)
		{
			$n = $n>>1;
			$entrySelector++;
		}
		$searchRange = 16*(1<<$entrySelector);
		$rangeShift = 16*$numTables - $searchRange;
		$offsetTable = pack('nnnnnn', 1, 0, $numTables, $searchRange, $entrySelector, $rangeShift);
		foreach($tags as $tag)
		{
			$table = $this->tables[$tag];
			$offsetTable .= $tag.$table['checkSum'].pack('NN', $table['offset'], $table['length']);
		}

		// Compute checkSumAdjustment (0xB1B0AFBA - font checkSum)
		$s = $this->CheckSum($offsetTable);
		foreach($tags as $tag)
			$s .= $this->tables[$tag]['checkSum'];
		$a = unpack('n2', $this->CheckSum($s));
		$high = 0xB1B0 + ($a[1]^0xFFFF);
		$low = 0xAFBA + ($a[2]^0xFFFF) + 1;
		$checkSumAdjustment = pack('nn', $high+($low>>16), $low);
		$this->tables['head']['data'] = substr_replace($this->tables['head']['data'], $checkSumAdjustment, 8, 4);

		$font = $offsetTable;
		foreach($tags as $tag)
			$font .= $this->tables[$tag]['data'];

		return $font;
	}

	function LoadTable($tag)
	{
		$this->Seek($tag);
		$length = $this->tables[$tag]['length'];
		$n = $length % 4;
		if($n>0)
			$length += 4 - $n;
		$this->tables[$tag]['data'] = $this->Read($length);
	}

	function SetTable($tag, $data)
	{
		$length = strlen($data);
		$n = $length % 4;
		if($n>0)
			$data = str_pad($data, $length+4-$n, "\x00");
		$this->tables[$tag]['data'] = $data;
		$this->tables[$tag]['length'] = $length;
		$this->tables[$tag]['checkSum'] = $this->CheckSum($data);
	}

	function Seek($tag)
	{
		if(!isset($this->tables[$tag]))
			$this->Error('Table not found: '.$tag);
		fseek($this->f, $this->tables[$tag]['offset'], SEEK_SET);
	}

	function Skip($n)
	{
		fseek($this->f, $n, SEEK_CUR);
	}

	function Read($n)
	{
		return $n>0 ? fread($this->f, $n) : '';
	}

	function ReadUShort()
	{
		$a = unpack('nn', fread($this->f,2));
		return $a['n'];
	}

	function ReadShort()
	{
		$a = unpack('nn', fread($this->f,2));
		$v = $a['n'];
		if($v>=0x8000)
			$v -= 65536;
		return $v;
	}

	function ReadULong()
	{
		$a = unpack('NN', fread($this->f,4));
		return $a['N'];
	}

	function CheckSum($s)
	{
		$n = strlen($s);
		$high = 0;
		$low = 0;
		for($i=0;$i<$n;$i+=4)
		{
			$high += (ord($s[$i])<<8) + ord($s[$i+1]);
			$low += (ord($s[$i+2])<<8) + ord($s[$i+3]);
		}
		return pack('nn', $high+($low>>16), $low);
	}

	function Error($msg)
	{
		throw new Exception($msg);
	}
}
?>
