<?php
/**
 * Interface for Zip libraries used in odtPHP
 * You need PHP 5.2 at least
 * You need Zip Extension or PclZip library
 * Encoding : ISO-8859-1
 *
 * @copyright  GPL License 2008 - Julien Pauli - Cyril PIERRE de GEYER - Anaska (http://www.anaska.com)
 * @license    http://www.gnu.org/copyleft/gpl.html  GPL License
 * @version 1.3
 */
interface ZipInterface
{
	/**
	 * Open a Zip archive
	 * 
	 * @param string $filename the name of the archive to open
	 * @return true if openning has succeeded
	 */	
	public function open($filename);
	/**
	 * Retrieve the content of a file within the archive from its name
	 * 
	 * @param string $name the name of the file to extract
	 * @return the content of the file in a string
	 */	
	public function getFromName($name);
	/**
	 * Add a file within the archive from a string
	 * 
	 * @param string $localname the local path to the file in the archive
	 * @param string $contents the content of the file
	 * @return true if the file has been successful added
	 */	
	public function addFromString($localname, $contents);
	/**
	 * Add a file within the archive from a file
	 * 
	 * @param string $filename the path to the file we want to add
	 * @param string $localname the local path to the file in the archive
	 * @return true if the file has been successful added
	 */	
	public function addFile($filename, $localname = null);
	/**
	 * Close the Zip archive
	 * @return true
	 */	
	public function close();
}
?>