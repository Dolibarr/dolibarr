<?php
/* Copyright (C) 2009-2012 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *      \file       dev/translation/langAutoParser.class.php
 *		\ingroup    dev
 *      \brief      This file is an example for a command line script
 */

/**
 * Class to parse language files and translate them
 * This is a language automatic translator parser for Dolibarr
 */
class autoTranslator
{
	private $translatedFiles = array();
	private $destLang = '';
	private $refLang = '';
	private $langDir = '';
	private $limittofile = '';
	private $time;
	private $time_end;
	private $outputpagecode = 'UTF-8';
	private $apikey;
	//private $outputpagecode = 'ISO-8859-1';
	const DIR_SEPARATOR = '/';


	function __construct($destLang,$refLang,$langDir,$limittofile,$apikey)
	{

		// Set enviorment variables
		$this->destLang = $destLang;
		$this->refLang = $refLang;
		$this->langDir = $langDir.self::DIR_SEPARATOR;
		$this->time = date('Y-m-d H:i:s');
		$this->limittofile = $limittofile;
        $this->apikey = $apikey;

		// Translate
		//ini_set('default_charset','UTF-8');
		ini_set('default_charset',$this->outputpagecode);
		$this->parseRefLangTranslationFiles();

	}

	/**
	 * 	Parse file
	 *
	 * 	@return	void
	 */
	private function parseRefLangTranslationFiles()
	{

		$files = $this->getTranslationFilesArray($this->refLang);
		$counter = 1;
		foreach($files as $file)
		{
			if ($this->limittofile && $this->limittofile != $file) continue;
			$counter++;
			$fileContent = null;
			$refPath = $this->langDir.$this->refLang.self::DIR_SEPARATOR.$file;
			$fileContent = file($refPath,FILE_IGNORE_NEW_LINES|FILE_SKIP_EMPTY_LINES);
			print "Processing file " . $file . ", with ".count($fileContent)." lines<br>\n";

			// Define target dirs
			$targetlangs=array($this->destLang);
			if ($this->destLang == 'all')
			{
				$targetlangs=array();

				// If we must process all languages
				$arraytmp=dol_dir_list($this->langDir,'directories',0);
				foreach($arraytmp as $dirtmp)
				{
					if ($dirtmp['name'] === $this->refLang) continue;	// We discard source language
					$tmppart=explode('_',$dirtmp['name']);
					if (preg_match('/^en/i',$dirtmp['name']))  continue;	// We discard en_* languages
					if (preg_match('/^fr/i',$dirtmp['name']))  continue;	// We discard fr_* languages
					if (preg_match('/^es/i',$dirtmp['name']))  continue;	// We discard es_* languages
					if (preg_match('/ca_ES/i',$dirtmp['name']))  continue;	// We discard es_CA language
					if (preg_match('/pt_BR/i',$dirtmp['name']))  continue;	// We discard pt_BR language
                    if (preg_match('/nl_BE/i',$dirtmp['name']))  continue;  // We discard nl_BE language
					if (preg_match('/^\./i',$dirtmp['name']))  continue;	// We discard files .*
					if (preg_match('/^CVS/i',$dirtmp['name']))  continue;	// We discard CVS
					$targetlangs[]=$dirtmp['name'];
				}
				//var_dump($targetlangs);
			}

			// Process translation of source file for each target languages
			foreach($targetlangs as $mydestLang)
			{
				$this->translatedFiles = array();

				$destPath = $this->langDir.$mydestLang.self::DIR_SEPARATOR.$file;
				// Check destination file presence
				if (! file_exists($destPath))
				{
					// No file present, we generate file
					echo "File not found: " . $destPath . ". We generate it.<br>\n";
					$this->createTranslationFile($destPath,$mydestLang);
				}
				else
				{
					echo "Updating file: " . $destPath . "<br>\n";
				}

				// Translate lines
				$fileContentDest = file($destPath,FILE_IGNORE_NEW_LINES|FILE_SKIP_EMPTY_LINES);
				$newlines=0;
				foreach($fileContent as $line){
					$key = $this->getLineKey($line);
					$value = $this->getLineValue($line);
					if ($key && $value)
					{
						$newlines+=$this->translateFileLine($fileContentDest,$file,$key,$value,$mydestLang);
					}
				}

				$this->updateTranslationFile($destPath,$file,$mydestLang);
				echo "New translated lines: " . $newlines . "<br>\n";
				//if ($counter ==3) die('fim');
			}
		}
	}

	/**
	 * Update file with new translations
	 *
	 * @param 	string	$destPath		Target path
	 * @param 	string	$file			File
	 * @param 	string	$mydestLang		Target language code
	 * @return	void
	 */
	private function updateTranslationFile($destPath,$file,$mydestLang)
	{
		$this->time_end = date('Y-m-d H:i:s');

		if (count($this->translatedFiles[$file])>0)
		{
			$fp = fopen($destPath, 'a');
			fwrite($fp, "\r\n");
			fwrite($fp, "\r\n");
			fwrite($fp, "// START - Lines generated via autotranslator.php tool (".$this->time.").\r\n");
			fwrite($fp, "// Reference language: ".$this->refLang." -> ".$mydestLang."\r\n");
			foreach( $this->translatedFiles[$file] as $line) {
				fwrite($fp, $line . "\r\n");
			}
			fwrite($fp, "// STOP - Lines generated via autotranslator.php tool (".$this->time_end.").\r\n");
			fclose($fp);
		}
		return;
	}

