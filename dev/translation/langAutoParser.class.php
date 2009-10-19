<?php
/**
 * This software is licensed under GPL license agreement
 * This is a language automatic translator parser for Dolibarr
 * This script uses google language ajax api as the translator engine
 * The main translator function can be found at:
 *
 * http://www.plentyofcode.com/2008/10/google-translation-api-translate-on.html
 * Hope you make a good use of it  :)
 *
 * http://code.google.com/intl/fr/apis/ajaxlanguage/documentation/#SupportedPairs
 */

class langAutoParser {

	private $translatedFiles = array();
	private $destLang = string;
	private $refLang = string;
	private $langDir = string;
	private $limittofile = string;
	private $outputpagecode = 'UTF-8';
	//private $outputpagecode = 'ISO-8859-1';
	const DIR_SEPARATOR = '/';

	function __construct($destLang,$refLang,$langDir,$limittofile){

		// Set enviorment variables
		$this->destLang = $destLang;
		$this->refLang = $refLang;
		$this->langDir = $langDir.self::DIR_SEPARATOR;
		$this->time = date('Y-m-d H:i:s');
		$this->limittofile = $limittofile;

		// Translate
		//ini_set('default_charset','UTF-8');
		ini_set('default_charset',$this->outputpagecode);
		$this->parseRefLangTranslationFiles();

	}

	private function parseRefLangTranslationFiles(){

		$files = $this->getTranslationFilesArray($this->refLang);
		$counter = 1;
		foreach($files as $file) {
			if ($this->limittofile && $this->limittofile != $file) continue;
			$counter++;
			$fileContent = null;
			$this->translatedFiles = array();
			$refPath = $this->langDir.$this->refLang.self::DIR_SEPARATOR.$file;
			$destPath = $this->langDir.$this->destLang.self::DIR_SEPARATOR.$file;
			$fileContent = file($refPath,FILE_IGNORE_NEW_LINES |
FILE_SKIP_EMPTY_LINES);
			print "Processing file " . $file . ", found ".sizeof($fileContent)." records<br>\n";
			// Check destination file presence
			if ( ! file_exists( $destPath ) ){
				// No file presente generate file
				echo "File not found: " . $file . "<br>\n";
				echo "Generating file " . $file . "<br>\n";
				$this->createTranslationFile($destPath);
			}
			// Translate lines
			$fileContentDest = file($destPath,FILE_IGNORE_NEW_LINES |
FILE_SKIP_EMPTY_LINES);
			$newlines=0;
			foreach($fileContent as $line){
				$key = $this->getLineKey($line);
				$value = $this->getLineValue($line);
				if ($key && $value)
				{
					$newlines+=$this->translateFileLine($fileContentDest,$file,$key,$value);
				}
			}

			$this->updateTranslationFile($destPath,$file);
			echo "New translated lines: " . $newlines . "<br>\n";
			#if ($counter ==3) die('fim');
		}
	}


	private function updateTranslationFile($destPath,$file){

		if (count($this->translatedFiles[$file])>0){
			$fp = fopen($destPath, 'a');
			fwrite($fp, "\r\n");
			fwrite($fp, "\r\n");
			fwrite($fp, "// START - Lines generated via autotranslator.php tool (".$this->time.").\r\n");
			fwrite($fp, "// Reference language: {$this->refLang}\r\n");
			foreach( $this->translatedFiles[$file] as $line) {
				fwrite($fp, $line . "\r\n");
			}
			fwrite($fp, "// STOP - Lines generated via autotranslator.php tool (".$this->time.").\r\n");
			fclose($fp);
		}
		return;
	}

	private function createTranslationFile($path){
		$fp = fopen($path, 'w+');
		fwrite($fp, "/*\r\n");
		fwrite($fp, " * Language code: {$this->destLang}\r\n");
		fwrite($fp, " * Automatic generated via autotranslator.php tool\r\n");
		fwrite($fp, " * Generation date " . $this->time. "\r\n");
		fwrite($fp, " */\r\n");
		fclose($fp);
		return;
	}

	/**
	 * Put in array translation of a key
	 *
	 * @param unknown_type $content		Existing content of dest file
	 * @param unknown_type $file		File name translated (xxxx.lang)
	 * @param unknown_type $key			Key to translate
	 * @param unknown_type $value		Existing key in source file
	 * @return	int						0=Nothing translated, 1=Record translated
	 */
	private function translateFileLine($content,$file,$key,$value){

		//print "key    =".$key."\n";
		foreach( $content as $line ) {
			$destKey = $this->getLineKey($line);
			$destValue = $this->getLineValue($line);
			// If translated return
			//print "destKey=".$destKey."\n";
			if ( trim($destKey) == trim($key) )
			{	// Found already existing translation
				 return 0;
			}
		}

		// If not translated then translate
		if ($this->outputpagecode == 'UTF-8') $val=$this->translateTexts(array($value),substr($this->refLang,0,2),substr($this->destLang,0,2));
		else $val=utf8_decode($this->translateTexts(array($value),substr($this->refLang,0,2),substr($this->destLang,0,2)));

		if ($key == 'CHARSET') $val=$this->outputpagecode;

		$this->translatedFiles[$file][] = $key . '=' . $val ;
		return 1;
	}


	private function getLineKey($line){
		$arraykey = split('=',$line,2);
		return trim( $arraykey[0] );
	}

	private function getLineValue($line){
		$arraykey = split('=',$line,2);
		return trim( $arraykey[1] );
	}

	private function getTranslationFilesArray($lang){
		$dir = new DirectoryIterator($this->langDir.$lang);
		while($dir->valid()) {
			if(!$dir->isDot() && $dir->isFile() && ! eregi('^\.',$dir->getFilename())) {
				$files[] =  $dir->getFilename();
			}
			$dir->next();
		}
		return $files;
	}

	private function translateTexts($src_texts = array(), $src_lang,
$dest_lang){

		$tmp=explode('_',$src_lang);
		if ($tmp[0] == $tmp[1]) $src_lang=$tmp[0];

		$tmp=explode('_',$dest_lang);
		if ($tmp[0] == $tmp[1]) $dest_lang=$tmp[0];

		//setting language pair
		$lang_pair = $src_lang.'|'.$dest_lang;

		$src_texts_query = "";
		foreach ($src_texts as $src_text){
			$src_texts_query .= "&q=".urlencode($src_text);
		}

		$url =
"http://ajax.googleapis.com/ajax/services/language/translate?v=1.0".$src_texts_query."&langpair=".urlencode($lang_pair);

		// sendRequest
		// note how referer is set manually

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_REFERER, "http://www.YOURWEBSITE.com");
		$body = curl_exec($ch);
		curl_close($ch);

		// now, process the JSON string
		$json = json_decode($body, true);

		if ($json['responseStatus'] != 200){
			return false;
		}

		$results = $json['responseData'];

		$return_array = array();

		foreach ($results as $result){
			if ($result['responseStatus'] == 200){
				$return_array[] = $result['responseData']['translatedText'];
			} else {
				$return_array[] = false;
			}
		}

		//return translated text
		#return $return_array;
		return $json['responseData']['translatedText'];
	}

}
?>