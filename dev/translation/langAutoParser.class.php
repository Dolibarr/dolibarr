<?php
/**
 * This software is licensed under GPL license agreement
 * This is a language automatic translator parser for Dolibarr
 * This script uses google language ajax api as the translator engine
 * The main translator function can be found at:
 *
 * http://www.plentyofcode.com/2008/10/google-translation-api-translate-on.html
 * Hope you make a good use of it  :)
 */

class langAutoParser {

	private $translatedFiles = array();
	private $destLang = string;
	private $refLang = string;
	private $langDir = string;

	const DIR_SEPARATOR = '/';

	function __construct($destLang,$refLang,$langDir){

		// Set enviorment variables
		$this->destLang = $destLang;
		$this->refLang = $refLang;
		$this->langDir = $langDir.self::DIR_SEPARATOR;
		$this->time = date('Y-m-d H:i:s');

		// Translate
		ini_set('default_charset','UTF-8');
		//ini_set('default_charset','ISO-8859-1');
		$this->parseRefLangTranslationFiles();

	}

	private function parseRefLangTranslationFiles(){

		$files = $this->getTranslationFilesArray($this->refLang);
		$counter = 1;
		foreach($files as $file) {
			$counter++;
			$fileContent = null;
			$this->translatedFiles = array();
			$refPath = $this->langDir.$this->refLang.self::DIR_SEPARATOR.$file;
			$destPath = $this->langDir.$this->destLang.self::DIR_SEPARATOR.$file;
			$fileContent = file($refPath,FILE_IGNORE_NEW_LINES |
FILE_SKIP_EMPTY_LINES);
			print "Processing file " . $file . "<br>\n";
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
			foreach($fileContent as $line){
				$key = $this->getLineKey($line);
				$value = $this->getLineValue($line);
				$this->translateFileLine($fileContentDest,$file,$key,$value);
			}

			$this->updateTranslationFile($destPath,$file);
			#if ($counter ==3) die('fim');
		}
	}


	private function updateTranslationFile($destPath,$file){

		if (count($this->translatedFiles[$file])>0){
			$fp = fopen($destPath, 'a');
			fwrite($fp, "\r\n");
			fwrite($fp, "\r\n");
			fwrite($fp, "// Date " . $this->time . "\r\n");
			fwrite($fp, "// START - Lines generated via autotranslator.php tool.\r\n");
			fwrite($fp, "// Reference language: {$this->refLang}\r\n");
			foreach( $this->translatedFiles[$file] as $line) {
				fwrite($fp, $line . "\r\n");
			}
			fwrite($fp, "// Date " . $this->time . "\r\n");
			fwrite($fp, "// STOP - Lines generated via parser\r\n");
			fclose($fp);
		}
		return;
	}

	private function createTranslationFile($path){
		$fp = fopen($path, 'w+');
		fwrite($fp, "/*\r\n");
		fwrite($fp, " * Lince Translation File\r\n");
		fwrite($fp, " * Filename: {$file}\r\n");
		fwrite($fp, " * Language code: {$this->destLang}\r\n");
		fwrite($fp, " * Automatic generated via autotranslator tool\r\n");
		fwrite($fp, " * Generation date " . $this->time. "\r\n");
		fwrite($fp, " */\r\n");
		fclose($fp);
		return;
	}

	private function translateFileLine($content,$file,$key,$value){

		foreach( $content as $line ) {
			$destKey = $this->getLineKey($line);
			$destValue = $this->getLineValue($line);
			// If translated return
			if ( $destKey == $key ) { return; }
		}

		// If not translated then translate
		$this->translatedFiles[$file][] = $key . '=' .
utf8_decode($this->translateTexts(array($value),substr($this->refLang,0,2),substr($this->destLang,0,2)));

	}


	private function getLineKey($line){
		$key = preg_match('/^(.*)=/',$line,$matches);
		return trim( $matches[1] );
	}

	private function getLineValue($line){
		$value = preg_match('/=(.*)$/',$line,$matches);
		return trim( $matches[1] );
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