	/**
	 * Create a new translation file
	 *
	 * @param 	string	$path			Path
	 * @param 	string	$mydestlang		Target language code
	 * @return	void
	 */
	private function createTranslationFile($path,$mydestlang)
	{
		$fp = fopen($path, 'w+');
		fwrite($fp, "/*\r\n");
		fwrite($fp, " * Language code: {$mydestlang}\r\n");
		fwrite($fp, " * Automatic generated via autotranslator.php tool\r\n");
		fwrite($fp, " * Generation date " . $this->time. "\r\n");
		fwrite($fp, " */\r\n");
		fclose($fp);
		return;
	}

	/**
	 * Put in array translatedFiles[$file], line of a new tranlated pair
	 *
	 * @param 	string	$content		Existing content of dest file
	 * @param 	string	$file			Target file name translated (xxxx.lang)
	 * @param 	string	$key			Key to translate
	 * @param 	string	$value			Existing value in source file
	 * @param	string	$mydestLang		Language code (ie: fr_FR)
	 * @return	int						0=Nothing translated, 1=Record translated
	 */
	private function translateFileLine($content,$file,$key,$value,$mydestLang)
	{

		//print "key    =".$key."\n";
		foreach( $content as $line ) {
			$destKey = $this->getLineKey($line);
			$destValue = $this->getLineValue($line);
			// If translated return
			//print "destKey=".$destKey."\n";
			if ( trim($destKey) == trim($key) )
			{	// Found already existing translation (key already exits in dest file)
				return 0;
			}
		}

		if ($key == 'CHARSET') $val=$this->outputpagecode;
		else if (preg_match('/^Format/',$key)) $val=$value;
		else if ($value=='-') $val=$value;
		else
		{
			// If not translated then translate
			if ($this->outputpagecode == 'UTF-8') $val=$this->translateTexts(array($value),substr($this->refLang,0,2),substr($mydestLang,0,2));
			else $val=utf8_decode($this->translateTexts(array($value),substr($this->refLang,0,2),substr($mydestLang,0,2)));
		}

		$val=trim($val);

		if (empty($val)) return 0;

		$this->translatedFiles[$file][] = $key . '=' . $val ;
		return 1;
	}

	/**
	 * getLineKey
	 *
	 * @param 	string 	$line	Line found into file
	 * @return	string	Key
	 */
	private function getLineKey($line)
	{
		$arraykey = explode('=',$line,2);
		return trim($arraykey[0]);
	}

	/**
	 * getLineValue
	 *
	 * @param 	string 	$line	Line found into file
	 * @return	string	Value
	 */
	private function getLineValue($line)
	{
		$arraykey = explode('=',$line,2);
		return trim($arraykey[1]);
	}

	/**
	 * getTranslationFilesArray
	 *
	 * @param 	string 	$lang	Language code
	 * @return	array	Array
	 */
	private function getTranslationFilesArray($lang)
	{
		$dir = new DirectoryIterator($this->langDir.$lang);
		while($dir->valid()) {
			if(!$dir->isDot() && $dir->isFile() && ! preg_match('/^\./',$dir->getFilename())) {
				$files[] =  $dir->getFilename();
			}
			$dir->next();
		}
		return $files;
	}

	/**
	 * Return translation of a value
	 *
	 * @param 	array	$src_texts		Array with one value
	 * @param 	string	$src_lang		Language code source
	 * @param 	string	$dest_lang		Language code target
	 * @return 	string					Value translated
	 */
	private function translateTexts($src_texts, $src_lang, $dest_lang)
	{
		$tmp=explode('_',$src_lang);
		if ($tmp[0] == $tmp[1]) $src_lang=$tmp[0];

		$tmp=explode('_',$dest_lang);
		if ($tmp[0] == $tmp[1]) $dest_lang=$tmp[0];

		//setting language pair
		$lang_pair = $src_lang.'|'.$dest_lang;

		$src_text_to_translate=preg_replace('/%s/','SSSSS',join('',$src_texts));
		$src_text_to_translate=preg_replace('/'.preg_quote('\n\n').'/',' NNNNN ',$src_text_to_translate);

		// Define GET URL v1
		//$url = "http://ajax.googleapis.com/ajax/services/language/translate?v=1.0&q=".urlencode($src_text_to_translate)."&langpair=".urlencode($lang_pair);
		// Example: http://ajax.googleapis.com/ajax/services/language/translate?v=1.0&q=Setup%20area&langpair=en_US|fr_FR
        // Define GET URL v2
		$url = "https://www.googleapis.com/language/translate/v2?key=".$this->apikey."&q=".urlencode($src_text_to_translate)."&source=".urlencode($src_lang)."&target=".urlencode($dest_lang);
		// Example: https://www.googleapis.com/language/translate/v2?key=APIKEY&q=Setup%20area&source=en_US&target=fr_FR

		// Send request
		print "Url to translate: ".$url."\n";

		if (! function_exists("curl_init"))
		{
		      print "Error, your PHP does not support curl functions.\n";
		      die();
		}

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_REFERER, "Mozilla");
		$body = curl_exec($ch);
		curl_close($ch);
		sleep(6);	// This is to avoid to overload server. Best value is 6.

		// now, process the JSON string
		$json = json_decode($body, true);

		if ($json['responseStatus'] != 200)
		{
			print "Error: ".$json['responseStatus']." ".$url."\n";
			return false;
		}

		$rep=$json['responseData']['translatedText'];
		$rep=preg_replace('/SSSSS/i','%s',$rep);
		$rep=preg_replace('/NNNNN/i','\n\n',$rep);
		$rep=preg_replace('/&#39;/i','\'',$rep);

		//print "OK ".join('',$src_texts).' => '.$rep."\n";

		return $rep;
	}

}
?>
